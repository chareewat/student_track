<?php
/**
 * ครูที่ปรึกษา: นักเรียนที่ติดตามครบ 3 ครั้งแล้ว → ยืนยันส่ง ขร.6 เสนอจำหน่าย (รายคน)
 */
require __DIR__ . '/../bootstrap.php';

$user = trk_user();
$semesterId = trk_semester_id();
$rooms = trk_advisor_classrooms($user['id'], $semesterId);
if (!$rooms) {
    trk_render_message('ไม่ได้เป็นครูที่ปรึกษา', 'คุณไม่ได้เป็นครูที่ปรึกษาในภาคเรียนนี้');
    exit;
}

if (trk_is_post()) {
    trk_csrf_check();
    $docNo = trim((string) ($_POST['doc_no'] ?? ''));
    if (($_POST['confirm'] ?? '') !== '1') {
        trk_flash('error', 'กรุณาติ๊กยืนยันก่อนส่ง ขร.6');
        trk_redirect('advisor/case3.php');
    }
    try {
        $docId = trk_case3_issue($user['id'], (int) ($_POST['student_id'] ?? 0), mb_substr($docNo, 0, 30));
        trk_flash('success', 'ส่ง ขร.6 ถึงเจ้าหน้าที่วิชาการแล้ว');
        trk_redirect('documents/view.php?id=' . $docId);
    } catch (RuntimeException $ex) {
        trk_flash('error', $ex->getMessage());
        trk_redirect('advisor/case3.php');
    }
}

trk_render_header('เสนอจำหน่าย (ขร.6)', 'case3');
?>
<h1 class="text-xl font-semibold">นักเรียนที่ติดตามครบ 3 ครั้ง</h1>
<p class="mt-1 text-sm text-slate-600">
  นับบันทึกผลติดตามของเคสขาดรายวิชาและเคสขาดติดต่อกันรวมกันในภาคเรียนนี้
  ถ้าหลังวันที่ติดตามครั้งล่าสุดนักเรียนยังไม่มาโรงเรียนเลย ให้ยืนยันส่ง ขร.6 ขอให้จำหน่ายนักเรียนถึงเจ้าหน้าที่วิชาการ
</p>

<?php foreach ($rooms as $room): ?>
  <?php $candidates = trk_case3_candidates((int) $room['std_classroom_id'], $semesterId); ?>
  <section class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-4 py-3 font-semibold"><?= e(trk_classroom_label($room)) ?></div>
    <?php if (!$candidates): ?>
      <p class="px-4 py-4 text-sm text-slate-500">ยังไม่มีนักเรียนที่ติดตามครบ 3 ครั้ง</p>
    <?php else: ?>
      <ul class="divide-y divide-slate-100">
        <?php foreach ($candidates as $c): ?>
          <?php $followups = trk_student_followups((int) $c['std_student_id'], $semesterId); ?>
          <li class="px-4 py-4">
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
              <span class="font-medium"><?= e($c['full_name']) ?></span>
              <span class="text-xs text-slate-400"><?= e($c['std_student_code']) ?> · เลขที่ <?= (int) $c['std_student_number'] ?></span>
              <span class="rounded-full px-2 py-0.5 text-xs font-medium <?= $c['eligible'] ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600' ?>">
                <?= e($c['reason']) ?>
              </span>
            </div>
            <ol class="mt-2 space-y-0.5 text-xs text-slate-600">
              <?php foreach ($followups as $i => $f): ?>
                <li>
                  <?= $i + 1 ?>. <?= e(trk_thai_date($f['followup_date'], true)) ?> · <?= e($f['method']) ?> ·
                  <?php if ((int) $f['case_type'] === 1): ?>
                    ขาดรายวิชา <?= e($f['vic_subject_code']) ?> ครั้งที่ <?= (int) $f['round_no'] ?>
                  <?php else: ?>
                    ขาดติดต่อกัน ครั้งที่ <?= (int) $f['round_no'] ?>
                  <?php endif; ?>
                  <a href="<?= e(trk_url('advisor/round.php?id=' . (int) $f['round_id'])) ?>" class="text-blue-700 hover:underline">รอบติดตาม</a>
                </li>
              <?php endforeach; ?>
            </ol>
            <?php if ($c['kr6_id']): ?>
              <a href="<?= e(trk_url('documents/view.php?id=' . $c['kr6_id'])) ?>" class="mt-2 inline-block text-sm text-blue-700 hover:underline">ดู ขร.6 →</a>
            <?php elseif ($c['eligible']): ?>
              <form method="post" class="mt-3 flex flex-wrap items-end gap-3 rounded-lg bg-rose-50 p-3">
                <?= trk_csrf_field() ?>
                <input type="hidden" name="student_id" value="<?= (int) $c['std_student_id'] ?>">
                <label class="text-sm">
                  <span class="mb-1 block text-xs text-slate-500">เลขที่บันทึกข้อความ (ไม่บังคับ)</span>
                  <input name="doc_no" maxlength="30" class="w-36 rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                </label>
                <label class="flex items-center gap-2 text-sm text-rose-900">
                  <input type="checkbox" name="confirm" value="1" required class="h-4 w-4 rounded border-slate-300">
                  ยืนยันว่าติดตามครบ 3 ครั้งแล้ว ผู้ปกครองยังไม่มาติดต่อและนักเรียนไม่มาเรียน
                </label>
                <button class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">ส่ง ขร.6 เสนอจำหน่าย</button>
              </form>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
<?php
trk_render_footer();
