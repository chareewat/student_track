<?php
/**
 * ค่าที่ admin แก้ได้ เก็บใน trk_settings ถ้ายังไม่เคยตั้งจะใช้ค่าเริ่มต้นด้านล่าง
 */

function trk_setting_defaults(): array
{
    return [
        'kr1_threshold_1' => '10',          // % ชั่วโมงขาดรายวิชา ครั้งที่ 1
        'kr1_threshold_2' => '20',          // ครั้งที่ 2
        'kr1_threshold_3' => '30',          // ครั้งที่ 3
        'hours_per_credit' => '40',         // เวลาเรียนทั้งหมด = หน่วยกิต × ค่านี้
        'consecutive_absent_days' => '5',   // เคส 2
        'director_name' => '',              // ผู้อำนวยการ (พิมพ์ลงเอกสาร)
        'deputy_academic_name' => '',       // รองผู้อำนวยการกลุ่มบริหารวิชาการ
        'doc_no_prefix' => 'ศธ 04294.32/',  // นำหน้าเลขหนังสือออกถึงผู้ปกครอง (ขร.2, บค.14, ขร.5)
        'school_postcode' => '62210',
        'academic_phone' => '',             // โทรศัพท์กลุ่มบริหารวิชาการ (ขร.5)
    ];
}

function trk_settings(): array
{
    static $settings = null;
    if ($settings === null) {
        $settings = trk_setting_defaults();
        foreach (trk_db_all('SELECT setting_key, setting_value FROM trk_settings') as $row) {
            if (array_key_exists($row['setting_key'], $settings)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    return $settings;
}

function trk_setting(string $key): string
{
    $all = trk_settings();
    return $all[$key] ?? '';
}

/** เกณฑ์ขาดรายวิชา [1 => 10, 2 => 20, 3 => 30] */
function trk_kr1_thresholds(): array
{
    return [
        1 => (int) trk_setting('kr1_threshold_1'),
        2 => (int) trk_setting('kr1_threshold_2'),
        3 => (int) trk_setting('kr1_threshold_3'),
    ];
}

function trk_save_settings(array $values, int $memberId): void
{
    $defaults = trk_setting_defaults();
    $now = date('Y-m-d H:i:s');
    foreach ($values as $key => $value) {
        if (!array_key_exists($key, $defaults)) {
            continue;
        }
        trk_db_exec(
            'INSERT INTO trk_settings (setting_key, setting_value, updated_by, updated_at) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
            [$key, (string) $value, $memberId, $now]
        );
    }
}

/**
 * เวลาเรียนทั้งหมดของรายวิชา (ชั่วโมงต่อภาค)
 * ใช้ค่าที่กำหนดเองใน trk_subject_hours ก่อน ถ้าไม่มีใช้ หน่วยกิต × hours_per_credit
 * คืน 0 ถ้าคำนวณไม่ได้ (วิชาไม่มีหน่วยกิตและยังไม่ได้กำหนดชั่วโมง)
 */
function trk_subject_total_hours(int $subjectId, float $credit): int
{
    $custom = trk_db_value('SELECT hours FROM trk_subject_hours WHERE vic_subject_id = ?', [$subjectId]);
    if ($custom !== null) {
        return (int) $custom;
    }
    return (int) round($credit * (int) trk_setting('hours_per_credit'));
}
