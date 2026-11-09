<?php
/**
 * ครูที่ปรึกษา: รอบติดตามของห้องตนเอง แยกตามขั้นที่ต้องทำ
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
$groupInfo = [
    'need_kr2' => ['รอออก ขร.2 แจ้งผู้ปกครอง', 'ครูประจำวิชาส่ง ขร.1 มาแล้ว ให้ขอเลขหนังสือจากงานสารบรรณแล้วออก ขร.2 (ม.1–3 ออก บค.14 คู่กัน)', 'bg-amber-100 text-amber-800'],
    'need_followup' => ['รอบันทึกผลติดตาม (ขร.3)', 'ออก ขร.2 หรือ ขร.5 แล้ว ลงพื้นที่ติดตามนักเรียนพร้อมเจ้าหน้าที่ฝ่ายกิจการนักเรียน แล้วบันทึกผล', 'bg-blue-100 text-blue-800'],
    'closed' => ['ปิดรอบแล้ว', 'บันทึกผลติดตามและออก ขร.3 แล้ว พิมพ์ ขร.3 เสนอผู้อำนวยการ', 'bg-emerald-100 text-emerald-800'],
];

trk_render_header('งานครูที่ปรึกษา', 'advisor');
?>
<h1 class="text-xl font-semibold">งานครูที่ปรึกษา</h1>
<p class="mt-1 text-sm text-slate-600">
  ห้อง <?= e(implode(', ', array_map('trk_classroom_label', $rooms))) ?> · ภาคเรียน <?= e(trk_semester_label()) ?>
</p>

<?php foreach ($groupInfo as $stage => [$title, $hint, $badge]): ?>
  <section class="mt-6">
    <div class="flex items-baseline gap-2">
      <h2 class="text-base font-semibold"><?= e($title) ?></h2>
      <span class="rounded-full px-2 py-0.5 text-xs font-medium <?= $badge ?>"><?= count($groups[$stage]) ?></span>
    </div>
    <p class="mt-0.5 text-xs text-slate-500"><?= e($hint) ?></p>
    <?php if ($groups[$stage]): ?>
      <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-left text-xs text-slate-500">
            <tr>
              <th class="px-4 py-2 font-medium">นักเรียน</th>
              <th class="px-4 py-2 font-medium">ห้อง</th>
              <th class="px-4 py-2 font-medium">เหตุ</th>
              <th class="px-4 py-2 font-medium">ครั้งที่</th>
              <th class="px-4 py-2 font-medium">เปิดรอบเมื่อ</th>
              <th class="px-4 py-2"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($groups[$stage] as $r): ?>
              <tr>
                <td class="px-4 py-2">
                  <div><?= e($r['full_name']) ?></div>
                  <div class="text-xs text-slate-400"><?= e($r['std_student_code']) ?> · เลขที่ <?= (int) $r['std_student_number'] ?></div>
                </td>
                <td class="px-4 py-2"><?= e($r['classroom_label']) ?></td>
                <td class="px-4 py-2">
                  <?php if ((int) $r['case_type'] === 1): ?>
                    ขาดรายวิชา <span class="font-mono text-xs"><?= e($r['vic_subject_code']) ?></span> <?= e($r['vic_subject_name']) ?>
                    <span class="text-xs text-slate-500">(<?= (int) $r['absent_hours'] ?>/<?= (int) $r['total_hours'] ?> ชม.)</span>
                  <?php else: ?>
                    ขาดติดต่อกัน <span class="text-xs text-slate-500"><?= e(trk_thai_date($r['absent_from'], true)) ?> – <?= e(trk_thai_date($r['absent_to'], true)) ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-2"><?= (int) $r['round_no'] ?></td>
                <td class="px-4 py-2 text-slate-600"><?= e(trk_thai_date($r['opened_at'], true)) ?></td>
                <td class="px-4 py-2 text-right">
                  <?php if ($stage === 'need_kr2'): ?>
                    <a href="<?= e(trk_url('advisor/kr2.php?round=' . (int) $r['id'])) ?>" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">ออก ขร.2</a>
                  <?php elseif ($stage === 'need_followup'): ?>
                    <a href="<?= e(trk_url('advisor/followup.php?round=' . (int) $r['id'])) ?>" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">บันทึกผลติดตาม</a>
                  <?php endif; ?>
                  <a href="<?= e(trk_url('advisor/round.php?id=' . (int) $r['id'])) ?>" class="ml-2 text-xs text-blue-700 hover:underline">รายละเอียด</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="mt-2 text-sm text-slate-400">ไม่มีรายการ</p>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
<?php
trk_render_footer();
