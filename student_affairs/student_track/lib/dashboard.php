<?php
/**
 * ภาพรวมทั้งโรงเรียน (เฟส 5) สำหรับ admin, ผู้บริหาร, เจ้าหน้าที่วิชาการ, เจ้าหน้าที่กิจการนักเรียน
 */

/**
 * นักเรียนที่รอออก ขร.1 ทั้งโรงเรียน คำนวณด้วย query เดียว (ไม่วนทีละรายวิชา)
 * คืน [std_classroom_id => จำนวน (นักเรียน × รายวิชา) ที่ถึงเกณฑ์ครั้งถัดไปแต่ยังไม่ออก]
 */
function trk_dashboard_case1_waiting(int $semesterId): array
{
    $waiting = [];
    foreach (trk_dashboard_case1_waiting_rows($semesterId) as $r) {
        $cid = (int) $r['std_classroom_id'];
        $waiting[$cid] = ($waiting[$cid] ?? 0) + 1;
    }
    return $waiting;
}

/**
 * รายการ (นักเรียน × รายวิชา) ที่ถึงเกณฑ์ ขร.1 ครั้งถัดไปแต่ครูประจำวิชายังไม่ออก
 * ระบุ $classroomId เพื่อดูเฉพาะห้อง (ห้องปัจจุบันของนักเรียน)
 * แต่ละแถว: นักเรียน, รายวิชา, teachers (ครูประจำวิชาตามตารางสอน ถ้าไม่มีในตารางสอนใช้ครูที่เช็คชื่อ),
 *           absent_hours, total_hours, percent, next_round
 */
function trk_dashboard_case1_waiting_rows(int $semesterId, ?int $classroomId = null): array
{
    $thresholds = trk_kr1_thresholds();
    $params = [$semesterId];
    $roomSql = '';
    if ($classroomId !== null) {
        $roomSql = ' AND st.std_classroom_id = ?';
        $params[] = $classroomId;
    }
    $rows = trk_db_all(
        "SELECT st.std_student_id, st.std_classroom_id, st.std_student_code, st.std_student_number,
                CONCAT(IFNULL(ti.psn_title_name, ''), st.std_student_name, ' ', st.std_student_surname) AS full_name,
                t.vic_subject_id, s.vic_subject_code, s.vic_subject_name, s.vic_subject_credit,
                t.vic_att_classroom AS teach_room,
                GROUP_CONCAT(DISTINCT CONCAT(m.psn_mem_name, ' ', m.psn_mem_surname) SEPARATOR ', ') AS teachers,
                SUM(a.vic_att_subject_check_status IN (2, 3, 4)) AS absent_hours
         FROM vic_attendance_teach t
         JOIN vic_attendance_subject a ON a.vic_att_teach_id = t.vic_att_teach_id
         JOIN std_student st ON st.std_student_code = a.std_student_code AND st.std_status_id = 1
         LEFT JOIN psn_title ti ON ti.psn_title_id = st.psn_title_id
         JOIN vic_subject s ON s.vic_subject_id = t.vic_subject_id
         LEFT JOIN psn_member m ON m.psn_mem_id = t.psn_mem_id
         WHERE t.std_semester_id = ?$roomSql
         GROUP BY st.std_student_id, st.std_classroom_id, st.std_student_code, st.std_student_number,
                  ti.psn_title_name, st.std_student_name, st.std_student_surname,
                  t.vic_subject_id, s.vic_subject_code, s.vic_subject_name, s.vic_subject_credit, t.vic_att_classroom
         HAVING absent_hours > 0
         ORDER BY st.std_student_number, s.vic_subject_code",
        $params
    );
    $issued = [];
    foreach (trk_db_all(
        "SELECT std_student_id, vic_subject_id, MAX(round_no) AS last_round FROM trk_rounds
         WHERE case_type = 1 AND semester_id = ? AND status <> 'cancelled' GROUP BY std_student_id, vic_subject_id",
        [$semesterId]
    ) as $r) {
        $issued[$r['std_student_id'] . ':' . $r['vic_subject_id']] = (int) $r['last_round'];
    }
    $customHours = [];
    foreach (trk_db_all('SELECT vic_subject_id, hours FROM trk_subject_hours') as $h) {
        $customHours[(int) $h['vic_subject_id']] = (int) $h['hours'];
    }
    $perCredit = (int) trk_setting('hours_per_credit');
    $label = TRK_SQL_ROOM_LABEL;
    $timetable = [];
    foreach (trk_db_all(
        "SELECT tt.vic_subject_id, $label AS room,
                GROUP_CONCAT(DISTINCT CONCAT(m.psn_mem_name, ' ', m.psn_mem_surname) SEPARATOR ', ') AS teachers
         FROM t_teachtable tt
         JOIN std_classroom c ON c.std_classroom_id = tt.std_classroom_id
         JOIN psn_member m ON m.psn_mem_id = tt.psn_mem_id
         WHERE tt.std_semester_id = ? AND tt.t_teachtable_status = 1 AND tt.vic_subject_id > 0
         GROUP BY tt.vic_subject_id, c.std_classroom_level, c.std_classroom_room",
        [$semesterId]
    ) as $t) {
        $timetable[$t['vic_subject_id'] . ':' . $t['room']] = $t['teachers'];
    }

    $waiting = [];
    foreach ($rows as $r) {
        $total = $customHours[(int) $r['vic_subject_id']] ?? (int) round((float) $r['vic_subject_credit'] * $perCredit);
        $level = trk_case1_level((int) $r['absent_hours'], $total, $thresholds);
        $next = ($issued[$r['std_student_id'] . ':' . $r['vic_subject_id']] ?? 0) + 1;
        if ($next <= 3 && $level >= $next) {
            $r['total_hours'] = $total;
            $r['percent'] = $total > 0 ? round((int) $r['absent_hours'] * 100 / $total, 1) : 0;
            $r['next_round'] = $next;
            $r['teachers'] = $timetable[$r['vic_subject_id'] . ':' . $r['teach_room']] ?? $r['teachers'];
            $waiting[] = $r;
        }
    }
    return $waiting;
}

/** role ที่เปิดดูรายชื่อนักเรียนที่รอออกเอกสารรายห้องได้ */
const TRK_ROLES_ROOM_DETAIL = ['admin', 'academic', 'affairs'];

/** ห้องเรียนที่เปิดใช้งาน พร้อมรายชื่อครูที่ปรึกษา */
function trk_dashboard_classrooms(int $semesterId): array
{
    $rooms = trk_db_all(
        "SELECT c.*,
                (SELECT GROUP_CONCAT(CONCAT(m.psn_mem_name, ' ', m.psn_mem_surname) ORDER BY a.std_advisor_id SEPARATOR ', ')
                 FROM std_advisor a JOIN psn_member m ON m.psn_mem_id = a.psn_mem_id
                 WHERE a.std_classroom_id = c.std_classroom_id AND a.std_semester_id = ?) AS advisors
         FROM std_classroom c
         WHERE c.std_classroom_status = 1 AND c.std_classroom_id > 0
         ORDER BY c.std_classroom_priority",
        [$semesterId]
    );
    return $rooms;
}

/**
 * ตัวเลขรายห้อง: case1_waiting, case2_waiting, need_kr2, need_followup, closed, kr6
 * need_kr2 นับเป็นรายคน, need_followup และ closed นับเป็นรายชุด ขร.2 (ดู trk_round_batch_key)
 * คืน [std_classroom_id => [...]] และผลรวมทั้งโรงเรียนใน ['total']
 */
function trk_dashboard_counts(int $semesterId, array $rooms): array
{
    $counts = [];
    $blank = ['case1_waiting' => 0, 'case2_waiting' => 0, 'need_kr2' => 0, 'need_followup' => 0, 'closed' => 0, 'kr6' => 0];
    foreach ($rooms as $room) {
        $counts[(int) $room['std_classroom_id']] = $blank;
    }

    foreach (trk_dashboard_case1_waiting($semesterId) as $cid => $n) {
        if (isset($counts[$cid])) {
            $counts[$cid]['case1_waiting'] = $n;
        }
    }
    foreach ($rooms as $room) {
        $cid = (int) $room['std_classroom_id'];
        foreach (trk_case2_candidates($cid, $semesterId) as $c) {
            $counts[$cid]['case2_waiting'] += $c['eligible'] ? 1 : 0;
        }
    }
    $seen = [];
    foreach (trk_rounds_for_classrooms(array_keys($counts), $semesterId) as $r) {
        $cid = (int) $r['std_classroom_id'];
        $key = $r['stage'] . ':' . trk_round_batch_key($r);
        if (isset($counts[$cid][$r['stage']]) && !isset($seen[$key])) {
            $seen[$key] = true;
            $counts[$cid][$r['stage']]++;
        }
    }
    foreach (trk_db_all(
        "SELECT std_classroom_id, COUNT(*) AS n FROM trk_documents
         WHERE doc_type = 'KR6' AND status = 'active' AND semester_id = ? GROUP BY std_classroom_id",
        [$semesterId]
    ) as $r) {
        if (isset($counts[(int) $r['std_classroom_id']])) {
            $counts[(int) $r['std_classroom_id']]['kr6'] = (int) $r['n'];
        }
    }

    $total = $blank;
    foreach ($counts as $c) {
        foreach ($c as $k => $v) {
            $total[$k] += $v;
        }
    }
    $counts['total'] = $total;
    return $counts;
}

/**
 * รอบติดตามที่ค้างนานเกินกำหนด
 *  - รอออก ขร.2: นับจากวันที่เปิดรอบ (ขร.1) เกิน overdue_kr2_days วัน
 *  - รอบันทึกผลติดตาม: นับจากวันที่ออก ขร.2/ขร.5 เกิน overdue_followup_days วัน
 */
function trk_dashboard_overdue(int $semesterId): array
{
    $kr2Days = (int) trk_setting('overdue_kr2_days');
    $followDays = (int) trk_setting('overdue_followup_days');
    $today = new DateTime(date('Y-m-d'));
    $rows = [];
    foreach (trk_db_all(trk_rounds_select_sql() . " WHERE r.semester_id = ? AND r.status = 'open' ORDER BY r.opened_at", [$semesterId]) as $r) {
        $r['stage'] = trk_round_stage($r);
        if ($r['stage'] === 'need_kr2') {
            $since = substr($r['opened_at'], 0, 10);
            $limit = $kr2Days;
        } else {
            $stepDoc = (int) ($r['kr2_id'] ?: 0);
            $since = $stepDoc
                ? substr((string) trk_db_value('SELECT issued_at FROM trk_documents WHERE id = ?', [$stepDoc]), 0, 10)
                : substr($r['opened_at'], 0, 10);
            $limit = $followDays;
        }
        $age = (int) $today->diff(new DateTime($since))->days;
        if ($age > $limit) {
            $r['since'] = $since;
            $r['age_days'] = $age;
            $r['limit_days'] = $limit;
            $rows[] = $r;
        }
    }
    usort($rows, function ($a, $b) {
        return $b['age_days'] <=> $a['age_days'];
    });
    return $rows;
}

/** วันสุดท้ายของสัปดาห์ที่ 8 ของภาคเรียน (หมายเหตุใน ขร.2: ต้องแจ้งผู้ปกครองภายในสัปดาห์ที่ 8) */
function trk_semester_week8_end(array $semester): string
{
    return date('Y-m-d', strtotime($semester['std_semester_beginday'] . ' +8 weeks -1 day'));
}
