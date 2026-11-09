<?php
/**
 * เอกสาร ขร. และการยกเลิกเอกสาร (ดู CONTEXT.md)
 */

const TRK_DOC_TYPES = [
    'KR1' => 'ขร.1',
    'KR2' => 'ขร.2',
    'BK14' => 'บค.14',
    'KR3' => 'ขร.3',
    'KR5' => 'ขร.5',
    'KR6' => 'ขร.6',
];

function trk_document_create(string $type, int $semesterId, int $classroomId, ?int $subjectId, ?int $roundNo,
                             ?string $docNo, array $data, int $issuedBy, array $roundIds): int
{
    trk_db_exec(
        "INSERT INTO trk_documents (doc_type, semester_id, std_classroom_id, vic_subject_id, round_no, doc_no, data, status, issued_by, issued_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)",
        [$type, $semesterId, $classroomId, $subjectId, $roundNo, $docNo,
         json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE), $issuedBy, date('Y-m-d H:i:s')]
    );
    $id = (int) trk_db()->insert_id;
    foreach ($roundIds as $roundId) {
        trk_db_exec('INSERT INTO trk_document_rounds (document_id, round_id) VALUES (?, ?)', [$id, $roundId]);
    }
    return $id;
}

/** เอกสาร + data (array) + ชื่อผู้ออก หรือ null */
function trk_document_get(int $id): ?array
{
    $doc = trk_db_one(
        "SELECT d.*, CONCAT(IFNULL(t.psn_title_name, ''), m.psn_mem_name, ' ', m.psn_mem_surname) AS issued_by_name
         FROM trk_documents d
         JOIN psn_member m ON m.psn_mem_id = d.issued_by
         LEFT JOIN psn_title t ON t.psn_title_id = m.psn_title_id
         WHERE d.id = ?",
        [$id]
    );
    if ($doc) {
        $doc['data'] = json_decode($doc['data'], true);
    }
    return $doc;
}

/** ผู้ออก, ครูที่ปรึกษาของห้อง (ในภาคเรียนของเอกสาร) และ role ที่เห็นทุกห้อง */
function trk_document_can_view(array $doc, int $memberId): bool
{
    return (int) $doc['issued_by'] === $memberId
        || trk_sees_all_rooms()
        || trk_is_advisor_of($memberId, (int) $doc['std_classroom_id'], (int) $doc['semester_id']);
}

/**
 * ยกเลิกได้เมื่อยังไม่มีขั้นตอนถัดไป: รอบติดตามของเอกสารนี้ต้องไม่มีเอกสารอื่นที่ยัง active
 * ผู้ออกยกเลิกเองได้เฉพาะตอนที่ยังไม่มีขั้นตอนถัดไป ส่วน admin ยกเลิกได้ตลอด
 * คืนข้อความเหตุผลที่ยกเลิกไม่ได้ หรือ null ถ้ายกเลิกได้
 */
function trk_document_cancel_block_reason(array $doc, int $memberId): ?string
{
    if ($doc['status'] !== 'active') {
        return 'เอกสารนี้ถูกยกเลิกแล้ว';
    }
    $isAdmin = trk_has_role('admin');
    if ((int) $doc['issued_by'] !== $memberId && !$isAdmin) {
        return 'เฉพาะผู้ออกเอกสารหรือ Admin เท่านั้นที่ยกเลิกได้';
    }
    if (!$isAdmin && trk_document_has_next_step((int) $doc['id'])) {
        return 'มีการดำเนินการขั้นถัดไปแล้ว ต้องให้ Admin เป็นผู้ยกเลิก';
    }
    return null;
}

/**
 * มีเอกสารขั้นถัดไป (ออกหลังเอกสารนี้ ในรอบเดียวกัน และยัง active) หรือไม่
 * บค.14 เป็นเอกสารคู่ของ ขร.2 จึงไม่นับเป็นขั้นถัดไป (ยกเลิก ขร.2 แล้ว บค.14 ถูกยกเลิกตาม)
 */
function trk_document_has_next_step(int $docId): bool
{
    return (bool) trk_db_value(
        "SELECT 1 FROM trk_document_rounds dr
         JOIN trk_document_rounds other ON other.round_id = dr.round_id AND other.document_id > dr.document_id
         JOIN trk_documents d ON d.id = other.document_id AND d.status = 'active' AND d.doc_type <> 'BK14'
         WHERE dr.document_id = ? LIMIT 1",
        [$docId]
    );
}

/**
 * ยกเลิกเอกสาร (ไม่ลบจริง) รอบติดตามที่ไม่เหลือเอกสาร active ผูกอยู่จะถูกยกเลิกด้วย
 * เพื่อให้นักเรียนกลับมาอยู่ในรายชื่อที่ต้องออกเอกสารอีกครั้ง
 */
function trk_document_cancel(int $docId, int $memberId, string $reason): void
{
    trk_db_transaction(function () use ($docId, $memberId, $reason) {
        $now = date('Y-m-d H:i:s');
        $doc = trk_db_one('SELECT id, doc_type FROM trk_documents WHERE id = ?', [$docId]);
        trk_db_exec(
            "UPDATE trk_documents SET status = 'cancelled', cancelled_by = ?, cancelled_at = ?, cancel_reason = ?
             WHERE id = ? AND status = 'active'",
            [$memberId, $now, $reason, $docId]
        );

        if ($doc['doc_type'] === 'KR2') {
            // บค.14 ที่ออกคู่กับ ขร.2 นี้ ถูกยกเลิกตาม
            trk_db_exec(
                "UPDATE trk_documents SET status = 'cancelled', cancelled_by = ?, cancelled_at = ?, cancel_reason = ?
                 WHERE doc_type = 'BK14' AND status = 'active'
                   AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.kr2_document_id')) = ?",
                [$memberId, $now, 'ยกเลิกตาม ขร.2: ' . $reason, (string) $docId]
            );
        }
        if ($doc['doc_type'] === 'KR3') {
            // ผลติดตามของ ขร.3 นี้ไม่นับ และเปิดรอบกลับมาให้บันทึกใหม่
            trk_db_exec("UPDATE trk_followups SET status = 'cancelled' WHERE document_id = ?", [$docId]);
            trk_db_exec(
                "UPDATE trk_rounds SET status = 'open', closed_at = NULL
                 WHERE status = 'closed' AND id IN (SELECT round_id FROM trk_document_rounds WHERE document_id = ?)",
                [$docId]
            );
        }
        trk_db_exec(
            "UPDATE trk_rounds r SET r.status = 'cancelled'
             WHERE r.id IN (SELECT round_id FROM trk_document_rounds WHERE document_id = ?)
               AND NOT EXISTS (
                   SELECT 1 FROM trk_document_rounds dr JOIN trk_documents d ON d.id = dr.document_id
                   WHERE dr.round_id = r.id AND d.status = 'active'
               )",
            [$docId]
        );
    });
}

/** ชื่อเอกสารสำหรับแสดงผล เช่น "ขร.1 ครั้งที่ 2" */
function trk_document_title(array $doc): string
{
    $title = TRK_DOC_TYPES[$doc['doc_type']] ?? $doc['doc_type'];
    if ($doc['round_no']) {
        $title .= ' ครั้งที่ ' . $doc['round_no'];
    }
    return $title;
}
