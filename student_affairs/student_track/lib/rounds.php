<?php
/**
 * รอบติดตาม (ดู CONTEXT.md) และขั้นตอนของครูที่ปรึกษา
 *
 * เคส 1: ขร.1 (ครูประจำวิชา) → ขร.2 (+ บค.14 ถ้า ม.1–3) → บันทึกผลติดตาม + ขร.3 → ปิดรอบ
 * ขร.2 หนึ่งใบรวมได้หลายรายวิชาของนักเรียนคนเดียวกัน (ชุด ขร.2) ทุกรอบในชุดติดตามด้วย ขร.3 ใบเดียวและปิดพร้อมกัน
 */

const TRK_STAGE_LABELS = [
    'need_kr2' => 'รอออก ขร.2',
    'need_followup' => 'รอบันทึกผลติดตาม',
    'closed' => 'ปิดรอบแล้ว',
    'cancelled' => 'ยกเลิก',
];

/** SQL ส่วน SELECT ของรอบติดตาม พร้อมข้อมูลที่ต้องใช้แสดงผล และ id ของเอกสาร active แต่ละชนิด */
function trk_rounds_select_sql(): string
{
    return "SELECT r.*, CONCAT(IFNULL(ti.psn_title_name, ''), st.std_student_name, ' ', st.std_student_surname) AS full_name,
                   st.std_student_number, c.std_classroom_level, c.std_classroom_room,
                   s.vic_subject_code, s.vic_subject_name,
                   (SELECT MAX(d.id) FROM trk_document_rounds dr JOIN trk_documents d ON d.id = dr.document_id
                     WHERE dr.round_id = r.id AND d.status = 'active' AND d.doc_type = 'KR1') AS kr1_id,
                   (SELECT MAX(d.id) FROM trk_document_rounds dr JOIN trk_documents d ON d.id = dr.document_id
                     WHERE dr.round_id = r.id AND d.status = 'active' AND d.doc_type = 'KR2') AS kr2_id,
                   (SELECT MAX(d.id) FROM trk_document_rounds dr JOIN trk_documents d ON d.id = dr.document_id
                     WHERE dr.round_id = r.id AND d.status = 'active' AND d.doc_type = 'BK14') AS bk14_id,
                   (SELECT MAX(d.id) FROM trk_document_rounds dr JOIN trk_documents d ON d.id = dr.document_id
                     WHERE dr.round_id = r.id AND d.status = 'active' AND d.doc_type = 'KR3') AS kr3_id
            FROM trk_rounds r
            JOIN std_student st ON st.std_student_id = r.std_student_id
            JOIN std_classroom c ON c.std_classroom_id = r.std_classroom_id
            LEFT JOIN psn_title ti ON ti.psn_title_id = st.psn_title_id
            LEFT JOIN vic_subject s ON s.vic_subject_id = r.vic_subject_id";
}

function trk_round_stage(array $round): string
{
    if ($round['status'] === 'cancelled') {
        return 'cancelled';
    }
    if ($round['status'] === 'closed') {
        return 'closed';
    }
    if ((int) $round['case_type'] === 1 && !$round['kr2_id']) {
        return 'need_kr2';
    }
    return 'need_followup';
}

function trk_round_get(int $id): ?array
{
    $round = trk_db_one(trk_rounds_select_sql() . ' WHERE r.id = ?', [$id]);
    if ($round) {
        $round['stage'] = trk_round_stage($round);
    }
    return $round;
}

/**
 * รอบติดตามในชุดเดียวกับรอบนี้ (รวมตัวเอง) เรียงตาม id
 * ชุด = รอบที่ผูกกับ ขร.2 ใบเดียวกัน (หรือ ขร.3 ใบเดียวกันสำหรับเคส 2) ถ้ายังไม่ออก ขร.2 ชุดมีแค่รอบนี้
 */
function trk_round_batch(array $round): array
{
    $docId = (int) ($round['kr2_id'] ?: $round['kr3_id']);
    if (!$docId) {
        return [$round];
    }
    $rows = trk_db_all(
        trk_rounds_select_sql() . " WHERE r.id IN (SELECT round_id FROM trk_document_rounds WHERE document_id = ?)
           AND r.status <> 'cancelled' ORDER BY r.id",
        [$docId]
    );
    foreach ($rows as &$row) {
        $row['stage'] = trk_round_stage($row);
    }
    return $rows ?: [$round];
}

/** กุญแจจัดกลุ่มสำหรับแสดงผลและนับ: รอออก ขร.2 = นักเรียน, รอติดตาม = ชุด ขร.2, ปิดแล้ว = ขร.3 ใบเดียวกัน */
function trk_round_batch_key(array $r): string
{
    if ($r['stage'] === 'need_kr2') {
        return 'S' . $r['std_student_id'] . ':' . $r['std_classroom_id'];
    }
    if ($r['stage'] === 'closed' && $r['kr3_id']) {
        return 'D' . $r['kr3_id'];
    }
    if ($r['kr2_id']) {
        return 'K' . $r['kr2_id'];
    }
    return 'R' . $r['id'];
}

/** จัดรอบเป็นกลุ่มตาม trk_round_batch_key โดยคงลำดับเดิม [key => [round, ...]] */
function trk_rounds_group(array $rounds): array
{
    $groups = [];
    foreach ($rounds as $r) {
        $groups[trk_round_batch_key($r)][] = $r;
    }
    return $groups;
}

/** รอบเคส 1 ที่รอออก ขร.2 ของนักเรียนคนเดียวกับรอบนี้ (ภาคเรียนและห้องเดียวกัน) ใช้เลือกรวมใน ขร.2 ใบเดียว */
function trk_kr2_candidate_rounds(array $round): array
{
    $rows = trk_db_all(
        trk_rounds_select_sql() . " WHERE r.case_type = 1 AND r.status = 'open' AND r.semester_id = ?
           AND r.std_student_id = ? AND r.std_classroom_id = ? ORDER BY s.vic_subject_code, r.round_no, r.id",
        [(int) $round['semester_id'], (int) $round['std_student_id'], (int) $round['std_classroom_id']]
    );
    $result = [];
    foreach ($rows as $row) {
        $row['stage'] = trk_round_stage($row);
        if ($row['stage'] === 'need_kr2') {
            $result[] = $row;
        }
    }
    return $result;
}

/** ครั้งที่ของ ขร.2 ใบถัดไปของนักเรียนในภาคเรียน (นับ ขร.2 ที่ยัง active) ใช้เป็น "ครั้งที่" ของ ขร.2, บค.14 และ ขร.3 */
function trk_kr2_next_sequence(int $studentId, int $semesterId): int
{
    return 1 + (int) trk_db_value(
        "SELECT COUNT(*) FROM trk_documents WHERE doc_type = 'KR2' AND status = 'active' AND semester_id = ?
           AND JSON_EXTRACT(data, '$.student.id') = ?",
        [$semesterId, $studentId]
    );
}

/** id ของรอบทั้งหมดในรายการ */
function trk_round_ids(array $rounds): array
{
    return array_map(function ($r) {
        return (int) $r['id'];
    }, $rounds);
}

/** รอบติดตามของห้องที่กำหนดในภาคเรียน (ไม่รวมที่ยกเลิก) */
function trk_rounds_for_classrooms(array $classroomIds, int $semesterId): array
{
    if (!$classroomIds) {
        return [];
    }
    $marks = implode(',', array_fill(0, count($classroomIds), '?'));
    $rows = trk_db_all(
        trk_rounds_select_sql() . " WHERE r.semester_id = ? AND r.std_classroom_id IN ($marks) AND r.status <> 'cancelled'
         ORDER BY c.std_classroom_priority, st.std_student_number, r.id",
        array_merge([$semesterId], array_map('intval', $classroomIds))
    );
    foreach ($rows as &$row) {
        $row['stage'] = trk_round_stage($row);
    }
    return $rows;
}

/** ครูที่ปรึกษาของห้อง (ตามห้องที่บันทึกไว้ในรอบ) ดำเนินการได้ */
function trk_round_can_act(array $round, int $memberId): bool
{
    return trk_is_advisor_of($memberId, (int) $round['std_classroom_id'], (int) $round['semester_id']);
}

/** ดูได้: ครูที่ปรึกษา, role ที่เห็นทุกห้อง, ครูประจำวิชาที่ออก ขร.1 ของรอบนั้น */
function trk_round_can_view(array $round, int $memberId): bool
{
    return trk_round_can_act($round, $memberId)
        || trk_sees_all_rooms()
        || (int) $round['opened_by'] === $memberId;
}

/** โหลดรอบจาก ?round= หรือ POST round และตรวจสิทธิ์ดำเนินการ ถ้าไม่ผ่านจะหยุดที่หน้าแจ้งข้อความ */
function trk_round_require_actionable(int $roundId, string $expectedStage): array
{
    $round = trk_round_get($roundId);
    if (!$round || !trk_round_can_act($round, trk_user()['id'])) {
        http_response_code(404);
        trk_render_message('ไม่พบรอบติดตาม', 'รอบติดตามนี้ไม่มีอยู่ หรือคุณไม่ได้เป็นครูที่ปรึกษาของห้องนี้');
        exit;
    }
    if ($round['stage'] !== $expectedStage) {
        trk_flash('error', 'รอบติดตามนี้อยู่ในขั้น "' . TRK_STAGE_LABELS[$round['stage']] . '" แล้ว');
        trk_redirect('advisor/round.php?id=' . $roundId);
    }
    return $round;
}

/** ผู้ปกครองจากข้อมูลครอบครัวในระบบเดิม (ชื่อเต็ม หรือ '') */
function trk_student_guardian_name(int $studentId): string
{
    $row = trk_db_one(
        "SELECT CONCAT(IFNULL(t.psn_title_name, ''), f.std_parent_name, ' ', f.std_parent_surname) AS name
         FROM std_family f LEFT JOIN psn_title t ON t.psn_title_id = f.std_parent_title_id
         WHERE f.std_student_id = ? ORDER BY f.std_family_id DESC LIMIT 1",
        [$studentId]
    );
    return $row ? trim($row['name']) : '';
}

/** ม.1–3 ต้องออก บค.14 คู่กับ ขร.2 */
function trk_requires_bk14(array $round): bool
{
    return (int) $round['std_classroom_level'] <= 3;
}

/**
 * จำนวนวันที่ขาดเรียน (สถานะ ขาด) ในเดือนของวันที่กำหนด และจำนวนวันขาดติดต่อกันสูงสุดในเดือนนั้น
 * วันที่ไม่มีการเช็คชื่อจะถูกข้าม (ไม่นับและไม่ตัดความต่อเนื่อง) ตาม CONTEXT.md
 */
function trk_month_absence(int $studentId, string $date): array
{
    $from = date('Y-m-01', strtotime($date));
    $to = date('Y-m-t', strtotime($date));
    $rows = trk_db_all(
        'SELECT sch_att_day, sch_att_type_id FROM sch_attendance
         WHERE std_student_id = ? AND sch_att_day BETWEEN ? AND ? ORDER BY sch_att_day',
        [$studentId, $from, $to]
    );
    $total = 0;
    $run = 0;
    $maxRun = 0;
    foreach ($rows as $r) {
        if ((int) $r['sch_att_type_id'] === 0) {
            $total++;
            $run++;
            $maxRun = max($maxRun, $run);
        } else {
            $run = 0;
        }
    }
    return ['days' => $total, 'max_consecutive' => $maxRun];
}

/** ล็อกแถวรอบติดตามภายใน transaction แล้วตรวจขั้นอีกครั้ง กันการกดซ้ำ/ส่งพร้อมกัน */
function trk_round_lock(int $roundId, string $expectedStage): void
{
    trk_db_value('SELECT id FROM trk_rounds WHERE id = ? FOR UPDATE', [$roundId]);
    $fresh = trk_round_get($roundId);
    if (!$fresh || $fresh['stage'] !== $expectedStage) {
        throw new RuntimeException('รอบติดตามนี้ถูกดำเนินการไปแล้ว');
    }
}

// ---------------------------------------------------------------- ขร.2 + บค.14

/**
 * ออก ขร.2 (และ บค.14 ถ้า ม.1–3) ใบเดียวให้รอบติดตามเคส 1 หลายรอบ (หลายรายวิชา) ของนักเรียนคนเดียวกัน
 * $rounds ต้องมาจาก trk_kr2_candidate_rounds() คืน [kr2_id, bk14_id|null]
 */
function trk_issue_kr2(array $rounds, int $memberId, string $docNo, string $bk14DocNo, string $guardian): array
{
    if (!$rounds) {
        throw new RuntimeException('กรุณาเลือกรายวิชาอย่างน้อย 1 วิชา');
    }
    $round = $rounds[0];
    foreach ($rounds as $r) {
        if ((int) $r['case_type'] !== 1 || (int) $r['std_student_id'] !== (int) $round['std_student_id']
            || (int) $r['std_classroom_id'] !== (int) $round['std_classroom_id'] || (int) $r['semester_id'] !== (int) $round['semester_id']) {
            throw new RuntimeException('รวม ขร.2 ได้เฉพาะรายวิชาของนักเรียนคนเดียวกันในห้องและภาคเรียนเดียวกัน');
        }
    }
    $semester = trk_current_semester();
    $common = [
        'issued_date' => date('Y-m-d'),
        'semester' => ['term' => $semester['std_semester_term'], 'year' => $semester['std_semester_year']],
        'doc_no_prefix' => trk_setting('doc_no_prefix'),
        'postcode' => trk_setting('school_postcode'),
        'guardian' => $guardian,
        'student' => [
            'id' => (int) $round['std_student_id'],
            'code' => $round['std_student_code'],
            'name' => $round['full_name'],
            'level' => $round['std_classroom_level'],
            'room' => $round['std_classroom_room'],
        ],
        'signatories' => ['director' => trk_setting('director_name')],
    ];

    return trk_db_transaction(function () use ($rounds, $round, $memberId, $docNo, $bk14DocNo, $common) {
        $subjects = [];
        foreach ($rounds as $r) {
            trk_round_lock((int) $r['id'], 'need_kr2');
            $kr1 = trk_document_get((int) $r['kr1_id']);
            $subjects[] = [
                'code' => $kr1['data']['subject']['code'],
                'name' => $kr1['data']['subject']['name'],
                'absent_hours' => (int) $r['absent_hours'],
                'total_hours' => (int) $r['total_hours'],
                'round_no' => (int) $r['round_no'],
                'kr1_document_id' => (int) $kr1['id'],
            ];
        }
        $seq = trk_kr2_next_sequence((int) $round['std_student_id'], (int) $round['semester_id']);
        $roundIds = trk_round_ids($rounds);
        $subjectId = count($rounds) === 1 && $round['vic_subject_id'] !== null ? (int) $round['vic_subject_id'] : null;

        $kr2Data = $common + [
            'round_no' => $seq,
            'subjects' => $subjects,
            'kr1_document_id' => $subjects[0]['kr1_document_id'],
        ];
        $kr2Id = trk_document_create('KR2', (int) $round['semester_id'], (int) $round['std_classroom_id'],
            $subjectId, $seq, $docNo, $kr2Data, $memberId, $roundIds);

        $bk14Id = null;
        if (trk_requires_bk14($round)) {
            $bk14Data = $common + [
                'round_no' => $seq,
                'subjects' => $subjects,
                'month_absence' => trk_month_absence((int) $round['std_student_id'], $common['issued_date']),
                'consecutive_days' => (int) trk_setting('consecutive_absent_days'),
                'kr2_document_id' => $kr2Id,
            ];
            $bk14Id = trk_document_create('BK14', (int) $round['semester_id'], (int) $round['std_classroom_id'],
                $subjectId, $seq, $bk14DocNo !== '' ? $bk14DocNo : null, $bk14Data, $memberId, $roundIds);
        }
        trk_audit('kr2.issue', ['rounds' => $roundIds, 'kr2' => $kr2Id, 'bk14' => $bk14Id]);
        return [$kr2Id, $bk14Id];
    });
}

// ---------------------------------------------------------------- บันทึกผลติดตาม + ขร.3

/** บันทึกผลติดตามของรอบ (active) หรือ null รวมกรณีที่บันทึกไว้กับรอบอื่นในชุดเดียวกัน */
function trk_round_followup(int $roundId): ?array
{
    $f = trk_db_one(
        "SELECT * FROM trk_followups
         WHERE status = 'active'
           AND (round_id = ? OR document_id IN (SELECT document_id FROM trk_document_rounds WHERE round_id = ?))
         ORDER BY id DESC LIMIT 1",
        [$roundId, $roundId]
    );
    if ($f) {
        $f['photos'] = trk_db_all('SELECT * FROM trk_followup_photos WHERE followup_id = ? ORDER BY id', [(int) $f['id']]);
    }
    return $f;
}

/**
 * บันทึกผลติดตาม + ออก ขร.3 + ปิดทุกรอบในชุด ในครั้งเดียว
 * $rounds = trk_round_batch() ของรอบที่เลือก (รอบแรกเป็นรอบที่ผูกกับบันทึกผลติดตาม)
 * $photos = รายการไฟล์ที่ผ่าน trk_store_photo แล้ว [['path' => ..., 'name' => ...], ...]
 */
function trk_record_followup(array $rounds, int $memberId, array $input, array $photos): int
{
    $round = $rounds[0];
    $semester = trk_current_semester();
    $now = date('Y-m-d H:i:s');

    return trk_db_transaction(function () use ($rounds, $round, $memberId, $input, $photos, $semester, $now) {
        foreach ($rounds as $r) {
            trk_round_lock((int) $r['id'], 'need_followup');
        }
        trk_db_exec(
            "INSERT INTO trk_followups (round_id, semester_id, std_student_id, followup_date, method, visitors, findings,
                                        status, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)",
            [(int) $round['id'], (int) $round['semester_id'], (int) $round['std_student_id'], $input['followup_date'],
             $input['method'], $input['visitors'], $input['findings'], $memberId, $now]
        );
        $followupId = (int) trk_db()->insert_id;
        foreach ($photos as $p) {
            trk_db_exec('INSERT INTO trk_followup_photos (followup_id, file_path, original_name, created_at) VALUES (?, ?, ?, ?)',
                [$followupId, $p['path'], $p['name'], $now]);
        }

        $isCase1 = (int) $round['case_type'] === 1;
        $subjects = null;
        if ($isCase1) {
            $subjects = [];
            foreach ($rounds as $r) {
                $kr1 = $r['kr1_id'] ? trk_document_get((int) $r['kr1_id']) : null;
                $subjects[] = [
                    'code' => $kr1 ? $kr1['data']['subject']['code'] : $r['vic_subject_code'],
                    'name' => $kr1 ? $kr1['data']['subject']['name'] : $r['vic_subject_name'],
                    'absent_hours' => (int) $r['absent_hours'],
                    'total_hours' => (int) $r['total_hours'],
                    'round_no' => (int) $r['round_no'],
                ];
            }
        }
        // ครั้งที่ของ ขร.3: เคส 1 = ครั้งที่ของ ขร.2 ในชุด, เคส 2 = ครั้งที่ของรอบ
        $roundNo = (int) $round['round_no'];
        if ($isCase1 && $round['kr2_id']) {
            $roundNo = (int) (trk_db_value('SELECT round_no FROM trk_documents WHERE id = ?', [(int) $round['kr2_id']]) ?: $roundNo);
        }

        $advisors = trk_classroom_advisors((int) $round['std_classroom_id'], (int) $round['semester_id']);
        $data = [
            'issued_date' => date('Y-m-d'),
            'case_type' => (int) $round['case_type'],
            'round_no' => $roundNo,
            'semester' => ['term' => $semester['std_semester_term'], 'year' => $semester['std_semester_year']],
            'student' => [
                'id' => (int) $round['std_student_id'],
                'code' => $round['std_student_code'],
                'name' => $round['full_name'],
                'level' => $round['std_classroom_level'],
                'room' => $round['std_classroom_room'],
            ],
            'subjects' => $subjects,
            'absent_from' => $round['absent_from'],
            'absent_to' => $round['absent_to'],
            'consecutive_days' => (int) trk_setting('consecutive_absent_days'),
            'followup' => [
                'id' => $followupId,
                'date' => $input['followup_date'],
                'method' => $input['method'],
                'visitors' => $input['visitors'],
                'findings' => $input['findings'],
                'photos' => array_column($photos, 'path'),
            ],
            'advisors' => array_column($advisors, 'full_name'),
            'signatories' => [
                'director' => trk_setting('director_name'),
                'deputy_academic' => trk_setting('deputy_academic_name'),
            ],
        ];
        $roundIds = trk_round_ids($rounds);
        $subjectId = count($rounds) === 1 && $round['vic_subject_id'] !== null ? (int) $round['vic_subject_id'] : null;
        $kr3Id = trk_document_create('KR3', (int) $round['semester_id'], (int) $round['std_classroom_id'],
            $subjectId, $roundNo, $input['doc_no'] !== '' ? $input['doc_no'] : null, $data, $memberId, $roundIds);

        trk_db_exec('UPDATE trk_followups SET document_id = ? WHERE id = ?', [$kr3Id, $followupId]);
        $marks = implode(',', array_fill(0, count($roundIds), '?'));
        trk_db_exec("UPDATE trk_rounds SET status = 'closed', closed_at = ? WHERE id IN ($marks)", array_merge([$now], $roundIds));
        trk_audit('followup.record', ['rounds' => $roundIds, 'followup' => $followupId, 'kr3' => $kr3Id]);
        return $kr3Id;
    });
}

// ---------------------------------------------------------------- รูปถ่าย

const TRK_PHOTO_MAX = 4;
const TRK_PHOTO_MAX_BYTES = 10 * 1024 * 1024;
const TRK_PHOTO_MAX_SIDE = 1600;

/** แปลง $_FILES['photos'] (multiple) เป็นรายการไฟล์ที่อัปโหลดจริง */
function trk_uploaded_files(string $field): array
{
    $files = [];
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return $files;
    }
    foreach ($_FILES[$field]['name'] as $i => $name) {
        if ($_FILES[$field]['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $files[] = [
            'name' => (string) $name,
            'tmp' => $_FILES[$field]['tmp_name'][$i],
            'size' => (int) $_FILES[$field]['size'][$i],
            'error' => (int) $_FILES[$field]['error'][$i],
        ];
    }
    return $files;
}

/** ตรวจไฟล์รูป คืนข้อความผิดพลาด หรือ null */
function trk_validate_photo(array $file): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'อัปโหลดไฟล์ ' . $file['name'] . ' ไม่สำเร็จ';
    }
    if ($file['size'] > TRK_PHOTO_MAX_BYTES) {
        return 'ไฟล์ ' . $file['name'] . ' ใหญ่เกิน 10 MB';
    }
    $info = @getimagesize($file['tmp']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
        return 'ไฟล์ ' . $file['name'] . ' ไม่ใช่รูป JPG หรือ PNG';
    }
    return null;
}

/**
 * เก็บรูปไว้ใน storage/uploads/followups/YYYY/MM/ เป็น JPG ย่อด้านยาวไม่เกิน 1600px
 * (เข้ารหัสใหม่ด้วย GD เพื่อตัด metadata และไฟล์แปลกปลอม ถ้าไม่มี GD จะเก็บไฟล์เดิม)
 * คืน ['path' => path ภายใต้ storage/uploads, 'name' => ชื่อเดิม]
 */
function trk_store_photo(array $file): array
{
    $relDir = 'followups/' . date('Y/m');
    $absDir = TRK_ROOT . '/storage/uploads/' . $relDir;
    if (!is_dir($absDir)) {
        mkdir($absDir, 0775, true);
    }
    $info = getimagesize($file['tmp']);
    $name = bin2hex(random_bytes(16));

    if (function_exists('imagecreatefromjpeg')) {
        $src = $info[2] === IMAGETYPE_PNG ? imagecreatefrompng($file['tmp']) : imagecreatefromjpeg($file['tmp']);
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, TRK_PHOTO_MAX_SIDE / max($w, $h));
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);
        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $rel = $relDir . '/' . $name . '.jpg';
        imagejpeg($dst, TRK_ROOT . '/storage/uploads/' . $rel, 82);
        imagedestroy($src);
        imagedestroy($dst);
    } else {
        $rel = $relDir . '/' . $name . ($info[2] === IMAGETYPE_PNG ? '.png' : '.jpg');
        move_uploaded_file($file['tmp'], TRK_ROOT . '/storage/uploads/' . $rel);
    }
    return ['path' => $rel, 'name' => mb_substr($file['name'], 0, 255)];
}
