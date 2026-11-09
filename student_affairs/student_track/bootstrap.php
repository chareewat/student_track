<?php
/**
 * จุดเริ่มของทุกหน้าในโมดูล: เชื่อมฐานข้อมูลและ login เดิม แล้วโหลด lib ของโมดูล
 *
 * โค้ดในโมดูลนี้ต้องรันได้บน PHP 7.4 (production)
 */

define('TRK_ROOT', __DIR__);
define('TRK_LEGACY_ROOT', dirname(__DIR__, 2));

date_default_timezone_set('Asia/Bangkok');

// dbconfig เดิม: เรียก session_start(), global_config.php และสร้าง $conn (mysqli)
require_once TRK_LEGACY_ROOT . '/dbconfig.inc.php';
// auth เดิม: ถ้ายังไม่ login จะพิมพ์ script redirect แต่ไม่หยุดการทำงาน เราจึงต้องเช็คเองด้านล่าง
require_once TRK_LEGACY_ROOT . '/auth.php';

if (!isset($_SESSION['psn_mem_id']) || ($_SESSION['sch_key'] ?? null) !== $sch_key) {
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

require_once TRK_ROOT . '/lib/db.php';
require_once TRK_ROOT . '/lib/semester.php';
require_once TRK_ROOT . '/lib/settings.php';
require_once TRK_ROOT . '/lib/roles.php';
require_once TRK_ROOT . '/lib/duties.php';
require_once TRK_ROOT . '/lib/audit.php';
require_once TRK_ROOT . '/lib/view.php';
require_once TRK_ROOT . '/lib/thai.php';
require_once TRK_ROOT . '/lib/members.php';
require_once TRK_ROOT . '/lib/notify.php';
require_once TRK_ROOT . '/lib/documents.php';
require_once TRK_ROOT . '/lib/case1.php';

trk_db_init($conn);

// ต้องมีอย่างน้อย 1 role จึงใช้งานได้ (admin กำหนดในหน้าจัดการสิทธิ์)
if (!trk_user()['roles']) {
    trk_render_message('ยังไม่ได้รับสิทธิ์ใช้งาน', 'กรุณาติดต่อผู้ดูแลระบบเพื่อขอสิทธิ์ใช้งานระบบติดตามนักเรียน');
    exit;
}
