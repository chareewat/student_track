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
    $items = [['key' => 'home', 'icon' => 'home', 'label' => 'หน้าหลัก', 'url' => trk_url('index.php')]];
    if (trk_sees_all_rooms()) {
        $items[] = ['key' => 'dashboard', 'icon' => 'dashboard', 'label' => 'ภาพรวม', 'url' => trk_url('dashboard/index.php')];
    }
    if (trk_advisor_classrooms($user['id'], trk_semester_id())) {
        $pending = (int) trk_db_value(
            "SELECT COUNT(*) FROM trk_rounds r JOIN std_advisor a
               ON a.std_classroom_id = r.std_classroom_id AND a.std_semester_id = r.semester_id AND a.psn_mem_id = ?
             WHERE r.semester_id = ? AND r.status = 'open'",
            [$user['id'], trk_semester_id()]
        );
        $items[] = ['key' => 'advisor', 'icon' => 'clipboard', 'label' => 'งานครูที่ปรึกษา', 'count' => $pending, 'url' => trk_url('advisor/index.php')];
        $case2 = 0;
        foreach (trk_advisor_classrooms($user['id'], trk_semester_id()) as $room) {
            foreach (trk_case2_candidates((int) $room['std_classroom_id'], trk_semester_id()) as $c) {
                $case2 += $c['eligible'] ? 1 : 0;
            }
        }
        $items[] = ['key' => 'case2', 'icon' => 'calendar-x', 'label' => 'นักเรียนขาดติดต่อกัน', 'count' => $case2, 'url' => trk_url('advisor/case2.php')];
        $case3 = 0;
        foreach (trk_advisor_classrooms($user['id'], trk_semester_id()) as $room) {
            foreach (trk_case3_candidates((int) $room['std_classroom_id'], trk_semester_id()) as $c) {
                $case3 += $c['eligible'] ? 1 : 0;
            }
        }
        $items[] = ['key' => 'case3', 'icon' => 'user-x', 'label' => 'เสนอจำหน่าย', 'count' => $case3, 'url' => trk_url('advisor/case3.php')];
    }
    if (trk_teaching_assignments($user['id'], trk_semester_id())) {
        $items[] = ['key' => 'case1', 'icon' => 'book', 'label' => 'นักเรียนขาดรายวิชา', 'url' => trk_url('case1/index.php')];
    }
    if (trk_sees_all_rooms()) {
        $items[] = ['key' => 'kr6', 'icon' => 'file-warning', 'label' => 'ขร.6 เสนอจำหน่าย', 'url' => trk_url('academic/kr6.php')];
    }
    $unread = trk_unread_count($user['id']);
    $items[] = ['key' => 'notifications', 'icon' => 'bell', 'label' => 'แจ้งเตือน', 'count' => $unread, 'url' => trk_url('notifications.php')];
    if (trk_has_role('admin')) {
        $items[] = ['key' => 'roles', 'icon' => 'shield', 'label' => 'จัดการสิทธิ์', 'url' => trk_url('admin/roles.php')];
        $items[] = ['key' => 'settings', 'icon' => 'sliders', 'label' => 'ตั้งค่า', 'url' => trk_url('admin/settings.php')];
        $items[] = ['key' => 'system', 'icon' => 'activity', 'label' => 'ตรวจสอบระบบ', 'url' => trk_url('admin/system.php')];
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
    echo '<div class="card mx-auto mt-6 max-w-lg p-8 text-center">'
        . '<div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-canvas text-navy">' . trk_icon('info', 'h-7 w-7') . '</div>'
        . '<h1 class="mt-4 text-lg font-semibold">' . e($title) . '</h1>'
        . '<p class="hint mt-2">' . e($message) . '</p>'
        . '<a href="' . e(trk_url('index.php')) . '" class="btn btn-navy mt-6">' . trk_icon('home') . 'กลับหน้าหลัก</a>'
        . '</div>';
    trk_render_footer();
}

function trk_role_badges(array $roles): string
{
    $colors = [
        'admin' => 'chip-red',
        'teacher' => 'chip-sand',
        'executive' => 'chip-yellow',
        'academic' => 'chip-blue',
        'affairs' => 'chip-green',
    ];
    $html = '';
    foreach ($roles as $role) {
        $html .= '<span class="' . ($colors[$role] ?? 'chip-sand') . '">' . e(TRK_ROLES[$role] ?? $role) . '</span> ';
    }
    return $html;
}
