<?php
/**
 * หน้าที่ที่ระบบอนุมานจากข้อมูล (ไม่ใช่ role):
 *   ครูที่ปรึกษา  ← std_advisor ของภาคเรียน
 *   ครูประจำวิชา ← การเช็คชื่อรายวิชาจริง (vic_attendance_teach) รวมกับตารางสอน (t_teachtable)
 *
 * ห้องใน vic_attendance_teach เก็บเป็นข้อความ "ชั้น/ห้อง" เช่น "1/1"
 * จับคู่กับ std_classroom ที่เปิดใช้งาน (status = 1)
 */

/** SQL สำหรับ join ข้อความห้อง "1/1" กับ std_classroom */
const TRK_SQL_ROOM_LABEL = "CONCAT(c.std_classroom_level, '/', c.std_classroom_room)";

function trk_classroom_label(array $classroom): string
{
    return 'ม.' . $classroom['std_classroom_level'] . '/' . $classroom['std_classroom_room'];
}

/** ห้องที่บุคลากรเป็นครูที่ปรึกษาในภาคเรียน */
function trk_advisor_classrooms(int $memberId, int $semesterId): array
{
    return trk_db_all(
        'SELECT c.* FROM std_advisor a
         JOIN std_classroom c ON c.std_classroom_id = a.std_classroom_id
         WHERE a.psn_mem_id = ? AND a.std_semester_id = ?
         ORDER BY c.std_classroom_priority',
        [$memberId, $semesterId]
    );
}

function trk_is_advisor_of(int $memberId, int $classroomId, int $semesterId): bool
{
    return (bool) trk_db_value(
        'SELECT 1 FROM std_advisor WHERE psn_mem_id = ? AND std_classroom_id = ? AND std_semester_id = ? LIMIT 1',
        [$memberId, $classroomId, $semesterId]
    );
}

/** รายชื่อครูที่ปรึกษาทุกคนของห้อง (ห้องหนึ่งมีได้หลายคน) */
function trk_classroom_advisors(int $classroomId, int $semesterId): array
{
    return trk_db_all(
        "SELECT m.psn_mem_id, CONCAT(IFNULL(t.psn_title_name, ''), m.psn_mem_name, ' ', m.psn_mem_surname) AS full_name
         FROM std_advisor a
         JOIN psn_member m ON m.psn_mem_id = a.psn_mem_id
         LEFT JOIN psn_title t ON t.psn_title_id = m.psn_title_id
         WHERE a.std_classroom_id = ? AND a.std_semester_id = ?
         ORDER BY a.std_advisor_id",
        [$classroomId, $semesterId]
    );
}

/**
 * รายวิชา × ห้อง ที่บุคลากรสอนในภาคเรียน ตามตารางสอน (t_teachtable) เท่านั้น
 * ไม่ดึงจากประวัติเช็คชื่อ เพราะครูที่เคยเช็คชื่อแทน หรือเคยสอนก่อนเปลี่ยนตารางสอน จะเห็นวิชาที่ไม่ได้สอนแล้ว
 * ชั่วโมงขาดยังนับจากการเช็คชื่อของรายวิชา × ห้อง ทั้งหมด ไม่ว่าใครเป็นคนเช็ค
 * คืน: vic_subject_id, vic_subject_code, vic_subject_name, vic_subject_credit,
 *      std_classroom_id, std_classroom_level, std_classroom_room, periods_checked
 */
function trk_teaching_assignments(int $memberId, int $semesterId): array
{
    $label = TRK_SQL_ROOM_LABEL;
    return trk_db_all(
        "SELECT s.vic_subject_id, s.vic_subject_code, s.vic_subject_name, s.vic_subject_credit,
                c.std_classroom_id, c.std_classroom_level, c.std_classroom_room,
                COUNT(DISTINCT t.vic_att_teach_id) AS periods_checked
         FROM (
             SELECT DISTINCT tt.vic_subject_id, tt.std_classroom_id
             FROM t_teachtable tt
             WHERE tt.psn_mem_id = ? AND tt.std_semester_id = ? AND tt.t_teachtable_status = 1 AND tt.vic_subject_id > 0
         ) a
         JOIN vic_subject s ON s.vic_subject_id = a.vic_subject_id
         JOIN std_classroom c ON c.std_classroom_id = a.std_classroom_id
         LEFT JOIN vic_attendance_teach t
                ON t.vic_subject_id = a.vic_subject_id AND t.std_semester_id = ? AND t.vic_att_classroom = $label
         GROUP BY s.vic_subject_id, s.vic_subject_code, s.vic_subject_name, s.vic_subject_credit,
                  c.std_classroom_id, c.std_classroom_level, c.std_classroom_room, c.std_classroom_priority
         ORDER BY s.vic_subject_code, c.std_classroom_priority",
        [$memberId, $semesterId, $semesterId]
    );
}

function trk_is_subject_teacher(int $memberId, int $subjectId, int $classroomId, int $semesterId): bool
{
    foreach (trk_teaching_assignments($memberId, $semesterId) as $a) {
        if ((int) $a['vic_subject_id'] === $subjectId && (int) $a['std_classroom_id'] === $classroomId) {
            return true;
        }
    }
    return false;
}
