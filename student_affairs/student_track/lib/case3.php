<?php
/**
 * เคส 3: ติดตามครบ 3 ครั้งแล้วยังไม่มาเรียน → ครูที่ปรึกษายืนยันส่ง ขร.6 ถึงเจ้าหน้าที่วิชาการ
 *
 * กติกา (CONTEXT.md "การติดตามครบ 3 ครั้ง"):
 *  - นับบันทึกผลติดตามที่ยัง active ในภาคเรียน รวมเคส 1 และเคส 2
 *  - หลังวันที่ติดตามครั้งล่าสุด ต้องมีการเช็คชื่อรายวันอย่างน้อย 1 วัน และไม่มีวันไหนที่นักเรียนมาโรงเรียนเลย
 *  - "มาโรงเรียน" = สถานะ มา(1), กิจกรรม(3), ไม่ร่วมกิจกรรมหน้าเสาธง(4) ส่วน ขาด(0) และ ลา(2) ถือว่าไม่มา
 *  - นักเรียนที่มี ขร.6 (active) ในภาคเรียนแล้วจะไม่แสดงซ้ำ
 */

const TRK_CASE3_MIN_FOLLOWUPS = 3;
const TRK_DAILY_CAME = [1, 3, 4];

/** บันทึกผลติดตาม active ของนักเรียนในภาคเรียน เรียงตามวันที่ พร้อมข้อมูลรอบติดตาม */
function trk_student_followups(int $studentId, int $semesterId): array
{
    return trk_db_all(
        "SELECT f.*, r.case_type, r.round_no, r.absent_from, r.absent_to, r.absent_hours, r.total_hours,
                s.vic_subject_code, s.vic_subject_name
         FROM trk_followups f
         JOIN trk_rounds r ON r.id = f.round_id
         LEFT JOIN vic_subject s ON s.vic_subject_id = r.vic_subject_id
         WHERE f.std_student_id = ? AND f.semester_id = ? AND f.status = 'active'
         ORDER BY f.followup_date, f.id",
        [$studentId, $semesterId]
    );
}

/** ขร.6 ที่ยัง active ของนักเรียนในภาคเรียน (id) หรือ null */
function trk_student_active_kr6(int $studentId, int $semesterId): ?int
{
    $id = trk_db_value(
        "SELECT id FROM trk_documents WHERE doc_type = 'KR6' AND status = 'active' AND semester_id = ?
           AND JSON_EXTRACT(data, '$.student.id') = ? LIMIT 1",
        [$semesterId, $studentId]
    );
    return $id !== null ? (int) $id : null;
}

/** การมาโรงเรียนหลังวันที่กำหนด ['checked' => จำนวนวันที่เช็คชื่อ, 'came' => จำนวนวันที่มา, 'absent' => จำนวนวันที่ขาด] */
function trk_attendance_after(int $studentId, int $semesterId, string $afterDate): array
{
    $row = trk_db_one(
        'SELECT COUNT(*) AS checked,
                SUM(sch_att_type_id IN (1, 3, 4)) AS came,
                SUM(sch_att_type_id = 0) AS absent
         FROM sch_attendance WHERE std_student_id = ? AND std_semester_id = ? AND sch_att_day > ?',
        [$studentId, $semesterId, $afterDate]
    );
    return ['checked' => (int) $row['checked'], 'came' => (int) $row['came'], 'absent' => (int) $row['absent']];
}

/**
 * นักเรียนในห้องที่ติดตามครบ 3 ครั้งแล้ว พร้อมสถานะว่าเสนอ ขร.6 ได้หรือไม่
 * แต่ละแถว: ข้อมูลนักเรียน, followup_count, last_followup, after (การมาเรียนหลังติดตามครั้งล่าสุด), kr6_id, eligible, reason
 */
function trk_case3_candidates(int $classroomId, int $semesterId): array
{
    $rows = trk_db_all(
        "SELECT st.std_student_id, st.std_student_code, st.std_student_number,
                CONCAT(IFNULL(t.psn_title_name, ''), st.std_student_name, ' ', st.std_student_surname) AS full_name,
                COUNT(*) AS followup_count, MAX(f.followup_date) AS last_followup
         FROM trk_followups f
         JOIN std_student st ON st.std_student_id = f.std_student_id
         LEFT JOIN psn_title t ON t.psn_title_id = st.psn_title_id
         WHERE f.semester_id = ? AND f.status = 'active' AND st.std_classroom_id = ? AND st.std_status_id = 1
         GROUP BY st.std_student_id
         HAVING COUNT(*) >= ?
         ORDER BY st.std_student_number",
        [$semesterId, $classroomId, TRK_CASE3_MIN_FOLLOWUPS]
    );
    foreach ($rows as &$r) {
        $r['after'] = trk_attendance_after((int) $r['std_student_id'], $semesterId, $r['last_followup']);
        $r['kr6_id'] = trk_student_active_kr6((int) $r['std_student_id'], $semesterId);
        if ($r['kr6_id']) {
            $r['eligible'] = false;
            $r['reason'] = 'ส่ง ขร.6 แล้ว';
        } elseif ($r['after']['came'] > 0) {
            $r['eligible'] = false;
            $r['reason'] = 'กลับมาเรียนแล้ว ' . $r['after']['came'] . ' วัน';
        } elseif ($r['after']['checked'] === 0) {
            $r['eligible'] = false;
            $r['reason'] = 'ยังไม่มีการเช็คชื่อหลังวันที่ติดตามครั้งล่าสุด';
        } else {
            $r['eligible'] = true;
            $r['reason'] = 'ไม่มาเรียนเลยหลังติดตามครั้งล่าสุด (เช็คชื่อ ' . $r['after']['checked'] . ' วัน)';
        }
    }
    return $rows;
}

/** ครูที่ปรึกษายืนยันส่ง ขร.6 ของนักเรียน 1 คน คืน document id */
function trk_case3_issue(int $advisorId, int $studentId, string $docNo): int
{
    $semesterId = trk_semester_id();
    $student = trk_db_one(
        "SELECT st.*, c.std_classroom_level, c.std_classroom_room,
                CONCAT(IFNULL(t.psn_title_name, ''), st.std_student_name, ' ', st.std_student_surname) AS full_name
         FROM std_student st
         JOIN std_classroom c ON c.std_classroom_id = st.std_classroom_id
         LEFT JOIN psn_title t ON t.psn_title_id = st.psn_title_id
         WHERE st.std_student_id = ? AND st.std_status_id = 1",
        [$studentId]
    );
    if (!$student || !trk_is_advisor_of($advisorId, (int) $student['std_classroom_id'], $semesterId)) {
        throw new RuntimeException('คุณไม่ได้เป็นครูที่ปรึกษาของนักเรียนคนนี้');
    }
    $classroomId = (int) $student['std_classroom_id'];

    return trk_db_transaction(function () use ($advisorId, $student, $studentId, $classroomId, $semesterId, $docNo) {
        // ล็อกเอกสาร ขร.6 ของห้องในภาคเรียน กันการกดซ้ำ
        trk_db_value("SELECT id FROM trk_documents WHERE doc_type = 'KR6' AND semester_id = ? AND std_classroom_id = ? FOR UPDATE",
            [$semesterId, $classroomId]);
        $candidate = null;
        foreach (trk_case3_candidates($classroomId, $semesterId) as $c) {
            if ((int) $c['std_student_id'] === $studentId) {
                $candidate = $c;
            }
        }
        if (!$candidate || !$candidate['eligible']) {
            throw new RuntimeException('นักเรียนคนนี้ยังไม่เข้าเงื่อนไขเสนอจำหน่าย' . ($candidate ? ' (' . $candidate['reason'] . ')' : ''));
        }

        // ใช้การติดตาม 3 ครั้งล่าสุดในเอกสาร
        $followups = array_slice(trk_student_followups($studentId, $semesterId), -TRK_CASE3_MIN_FOLLOWUPS);
        $occasions = [];
        foreach ($followups as $f) {
            $item = [
                'case_type' => (int) $f['case_type'],
                'round_no' => (int) $f['round_no'],
                'followup_date' => $f['followup_date'],
                'method' => $f['method'],
            ];
            if ((int) $f['case_type'] === 2) {
                $item['from'] = $f['absent_from'];
                $item['to'] = $f['absent_to'];
                $item['days'] = (int) trk_db_value(
                    'SELECT COUNT(*) FROM sch_attendance WHERE std_student_id = ? AND sch_att_type_id = 0 AND sch_att_day BETWEEN ? AND ?',
                    [$studentId, $f['absent_from'], $f['absent_to']]
                );
            } else {
                $item['subject'] = $f['vic_subject_code'] . ' ' . $f['vic_subject_name'];
                $item['absent_hours'] = (int) $f['absent_hours'];
            }
            $occasions[] = $item;
        }

        $advisor = trk_member_profile($advisorId);
        $data = [
            'issued_date' => date('Y-m-d'),
            'semester' => ['term' => trk_current_semester()['std_semester_term'], 'year' => trk_current_semester()['std_semester_year']],
            'student' => [
                'id' => $studentId,
                'code' => $student['std_student_code'],
                'name' => $student['full_name'],
                'level' => $student['std_classroom_level'],
                'room' => $student['std_classroom_room'],
            ],
            'advisor' => $advisor,
            'followup_count' => (int) $candidate['followup_count'],
            'occasions' => $occasions,
            'last_followup' => $candidate['last_followup'],
            'after' => $candidate['after'],
            'signatories' => [
                'director' => trk_setting('director_name'),
                'deputy_academic' => trk_setting('deputy_academic_name'),
            ],
        ];
        $docId = trk_document_create('KR6', $semesterId, $classroomId, null, null, $docNo !== '' ? $docNo : null,
            $data, $advisorId, []);

        // แจ้งเจ้าหน้าที่วิชาการทุกคน
        foreach (trk_db_all("SELECT psn_mem_id FROM trk_user_roles WHERE role = 'academic'") as $m) {
            trk_notify((int) $m['psn_mem_id'],
                'ขร.6 เสนอจำหน่าย ' . $student['full_name'] . ' ม.' . $student['std_classroom_level'] . '/' . $student['std_classroom_room']
                . ' จาก ' . $advisor['name'],
                'documents/view.php?id=' . $docId, $docId);
        }
        trk_audit('kr6.issue', ['student' => $studentId, 'document' => $docId]);
        return $docId;
    });
}
