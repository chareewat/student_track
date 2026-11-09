<?php
require __DIR__ . '/../bootstrap.php';

$user = trk_user();
$doc = trk_document_get((int) ($_GET['id'] ?? 0));
if (!$doc || !trk_document_can_view($doc, $user['id'])) {
    http_response_code(404);
    trk_render_message('ไม่พบเอกสาร', 'เอกสารนี้ไม่มีอยู่ หรือคุณไม่มีสิทธิ์ดู');
    exit;
}
trk_mark_document_read($user['id'], (int) $doc['id']);

$d = $doc['data'];
$cancelBlock = trk_document_cancel_block_reason($doc, $user['id']);
$canCancel = $cancelBlock === null;
$canceller = $doc['cancelled_by'] ? trk_member_profile((int) $doc['cancelled_by'])['name'] : '';
$isKr1 = $doc['doc_type'] === 'KR1';

// รอบติดตามที่ผูกกับเอกสาร [std_student_id => round_id]
$roundIds = [];
foreach (trk_db_all('SELECT r.id, r.std_student_id FROM trk_document_rounds dr JOIN trk_rounds r ON r.id = dr.round_id WHERE dr.document_id = ?', [(int) $doc['id']]) as $r) {
    $roundIds[(int) $r['std_student_id']] = (int) $r['id'];
}

if ($isKr1) {
    $heading = $d['subject']['code'] . ' ' . $d['subject']['name'] . ' · ม.' . $d['classroom']['level'] . '/' . $d['classroom']['room'];
} else {
    $heading = $d['student']['name'] . ' · ม.' . $d['student']['level'] . '/' . $d['student']['room'];
}

trk_render_header(trk_document_title($doc));
?>
<div class="flex flex-wrap items-start justify-between gap-3">
  <div>
    <div class="text-sm text-slate-500"><?= e(trk_document_title($doc)) ?><?= $doc['doc_no'] ? ' · ที่ ' . e($doc['doc_no']) : '' ?></div>
    <h1 class="mt-1 text-xl font-semibold"><?= e($heading) ?></h1>
    <p class="mt-1 text-sm text-slate-600">
      ออกโดย <?= e($doc['issued_by_name']) ?> เมื่อ <?= e(trk_thai_date($doc['issued_at'])) ?> <?= e(substr($doc['issued_at'], 11, 5)) ?> น.
    </p>
  </div>
  <a href="<?= e(trk_url('documents/print.php?id=' . (int) $doc['id'])) ?>" target="_blank"
     class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">พิมพ์ PDF</a>
</div>

<?php if ($doc['status'] === 'cancelled'): ?>
  <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
    ยกเลิกแล้วโดย <?= e($canceller) ?> เมื่อ <?= e(trk_thai_date($doc['cancelled_at'])) ?> · เหตุผล: <?= e($doc['cancel_reason']) ?>
  </div>
<?php endif; ?>

<?php if ($isKr1): ?>
<div class="mt-6 grid gap-4 sm:grid-cols-3">
  <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-sm">
    <div class="text-xs text-slate-500">เวลาเรียนทั้งหมด</div>
    <div class="mt-1 text-lg font-semibold"><?= (int) $d['subject']['total_hours'] ?> ชม.</div>
  </div>
  <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-sm">
    <div class="text-xs text-slate-500">เกณฑ์ครั้งที่ <?= (int) $d['round_no'] ?></div>
    <div class="mt-1 text-lg font-semibold"><?= (int) $d['thresholds'][$d['round_no']] ?>% (<?= (int) $d['threshold_hours'] ?> ชม.)</div>
  </div>
  <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-sm">
    <div class="text-xs text-slate-500">ครูที่ปรึกษาที่ได้รับแจ้ง</div>
    <div class="mt-1"><?= e(implode(', ', $d['advisors']) ?: '-') ?></div>
  </div>
</div>

<div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-left text-xs text-slate-500">
      <tr>
        <th class="px-4 py-2 font-medium">นักเรียน</th>
        <th class="px-4 py-2 text-right font-medium">ขาดสะสม</th>
        <th class="px-4 py-2 font-medium">วันที่ขาด / ลา / หนี</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($d['students'] as $s): ?>
        <tr class="align-top">
          <td class="px-4 py-2">
            <div><?= e($s['name']) ?></div>
            <div class="text-xs text-slate-400"><?= e($s['code']) ?></div>
            <?php if (isset($roundIds[(int) $s['id']])): ?>
              <a href="<?= e(trk_url('advisor/round.php?id=' . $roundIds[(int) $s['id']])) ?>" class="text-xs text-blue-700 hover:underline">รอบติดตาม →</a>
            <?php endif; ?>
          </td>
          <td class="px-4 py-2 text-right"><?= (int) $s['absent_hours'] ?> ชม.</td>
          <td class="px-4 py-2 text-xs text-slate-600">
            <?= e(implode(', ', array_map(function ($r) {
                return trk_thai_date($r['date'], true) . ' คาบ ' . $r['period'] . ' (' . (TRK_SUBJECT_STATUS_LABELS[(int) $r['status']] ?? '') . ')';
            }, $s['details']))) ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-sm">
  <dl class="grid gap-x-6 gap-y-2 sm:grid-cols-[10rem_1fr]">
    <dt class="text-slate-500">นักเรียน</dt><dd><?= e($d['student']['name']) ?> (<?= e($d['student']['code']) ?>)</dd>
    <?php if (!empty($d['guardian']) || array_key_exists('guardian', $d)): ?>
      <dt class="text-slate-500">ผู้ปกครอง</dt><dd><?= e($d['guardian'] ?: '-') ?></dd>
    <?php endif; ?>
    <?php if (!empty($d['subjects'])): ?>
      <dt class="text-slate-500">รายวิชา</dt>
      <dd><?= e(implode(', ', array_map(function ($s) { return $s['code'] . ' ' . $s['name'] . ' (' . $s['absent_hours'] . ' คาบ)'; }, $d['subjects']))) ?></dd>
    <?php endif; ?>
    <?php if (!empty($d['absence'])): ?>
      <dt class="text-slate-500">ขาดติดต่อกัน</dt>
      <dd><?= e(trk_thai_date($d['absence']['from'])) ?> – <?= e(trk_thai_date($d['absence']['to'])) ?> (<?= (int) $d['absence']['days'] ?> วัน)</dd>
      <dt class="text-slate-500">ให้ติดต่อภายใน</dt><dd><?= e(trk_thai_date($d['deadline'])) ?></dd>
    <?php endif; ?>
    <?php if (!empty($d['month_absence'])): ?>
      <dt class="text-slate-500">ขาดเรียนในเดือนนี้</dt>
      <dd><?= (int) $d['month_absence']['days'] ?> วัน (ติดต่อกันสูงสุด <?= (int) $d['month_absence']['max_consecutive'] ?> วัน)</dd>
    <?php endif; ?>
    <?php if (!empty($d['followup'])): ?>
      <dt class="text-slate-500">วันที่ติดตาม</dt><dd><?= e(trk_thai_date($d['followup']['date'])) ?> (<?= e($d['followup']['method']) ?>)</dd>
      <dt class="text-slate-500">ผลที่พบ</dt><dd class="whitespace-pre-line"><?= e($d['followup']['findings']) ?></dd>
    <?php endif; ?>
  </dl>
  <?php foreach ($roundIds as $rid): ?>
    <a href="<?= e(trk_url('advisor/round.php?id=' . $rid)) ?>" class="mt-3 inline-block text-blue-700 hover:underline">ดูรอบติดตาม →</a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($doc['status'] === 'active'): ?>
  <details class="mt-8 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <summary class="cursor-pointer text-sm font-medium text-rose-700">ยกเลิกเอกสารนี้</summary>
    <?php if ($canCancel): ?>
      <form method="post" action="<?= e(trk_url('documents/cancel.php')) ?>" class="mt-3 space-y-3">
        <?= trk_csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $doc['id'] ?>">
        <p class="text-sm text-slate-600">
          <?php if ($isKr1): ?>
            นักเรียนในเอกสารนี้จะกลับไปอยู่ในรายชื่อที่ต้องออก ขร.1 อีกครั้ง และครูที่ปรึกษาจะได้รับแจ้ง
          <?php elseif ($doc['doc_type'] === 'KR2'): ?>
            รอบติดตามจะกลับไปที่ขั้น "รอออก ขร.2" และ บค.14 ที่ออกคู่กันจะถูกยกเลิกด้วย
          <?php elseif ($doc['doc_type'] === 'KR5'): ?>
            รอบติดตามนี้จะถูกยกเลิก และนักเรียนจะกลับไปอยู่ในรายชื่อที่ต้องออก ขร.5 อีกครั้ง
          <?php elseif ($doc['doc_type'] === 'KR3'): ?>
            ผลการติดตามนี้จะไม่ถูกนับ และรอบติดตามจะกลับไปที่ขั้น "รอบันทึกผลติดตาม"
          <?php else: ?>
            เอกสารนี้จะถูกทำเครื่องหมายว่ายกเลิก
          <?php endif; ?>
        </p>
        <input name="reason" required maxlength="255" placeholder="เหตุผลที่ยกเลิก (จำเป็น)"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <button class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">ยืนยันยกเลิก</button>
      </form>
    <?php else: ?>
      <p class="mt-3 text-sm text-slate-600"><?= e($cancelBlock) ?></p>
    <?php endif; ?>
  </details>
<?php endif; ?>
<?php
trk_render_footer();
