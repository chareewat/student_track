<?php /** @var string $title @var string $active @var array $user */ ?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(trk_csrf_token()) ?>">
  <title><?= e($title) ?> · ระบบติดตามนักเรียน</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(trk_url('assets/css/app.css')) ?>?v=<?= e((string) @filemtime(TRK_ROOT . '/assets/css/app.css')) ?>">
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
  <header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3">
      <a href="<?= e(trk_url('index.php')) ?>" class="text-base font-semibold text-blue-700">ระบบติดตามนักเรียน</a>
      <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-600">ภาคเรียน <?= e(trk_semester_label()) ?></span>
      <div class="ml-auto flex flex-wrap items-center gap-2 text-sm">
        <span class="text-slate-700"><?= e($user['name']) ?></span>
        <?= trk_role_badges($user['roles']) ?>
        <a href="<?= e(trk_legacy_url('lobby.php')) ?>" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs text-slate-600 hover:bg-slate-50">← หน้ารวมระบบ</a>
      </div>
    </div>
    <nav class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4">
      <?php foreach (trk_menu() as $item): ?>
        <?php $isActive = $item['key'] === $active; ?>
        <a href="<?= e($item['url']) ?>"
           class="whitespace-nowrap border-b-2 px-3 py-2 text-sm <?= $isActive ? 'border-blue-600 font-medium text-blue-700' : 'border-transparent text-slate-600 hover:text-slate-900' ?>">
          <?= e($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </header>
  <main class="mx-auto max-w-6xl px-4 py-6">
    <?php foreach (trk_take_flashes() as $flash): ?>
      <div class="mb-4 rounded-lg border px-4 py-3 text-sm <?= $flash['type'] === 'error' ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800' ?>">
        <?= e($flash['message']) ?>
      </div>
    <?php endforeach; ?>
