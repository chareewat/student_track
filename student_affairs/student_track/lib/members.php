<?php
/**
 * ข้อมูลบุคลากรสำหรับพิมพ์ลงเอกสาร
 */

/** ชื่อเต็ม, ตำแหน่ง (ใช้วิทยฐานะครูถ้ามี) และกลุ่มสาระ */
function trk_member_profile(int $memberId): array
{
    $row = trk_db_one(
        "SELECT CONCAT(IFNULL(t.psn_title_name, ''), m.psn_mem_name, ' ', m.psn_mem_surname) AS full_name,
                p.psn_position_name, l.psn_pos_lev_id, l.psn_pos_lev_name, s.psn_subject_name
         FROM psn_member m
         LEFT JOIN psn_title t ON t.psn_title_id = m.psn_title_id
         LEFT JOIN psn_position p ON p.psn_position_id = m.psn_position_id
         LEFT JOIN psn_position_level l ON l.psn_pos_lev_id = m.psn_pos_lev_id
         LEFT JOIN psn_subject s ON s.psn_subject_id = m.psn_subject_id
         WHERE m.psn_mem_id = ?",
        [$memberId]
    );
    // วิทยฐานะครู (id 8–11) แทนตำแหน่ง เช่น "ครูชำนาญการ"
    $levelId = (int) ($row['psn_pos_lev_id'] ?? 0);
    $position = ($levelId >= 8 && $levelId <= 11) ? $row['psn_pos_lev_name'] : ($row['psn_position_name'] ?? '');
    return [
        'id' => $memberId,
        'name' => $row['full_name'] ?? '',
        'position' => $position === '*' ? '' : $position,
        'department' => $row['psn_subject_name'] ?? '',
    ];
}
