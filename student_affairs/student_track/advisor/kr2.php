<?php
/**
 * ครูที่ปรึกษาออก ขร.2 (และ บค.14 สำหรับ ม.1–3) ให้รอบติดตามเคส 1
 */
require __DIR__ . '/../bootstrap.php';

$user = trk_user();
$roundId = (int) ($_GET['round'] ?? $_POST['round'] ?? 0);
$round = trk_round_require_actionable($roundId, 'need_kr2');
$needsBk14 = trk_requires_bk14($round);

$values = [
    'doc_no' => '',
    'bk14_doc_no' => '',
    'guardian' => trk_student_guardian_name((int) $round['std_student_id']),
];
$errors = [];

if (trk_is_post()) {
    trk_csrf_check();
    foreach ($values as $k => $_) {
        $values[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    if ($values['doc_no'] === '') {
        $errors['doc_no'] = 'ต้องกรอกเลขหนังสือออกที่ได้จากงานสารบรรณ';
    }
    if (mb_strlen($values['doc_no']) > 30 || mb_strlen($values['bk14_doc_no']) > 30) {
        $errors['doc_no'] = 'เลขหนังสือยาวเกิน 30 ตัวอักษร';
    }
    if (!$errors) {
        try {
            [$kr2Id, $bk14Id] = trk_issue_kr2($round, $user['id'], $values['doc_no'], $values['bk14_doc_no'], mb_substr($values['guardian'], 0, 120));
            trk_flash('success', 'ออก ขร.2' . ($bk14Id ? ' และ บค.14' : '') . ' แล้ว พิมพ์ส่งผู้ปกครอง แล้วลงพื้นที่ติดตามนักเรียน');
        } catch (RuntimeException $ex) {
            trk_flash('error', $ex->getMessage());
        }
        trk_redirect('advisor/round.php?id=' . (int) $round['id']);
    }
}

$prefix = trk_setting('doc_no_prefix');
trk_render_header('ออก ขร.2', 'advisor');
?>
<a href="<?= e(trk_url('advisor/round.php?id=' . (int) $round['id'])) ?>" class="btn btn-navy btn-sm">← รอบติดตาม</a>
<h1 class="mt-2 text-2xl font-semibold tracking-tight">ออก ขร.2 แจ้งพฤติกรรมการเรียนถึงผู้ปกครอง</h1>
<p class="mt-1 text-sm text-ink/60">
  <?= e($round['full_name']) ?> (<?= e($round['std_student_code']) ?>) <?= e($round['classroom_label']) ?> ·
  ขาดรายวิชา <?= e($round['vic_subject_code'] . ' ' . $round['vic_subject_name']) ?> <?= (int) $round['absent_hours'] ?> คาบ · ครั้งที่ <?= (int) $round['round_no'] ?>
</p>

<form method="post" class="mt-6 max-w-xl space-y-4 card p-5">
  <?= trk_csrf_field() ?>
  <input type="hidden" name="round" value="<?= (int) $round['id'] ?>">

  <label class="block">
    <span class="mb-1 block text-sm font-medium">เลขหนังสือออก ขร.2 <span class="text-rose-600">*</span></span>
    <div class="flex items-center gap-2">
      <span class="text-sm text-ink/55"><?= e($prefix) ?></span>
      <input name="doc_no" value="<?= e($values['doc_no']) ?>" maxlength="30" required placeholder="เช่น 1234"
             class="w-40 input">
    </div>
    <?php if (isset($errors['doc_no'])): ?><p class="mt-1 text-sm text-rose-600"><?= e($errors['doc_no']) ?></p><?php endif; ?>
  </label>

  <?php if ($needsBk14): ?>
    <label class="block">
      <span class="mb-1 block text-sm font-medium">เลขหนังสือ บค.14 (ไม่บังคับ)</span>
      <div class="flex items-center gap-2">
        <span class="text-sm text-ink/55"><?= e($prefix) ?>ว</span>
        <input name="bk14_doc_no" value="<?= e($values['bk14_doc_no']) ?>" maxlength="30" class="w-40 input">
      </div>
      <p class="mt-1 text-xs text-ink/55">นักเรียน ม.1–3 อยู่ในการศึกษาภาคบังคับ ระบบจะออก บค.14 หนังสือเตือนผู้ปกครองคู่กับ ขร.2</p>
    </label>
  <?php endif; ?>

  <label class="block">
    <span class="mb-1 block text-sm font-medium">เรียน ผู้ปกครอง</span>
    <input name="guardian" value="<?= e($values['guardian']) ?>" maxlength="120" placeholder="ชื่อผู้ปกครอง (เว้นว่างได้ จะพิมพ์เป็นจุดไข่ปลา)"
           class="input w-full">
    <p class="mt-1 text-xs text-ink/55">ดึงจากข้อมูลครอบครัวในระบบเดิม แก้ไขได้</p>
  </label>

  <div class="flex justify-end">
    <button class="btn btn-green btn-lg">
      ออก ขร.2<?= $needsBk14 ? ' + บค.14' : '' ?>
    </button>
  </div>
</form>
<?php
trk_render_footer();
