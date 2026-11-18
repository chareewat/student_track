<?php
/**
 * สร้าง PDF ด้วย mPDF ฟอนต์ TH Sarabun New เนื้อหา 16pt เลขไทย (แบบเดียวกับหนังสือราชการและไฟล์ Word)
 * ฟอนต์ Sarabun เดิมยังเก็บไว้ใน assets/fonts (ชื่อ sarabun) เผื่อต้องการกลับไปใช้
 */

function trk_pdf_new(string $title): \Mpdf\Mpdf
{
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
        'margin_left' => 25,
        'margin_right' => 18,
        'margin_top' => 12,
        'margin_bottom' => 12,
        'autoScriptToLang' => true,
        'autoLangToFont' => false,
    ]);
    $mpdf->SetTitle($title);
    $mpdf->SetCreator('ระบบติดตามนักเรียน');
    return $mpdf;
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
