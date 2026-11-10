<?php
/** รีเซ็ตข้อมูลสาธิตกลับจุดเริ่มต้น (POST จากหน้า demo/ เท่านั้น) แล้วเข้าระบบเป็นผู้ดูแลระบบ */
require __DIR__ . '/boot.php';
require __DIR__ . '/build.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(trk_csrf_token(), (string) ($_POST['_csrf'] ?? ''))) {
    http_response_code(400);
    exit('คำขอไม่ถูกต้อง');
}

try {
    $log = trk_demo_build();
    $_SESSION['trk_demo_flash'] = ['success', 'รีเซ็ตข้อมูลสาธิตแล้ว · ' . end($log)];
} catch (Throwable $ex) {
    $_SESSION['trk_demo_flash'] = ['error', 'รีเซ็ตไม่สำเร็จ: ' . $ex->getMessage()];
}
header('Location: switch.php?u=admin&to=demo/index.php');
