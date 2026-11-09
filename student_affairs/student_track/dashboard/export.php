<?php
/**
 * ดาวน์โหลดรอบติดตามของภาคเรียนเป็น CSV (UTF-8 พร้อม BOM ให้ Excel อ่านภาษาไทยได้)
 */
require __DIR__ . '/../bootstrap.php';
trk_require_role(...TRK_ROLES_SEE_ALL);

$semesterId = trk_semester_id();
$rounds = trk_db_all(
    trk_rounds_select_sql() . ' WHERE r.semester_id = ? ORDER BY c.std_classroom_priority, st.std_student_number, r.id',
    [$semesterId]
);
$docNos = [];
foreach (trk_db_all(
    "SELECT dr.round_id, d.doc_type, d.doc_no FROM trk_document_rounds dr JOIN trk_documents d ON d.id = dr.document_id
     WHERE d.status = 'active' AND d.semester_id = ?",
    [$semesterId]
) as $d) {
    $docNos[(int) $d['round_id']][$d['doc_type']] = $d['doc_no'];
}

trk_audit('dashboard.export', ['semester' => $semesterId, 'rows' => count($rounds)]);

$filename = 'student-track-' . str_replace('/', '-', trk_semester_label()) . '-' . date('Ymd') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

/** กันสูตร Excel (CSV injection): ข้อความที่ขึ้นต้นด้วย = + - @ ให้นำหน้าด้วย ' */
function trk_csv_safe(array $row): array
{
    return array_map(function ($v) {
        $v = (string) $v;
        return ($v !== '' && strpos('=+-@', $v[0]) !== false) ? "'" . $v : $v;
    }, $row);
}

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['รอบ', 'เลขประจำตัว', 'ชื่อ-สกุล', 'ห้อง', 'เคส', 'รายวิชา', 'ครั้งที่', 'ชั่วโมงขาด/ทั้งหมด',
    'ขาดตั้งแต่', 'ขาดถึง', 'ขั้น', 'เปิดรอบ', 'ปิดรอบ', 'เลข ขร.2', 'เลข ขร.5', 'วันที่ติดตาม', 'ผลการติดตาม']);
foreach ($rounds as $r) {
    $r['stage'] = trk_round_stage($r);
    $f = trk_round_followup((int) $r['id']);
    fputcsv($out, trk_csv_safe([
        $r['id'],
        $r['std_student_code'],
        $r['full_name'],
        $r['classroom_label'],
        (int) $r['case_type'] === 1 ? 'ขาดรายวิชา' : 'ขาดติดต่อกัน',
        $r['vic_subject_code'] ? $r['vic_subject_code'] . ' ' . $r['vic_subject_name'] : '',
        $r['round_no'],
        (int) $r['case_type'] === 1 ? $r['absent_hours'] . '/' . $r['total_hours'] : '',
        $r['absent_from'],
        $r['absent_to'],
        TRK_STAGE_LABELS[$r['stage']],
        $r['opened_at'],
        $r['closed_at'],
        $docNos[(int) $r['id']]['KR2'] ?? '',
        $docNos[(int) $r['id']]['KR5'] ?? '',
        $f ? $f['followup_date'] : '',
        $f ? $f['findings'] : '',
    ]));
}
fclose($out);
