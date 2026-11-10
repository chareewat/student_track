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
<h1 class="text-2xl font-semibold tracking-tight flex items-center gap-3"><span class="title-icon"><?= trk_icon('home', 'h-5 w-5') ?></span>สวัสดี <?= e($user['name']) ?></h1>
<p class="mt-1 text-sm text-ink/60">หน้าที่ของคุณในภาคเรียน <?= e(trk_semester_label()) ?> ระบบดึงจากข้อมูลครูที่ปรึกษาและการเช็คชื่อรายวิชา</p>

<?php if (trk_sees_all_rooms()): ?>
  <div class="mt-4 alert border-blue-200 bg-blue-50 text-blue-800">
    บทบาทของคุณ (<?= strip_tags(trk_role_badges(array_intersect($user['roles'], TRK_ROLES_SEE_ALL))) ?>) เห็นข้อมูลการติดตามของทุกห้อง
  </div>
<?php endif; ?>

<section class="mt-6">
  <h2 class="text-base font-semibold flex items-center gap-2"><?= trk_icon('users', 'h-[18px] w-[18px] text-navy/70') ?>ห้องที่เป็นครูที่ปรึกษา</h2>
  <?php if (!$advisorRooms): ?>
    <p class="mt-2 text-sm text-ink/55 flex items-center gap-2"><?= trk_icon('inbox', 'h-4 w-4 text-ink/35') ?>ไม่ได้เป็นครูที่ปรึกษาในภาคเรียนนี้</p>
  <?php else: ?>
    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($advisorRooms as $room): ?>
        <div class="card p-4">
          <div class="flex items-baseline justify-between">
            <span class="text-lg font-semibold"><?= e(trk_classroom_label($room)) ?></span>
            <span class="text-sm text-ink/55"><?= $room['student_count'] ?> คน</span>
          </div>
          <div class="mt-2 text-xs text-ink/55">ครูที่ปรึกษา</div>
          <ul class="mt-1 space-y-0.5 text-sm">
            <?php foreach ($room['advisors'] as $a): ?>
              <li class="<?= (int) $a['psn_mem_id'] === $user['id'] ? 'font-semibold text-navy' : 'text-ink/80' ?>"><?= e($a['full_name']) ?><?= (int) $a['psn_mem_id'] === $user['id'] ? ' <span class="chip-navy">คุณ</span>' : '' ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="mt-8">
  <h2 class="text-base font-semibold flex items-center gap-2"><?= trk_icon('book', 'h-[18px] w-[18px] text-navy/70') ?>รายวิชาที่สอน</h2>
  <?php if (!$teaching): ?>
    <p class="mt-2 text-sm text-ink/55 flex items-center gap-2"><?= trk_icon('search', 'h-4 w-4 text-ink/35') ?>ไม่พบรายวิชาที่สอนในภาคเรียนนี้</p>
  <?php else: ?>
    <div class="mt-3 table-wrap">
      <table class="table">
        <thead>
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
        <tbody>
          <?php foreach ($teaching as $t): ?>
            <?php $total = trk_subject_total_hours((int) $t['vic_subject_id'], (float) $t['vic_subject_credit']); ?>
            <tr>
              <td class="px-4 py-2 font-mono text-xs"><?= e($t['vic_subject_code']) ?></td>
              <td class="px-4 py-2"><?= e($t['vic_subject_name']) ?></td>
              <td class="px-4 py-2"><?= e(trk_classroom_label($t)) ?></td>
              <td class="px-4 py-2 text-right"><?= e(rtrim(rtrim($t['vic_subject_credit'], '0'), '.')) ?></td>
              <?php if ($total > 0): ?>
                <td class="px-4 py-2 text-right"><?= $total ?> ชม.</td>
                <td class="px-4 py-2 text-right text-ink/60">
                  <?= implode(' / ', array_map(function ($p) use ($total) {
                      return rtrim(rtrim(number_format($total * $p / 100, 1), '0'), '.');
                  }, $thresholds)) ?> ชม.
                </td>
              <?php else: ?>
                <td class="px-4 py-2 text-right" colspan="2"><span class="chip-yellow">ยังไม่ได้กำหนดชั่วโมง</span></td>
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
