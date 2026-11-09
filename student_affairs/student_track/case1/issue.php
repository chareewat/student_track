<?php
require __DIR__ . '/../bootstrap.php';

if (!trk_is_post()) {
    trk_redirect('case1/index.php');
}
trk_csrf_check();

$studentIds = isset($_POST['student_ids']) && is_array($_POST['student_ids']) ? $_POST['student_ids'] : [];
$docNo = trim((string) ($_POST['doc_no'] ?? ''));

if (!$studentIds) {
    trk_flash('error', 'กรุณาเลือกนักเรียนอย่างน้อย 1 คน');
    trk_redirect('case1/index.php');
}

try {
    $docIds = trk_case1_issue(trk_user()['id'], (int) ($_POST['subject_id'] ?? 0), (int) ($_POST['classroom_id'] ?? 0),
        $studentIds, mb_substr($docNo, 0, 30));
    trk_flash('success', 'ออก ขร.1 แล้ว ' . count($docIds) . ' ฉบับ และแจ้งครูที่ปรึกษาในระบบแล้ว');
    trk_redirect(count($docIds) === 1 ? 'documents/view.php?id=' . $docIds[0] : 'case1/index.php');
} catch (RuntimeException $ex) {
    trk_flash('error', $ex->getMessage());
    trk_redirect('case1/index.php');
}
