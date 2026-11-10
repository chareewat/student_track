<?php
/**
 * หน้าบทนำเสนอ (โหมดสาธิต): สลับผู้ใช้ด้วยคลิกเดียว รีเซ็ตข้อมูล และเดินตามฉากของทุก workflow
 */
require __DIR__ . '/boot.php';

$state = trk_demo_state();
$groups = $state ? trk_demo_scenes($state) : [];
$flash = $_SESSION['trk_demo_flash'] ?? null;
unset($_SESSION['trk_demo_flash']);

$current = null;
if (!empty($_SESSION['psn_mem_id'])) {
    $current = trk_db_one('SELECT psn_mem_username FROM psn_member WHERE psn_mem_id = ?', [(int) $_SESSION['psn_mem_id']]);
}
$currentUser = $current['psn_mem_username'] ?? null;

$sw = function (string $user, string $to = 'demo/index.php'): string {
    return 'switch.php?u=' . rawurlencode($user) . '&to=' . rawurlencode($to);
};
$roleChip = [
    'admin' => 'chip-red', 'executive' => 'chip-purple', 'academic' => 'chip-blue', 'affairs' => 'chip-green', 'teacher' => 'chip-sand',
];
$roleOf = function (string $user): array {
    if (isset(TRK_DEMO_USERS[$user])) {
        return TRK_DEMO_USERS[$user];
    }
    return ['ครูประจำวิชา', 'teacher', ''];
};
$sceneNo = 0;
$liveTotal = 0;
$liveReady = 0;
foreach ($groups as $g) {
    foreach ($g['scenes'] as $sc) {
        if ($sc[6]) {
            $liveTotal++;
            $liveReady += $sc[5] ? 1 : 0;
        }
    }
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>บทนำเสนอ · ระบบติดตามนักเรียน</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../student_affairs/student_track/assets/css/app.css?v=<?= (int) @filemtime(TRK_ROOT . '/assets/css/app.css') ?>">
</head>
<body class="min-h-screen pb-16">
  <header class="px-3 pt-3 sm:px-4">
    <div class="mx-auto max-w-5xl overflow-hidden rounded-3xl border border-sand/60 bg-white shadow-soft">
      <div class="h-1 bg-gradient-to-r from-navy via-navy to-accent" aria-hidden="true"></div>
      <div class="flex flex-wrap items-center gap-3 px-5 py-4">
        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-navy text-accent"><?= trk_icon('sparkles', 'h-5 w-5') ?></span>
        <div class="mr-auto">
          <div class="text-lg font-semibold text-navy">บทนำเสนอระบบติดตามนักเรียน</div>
          <div class="text-xs text-ink/55">โหมดสาธิตบนเครื่องนี้ · ข้อมูลจำลองทั้งหมด ไม่มีข้อมูลนักเรียนจริง</div>
        </div>
        <?php if ($currentUser): ?>
          <span class="chip-navy"><?= trk_icon('user', 'h-3.5 w-3.5') ?>กำลังใช้งานเป็น <?= e(trk_demo_member_name($currentUser)) ?></span>
          <a href="../student_affairs/student_track/index.php" class="btn btn-navy btn-sm"><?= trk_icon('home') ?>เปิดระบบ</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-5xl px-3 py-6 sm:px-4">
    <?php if ($flash): ?>
      <div class="<?= $flash[0] === 'error' ? 'alert-error' : 'alert-success' ?> mb-4">
        <?= trk_icon($flash[0] === 'error' ? 'alert' : 'check-circle', 'mt-0.5 h-4 w-4') ?><span><?= e($flash[1]) ?></span>
      </div>
    <?php endif; ?>

    <section class="card-pad flex flex-wrap items-center gap-4">
      <div class="mr-auto">
        <h2 class="text-base font-semibold flex items-center gap-2"><?= trk_icon('history', 'h-[18px] w-[18px] text-navy/70') ?>ข้อมูลสาธิต</h2>
        <?php if ($state): ?>
          <p class="hint mt-1">
            สร้างเมื่อ <?= e(trk_thai_date(substr($state['built_at'], 0, 10))) ?> <?= e(substr($state['built_at'], 11, 5)) ?> น. ·
            ฉากสาธิตสดพร้อม <b class="text-ink"><?= $liveReady ?>/<?= $liveTotal ?></b> ฉาก
          </p>
        <?php else: ?>
          <p class="hint mt-1">ยังไม่มีข้อมูลสาธิต กดปุ่มสร้างข้อมูลก่อนเริ่มนำเสนอ</p>
        <?php endif; ?>
      </div>
      <form method="post" action="reset.php" onsubmit="this.querySelector('button').disabled = true; this.querySelector('span').textContent = 'กำลังสร้างข้อมูล (ประมาณ 30 วินาที)…'">
        <input type="hidden" name="_csrf" value="<?= e(trk_csrf_token()) ?>">
        <button class="btn <?= $state ? 'btn-red' : 'btn-green' ?>"><?= trk_icon('history') ?><span><?= $state ? 'รีเซ็ตข้อมูลสาธิต' : 'สร้างข้อมูลสาธิต' ?></span></button>
      </form>
    </section>

    <section class="mt-6">
      <h2 class="text-base font-semibold flex items-center gap-2"><?= trk_icon('users', 'h-[18px] w-[18px] text-navy/70') ?>สลับผู้ใช้ (ไม่ต้องใส่รหัสผ่าน)</h2>
      <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach (TRK_DEMO_USERS as $user => [$label, $role, $hint]): ?>
          <div class="card flex flex-col gap-2 p-4 <?= $user === $currentUser ? 'ring-2 ring-accent' : '' ?>">
            <span class="<?= $roleChip[$role] ?> self-start"><?= e(TRK_ROLES[$role]) ?></span>
            <div class="font-semibold text-navy"><?= e($label) ?></div>
            <div class="text-xs text-ink/55"><?= e($hint) ?></div>
            <a href="<?= e($sw($user, 'student_affairs/student_track/index.php')) ?>" class="btn btn-soft-blue btn-sm mt-auto"><?= trk_icon('user') ?>เข้าเป็น <?= e($user) ?></a>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <?php if (!$state): ?>
      <p class="mt-8 card px-4 py-6 text-center text-sm text-ink/55">กด "สร้างข้อมูลสาธิต" ด้านบนเพื่อเตรียมฉากนำเสนอ</p>
    <?php endif; ?>

    <?php foreach ($groups as $g): ?>
      <section class="mt-10">
        <h2 class="text-lg font-semibold flex items-center gap-2"><span class="title-icon h-9 w-9 rounded-xl"><?= trk_icon($g['icon']) ?></span><?= e($g['title']) ?></h2>
        <?php if ($g['flow']): ?>
          <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs">
            <?php foreach ($g['flow'] as $i => $step): ?>
              <?php if ($i): ?><span class="text-accent"><?= trk_icon('chevron-right', 'h-4 w-4') ?></span><?php endif; ?>
              <span class="rounded-full border border-sand bg-white px-3 py-1 font-medium text-navy"><?= e($step) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="mt-4 space-y-3">
          <?php foreach ($g['scenes'] as $scene): ?>
            <?php
              [$key, $title, $user, $path, $points, $ready, $live] = $scene;
              $sceneNo++;
              [$roleLabel, $role] = $roleOf($user);
              $roleLabel = $scene[7] ?? $roleLabel;
            ?>
            <article class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-start" id="scene-<?= e($key) ?>">
              <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-navy text-sm font-bold text-white"><?= $sceneNo ?></span>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="font-semibold"><?= e($title) ?></h3>
                  <?php if ($live): ?>
                    <?php if ($ready): ?>
                      <span class="chip-green"><?= trk_icon('check-circle', 'h-3.5 w-3.5') ?>พร้อมสาธิตสด</span>
                    <?php else: ?>
                      <span class="chip-sand" title="กดรีเซ็ตข้อมูลสาธิตเพื่อทำฉากนี้ใหม่"><?= trk_icon('check-check', 'h-3.5 w-3.5') ?>ทำไปแล้ว</span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="chip-blue"><?= trk_icon('eye', 'h-3.5 w-3.5') ?>ดูอย่างเดียว</span>
                  <?php endif; ?>
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-ink/60">
                  <span class="<?= $roleChip[$role] ?>"><?= e(TRK_ROLES[$role]) ?></span>
                  <?= e(trk_demo_member_name($user)) ?> (<?= e($user) ?>) · <?= e($roleLabel) ?>
                </div>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-ink/80">
                  <?php foreach ($points as $p): ?><li><?= e($p) ?></li><?php endforeach; ?>
                </ul>
              </div>
              <a href="<?= e($sw($user, $path)) ?>" class="btn <?= $live && $ready ? 'btn-orange' : 'btn-navy' ?> btn-sm shrink-0 self-start"><?= trk_icon('send') ?>เข้าเป็น <?= e($user) ?> แล้วเปิดหน้านี้</a>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>

    <p class="mt-10 text-center text-xs text-ink/45">
      บัญชีจำลองทุกบัญชีใช้รหัสผ่าน 1234 ถ้าต้องการสาธิตหน้าเข้าสู่ระบบจริง · หน้านี้เปิดได้เฉพาะจากเครื่องนี้ และไม่อยู่ในชุดไฟล์ขึ้น production
    </p>
  </main>
</body>
</html>
