<?php
/**
 * วันที่และตัวเลขภาษาไทย
 */

const TRK_THAI_MONTHS = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
const TRK_THAI_MONTHS_SHORT = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
    'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

/** 2026-11-09 → 9 พฤศจิกายน 2569 */
function trk_thai_date(?string $date, bool $short = false): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    $months = $short ? TRK_THAI_MONTHS_SHORT : TRK_THAI_MONTHS;
    return date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . ((int) date('Y', $ts) + 543);
}

/** แยกส่วนวัน เดือน ปี สำหรับช่อง "วันที่ ... เดือน ... พ.ศ. ..." */
function trk_thai_date_parts(string $date): array
{
    $ts = strtotime($date);
    return [
        'day' => date('j', $ts),
        'month' => TRK_THAI_MONTHS[(int) date('n', $ts)],
        'year' => (string) ((int) date('Y', $ts) + 543),
    ];
}

/** 1.50 → "1.5", 1.00 → "1.0" */
function trk_format_credit($credit): string
{
    return number_format((float) $credit, 1);
}

/** 7.5 → "7.5", 8.0 → "8" */
function trk_format_hours(float $hours): string
{
    return rtrim(rtrim(number_format($hours, 1), '0'), '.');
}
