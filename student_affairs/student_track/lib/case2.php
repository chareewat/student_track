<?php
/**
 * เคส 2: ขาดเรียนติดต่อกัน → ครูที่ปรึกษาออก ขร.5 → บันทึกผลติดตาม + ขร.3 → ปิดรอบ
 *
 * ช่วงขาดแต่ละช่วงที่ถึงเกณฑ์ผูกกับรอบติดตามด้วยวันแรกของช่วง (trk_rounds.absent_from)
 * ครั้งที่ = จำนวนรอบเคส 2 ที่ออกแล้วในภาคเรียน (ไม่นับที่ยกเลิก) + 1 สูงสุด 3 ครั้ง
 */

require_once __DIR__ . '/case2_episodes.php';

/** เช็คชื่อรายวันของนักเรียนที่ยังศึกษาอยู่ในห้อง [std_student_id => [['day', 'status'], ...]] */
function trk_case2_daily_records(int $classroomId, int $semesterId): array
{
    $rows = trk_db_all(
        'SELECT a.std_student_id, a.sch_att_day AS day, a.sch_att_type_id AS status
         FROM sch_attendance a
         JOIN std_student st ON st.std_student_id = a.std_student_id
         WHERE st.std_classroom_id = ? AND st.std_status_id = 1 AND a.std_semester_id = ?
         ORDER BY a.std_student_id, a.sch_att_day',
        [$classroomId, $semesterId]
    );
    $byStudent = [];
    foreach ($rows as $r) {
        $byStudent[(int) $r['std_student_id']][] = ['day' => $r['day'], 'status' => (int) $r['status']];
    }
    return $byStudent;
}

/**
 * นักเรียนในห้องที่มีช่วงขาดติดต่อกันถึงเกณฑ์ พร้อมสถานะการออก ขร.5
 * แต่ละแถว: ข้อมูลนักเรียน + episodes (ทุกช่วง แต่ละช่วงมี round_id/round_no ถ้าออกแล้ว)
 *           + next_episode (ช่วงแรกที่ยังไม่ออก หรือ null) + next_round + eligible
 */
function trk_case2_candidates(int $classroomId, int $semesterId): array
{
    $minDays = (int) trk_setting('consecutive_absent_days');
    $records = trk_case2_daily_records($classroomId, $semesterId);

    $issued = [];
    foreach (trk_db_all(
        "SELECT id, std_student_id, absent_from, round_no FROM trk_rounds
         WHERE case_type = 2 AND semester_id = ? AND status <> 'cancelled' AND std_classroom_id = ?",
        [$semesterId, $classroomId]
    ) as $r) {
        $issued[(int) $r['std_student_id']][$r['absent_from']] = $r;
    }

    $students = [];
    foreach (trk_db_all(
        "SELECT st.std_student_id, st.std_student_code, st.std_student_number,
                CONCAT(IFNULL(t.psn_title_name, ''), st.std_student_name, ' ', st.std_student_surname) AS full_name
         FROM std_student st LEFT JOIN psn_title t ON t.psn_title_id = st.psn_title_id
         WHERE st.std_classroom_id = ? AND st.std_status_id = 1 ORDER BY st.std_student_number",
        [$classroomId]
    ) as $s) {
        $students[(int) $s['std_student_id']] = $s;
    }

    $result = [];
    foreach ($records as $studentId => $recs) {
        if (!isset($students[$studentId])) {
            continue;
        }
        $episodes = trk_case2_episodes($recs, $minDays);
        if (!$episodes && empty($issued[$studentId])) {
            continue;
        }
        $issuedCount = count($issued[$studentId] ?? []);
        $next = null;
        foreach ($episodes as &$ep) {
            $round = $issued[$studentId][$ep['from']] ?? null;
            $ep['round_id'] = $round ? (int) $round['id'] : null;
            $ep['round_no'] = $round ? (int) $round['round_no'] : null;
            if (!$round && $next === null) {
                $next = $ep;
            }
        }
        unset($ep);
        $row = $students[$studentId];
        $row['episodes'] = $episodes;
        $row['next_episode'] = $next;
        $row['next_round'] = $issuedCount < 3 ? $issuedCount + 1 : null;
        $row['eligible'] = $next !== null && $row['next_round'] !== null;
        $result[] = $row;
    }
    usort($result, function ($a, $b) {
        return (int) $a['std_student_number'] <=> (int) $b['std_student_number'];
    });
    return $result;
}

/**
 * ออก ขร.5 ให้นักเรียนที่เลือก (ช่วงขาดแรกที่ยังไม่ออกของแต่ละคน) เปิดรอบติดตามเคส 2
 * $docNos = [std_student_id => เลขหนังสือออก] (บังคับทุกคน)
 * คืน [std_student_id => document_id]
 */
function trk_case2_issue(int $advisorId, int $classroomId, array $docNos, string $deadline): array
{
    $semesterId = trk_semester_id();
    if (!trk_is_advisor_of($advisorId, $classroomId, $semesterId)) {
        throw new RuntimeException('คุณไม่ได้เป็นครูที่ปรึกษาของห้องนี้');
    }

    return trk_db_transaction(function () use ($advisorId, $classroomId, $docNos, $deadline, $semesterId) {
        // ล็อกรอบเคส 2 ของห้องนี้ กันการกดออกซ้ำพร้อมกัน
        trk_db_value('SELECT id FROM trk_rounds WHERE case_type = 2 AND semester_id = ? AND std_classroom_id = ? FOR UPDATE',
            [$semesterId, $classroomId]);

        $classroom = trk_db_one('SELECT * FROM std_classroom WHERE std_classroom_id = ?', [$classroomId]);
        $advisors = trk_classroom_advisors($classroomId, $semesterId);
        $semester = trk_current_semester();
        $now = date('Y-m-d H:i:s');
        $minDays = (int) trk_setting('consecutive_absent_days');
        $created = [];

        foreach (trk_case2_candidates($classroomId, $semesterId) as $c) {
            $studentId = (int) $c['std_student_id'];
            if (!isset($docNos[$studentId]) || !$c['eligible']) {
                continue;
            }
            $ep = $c['next_episode'];
            trk_db_exec(
                "INSERT INTO trk_rounds (case_type, semester_id, std_student_id, std_student_code, std_classroom_id, classroom_label,
                                         vic_subject_id, round_no, absent_from, absent_to, status, opened_by, opened_at)
                 VALUES (2, ?, ?, ?, ?, ?, NULL, ?, ?, ?, 'open', ?, ?)",
                [$semesterId, $studentId, $c['std_student_code'], $classroomId, trk_classroom_label($classroom),
                 (int) $c['next_round'], $ep['from'], $ep['to'], $advisorId, $now]
            );
            $roundId = (int) trk_db()->insert_id;

            $data = [
                'issued_date' => date('Y-m-d'),
                'semester' => ['term' => $semester['std_semester_term'], 'year' => $semester['std_semester_year']],
                'round_no' => (int) $c['next_round'],
                'doc_no_prefix' => trk_setting('doc_no_prefix'),
                'postcode' => trk_setting('school_postcode'),
                'academic_phone' => trk_setting('academic_phone'),
                'consecutive_days' => $minDays,
                'guardian' => trk_student_guardian_name($studentId),
                'student' => [
                    'id' => $studentId,
                    'code' => $c['std_student_code'],
                    'name' => $c['full_name'],
                    'level' => $classroom['std_classroom_level'],
                    'room' => $classroom['std_classroom_room'],
                ],
                'absence' => ['from' => $ep['from'], 'to' => $ep['to'], 'days' => (int) $ep['days']],
                'deadline' => $deadline,
                'advisors' => array_column($advisors, 'full_name'),
                'signatories' => ['director' => trk_setting('director_name')],
            ];
            $created[$studentId] = trk_document_create('KR5', $semesterId, $classroomId, null, (int) $c['next_round'],
                $docNos[$studentId], $data, $advisorId, [$roundId]);
        }
        if (!$created) {
            throw new RuntimeException('ไม่มีนักเรียนที่เลือกที่ออก ขร.5 ได้ (อาจถูกออกไปแล้ว)');
        }
        trk_audit('kr5.issue', ['classroom' => $classroomId, 'documents' => array_values($created)]);
        return $created;
    });
}
