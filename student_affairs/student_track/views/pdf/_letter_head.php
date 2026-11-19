<?php
/**
 * หัวหนังสือภายนอก (ขร.2, บค.14, ขร.5)
 *  - ตราครุฑสูง 3 ซม. กลางหน้า
 *  - "ที่ ..." (ซ้าย) และชื่อโรงเรียน (ขวา) อยู่ระนาบเดียวกับเท้าครุฑ
 *  - ที่อยู่โรงเรียนเริ่มจากกึ่งกลางหน้าเยื้องขวา 2 แท็บ
 *  - วันที่เริ่มที่กึ่งกลางหน้า
 * @var array $doc
 * @var string $formCode  เช่น "แบบ ขร.๒"
 * @var string $docNoPrefix  เลขนำหน้า เช่น "ศธ 04294.32/" หรือ "ศธ 04294.32/ว"
 * @var string $school, $amphur, $province
 */
$d = $doc['data'];
$date = trk_thai_date_parts($d['issued_date']);
$half = TRK_PDF_TEXT_WIDTH_MM / 2;
$garuda = 30.0;                       // ครุฑ 3 x 3 ซม.
$left = $half - $garuda / 2;          // ช่อง "ที่" จบที่ขอบซ้ายของครุฑ
$addressPad = 25.4 - $garuda / 2;     // ที่อยู่เริ่มที่กึ่งกลาง + 2 แท็บ
$mm = function (float $v): string {
    return number_format($v, 1, '.', '') . 'mm';
};
?>
<?php if ($doc['status'] === 'cancelled'): ?>
  <div class="void">ยกเลิกแล้ว: <?= e($doc['cancel_reason']) ?></div>
<?php endif; ?>
<div class="form-code"><?= e($formCode) ?></div>
<table width="100%" class="letter-head">
  <tr>
    <td style="width: <?= $mm($left) ?>; vertical-align: bottom;">ที่ <?= e($docNoPrefix) ?><?= trk_pdf_fill($doc['doc_no'], 12) ?></td>
    <td style="width: <?= $mm($garuda) ?>; text-align: center; vertical-align: bottom;">
      <img src="<?= e(TRK_ROOT . '/assets/img/garuda.jpg') ?>" style="height: 3cm; width: 3cm;">
    </td>
    <td style="vertical-align: bottom; padding-left: <?= $mm($addressPad) ?>; padding-bottom: 0;"><?= e($school) ?></td>
  </tr>
  <tr>
    <td></td>
    <td></td>
    <td style="padding-left: <?= $mm($addressPad) ?>; padding-top: 0;">
      อำเภอ<?= e($amphur) ?><br>
      จังหวัด<?= e($province) ?> <?= e($d['postcode'] ?? '') ?>
    </td>
  </tr>
</table>
<table width="100%" style="margin-top: 6pt;">
  <tr>
    <td style="width: <?= $mm($half) ?>;"></td>
    <td>วันที่ <?= e($date['day']) ?> <?= e($date['month']) ?> พ.ศ. <?= e($date['year']) ?></td>
  </tr>
</table>
