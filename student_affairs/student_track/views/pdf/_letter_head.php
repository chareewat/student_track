<?php
/**
 * หัวหนังสือภายนอก (ขร.2, บค.14, ขร.5): ตราครุฑกลาง ที่ ... ซ้าย ที่อยู่โรงเรียนขวา วันที่
 * @var array $doc
 * @var string $formCode  เช่น "แบบ ขร.๒"
 * @var string $docNoPrefix  เลขนำหน้า เช่น "ศธ 04294.32/" หรือ "ศธ 04294.32/ว"
 * @var string $school, $amphur, $province
 */
$d = $doc['data'];
$date = trk_thai_date_parts($d['issued_date']);
?>
<?php if ($doc['status'] === 'cancelled'): ?>
  <div class="void">ยกเลิกแล้ว: <?= e($doc['cancel_reason']) ?></div>
<?php endif; ?>
<div class="form-code"><?= e($formCode) ?></div>
<div style="text-align: center;"><img src="<?= e(TRK_ROOT . '/assets/img/garuda.jpg') ?>" style="height: 2cm;"></div>
<table width="100%" class="letter-head">
  <tr>
    <td width="55%">ที่ <?= e($docNoPrefix) ?><?= trk_pdf_fill($doc['doc_no'], 12) ?></td>
    <td width="45%">
      <?= e($school) ?><br>
      อำเภอ<?= e($amphur) ?><br>
      จังหวัด<?= e($province) ?> <?= e($d['postcode'] ?? '') ?>
    </td>
  </tr>
  <tr>
    <td></td>
    <td style="padding-top: 6pt;">วันที่ <?= e($date['day']) ?> เดือน <?= e($date['month']) ?> พ.ศ. <?= e($date['year']) ?></td>
  </tr>
</table>
