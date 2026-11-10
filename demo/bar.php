<?php
/**
 * แถบโหมดสาธิตที่มุมล่างของทุกหน้าในโมดูล (เรียกจาก views/layout_footer.php เมื่อมีโฟลเดอร์ demo/)
 * แสดงเฉพาะบนเครื่องพัฒนาที่เปิดจากเครื่องเดียวกัน
 */
require_once __DIR__ . '/guard.php';
if (!trk_demo_allowed()) {
    return;
}
$demoBase = substr(trk_url(), 0, -strlen('student_affairs/student_track/')) . 'demo/';
$demoHere = ltrim(substr((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), strlen(substr(trk_url(), 0, -strlen('student_affairs/student_track/')))), '/');
$demoQuery = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
$demoTo = $demoHere . ($demoQuery !== '' ? '?' . $demoQuery : '');
$demoMe = trk_db_value('SELECT psn_mem_username FROM psn_member WHERE psn_mem_id = ?', [(int) ($_SESSION['psn_mem_id'] ?? 0)]);
?>
<div class="fixed bottom-3 left-3 z-40 flex max-w-[calc(100vw-1.5rem)] flex-wrap items-center gap-2 rounded-2xl border border-accent/40 bg-white/95 p-2 shadow-lift backdrop-blur print:hidden">
  <span class="chip-orange"><?= trk_icon('sparkles', 'h-3.5 w-3.5') ?>โหมดสาธิต</span>
  <form action="<?= e($demoBase . 'switch.php') ?>" method="get" class="flex items-center gap-1.5">
    <input type="hidden" name="to" value="<?= e($demoTo) ?>">
    <label for="demo-user" class="sr-only">สลับผู้ใช้</label>
    <select id="demo-user" name="u" class="input px-2 py-1 text-xs" onchange="this.form.submit()">
      <?php foreach (TRK_DEMO_USERS as $u => [$label]): ?>
        <option value="<?= e($u) ?>" <?= $u === $demoMe ? 'selected' : '' ?>><?= e($label) ?> (<?= e($u) ?>)</option>
      <?php endforeach; ?>
      <?php if ($demoMe && !isset(TRK_DEMO_USERS[$demoMe])): ?>
        <option value="<?= e($demoMe) ?>" selected>ครูประจำวิชา (<?= e($demoMe) ?>)</option>
      <?php endif; ?>
    </select>
    <button class="btn btn-soft-orange btn-sm"><?= trk_icon('user') ?>สลับ</button>
  </form>
  <a href="<?= e($demoBase . 'index.php') ?>" class="btn btn-navy btn-sm"><?= trk_icon('clipboard') ?>บทนำเสนอ</a>
</div>
