<?php
require __DIR__ . '/../bootstrap.php';

if (!trk_is_post()) {
    trk_redirect('index.php');
}
trk_csrf_check();

$user = trk_user();
$doc = trk_document_get((int) ($_POST['id'] ?? 0));
$reason = trim((string) ($_POST['reason'] ?? ''));

if (!$doc || !trk_document_can_view($doc, $user['id'])) {
    trk_flash('error', 'ไม่พบเอกสาร');
    trk_redirect('index.php');
}
$back = 'documents/view.php?id=' . (int) $doc['id'];

$block = trk_document_cancel_block_reason($doc, $user['id']);
if ($block !== null) {
    trk_flash('error', $block);
    trk_redirect($back);
}
if ($reason === '') {
    trk_flash('error', 'กรุณาระบุเหตุผลที่ยกเลิก');
    trk_redirect($back);
}

trk_document_cancel((int) $doc['id'], $user['id'], mb_substr($reason, 0, 255));
trk_audit('document.cancel', ['document' => (int) $doc['id'], 'type' => $doc['doc_type'], 'reason' => $reason]);

// แจ้งครูที่ปรึกษาของห้อง (ยกเว้นผู้ยกเลิกเอง)
foreach (trk_classroom_advisors((int) $doc['std_classroom_id'], (int) $doc['semester_id']) as $a) {
    if ((int) $a['psn_mem_id'] !== $user['id']) {
        trk_notify((int) $a['psn_mem_id'], 'ยกเลิก ' . trk_document_title($doc) . ' #' . $doc['id'] . ': ' . $reason,
            $back, (int) $doc['id']);
    }
}

trk_flash('success', 'ยกเลิกเอกสารแล้ว');
trk_redirect($back);
