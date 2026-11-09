<?php
/**
 * แบบ ขร.3 บันทึกข้อความรายงานผลการติดตามนักเรียน
 * หัวเรื่องเปลี่ยนตามเคส: เคส 1 = ขาดเรียนรายวิชา, เคส 2 = ขาดเรียนนานติดต่อกัน (ดู CONTEXT.md)
 * หน้า 2 เป็นรูปถ่ายประกอบการติดตาม
 * @var array $doc
 * @var string $school, $amphur, $province
 */
$d = $doc['data'];
$s = $d['student'];
$f = $d['followup'];
$date = trk_thai_date_parts($d['issued_date']);
$days = (int) ($d['consecutive_days'] ?? trk_setting('consecutive_absent_days'));
$isCase1 = (int) $d['case_type'] === 1;
?>
<?php require __DIR__ . '/_style.php'; ?>

<?php if ($doc['status'] === 'cancelled'): ?>
  <div class="void">ยกเลิกแล้ว: <?= e($doc['cancel_reason']) ?></div>
<?php endif; ?>

<div class="form-code">แบบ ขร.๓</div>
<table width="100%">
  <tr>
    <td width="20%"><img src="<?= e(TRK_ROOT . '/assets/img/garuda.jpg') ?>" style="height: 1.5cm;"></td>
    <td width="60%" class="title">บันทึกข้อความ</td>
    <td width="20%"></td>
  </tr>
</table>
<div><span class="label">ส่วนราชการ</span> กลุ่มบริหารวิชาการ <?= e($school) ?></div>
<table width="100%">
  <tr>
    <td width="50%"><span class="label">ที่</span> <?= trk_pdf_fill($doc['doc_no'], 22) ?></td>
    <td width="50%"><span class="label">วันที่</span> <?= e($date['day']) ?> เดือน <?= e($date['month']) ?> พ.ศ. <?= e($date['year']) ?></td>
  </tr>
</table>
<div class="rule">
  <span class="label">เรื่อง</span>
  <?php if ($isCase1): ?>
    นักเรียนขาดเรียนรายวิชา <?= e($d['subject']['code'] . ' ' . $d['subject']['name']) ?>
  <?php else: ?>
    นักเรียนขาดเรียนนานติดต่อกัน <?= $days ?> วันทำการ
  <?php endif; ?>
  (<?php foreach ([1, 2, 3] as $n): ?><?= trk_pdf_checkbox((int) $d['round_no'] === $n) ?> ครั้งที่ <?= $n ?><?= $n < 3 ? ' ' : '' ?><?php endforeach; ?>)
</div>

<p><span class="label">เรียน</span> ผู้อำนวยการ<?= e($school) ?></p>

<p class="indent">
  ด้วย <?= e($s['name']) ?> เลขประจำตัว <?= e($s['code']) ?> นักเรียนระดับชั้น ม.<?= e($s['level']) ?> ห้อง <?= e($s['room']) ?>
  <?php if ($isCase1): ?>
    ได้ขาดเรียนรายวิชา <?= e($d['subject']['code'] . ' ' . $d['subject']['name']) ?>
    สะสม <?= (int) $d['absent_hours'] ?> ชั่วโมง จากเวลาเรียนทั้งหมด <?= (int) $d['subject']['total_hours'] ?> ชั่วโมง
    ตามที่ครูประจำวิชารายงานด้วยแบบ ขร.1 ครั้งที่ <?= (int) $d['round_no'] ?>
  <?php else: ?>
    ได้ขาดเรียนติดต่อกัน <?= $days ?> วันทำการ ตั้งแต่วันที่ <?= e(trk_thai_date($d['absent_from'])) ?>
    ถึงวันที่ <?= e(trk_thai_date($d['absent_to'])) ?>
  <?php endif; ?>
</p>
<p class="indent">
  คุณครูที่ปรึกษาได้ดำเนินการติดตามนักเรียน (<?= e($f['method']) ?>) เมื่อวันที่ <?= e(trk_thai_date($f['date'])) ?>
  ร่วมกับ <?= e($f['visitors']) ?> พบว่า <?= nl2br(e($f['findings'])) ?>
</p>
<p class="indent">จึงเรียนมาเพื่อโปรดพิจารณา</p>

<table width="100%" class="sign">
  <tr>
    <?php foreach (array_slice($d['advisors'] ?: [''], 0, 3) as $advisor): ?>
      <td>
        ลงชื่อ ....................................<br>
        (<?= trk_pdf_fill($advisor, 35) ?>)<br>
        ครูที่ปรึกษา
      </td>
    <?php endforeach; ?>
  </tr>
</table>

<div style="margin-top: 8pt;"><b>ความเห็นรองผู้อำนวยการกลุ่มบริหารวิชาการ</b></div>
<div class="dots"></div>
<div class="dots"></div>
<table width="100%" class="sign">
  <tr><td width="40%"></td><td>
    (<?= trk_pdf_fill($d['signatories']['deputy_academic'], 35) ?>)<br>
    รองผู้อำนวยการกลุ่มบริหารวิชาการ
  </td></tr>
</table>

<div style="margin-top: 8pt;"><b>ความเห็นผู้อำนวยการโรงเรียน</b></div>
<div class="dots"></div>
<div class="dots"></div>
<table width="100%" class="sign">
  <tr><td width="40%"></td><td>
    (<?= trk_pdf_fill($d['signatories']['director'], 35) ?>)<br>
    ผู้อำนวยการ<?= e($school) ?>
  </td></tr>
</table>
<div style="text-align: center; margin-top: 6pt;">"เรียนดี มีคุณธรรม"</div>

<pagebreak />

<div class="form-code">แบบ ขร.๓ (ต่อ)</div>
<div style="text-align: center; font-size: 14pt; font-weight: bold;">หลักฐานการติดตามนักเรียนขาดเรียน ครั้งที่ <?= (int) $d['round_no'] ?></div>
<div style="text-align: center;"><?= e($s['name']) ?> เลขประจำตัว <?= e($s['code']) ?> ชั้น ม.<?= e($s['level'] . '/' . $s['room']) ?></div>
<div style="text-align: center;">วันที่ติดตาม <?= e(trk_thai_date($f['date'])) ?></div>

<?php if ($f['photos']): ?>
  <div style="text-align: center; margin-top: 8pt;">
    <?php foreach ($f['photos'] as $path): ?>
      <?php $abs = TRK_ROOT . '/storage/uploads/' . $path; ?>
      <?php if (is_file($abs)): ?><img class="photo" src="<?= e($abs) ?>"><?php endif; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<table width="100%" class="sign" style="margin-top: 10pt;">
  <tr>
    <td width="50%">ลงชื่อ ....................................<br>(<?= trk_pdf_fill($d['advisors'][0] ?? '', 35) ?>)<br>ครูที่ปรึกษา</td>
    <td width="50%">ลงชื่อ ....................................<br>(....................................................)<br>ผู้ปกครอง</td>
  </tr>
  <tr>
    <td colspan="2">ลงชื่อ ....................................<br>(<?= e($s['name']) ?>)<br>นักเรียน</td>
  </tr>
</table>
<p class="note">
  <b>หมายเหตุ:</b> การรายงานตามแบบฟอร์ม ขร.3 ให้ครูที่ปรึกษาดำเนินการรายงานครั้งที่ 1–3
  และใช้เป็นหลักฐานในกรณีติดตามนักเรียนไม่มีตัวตน หรือขาดเรียนนาน และใช้เป็นหลักฐานในการลาออก
</p>
