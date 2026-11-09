<?php
/**
 * ภาคเรียนปัจจุบัน: ภาคเรียนที่วันนี้อยู่ในช่วงเปิด–ปิด
 * ถ้าไม่มี (ช่วงปิดภาค) ใช้ภาคเรียนล่าสุดที่เริ่มแล้ว แบบเดียวกับระบบเดิม
 */

function trk_current_semester(): array
{
    static $semester = null;
    if ($semester === null) {
        $today = date('Y-m-d');
        $semester = trk_db_one(
            'SELECT * FROM std_semester WHERE std_semester_beginday <= ? AND std_semester_endday >= ?
             ORDER BY std_semester_id DESC LIMIT 1',
            [$today, $today]
        ) ?: trk_db_one(
            'SELECT * FROM std_semester WHERE std_semester_beginday <= ? ORDER BY std_semester_beginday DESC LIMIT 1',
            [$today]
        ) ?: trk_db_one('SELECT * FROM std_semester ORDER BY std_semester_id DESC LIMIT 1');
    }
    return $semester;
}

function trk_semester_id(): int
{
    return (int) trk_current_semester()['std_semester_id'];
}

function trk_semester_label(?array $semester = null): string
{
    $s = $semester ?: trk_current_semester();
    return $s['std_semester_term'] . '/' . $s['std_semester_year'];
}
