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
  <h1 class="text-xl font-semibold">แจ้งเตือน</h1>
  <?php if ($unread): ?>
    <form method="post">
      <?= trk_csrf_field() ?>
      <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-white">อ่านทั้งหมดแล้ว</button>
    </form>
  <?php endif; ?>
</div>

<?php if (!$items): ?>
  <p class="mt-4 text-sm text-slate-500">ยังไม่มีการแจ้งเตือน</p>
<?php else: ?>
  <ul class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <?php foreach ($items as $n): ?>
      <li>
        <a href="<?= e(trk_url($n['link'])) ?>" class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50">
          <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full <?= $n['read_at'] === null ? 'bg-blue-600' : 'bg-transparent' ?>"></span>
          <span class="flex-1">
            <span class="block text-sm <?= $n['read_at'] === null ? 'font-medium text-slate-900' : 'text-slate-600' ?>"><?= e($n['title']) ?></span>
            <span class="text-xs text-slate-400"><?= e(trk_thai_date($n['created_at'], true)) ?> <?= e(substr($n['created_at'], 11, 5)) ?> น.</span>
          </span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<?php
trk_render_footer();
