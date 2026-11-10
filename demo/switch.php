<?php
/**
 * สลับผู้ใช้ในโหมดสาธิต: เข้าระบบเป็นบัญชีจำลองแบบเดียวกับ process_login.php แล้วเปิดหน้าที่ต้องการ
 *   demo/switch.php?u=t01&to=student_affairs/student_track/advisor/index.php
 */
require __DIR__ . '/boot.php';

$username = (string) ($_GET['u'] ?? '');
$to = (string) ($_GET['to'] ?? 'demo/index.php');
// เปิดได้เฉพาะหน้าในโมดูล หน้าสาธิต และหน้ารวมระบบ
if (!preg_match('#^(student_affairs/student_track/[A-Za-z0-9_/]+\.php(\?[A-Za-z0-9_=&]*)?|demo/index\.php|lobby\.php)$#', $to)) {
    $to = 'demo/index.php';
}

$member = trk_db_one('SELECT * FROM psn_member WHERE psn_mem_username = ? AND psn_memberstatus_id = 1', [$username]);
if (!$member) {
    http_response_code(404);
    exit('ไม่พบบัญชีจำลอง ' . htmlspecialchars($username));
}

session_regenerate_id(true);
$_SESSION['psn_mem_id'] = $member['psn_mem_id'];
$_SESSION['psn_mem_name'] = $member['psn_mem_name'];
$_SESSION['psn_mem_surname'] = $member['psn_mem_surname'];
$_SESSION['psn_permit_id'] = $member['psn_permit_id'] ?? null;
$_SESSION['admin_type_id'] = $member['admin_type_id'];
$_SESSION['psn_mem_vichakan_permit'] = $member['psn_mem_vicharkan_permit'];
$_SESSION['psn_mem_behavior_permit'] = $member['psn_mem_behavior_permit'];
$_SESSION['sch_key'] = $sch_key;
$_SESSION['psn_title_name'] = (string) trk_db_value('SELECT psn_title_name FROM psn_title WHERE psn_title_id = ?', [(int) $member['psn_title_id']]);

$base = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
header('Location: ' . $base . '/' . $to);
