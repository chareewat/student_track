<?php
/**
 * แบบ ขร.6 บันทึกข้อความขอให้จำหน่ายนักเรียนที่ไม่มาโรงเรียนติดต่อกันเป็นเวลานาน
 * @var array $doc
 * @var string $school, $amphur, $province
 */
$d = $doc['data'];
$s = $d['student'];
$date = trk_thai_date_parts($d['issued_date']);
$occasions = array_values($d['occasions']);
?>
<?php require __DIR__ . '/_style.php'; ?>

<?php if ($doc['status'] === 'cancelled'): ?>
  <div class="void">ยกเลิกแล้ว: <?= e($doc['cancel_reason']) ?></div>
<?php endif; ?>

<div class="form-code">แบบ ขร.๖</div>
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
<div class="rule"><span class="label">เรื่อง</span> ขอให้จำหน่ายนักเรียนที่ไม่มาโรงเรียนติดต่อกันเป็นเวลานาน</div>

<p><span class="label">เรียน</span> ผู้อำนวยการ<?= e($school) ?></p>

<p class="indent">
  ด้วยข้าพเจ้า <?= e($d['advisor']['name']) ?> เป็นครูที่ปรึกษา ม.<?= e($s['level'] . '/' . $s['room']) ?>
  ได้ติดตามการมาเรียนของ <?= e($s['name']) ?> เลขประจำตัว <?= e($s['code']) ?> ซึ่งไม่มาโรงเรียน ดังนี้
</p>
<div style="margin-left: 1.2cm;">
  <?php foreach ([0, 1, 2] as $i): ?>
    <?php $o = $occasions[$i] ?? null; ?>
    <div>
      <?= trk_pdf_checkbox($o !== null) ?> ครั้งที่ <?= $i + 1 ?>
      <?php if ($o === null): ?>
        ตั้งแต่วันที่ ................ ถึงวันที่ ................ เป็นเวลารวม ........ วัน
      <?php elseif ((int) $o['case_type'] === 2): ?>
        ตั้งแต่วันที่ <?= e(trk_thai_date($o['from'])) ?> ถึงวันที่ <?= e(trk_thai_date($o['to'])) ?> เป็นเวลารวม <?= (int) $o['days'] ?> วัน
      <?php else: ?>
        ขาดเรียนรายวิชา <?= e(trk_kr6_case1_text($o)) ?>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<p class="indent">โดยข้าพเจ้าได้ส่งหนังสือ/ติดตามจำนวน <?= count($occasions) ?> ครั้ง ดังนี้</p>
<div style="margin-left: 2.5cm;">
  <?php foreach ([0, 1, 2] as $i): ?>
    <?php $o = $occasions[$i] ?? null; ?>
    <div>ครั้งที่ <?= $i + 1 ?> เมื่อวันที่ <?= $o ? e(trk_thai_date($o['followup_date'])) . ' (' . e($o['method']) . ')' : '..............................' ?></div>
  <?php endforeach; ?>
</div>
<p class="indent">
  รวมการส่งหนังสือ/ติดตาม <?= count($occasions) ?> ครั้งแล้ว ผู้ปกครองยังไม่มาติดต่อและไม่ได้นำนักเรียนมาเข้าเรียน
  (หลังการติดตามครั้งล่าสุดเมื่อวันที่ <?= e(trk_thai_date($d['last_followup'])) ?> นักเรียนไม่มาโรงเรียน <?= (int) $d['after']['checked'] ?> วันทำการ)
  จึงขอให้โรงเรียนพิจารณาดำเนินการจำหน่าย <?= e($s['name']) ?> เลขประจำตัว <?= e($s['code']) ?> ออกจากโรงเรียน
</p>
<p class="indent">จึงเรียนมาเพื่อโปรดพิจารณา</p>

<table width="100%" class="sign">
  <tr><td width="45%"></td><td>
    ลงชื่อ ....................................<br>
    (<?= e($d['advisor']['name']) ?>)<br>
    ครูที่ปรึกษา
  </td></tr>
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
