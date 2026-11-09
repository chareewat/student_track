<?php
require __DIR__ . '/../bootstrap.php';
trk_require_role('admin');

$me = trk_user();
$semesterId = trk_semester_id();

// รายวิชาที่ไม่มีหน่วยกิตหรือมีชั่วโมงกำหนดเอง ที่มีการสอนในภาคเรียนนี้
$customSubjects = trk_db_all(
    'SELECT s.vic_subject_id, s.vic_subject_code, s.vic_subject_name, s.vic_subject_credit, h.hours
     FROM vic_subject s
     LEFT JOIN trk_subject_hours h ON h.vic_subject_id = s.vic_subject_id
     WHERE (s.vic_subject_credit = 0 OR h.vic_subject_id IS NOT NULL)
       AND s.vic_subject_id IN (SELECT vic_subject_id FROM vic_attendance_teach WHERE std_semester_id = ?
                                UNION SELECT vic_subject_id FROM t_teachtable WHERE std_semester_id = ? AND t_teachtable_status = 1)
     ORDER BY s.vic_subject_code',
    [$semesterId, $semesterId]
);

$errors = [];
$values = trk_settings();

if (trk_is_post()) {
    trk_csrf_check();
    $input = [];
    foreach (array_keys(trk_setting_defaults()) as $key) {
        $input[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    $t1 = (int) $input['kr1_threshold_1'];
    $t2 = (int) $input['kr1_threshold_2'];
    $t3 = (int) $input['kr1_threshold_3'];
    if (!($t1 >= 1 && $t1 < $t2 && $t2 < $t3 && $t3 <= 100)) {
        $errors['kr1_threshold_1'] = 'เกณฑ์ต้องเป็นตัวเลข 1–100 และเรียงจากน้อยไปมาก';
    }
    if ((int) $input['hours_per_credit'] < 1 || (int) $input['hours_per_credit'] > 200) {
        $errors['hours_per_credit'] = 'ต้องเป็นตัวเลข 1–200';
    }
    if ((int) $input['consecutive_absent_days'] < 1 || (int) $input['consecutive_absent_days'] > 30) {
        $errors['consecutive_absent_days'] = 'ต้องเป็นตัวเลข 1–30';
    }

    $hours = [];
    foreach ((array) ($_POST['subject_hours'] ?? []) as $subjectId => $h) {
        $h = trim((string) $h);
        if ($h === '') {
            $hours[(int) $subjectId] = null; // ลบค่าที่กำหนดเอง
        } elseif (ctype_digit($h) && (int) $h > 0 && (int) $h <= 400) {
            $hours[(int) $subjectId] = (int) $h;
        } else {
            $errors['subject_hours'] = 'ชั่วโมงเรียนต้องเป็นตัวเลข 1–400 หรือเว้นว่าง';
        }
    }

    if (!$errors) {
        trk_db_transaction(function () use ($input, $hours, $me) {
            trk_save_settings($input, $me['id']);
            foreach ($hours as $subjectId => $h) {
                if ($h === null) {
                    trk_db_exec('DELETE FROM trk_subject_hours WHERE vic_subject_id = ?', [$subjectId]);
                } else {
                    trk_db_exec(
                        'INSERT INTO trk_subject_hours (vic_subject_id, hours, updated_by, updated_at) VALUES (?, ?, ?, NOW())
                         ON DUPLICATE KEY UPDATE hours = VALUES(hours), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
                        [$subjectId, $h, $me['id']]
                    );
                }
            }
        });
        trk_audit('settings.update', ['settings' => $input, 'subject_hours' => $hours]);
        trk_flash('success', 'บันทึกการตั้งค่าแล้ว');
        trk_redirect('admin/settings.php');
    }
    $values = $input;
}

function trk_settings_input(string $name, string $value, string $suffix = '', string $type = 'number'): string
{
    return '<div class="flex items-center gap-2"><input type="' . $type . '" name="' . e($name) . '" value="' . e($value) . '" '
        . ($type === 'number' ? 'min="1" ' : '')
        . 'class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm' . ($type === 'number' ? ' sm:w-28' : '') . '">'
        . ($suffix ? '<span class="text-sm text-slate-500">' . e($suffix) . '</span>' : '') . '</div>';
}

function trk_settings_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<p class="mt-1 text-sm text-rose-600">' . e($errors[$key]) . '</p>' : '';
}

trk_render_header('ตั้งค่า', 'settings');
?>
<h1 class="text-xl font-semibold">ตั้งค่า</h1>

<form method="post" class="mt-4 space-y-6">
  <?= trk_csrf_field() ?>

  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="font-semibold">เคส 1: ขาดเรียนรายวิชา</h2>
    <p class="mt-1 text-sm text-slate-500">นับชั่วโมงที่มีสถานะ ขาด, ลา และหนี เทียบกับเวลาเรียนทั้งหมดของรายวิชา</p>
    <div class="mt-4 grid gap-4 sm:grid-cols-3">
      <?php for ($i = 1; $i <= 3; $i++): ?>
        <label class="block">
          <span class="mb-1 block text-sm text-slate-700">ครั้งที่ <?= $i ?></span>
          <?= trk_settings_input("kr1_threshold_$i", $values["kr1_threshold_$i"], '%') ?>
        </label>
      <?php endfor; ?>
    </div>
    <?= trk_settings_error($errors, 'kr1_threshold_1') ?>
    <label class="mt-4 block">
      <span class="mb-1 block text-sm text-slate-700">เวลาเรียนทั้งหมด = หน่วยกิต ×</span>
      <?= trk_settings_input('hours_per_credit', $values['hours_per_credit'], 'ชั่วโมง') ?>
    </label>
    <?= trk_settings_error($errors, 'hours_per_credit') ?>
  </section>

  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="font-semibold">เคส 2: ขาดเรียนติดต่อกัน</h2>
    <label class="mt-4 block">
      <span class="mb-1 block text-sm text-slate-700">จำนวนวันที่ขาดติดต่อกัน</span>
      <?= trk_settings_input('consecutive_absent_days', $values['consecutive_absent_days'], 'วัน') ?>
    </label>
    <?= trk_settings_error($errors, 'consecutive_absent_days') ?>
  </section>

  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="font-semibold">ชื่อผู้ลงนามในเอกสาร</h2>
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-sm text-slate-700">ผู้อำนวยการโรงเรียน</span>
        <?= trk_settings_input('director_name', $values['director_name'], '', 'text') ?>
      </label>
      <label class="block">
        <span class="mb-1 block text-sm text-slate-700">รองผู้อำนวยการกลุ่มบริหารวิชาการ</span>
        <?= trk_settings_input('deputy_academic_name', $values['deputy_academic_name'], '', 'text') ?>
      </label>
    </div>
  </section>

  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="font-semibold">ชั่วโมงเรียนของรายวิชาที่ไม่มีหน่วยกิต</h2>
    <p class="mt-1 text-sm text-slate-500">รายวิชาที่สอนในภาคเรียน <?= e(trk_semester_label()) ?> ที่หน่วยกิตเป็น 0 ต้องกำหนดชั่วโมงเอง ระบบจึงจะคำนวณเกณฑ์ได้</p>
    <?= trk_settings_error($errors, 'subject_hours') ?>
    <?php if (!$customSubjects): ?>
      <p class="mt-3 text-sm text-slate-500">ไม่มีรายวิชาที่ต้องกำหนดชั่วโมงเอง</p>
    <?php else: ?>
      <div class="mt-3 divide-y divide-slate-100">
        <?php foreach ($customSubjects as $s): ?>
          <label class="flex items-center justify-between gap-4 py-2">
            <span class="text-sm"><span class="font-mono text-xs text-slate-500"><?= e($s['vic_subject_code']) ?></span> <?= e($s['vic_subject_name']) ?></span>
            <?= trk_settings_input('subject_hours[' . (int) $s['vic_subject_id'] . ']', (string) ($_POST['subject_hours'][$s['vic_subject_id']] ?? $s['hours']), 'ชม.') ?>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <div class="flex justify-end">
    <button class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700">บันทึก</button>
  </div>
</form>
<?php
trk_render_footer();
