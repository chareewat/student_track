<?php
/**
 * สร้าง PDF ด้วย mPDF ฟอนต์ Sarabun เนื้อหา 11pt
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
            'sarabun' => [
                'R' => 'Sarabun-Regular.ttf',
                'B' => 'Sarabun-Bold.ttf',
                'I' => 'Sarabun-Italic.ttf',
                'BI' => 'Sarabun-BoldItalic.ttf',
                'useOTL' => 0xFF,
            ],
        ],
        'default_font' => 'sarabun',
        'default_font_size' => 11,
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

/** ช่องติ๊ก ☐ / ☑ (ใช้ฟอนต์ DejaVu เพราะ Sarabun ไม่มีสัญลักษณ์นี้) */
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
