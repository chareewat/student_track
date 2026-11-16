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

/** เอกสารที่ครูที่ปรึกษาของห้องออก ครูที่ปรึกษาทุกคนของห้องยกเลิกแทนกันได้ (ขร.1 เป็นของครูประจำวิชา) */
const TRK_ADVISOR_DOC_TYPES = ['KR2', 'BK14', 'KR3', 'KR5', 'KR6'];

/**
 * ใครยกเลิกได้: ผู้ออก, ครูที่ปรึกษาของห้อง (เฉพาะเอกสารของครูที่ปรึกษา) หรือ admin
 * ยกเลิกเองได้เฉพาะตอนที่ยังไม่มีขั้นตอนถัดไป ส่วน admin ยกเลิกได้ตลอด และขั้นถัดไปจะถูกยกเลิกตาม
 * คืนข้อความเหตุผลที่ยกเลิกไม่ได้ หรือ null ถ้ายกเลิกได้
 */
function trk_document_cancel_block_reason(array $doc, int $memberId): ?string
{
    if ($doc['status'] !== 'active') {
        return 'เอกสารนี้ถูกยกเลิกแล้ว';
    }
    $isAdmin = trk_has_role('admin');
    $isRoomAdvisor = in_array($doc['doc_type'], TRK_ADVISOR_DOC_TYPES, true)
        && trk_is_advisor_of($memberId, (int) $doc['std_classroom_id'], (int) $doc['semester_id']);
    if ((int) $doc['issued_by'] !== $memberId && !$isRoomAdvisor && !$isAdmin) {
        return $doc['doc_type'] === 'KR1'
            ? 'เฉพาะครูประจำวิชาที่ออกเอกสารหรือ Admin เท่านั้นที่ยกเลิกได้'
            : 'เฉพาะครูที่ปรึกษาของห้องหรือ Admin เท่านั้นที่ยกเลิกได้';
    }
    if (!$isAdmin && trk_document_has_next_step((int) $doc['id'])) {
        return 'มีการดำเนินการขั้นถัดไปแล้ว ต้องให้ Admin เป็นผู้ยกเลิก';
    }
    return null;
}

/**
 * เอกสารขั้นถัดไปที่ยัง active ทั้งหมด (ไล่ต่อเป็นทอด) เรียงจากใหม่ไปเก่า
 *  - เอกสารที่ออกทีหลังในรอบติดตามเดียวกัน (ขร.1 → ขร.2/บค.14 → ขร.3, ขร.5 → ขร.3)
 *  - ขร.3 → ขร.6 ของนักเรียนคนเดียวกันในภาคเรียนที่ออกทีหลัง (ขร.6 นับจากผลติดตาม)
 * คืน [['id' => .., 'doc_type' => .., 'round_no' => ..], ...]
 */
function trk_document_dependents(int $docId): array
{
    $seen = [$docId => true];
    $queue = [$docId];
    $result = [];
    while ($queue) {
        $id = array_shift($queue);
        $doc = trk_db_one('SELECT id, doc_type, semester_id, data FROM trk_documents WHERE id = ?', [$id]);
        $next = trk_db_all(
            "SELECT DISTINCT d.id, d.doc_type, d.round_no FROM trk_document_rounds dr
             JOIN trk_document_rounds other ON other.round_id = dr.round_id AND other.document_id > dr.document_id
             JOIN trk_documents d ON d.id = other.document_id AND d.status = 'active'
             WHERE dr.document_id = ?",
            [$id]
        );
        if ($doc && $doc['doc_type'] === 'KR3') {
            $data = json_decode($doc['data'], true);
            $next = array_merge($next, trk_db_all(
                "SELECT id, doc_type, round_no FROM trk_documents
                 WHERE doc_type = 'KR6' AND status = 'active' AND semester_id = ? AND id > ?
                   AND JSON_EXTRACT(data, '$.student.id') = ?",
                [(int) $doc['semester_id'], $id, (int) ($data['student']['id'] ?? 0)]
            ));
        }
        foreach ($next as $n) {
            if (!isset($seen[(int) $n['id']])) {
                $seen[(int) $n['id']] = true;
                $queue[] = (int) $n['id'];
                $result[] = $n;
            }
        }
    }
    usort($result, function ($a, $b) {
        return (int) $b['id'] <=> (int) $a['id'];
    });
    return $result;
}

/**
 * มีเอกสารขั้นถัดไปที่ยัง active หรือไม่
 * บค.14 เป็นเอกสารคู่ของ ขร.2 จึงไม่นับเป็นขั้นถัดไป (ยกเลิก ขร.2 แล้ว บค.14 ถูกยกเลิกตาม)
 */
function trk_document_has_next_step(int $docId): bool
{
    foreach (trk_document_dependents($docId) as $d) {
        if ($d['doc_type'] !== 'BK14') {
            return true;
        }
    }
    return false;
}

/**
 * ยกเลิกเอกสาร (ไม่ลบจริง) พร้อมเอกสารขั้นถัดไปทั้งหมด (ยกเลิกจากใบใหม่ไปใบเก่า)
 * รอบติดตามที่ไม่เหลือเอกสาร active ผูกอยู่จะถูกยกเลิกด้วย เพื่อให้นักเรียนกลับมาอยู่ในรายชื่อที่ต้องออกเอกสารอีกครั้ง
 * คืนรายการเอกสารที่ถูกยกเลิกตาม (ไม่รวมใบหลัก)
 */
function trk_document_cancel(int $docId, int $memberId, string $reason): array
{
    return trk_db_transaction(function () use ($docId, $memberId, $reason) {
        $main = trk_db_one('SELECT id, doc_type, round_no FROM trk_documents WHERE id = ?', [$docId]);
        $dependents = trk_document_dependents($docId);
        foreach ($dependents as $dep) {
            trk_document_cancel_one((int) $dep['id'], $memberId,
                'ยกเลิกตาม ' . trk_document_title($main) . ' #' . $docId . ': ' . $reason);
        }
        trk_document_cancel_one($docId, $memberId, $reason);
        return $dependents;
    });
}

/** ยกเลิกเอกสารใบเดียวและปรับผลติดตาม/รอบติดตามที่ผูกอยู่ (เรียกจาก trk_document_cancel เท่านั้น) */
function trk_document_cancel_one(int $docId, int $memberId, string $reason): void
{
    $now = date('Y-m-d H:i:s');
    $doc = trk_db_one('SELECT id, doc_type FROM trk_documents WHERE id = ?', [$docId]);
    trk_db_exec(
        "UPDATE trk_documents SET status = 'cancelled', cancelled_by = ?, cancelled_at = ?, cancel_reason = ?
         WHERE id = ? AND status = 'active'",
        [$memberId, $now, mb_substr($reason, 0, 255), $docId]
    );

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

/**
 * รายวิชาใน ขร.3 เคส 1 [{code, name, absent_hours, total_hours, round_no}]
 * ขร.3 ที่ออกก่อนรองรับหลายรายวิชาเก็บเป็น subject เดียว (ครั้งที่ของ ขร.1 = round_no ของเอกสาร)
 */
function trk_kr3_subjects(array $d): array
{
    if (!empty($d['subjects'])) {
        return $d['subjects'];
    }
    if (!empty($d['subject'])) {
        return [[
            'code' => $d['subject']['code'],
            'name' => $d['subject']['name'],
            'absent_hours' => (int) ($d['absent_hours'] ?? 0),
            'total_hours' => (int) ($d['subject']['total_hours'] ?? 0),
            'round_no' => (int) $d['round_no'],
        ]];
    }
    return [];
}

/** หัวเรื่อง ขร.3 เคส 1 */
function trk_kr3_case1_title(array $subjects): string
{
    if (count($subjects) === 1) {
        return 'นักเรียนขาดเรียนรายวิชา ' . $subjects[0]['code'] . ' ' . $subjects[0]['name'];
    }
    return 'นักเรียนขาดเรียนรายวิชา จำนวน ' . count($subjects) . ' รายวิชา';
}

/** ข้อความต่อจาก "ได้" ในย่อหน้าแรกของ ขร.3 เคส 1 */
function trk_kr3_case1_detail(array $subjects): string
{
    if (count($subjects) === 1) {
        $s = $subjects[0];
        return 'ขาดเรียนรายวิชา ' . $s['code'] . ' ' . $s['name'] . ' สะสม ' . (int) $s['absent_hours']
            . ' ชั่วโมง จากเวลาเรียนทั้งหมด ' . (int) $s['total_hours'] . ' ชั่วโมง ตามที่ครูประจำวิชารายงานด้วยแบบ ขร.1 ครั้งที่ ' . (int) $s['round_no'];
    }
    $items = array_map(function ($s) {
        return 'รายวิชา ' . $s['code'] . ' ' . $s['name'] . ' สะสม ' . (int) $s['absent_hours'] . ' จาก ' . (int) $s['total_hours']
            . ' ชั่วโมง (ขร.1 ครั้งที่ ' . (int) $s['round_no'] . ')';
    }, $subjects);
    return 'ขาดเรียน ' . count($subjects) . ' รายวิชา ตามที่ครูประจำวิชารายงานด้วยแบบ ขร.1 ได้แก่ ' . implode(', ', $items);
}

/** รายละเอียดรายวิชาของการติดตามเคส 1 ใน ขร.6 (ต่อจาก "ขาดเรียนรายวิชา ") */
function trk_kr6_case1_text(array $o): string
{
    $subjects = $o['subjects'] ?? [['label' => $o['subject'], 'absent_hours' => $o['absent_hours'], 'round_no' => $o['round_no']]];
    return implode(', ', array_map(function ($s) {
        $label = $s['label'] ?? ($s['code'] . ' ' . $s['name']);
        return $label . ' สะสม ' . (int) $s['absent_hours'] . ' ชั่วโมง (รายงานตาม ขร.1 ครั้งที่ ' . (int) $s['round_no'] . ')';
    }, $subjects));
}

/**
 * รายวิชาที่ขาดใน บค.14 (ชุดเดียวกับ ขร.2 ที่ออกคู่กัน)
 * บค.14 ที่ออกก่อนเก็บรายวิชาไว้ในตัวเอง จะอ่านจาก ขร.2 ที่อ้างถึงแทน
 */
function trk_bk14_subjects(array $d): array
{
    if (!empty($d['subjects'])) {
        return $d['subjects'];
    }
    if (!empty($d['kr2_document_id'])) {
        $kr2 = trk_document_get((int) $d['kr2_document_id']);
        if ($kr2 && !empty($kr2['data']['subjects'])) {
            return $kr2['data']['subjects'];
        }
    }
    return [];
}

/** ข้อความรายวิชาที่ขาด 1 บรรทัด แบบเดียวกับ ขร.2 */
function trk_absent_subject_line(array $subj): string
{
    return 'รหัสวิชา ' . $subj['code'] . ' รายวิชา ' . $subj['name'] . ' จำนวน ' . (int) $subj['absent_hours'] . ' คาบ'
        . (isset($subj['round_no']) ? ' (ขร.1 ครั้งที่ ' . (int) $subj['round_no'] . ')' : '');
}
