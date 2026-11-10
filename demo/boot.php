<?php
/**
 * โหมดสาธิต (demo) สำหรับนำเสนอระบบติดตามนักเรียนบนเครื่องนี้เท่านั้น
 *
 * ใช้ได้เมื่อ ฐานข้อมูลอยู่ที่ localhost และเปิดจากเครื่องเดียวกัน (127.0.0.1 / ::1)
 * โฟลเดอร์ demo/ ไม่อยู่ในชุดไฟล์ขึ้น production (tools/build-release.php แพ็กเฉพาะโมดูล)
 */

define('TRK_DEMO_ROOT', __DIR__);
if (!defined('TRK_LEGACY_ROOT')) {
    define('TRK_LEGACY_ROOT', dirname(__DIR__));
}
if (!defined('TRK_ROOT')) {
    define('TRK_ROOT', TRK_LEGACY_ROOT . '/student_affairs/student_track');
}

date_default_timezone_set('Asia/Bangkok');

require_once TRK_LEGACY_ROOT . '/dbconfig.inc.php'; // session_start + global_config + $conn
require_once TRK_DEMO_ROOT . '/guard.php';

if (!trk_demo_allowed()) {
    http_response_code(403);
    exit('โหมดสาธิตใช้ได้เฉพาะบนเครื่องพัฒนา (localhost) เท่านั้น');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

foreach (['db', 'semester', 'settings', 'roles', 'duties', 'audit', 'view', 'icons', 'thai', 'members', 'notify',
          'documents', 'case1', 'rounds', 'case2', 'case3', 'dashboard'] as $lib) {
    require_once TRK_ROOT . '/lib/' . $lib . '.php';
}
trk_db_init($conn);

require_once TRK_DEMO_ROOT . '/scenes.php';
