<?php
/**
 * ส่วนท้ายหนังสือภายนอก (ขร.2, บค.14, ขร.5): "ขอแสดงความนับถือ" เริ่มที่กึ่งกลางหน้า (แนวเดียวกับวันที่)
 * ชื่อผู้อำนวยการและตำแหน่งจัดกึ่งกลางใต้คำว่า "ขอแสดงความนับถือ"
 * @var array $d
 * @var string $school
 */
$closing = 'ขอแสดงความนับถือ';
$half = TRK_PDF_TEXT_WIDTH_MM / 2;                      // กึ่งกลางพื้นที่ข้อความ (จากขอบซ้าย)
$phraseCenter = $half + trk_pdf_text_width_mm($closing) / 2;
$span = min(50.0, TRK_PDF_TEXT_WIDTH_MM - $phraseCenter); // ครึ่งความกว้างของช่องชื่อ/ตำแหน่ง
?>
<table width="100%" style="margin-top: 14pt;">
  <tr>
    <td style="width: <?= number_format($half, 1, '.', '') ?>mm;"></td>
    <td><?= e($closing) ?></td>
  </tr>
</table>
<table width="100%" style="margin-top: 42pt;">
  <tr>
    <td style="width: <?= number_format($phraseCenter - $span, 1, '.', '') ?>mm;"></td>
    <td style="width: <?= number_format($span * 2, 1, '.', '') ?>mm; text-align: center;">
      (<?= trk_pdf_fill($d['signatories']['director'], 35) ?>)<br>
      ผู้อำนวยการ<?= e($school) ?>
    </td>
    <td></td>
  </tr>
</table>
