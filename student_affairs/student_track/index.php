<?php
require __DIR__ . '/bootstrap.php';

$user = trk_user();
$semesterId = trk_semester_id();
$thresholds = trk_kr1_thresholds();

$advisorRooms = trk_advisor_classrooms($user['id'], $semesterId);
foreach ($advisorRooms as &$room) {
    $room['advisors'] = trk_classroom_advisors((int) $room['std_classroom_id'], $semesterId);
    $room['student_count'] = (int) trk_db_value(
        'SELECT COUNT(*) FROM std_student WHERE std_classroom_id = ? AND std_status_id = 1',
        [(int) $room['std_classroom_id']]
    );
}
unset($room);

$teaching = trk_teaching_assignments($user['id'], $semesterId);

trk_render_header('หน้าหลัก', 'home');
?>
<h1 class="text-xl font-semibold">สวัสดี <?= e($user['name']) ?></h1>
<p class="mt-1 text-sm text-slate-600">หน้าที่ของคุณในภาคเรียน <?= e(trk_semester_label()) ?> ระบบดึงจากข้อมูลครูที่ปรึกษาและการเช็คชื่อรายวิชา</p>

<?php if (trk_sees_all_rooms()): ?>
  <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
    บทบาทของคุณ (<?= strip_tags(trk_role_badges(array_intersect($user['roles'], TRK_ROLES_SEE_ALL))) ?>) เห็นข้อมูลการติดตามของทุกห้อง
  </div>
<?php endif; ?>

<section class="mt-6">
  <h2 class="text-base font-semibold">ห้องที่เป็นครูที่ปรึกษา</h2>
  <?php if (!$advisorRooms): ?>
    <p class="mt-2 text-sm text-slate-500">ไม่ได้เป็นครูที่ปรึกษาในภาคเรียนนี้</p>
  <?php else: ?>
    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($advisorRooms as $room): ?>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-baseline justify-between">
            <span class="text-lg font-semibold"><?= e(trk_classroom_label($room)) ?></span>
            <span class="text-sm text-slate-500"><?= $room['student_count'] ?> คน</span>
          </div>
          <div class="mt-2 text-xs text-slate-500">ครูที่ปรึกษา</div>
          <ul class="mt-1 space-y-0.5 text-sm">
            <?php foreach ($room['advisors'] as $a): ?>
              <li class="<?= (int) $a['psn_mem_id'] === $user['id'] ? 'font-medium text-blue-700' : 'text-slate-700' ?>"><?= e($a['full_name']) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="mt-8">
  <h2 class="text-base font-semibold">รายวิชาที่สอน</h2>
  <?php if (!$teaching): ?>
    <p class="mt-2 text-sm text-slate-500">ไม่พบรายวิชาที่สอนในภาคเรียนนี้</p>
  <?php else: ?>
    <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs text-slate-500">
          <tr>
            <th class="px-4 py-2 font-medium">รหัสวิชา</th>
            <th class="px-4 py-2 font-medium">รายวิชา</th>
            <th class="px-4 py-2 font-medium">ห้อง</th>
            <th class="px-4 py-2 text-right font-medium">หน่วยกิต</th>
            <th class="px-4 py-2 text-right font-medium">เวลาเรียนทั้งหมด</th>
            <th class="px-4 py-2 text-right font-medium">เกณฑ์ <?= implode('/', $thresholds) ?>%</th>
            <th class="px-4 py-2 text-right font-medium">เช็คชื่อแล้ว</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($teaching as $t): ?>
            <?php $total = trk_subject_total_hours((int) $t['vic_subject_id'], (float) $t['vic_subject_credit']); ?>
            <tr>
              <td class="px-4 py-2 font-mono text-xs"><?= e($t['vic_subject_code']) ?></td>
              <td class="px-4 py-2"><?= e($t['vic_subject_name']) ?></td>
              <td class="px-4 py-2"><?= e(trk_classroom_label($t)) ?></td>
              <td class="px-4 py-2 text-right"><?= e(rtrim(rtrim($t['vic_subject_credit'], '0'), '.')) ?></td>
              <?php if ($total > 0): ?>
                <td class="px-4 py-2 text-right"><?= $total ?> ชม.</td>
                <td class="px-4 py-2 text-right text-slate-600">
                  <?= implode(' / ', array_map(function ($p) use ($total) {
                      return rtrim(rtrim(number_format($total * $p / 100, 1), '0'), '.');
                  }, $thresholds)) ?> ชม.
                </td>
              <?php else: ?>
                <td class="px-4 py-2 text-right text-amber-700" colspan="2">ยังไม่ได้กำหนดชั่วโมง</td>
              <?php endif; ?>
              <td class="px-4 py-2 text-right"><?= (int) $t['periods_checked'] ?> คาบ</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php
trk_render_footer();
