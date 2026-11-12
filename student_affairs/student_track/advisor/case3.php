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
<h1 class="text-2xl font-semibold tracking-tight flex items-center gap-3"><span class="title-icon"><?= trk_icon('user-x', 'h-5 w-5') ?></span>นักเรียนที่ติดตามครบ 3 ครั้ง</h1>
<p class="mt-1 text-sm text-ink/60">
  นับบันทึกผลติดตามของเคสขาดรายวิชาและเคสขาดติดต่อกันรวมกันในภาคเรียนนี้
  ถ้าหลังวันที่ติดตามครั้งล่าสุดนักเรียนยังไม่มาโรงเรียนเลย ให้ยืนยันส่ง ขร.6 ขอให้จำหน่ายนักเรียนถึงเจ้าหน้าที่วิชาการ
</p>

<?php foreach ($rooms as $room): ?>
  <?php $candidates = trk_case3_candidates((int) $room['std_classroom_id'], $semesterId); ?>
  <section class="mt-6 card">
    <div class="border-b border-sand/50 px-4 py-3 font-semibold"><?= e(trk_classroom_label($room)) ?></div>
    <?php if (!$candidates): ?>
      <p class="px-4 py-4 text-sm text-ink/55 flex items-center gap-2"><?= trk_icon('inbox', 'h-4 w-4 text-ink/35') ?>ยังไม่มีนักเรียนที่ติดตามครบ 3 ครั้ง</p>
    <?php else: ?>
      <ul class="divide-y divide-sand/40">
        <?php foreach ($candidates as $c): ?>
          <?php $followups = trk_student_followups((int) $c['std_student_id'], $semesterId); ?>
          <li class="px-4 py-4">
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
              <span class="font-medium"><?= e($c['full_name']) ?></span>
              <span class="text-xs text-ink/40"><?= e($c['std_student_code']) ?> · เลขที่ <?= (int) $c['std_student_number'] ?></span>
              <span class="rounded-full px-2 py-0.5 text-xs font-medium <?= $c['eligible'] ? 'bg-rose-100 text-rose-800' : 'bg-sand/50 text-ink/60' ?>">
                <?= e($c['reason']) ?>
              </span>
            </div>
            <ol class="mt-2 space-y-0.5 text-xs text-ink/60">
              <?php foreach ($followups as $i => $f): ?>
                <li>
                  <?= $i + 1 ?>. <?= e(trk_thai_date($f['followup_date'], true)) ?> · <?= e($f['method']) ?> ·
                  <?php if ((int) $f['case_type'] === 1): ?>
                    ขาดรายวิชา <?= e($f['subject_codes'] ?: $f['vic_subject_code']) ?> ครั้งที่ <?= (int) $f['round_no'] ?>
                  <?php else: ?>
                    ขาดติดต่อกัน ครั้งที่ <?= (int) $f['round_no'] ?>
                  <?php endif; ?>
                  <a href="<?= e(trk_url('advisor/round.php?id=' . (int) $f['round_id'])) ?>" class="btn btn-soft-blue btn-sm ml-1"><?= trk_icon('history') ?>รอบติดตาม</a>
                </li>
              <?php endforeach; ?>
            </ol>
            <?php if ($c['kr6_id']): ?>
              <a href="<?= e(trk_url('documents/view.php?id=' . $c['kr6_id'])) ?>" class="btn btn-blue btn-sm mt-3"><?= trk_icon('eye') ?>ดู ขร.6</a>
            <?php elseif ($c['eligible']): ?>
              <form method="post" class="mt-3 flex flex-wrap items-end gap-3 rounded-lg bg-rose-50 p-3">
                <?= trk_csrf_field() ?>
                <input type="hidden" name="student_id" value="<?= (int) $c['std_student_id'] ?>">
                <label class="text-sm">
                  <span class="mb-1 block text-xs text-ink/55">เลขที่บันทึกข้อความ (ไม่บังคับ)</span>
                  <input name="doc_no" maxlength="30" class="w-36 input py-1.5">
                </label>
                <label class="flex items-center gap-2 text-sm text-rose-900">
                  <input type="checkbox" name="confirm" value="1" required >
                  ยืนยันว่าติดตามครบ 3 ครั้งแล้ว ผู้ปกครองยังไม่มาติดต่อและนักเรียนไม่มาเรียน
                </label>
                <button class="btn btn-red"><?= trk_icon('send') ?>ส่ง ขร.6 เสนอจำหน่าย</button>
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
