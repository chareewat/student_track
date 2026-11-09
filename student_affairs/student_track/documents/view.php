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

trk_render_header(trk_document_title($doc));
?>
<div class="flex flex-wrap items-start justify-between gap-3">
  <div>
    <div class="text-sm text-slate-500"><?= e(trk_document_title($doc)) ?><?= $doc['doc_no'] ? ' · ที่ ' . e($doc['doc_no']) : '' ?></div>
    <h1 class="mt-1 text-xl font-semibold">
      <?= e($d['subject']['code'] . ' ' . $d['subject']['name']) ?> · ม.<?= e($d['classroom']['level'] . '/' . $d['classroom']['room']) ?>
    </h1>
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

<?php if ($doc['status'] === 'active'): ?>
  <details class="mt-8 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <summary class="cursor-pointer text-sm font-medium text-rose-700">ยกเลิกเอกสารนี้</summary>
    <?php if ($canCancel): ?>
      <form method="post" action="<?= e(trk_url('documents/cancel.php')) ?>" class="mt-3 space-y-3">
        <?= trk_csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $doc['id'] ?>">
        <p class="text-sm text-slate-600">นักเรียนในเอกสารนี้จะกลับไปอยู่ในรายชื่อที่ต้องออกเอกสารอีกครั้ง และครูที่ปรึกษาจะได้รับแจ้ง</p>
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
