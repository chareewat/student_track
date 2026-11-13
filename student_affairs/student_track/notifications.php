<?php
require __DIR__ . '/bootstrap.php';

$user = trk_user();

if (trk_is_post()) {
    trk_csrf_check();
    trk_db_exec('UPDATE trk_notifications SET read_at = ? WHERE recipient_id = ? AND read_at IS NULL',
        [date('Y-m-d H:i:s'), $user['id']]);
    trk_redirect('notifications.php');
}

$items = trk_db_all(
    'SELECT * FROM trk_notifications WHERE recipient_id = ? ORDER BY id DESC LIMIT 100',
    [$user['id']]
);
$unread = count(array_filter($items, function ($n) { return $n['read_at'] === null; }));

trk_render_header('แจ้งเตือน', 'notifications');
?>
<div class="flex flex-wrap items-center justify-between gap-3">
  <h1 class="text-2xl font-semibold tracking-tight flex items-center gap-3"><span class="title-icon"><?= trk_icon('bell', 'h-5 w-5') ?></span>แจ้งเตือน</h1>
  <?php if ($unread): ?>
    <form method="post">
      <?= trk_csrf_field() ?>
      <button class="btn btn-yellow btn-sm"><?= trk_icon('check-check') ?>อ่านทั้งหมดแล้ว</button>
    </form>
  <?php endif; ?>
</div>

<?php if (!$items): ?>
  <p class="mt-4 text-sm text-ink/55 flex items-center gap-2"><?= trk_icon('inbox', 'h-4 w-4 text-ink/35') ?>ยังไม่มีการแจ้งเตือน</p>
<?php else: ?>
  <ul class="mt-4 space-y-2">
    <?php foreach ($items as $n): ?>
      <?php $isUnread = $n['read_at'] === null; ?>
      <li>
        <a href="<?= e(trk_url($n['link'])) ?>"
           class="card group flex items-center gap-3 px-4 py-3 transition hover:-translate-y-px hover:shadow-lift <?= $isUnread ? 'border-l-4 border-l-accent' : '' ?>">
          <span class="flex-1">
            <span class="block text-sm <?= $isUnread ? 'font-semibold text-ink' : 'text-ink/60' ?>"><?= e($n['title']) ?></span>
            <span class="text-xs text-ink/60">
              <?= e(trk_thai_date($n['created_at'], true)) ?> <?= e(substr($n['created_at'], 11, 5)) ?> น.
              <?php if ($isUnread): ?><span class="chip-orange ml-1">ใหม่</span><?php endif; ?>
            </span>
          </span>
          <span class="btn btn-soft-blue btn-sm shrink-0">เปิด<?= trk_icon('chevron-right') ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<?php
trk_render_footer();
