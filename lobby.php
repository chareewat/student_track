<?php
/**
 * Modern school services lobby (PHP 7.x compatible)
 *
 * Layout changes made by administrators are stored in lobby_layout.json.
 * Ensure that the web-server user can write to this directory.
 */
require_once __DIR__ . '/dbconfig.inc.php';
require_once __DIR__ . '/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION['userimgadd'] = true;

if (empty($_SESSION['lobby_csrf'])) {
    $_SESSION['lobby_csrf'] = bin2hex(random_bytes(32));
}

$isAdmin = isset($_SESSION['admin_type_id']) && in_array((int) $_SESSION['admin_type_id'], array(1, 99), true);
$layoutFile = __DIR__ . '/lobby_layout.json';

$defaultGroups = array(
    array('id' => 'student-care', 'name' => 'ดูแลและติดตามนักเรียน', 'icon' => 'fa-heart', 'description' => 'ข้อมูล การมาเรียน พฤติกรรม และการดูแลช่วยเหลือ'),
    array('id' => 'academic', 'name' => 'งานวิชาการ', 'icon' => 'fa-graduation-cap', 'description' => 'ผลการเรียน ตารางสอน การสอบ และกิจกรรมวิชาการ'),
    array('id' => 'staff-office', 'name' => 'บุคลากรและงานสำนักงาน', 'icon' => 'fa-briefcase', 'description' => 'งานบุคลากร หนังสือราชการ และระบบสนับสนุน')
);

$defaultApps = array(
    array('id'=>'student-info','group'=>'student-care','name'=>'ข้อมูลนักเรียน','description'=>'ค้นหาและดูข้อมูลนักเรียน','url'=>'studentcenter/index.php','icon'=>'fa-users','color'=>'sky'),
    array('id'=>'school-checkin','group'=>'student-care','name'=>'มาโรงเรียน','description'=>'บันทึกและตรวจสอบการมาโรงเรียน','url'=>'student_affairs/checkinrow/','icon'=>'fa-calendar-check-o','color'=>'emerald'),
    array('id'=>'class-checkin','group'=>'student-care','name'=>'เข้าเรียนวิชาพื้นฐาน','description'=>'ตรวจสอบการเข้าเรียนรายคาบ','url'=>'student_affairs/checkinclass/','icon'=>'fa-check-square-o','color'=>'indigo'),
    array('id'=>'schedule-checkin','group'=>'student-care','name'=>'เช็กชื่อด้วยตารางสอน','description'=>'เช็กชื่อเข้าเรียนจากตารางสอน','url'=>'teacher/src/checkinclass.php?m=5','icon'=>'fa-clock-o','color'=>'violet','tag'=>'ใหม่'),
    array('id'=>'student-track','group'=>'student-care','name'=>'บันทึกติดตามนักเรียน','description'=>'บันทึกข้อมูลการติดตามรายบุคคล','url'=>'student_affairs/advisor/studenttrack.php','icon'=>'fa-map-marker','color'=>'rose'),
    array('id'=>'student-track-report','group'=>'student-care','name'=>'รายงานการติดตาม','description'=>'ดูรายงานและผลการติดตามนักเรียน','url'=>'student_affairs/advisor/studenttrack_report.php','icon'=>'fa-line-chart','color'=>'amber'),
    array('id'=>'advisor','group'=>'student-care','name'=>'ข้อมูลครูที่ปรึกษา','description'=>'ข้อมูลนักเรียนและครูที่ปรึกษา','url'=>'student_affairs/advisor/','icon'=>'fa-user-circle-o','color'=>'cyan'),
    array('id'=>'behavior','group'=>'student-care','name'=>'พฤติกรรมนักเรียน','description'=>'บันทึกและติดตามพฤติกรรม','url'=>'student_affairs/new_behavior/','icon'=>'fa-child','color'=>'orange'),
    array('id'=>'home-visit','group'=>'student-care','name'=>'บันทึกเยี่ยมบ้าน','description'=>'บันทึกข้อมูลการเยี่ยมบ้านนักเรียน','url'=>'student_affairs/advisor/homevisit.php','icon'=>'fa-home','color'=>'teal'),
    array('id'=>'home-visit-summary','group'=>'student-care','name'=>'สรุปการเยี่ยมบ้าน','description'=>'ดูร้อยละและความก้าวหน้าการเยี่ยมบ้าน','url'=>'student_affairs/advisor/homevisitgo.php','icon'=>'fa-pie-chart','color'=>'blue'),
    array('id'=>'student-referral','group'=>'student-care','name'=>'ส่งต่อนักเรียน','description'=>'บันทึกการส่งต่อและดูแลช่วยเหลือ','url'=>'student_affairs/sc','icon'=>'fa-share-square-o','color'=>'pink'),

    array('id'=>'rally','group'=>'academic','name'=>'กิจกรรมชุมนุม','description'=>'จัดการกิจกรรมและสมาชิกชุมนุม','url'=>'student_affairs/rally/','icon'=>'fa-flag','color'=>'purple'),
    array('id'=>'gradebook','group'=>'academic','name'=>'ผลการเรียน','description'=>'ดูและจัดการผลการเรียน','url'=>'gradebook/index.php','icon'=>'fa-book','color'=>'blue'),
    array('id'=>'grade-fix','group'=>'academic','name'=>'แก้ไขผลการเรียน 0 ร มส.','description'=>'ติดตามกระบวนการแก้ไขผลการเรียน','url'=>'grade_fix/index.php','icon'=>'fa-refresh','color'=>'red','badge'=>'grade_fix'),
    array('id'=>'activity-permission','group'=>'academic','name'=>'ระบบขอเวลาเรียน','description'=>'จัดการคำร้องขอเวลาเรียนนักเรียน','url'=>'activity_permission/page/check_user_type.php','icon'=>'fa-file-text-o','color'=>'amber','badge'=>'activity_permission'),
    array('id'=>'teaching-table','group'=>'academic','name'=>'ตารางเรียน / ตารางสอน','description'=>'ดูตารางเรียนและตารางสอน','url'=>'teacher/src/teachtable.php?m=1','icon'=>'fa-table','color'=>'cyan'),
    array('id'=>'subject-score','group'=>'academic','name'=>'คะแนนรายวิชา','description'=>'บันทึกและตรวจสอบคะแนนรายวิชา','url'=>'student_score/login.php','icon'=>'fa-star-o','color'=>'yellow'),
    array('id'=>'substitute','group'=>'academic','name'=>'สอนแทน / แลกคาบ','description'=>'ส่งและติดตามคำขอสอนแทนหรือแลกคาบ','url'=>'https://rwsservice.online/substitute_teaching/login.php','icon'=>'fa-exchange','color'=>'fuchsia','badge'=>'substitute'),
    array('id'=>'exam','group'=>'academic','name'=>'จัดการตารางสอบ','description'=>'สำรวจและจัดการตารางสอบ','url'=>'teacher/studentsubjectscore/examsurvey.php?m=3','icon'=>'fa-pencil-square-o','color'=>'lime'),
    array('id'=>'achievement','group'=>'academic','name'=>'รางวัล / เกียรติบัตร','description'=>'บันทึกผลงาน รางวัล และเกียรติบัตร','url'=>'ach/index.php','icon'=>'fa-trophy','color'=>'orange','badge'=>'achievement'),

    array('id'=>'teacher-attendance','group'=>'staff-office','name'=>'วันปฏิบัติราชการ','description'=>'ตรวจสอบวันลาและวันปฏิบัติราชการ','url'=>'teacher_attendance/index.php','icon'=>'fa-address-card-o','color'=>'emerald'),
    array('id'=>'duty-report','group'=>'staff-office','name'=>'เวรประจำ','description'=>'บันทึกและตรวจสอบเวรประจำ','url'=>'https://rwsservice.online/duty_report/index.php','icon'=>'fa-shield','color'=>'slate'),
    array('id'=>'edocument','group'=>'staff-office','name'=>'E-Office','description'=>'ระบบรับส่งและจัดการหนังสือราชการ','url'=>'edocument','icon'=>'fa-envelope-open-o','color'=>'indigo','badge'=>'edocument'),
    array('id'=>'tablet','group'=>'staff-office','name'=>'บริหารจัดการแท็บเล็ต','description'=>'ข้อมูลอุปกรณ์และการจัดสรรแท็บเล็ต','url'=>'tablet','icon'=>'fa-tablet','color'=>'sky'),
    array('id'=>'admin','group'=>'staff-office','name'=>'ตั้งค่าระบบ','description'=>'ตั้งค่าข้อมูลและสิทธิ์ของระบบ','url'=>'https://rwsservice.online/admin/settingpage.php','icon'=>'fa-cog','color'=>'zinc','adminOnly'=>true)
);

function lobby_json_response($status, $payload, $httpCode)
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(array('success' => $status), $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function lobby_clean_text($value, $maxLength)
{
    $value = trim(strip_tags((string) $value));
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength, 'UTF-8') : substr($value, 0, $maxLength);
}

function lobby_clean_url($value)
{
    $value = trim((string) $value);
    if ($value === '' || preg_match('/[\x00-\x1F\x7F]/', $value)) { return ''; }
    if (preg_match('#^https?://#i', $value)) {
        return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
    }
    // Local links only: prohibit schemes, protocol-relative URLs and traversal.
    if (strpos($value, ':') !== false || strpos($value, '//') === 0 || preg_match('#(^|/)\.\.(/|$)#', $value)) { return ''; }
    return ltrim($value, '/');
}

function lobby_load_layout($file, $defaultGroups, $defaultApps)
{
    if (!is_file($file) || !is_readable($file)) {
        return array('groups' => $defaultGroups, 'apps' => $defaultApps);
    }
    $saved = json_decode((string) file_get_contents($file), true);
    if (!is_array($saved) || !isset($saved['groups'], $saved['apps']) || !is_array($saved['groups']) || !is_array($saved['apps'])) {
        return array('groups' => $defaultGroups, 'apps' => $defaultApps);
    }

    // Built-in application metadata stays in source; administrator-created cards are persisted.
    $appSettings = array();
    foreach ($saved['apps'] as $item) {
        if (is_array($item) && isset($item['id'])) {
            $appSettings[(string) $item['id']] = $item;
        }
    }
    $apps = array();
    foreach ($defaultApps as $app) {
        if (isset($appSettings[$app['id']])) {
            $setting = $appSettings[$app['id']];
            $app['group'] = isset($setting['group']) ? (string) $setting['group'] : $app['group'];
            $app['hidden'] = !empty($setting['hidden']);
            $app['order'] = isset($setting['order']) ? (int) $setting['order'] : 999;
        } else {
            $app['hidden'] = false;
            $app['order'] = 999;
        }
        // The administrator settings entry must always remain discoverable to administrators.
        if ($app['id'] === 'admin') { $app['hidden'] = false; }
        $apps[] = $app;
    }
    $savedGroupIds = array();
    foreach ($saved['groups'] as $savedGroup) {
        if (is_array($savedGroup) && isset($savedGroup['id'])) { $savedGroupIds[(string) $savedGroup['id']] = true; }
    }
    foreach ($saved['apps'] as $item) {
        if (!is_array($item) || empty($item['custom']) || empty($item['id'])) { continue; }
        $url = isset($item['url']) ? lobby_clean_url($item['url']) : '';
        $name = isset($item['name']) ? lobby_clean_text($item['name'], 80) : '';
        if ($url === '' || $name === '') { continue; }
        $icon = isset($item['icon']) && preg_match('/^fa-[a-z0-9-]+$/', (string) $item['icon']) ? (string) $item['icon'] : 'fa-link';
        $group = isset($item['group'], $savedGroupIds[(string) $item['group']]) ? (string) $item['group'] : (string) $saved['groups'][0]['id'];
        $apps[] = array(
            'id' => (string) $item['id'], 'group' => $group,
            'name' => $name, 'description' => isset($item['description']) ? lobby_clean_text($item['description'], 160) : '',
            'url' => $url, 'icon' => $icon, 'color' => 'blue',
            'custom' => true, 'hidden' => !empty($item['hidden']), 'order' => isset($item['order']) ? (int) $item['order'] : 999
        );
    }
    usort($apps, function ($a, $b) { return $a['order'] - $b['order']; });
    return array('groups' => $saved['groups'], 'apps' => $apps);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_layout') {
    if (!$isAdmin) {
        lobby_json_response(false, array('message' => 'ไม่มีสิทธิ์แก้ไขหน้าล็อบบี้'), 403);
    }
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['lobby_csrf'], (string) $_POST['csrf'])) {
        lobby_json_response(false, array('message' => 'เซสชันหมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่'), 419);
    }
    $input = isset($_POST['layout']) ? json_decode((string) $_POST['layout'], true) : null;
    if (!is_array($input) || !isset($input['groups'], $input['apps']) || !is_array($input['groups']) || !is_array($input['apps'])) {
        lobby_json_response(false, array('message' => 'รูปแบบข้อมูลไม่ถูกต้อง'), 422);
    }

    $allowedAppIds = array();
    foreach ($defaultApps as $app) { $allowedAppIds[$app['id']] = true; }
    $cleanGroups = array();
    $groupIds = array();
    foreach (array_slice($input['groups'], 0, 20) as $index => $group) {
        if (!is_array($group)) { continue; }
        $id = isset($group['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $group['id']) : '';
        $name = isset($group['name']) ? lobby_clean_text($group['name'], 80) : '';
        if ($id === '' || $name === '' || isset($groupIds[$id])) { continue; }
        $groupIds[$id] = true;
        $cleanGroups[] = array(
            'id' => $id,
            'name' => $name,
            'icon' => 'fa-folder-open-o',
            'description' => isset($group['description']) ? lobby_clean_text($group['description'], 140) : 'กลุ่มระบบบริการ'
        );
    }
    if (!$cleanGroups) {
        lobby_json_response(false, array('message' => 'ต้องมีอย่างน้อย 1 กลุ่ม'), 422);
    }

    $cleanApps = array();
    $seenApps = array();
    $allowedIcons = array('fa-link','fa-star','fa-book','fa-graduation-cap','fa-users','fa-user','fa-calendar','fa-clock-o','fa-check-square-o','fa-file-text-o','fa-folder-open-o','fa-briefcase','fa-cog','fa-home','fa-heart','fa-trophy','fa-line-chart','fa-bar-chart','fa-table','fa-envelope','fa-bullhorn','fa-cloud','fa-database','fa-desktop','fa-laptop','fa-mobile','fa-shield','fa-wrench','fa-lightbulb-o','fa-map-marker','fa-pencil-square-o','fa-picture-o','fa-video-camera');
    foreach (array_slice($input['apps'], 0, 200) as $order => $app) {
        if (!is_array($app) || !isset($app['id']) || isset($seenApps[$app['id']])) { continue; }
        $isCustom = !empty($app['custom']) && strpos((string) $app['id'], 'custom-') === 0;
        if (!$isCustom && !isset($allowedAppIds[$app['id']])) { continue; }
        $group = isset($app['group'], $groupIds[$app['group']]) ? $app['group'] : $cleanGroups[0]['id'];
        $seenApps[$app['id']] = true;
        $clean = array('id' => preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $app['id']), 'group' => $group, 'hidden' => !empty($app['hidden']), 'order' => (int) $order);
        if ($isCustom) {
            $clean['custom'] = true;
            $clean['name'] = isset($app['name']) ? lobby_clean_text($app['name'], 80) : '';
            $clean['description'] = isset($app['description']) ? lobby_clean_text($app['description'], 160) : '';
            $clean['url'] = isset($app['url']) ? lobby_clean_url($app['url']) : '';
            $clean['icon'] = isset($app['icon']) && in_array($app['icon'], $allowedIcons, true) ? $app['icon'] : 'fa-link';
            if ($clean['name'] === '' || $clean['url'] === '') { continue; }
        }
        $cleanApps[] = $clean;
    }
    foreach ($defaultApps as $app) {
        if (!isset($seenApps[$app['id']])) {
            $cleanApps[] = array('id' => $app['id'], 'group' => isset($groupIds[$app['group']]) ? $app['group'] : $cleanGroups[0]['id'], 'hidden' => false, 'order' => count($cleanApps));
        }
    }

    $json = json_encode(array('groups' => $cleanGroups, 'apps' => $cleanApps), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $tempFile = $layoutFile . '.tmp';
    if (file_put_contents($tempFile, $json, LOCK_EX) === false || !rename($tempFile, $layoutFile)) {
        @unlink($tempFile);
        lobby_json_response(false, array('message' => 'บันทึกไม่ได้ โปรดตรวจสอบสิทธิ์เขียนไฟล์ lobby_layout.json'), 500);
    }
    lobby_json_response(true, array('message' => 'บันทึกหน้าล็อบบี้เรียบร้อยแล้ว'), 200);
}

$layout = lobby_load_layout($layoutFile, $defaultGroups, $defaultApps);
$memberId = isset($_SESSION['psn_mem_id']) ? (string) $_SESSION['psn_mem_id'] : (isset($memberid) ? (string) $memberid : '');
$defaultImage = 'teacher/src/teacher-image/user.png';
$memberImage = 'teacher/src/teacher-image/' . basename($memberId) . '.jpg';
$imgPath = ($memberId !== '' && is_file(__DIR__ . '/' . $memberImage)) ? $memberImage : $defaultImage;
$fullName = trim(
    (isset($_SESSION['psn_title_name']) ? $_SESSION['psn_title_name'] : '') .
    (isset($_SESSION['psn_mem_name']) ? $_SESSION['psn_mem_name'] : '') . ' ' .
    (isset($_SESSION['psn_mem_surname']) ? $_SESSION['psn_mem_surname'] : '')
);
$pageTitle = isset($titlename) ? $titlename : 'ศูนย์บริการโรงเรียน';
$schoolName = isset($school_name) ? $school_name : 'School Service Hub';
$credit = isset($creditname) ? $creditname : 'ระบบสารสนเทศโรงเรียน';
$selfPage = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : 'lobby.php';

function lobby_count_query($conn, $sql)
{
    if (!isset($conn) || !$conn) { return 0; }
    try {
        $result = $conn->query($sql);
        if (!$result) { return 0; }
        $row = $result->fetch_row();
        return $row ? (int) $row[0] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

$badges = array();
$badges['achievement'] = lobby_count_query($conn, "SELECT COUNT(*) FROM onlinebox ob INNER JOIN onlinebox_type obt ON ob.obt_id=obt.obt_id WHERE obt.obt_mode=3 AND ob.ob_status=1");
$badges['edocument'] = lobby_count_query($conn, "SELECT COUNT(*) FROM onlinebox ob INNER JOIN onlinebox_type obt ON ob.obt_id=obt.obt_id WHERE obt.obt_mode<>3 AND ob.ob_status=1");
$activeRound = lobby_count_query($conn, "SELECT COALESCE(MAX(reg_count_id),0) FROM regrade2_count WHERE reg_count_status=1");
$badges['grade_fix'] = $activeRound > 0 ? lobby_count_query($conn, "SELECT COUNT(*) FROM regrade2_std_data WHERE reg_count_id=" . (int) $activeRound . " AND reg_std_data_regis=2") : 0;
$badges['grade_fix'] += lobby_count_query($conn, "SELECT COUNT(*) FROM regrade2_stdfollow WHERE reg_stdflw_status=1 AND (reg_stdflw_director1=1 OR reg_stdflw_director2=1)");
$badges['activity_permission'] = lobby_count_query($conn, "SELECT COUNT(*) FROM act_pms_data WHERE act_pms_data_work=1 AND (act_pms_data_status1=1 OR act_pms_data_status2=1)");
$badges['activity_permission'] += lobby_count_query($conn, "SELECT COUNT(*) FROM act_pms_report_list WHERE act_pms_report_list_status=0");
$escapedMemberId = isset($conn) && method_exists($conn, 'real_escape_string') ? $conn->real_escape_string($memberId) : addslashes($memberId);
$badges['substitute'] = lobby_count_query($conn, "SELECT COUNT(*) FROM ct_teach_data WHERE ct_teach_data_status1=1 AND (psn_mem_id_1='" . $escapedMemberId . "' OR psn_mem_id_2='" . $escapedMemberId . "')");

function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#dce9ff">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="icon" href="images/fav.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Noto Sans Thai', 'system-ui', 'sans-serif'] } } } };
    </script>
    <style>
        :root { color-scheme: light; --glass: rgba(255,255,255,.54); --stroke: rgba(255,255,255,.72); --ink: #17233b; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { min-height: 100vh; min-height: 100dvh; color: var(--ink); background: #dce9ff; overflow-x: hidden; }
        body:before, body:after { content:""; position:fixed; border-radius:999px; filter:blur(1px); pointer-events:none; z-index:-2; }
        body:before { width:44rem; height:44rem; left:-15rem; top:-17rem; background:radial-gradient(circle at 60% 60%,rgba(93,163,255,.9),rgba(166,216,255,.42) 45%,transparent 72%); }
        body:after { width:48rem; height:48rem; right:-17rem; bottom:-22rem; background:radial-gradient(circle at 45% 40%,rgba(181,118,255,.58),rgba(255,183,221,.34) 45%,transparent 72%); }
        .aurora { position:fixed; inset:0; z-index:-3; background:linear-gradient(145deg,#eff8ff 0%,#d8e9ff 35%,#efe7ff 70%,#fff3f8 100%); }
        .glass { background:var(--glass); border:1px solid var(--stroke); box-shadow:0 18px 55px rgba(48,76,125,.13), inset 0 1px 0 rgba(255,255,255,.74); -webkit-backdrop-filter:blur(28px) saturate(150%); backdrop-filter:blur(28px) saturate(150%); }
        .glass-soft { background:rgba(255,255,255,.36); border:1px solid rgba(255,255,255,.56); -webkit-backdrop-filter:blur(20px) saturate(140%); backdrop-filter:blur(20px) saturate(140%); }
        .app-card { transform:translateZ(0); transition:transform .24s ease, background .24s ease, box-shadow .24s ease; }
        .app-card:hover { transform:translateY(-4px) scale(1.008); background:rgba(255,255,255,.68); box-shadow:0 20px 45px rgba(54,80,130,.16),inset 0 1px 0 #fff; }
        .app-card:active { transform:scale(.985); }
        .app-icon { background:linear-gradient(145deg,rgba(255,255,255,.9),rgba(255,255,255,.45)); box-shadow:0 9px 20px rgba(55,80,125,.12),inset 0 1px 0 #fff; }
        .search-ring:focus-within { box-shadow:0 0 0 4px rgba(59,130,246,.14),0 18px 50px rgba(48,76,125,.13); }
        .edit-mode .app-card { cursor:grab; }
        .edit-mode .app-card:active { cursor:grabbing; }
        .edit-tools { display:none; }
        .edit-mode .edit-tools { display:flex; }
        .edit-mode .app-card[draggable="true"] { outline:1px dashed rgba(37,99,235,.28); outline-offset:3px; }
        .dragging { opacity:.45; transform:scale(.97); }
        .drag-over { box-shadow:inset 0 0 0 2px rgba(37,99,235,.5); }
        .hidden-app { opacity:.48; filter:grayscale(.45); }
        .toast { animation:toast-in .3s ease both; }
        @keyframes toast-in { from{opacity:0;transform:translate(-50%,16px)} to{opacity:1;transform:translate(-50%,0)} }
        @media (prefers-reduced-motion:reduce) { *,*:before,*:after { scroll-behavior:auto!important; transition:none!important; animation:none!important; } }
        @media (max-width:640px) { .glass { -webkit-backdrop-filter:blur(20px) saturate(140%); backdrop-filter:blur(20px) saturate(140%); } }
    </style>
</head>
<body class="font-sans antialiased selection:bg-blue-200/70">
<div class="aurora"></div>

<header class="sticky top-0 z-40 px-3 pt-3 sm:px-5 sm:pt-4">
    <nav class="glass mx-auto flex max-w-7xl items-center gap-3 rounded-[1.4rem] px-3 py-2.5 sm:px-4" aria-label="เมนูหลัก">
        <a href="<?php echo e($selfPage); ?>" class="flex min-w-0 items-center gap-3 rounded-2xl focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-300/50">
            <span class="app-icon grid h-11 w-11 shrink-0 place-items-center rounded-2xl text-lg text-blue-600"><i class="fa fa-th-large" aria-hidden="true"></i></span>
            <span class="hidden min-w-0 sm:block"><strong class="block truncate text-sm font-semibold"><?php echo e($schoolName); ?></strong><span class="block text-xs text-slate-500">ศูนย์รวมระบบบริการ</span></span>
        </a>
        <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
            <?php if ($isAdmin): ?>
                <button id="editToggle" type="button" class="hidden items-center gap-2 rounded-2xl bg-blue-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-300 sm:flex">
                    <i class="fa fa-sliders" aria-hidden="true"></i><span>แก้ไขหน้าล็อบบี้</span>
                </button>
                <button id="editToggleMobile" type="button" class="grid h-10 w-10 place-items-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/20 sm:hidden" aria-label="แก้ไขหน้าล็อบบี้"><i class="fa fa-sliders" aria-hidden="true"></i></button>
            <?php endif; ?>
            <button id="profileButton" type="button" class="flex min-w-0 items-center gap-2 rounded-2xl p-1.5 pr-2 transition hover:bg-white/45 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-300/50" aria-haspopup="dialog">
                <img src="<?php echo e($imgPath); ?>?v=<?php echo time(); ?>" alt="รูปของ <?php echo e($fullName); ?>" class="h-9 w-9 rounded-xl border border-white/80 object-cover shadow-sm">
                <span class="hidden max-w-[11rem] truncate text-sm font-medium md:block"><?php echo e($fullName ?: 'ผู้ใช้งาน'); ?></span>
                <i class="fa fa-angle-down text-slate-500" aria-hidden="true"></i>
            </button>
        </div>
    </nav>
</header>

<main class="mx-auto max-w-7xl px-4 pb-16 pt-8 sm:px-6 sm:pt-12 lg:px-8">
    <section class="mb-8 text-center sm:mb-11">
        <span class="glass-soft inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold text-blue-700"><span class="h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,.14)]"></span>พร้อมให้บริการ</span>
        <h1 class="mx-auto mt-5 max-w-3xl text-3xl font-semibold leading-tight tracking-tight text-slate-900 sm:text-5xl">ระบบสารสนเทศโรงเรียนระหานวิทยา</h1>
        <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-600 sm:text-base">ค้นหาและเข้าสู่ระบบงานทั้งหมดได้จากที่เดียว รวดเร็ว เป็นระเบียบ และใช้งานง่ายในทุกอุปกรณ์</p>
        <div class="search-ring glass mx-auto mt-6 flex max-w-2xl items-center gap-3 rounded-[1.35rem] px-4 py-3 transition">
            <i class="fa fa-search text-slate-400" aria-hidden="true"></i>
            <input id="appSearch" type="search" class="w-full bg-transparent text-base outline-none placeholder:text-slate-400" placeholder="ค้นหาระบบ เช่น คะแนน, ตารางสอน, นักเรียน" autocomplete="off" aria-label="ค้นหาระบบ">
            <kbd class="hidden rounded-lg border border-white/70 bg-white/40 px-2 py-1 text-[10px] text-slate-500 sm:block">/</kbd>
        </div>
    </section>

    <?php if ($isAdmin): ?>
    <section id="editorBar" class="glass mb-6 hidden items-center gap-3 rounded-[1.35rem] p-3 sm:p-4" aria-label="เครื่องมือแก้ไขหน้าล็อบบี้">
        <div class="min-w-0 flex-1"><p class="text-sm font-semibold text-slate-900">โหมดแก้ไข</p><p class="truncate text-xs text-slate-500">กด “+ การ์ด” ข้างชื่อกลุ่ม หรือลากการ์ดเพื่อเรียงและย้ายกลุ่ม</p></div>
        <button id="addGroup" type="button" class="rounded-xl bg-white/65 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-white"><i class="fa fa-plus mr-1" aria-hidden="true"></i> กลุ่ม</button>
        <button id="cancelEdit" type="button" class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-white/45">ยกเลิก</button>
        <button id="saveLayout" type="button" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">บันทึก</button>
    </section>
    <?php endif; ?>

    <div id="groups" class="space-y-9">
        <?php foreach ($layout['groups'] as $group): ?>
            <section class="group-section" data-group-id="<?php echo e($group['id']); ?>">
                <div class="mb-3 flex items-end gap-3 px-1 sm:mb-4">
                    <span class="app-icon grid h-10 w-10 shrink-0 place-items-center rounded-2xl text-blue-600"><i class="fa <?php echo e(isset($group['icon']) ? $group['icon'] : 'fa-folder-open-o'); ?>" aria-hidden="true"></i></span>
                    <div class="min-w-0 flex-1"><h2 class="group-name text-lg font-semibold text-slate-900 sm:text-xl"><?php echo e($group['name']); ?></h2><p class="group-description hidden truncate text-sm text-slate-500 sm:block"><?php echo e(isset($group['description']) ? $group['description'] : ''); ?></p></div>
                    <div class="edit-tools gap-1">
                        <button type="button" class="add-card flex h-9 shrink-0 items-center gap-1.5 rounded-xl bg-blue-600 px-3 text-xs font-semibold text-white shadow-sm hover:bg-blue-700" aria-label="เพิ่มการ์ดในกลุ่มนี้"><i class="fa fa-plus" aria-hidden="true"></i><span>การ์ด</span></button>
                        <button type="button" class="rename-group grid h-9 w-9 place-items-center rounded-xl bg-white/45 text-slate-600 hover:bg-white" aria-label="เปลี่ยนชื่อกลุ่ม"><i class="fa fa-pencil"></i></button>
                        <button type="button" class="delete-group grid h-9 w-9 place-items-center rounded-xl bg-white/45 text-rose-600 hover:bg-white" aria-label="ลบกลุ่ม"><i class="fa fa-trash-o"></i></button>
                    </div>
                </div>
                <div class="app-grid grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <?php foreach ($layout['apps'] as $app):
                        if ($app['group'] !== $group['id'] || (!empty($app['adminOnly']) && !$isAdmin)) { continue; }
                        $hidden = !empty($app['hidden']);
                        $badge = isset($app['badge'], $badges[$app['badge']]) ? (int) $badges[$app['badge']] : 0;
                    ?>
                    <article class="app-card glass-soft relative rounded-[1.45rem] p-3 <?php echo $hidden ? 'hidden-app' : ''; ?>" data-app-id="<?php echo e($app['id']); ?>" data-search="<?php echo e($app['name'] . ' ' . $app['description']); ?>" data-hidden="<?php echo $hidden ? '1' : '0'; ?>" data-custom="<?php echo !empty($app['custom']) ? '1' : '0'; ?>" data-name="<?php echo e($app['name']); ?>" data-description="<?php echo e($app['description']); ?>" data-url="<?php echo e($app['url']); ?>" data-icon="<?php echo e($app['icon']); ?>" draggable="false">
                        <a href="<?php echo e($app['url']); ?>" class="app-link flex min-h-[5.5rem] items-center gap-3 rounded-2xl outline-none focus-visible:ring-4 focus-visible:ring-blue-300/50">
                            <span class="app-icon grid h-14 w-14 shrink-0 place-items-center rounded-[1.15rem] text-xl text-blue-600"><i class="fa <?php echo e($app['icon']); ?>" aria-hidden="true"></i></span>
                            <span class="min-w-0 flex-1"><span class="flex items-start gap-2"><strong class="block text-[15px] font-semibold leading-6 text-slate-900"><?php echo e($app['name']); ?></strong><?php if (!empty($app['tag'])): ?><span class="rounded-full bg-blue-600 px-1.5 py-0.5 text-[9px] font-bold text-white"><?php echo e($app['tag']); ?></span><?php endif; ?></span><span class="mt-0.5 line-clamp-2 block text-xs leading-5 text-slate-500"><?php echo e($app['description']); ?></span></span>
                            <span class="flex shrink-0 flex-col items-center gap-2"><?php if ($badge > 0): ?><span class="grid min-h-6 min-w-6 place-items-center rounded-full bg-rose-500 px-1.5 text-[10px] font-bold text-white shadow-md shadow-rose-500/25" aria-label="<?php echo $badge; ?> รายการรอดำเนินการ"><?php echo $badge > 99 ? '99+' : $badge; ?></span><?php endif; ?><i class="fa fa-angle-right text-slate-400" aria-hidden="true"></i></span>
                        </a>
                        <div class="edit-tools absolute right-2 top-2 gap-1">
                            <button type="button" class="toggle-app grid h-8 w-8 place-items-center rounded-xl bg-white/90 text-slate-600 shadow-sm" aria-label="ซ่อนหรือแสดงระบบ"><i class="fa <?php echo $hidden ? 'fa-eye-slash' : 'fa-eye'; ?>"></i></button>
                            <?php if (!empty($app['custom'])): ?><button type="button" class="delete-card grid h-8 w-8 place-items-center rounded-xl bg-rose-50 text-rose-600 shadow-sm" aria-label="ลบการ์ด"><i class="fa fa-trash-o"></i></button><?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <div class="empty-group glass-soft hidden rounded-2xl border-dashed p-7 text-center text-sm text-slate-500">ลากระบบมาวางในกลุ่มนี้</div>
            </section>
        <?php endforeach; ?>
    </div>
    <div id="emptySearch" class="glass mx-auto my-14 hidden max-w-md rounded-[1.5rem] p-8 text-center"><i class="fa fa-search mb-3 text-3xl text-slate-400"></i><h2 class="font-semibold">ไม่พบระบบที่ค้นหา</h2><p class="mt-1 text-sm text-slate-500">ลองใช้คำค้นอื่น หรือล้างช่องค้นหา</p></div>
</main>

<footer class="px-4 pb-[max(1.5rem,env(safe-area-inset-bottom))] text-center text-xs text-slate-500"><?php echo e($credit); ?> · <?php echo date('Y'); ?></footer>

<div id="profileMenu" class="glass fixed right-4 top-20 z-50 hidden w-[min(22rem,calc(100vw-2rem))] rounded-[1.5rem] p-3 sm:right-6" role="dialog" aria-modal="true" aria-label="เมนูผู้ใช้">
    <div class="flex items-center gap-3 p-2"><img src="<?php echo e($imgPath); ?>?v=<?php echo time(); ?>" alt="" class="h-14 w-14 rounded-2xl border border-white object-cover"><div class="min-w-0"><p class="truncate font-semibold"><?php echo e($fullName ?: 'ผู้ใช้งาน'); ?></p><p class="text-xs text-slate-500">รหัสบุคลากร <?php echo e($memberId ?: '-'); ?></p></div></div>
    <div class="my-2 h-px bg-white/65"></div>
    <a href="userinfo/changepassword.php" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium hover:bg-white/55"><i class="fa fa-key w-5 text-blue-600"></i>เปลี่ยนรหัสผ่าน</a>
    <button id="openPhoto" type="button" class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-left text-sm font-medium hover:bg-white/55"><i class="fa fa-camera w-5 text-blue-600"></i>เปลี่ยนรูปโปรไฟล์</button>
    <a href="logout.php" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium text-rose-600 hover:bg-rose-50/60"><i class="fa fa-sign-out w-5"></i>ออกจากระบบ</a>
</div>

<?php if ($isAdmin): ?>
<div id="cardModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/25 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="cardModalTitle">
    <form id="cardForm" class="glass max-h-[calc(100dvh-2rem)] w-full max-w-lg overflow-y-auto rounded-[1.8rem] p-5 sm:p-6">
        <div class="flex items-center justify-between"><div><h2 id="cardModalTitle" class="text-lg font-semibold">เพิ่มการ์ดเมนู</h2><p id="cardGroupLabel" class="mt-0.5 text-xs text-slate-500"></p></div><button type="button" class="close-card-modal grid h-9 w-9 place-items-center rounded-full bg-white/50" aria-label="ปิด"><i class="fa fa-times"></i></button></div>
        <div class="mt-5 space-y-4">
            <label class="block"><span class="mb-1.5 block text-sm font-semibold">ชื่อเมนู <b class="text-rose-500">*</b></span><input id="cardName" maxlength="80" required class="w-full rounded-2xl border border-white/70 bg-white/55 px-4 py-3 outline-none focus:ring-4 focus:ring-blue-300/30" placeholder="เช่น ระบบจองห้องประชุม"></label>
            <label class="block"><span class="mb-1.5 block text-sm font-semibold">รายละเอียด</span><textarea id="cardDescription" maxlength="160" rows="3" class="w-full resize-none rounded-2xl border border-white/70 bg-white/55 px-4 py-3 outline-none focus:ring-4 focus:ring-blue-300/30" placeholder="อธิบายสั้น ๆ ว่าระบบนี้ใช้ทำอะไร"></textarea></label>
            <label class="block"><span class="mb-1.5 block text-sm font-semibold">ลิงก์ระบบ <b class="text-rose-500">*</b></span><input id="cardUrl" maxlength="500" required class="w-full rounded-2xl border border-white/70 bg-white/55 px-4 py-3 outline-none focus:ring-4 focus:ring-blue-300/30" placeholder="https://example.com หรือ path/to/page.php"></label>
            <fieldset><legend class="mb-2 text-sm font-semibold">เลือกไอคอน</legend><div id="iconPicker" class="grid grid-cols-7 gap-2 sm:grid-cols-9"></div><input id="cardIcon" type="hidden" value="fa-link"></fieldset>
        </div>
        <div class="mt-6 flex justify-end gap-2"><button type="button" class="close-card-modal rounded-xl px-4 py-2 text-sm font-semibold text-slate-600">ยกเลิก</button><button type="submit" class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-600/20">เพิ่มการ์ด</button></div>
    </form>
</div>
<?php endif; ?>

<div id="photoModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/25 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="photoTitle">
    <div class="glass w-full max-w-md rounded-[1.8rem] p-5 sm:p-6">
        <div class="flex items-center justify-between"><h2 id="photoTitle" class="text-lg font-semibold">เปลี่ยนรูปโปรไฟล์</h2><button type="button" class="close-modal grid h-9 w-9 place-items-center rounded-full bg-white/50" aria-label="ปิด"><i class="fa fa-times"></i></button></div>
        <form action="upload.php" method="post" enctype="multipart/form-data" class="mt-5">
            <input type="hidden" name="tid" value="<?php echo e($memberId); ?>">
            <div class="mx-auto h-32 w-32 overflow-hidden rounded-[2rem] border-2 border-white/80 bg-white/40 shadow-xl"><img id="photoPreview" src="<?php echo e($imgPath); ?>?v=<?php echo time(); ?>" alt="ภาพตัวอย่าง" class="h-full w-full object-cover"></div>
            <label class="mt-5 block rounded-2xl border border-dashed border-blue-300 bg-blue-50/45 p-4 text-center text-sm font-medium text-blue-700 hover:bg-blue-50"><i class="fa fa-cloud-upload mr-2"></i>เลือกรูปภาพ JPG<input id="photoInput" type="file" name="fileToUpload" accept="image/jpeg,.jpg,.jpeg" class="sr-only" required></label>
            <p class="mt-2 text-center text-xs text-slate-500">ระบบจะตรวจสอบชนิดและขนาดไฟล์อีกครั้งเมื่อบันทึก</p>
            <div class="mt-5 flex justify-end gap-2"><button type="button" class="close-modal rounded-xl px-4 py-2 text-sm font-semibold text-slate-600">ยกเลิก</button><button type="submit" class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-600/20">บันทึกรูป</button></div>
        </form>
    </div>
</div>

<div id="toast" class="toast glass fixed bottom-6 left-1/2 z-[80] hidden -translate-x-1/2 rounded-2xl px-4 py-3 text-sm font-semibold shadow-xl" role="status"></div>

<script>
(function () {
    'use strict';
    var body = document.body;
    var search = document.getElementById('appSearch');
    var groups = document.getElementById('groups');
    var initialHTML = groups.innerHTML;
    var editMode = false;
    var dragged = null;

    function showToast(message, error) {
        var toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast glass fixed bottom-6 left-1/2 z-[80] -translate-x-1/2 rounded-2xl px-4 py-3 text-sm font-semibold shadow-xl ' + (error ? 'text-rose-700' : 'text-emerald-700');
        window.clearTimeout(showToast.timer);
        showToast.timer = window.setTimeout(function () { toast.classList.add('hidden'); }, 3200);
    }

    function refreshEmptyStates() {
        var visibleCount = 0;
        document.querySelectorAll('.group-section').forEach(function (section) {
            var visibleCards = Array.prototype.filter.call(section.querySelectorAll('.app-card'), function (card) { return card.style.display !== 'none'; });
            section.querySelector('.empty-group').classList.toggle('hidden', !editMode || section.querySelectorAll('.app-card').length > 0);
            section.style.display = (!editMode && visibleCards.length === 0) ? 'none' : '';
            visibleCount += visibleCards.length;
        });
        document.getElementById('emptySearch').classList.toggle('hidden', visibleCount !== 0 || editMode);
    }

    function filterApps() {
        var query = search.value.trim().toLocaleLowerCase('th');
        document.querySelectorAll('.app-card').forEach(function (card) {
            var matches = !query || card.dataset.search.toLocaleLowerCase('th').indexOf(query) !== -1;
            var isHidden = card.dataset.hidden === '1';
            card.style.display = matches && (editMode || !isHidden) ? '' : 'none';
        });
        refreshEmptyStates();
    }

    search.addEventListener('input', filterApps);
    document.addEventListener('keydown', function (event) {
        if (event.key === '/' && document.activeElement.tagName !== 'INPUT') { event.preventDefault(); search.focus(); }
        if (event.key === 'Escape') { document.getElementById('profileMenu').classList.add('hidden'); closePhoto(); }
    });

    var profileButton = document.getElementById('profileButton');
    var profileMenu = document.getElementById('profileMenu');
    profileButton.addEventListener('click', function (event) { event.stopPropagation(); profileMenu.classList.toggle('hidden'); });
    document.addEventListener('click', function (event) { if (!profileMenu.contains(event.target) && event.target !== profileButton) profileMenu.classList.add('hidden'); });

    var photoModal = document.getElementById('photoModal');
    function closePhoto() { photoModal.classList.add('hidden'); photoModal.classList.remove('flex'); }
    document.getElementById('openPhoto').addEventListener('click', function () { profileMenu.classList.add('hidden'); photoModal.classList.remove('hidden'); photoModal.classList.add('flex'); });
    document.querySelectorAll('.close-modal').forEach(function (button) { button.addEventListener('click', closePhoto); });
    photoModal.addEventListener('click', function (event) { if (event.target === photoModal) closePhoto(); });
    document.getElementById('photoInput').addEventListener('change', function () {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function (event) { document.getElementById('photoPreview').src = event.target.result; };
            reader.readAsDataURL(this.files[0]);
        }
    });

    function setupEditorEvents() {
        document.querySelectorAll('.app-card').forEach(function (card) {
            card.draggable = editMode;
            card.querySelector('.app-link').addEventListener('click', function (event) { if (editMode) event.preventDefault(); });
            card.addEventListener('dragstart', function () { if (!editMode) return; dragged = card; card.classList.add('dragging'); });
            card.addEventListener('dragend', function () { card.classList.remove('dragging'); dragged = null; document.querySelectorAll('.drag-over').forEach(function (el) { el.classList.remove('drag-over'); }); });
            card.addEventListener('dragover', function (event) { if (!editMode || !dragged) return; event.preventDefault(); card.classList.add('drag-over'); });
            card.addEventListener('dragleave', function () { card.classList.remove('drag-over'); });
            card.addEventListener('drop', function (event) { if (!editMode || !dragged) return; event.preventDefault(); card.classList.remove('drag-over'); card.parentNode.insertBefore(dragged, card); refreshEmptyStates(); });
        });
        document.querySelectorAll('.app-grid, .empty-group').forEach(function (area) {
            area.addEventListener('dragover', function (event) { if (editMode && dragged) event.preventDefault(); });
            area.addEventListener('drop', function (event) { if (!editMode || !dragged) return; event.preventDefault(); var grid = area.classList.contains('app-grid') ? area : area.parentNode.querySelector('.app-grid'); grid.appendChild(dragged); refreshEmptyStates(); });
        });
    }
    setupEditorEvents();

    <?php if ($isAdmin): ?>
    var editorBar = document.getElementById('editorBar');
    var cardModal = document.getElementById('cardModal');
    var cardTargetGroup = null;
    var iconNames = ['fa-link','fa-star','fa-book','fa-graduation-cap','fa-users','fa-user','fa-calendar','fa-clock-o','fa-check-square-o','fa-file-text-o','fa-folder-open-o','fa-briefcase','fa-cog','fa-home','fa-heart','fa-trophy','fa-line-chart','fa-bar-chart','fa-table','fa-envelope','fa-bullhorn','fa-cloud','fa-database','fa-desktop','fa-laptop','fa-mobile','fa-shield','fa-wrench','fa-lightbulb-o','fa-map-marker','fa-pencil-square-o','fa-picture-o','fa-video-camera'];
    function closeCardModal() { cardModal.classList.add('hidden'); cardModal.classList.remove('flex'); }
    function openCardModal(section) {
        cardTargetGroup = section;
        document.getElementById('cardForm').reset();
        document.getElementById('cardIcon').value = 'fa-link';
        document.getElementById('cardGroupLabel').textContent = 'เพิ่มในกลุ่ม “' + section.querySelector('.group-name').textContent.trim() + '”';
        document.querySelectorAll('.icon-choice').forEach(function (button) { button.classList.toggle('bg-blue-600', button.dataset.icon === 'fa-link'); button.classList.toggle('text-white', button.dataset.icon === 'fa-link'); });
        cardModal.classList.remove('hidden'); cardModal.classList.add('flex');
        window.setTimeout(function () { document.getElementById('cardName').focus(); }, 50);
    }
    function createCustomCard(data) {
        var card = document.createElement('article');
        card.className = 'app-card glass-soft relative rounded-[1.45rem] p-3';
        card.dataset.appId = data.id; card.dataset.search = data.name + ' ' + data.description;
        card.dataset.hidden = '0'; card.dataset.custom = '1'; card.dataset.name = data.name;
        card.dataset.description = data.description; card.dataset.url = data.url; card.dataset.icon = data.icon;
        card.innerHTML = '<a class="app-link flex min-h-[5.5rem] items-center gap-3 rounded-2xl outline-none focus-visible:ring-4 focus-visible:ring-blue-300/50"><span class="app-icon grid h-14 w-14 shrink-0 place-items-center rounded-[1.15rem] text-xl text-blue-600"><i aria-hidden="true"></i></span><span class="min-w-0 flex-1"><span class="flex items-start gap-2"><strong class="card-title block text-[15px] font-semibold leading-6 text-slate-900"></strong></span><span class="card-description mt-0.5 line-clamp-2 block text-xs leading-5 text-slate-500"></span></span><span class="flex shrink-0 flex-col items-center gap-2"><i class="fa fa-angle-right text-slate-400" aria-hidden="true"></i></span></a><div class="edit-tools absolute right-2 top-2 gap-1"><button type="button" class="toggle-app grid h-8 w-8 place-items-center rounded-xl bg-white/90 text-slate-600 shadow-sm" aria-label="ซ่อนหรือแสดงระบบ"><i class="fa fa-eye"></i></button><button type="button" class="delete-card grid h-8 w-8 place-items-center rounded-xl bg-rose-50 text-rose-600 shadow-sm" aria-label="ลบการ์ด"><i class="fa fa-trash-o"></i></button></div>';
        card.querySelector('.app-link').setAttribute('href', data.url);
        card.querySelector('.app-icon i').className = 'fa ' + data.icon;
        card.querySelector('.card-title').textContent = data.name;
        card.querySelector('.card-description').textContent = data.description;
        return card;
    }
    var iconPicker = document.getElementById('iconPicker');
    iconNames.forEach(function (icon) {
        var button = document.createElement('button'); button.type = 'button'; button.dataset.icon = icon;
        button.className = 'icon-choice grid aspect-square place-items-center rounded-xl bg-white/55 text-slate-600 hover:bg-white';
        button.setAttribute('aria-label', 'เลือกไอคอน ' + icon); button.innerHTML = '<i class="fa ' + icon + '"></i>';
        button.addEventListener('click', function () { document.getElementById('cardIcon').value = icon; document.querySelectorAll('.icon-choice').forEach(function (item) { item.classList.remove('bg-blue-600','text-white'); }); button.classList.add('bg-blue-600','text-white'); });
        iconPicker.appendChild(button);
    });
    document.querySelectorAll('.close-card-modal').forEach(function (button) { button.addEventListener('click', closeCardModal); });
    cardModal.addEventListener('click', function (event) { if (event.target === cardModal) closeCardModal(); });
    document.getElementById('cardForm').addEventListener('submit', function (event) {
        event.preventDefault();
        var data = {id:'custom-' + Date.now(), name:document.getElementById('cardName').value.trim(), description:document.getElementById('cardDescription').value.trim(), url:document.getElementById('cardUrl').value.trim(), icon:document.getElementById('cardIcon').value};
        if (!data.name || !data.url) { showToast('กรุณากรอกชื่อและลิงก์ของระบบ', true); return; }
        if (/^(javascript|data|vbscript):/i.test(data.url)) { showToast('ลิงก์นี้ไม่สามารถใช้งานได้', true); return; }
        cardTargetGroup.querySelector('.app-grid').appendChild(createCustomCard(data));
        closeCardModal(); setupEditorEvents(); refreshEmptyStates(); showToast('เพิ่มการ์ดแล้ว กดบันทึกเพื่อยืนยัน');
    });
    function setEditMode(enabled) {
        editMode = enabled;
        body.classList.toggle('edit-mode', enabled);
        editorBar.classList.toggle('hidden', !enabled);
        editorBar.classList.toggle('flex', enabled);
        search.value = '';
        setupEditorEvents();
        filterApps();
        if (enabled) showToast('เปิดโหมดแก้ไขแล้ว ลากการ์ดเพื่อจัดตำแหน่ง');
    }
    document.getElementById('editToggle').addEventListener('click', function () { setEditMode(true); });
    document.getElementById('editToggleMobile').addEventListener('click', function () { setEditMode(true); });
    document.getElementById('cancelEdit').addEventListener('click', function () { groups.innerHTML = initialHTML; setupEditorEvents(); setEditMode(false); });

    groups.addEventListener('click', function (event) {
        var addCard = event.target.closest('.add-card');
        if (addCard) { openCardModal(addCard.closest('.group-section')); return; }
        var deleteCard = event.target.closest('.delete-card');
        if (deleteCard) { if (window.confirm('ลบการ์ดเมนูนี้หรือไม่?')) deleteCard.closest('.app-card').remove(); return; }
        var toggle = event.target.closest('.toggle-app');
        if (toggle) {
            var card = toggle.closest('.app-card');
            var hidden = card.dataset.hidden !== '1';
            card.dataset.hidden = hidden ? '1' : '0';
            card.classList.toggle('hidden-app', hidden);
            toggle.querySelector('i').className = 'fa ' + (hidden ? 'fa-eye-slash' : 'fa-eye');
            return;
        }
        var rename = event.target.closest('.rename-group');
        if (rename) {
            var section = rename.closest('.group-section');
            var heading = section.querySelector('.group-name');
            var name = window.prompt('ชื่อกลุ่ม', heading.textContent.trim());
            if (name && name.trim()) heading.textContent = name.trim().slice(0, 80);
            return;
        }
        var remove = event.target.closest('.delete-group');
        if (remove) {
            var target = remove.closest('.group-section');
            if (document.querySelectorAll('.group-section').length <= 1) { showToast('ต้องมีอย่างน้อย 1 กลุ่ม', true); return; }
            if (!window.confirm('ลบกลุ่มนี้หรือไม่? ระบบภายในจะถูกย้ายไปกลุ่มแรก')) return;
            var first = Array.prototype.find.call(document.querySelectorAll('.group-section'), function (item) { return item !== target; });
            target.querySelectorAll('.app-card').forEach(function (card) { first.querySelector('.app-grid').appendChild(card); });
            target.remove();
            refreshEmptyStates();
        }
    });

    document.getElementById('addGroup').addEventListener('click', function () {
        var name = window.prompt('ชื่อกลุ่มใหม่');
        if (!name || !name.trim()) return;
        var id = 'group-' + Date.now();
        var section = document.createElement('section');
        section.className = 'group-section';
        section.dataset.groupId = id;
        section.innerHTML = '<div class="mb-3 flex items-end gap-3 px-1 sm:mb-4"><span class="app-icon grid h-10 w-10 shrink-0 place-items-center rounded-2xl text-blue-600"><i class="fa fa-folder-open-o"></i></span><div class="min-w-0 flex-1"><h2 class="group-name text-lg font-semibold text-slate-900 sm:text-xl"></h2><p class="group-description hidden truncate text-sm text-slate-500 sm:block">กลุ่มระบบบริการ</p></div><div class="edit-tools gap-1"><button type="button" class="add-card flex h-9 shrink-0 items-center gap-1.5 rounded-xl bg-blue-600 px-3 text-xs font-semibold text-white"><i class="fa fa-plus" aria-hidden="true"></i><span>การ์ด</span></button><button type="button" class="rename-group grid h-9 w-9 place-items-center rounded-xl bg-white/45 text-slate-600"><i class="fa fa-pencil"></i></button><button type="button" class="delete-group grid h-9 w-9 place-items-center rounded-xl bg-white/45 text-rose-600"><i class="fa fa-trash-o"></i></button></div></div><div class="app-grid grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"></div><div class="empty-group glass-soft rounded-2xl border-dashed p-7 text-center text-sm text-slate-500">ลากระบบมาวางในกลุ่มนี้</div>';
        section.querySelector('.group-name').textContent = name.trim().slice(0, 80);
        groups.appendChild(section);
        setupEditorEvents();
        section.scrollIntoView({behavior:'smooth', block:'center'});
    });

    document.getElementById('saveLayout').addEventListener('click', function () {
        var button = this;
        var payload = {groups:[], apps:[]};
        document.querySelectorAll('.group-section').forEach(function (section) {
            payload.groups.push({id:section.dataset.groupId, name:section.querySelector('.group-name').textContent.trim(), description:section.querySelector('.group-description').textContent.trim()});
            section.querySelectorAll('.app-card').forEach(function (card) { payload.apps.push({id:card.dataset.appId, group:section.dataset.groupId, hidden:card.dataset.hidden === '1', custom:card.dataset.custom === '1', name:card.dataset.name || '', description:card.dataset.description || '', url:card.dataset.url || '', icon:card.dataset.icon || 'fa-link'}); });
        });
        var data = new FormData();
        data.append('action', 'save_layout');
        data.append('csrf', <?php echo json_encode($_SESSION['lobby_csrf']); ?>);
        data.append('layout', JSON.stringify(payload));
        button.disabled = true; button.textContent = 'กำลังบันทึก…';
        fetch(window.location.pathname, {method:'POST', body:data, credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
            .then(function (response) {
                return response.text().then(function (text) {
                    var json;
                    try { json = JSON.parse(text); }
                    catch (error) { throw new Error('เซิร์ฟเวอร์ไม่ได้ส่งข้อมูล JSON กลับมา กรุณาตรวจสอบ PHP error log'); }
                    if (!response.ok || !json.success) throw new Error(json.message || 'บันทึกไม่สำเร็จ');
                    return json;
                });
            })
            .then(function (json) { showToast(json.message); window.setTimeout(function () { window.location.reload(); }, 650); })
            .catch(function (error) { showToast(error.message, true); button.disabled = false; button.textContent = 'บันทึก'; });
    });
    <?php endif; ?>

    filterApps();
})();
</script>
</body>
</html>
