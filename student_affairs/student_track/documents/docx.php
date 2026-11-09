<?php
/**
 * ดาวน์โหลดเอกสาร ขร. เป็น Word (.docx) สิทธิ์เหมือนการดู/พิมพ์ PDF
 */
require __DIR__ . '/../bootstrap.php';
require_once TRK_ROOT . '/lib/docx.php';

$user = trk_user();
$doc = trk_document_get((int) ($_GET['id'] ?? 0));
if (!$doc || !trk_document_can_view($doc, $user['id'])) {
    http_response_code(404);
    trk_render_message('ไม่พบเอกสาร', 'เอกสารนี้ไม่มีอยู่ หรือคุณไม่มีสิทธิ์ดู');
    exit;
}
if (!trk_docx_supported($doc['doc_type'])) {
    trk_render_message('ยังดาวน์โหลด Word ของเอกสารชนิดนี้ไม่ได้', trk_document_title($doc));
    exit;
}

$w = trk_docx_build($doc, [
    'school' => $GLOBALS['school_name'] ?? '',
    'amphur' => $GLOBALS['amphur'] ?? '',
    'province' => $GLOBALS['province'] ?? '',
]);

// ชื่อไฟล์ เช่น "ขร.2-ม.1-1-900003-#12.docx"
$d = $doc['data'];
$who = isset($d['student']) ? 'ม.' . $d['student']['level'] . '-' . $d['student']['room'] . '-' . $d['student']['code']
    : (isset($d['subject']) ? $d['subject']['code'] . '-ม.' . $d['classroom']['level'] . '-' . $d['classroom']['room'] : '');
$name = TRK_DOC_TYPES[$doc['doc_type']] . ($doc['round_no'] ? '-ครั้งที่' . $doc['round_no'] : '') . '-' . $who . '-#' . $doc['id'] . '.docx';

trk_audit('document.docx', ['document' => (int) $doc['id']]);
$w->download($name);
