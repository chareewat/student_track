<?php
/**
 * ครูที่ปรึกษา: นักเรียนขาดเรียนติดต่อกัน (เคส 2) → เลือกแล้วออก ขร.5 รายคน
 */
require __DIR__ . '/../bootstrap.php';

$user = trk_user();
$semesterId = trk_semester_id();
$rooms = trk_advisor_classrooms($user['id'], $semesterId);
if (!$rooms) {
    trk_render_message('ไม่ได้เป็นครูที่ปรึกษา', 'คุณไม่ได้เป็นครูที่ปรึกษาในภาคเรียนนี้');
    exit;
}
$minDays = (int) trk_setting('consecutive_absent_days');
$defaultDeadline = date('Y-m-d', strtotime('+7 days'));
$errors = [];
$posted = ['doc_no' => [], 'deadline' => $defaultDeadline, 'classroom_id' => 0, 'student_ids' => []];

if (trk_is_post()) {
    trk_csrf_check();
    $classroomId = (int) ($_POST['classroom_id'] ?? 0);
    $studentIds = array_map('intval', isset($_POST['student_ids']) && is_array($_POST['student_ids']) ? $_POST['student_ids'] : []);
    $docNoInput = isset($_POST['doc_no']) && is_array($_POST['doc_no']) ? $_POST['doc_no'] : [];
    $deadline = trim((string) ($_POST['deadline'] ?? ''));
    $posted = ['doc_no' => $docNoInput, 'deadline' => $deadline, 'classroom_id' => $classroomId, 'student_ids' => $studentIds];

    $docNos = [];
    foreach ($studentIds as $id) {
        $no = trim((string) ($docNoInput[$id] ?? ''));
        if ($no === '' || mb_strlen($no) > 30) {
            $errors[$classroomId] = 'นักเรียนที่เลือกทุกคนต้องมีเลขหนังสือออก (ไม่เกิน 30 ตัวอักษร)';
        }
        $docNos[$id] = $no;
    }
    $d = DateTime::createFromFormat('Y-m-d', $deadline);
    if (!$d || $d->format('Y-m-d') !== $deadline || $deadline <= date('Y-m-d')) {
        $errors[$classroomId] = 'วันที่ให้ผู้ปกครองติดต่อกลับต้องเป็นวันหลังจากวันนี้';
    }
    if (!$studentIds) {
        $errors[$classroomId] = 'กรุณาเลือกนักเรียนอย่างน้อย 1 คน';
    }

    if (!$errors) {
        try {
            $created = trk_case2_issue($user['id'], $classroomId, $docNos, $deadline);
            trk_flash('success', 'ออก ขร.5 แล้ว ' . count($created) . ' ฉบับ พิมพ์ส่งผู้ปกครอง แล้วลงพื้นที่ติดตามนักเรียน');
            trk_redirect(count($created) === 1 ? 'documents/view.php?id=' . reset($created) : 'advisor/index.php');
        } catch (RuntimeException $ex) {
            $errors[$classroomId] = $ex->getMessage();
        }
    }
}

$sections = [];
foreach ($rooms as $room) {
    $sections[] = ['room' => $room, 'candidates' => trk_case2_candidates((int) $room['std_classroom_id'], $semesterId)];
}

trk_render_header('นักเรียนขาดติดต่อกัน', 'case2');
?>
<h1 class="text-2xl font-semibold tracking-tight">นักเรียนขาดเรียนติดต่อกัน</h1>
<p class="mt-1 text-sm text-ink/60">
  นับจากการเช็คชื่อรายวัน เฉพาะสถานะ "ขาด" ตั้งแต่ <?= $minDays ?> วันติดต่อกันขึ้นไป (วันที่ไม่มีการเช็คชื่อจะถูกข้าม)
  ครั้งถัดไปนับเมื่อนักเรียนกลับมาเรียนแล้วขาดใหม่ ขร.5 ต้องมีเลขหนังสือออกรายคน
</p>
<?php foreach (array_diff_key($errors, array_flip(array_map('intval', array_column($rooms, 'std_classroom_id')))) as $msg): ?>
  <div class="mt-4 alert border-rose-200 bg-rose-50 text-rose-800"><?= e($msg) ?></div>
<?php endforeach; ?>

<?php foreach ($sections as $sec): ?>
  <?php
    $room = $sec['room'];
    $rid = (int) $room['std_classroom_id'];
    $eligible = array_filter($sec['candidates'], function ($c) { return $c['eligible']; });
  ?>
  <section class="mt-6 card">
    <div class="border-b border-sand/50 px-4 py-3 font-semibold"><?= e(trk_classroom_label($room)) ?></div>
    <?php if (isset($errors[$rid])): ?>
      <div class="mx-4 mt-3 alert border-rose-200 bg-rose-50 text-rose-800"><?= e($errors[$rid]) ?></div>
    <?php endif; ?>
    <?php if (!$sec['candidates']): ?>
      <p class="px-4 py-4 text-sm text-ink/55">ไม่มีนักเรียนที่ขาดติดต่อกันถึงเกณฑ์</p>
    <?php else: ?>
      <form method="post">
        <?= trk_csrf_field() ?>
        <input type="hidden" name="classroom_id" value="<?= $rid ?>">
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th class="w-10 px-4 py-2"></th>
                <th class="px-2 py-2 font-medium">นักเรียน</th>
                <th class="px-2 py-2 font-medium">ช่วงที่ขาดติดต่อกัน</th>
                <th class="px-2 py-2 font-medium">ขร.5</th>
                <th class="px-4 py-2 font-medium">เลขหนังสือออก</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sec['candidates'] as $c): ?>
                <?php
                  $sid = (int) $c['std_student_id'];
                  $checked = $posted['classroom_id'] === $rid ? in_array($sid, $posted['student_ids'], true) : true;
                ?>
                <tr class="align-top <?= $c['eligible'] ? '' : 'text-ink/55' ?>">
                  <td class="px-4 py-2">
                    <?php if ($c['eligible']): ?>
                      <input type="checkbox" name="student_ids[]" value="<?= $sid ?>" <?= $checked ? 'checked' : '' ?>
                            >
                    <?php endif; ?>
                  </td>
                  <td class="px-2 py-2">
                    <div><?= e($c['full_name']) ?></div>
                    <div class="text-xs text-ink/40"><?= e($c['std_student_code']) ?> · เลขที่ <?= (int) $c['std_student_number'] ?></div>
                  </td>
                  <td class="px-2 py-2">
                    <?php foreach ($c['episodes'] as $ep): ?>
                      <div class="text-xs">
                        <?= e(trk_thai_date($ep['from'], true)) ?> – <?= e(trk_thai_date($ep['to'], true)) ?>
                        (<?= (int) $ep['days'] ?> วัน<?= $ep['ongoing'] ? ', ยังขาดอยู่' : '' ?>)
                        <?php if ($ep['round_no']): ?>
                          <a href="<?= e(trk_url('advisor/round.php?id=' . $ep['round_id'])) ?>" class="btn btn-soft-green btn-sm">ออกครั้งที่ <?= (int) $ep['round_no'] ?> แล้ว</a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </td>
                  <td class="px-2 py-2 text-xs">
                    <?php if ($c['eligible']): ?>
                      <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-800">รอออกครั้งที่ <?= (int) $c['next_round'] ?></span>
                    <?php elseif ($c['next_round'] === null): ?>
                      <span class="rounded-full bg-sand/50 px-2 py-0.5">ออกครบ 3 ครั้งแล้ว</span>
                    <?php else: ?>
                      <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-800">ออกแล้ว</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-2">
                    <?php if ($c['eligible']): ?>
                      <div class="flex items-center gap-1 text-xs text-ink/55">
                        <?= e(trk_setting('doc_no_prefix')) ?>
                        <input name="doc_no[<?= $sid ?>]" value="<?= e($posted['doc_no'][$sid] ?? '') ?>" maxlength="30"
                               class="w-24 input px-2 py-1 text-ink">
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($eligible): ?>
          <div class="flex flex-wrap items-end justify-end gap-3 border-t border-sand/50 px-4 py-3">
            <label class="text-sm">
              <span class="mb-1 block text-xs text-ink/55">ให้ผู้ปกครองติดต่อโรงเรียนภายในวันที่</span>
              <input type="date" name="deadline" min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>"
                     value="<?= e($posted['classroom_id'] === $rid ? $posted['deadline'] : $defaultDeadline) ?>"
                     class="input py-1.5">
            </label>
            <button class="btn btn-green">ออก ขร.5 ให้นักเรียนที่เลือก</button>
          </div>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </section>
<?php endforeach; ?>
<?php
trk_render_footer();
