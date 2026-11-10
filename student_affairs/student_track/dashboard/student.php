<?php
/**
 * ค้นหานักเรียน และประวัติการติดตามรายนักเรียน (ทุกภาคเรียน)
 */
require __DIR__ . '/../bootstrap.php';
trk_require_role(...TRK_ROLES_SEE_ALL);

$q = trim((string) ($_GET['q'] ?? ''));
$id = (int) ($_GET['id'] ?? 0);

$results = [];
if ($id === 0 && $q !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $results = trk_db_all(
        "SELECT st.std_student_id, st.std_student_code, st.std_status_id, ss.std_status_name,
                CONCAT(IFNULL(t.psn_title_name, ''), st.std_student_name, ' ', st.std_student_surname) AS full_name,
                c.std_classroom_level, c.std_classroom_room,
                (SELECT COUNT(*) FROM trk_rounds r WHERE r.std_student_id = st.std_student_id AND r.status <> 'cancelled') AS round_count
         FROM std_student st
         LEFT JOIN psn_title t ON t.psn_title_id = st.psn_title_id
         LEFT JOIN std_classroom c ON c.std_classroom_id = st.std_classroom_id
         LEFT JOIN std_status ss ON ss.std_status_id = st.std_status_id
         WHERE st.std_student_code LIKE ? OR st.std_student_name LIKE ? OR st.std_student_surname LIKE ?
            OR CONCAT(st.std_student_name, ' ', st.std_student_surname) LIKE ?
         ORDER BY st.std_status_id <> 1, st.std_student_code
         LIMIT 50",
        [$like, $like, $like, $like]
    );
    if (count($results) === 1) {
        trk_redirect('dashboard/student.php?id=' . (int) $results[0]['std_student_id']);
    }
}

$student = null;
if ($id > 0) {
    $student = trk_db_one(
        "SELECT st.*, ss.std_status_name, c.std_classroom_level, c.std_classroom_room,
                CONCAT(IFNULL(t.psn_title_name, ''), st.std_student_name, ' ', st.std_student_surname) AS full_name
         FROM std_student st
         LEFT JOIN psn_title t ON t.psn_title_id = st.psn_title_id
         LEFT JOIN std_classroom c ON c.std_classroom_id = st.std_classroom_id
         LEFT JOIN std_status ss ON ss.std_status_id = st.std_status_id
         WHERE st.std_student_id = ?",
        [$id]
    );
}

if ($student) {
    $rounds = trk_db_all(
        trk_rounds_select_sql() . ' WHERE r.std_student_id = ?
         ORDER BY r.semester_id DESC, r.opened_at DESC, r.id DESC',
        [$id]
    );
    foreach ($rounds as &$r) {
        $r['stage'] = trk_round_stage($r);
        $r['followup'] = trk_round_followup((int) $r['id']);
    }
    unset($r);
    $kr6 = trk_db_all(
        "SELECT id, status, issued_at, semester_id FROM trk_documents
         WHERE doc_type = 'KR6' AND JSON_EXTRACT(data, '$.student.id') = ? ORDER BY id DESC",
        [$id]
    );
    $semesters = [];
    foreach (trk_db_all('SELECT * FROM std_semester') as $s) {
        $semesters[(int) $s['std_semester_id']] = $s;
    }
    $guardian = trk_student_guardian_name($id);
}

trk_render_header($student ? $student['full_name'] : 'ค้นหานักเรียน', 'dashboard');
?>
<a href="<?= e(trk_url('dashboard/index.php')) ?>" class="btn btn-navy btn-sm"><?= trk_icon('arrow-left') ?>ภาพรวม</a>

<form class="mt-3 flex max-w-lg gap-2">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="ชื่อ นามสกุล หรือเลขประจำตัว" autofocus
         class="flex-1 input">
  <button class="btn btn-blue"><?= trk_icon('search') ?>ค้นหา</button>
</form>

<?php if ($id === 0 && $q !== ''): ?>
  <?php if (!$results): ?>
    <p class="mt-4 text-sm text-ink/55 flex items-center gap-2"><?= trk_icon('search', 'h-4 w-4 text-ink/35') ?>ไม่พบนักเรียน</p>
  <?php else: ?>
    <ul class="mt-4 space-y-2">
      <?php foreach ($results as $s): ?>
        <li>
          <a href="<?= e(trk_url('dashboard/student.php?id=' . (int) $s['std_student_id'])) ?>"
             class="card flex flex-wrap items-center gap-x-3 gap-y-1 px-4 py-3 transition hover:-translate-y-px hover:shadow-lift">
            <span class="font-medium"><?= e($s['full_name']) ?></span>
            <span class="text-xs text-ink/40"><?= e($s['std_student_code']) ?></span>
            <span class="text-sm text-ink/60"><?= $s['std_classroom_level'] ? 'ม.' . e($s['std_classroom_level'] . '/' . $s['std_classroom_room']) : '' ?></span>
            <?php if ((int) $s['std_status_id'] !== 1): ?><span class="text-xs text-ink/55">(<?= e($s['std_status_name']) ?>)</span><?php endif; ?>
            <span class="chip-sand ml-auto">ติดตาม <?= (int) $s['round_count'] ?> รอบ</span>
            <span class="btn btn-soft-blue btn-sm"><?= trk_icon('history') ?>ดูประวัติ</span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php if (count($results) === 50): ?><p class="mt-2 text-xs text-ink/55">แสดง 50 รายการแรก ระบุคำค้นให้ละเอียดขึ้น</p><?php endif; ?>
  <?php endif; ?>
<?php endif; ?>

<?php if ($id > 0 && !$student): ?>
  <p class="mt-4 text-sm text-ink/55 flex items-center gap-2"><?= trk_icon('search', 'h-4 w-4 text-ink/35') ?>ไม่พบนักเรียน</p>
<?php elseif ($student): ?>
  <div class="mt-6 card p-4">
    <h1 class="text-2xl font-semibold tracking-tight flex items-center gap-3"><span class="title-icon"><?= trk_icon('user', 'h-5 w-5') ?></span><?= e($student['full_name']) ?></h1>
    <dl class="mt-2 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-[9rem_1fr]">
      <dt class="text-ink/55">เลขประจำตัว</dt><dd><?= e($student['std_student_code']) ?></dd>
      <dt class="text-ink/55">ห้องปัจจุบัน</dt><dd><?= $student['std_classroom_level'] ? 'ม.' . e($student['std_classroom_level'] . '/' . $student['std_classroom_room']) : '-' ?> (เลขที่ <?= (int) $student['std_student_number'] ?>)</dd>
      <dt class="text-ink/55">สถานะ</dt><dd><?= e($student['std_status_name']) ?></dd>
      <dt class="text-ink/55">ผู้ปกครอง</dt><dd><?= e($guardian ?: '-') ?></dd>
    </dl>
  </div>

  <?php if ($kr6): ?>
    <div class="mt-4 alert border-rose-200 bg-rose-50 text-rose-900">
      <?php foreach ($kr6 as $d): ?>
        <div>
          ขร.6 เสนอจำหน่าย ภาคเรียน <?= e(trk_semester_label($semesters[(int) $d['semester_id']] ?? null)) ?>
          เมื่อ <?= e(trk_thai_date($d['issued_at'])) ?> <?= $d['status'] === 'cancelled' ? '(ยกเลิกแล้ว)' : '' ?>
          <a href="<?= e(trk_url('documents/view.php?id=' . (int) $d['id'])) ?>" class="btn btn-soft-red btn-sm ml-2"><?= trk_icon('file-warning') ?>ดู ขร.6</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h2 class="mt-6 text-base font-semibold flex items-center gap-2"><?= trk_icon('history', 'h-[18px] w-[18px] text-navy/70') ?>ประวัติการติดตาม</h2>
  <?php if (!$rounds): ?>
    <p class="mt-2 text-sm text-ink/55 flex items-center gap-2"><?= trk_icon('inbox', 'h-4 w-4 text-ink/35') ?>ไม่เคยถูกติดตาม</p>
  <?php else: ?>
    <ol class="mt-3 space-y-3">
      <?php foreach ($rounds as $r): ?>
        <li class="card p-4 text-sm <?= $r['stage'] === 'cancelled' ? 'opacity-60' : '' ?>">
          <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <span class="text-xs text-ink/55">ภาคเรียน <?= e(trk_semester_label($semesters[(int) $r['semester_id']] ?? null)) ?></span>
            <span class="font-medium">
              <?= (int) $r['case_type'] === 1
                  ? 'ขาดรายวิชา ' . e($r['vic_subject_code'] . ' ' . $r['vic_subject_name'])
                  : 'ขาดติดต่อกัน ' . e(trk_thai_date($r['absent_from'], true)) . ' – ' . e(trk_thai_date($r['absent_to'], true)) ?>
              ครั้งที่ <?= (int) $r['round_no'] ?>
            </span>
            <span class="rounded-full bg-sand/50 px-2 py-0.5 text-xs"><?= e(TRK_STAGE_LABELS[$r['stage']]) ?></span>
            <span class="text-xs text-ink/55"><?= e($r['classroom_label']) ?> · เปิดรอบ <?= e(trk_thai_date($r['opened_at'], true)) ?></span>
            <a href="<?= e(trk_url('advisor/round.php?id=' . (int) $r['id'])) ?>" class="btn btn-soft-blue btn-sm ml-auto"><?= trk_icon('eye') ?>รายละเอียด</a>
          </div>
          <?php if ($r['followup']): ?>
            <p class="mt-2 text-ink/60">
              ติดตาม <?= e(trk_thai_date($r['followup']['followup_date'])) ?> (<?= e($r['followup']['method']) ?>):
              <?= e($r['followup']['findings']) ?>
            </p>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>
<?php endif; ?>
<?php
trk_render_footer();
