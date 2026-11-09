<?php
require __DIR__ . '/../bootstrap.php';
require_once TRK_ROOT . '/lib/pdf.php';

$user = trk_user();
$doc = trk_document_get((int) ($_GET['id'] ?? 0));
if (!$doc || !trk_document_can_view($doc, $user['id'])) {
    http_response_code(404);
    trk_render_message('ไม่พบเอกสาร', 'เอกสารนี้ไม่มีอยู่ หรือคุณไม่มีสิทธิ์ดู');
    exit;
}

$templates = ['KR1' => 'kr1', 'KR2' => 'kr2', 'BK14' => 'bk14', 'KR3' => 'kr3', 'KR5' => 'kr5'];
if (!isset($templates[$doc['doc_type']])) {
    trk_render_message('ยังพิมพ์เอกสารชนิดนี้ไม่ได้', trk_document_title($doc));
    exit;
}

$title = trk_document_title($doc) . ' #' . $doc['id'];
$mpdf = trk_pdf_new($title);
$mpdf->WriteHTML(trk_pdf_template($templates[$doc['doc_type']], [
    'doc' => $doc,
    'school' => $GLOBALS['school_name'] ?? '',
    'amphur' => $GLOBALS['amphur'] ?? '',
    'province' => $GLOBALS['province'] ?? '',
]));
$mpdf->Output($doc['doc_type'] . '-' . $doc['id'] . '.pdf', \Mpdf\Output\Destination::INLINE);
