<?php
/**
 * Dashboard สรุปทั้งโรงเรียน (admin, ผู้บริหาร, เจ้าหน้าที่วิชาการ, เจ้าหน้าที่กิจการนักเรียน)
 */
require __DIR__ . '/../bootstrap.php';
require_once TRK_ROOT . '/lib/dashboard.php';
trk_require_role(...TRK_ROLES_SEE_ALL);

$semester = trk_current_semester();
$semesterId = trk_semester_id();
$rooms = trk_dashboard_classrooms($semesterId);
$counts = trk_dashboard_counts($semesterId, $rooms);
$total = $counts['total'];
$overdue = trk_dashboard_overdue($semesterId);
$week8 = trk_semester_week8_end($semester);
$pastWeek8 = date('Y-m-d') > $week8;

$levels = array_values(array_unique(array_column($rooms, 'std_classroom_level')));
$levelFilter = (string) ($_GET['level'] ?? '');
if ($levelFilter !== '' && !in_array($levelFilter, $levels, true)) {
    $levelFilter = '';
}

$tiles = [
    ['รอออก ขร.1', $total['case1_waiting'], 'นักเรียน × รายวิชาที่ถึงเกณฑ์แต่ครูประจำวิชายังไม่ออก'],
    ['รอออก ขร.5', $total['case2_waiting'], 'นักเรียนขาดติดต่อกันที่ครูที่ปรึกษายังไม่ออก'],
    ['รอออก ขร.2', $total['need_kr2'], 'ครูที่ปรึกษาได้รับ ขร.1 แล้ว ยังไม่แจ้งผู้ปกครอง'],
    ['รอบันทึกผลติดตาม', $total['need_followup'], 'รอลงพื้นที่และออก ขร.3'],
    ['ปิดรอบแล้ว', $total['closed'], 'บันทึกผลและออก ขร.3 แล้วในภาคเรียนนี้'],
    ['ขร.6 เสนอจำหน่าย', $total['kr6'], 'ส่งถึงเจ้าหน้าที่วิชาการแล้ว'],
];
$columns = [
    'case1_waiting' => 'รอ ขร.1',
    'case2_waiting' => 'รอ ขร.5',
    'need_kr2' => 'รอ ขร.2',
    'need_followup' => 'รอติดตาม',
    'closed' => 'ปิดรอบ',
    'kr6' => 'ขร.6',
];

trk_render_header('ภาพรวม', 'dashboard');
?>
<div class="flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="text-xl font-semibold">ภาพรวมการติดตามนักเรียน</h1>
    <p class="mt-1 text-sm text-slate-600">ภาคเรียน <?= e(trk_semester_label()) ?> · ข้อมูล ณ <?= e(trk_thai_date(date('Y-m-d'))) ?> <?= date('H:i') ?> น.</p>
  </div>
  <div class="flex flex-wrap gap-2">
    <form action="<?= e(trk_url('dashboard/student.php')) ?>" class="flex gap-2">
      <input type="search" name="q" placeholder="ค้นหานักเรียน (ชื่อ/เลขประจำตัว)" class="w-56 rounded-lg border border-slate-300 px-3 py-2 text-sm">
      <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">ค้นหา</button>
    </form>
    <a href="<?= e(trk_url('dashboard/export.php')) ?>" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">ดาวน์โหลด CSV</a>
  </div>
</div>

<section class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
  <?php foreach ($tiles as [$label, $value, $hint]): ?>
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" title="<?= e($hint) ?>">
      <div class="text-xs text-slate-500"><?= e($label) ?></div>
      <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900"><?= (int) $value ?></div>
      <div class="mt-1 text-xs leading-snug text-slate-400"><?= e($hint) ?></div>
    </div>
  <?php endforeach; ?>
</section>

<section class="mt-8">
  <div class="flex flex-wrap items-baseline gap-2">
    <h2 class="text-base font-semibold">งานค้างเกินกำหนด</h2>
    <span class="text-xs text-slate-500">
      รอ ขร.2 เกิน <?= (int) trk_setting('overdue_kr2_days') ?> วัน · รอติดตามเกิน <?= (int) trk_setting('overdue_followup_days') ?> วัน ·
      ขร.2 ต้องแจ้งผู้ปกครองภายในสัปดาห์ที่ 8 (<?= e(trk_thai_date($week8, true)) ?>)
    </span>
  </div>
  <?php if ($pastWeek8 && $total['need_kr2'] > 0): ?>
    <div class="mt-3 flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
      <span aria-hidden="true">⚠</span>
      <span><b>เลยสัปดาห์ที่ 8 แล้ว:</b> ยังมี <?= (int) $total['need_kr2'] ?> รอบที่ยังไม่ได้ออก ขร.2</span>
    </div>
  <?php endif; ?>
  <?php if (!$overdue): ?>
    <p class="mt-3 text-sm text-slate-500">ไม่มีงานค้างเกินกำหนด</p>
  <?php else: ?>
    <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs text-slate-500">
          <tr>
            <th class="px-4 py-2 font-medium">ค้างนาน</th>
            <th class="px-4 py-2 font-medium">ขั้น</th>
            <th class="px-4 py-2 font-medium">นักเรียน</th>
            <th class="px-4 py-2 font-medium">ห้อง</th>
            <th class="px-4 py-2 font-medium">เหตุ</th>
            <th class="px-4 py-2"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($overdue as $r): ?>
            <tr>
              <td class="px-4 py-2 whitespace-nowrap">
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900">
                  <span aria-hidden="true">⏱</span> <?= (int) $r['age_days'] ?> วัน
                </span>
              </td>
              <td class="px-4 py-2"><?= e(TRK_STAGE_LABELS[$r['stage']]) ?></td>
              <td class="px-4 py-2"><?= e($r['full_name']) ?> <span class="text-xs text-slate-400"><?= e($r['std_student_code']) ?></span></td>
              <td class="px-4 py-2"><?= e($r['classroom_label']) ?></td>
              <td class="px-4 py-2 text-slate-600">
                <?= (int) $r['case_type'] === 1
                    ? 'ขาดรายวิชา ' . e($r['vic_subject_code']) . ' ครั้งที่ ' . (int) $r['round_no']
                    : 'ขาดติดต่อกัน ครั้งที่ ' . (int) $r['round_no'] ?>
              </td>
              <td class="px-4 py-2 text-right"><a href="<?= e(trk_url('advisor/round.php?id=' . (int) $r['id'])) ?>" class="text-blue-700 hover:underline">ดู</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="mt-8">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <h2 class="text-base font-semibold">รายห้อง</h2>
    <form class="flex items-center gap-2 text-sm">
      <label for="level" class="text-slate-500">ระดับชั้น</label>
      <select id="level" name="level" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
        <option value="">ทั้งหมด</option>
        <?php foreach ($levels as $lv): ?>
          <option value="<?= e($lv) ?>" <?= $levelFilter === $lv ? 'selected' : '' ?>>ม.<?= e($lv) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
  <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-left text-xs text-slate-500">
        <tr>
          <th class="px-4 py-2 font-medium">ห้อง</th>
          <th class="px-4 py-2 font-medium">ครูที่ปรึกษา</th>
          <?php foreach ($columns as $label): ?>
            <th class="px-3 py-2 text-right font-medium"><?= e($label) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($rooms as $room): ?>
          <?php
            if ($levelFilter !== '' && $room['std_classroom_level'] !== $levelFilter) {
                continue;
            }
            $c = $counts[(int) $room['std_classroom_id']];
          ?>
          <tr>
            <td class="px-4 py-2 font-medium whitespace-nowrap"><?= e(trk_classroom_label($room)) ?></td>
            <td class="px-4 py-2 text-slate-600"><?= e($room['advisors'] ?: '-') ?></td>
            <?php foreach (array_keys($columns) as $key): ?>
              <td class="px-3 py-2 text-right tabular-nums <?= $c[$key] ? 'font-medium text-slate-900' : 'text-slate-300' ?>"><?= (int) $c[$key] ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="border-t border-slate-200 bg-slate-50 text-sm">
        <tr>
          <td class="px-4 py-2 font-semibold" colspan="2">รวมทั้งโรงเรียน</td>
          <?php foreach (array_keys($columns) as $key): ?>
            <td class="px-3 py-2 text-right font-semibold tabular-nums"><?= (int) $total[$key] ?></td>
          <?php endforeach; ?>
        </tr>
      </tfoot>
    </table>
  </div>
</section>
<?php
trk_render_footer();
