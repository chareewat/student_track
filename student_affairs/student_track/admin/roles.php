<?php
require __DIR__ . '/../bootstrap.php';
trk_require_role('admin');

$me = trk_user();
$firstSetup = !trk_roles_configured();

$members = trk_db_all(
    "SELECT m.psn_mem_id, m.admin_type_id, m.psn_mem_username,
            CONCAT(IFNULL(t.psn_title_name, ''), m.psn_mem_name, ' ', m.psn_mem_surname) AS full_name,
            a.admin_type_name
     FROM psn_member m
     LEFT JOIN psn_title t ON t.psn_title_id = m.psn_title_id
     LEFT JOIN admin_type a ON a.admin_type_id = m.admin_type_id
     WHERE m.psn_memberstatus_id = 1
     ORDER BY m.psn_mem_name, m.psn_mem_surname"
);
$memberIds = array_map('intval', array_column($members, 'psn_mem_id'));

$current = [];
foreach (trk_db_all('SELECT psn_mem_id, role FROM trk_user_roles') as $row) {
    $current[(int) $row['psn_mem_id']][] = $row['role'];
}

if (trk_is_post()) {
    trk_csrf_check();
    $posted = isset($_POST['roles']) && is_array($_POST['roles']) ? $_POST['roles'] : [];
    $assignments = [];
    foreach ($memberIds as $id) {
        $roles = isset($posted[$id]) && is_array($posted[$id]) ? array_values(array_intersect($posted[$id], array_keys(TRK_ROLES))) : [];
        $assignments[$id] = $roles;
    }

    if (!in_array('admin', $assignments[$me['id']] ?? [], true)) {
        trk_flash('error', 'ไม่สามารถถอดสิทธิ์ Admin ของตนเองได้ ให้ Admin คนอื่นเป็นผู้ถอดแทน');
        trk_redirect('admin/roles.php');
    }

    $changes = [];
    foreach ($assignments as $id => $roles) {
        $before = $current[$id] ?? [];
        sort($before);
        sort($roles);
        if ($before !== $roles) {
            $changes[$id] = ['before' => $before, 'after' => $roles];
        }
    }

    try {
        if ($changes) {
            trk_save_roles(array_intersect_key($assignments, $changes), $me['id']);
            trk_audit('roles.update', $changes);
        }
        trk_flash('success', $changes ? 'บันทึกสิทธิ์แล้ว (' . count($changes) . ' คน)' : 'ไม่มีการเปลี่ยนแปลง');
    } catch (RuntimeException $ex) {
        trk_flash('error', $ex->getMessage());
    }
    trk_redirect('admin/roles.php');
}

// ค่าที่แสดงในฟอร์ม: ครั้งแรกใช้ค่าแนะนำทั้งหมด หลังจากนั้นใช้ค่าจริง (กด "แนะนำ" เพื่อเติมให้คนที่ยังไม่มีสิทธิ์)
$suggestMissing = isset($_GET['suggest']);
$formRoles = [];
foreach ($members as $m) {
    $id = (int) $m['psn_mem_id'];
    $suggested = trk_suggested_roles((int) $m['admin_type_id']);
    if ($firstSetup || ($suggestMissing && empty($current[$id]))) {
        $formRoles[$id] = $suggested;
    } else {
        $formRoles[$id] = $current[$id] ?? [];
    }
}
$withoutRole = count(array_filter($memberIds, function ($id) use ($current) {
    return empty($current[$id]);
}));

trk_render_header('จัดการสิทธิ์', 'roles');
?>
<div class="flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="text-2xl font-semibold tracking-tight flex items-center gap-3"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white text-navy shadow-soft ring-1 ring-sand/60"><?= trk_icon('shield', 'h-5 w-5') ?></span>จัดการสิทธิ์</h1>
    <p class="mt-1 text-sm text-ink/60">ครูประจำวิชาและครูที่ปรึกษาไม่ต้องตั้งที่นี่ ระบบอนุมานจากข้อมูลการสอนและการมอบหมายห้อง</p>
  </div>
  <input type="search" placeholder="ค้นหาชื่อ" data-filter-target="#roles-table"
         class="input w-full sm:w-64">
</div>

<?php if ($firstSetup): ?>
  <div class="mt-4 alert border-amber-200 bg-amber-50 text-amber-900">
    <b>ตั้งค่าครั้งแรก:</b> ระบบติ๊กสิทธิ์ให้ตามสิทธิ์ในระบบเดิม (ทุกคน = ครู, Super Admin = Admin, ฝ่ายบริหาร = ผู้บริหาร, วิชาการ = เจ้าหน้าที่วิชาการ, กิจการนักเรียน = เจ้าหน้าที่กิจการนักเรียน)
    ตรวจสอบแล้วกด "บันทึก"
  </div>
<?php elseif ($suggestMissing): ?>
  <div class="mt-4 alert border-amber-200 bg-amber-50 text-amber-900">
    ติ๊กสิทธิ์ที่แนะนำให้ <?= $withoutRole ?> คนที่ยังไม่มีสิทธิ์แล้ว (แถวสีเหลือง) ตรวจสอบแล้วกด "บันทึก"
  </div>
<?php elseif ($withoutRole > 0): ?>
  <div class="card mt-4 flex flex-wrap items-center gap-3 px-4 py-3 text-sm">
    <span>มีบุคลากร <?= $withoutRole ?> คนที่ยังไม่มีสิทธิ์ใช้งาน</span>
    <a href="<?= e(trk_url('admin/roles.php?suggest=1')) ?>" class="btn btn-yellow btn-sm"><?= trk_icon('sparkles') ?>ติ๊กสิทธิ์ที่แนะนำให้</a>
  </div>
<?php endif; ?>

<form method="post" class="mt-4">
  <?= trk_csrf_field() ?>
  <div class="table-wrap">
    <table id="roles-table" class="table">
      <thead>
        <tr>
          <th class="px-4 py-2 font-medium">บุคลากร</th>
          <th class="px-4 py-2 font-medium">สิทธิ์ในระบบเดิม</th>
          <?php foreach (TRK_ROLES as $label): ?>
            <th class="px-3 py-2 text-center font-medium"><?= e($label) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $m): ?>
          <?php
            $id = (int) $m['psn_mem_id'];
            $prefilled = !$firstSetup && $suggestMissing && empty($current[$id]);
          ?>
          <tr data-filter-text="<?= e($m['full_name'] . ' ' . $m['psn_mem_username']) ?>" data-dirty-track
              class="<?= $prefilled ? 'bg-amber-50' : '' ?>">
            <td class="px-4 py-2">
              <div><?= e($m['full_name']) ?></div>
              <div class="text-xs text-ink/40"><?= e($m['psn_mem_username']) ?></div>
            </td>
            <td class="px-4 py-2 text-ink/55"><?= e($m['admin_type_name'] ?: '-') ?></td>
            <?php foreach (array_keys(TRK_ROLES) as $role): ?>
              <td class="px-3 py-2 text-center">
                <input type="checkbox" name="roles[<?= $id ?>][]" value="<?= e($role) ?>"
                      
                       <?= in_array($role, $formRoles[$id], true) ? 'checked' : '' ?>
                       <?= $id === $me['id'] && $role === 'admin' ? 'title="ถอดสิทธิ์ Admin ของตนเองไม่ได้"' : '' ?>>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="sticky bottom-0 mt-4 flex justify-end border-t border-sand/60 bg-canvas/90 py-3 backdrop-blur">
    <button class="btn btn-green btn-lg"><?= trk_icon('save') ?>บันทึก</button>
  </div>
</form>
<?php
trk_render_footer();
