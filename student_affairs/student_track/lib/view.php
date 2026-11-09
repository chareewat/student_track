<?php
/**
 * ตัวช่วยหน้าเว็บ: escape, CSRF, flash message, layout
 */

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

/** URL ของโมดูล เช่น /student_track/student_affairs/student_track/ (ใช้ได้ทั้งบนเครื่องและ server) */
function trk_url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $marker = '/student_affairs/student_track/';
        $pos = strpos($script, $marker);
        $base = $pos === false ? '/' : substr($script, 0, $pos) . $marker;
    }
    return $base . ltrim($path, '/');
}

/** URL ของระบบเดิมระดับ root เช่น lobby.php */
function trk_legacy_url(string $path): string
{
    return substr(trk_url(), 0, -strlen('student_affairs/student_track/')) . ltrim($path, '/');
}

function trk_redirect(string $path): void
{
    header('Location: ' . trk_url($path));
    exit;
}

// ---------------------------------------------------------------- CSRF

function trk_csrf_token(): string
{
    if (empty($_SESSION['trk_csrf'])) {
        $_SESSION['trk_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['trk_csrf'];
}

function trk_csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(trk_csrf_token()) . '">';
}

/** เรียกที่ต้นทุก POST */
function trk_csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(trk_csrf_token(), $sent)) {
        http_response_code(400);
        trk_render_message('ฟอร์มหมดอายุ', 'กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง');
        exit;
    }
}

function trk_is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

// ---------------------------------------------------------------- flash

function trk_flash(string $type, string $message): void
{
    $_SESSION['trk_flash'][] = ['type' => $type, 'message' => $message];
}

function trk_take_flashes(): array
{
    $flashes = $_SESSION['trk_flash'] ?? [];
    unset($_SESSION['trk_flash']);
    return $flashes;
}

// ---------------------------------------------------------------- layout

/** เมนูตาม role (เพิ่มเมนูของแต่ละเฟสที่นี่) */
function trk_menu(): array
{
    $user = trk_user();
    $items = [['key' => 'home', 'label' => 'หน้าหลัก', 'url' => trk_url('index.php')]];
    if (trk_advisor_classrooms($user['id'], trk_semester_id())) {
        $pending = (int) trk_db_value(
            "SELECT COUNT(*) FROM trk_rounds r JOIN std_advisor a
               ON a.std_classroom_id = r.std_classroom_id AND a.std_semester_id = r.semester_id AND a.psn_mem_id = ?
             WHERE r.semester_id = ? AND r.status = 'open'",
            [$user['id'], trk_semester_id()]
        );
        $items[] = ['key' => 'advisor', 'label' => 'งานครูที่ปรึกษา' . ($pending ? " ($pending)" : ''), 'url' => trk_url('advisor/index.php')];
        $case2 = 0;
        foreach (trk_advisor_classrooms($user['id'], trk_semester_id()) as $room) {
            foreach (trk_case2_candidates((int) $room['std_classroom_id'], trk_semester_id()) as $c) {
                $case2 += $c['eligible'] ? 1 : 0;
            }
        }
        $items[] = ['key' => 'case2', 'label' => 'นักเรียนขาดติดต่อกัน' . ($case2 ? " ($case2)" : ''), 'url' => trk_url('advisor/case2.php')];
    }
    if (trk_teaching_assignments($user['id'], trk_semester_id())) {
        $items[] = ['key' => 'case1', 'label' => 'นักเรียนขาดรายวิชา', 'url' => trk_url('case1/index.php')];
    }
    $unread = trk_unread_count($user['id']);
    $items[] = ['key' => 'notifications', 'label' => 'แจ้งเตือน' . ($unread ? " ($unread)" : ''), 'url' => trk_url('notifications.php')];
    if (trk_has_role('admin')) {
        $items[] = ['key' => 'roles', 'label' => 'จัดการสิทธิ์', 'url' => trk_url('admin/roles.php')];
        $items[] = ['key' => 'settings', 'label' => 'ตั้งค่า', 'url' => trk_url('admin/settings.php')];
    }
    return $items;
}

function trk_render_header(string $title, string $active = ''): void
{
    $user = trk_user();
    require TRK_ROOT . '/views/layout_header.php';
}

function trk_render_footer(): void
{
    require TRK_ROOT . '/views/layout_footer.php';
}

/** หน้าแจ้งข้อความเต็มหน้า (เช่น ไม่มีสิทธิ์) */
function trk_render_message(string $title, string $message): void
{
    trk_render_header($title);
    echo '<div class="mx-auto max-w-lg rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">'
        . '<h1 class="text-lg font-semibold text-slate-800">' . e($title) . '</h1>'
        . '<p class="mt-2 text-slate-600">' . e($message) . '</p>'
        . '<a href="' . e(trk_url('index.php')) . '" class="mt-6 inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">กลับหน้าหลัก</a>'
        . '</div>';
    trk_render_footer();
}

function trk_role_badges(array $roles): string
{
    $colors = [
        'admin' => 'bg-rose-100 text-rose-700',
        'teacher' => 'bg-slate-100 text-slate-700',
        'executive' => 'bg-amber-100 text-amber-800',
        'academic' => 'bg-blue-100 text-blue-700',
        'affairs' => 'bg-emerald-100 text-emerald-700',
    ];
    $html = '';
    foreach ($roles as $role) {
        $html .= '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium '
            . ($colors[$role] ?? 'bg-slate-100 text-slate-700') . '">' . e(TRK_ROLES[$role] ?? $role) . '</span> ';
    }
    return $html;
}
