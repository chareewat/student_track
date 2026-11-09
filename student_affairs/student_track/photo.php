<?php
/**
 * ส่งรูปผลติดตาม (storage/ เปิดตรงจากเว็บไม่ได้) เฉพาะผู้ที่ดูรอบติดตามนั้นได้
 */
require __DIR__ . '/bootstrap.php';

$photo = trk_db_one(
    'SELECT p.*, f.round_id FROM trk_followup_photos p JOIN trk_followups f ON f.id = p.followup_id WHERE p.id = ?',
    [(int) ($_GET['id'] ?? 0)]
);
$round = $photo ? trk_round_get((int) $photo['round_id']) : null;
if (!$photo || !$round || !trk_round_can_view($round, trk_user()['id'])) {
    http_response_code(404);
    exit;
}

$path = realpath(TRK_ROOT . '/storage/uploads/' . $photo['file_path']);
$base = realpath(TRK_ROOT . '/storage/uploads');
if ($path === false || strpos($path, $base) !== 0 || !is_file($path)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . (substr($path, -4) === '.png' ? 'image/png' : 'image/jpeg'));
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($path);
