<?php
// ลบฟอนต์ที่มากับ mPDF ที่ไม่ได้ใช้ (ใช้ Sarabun ของโมดูลเอง) เหลือ DejaVuSans ไว้สำหรับสัญลักษณ์ ☐ ☑
$dir = __DIR__ . '/../vendor/mpdf/mpdf/ttfonts';
$keep = ['DejaVuSans.ttf', 'DejaVuSans-Bold.ttf'];
foreach (glob($dir . '/*') ?: [] as $file) {
    if (!in_array(basename($file), $keep, true)) {
        unlink($file);
    }
}
echo "pruned mPDF fonts\n";
