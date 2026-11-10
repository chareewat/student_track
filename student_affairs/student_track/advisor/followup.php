<?php
/**
 * ครูที่ปรึกษาบันทึกผลติดตาม (พร้อมรูป) → ออก ขร.3 → ปิดรอบ
 */
require __DIR__ . '/../bootstrap.php';

const TRK_FOLLOWUP_METHODS = ['เยี่ยมบ้าน', 'เชิญผู้ปกครองมาโรงเรียน', 'ติดตามทางโทรศัพท์', 'ติดตามทาง LINE', 'อื่น ๆ'];

$user = trk_user();
$roundId = (int) ($_GET['round'] ?? $_POST['round'] ?? 0);
$round = trk_round_require_actionable($roundId, 'need_followup');

$advisorNames = array_column(trk_classroom_advisors((int) $round['std_classroom_id'], (int) $round['semester_id']), 'full_name');
$values = [
    'followup_date' => date('Y-m-d'),
    'method' => 'เยี่ยมบ้าน',
    'visitors' => implode(', ', $advisorNames) . ', เจ้าหน้าที่ฝ่ายกิจการนักเรียน',
    'findings' => '',
    'doc_no' => '',
];
$errors = [];

if (trk_is_post()) {
    trk_csrf_check();
    foreach ($values as $k => $_) {
        $values[$k] = trim((string) ($_POST[$k] ?? ''));
    }

    $date = DateTime::createFromFormat('Y-m-d', $values['followup_date']);
    if (!$date || $date->format('Y-m-d') !== $values['followup_date']) {
        $errors['followup_date'] = 'วันที่ไม่ถูกต้อง';
    } elseif ($values['followup_date'] > date('Y-m-d')) {
        $errors['followup_date'] = 'วันที่ติดตามต้องไม่เกินวันนี้';
    } elseif ($values['followup_date'] < substr($round['opened_at'], 0, 10)) {
        $errors['followup_date'] = 'วันที่ติดตามต้องไม่ก่อนวันที่เปิดรอบ (' . trk_thai_date($round['opened_at']) . ')';
    }
    if (!in_array($values['method'], TRK_FOLLOWUP_METHODS, true)) {
        $errors['method'] = 'กรุณาเลือกวิธีการติดตาม';
    }
    if ($values['visitors'] === '' || mb_strlen($values['visitors']) > 255) {
        $errors['visitors'] = 'กรุณาระบุผู้ร่วมติดตาม (ไม่เกิน 255 ตัวอักษร)';
    }
    if ($values['findings'] === '') {
        $errors['findings'] = 'กรุณาบันทึกผลที่พบ';
    }
    if (mb_strlen($values['doc_no']) > 30) {
        $errors['doc_no'] = 'เลขหนังสือยาวเกิน 30 ตัวอักษร';
    }

    $files = trk_uploaded_files('photos');
    if (count($files) > TRK_PHOTO_MAX) {
        $errors['photos'] = 'แนบรูปได้ไม่เกิน ' . TRK_PHOTO_MAX . ' รูป';
    } elseif ($values['method'] === 'เยี่ยมบ้าน' && !$files) {
        $errors['photos'] = 'การเยี่ยมบ้านต้องแนบรูปถ่ายอย่างน้อย 1 รูป';
    } else {
        foreach ($files as $f) {
            if ($msg = trk_validate_photo($f)) {
                $errors['photos'] = $msg;
                break;
            }
        }
    }

    if (!$errors) {
        $stored = array_map('trk_store_photo', $files);
        try {
            trk_record_followup($round, $user['id'], $values, $stored);
            trk_flash('success', 'บันทึกผลติดตามและออก ขร.3 แล้ว พิมพ์ ขร.3 เสนอผู้อำนวยการ');
        } catch (RuntimeException $ex) {
            foreach ($stored as $p) {
                @unlink(TRK_ROOT . '/storage/uploads/' . $p['path']);
            }
            trk_flash('error', $ex->getMessage());
        }
        trk_redirect('advisor/round.php?id=' . (int) $round['id']);
    }
}

function trk_field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<p class="mt-1 text-sm text-rose-600">' . e($errors[$key]) . '</p>' : '';
}

trk_render_header('บันทึกผลติดตาม', 'advisor');
?>
<a href="<?= e(trk_url('advisor/round.php?id=' . (int) $round['id'])) ?>" class="btn btn-navy btn-sm"><?= trk_icon('arrow-left') ?>รอบติดตาม</a>
<h1 class="mt-2 text-2xl font-semibold tracking-tight flex items-center gap-3"><span class="title-icon"><?= trk_icon('map-pin', 'h-5 w-5') ?></span>บันทึกผลติดตาม (ขร.3)</h1>
<p class="mt-1 text-sm text-ink/60">
  <?= e($round['full_name']) ?> (<?= e($round['std_student_code']) ?>) <?= e($round['classroom_label']) ?> · ครั้งที่ <?= (int) $round['round_no'] ?>
  <?php if ((int) $round['case_type'] === 1): ?>
    · ขาดรายวิชา <?= e($round['vic_subject_code'] . ' ' . $round['vic_subject_name']) ?>
  <?php endif; ?>
</p>

<?php if ($errors): ?>
  <div class="mt-4 alert border-rose-200 bg-rose-50 text-rose-800">กรุณาแก้ไขข้อมูลที่ไม่ถูกต้อง (ถ้าแนบรูปไว้ ต้องเลือกรูปใหม่อีกครั้ง)</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="mt-6 max-w-2xl space-y-4 card p-5">
  <?= trk_csrf_field() ?>
  <input type="hidden" name="round" value="<?= (int) $round['id'] ?>">

  <div class="grid gap-4 sm:grid-cols-2">
    <label class="block">
      <span class="mb-1 block text-sm font-medium">วันที่ติดตาม <span class="text-rose-600">*</span></span>
      <input type="date" name="followup_date" value="<?= e($values['followup_date']) ?>" max="<?= e(date('Y-m-d')) ?>" required
             class="input w-full">
      <?= trk_field_error($errors, 'followup_date') ?>
    </label>
    <label class="block">
      <span class="mb-1 block text-sm font-medium">วิธีการ <span class="text-rose-600">*</span></span>
      <select name="method" class="input w-full">
        <?php foreach (TRK_FOLLOWUP_METHODS as $m): ?>
          <option <?= $values['method'] === $m ? 'selected' : '' ?>><?= e($m) ?></option>
        <?php endforeach; ?>
      </select>
      <?= trk_field_error($errors, 'method') ?>
    </label>
  </div>

  <label class="block">
    <span class="mb-1 block text-sm font-medium">ผู้ร่วมติดตาม <span class="text-rose-600">*</span></span>
    <input name="visitors" value="<?= e($values['visitors']) ?>" maxlength="255" required class="input w-full">
    <?= trk_field_error($errors, 'visitors') ?>
  </label>

  <label class="block">
    <span class="mb-1 block text-sm font-medium">ผลที่พบ <span class="text-rose-600">*</span></span>
    <textarea name="findings" rows="5" required placeholder="สภาพปัญหา สาเหตุที่ขาดเรียน ข้อตกลงกับผู้ปกครอง"
              class="input w-full"><?= e($values['findings']) ?></textarea>
    <?= trk_field_error($errors, 'findings') ?>
  </label>

  <label class="block">
    <span class="mb-1 block text-sm font-medium">รูปถ่าย (JPG/PNG ไม่เกิน <?= TRK_PHOTO_MAX ?> รูป)</span>
    <input type="file" name="photos[]" accept="image/jpeg,image/png" multiple
           class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sand/50 file:px-3 file:py-2 file:text-sm">
    <p class="mt-1 text-xs text-ink/55">การเยี่ยมบ้านต้องแนบอย่างน้อย 1 รูป ระบบจะย่อรูปให้อัตโนมัติ</p>
    <?= trk_field_error($errors, 'photos') ?>
  </label>

  <label class="block">
    <span class="mb-1 block text-sm font-medium">เลขที่บันทึกข้อความ ขร.3 (ไม่บังคับ)</span>
    <input name="doc_no" value="<?= e($values['doc_no']) ?>" maxlength="30" class="w-40 input">
    <?= trk_field_error($errors, 'doc_no') ?>
  </label>

  <div class="flex justify-end">
    <button class="btn btn-green btn-lg"><?= trk_icon('save') ?>บันทึกและออก ขร.3</button>
  </div>
</form>
<?php
trk_render_footer();
