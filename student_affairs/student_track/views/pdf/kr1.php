<?php
/**
 * แบบ ขร.1 บันทึกข้อความนักเรียนขาดเรียนรายวิชา (ตามแบบฟอร์ม เอกสาร ขร/Word/ขร.1 .docx)
 * @var array $doc  เอกสาร (data = snapshot ตอนออก)
 * @var string $school, $amphur, $province
 */
$d = $doc['data'];
$date = trk_thai_date_parts($d['issued_date']);
$thresholds = $d['thresholds'];
$total = (int) $d['subject']['total_hours'];
$allowedAbsent = (int) floor($total * 20 / 100); // ต้องมีเวลาเรียนไม่น้อยกว่าร้อยละ 80
$classLabel = $d['classroom']['level'] . '/' . $d['classroom']['room'];
$cancelled = $doc['status'] === 'cancelled';
?>
<?php require __DIR__ . '/_style.php'; ?>

<?php if ($cancelled): ?>
  <div class="void">ยกเลิกแล้ว: <?= e($doc['cancel_reason']) ?></div>
<?php endif; ?>

<div class="form-code">แบบ ขร.๑</div>
<table width="100%">
  <tr>
    <td width="20%"><img src="<?= e(TRK_ROOT . '/assets/img/garuda.jpg') ?>" style="height: 1.5cm;"></td>
    <td width="60%" class="title">บันทึกข้อความ</td>
    <td width="20%"></td>
  </tr>
</table>

<div><span class="label">ส่วนราชการ</span> <?= e($school) ?> อำเภอ<?= e($amphur) ?> จังหวัด<?= e($province) ?></div>
<table width="100%">
  <tr>
    <td width="50%"><span class="label">ที่</span> <?= trk_pdf_fill($doc['doc_no'], 22) ?></td>
    <td width="50%"><span class="label">วันที่</span> <?= e($date['day']) ?> เดือน <?= e($date['month']) ?> พ.ศ. <?= e($date['year']) ?></td>
  </tr>
</table>
<div class="rule">
  <span class="label">เรื่อง</span> นักเรียนขาดเรียนรายวิชา
  <?php foreach ($thresholds as $n => $pct): ?>
    &nbsp;<?= trk_pdf_checkbox((int) $d['round_no'] === (int) $n) ?> ครั้งที่ <?= $n ?> ขาด <?= (int) $pct ?>%
  <?php endforeach; ?>
</div>

<p><span class="label">เรียน</span> ผู้อำนวยการ<?= e($school) ?></p>

<p class="indent">
  ด้วยข้าพเจ้า <?= e($d['teacher']['name']) ?> ตำแหน่ง <?= trk_pdf_fill($d['teacher']['position'], 10) ?>
  กลุ่มสาระการเรียนรู้<?= trk_pdf_fill($d['subject']['group'], 20) ?>
  ได้รับมอบหมายภาระงานสอน ภาคเรียนที่ <?= e($d['semester']['term']) ?>/<?= e($d['semester']['year']) ?>
  รายวิชา <?= e($d['subject']['code']) ?> <?= e($d['subject']['name']) ?> จำนวน <?= e(trk_format_credit($d['subject']['credit'])) ?> หน่วยกิต
  มีเวลาเรียนทั้งหมด <?= $total ?> ชั่วโมง/ภาคเรียน ซึ่งนักเรียนสามารถขาดและลาได้ไม่เกิน <?= $allowedAbsent ?> ชั่วโมง/ภาคเรียน
  เพื่อให้มีเวลาเรียนคิดเป็นร้อยละ 80 ของเวลาเรียนทั้งหมด ตามแนวปฏิบัติการวัดและประเมินผลการเรียนรู้
  ตามหลักสูตรแกนกลางการศึกษาขั้นพื้นฐาน พุทธศักราช 2551
</p>
<p class="indent">
  ในการนี้ ข้าพเจ้าได้ทำการสอนนักเรียนชั้นมัธยมศึกษาปีที่ <?= e($classLabel) ?>
  ตั้งแต่วันที่ <?= e(trk_thai_date($d['from_date'])) ?> ถึงวันที่ <?= e(trk_thai_date($d['to_date'])) ?>
  พบว่า มีนักเรียนขาดและลาตั้งแต่ <?= (int) $d['threshold_hours'] ?> ชั่วโมงขึ้นไป
  (ร้อยละ <?= (int) $thresholds[$d['round_no']] ?> ของเวลาเรียนทั้งหมด) ซึ่งมีความเสี่ยง "หมดสิทธิ์สอบ" ในรายวิชาดังกล่าว ดังนี้
</p>

<?php foreach ($d['students'] as $i => $s): ?>
  <div style="margin-left: 1.2cm;">
    <?= $i + 1 ?>. <?= e($s['name']) ?> &nbsp; เลขประจำตัว <?= e($s['code']) ?> &nbsp; ขาดเรียนสะสมมาแล้ว <?= (int) $s['absent_hours'] ?> ชั่วโมง
  </div>
<?php endforeach; ?>

<p class="indent">วันที่ที่นักเรียนขาดเรียน ระบุตามเอกสารดังแนบ</p>
<p class="indent">จึงเรียนเพื่อโปรดทราบและช่วยเหลือติดตามนักเรียน</p>

<table width="100%" class="sign">
  <tr>
    <td width="50%">
      ลงชื่อ ....................................<br>
      (<?= e($d['teacher']['name']) ?>)<br>
      ครูประจำวิชา
    </td>
    <td width="50%">
      <?php foreach ($d['advisors'] ?: [''] as $advisor): ?>
        ลงชื่อ ....................................<br>
        (<?= trk_pdf_fill($advisor, 35) ?>)<br>
        ครูที่ปรึกษาชั้น ม.<?= e($classLabel) ?><br><br>
      <?php endforeach; ?>
    </td>
  </tr>
</table>

<div style="margin-top: 8pt;"><b>ความคิดเห็นของรองผู้อำนวยการกลุ่มบริหารวิชาการ</b></div>
<div class="dots"></div>
<div class="dots"></div>
<table width="100%" class="sign">
  <tr><td width="40%"></td><td>
    ลงชื่อ ....................................<br>
    (<?= trk_pdf_fill($d['signatories']['deputy_academic'], 35) ?>)<br>
    รองผู้อำนวยการกลุ่มบริหารวิชาการ
  </td></tr>
</table>

<div style="margin-top: 8pt;"><b>ความคิดเห็นของผู้อำนวยการ<?= e($school) ?></b></div>
<div class="dots"></div>
<div class="dots"></div>
<table width="100%" class="sign">
  <tr><td width="40%"></td><td>
    ลงชื่อ ....................................<br>
    (<?= trk_pdf_fill($d['signatories']['director'], 35) ?>)<br>
    ผู้อำนวยการ<?= e($school) ?>
  </td></tr>
</table>
<div style="text-align: center; margin-top: 6pt;">"เรียนดี มีคุณธรรม"</div>

<div style="margin-top: 10pt; text-align: center;"><b>ตารางเปรียบเทียบหน่วยกิตและเวลาขาดเรียน</b></div>
<table class="grid" width="100%">
  <tr>
    <th>หน่วยกิต</th>
    <th>จำนวนชั่วโมงทั้งหมด</th>
    <?php foreach ($thresholds as $n => $pct): ?>
      <th>ครั้งที่ <?= $n ?><br>(ขาด <?= (int) $pct ?>%)</th>
    <?php endforeach; ?>
  </tr>
  <?php foreach ([0.5, 1.0, 1.5, 2.0] as $credit): ?>
    <?php $hours = (int) round($credit * $d['hours_per_credit']); ?>
    <tr>
      <td><?= e(trk_format_credit($credit)) ?></td>
      <td><?= $hours ?> ชั่วโมง</td>
      <?php foreach ($thresholds as $pct): ?>
        <td><?= trk_case1_threshold_hours($hours, (int) $pct) ?> ชั่วโมง</td>
      <?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
</table>
<p class="note">
  <b>หมายเหตุ:</b> หากขาดเกิน 20% ซึ่งจะตรงกับการรายงานครั้งที่ 2 นักเรียนจะหมดสิทธิ์สอบในรายวิชาดังกล่าว
  ในกรณีที่ขาดเกิน <?= (int) $thresholds[3] ?>% ซึ่งจะตรงกับการรายงานครั้งที่ 3 ให้อยู่ในดุลยพินิจของท่านผู้อำนวยการ
</p>

<pagebreak />

<div class="form-code">เอกสารแนบ แบบ ขร.๑</div>
<div style="text-align: center; font-size: 14pt; font-weight: bold;">วันที่นักเรียนขาดเรียน</div>
<div style="text-align: center;">
  รายวิชา <?= e($d['subject']['code']) ?> <?= e($d['subject']['name']) ?> ชั้นมัธยมศึกษาปีที่ <?= e($classLabel) ?>
  ภาคเรียนที่ <?= e($d['semester']['term']) ?>/<?= e($d['semester']['year']) ?>
</div>
<div style="text-align: center;">ข้อมูล ณ วันที่ <?= e(trk_thai_date($d['issued_date'])) ?></div>

<?php foreach ($d['students'] as $i => $s): ?>
  <div style="margin-top: 10pt;"><b><?= $i + 1 ?>. <?= e($s['name']) ?></b> เลขประจำตัว <?= e($s['code']) ?> ขาดเรียนสะสม <?= (int) $s['absent_hours'] ?> ชั่วโมง</div>
  <table class="grid" width="100%" style="margin-top: 3pt;">
    <tr><th width="10%">ที่</th><th width="45%">วันที่</th><th width="20%">คาบที่</th><th width="25%">สถานะ</th></tr>
    <?php foreach ($s['details'] as $j => $row): ?>
      <tr>
        <td><?= $j + 1 ?></td>
        <td style="text-align: left;"><?= e(trk_thai_date($row['date'])) ?></td>
        <td><?= (int) $row['period'] ?></td>
        <td><?= e(TRK_SUBJECT_STATUS_LABELS[(int) $row['status']] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endforeach; ?>
