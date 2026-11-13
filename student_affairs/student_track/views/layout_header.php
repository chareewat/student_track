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
<?php
$menu = trk_menu();
$menuCount = 0;
foreach ($menu as $item) {
    $menuCount += (int) ($item['count'] ?? 0);
}
?>
<body class="min-h-screen">
  <a href="#main" class="skip-link">ข้ามไปเนื้อหาหลัก</a>
  <header class="sticky top-0 z-30 px-3 pt-3 sm:px-4">
    <div class="mx-auto max-w-6xl overflow-hidden rounded-3xl border border-sand/60 bg-white/90 shadow-soft backdrop-blur">
      <div class="h-1 bg-gradient-to-r from-navy via-navy to-accent" aria-hidden="true"></div>
      <div class="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3">
        <a href="<?= e(trk_url('index.php')) ?>" class="btn btn-white -ml-1 gap-2.5 px-2.5 py-1.5 shadow-none ring-0 hover:shadow-btn">
          <span class="grid h-9 w-9 place-items-center rounded-xl bg-navy text-accent"><?= trk_icon('cap', 'h-5 w-5') ?></span>
          <span class="text-left leading-tight">
            <span class="block text-sm font-semibold text-navy">ระบบติดตามนักเรียน</span>
            <span class="block text-[12px] font-medium text-ink/60">ภาคเรียน <?= e(trk_semester_label()) ?></span>
          </span>
        </a>
        <?php // จอเล็กกว่า lg (1024px): เมนูยุบเป็นปุ่ม hamburger (เปิด/ปิดด้วย assets/js/app.js) ?>
        <button type="button" class="btn btn-white btn-sm ml-auto lg:hidden" data-nav-toggle="#main-nav"
                aria-controls="main-nav" aria-expanded="false">
          <span data-nav-icon="open"><?= trk_icon('menu') ?></span>
          <span data-nav-icon="close" hidden><?= trk_icon('x') ?></span>
          เมนู
          <?php if ($menuCount > 0): ?><span class="nav-count"><?= $menuCount > 99 ? '99+' : $menuCount ?></span><?php endif; ?>
        </button>
        <div class="flex w-full flex-wrap items-center gap-2 lg:ml-auto lg:w-auto lg:justify-end">
          <span class="hidden text-sm font-medium text-ink/80 sm:inline"><?= e($user['name']) ?></span>
          <span class="flex flex-wrap gap-1"><?= trk_role_badges($user['roles']) ?></span>
          <a href="<?= e(trk_legacy_url('lobby.php')) ?>" class="btn btn-sand btn-sm"><?= trk_icon('grid') ?>หน้ารวมระบบ</a>
        </div>
      </div>
      <nav id="main-nav" class="flex hidden max-h-[70vh] flex-col gap-1.5 overflow-y-auto border-t border-sand/50 px-4 py-2.5
                                lg:flex lg:max-h-none lg:flex-row lg:flex-wrap lg:gap-2 lg:overflow-visible" aria-label="เมนูหลัก">
        <?php foreach ($menu as $item): ?>
          <?php $isActive = $item['key'] === $active; $count = (int) ($item['count'] ?? 0); ?>
          <a href="<?= e($item['url']) ?>" class="<?= $isActive ? 'nav-btn-active' : 'nav-btn' ?> justify-start lg:justify-center" <?= $isActive ? 'aria-current="page"' : '' ?>>
            <?= trk_icon($item['icon'] ?? 'chevron-right') ?>
            <?= e($item['label']) ?>
            <?php if ($count > 0): ?><span class="nav-count"><?= $count > 99 ? '99+' : $count ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
  </header>
  <main id="main" tabindex="-1" class="mx-auto max-w-6xl px-3 py-6 focus:outline-none sm:px-4">
    <?php foreach (trk_take_flashes() as $flash): ?>
      <div class="<?= $flash['type'] === 'error' ? 'alert-error' : 'alert-success' ?> mb-4" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
        <?= trk_icon($flash['type'] === 'error' ? 'alert' : 'check-circle', 'mt-0.5 h-4 w-4') ?>
        <span><?= e($flash['message']) ?></span>
      </div>
    <?php endforeach; ?>
