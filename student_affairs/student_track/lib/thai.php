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

/** แปลงเลขอารบิกเป็นเลขไทย: "ม.1/1 ปี 2569" → "ม.๑/๑ ปี ๒๕๖๙" (ใช้กับเอกสาร PDF/Word) */
function trk_thai_digits(string $text): string
{
    return strtr($text, ['0' => '๐', '1' => '๑', '2' => '๒', '3' => '๓', '4' => '๔',
        '5' => '๕', '6' => '๖', '7' => '๗', '8' => '๘', '9' => '๙']);
}

/**
 * แปลงเลขเป็นเลขไทยเฉพาะข้อความที่แสดงใน HTML
 * ไม่แตะชื่อแท็ก/attribute (เช่น src, style="height: 1.5cm") และเนื้อหาใน <style>
 */
function trk_thai_digits_html(string $html): string
{
    // ส่วนที่ห้ามแปลง: <style>…</style>, แท็ก, และ HTML entity (เช่น &#039; ที่ e() สร้าง)
    $parts = preg_split('#(<style\b.*?</style>|<[^>]*>|&\#?[a-z0-9]+;)#si', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $i => $part) {
        if ($part !== '' && $part[0] !== '<' && $part[0] !== '&') {
            $parts[$i] = trk_thai_digits($part);
        }
    }
    return implode('', $parts);
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
