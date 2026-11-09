<?php
/**
 * เคส 1: ขาดเรียนรายวิชา
 *
 * ชั่วโมงขาดรายวิชา = จำนวนคาบที่สถานะ ขาด(2), ลา(3), หนี(4) ในรายวิชานั้นตลอดภาคเรียน (1 คาบ = 1 ชั่วโมง)
 * เกณฑ์ครั้งที่ n: ชั่วโมงขาด × 100 ≥ เวลาเรียนทั้งหมด × เกณฑ์ n%
 * ครั้งที่ต้องออกถัดไป = ครั้งล่าสุดที่ออกแล้ว (ไม่นับที่ยกเลิก) + 1 ต้องออกเรียงลำดับ ไม่ข้ามครั้ง
 */

const TRK_ABSENT_STATUSES = [2, 3, 4]; // ขาด, ลา, หนี ใน vic_attendance_subject
const TRK_SUBJECT_STATUS_LABELS = [2 => 'ขาด', 3 => 'ลา', 4 => 'หนี'];

/** ระดับเกณฑ์สูงสุดที่ถึงแล้ว (0 = ยังไม่ถึงเกณฑ์ครั้งที่ 1) */
function trk_case1_level(int $absentHours, int $totalHours, array $thresholds): int
{
    if ($totalHours <= 0) {
        return 0;
    }
    $level = 0;
    foreach ($thresholds as $n => $pct) {
        if ($absentHours * 100 >= $totalHours * $pct) {
            $level = $n;
        }
    }
    return $level;
}

/** ชั่วโมงขาดขั้นต่ำของเกณฑ์ (ปัดขึ้น) */
function trk_case1_threshold_hours(int $totalHours, int $pct): int
{
    return (int) ceil($totalHours * $pct / 100);
}

/**
 * นักเรียนทุกคนที่ถูกเช็คชื่อในรายวิชา × ห้องที่สอน (ข้อความห้อง เช่น "1/1") พร้อมชั่วโมงขาด
 * นักเรียนวิชาเลือกอาจมาจากหลายห้อง จึงคืนห้องจริงของนักเรียนด้วย (std_classroom_id)
 */
function trk_case1_student_hours(int $subjectId, string $roomLabel, int $semesterId): array
{
    return trk_db_all(
        "SELECT st.std_student_id, st.std_student_code, st.std_student_number,
                CONCAT(IFNULL(ti.psn_title_name, ''), st.std_student_name, ' ', st.std_student_surname) AS full_name,
                c.std_classroom_id, c.std_classroom_level, c.std_classroom_room,
                COUNT(*) AS periods,
                SUM(a.vic_att_subject_check_status IN (2, 3, 4)) AS absent_hours,
                SUM(a.vic_att_subject_check_status = 2) AS absent,
                SUM(a.vic_att_subject_check_status = 3) AS on_leave,
                SUM(a.vic_att_subject_check_status = 4) AS skipped
         FROM vic_attendance_teach t
         JOIN vic_attendance_subject a ON a.vic_att_teach_id = t.vic_att_teach_id
         JOIN std_student st ON st.std_student_code = a.std_student_code AND st.std_status_id = 1
         JOIN std_classroom c ON c.std_classroom_id = st.std_classroom_id
         LEFT JOIN psn_title ti ON ti.psn_title_id = st.psn_title_id
         WHERE t.std_semester_id = ? AND t.vic_subject_id = ? AND t.vic_att_classroom = ?
         GROUP BY st.std_student_id, st.std_student_code, st.std_student_number, ti.psn_title_name,
                  st.std_student_name, st.std_student_surname,
                  c.std_classroom_id, c.std_classroom_level, c.std_classroom_room, c.std_classroom_priority
         ORDER BY c.std_classroom_priority, st.std_student_number",
        [$semesterId, $subjectId, $roomLabel]
    );
}

/** ครั้งที่ล่าสุดที่ออก ขร.1 แล้ว ของนักเรียนแต่ละคนในรายวิชา [std_student_id => round_no] */
function trk_case1_issued_rounds(int $subjectId, int $semesterId): array
{
    $rows = trk_db_all(
        "SELECT std_student_id, MAX(round_no) AS last_round FROM trk_rounds
         WHERE case_type = 1 AND semester_id = ? AND vic_subject_id = ? AND status <> 'cancelled'
         GROUP BY std_student_id",
        [$semesterId, $subjectId]
    );
    $map = [];
    foreach ($rows as $r) {
        $map[(int) $r['std_student_id']] = (int) $r['last_round'];
    }
    return $map;
}

/**
 * รายชื่อนักเรียนที่ถึงเกณฑ์ครั้งที่ 1 ขึ้นไปของรายวิชา × ห้องที่สอน
 * แต่ละแถวเพิ่ม: total_hours, percent, level, last_round, next_round (null = ออกครบแล้ว/ยังไม่ถึง), eligible
 */
function trk_case1_candidates(array $subject, string $roomLabel, int $semesterId): array
{
    $thresholds = trk_kr1_thresholds();
    $total = trk_subject_total_hours((int) $subject['vic_subject_id'], (float) $subject['vic_subject_credit']);
    $issued = trk_case1_issued_rounds((int) $subject['vic_subject_id'], $semesterId);

    $result = [];
    foreach (trk_case1_student_hours((int) $subject['vic_subject_id'], $roomLabel, $semesterId) as $row) {
        $absent = (int) $row['absent_hours'];
        $level = trk_case1_level($absent, $total, $thresholds);
        $last = $issued[(int) $row['std_student_id']] ?? 0;
        if ($level === 0 && $last === 0) {
            continue;
        }
        $next = $last < 3 ? $last + 1 : null;
        $row['total_hours'] = $total;
        $row['percent'] = $total > 0 ? round($absent * 100 / $total, 1) : 0;
        $row['level'] = $level;
        $row['last_round'] = $last;
        $row['next_round'] = $next;
        $row['eligible'] = $next !== null && $level >= $next;
        $result[] = $row;
    }
    return $result;
}

/** วันที่/คาบที่นักเรียนขาด ลา หนี ในรายวิชา × ห้องที่สอน (ใช้ทำเอกสารแนบ) */
function trk_case1_absence_details(string $studentCode, int $subjectId, string $roomLabel, int $semesterId): array
{
    return trk_db_all(
        'SELECT t.vic_att_date_teach AS date, t.vic_att_period AS period, a.vic_att_subject_check_status AS status
         FROM vic_attendance_teach t
         JOIN vic_attendance_subject a ON a.vic_att_teach_id = t.vic_att_teach_id
         WHERE t.std_semester_id = ? AND t.vic_subject_id = ? AND t.vic_att_classroom = ?
           AND a.std_student_code = ? AND a.vic_att_subject_check_status IN (2, 3, 4)
         ORDER BY t.vic_att_date_teach, t.vic_att_period',
        [$semesterId, $subjectId, $roomLabel, $studentCode]
    );
}

/** ช่วงวันที่สอนรายวิชา × ห้องในภาคเรียน [first, last] */
function trk_case1_teaching_range(int $subjectId, string $roomLabel, int $semesterId): array
{
    $row = trk_db_one(
        'SELECT MIN(vic_att_date_teach) AS first_date, MAX(vic_att_date_teach) AS last_date
         FROM vic_attendance_teach WHERE std_semester_id = ? AND vic_subject_id = ? AND vic_att_classroom = ?',
        [$semesterId, $subjectId, $roomLabel]
    );
    return [$row['first_date'] ?? null, $row['last_date'] ?? null];
}

/** ข้อความห้องของระบบเช็คชื่อ "1/1" จากแถว std_classroom */
function trk_room_code(array $classroom): string
{
    return $classroom['std_classroom_level'] . '/' . $classroom['std_classroom_room'];
}

/**
 * ออก ขร.1 ให้นักเรียนที่เลือก จากรายวิชา × ห้องที่ครูสอน
 * แยกเอกสาร 1 ใบต่อ (ห้องจริงของนักเรียน × ครั้งที่) คืน id ของเอกสารที่สร้าง
 * ตรวจสิทธิ์และเกณฑ์ใหม่ทั้งหมดที่ฝั่ง server
 */
function trk_case1_issue(int $teacherId, int $subjectId, int $teachingClassroomId, array $studentIds, string $docNo): array
{
    $semesterId = trk_semester_id();
    if (!trk_is_subject_teacher($teacherId, $subjectId, $teachingClassroomId, $semesterId)) {
        throw new RuntimeException('คุณไม่ได้เป็นครูประจำวิชานี้ในห้องนี้');
    }
    $subject = trk_db_one(
        'SELECT s.*, g.vic_subject_group_name FROM vic_subject s
         LEFT JOIN vic_subject_group g ON g.vic_subject_group_id = s.vic_subject_group_id
         WHERE s.vic_subject_id = ?',
        [$subjectId]
    );
    $teachingRoom = trk_db_one('SELECT * FROM std_classroom WHERE std_classroom_id = ?', [$teachingClassroomId]);
    $roomLabel = trk_room_code($teachingRoom);
    $studentIds = array_map('intval', $studentIds);

    return trk_db_transaction(function () use ($teacherId, $subject, $roomLabel, $studentIds, $docNo, $semesterId) {
        // ล็อกกันการกดออกซ้ำพร้อมกัน
        trk_db_value('SELECT id FROM trk_rounds WHERE case_type = 1 AND semester_id = ? AND vic_subject_id = ? FOR UPDATE',
            [$semesterId, (int) $subject['vic_subject_id']]);

        $groups = [];
        foreach (trk_case1_candidates($subject, $roomLabel, $semesterId) as $c) {
            if (in_array((int) $c['std_student_id'], $studentIds, true) && $c['eligible']) {
                $groups[$c['std_classroom_id'] . ':' . $c['next_round']][] = $c;
            }
        }
        if (!$groups) {
            throw new RuntimeException('ไม่มีนักเรียนที่เลือกที่ออก ขร.1 ได้ (อาจถูกออกไปแล้ว)');
        }

        $thresholds = trk_kr1_thresholds();
        $teacher = trk_member_profile($teacherId);
        [$firstDate, $lastDate] = trk_case1_teaching_range((int) $subject['vic_subject_id'], $roomLabel, $semesterId);
        $semester = trk_current_semester();
        $now = date('Y-m-d H:i:s');
        $docIds = [];

        foreach ($groups as $students) {
            $roundNo = (int) $students[0]['next_round'];
            $classroom = $students[0];
            $classroomId = (int) $classroom['std_classroom_id'];
            $total = (int) $students[0]['total_hours'];
            $advisors = trk_classroom_advisors($classroomId, $semesterId);

            $data = [
                'semester' => ['term' => $semester['std_semester_term'], 'year' => $semester['std_semester_year']],
                'issued_date' => date('Y-m-d'),
                'round_no' => $roundNo,
                'thresholds' => $thresholds,
                'hours_per_credit' => (int) trk_setting('hours_per_credit'),
                'teacher' => $teacher,
                'subject' => [
                    'id' => (int) $subject['vic_subject_id'],
                    'code' => $subject['vic_subject_code'],
                    'name' => $subject['vic_subject_name'],
                    'credit' => (float) $subject['vic_subject_credit'],
                    'group' => $subject['vic_subject_group_name'],
                    'total_hours' => $total,
                ],
                'teaching_room' => $roomLabel,
                'classroom' => [
                    'id' => $classroomId,
                    'level' => $classroom['std_classroom_level'],
                    'room' => $classroom['std_classroom_room'],
                ],
                'from_date' => $firstDate,
                'to_date' => $lastDate,
                'threshold_hours' => trk_case1_threshold_hours($total, $thresholds[$roundNo]),
                'advisors' => array_column($advisors, 'full_name'),
                'signatories' => [
                    'director' => trk_setting('director_name'),
                    'deputy_academic' => trk_setting('deputy_academic_name'),
                ],
                'students' => [],
            ];

            $roundIds = [];
            foreach ($students as $s) {
                $data['students'][] = [
                    'id' => (int) $s['std_student_id'],
                    'code' => $s['std_student_code'],
                    'number' => (int) $s['std_student_number'],
                    'name' => $s['full_name'],
                    'absent_hours' => (int) $s['absent_hours'],
                    'percent' => $s['percent'],
                    'details' => trk_case1_absence_details($s['std_student_code'], (int) $subject['vic_subject_id'], $roomLabel, $semesterId),
                ];
                trk_db_exec(
                    "INSERT INTO trk_rounds (case_type, semester_id, std_student_id, std_student_code, std_classroom_id, classroom_label,
                                             vic_subject_id, round_no, absent_hours, total_hours, status, opened_by, opened_at)
                     VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', ?, ?)",
                    [$semesterId, (int) $s['std_student_id'], $s['std_student_code'], $classroomId,
                     trk_classroom_label($classroom), (int) $subject['vic_subject_id'], $roundNo,
                     (int) $s['absent_hours'], $total, $teacherId, $now]
                );
                $roundIds[] = (int) trk_db()->insert_id;
            }

            $docId = trk_document_create('KR1', $semesterId, $classroomId, (int) $subject['vic_subject_id'], $roundNo,
                $docNo !== '' ? $docNo : null, $data, $teacherId, $roundIds);
            $docIds[] = $docId;

            foreach ($advisors as $a) {
                trk_notify((int) $a['psn_mem_id'],
                    'ขร.1 ครั้งที่ ' . $roundNo . ' ' . $subject['vic_subject_code'] . ' ' . $subject['vic_subject_name']
                    . ' ' . trk_classroom_label($classroom) . ' (' . count($students) . ' คน) จาก ' . $teacher['name'],
                    'documents/view.php?id=' . $docId, $docId);
            }
        }
        trk_audit('kr1.issue', ['documents' => $docIds, 'subject' => (int) $subject['vic_subject_id'], 'room' => $roomLabel]);
        return $docIds;
    });
}
