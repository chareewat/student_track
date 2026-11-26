<?php
/**
 * สร้าง PDF ด้วย mPDF ฟอนต์ TH Sarabun New เนื้อหา 16pt เลขไทย (แบบเดียวกับหนังสือราชการและไฟล์ Word)
 * ฟอนต์ Sarabun เดิมยังเก็บไว้ใน assets/fonts (ชื่อ sarabun) เผื่อต้องการกลับไปใช้
 */

/** ความกว้างพื้นที่ข้อความ (มม.) ของเอกสารที่กำลังสร้าง ใช้คำนวณตำแหน่งกึ่งกลางหน้า */
function trk_pdf_text_area_mm(): float
{
    return trk_page_text_width_cm($GLOBALS['trk_pdf_family'] ?? 'memo') * 10;
}

/** $family = 'memo' (บันทึกข้อความ) หรือ 'letter' (หนังสือภายนอก) กำหนดขอบกระดาษ */
function trk_pdf_new(string $title, string $family = 'memo'): \Mpdf\Mpdf
{
    $margin = TRK_PAGE_MARGINS_CM[$family];
    require_once TRK_ROOT . '/vendor/autoload.php';

    $config = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $fonts = (new \Mpdf\Config\FontVariables())->getDefaults();

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'tempDir' => TRK_ROOT . '/storage/tmp',
        'fontDir' => array_merge($config['fontDir'], [TRK_ROOT . '/assets/fonts']),
        'fontdata' => $fonts['fontdata'] + [
            'thsarabunnew' => [
                'R' => 'THSarabunNew.ttf',
                'B' => 'THSarabunNew-Bold.ttf',
                'I' => 'THSarabunNew-Italic.ttf',
                'BI' => 'THSarabunNew-BoldItalic.ttf',
                // ต้องเปิด OTL เพื่อวางสระ/วรรณยุกต์ให้ถูกที่ ไฟล์ใน assets/fonts เติมอักขระตัดคำ U+200B (กว้าง 0) ไว้แล้ว
                // เพราะ mPDF แทรก U+200B ระหว่างคำไทย ถ้าใช้ไฟล์ต้นฉบับจะเห็นเป็นช่องสี่เหลี่ยม (ดู assets/fonts/THSarabunNew-NOTE.txt)
                'useOTL' => 0xFF,
            ],
            'sarabun' => [
                'R' => 'Sarabun-Regular.ttf',
                'B' => 'Sarabun-Bold.ttf',
                'I' => 'Sarabun-Italic.ttf',
                'BI' => 'Sarabun-BoldItalic.ttf',
                'useOTL' => 0xFF,
            ],
        ],
        'default_font' => 'thsarabunnew',
        'default_font_size' => 16,
        'margin_left' => $margin['left'] * 10,
        'margin_right' => $margin['right'] * 10,
        'margin_top' => $margin['top'] * 10,
        'margin_bottom' => $margin['bottom'] * 10,
        'autoScriptToLang' => true,
        'autoLangToFont' => false,
        // ห้ามย่อตาราง/ตัวอักษรให้พอดีหน้า (ค่าเริ่มต้นของ mPDF ย่อได้ถึง 1.4 เท่า) ให้ล้นไปหน้าถัดไปแทน
        'shrink_tables_to_fit' => 0,
    ]);
    $GLOBALS['trk_mpdf'] = $mpdf;
    $GLOBALS['trk_pdf_family'] = $family;
    $mpdf->SetTitle($title);
    $mpdf->SetCreator('ระบบติดตามนักเรียน');
    return $mpdf;
}

/** ความกว้างข้อความ (มม.) ด้วยฟอนต์เนื้อหา 16pt ใช้จัดตำแหน่งส่วนท้ายหนังสือ */
function trk_pdf_text_width_mm(string $text, float $sizePt = 16, string $style = ''): float
{
    $mpdf = $GLOBALS['trk_mpdf'] ?? null;
    if (!$mpdf) {
        return mb_strlen($text) * 1.85; // ประมาณ ถ้ายังไม่ได้สร้าง mPDF
    }
    $mpdf->SetFont('thsarabunnew', $style, $sizePt);
    return (float) $mpdf->GetStringWidth($text);
}

/** render เทมเพลตใน views/pdf/ เป็น HTML */
function trk_pdf_template(string $name, array $vars): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require TRK_ROOT . '/views/pdf/' . $name . '.php';
    return ob_get_clean();
}

/** ช่องติ๊ก ☐ / ☑ (ใช้ฟอนต์ DejaVu เพราะ TH Sarabun New ไม่มีสัญลักษณ์นี้) */
function trk_pdf_checkbox(bool $checked): string
{
    return '<span style="font-family: dejavusans; font-size: 10pt;">' . ($checked ? '☑' : '☐') . '</span>';
}

/** ข้อความในช่องเติม ถ้าว่างให้เป็นจุดไข่ปลา */
function trk_pdf_fill(?string $value, int $dots = 20): string
{
    $value = trim((string) $value);
    return $value !== '' ? e($value) : str_repeat('.', $dots);
}
