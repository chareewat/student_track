<?php
/**
 * รายละเอียดรอบติดตาม: เอกสารทุกฉบับ ผลติดตาม และสิ่งที่ต้องทำต่อ
 */
require __DIR__ . '/../bootstrap.php';

$user = trk_user();
$round = trk_round_get((int) ($_GET['id'] ?? 0));
if (!$round || !trk_round_can_view($round, $user['id'])) {
    http_response_code(404);
    trk_render_message('ไม่พบรอบติดตาม', 'รอบติดตามนี้ไม่มีอยู่ หรือคุณไม่มีสิทธิ์ดู');
    exit;
}
$canAct = trk_round_can_act($round, $user['id']);

$docs = trk_db_all(
    "SELECT d.id, d.doc_type, d.round_no, d.doc_no, d.status, d.issued_at, d.cancel_reason,
            CONCAT(IFNULL(t.psn_title_name, ''), m.psn_mem_name, ' ', m.psn_mem_surname) AS issued_by_name
     FROM trk_document_rounds dr
     JOIN trk_documents d ON d.id = dr.document_id
     JOIN psn_member m ON m.psn_mem_id = d.issued_by
     LEFT JOIN psn_title t ON t.psn_title_id = m.psn_title_id
     WHERE dr.round_id = ? ORDER BY d.id",
    [(int) $round['id']]
);
$followup = trk_round_followup((int) $round['id']);

trk_render_header('รอบติดตาม', 'advisor');
?>
<div class="text-sm text-ink/55">
  รอบติดตาม<?= (int) $round['case_type'] === 1 ? 'ขาดรายวิชา' : 'ขาดติดต่อกัน' ?> ครั้งที่ <?= (int) $round['round_no'] ?>
  · <span class="rounded-full bg-sand/50 px-2 py-0.5 text-xs"><?= e(TRK_STAGE_LABELS[$round['stage']]) ?></span>
</div>
<h1 class="mt-1 text-2xl font-semibold tracking-tight flex items-center gap-3"><span class="title-icon"><?= trk_icon('user', 'h-5 w-5') ?></span><?= e($round['full_name']) ?></h1>
<p class="mt-1 text-sm text-ink/60">
  <?= e($round['std_student_code']) ?> · <?= e($round['classroom_label']) ?>
  <?php if ((int) $round['case_type'] === 1): ?>
    · <?= e($round['vic_subject_code'] . ' ' . $round['vic_subject_name']) ?>
    ขาดสะสม <?= (int) $round['absent_hours'] ?>/<?= (int) $round['total_hours'] ?> ชม. (ณ วันที่ออก ขร.1)
  <?php else: ?>
    · ขาดติดต่อกันตั้งแต่ <?= e(trk_thai_date($round['absent_from'])) ?> ถึง <?= e(trk_thai_date($round['absent_to'])) ?> (ณ วันที่ออก ขร.5)
  <?php endif; ?>
</p>

<?php if ($canAct && $round['stage'] === 'need_kr2'): ?>
  <a href="<?= e(trk_url('advisor/kr2.php?round=' . (int) $round['id'])) ?>" class="btn btn-green mt-4"><?= trk_icon('file-plus') ?>ออก ขร.2</a>
<?php elseif ($canAct && $round['stage'] === 'need_followup'): ?>
  <a href="<?= e(trk_url('advisor/followup.php?round=' . (int) $round['id'])) ?>" class="btn btn-green mt-4"><?= trk_icon('map-pin') ?>บันทึกผลติดตาม (ขร.3)</a>
<?php endif; ?>

<section class="mt-6">
  <h2 class="text-base font-semibold flex items-center gap-2"><?= trk_icon('file-text', 'h-[18px] w-[18px] text-navy/70') ?>เอกสาร</h2>
  <ol class="mt-3 space-y-2">
    <?php foreach ($docs as $d): ?>
      <li class="card flex flex-wrap items-center gap-3 px-4 py-3 text-sm <?= $d['status'] === 'cancelled' ? 'opacity-60' : '' ?>">
        <span class="font-medium"><?= e(trk_document_title($d)) ?></span>
        <?php if ($d['doc_no']): ?><span class="text-xs text-ink/55">ที่ <?= e($d['doc_no']) ?></span><?php endif; ?>
        <span class="text-xs text-ink/55"><?= e($d['issued_by_name']) ?> · <?= e(trk_thai_date($d['issued_at'], true)) ?></span>
        <?php if ($d['status'] === 'cancelled'): ?>
          <span class="text-xs text-rose-700">ยกเลิก: <?= e($d['cancel_reason']) ?></span>
        <?php endif; ?>
        <span class="ml-auto flex gap-3">
          <a href="<?= e(trk_url('documents/view.php?id=' . (int) $d['id'])) ?>" class="btn btn-soft-blue btn-sm"><?= trk_icon('eye') ?>ดู</a>
          <a href="<?= e(trk_url('documents/print.php?id=' . (int) $d['id'])) ?>" target="_blank" class="btn btn-soft-purple btn-sm"><?= trk_icon('printer') ?>PDF</a>
          <a href="<?= e(trk_url('documents/docx.php?id=' . (int) $d['id'])) ?>" class="btn btn-soft-purple btn-sm"><?= trk_icon('file-text') ?>Word</a>
        </span>
      </li>
    <?php endforeach; ?>
  </ol>
</section>

<?php if ($followup): ?>
  <section class="mt-6 card p-4">
    <h2 class="text-base font-semibold flex items-center gap-2"><?= trk_icon('camera', 'h-[18px] w-[18px] text-navy/70') ?>ผลการติดตาม</h2>
    <dl class="mt-3 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[10rem_1fr]">
      <dt class="text-ink/55">วันที่ติดตาม</dt><dd><?= e(trk_thai_date($followup['followup_date'])) ?></dd>
      <dt class="text-ink/55">วิธีการ</dt><dd><?= e($followup['method']) ?></dd>
      <dt class="text-ink/55">ผู้ร่วมติดตาม</dt><dd><?= e($followup['visitors']) ?></dd>
      <dt class="text-ink/55">ผลที่พบ</dt><dd class="whitespace-pre-line"><?= e($followup['findings']) ?></dd>
    </dl>
    <?php if ($followup['photos']): ?>
      <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <?php foreach ($followup['photos'] as $p): ?>
          <a href="<?= e(trk_url('photo.php?id=' . (int) $p['id'])) ?>" target="_blank"
             class="group relative block overflow-hidden rounded-2xl ring-1 ring-sand transition hover:-translate-y-px hover:shadow-lift">
            <?= trk_icon('maximize', 'h-3.5 w-3.5') ?><img src="<?= e(trk_url('photo.php?id=' . (int) $p['id'])) ?>" alt="รูปการติดตาม" class="aspect-[4/3] w-full object-cover">
            <span class="btn btn-blue btn-sm absolute bottom-2 right-2">ขยาย</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
<?php endif; ?>
<?php
trk_render_footer();
