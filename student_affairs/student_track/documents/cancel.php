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

$dependents = trk_document_cancel((int) $doc['id'], $user['id'], mb_substr($reason, 0, 255));
trk_audit('document.cancel', ['document' => (int) $doc['id'], 'type' => $doc['doc_type'], 'reason' => $reason,
    'also_cancelled' => array_map('intval', array_column($dependents, 'id'))]);
$alsoText = $dependents ? ' (ยกเลิกขั้นถัดไปตามด้วย ' . count($dependents) . ' ฉบับ)' : '';

// แจ้งครูที่ปรึกษาของห้อง (ยกเว้นผู้ยกเลิกเอง)
foreach (trk_classroom_advisors((int) $doc['std_classroom_id'], (int) $doc['semester_id']) as $a) {
    if ((int) $a['psn_mem_id'] !== $user['id']) {
        trk_notify((int) $a['psn_mem_id'], 'ยกเลิก ' . trk_document_title($doc) . ' #' . $doc['id'] . $alsoText . ': ' . $reason,
            $back, (int) $doc['id']);
    }
}

// ขร.6 ส่งถึงเจ้าหน้าที่วิชาการ จึงแจ้งการยกเลิกด้วย (ทั้งยกเลิกตรง ๆ และถูกยกเลิกตามขั้นก่อนหน้า)
$kr6Ids = $doc['doc_type'] === 'KR6' ? [(int) $doc['id']] : [];
foreach ($dependents as $dep) {
    if ($dep['doc_type'] === 'KR6') {
        $kr6Ids[] = (int) $dep['id'];
    }
}
foreach ($kr6Ids as $kr6Id) {
    $via = $kr6Id === (int) $doc['id'] ? '' : ' (ตาม ' . trk_document_title($doc) . ' #' . $doc['id'] . ')';
    foreach (trk_db_all("SELECT psn_mem_id FROM trk_user_roles WHERE role = 'academic'") as $m) {
        if ((int) $m['psn_mem_id'] !== $user['id']) {
            trk_notify((int) $m['psn_mem_id'], 'ยกเลิก ขร.6 #' . $kr6Id . ' ' . $doc['data']['student']['name'] . $via . ': ' . $reason,
                'documents/view.php?id=' . $kr6Id, $kr6Id);
        }
    }
}

trk_flash('success', 'ยกเลิกเอกสารแล้ว' . $alsoText);
trk_redirect($back);
