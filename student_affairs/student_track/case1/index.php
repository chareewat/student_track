<?php
/**
 * ครูประจำวิชา: นักเรียนที่ถึงเกณฑ์ขาดรายวิชา → เลือกแล้วออก ขร.1
 */
require __DIR__ . '/../bootstrap.php';

$user = trk_user();
$semesterId = trk_semester_id();
$thresholds = trk_kr1_thresholds();
$assignments = trk_teaching_assignments($user['id'], $semesterId);

$sections = [];
foreach ($assignments as $a) {
    $roomLabel = trk_room_code($a);
    $total = trk_subject_total_hours((int) $a['vic_subject_id'], (float) $a['vic_subject_credit']);
    $sections[] = [
        'assignment' => $a,
        'total' => $total,
        'candidates' => $total > 0 ? trk_case1_candidates($a, $roomLabel, $semesterId) : [],
    ];
}

$issuedDocs = trk_db_all(
    "SELECT d.id, d.doc_type, d.round_no, d.doc_no, d.status, d.issued_at, d.cancel_reason, d.std_classroom_id,
            s.vic_subject_code, s.vic_subject_name, c.std_classroom_level, c.std_classroom_room,
            (SELECT COUNT(*) FROM trk_document_rounds dr WHERE dr.document_id = d.id) AS student_count
     FROM trk_documents d
     JOIN vic_subject s ON s.vic_subject_id = d.vic_subject_id
     JOIN std_classroom c ON c.std_classroom_id = d.std_classroom_id
     WHERE d.doc_type = 'KR1' AND d.issued_by = ? AND d.semester_id = ?
     ORDER BY d.id DESC",
    [$user['id'], $semesterId]
);

trk_render_header('นักเรียนขาดรายวิชา', 'case1');
?>
<h1 class="text-xl font-semibold">นักเรียนขาดรายวิชา</h1>
<p class="mt-1 text-sm text-slate-600">
  นับชั่วโมง ขาด + ลา + หนี เทียบกับเวลาเรียนทั้งหมด เกณฑ์ครั้งที่ 1/2/3 = <?= implode('/', $thresholds) ?>%
  แสดงเฉพาะนักเรียนที่ถึงเกณฑ์ครั้งที่ 1 ขึ้นไป ต้องออก ขร.1 เรียงตามครั้ง
</p>

<?php foreach ($sections as $i => $sec): ?>
  <?php
    $a = $sec['assignment'];
    $eligibleCount = count(array_filter($sec['candidates'], function ($c) { return $c['eligible']; }));
  ?>
  <section class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 border-b border-slate-100 px-4 py-3">
      <span class="font-mono text-xs text-slate-500"><?= e($a['vic_subject_code']) ?></span>
      <span class="font-semibold"><?= e($a['vic_subject_name']) ?></span>
      <span class="text-sm text-slate-600">ห้องที่สอน <?= e(trk_classroom_label($a)) ?></span>
      <span class="ml-auto text-xs text-slate-500">
        <?php if ($sec['total'] > 0): ?>
          เวลาเรียนทั้งหมด <?= $sec['total'] ?> ชม. · เกณฑ์ <?= implode(' / ', array_map(function ($p) use ($sec) {
              return trk_case1_threshold_hours($sec['total'], $p);
          }, $thresholds)) ?> ชม.
        <?php endif; ?>
      </span>
    </div>

    <?php if ($sec['total'] <= 0): ?>
      <p class="px-4 py-4 text-sm text-amber-700">รายวิชานี้ไม่มีหน่วยกิตและยังไม่ได้กำหนดชั่วโมงเรียน ให้ Admin กำหนดในหน้าตั้งค่าก่อน</p>
    <?php elseif (!$sec['candidates']): ?>
      <p class="px-4 py-4 text-sm text-slate-500">ยังไม่มีนักเรียนที่ถึงเกณฑ์</p>
    <?php else: ?>
      <form method="post" action="<?= e(trk_url('case1/issue.php')) ?>">
        <?= trk_csrf_field() ?>
        <input type="hidden" name="subject_id" value="<?= (int) $a['vic_subject_id'] ?>">
        <input type="hidden" name="classroom_id" value="<?= (int) $a['std_classroom_id'] ?>">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs text-slate-500">
              <tr>
                <th class="w-10 px-4 py-2"></th>
                <th class="px-2 py-2 font-medium">นักเรียน</th>
                <th class="px-2 py-2 font-medium">ห้อง</th>
                <th class="px-2 py-2 text-right font-medium">ขาด</th>
                <th class="px-2 py-2 text-right font-medium">ลา</th>
                <th class="px-2 py-2 text-right font-medium">หนี</th>
                <th class="px-2 py-2 text-right font-medium">รวม</th>
                <th class="px-2 py-2 text-right font-medium">%</th>
                <th class="px-4 py-2 font-medium">ขร.1</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <?php foreach ($sec['candidates'] as $c): ?>
                <tr class="<?= $c['eligible'] ? '' : 'text-slate-500' ?>">
                  <td class="px-4 py-2">
                    <?php if ($c['eligible']): ?>
                      <input type="checkbox" name="student_ids[]" value="<?= (int) $c['std_student_id'] ?>" checked
                             class="h-4 w-4 rounded border-slate-300 text-blue-600">
                    <?php endif; ?>
                  </td>
                  <td class="px-2 py-2">
                    <div><?= e($c['full_name']) ?></div>
                    <div class="text-xs text-slate-400"><?= e($c['std_student_code']) ?> · เลขที่ <?= (int) $c['std_student_number'] ?></div>
                  </td>
                  <td class="px-2 py-2"><?= e(trk_classroom_label($c)) ?></td>
                  <td class="px-2 py-2 text-right"><?= (int) $c['absent'] ?></td>
                  <td class="px-2 py-2 text-right"><?= (int) $c['on_leave'] ?></td>
                  <td class="px-2 py-2 text-right"><?= (int) $c['skipped'] ?></td>
                  <td class="px-2 py-2 text-right font-medium"><?= (int) $c['absent_hours'] ?> ชม.</td>
                  <td class="px-2 py-2 text-right"><?= e(trk_format_hours((float) $c['percent'])) ?>%</td>
                  <td class="px-4 py-2 text-xs">
                    <?php if ($c['eligible']): ?>
                      <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-800">รอออกครั้งที่ <?= (int) $c['next_round'] ?></span>
                    <?php elseif ($c['next_round'] === null): ?>
                      <span class="rounded-full bg-slate-100 px-2 py-0.5">ออกครบ 3 ครั้งแล้ว</span>
                    <?php else: ?>
                      <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-800">ออกครั้งที่ <?= (int) $c['last_round'] ?> แล้ว</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($eligibleCount): ?>
          <div class="flex flex-wrap items-end justify-end gap-3 border-t border-slate-100 px-4 py-3">
            <label class="text-sm">
              <span class="mb-1 block text-xs text-slate-500">เลขหนังสือ (ไม่บังคับ)</span>
              <input name="doc_no" maxlength="30" placeholder="เช่น 123/2569" class="w-40 rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
            </label>
            <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">ออก ขร.1 ให้นักเรียนที่เลือก</button>
          </div>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </section>
<?php endforeach; ?>

<section class="mt-10">
  <h2 class="text-base font-semibold">ขร.1 ที่ออกแล้วในภาคเรียนนี้</h2>
  <?php if (!$issuedDocs): ?>
    <p class="mt-2 text-sm text-slate-500">ยังไม่มี</p>
  <?php else: ?>
    <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs text-slate-500">
          <tr>
            <th class="px-4 py-2 font-medium">วันที่ออก</th>
            <th class="px-4 py-2 font-medium">รายวิชา</th>
            <th class="px-4 py-2 font-medium">ห้อง</th>
            <th class="px-4 py-2 font-medium">ครั้งที่</th>
            <th class="px-4 py-2 text-right font-medium">นักเรียน</th>
            <th class="px-4 py-2 font-medium">สถานะ</th>
            <th class="px-4 py-2"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($issuedDocs as $d): ?>
            <tr class="<?= $d['status'] === 'cancelled' ? 'text-slate-400' : '' ?>">
              <td class="px-4 py-2"><?= e(trk_thai_date($d['issued_at'], true)) ?></td>
              <td class="px-4 py-2"><span class="font-mono text-xs"><?= e($d['vic_subject_code']) ?></span> <?= e($d['vic_subject_name']) ?></td>
              <td class="px-4 py-2"><?= e(trk_classroom_label($d)) ?></td>
              <td class="px-4 py-2"><?= (int) $d['round_no'] ?></td>
              <td class="px-4 py-2 text-right"><?= (int) $d['student_count'] ?> คน</td>
              <td class="px-4 py-2">
                <?= $d['status'] === 'cancelled'
                    ? '<span class="text-xs">ยกเลิกแล้ว</span>'
                    : '<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800">ส่งครูที่ปรึกษาแล้ว</span>' ?>
              </td>
              <td class="px-4 py-2 text-right">
                <a href="<?= e(trk_url('documents/view.php?id=' . (int) $d['id'])) ?>" class="text-blue-700 hover:underline">ดู/พิมพ์</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php
trk_render_footer();
