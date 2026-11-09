<?php /** @var string $title @var string $active @var array $user */ ?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(trk_csrf_token()) ?>">
  <meta name="theme-color" content="#F1EFEC">
  <title><?= e($title) ?> · ระบบติดตามนักเรียน</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(trk_url('assets/css/app.css')) ?>?v=<?= e((string) @filemtime(TRK_ROOT . '/assets/css/app.css')) ?>">
</head>
<body class="min-h-screen">
  <header class="sticky top-0 z-30 px-3 pt-3 sm:px-4">
    <div class="mx-auto max-w-6xl rounded-3xl border border-sand/60 bg-white/90 shadow-soft backdrop-blur">
      <div class="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3">
        <a href="<?= e(trk_url('index.php')) ?>" class="btn btn-white -ml-1 gap-2.5 px-2.5 py-1.5 shadow-none ring-0 hover:shadow-btn">
          <span class="grid h-9 w-9 place-items-center rounded-xl bg-navy text-base font-bold text-white" aria-hidden="true">ต</span>
          <span class="text-left leading-tight">
            <span class="block text-sm font-semibold text-navy">ระบบติดตามนักเรียน</span>
            <span class="block text-[11px] font-medium text-ink/50">ภาคเรียน <?= e(trk_semester_label()) ?></span>
          </span>
        </a>
        <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
          <span class="hidden text-sm font-medium text-ink/80 sm:inline"><?= e($user['name']) ?></span>
          <span class="flex flex-wrap gap-1"><?= trk_role_badges($user['roles']) ?></span>
          <a href="<?= e(trk_legacy_url('lobby.php')) ?>" class="btn btn-sand btn-sm">← หน้ารวมระบบ</a>
        </div>
      </div>
      <nav class="flex gap-2 overflow-x-auto border-t border-sand/50 px-4 py-2.5" aria-label="เมนูหลัก">
        <?php foreach (trk_menu() as $item): ?>
          <?php $isActive = $item['key'] === $active; $count = (int) ($item['count'] ?? 0); ?>
          <a href="<?= e($item['url']) ?>" class="<?= $isActive ? 'nav-btn-active' : 'nav-btn' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
            <?= e($item['label']) ?>
            <?php if ($count > 0): ?><span class="nav-count"><?= $count > 99 ? '99+' : $count ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
  </header>
  <main class="mx-auto max-w-6xl px-3 py-6 sm:px-4">
    <?php foreach (trk_take_flashes() as $flash): ?>
      <div class="<?= $flash['type'] === 'error' ? 'alert-error' : 'alert-success' ?> mb-4" role="status">
        <span aria-hidden="true"><?= $flash['type'] === 'error' ? '✕' : '✓' ?></span>
        <span><?= e($flash['message']) ?></span>
      </div>
    <?php endforeach; ?>
