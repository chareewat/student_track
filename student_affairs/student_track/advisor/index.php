<?php
/**
 * ครูที่ปรึกษา: รอบติดตามของห้องตนเอง แยกตามขั้นที่ต้องทำ
 * แสดงเป็นกลุ่ม: รอออก ขร.2 = รายคน (รวมทุกวิชา), รอติดตาม/ปิดแล้ว = รายชุด ขร.2
 */
require __DIR__ . '/../bootstrap.php';

$user = trk_user();
$semesterId = trk_semester_id();
$rooms = trk_advisor_classrooms($user['id'], $semesterId);
if (!$rooms) {
    trk_render_message('ไม่ได้เป็นครูที่ปรึกษา', 'คุณไม่ได้เป็นครูที่ปรึกษาในภาคเรียนนี้');
    exit;
}

$rounds = trk_rounds_for_classrooms(array_column($rooms, 'std_classroom_id'), $semesterId);
$groups = ['need_kr2' => [], 'need_followup' => [], 'closed' => []];
foreach ($rounds as $r) {
    $groups[$r['stage']][] = $r;
}
foreach ($groups as $stage => $list) {
    $groups[$stage] = array_values(trk_rounds_group($list));
}
$groupInfo = [
    'need_kr2' => ['รอออก ขร.2 แจ้งผู้ปกครอง', 'ครูประจำวิชาส่ง ขร.1 มาแล้ว ให้ขอเลขหนังสือจากงานสารบรรณแล้วออก ขร.2 (ม.1–3 ออก บค.14 คู่กัน)', 'bg-amber-100 text-amber-800'],
    'need_followup' => ['รอบันทึกผลติดตาม (ขร.3)', 'ออก ขร.2 หรือ ขร.5 แล้ว ลงพื้นที่ติดตามนักเรียนพร้อมเจ้าหน้าที่ฝ่ายกิจการนักเรียน แล้วบันทึกผล', 'bg-blue-100 text-blue-800'],
    'closed' => ['ปิดรอบแล้ว', 'บันทึกผลติดตามและออก ขร.3 แล้ว พิมพ์ ขร.3 เสนอผู้อำนวยการ', 'bg-emerald-100 text-emerald-800'],
];

trk_render_header('งานครูที่ปรึกษา', 'advisor');
?>
<h1 class="text-2xl font-semibold tracking-tight flex items-center gap-3"><span class="title-icon"><?= trk_icon('clipboard', 'h-5 w-5') ?></span>งานครูที่ปรึกษา</h1>
<p class="mt-1 text-sm text-ink/60">
  ห้อง <?= e(implode(', ', array_map('trk_classroom_label', $rooms))) ?> · ภาคเรียน <?= e(trk_semester_label()) ?>
</p>

<?php $stageIcons = ['need_kr2' => 'file-plus', 'need_followup' => 'map-pin', 'closed' => 'folder-check']; ?>
<?php foreach ($groupInfo as $stage => [$title, $hint, $badge]): ?>
  <section class="mt-6">
    <div class="flex items-baseline gap-2">
      <h2 class="text-base font-semibold flex items-center gap-2"><?= trk_icon($stageIcons[$stage], 'h-[18px] w-[18px] text-navy/70') ?><?= e($title) ?></h2>
      <span class="rounded-full px-2 py-0.5 text-xs font-medium <?= $badge ?>"><?= count($groups[$stage]) ?></span>
    </div>
    <p class="mt-0.5 text-xs text-ink/55"><?= e($hint) ?></p>
    <?php if ($groups[$stage]): ?>
      <div class="mt-3 table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th class="px-4 py-2 font-medium">นักเรียน</th>
              <th class="hidden px-4 py-2 font-medium sm:table-cell">ห้อง</th>
              <th class="px-4 py-2 font-medium">เหตุ</th>
              <th class="px-4 py-2 font-medium">ครั้งที่</th>
              <th class="hidden px-4 py-2 font-medium md:table-cell">เปิดรอบเมื่อ</th>
              <th class="px-4 py-2"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($groups[$stage] as $batch): ?>
              <?php $r = $batch[0]; ?>
              <tr>
                <td class="px-4 py-2">
                  <div><?= e($r['full_name']) ?></div>
                  <div class="text-xs text-ink/40"><?= e($r['std_student_code']) ?> · เลขที่ <?= (int) $r['std_student_number'] ?></div>
                </td>
                <td class="hidden px-4 py-2 sm:table-cell"><?= e($r['classroom_label']) ?></td>
                <td class="px-4 py-2">
                  <?php if ((int) $r['case_type'] === 1): ?>
                    <?php foreach ($batch as $i => $b): ?>
                      <div><?= $i === 0 ? 'ขาดรายวิชา ' : '' ?><span class="font-mono text-xs"><?= e($b['vic_subject_code']) ?></span> <?= e($b['vic_subject_name']) ?>
                        <span class="text-xs text-ink/55">(<?= (int) $b['absent_hours'] ?>/<?= (int) $b['total_hours'] ?> ชม.<?= count($batch) > 1 ? ' · ขร.1 ครั้งที่ ' . (int) $b['round_no'] : '' ?>)</span></div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    ขาดติดต่อกัน <span class="text-xs text-ink/55"><?= e(trk_thai_date($r['absent_from'], true)) ?> – <?= e(trk_thai_date($r['absent_to'], true)) ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-2"><?= e(implode(', ', array_unique(array_map(function ($b) {
                    return (int) $b['round_no'];
                }, $batch)))) ?></td>
                <td class="hidden px-4 py-2 text-ink/60 md:table-cell"><?= e(trk_thai_date($r['opened_at'], true)) ?></td>
                <td class="px-4 py-2 text-right"><div class="flex flex-col items-end gap-1.5 sm:flex-row sm:justify-end">
                  <?php if ($stage === 'need_kr2'): ?>
                    <a href="<?= e(trk_url('advisor/kr2.php?round=' . (int) $r['id'])) ?>" class="btn btn-green btn-sm"><?= trk_icon('file-plus') ?>ออก ขร.2</a>
                  <?php elseif ($stage === 'need_followup'): ?>
                    <a href="<?= e(trk_url('advisor/followup.php?round=' . (int) $r['id'])) ?>" class="btn btn-green btn-sm"><?= trk_icon('map-pin') ?>บันทึกผลติดตาม</a>
                  <?php endif; ?>
                  <a href="<?= e(trk_url('advisor/round.php?id=' . (int) $r['id'])) ?>" class="btn btn-soft-blue btn-sm"><?= trk_icon('eye') ?>รายละเอียด</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="mt-2 text-sm text-ink/40 flex items-center gap-2"><?= trk_icon('inbox', 'h-4 w-4 text-ink/35') ?>ไม่มีรายการ</p>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
<?php
trk_render_footer();
