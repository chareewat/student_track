<?php
/**
 * ตรวจความพร้อมของ server (Admin) — เปิดหน้านี้หลังอัปโหลดขึ้น production
 */
require __DIR__ . '/../bootstrap.php';
trk_require_role('admin');

$checks = [];
$add = function (string $group, string $name, bool $ok, string $detail, bool $warnOnly = false) use (&$checks) {
    $checks[$group][] = ['name' => $name, 'status' => $ok ? 'ok' : ($warnOnly ? 'warn' : 'fail'), 'detail' => $detail];
};

$toBytes = function (string $v): int {
    $v = trim($v);
    $n = (int) $v;
    switch (strtolower(substr($v, -1))) {
        case 'g': return $n * 1024 * 1024 * 1024;
        case 'm': return $n * 1024 * 1024;
        case 'k': return $n * 1024;
    }
    return $n;
};

// ---------------------------------------------------------------- PHP
$add('PHP', 'เวอร์ชัน PHP', version_compare(PHP_VERSION, '7.4.0', '>='), PHP_VERSION . ' (ต้องการ 7.4 ขึ้นไป)');
foreach (['mysqli' => true, 'mbstring' => true, 'gd' => true, 'json' => true, 'zip' => true, 'dom' => true, 'xml' => true, 'fileinfo' => false] as $ext => $required) {
    $add('PHP', "extension $ext", extension_loaded($ext), extension_loaded($ext) ? 'เปิดอยู่' : 'ไม่ได้เปิด', !$required);
}
$add('PHP', 'mysqlnd (get_result)', method_exists('mysqli_stmt', 'get_result'),
    method_exists('mysqli_stmt', 'get_result') ? 'มี' : 'ไม่มี: ระบบใช้ทางสำรองแทน (ทำงานได้แต่ช้ากว่าเล็กน้อย)', true);
$add('PHP', 'GD รองรับ JPEG/PNG', function_exists('imagecreatefromjpeg') && function_exists('imagecreatefrompng'),
    function_exists('imagecreatefromjpeg') ? 'รองรับ' : 'ไม่รองรับ: รูปจะถูกเก็บโดยไม่ย่อขนาด');
$upload = ini_get('upload_max_filesize');
$post = ini_get('post_max_size');
$add('PHP', 'upload_max_filesize', $toBytes($upload) >= 10 * 1024 * 1024, "$upload (แนะนำอย่างน้อย 10M ต่อรูป)", true);
$add('PHP', 'post_max_size', $toBytes($post) >= 20 * 1024 * 1024, "$post (แนะนำอย่างน้อย 20M สำหรับ 4 รูป)", true);
$add('PHP', 'max_file_uploads', (int) ini_get('max_file_uploads') >= 4, ini_get('max_file_uploads') . ' (ต้องอย่างน้อย 4)');
$add('PHP', 'memory_limit', ini_get('memory_limit') === '-1' || $toBytes(ini_get('memory_limit')) >= 128 * 1024 * 1024,
    ini_get('memory_limit') . ' (mPDF แนะนำอย่างน้อย 128M)', true);

// ---------------------------------------------------------------- ไฟล์
$add('ไฟล์', 'vendor/autoload.php (mPDF)', is_file(TRK_ROOT . '/vendor/autoload.php'),
    is_file(TRK_ROOT . '/vendor/autoload.php') ? 'มี' : 'ไม่พบ: ต้องอัปโหลดโฟลเดอร์ vendor/ ด้วย');
foreach (['storage/tmp', 'storage/uploads'] as $dir) {
    $path = TRK_ROOT . '/' . $dir;
    $add('ไฟล์', "เขียนได้: $dir", is_dir($path) && is_writable($path), is_dir($path) ? (is_writable($path) ? 'เขียนได้' : 'เขียนไม่ได้ (chmod 775)') : 'ไม่พบโฟลเดอร์');
}
foreach (['Sarabun-Regular.ttf', 'Sarabun-Bold.ttf'] as $font) {
    $add('ไฟล์', "ฟอนต์ $font", is_file(TRK_ROOT . '/assets/fonts/' . $font), is_file(TRK_ROOT . '/assets/fonts/' . $font) ? 'มี' : 'ไม่พบ');
}
$add('ไฟล์', 'assets/css/app.css', is_file(TRK_ROOT . '/assets/css/app.css'), is_file(TRK_ROOT . '/assets/css/app.css') ? 'มี' : 'ไม่พบ');

// ---------------------------------------------------------------- ฐานข้อมูล
$db = trk_db();
$add('ฐานข้อมูล', 'เวอร์ชัน', true, $db->server_info);
$mode = (string) trk_db_value('SELECT @@SESSION.sql_mode');
$add('ฐานข้อมูล', 'sql_mode', true, $mode !== '' ? $mode : '(ว่าง)');
$migrationFiles = array_map('basename', glob(TRK_ROOT . '/migrations/*.sql') ?: []);
sort($migrationFiles);
$tableExists = function (string $table): bool {
    return (bool) trk_db_value('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1', [$table]);
};
$applied = [];
if ($tableExists('trk_migrations')) {
    $applied = array_column(trk_db_all('SELECT name FROM trk_migrations'), 'name');
}
foreach ($migrationFiles as $file) {
    $add('ฐานข้อมูล', "migration $file", in_array($file, $applied, true), in_array($file, $applied, true) ? 'รันแล้ว' : 'ยังไม่ได้รัน');
}
foreach (['trk_user_roles', 'trk_settings', 'trk_rounds', 'trk_documents', 'trk_followups', 'trk_notifications'] as $table) {
    $add('ฐานข้อมูล', "ตาราง $table", $tableExists($table), $tableExists($table) ? 'มี' : 'ไม่พบ');
}
$indexes = [
    'vic_attendance_teach' => 'trk_idx_sem_subject_room',
    'vic_attendance_subject' => 'trk_idx_student_teach',
    'sch_attendance' => 'trk_idx_sem_student_day',
];
foreach ($indexes as $table => $index) {
    $has = (bool) trk_db_value("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1", [$table, $index]);
    $add('ฐานข้อมูล', "index $table.$index", $has, $has ? 'มี' : 'ไม่พบ: รัน 002_legacy_attendance_indexes.sql', true);
}
foreach (['std_family' => 'ชื่อผู้ปกครองใน ขร.2/ขร.5', 'sch_attendance' => 'เช็คชื่อรายวัน', 'vic_attendance_subject' => 'เช็คชื่อรายวิชา'] as $table => $use) {
    $add('ฐานข้อมูล', "ตารางเดิม $table", $tableExists($table), $use);
}

// ---------------------------------------------------------------- การตั้งค่า
$add('ตั้งค่า', 'ภาคเรียนปัจจุบัน', true, trk_semester_label() . ' (' . trk_thai_date(trk_current_semester()['std_semester_beginday'], true)
    . ' – ' . trk_thai_date(trk_current_semester()['std_semester_endday'], true) . ')');
$add('ตั้งค่า', 'ตั้ง role ครั้งแรกแล้ว', trk_roles_configured(), trk_roles_configured() ? 'แล้ว' : 'ยัง: ไปที่ "จัดการสิทธิ์" แล้วกดบันทึก');
foreach (['director_name' => 'ชื่อผู้อำนวยการ', 'deputy_academic_name' => 'ชื่อรองผู้อำนวยการฝ่ายวิชาการ', 'academic_phone' => 'โทรศัพท์กลุ่มบริหารวิชาการ'] as $key => $label) {
    $add('ตั้งค่า', $label, trk_setting($key) !== '', trk_setting($key) !== '' ? trk_setting($key) : 'ยังไม่ได้กรอก (จะพิมพ์เป็นจุดไข่ปลา)', true);
}
$noHours = (int) trk_db_value(
    'SELECT COUNT(DISTINCT s.vic_subject_id) FROM vic_subject s
     JOIN vic_attendance_teach t ON t.vic_subject_id = s.vic_subject_id AND t.std_semester_id = ?
     LEFT JOIN trk_subject_hours h ON h.vic_subject_id = s.vic_subject_id
     WHERE s.vic_subject_credit = 0 AND h.vic_subject_id IS NULL',
    [trk_semester_id()]
);
$add('ตั้งค่า', 'วิชาไม่มีหน่วยกิตที่ยังไม่กำหนดชั่วโมง', $noHours === 0, $noHours . ' วิชา' . ($noHours ? ' (ไม่ถูกนับในเคส 1 จนกว่าจะกำหนด)' : ''), true);

// ---------------------------------------------------------------- ทดสอบสร้าง PDF
$pdfOk = false;
$pdfDetail = '';
if (is_file(TRK_ROOT . '/vendor/autoload.php')) {
    try {
        require_once TRK_ROOT . '/lib/pdf.php';
        $mpdf = trk_pdf_new('test');
        $mpdf->WriteHTML('<p>ทดสอบภาษาไทย ผู้ปกครอง ☑</p>');
        $bytes = strlen($mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN));
        $pdfOk = $bytes > 1000;
        $pdfDetail = 'สร้างได้ (' . number_format($bytes) . ' bytes)';
    } catch (Throwable $ex) {
        $pdfDetail = 'ผิดพลาด: ' . $ex->getMessage();
    }
} else {
    $pdfDetail = 'ข้าม (ไม่มี vendor/)';
}
$add('PDF', 'สร้าง PDF ด้วย mPDF', $pdfOk, $pdfDetail);

// ---------------------------------------------------------------- ทดสอบสร้าง Word
$docxOk = false;
$docxDetail = '';
if (is_file(TRK_ROOT . '/vendor/autoload.php') && class_exists('ZipArchive')) {
    try {
        require_once TRK_ROOT . '/lib/docx.php';
        $w = new TrkDocx('test');
        $w->p('ทดสอบภาษาไทย ผู้ปกครอง');
        $tmp = tempnam(TRK_ROOT . '/storage/tmp', 'chk');
        $w->save($tmp);
        $bytes = filesize($tmp);
        @unlink($tmp);
        $docxOk = $bytes > 1000;
        $docxDetail = 'สร้างได้ (' . number_format($bytes) . ' bytes)';
    } catch (Throwable $ex) {
        $docxDetail = 'ผิดพลาด: ' . $ex->getMessage();
    }
} else {
    $docxDetail = 'ข้าม (ไม่มี vendor/ หรือ extension zip)';
}
$add('PDF', 'สร้าง Word (.docx) ด้วย PhpWord', $docxOk, $docxDetail);

// ---------------------------------------------------------------- วัดเวลาหน้าภาพรวม (กดปุ่มเท่านั้น เพราะรัน query หนัก)
$profile = null;
if (($_GET['profile'] ?? '') === 'dashboard') {
    require_once TRK_ROOT . '/lib/dashboard.php';
    @set_time_limit(120);
    $GLOBALS['trk_db_profile'] = true;
    $GLOBALS['trk_db_stats'] = ['count' => 0, 'ms' => 0.0, 'slowest' => []];
    $semesterId = trk_semester_id();
    $steps = [];
    $measure = function (string $label, callable $fn) use (&$steps) {
        $before = $GLOBALS['trk_db_stats'];
        $t = microtime(true);
        $note = $fn();
        $after = $GLOBALS['trk_db_stats'];
        $steps[] = [
            'label' => $label,
            'ms' => (microtime(true) - $t) * 1000,
            'db_ms' => $after['ms'] - $before['ms'],
            'queries' => $after['count'] - $before['count'],
            'note' => (string) $note,
        ];
    };
    $rooms = [];
    $measure('รายชื่อห้อง + ครูที่ปรึกษา', function () use (&$rooms, $semesterId) {
        $rooms = trk_dashboard_classrooms($semesterId);
        return count($rooms) . ' ห้อง';
    });
    $measure('ตัวเลข "รอ ขร.1" (นักเรียน × วิชาที่ถึงเกณฑ์)', function () use ($semesterId) {
        return count(trk_dashboard_case1_waiting_rows($semesterId)) . ' รายการ';
    });
    $measure('ตัวเลข "รอ ขร.5" (ไล่ทีละห้อง)', function () use ($rooms, $semesterId) {
        $n = 0;
        foreach ($rooms as $room) {
            foreach (trk_case2_candidates((int) $room['std_classroom_id'], $semesterId) as $c) {
                $n += $c['eligible'] ? 1 : 0;
            }
        }
        return $n . ' คน';
    });
    $measure('ตารางรายห้อง (รอบติดตามทุกห้อง)', function () use ($rooms, $semesterId) {
        return count(trk_rounds_for_classrooms(array_column($rooms, 'std_classroom_id'), $semesterId)) . ' รอบ';
    });
    $measure('งานค้างเกินกำหนด', function () use ($semesterId) {
        return count(trk_dashboard_overdue($semesterId)) . ' รายการ';
    });
    $measure('เมนูด้านบน (ทุกหน้า)', function () {
        return count(trk_menu()) . ' เมนู';
    });
    $slowest = $GLOBALS['trk_db_stats']['slowest'];
    uasort($slowest, function ($a, $b) {
        return $b['ms'] <=> $a['ms'];
    });
    $volume = [];
    foreach ([
        'คาบที่เช็คชื่อรายวิชา (ภาคเรียนนี้)' => ['SELECT COUNT(*) FROM vic_attendance_teach WHERE std_semester_id = ?', [$semesterId]],
        'แถวเช็คชื่อรายวิชา (ภาคเรียนนี้)' => ['SELECT COUNT(*) FROM vic_attendance_subject a JOIN vic_attendance_teach t ON t.vic_att_teach_id = a.vic_att_teach_id WHERE t.std_semester_id = ?', [$semesterId]],
        'แถวเช็คชื่อรายวัน (ภาคเรียนนี้)' => ['SELECT COUNT(*) FROM sch_attendance WHERE std_semester_id = ?', [$semesterId]],
        'นักเรียนที่กำลังเรียน' => ['SELECT COUNT(*) FROM std_student WHERE std_status_id = 1', []],
        'รอบติดตาม (ภาคเรียนนี้)' => ['SELECT COUNT(*) FROM trk_rounds WHERE semester_id = ?', [$semesterId]],
    ] as $label => [$sql, $params]) {
        $volume[$label] = (int) trk_db_value($sql, $params);
    }
    $GLOBALS['trk_db_profile'] = false;
    $profile = ['steps' => $steps, 'slowest' => array_slice($slowest, 0, 8, true), 'volume' => $volume];
}

$summary = ['ok' => 0, 'warn' => 0, 'fail' => 0];
foreach ($checks as $items) {
    foreach ($items as $c) {
        $summary[$c['status']]++;
    }
}
$badge = [
    'ok' => ['ผ่าน', 'bg-emerald-100 text-emerald-800', 'check-circle'],
    'warn' => ['ควรแก้', 'bg-amber-100 text-amber-900', 'alert'],
    'fail' => ['ไม่ผ่าน', 'bg-rose-100 text-rose-800', 'x-circle'],
];

trk_render_header('ตรวจสอบระบบ', 'system');
?>
<h1 class="text-2xl font-semibold tracking-tight flex items-center gap-3"><span class="title-icon"><?= trk_icon('activity', 'h-5 w-5') ?></span>ตรวจสอบความพร้อมของระบบ</h1>
<p class="mt-1 text-sm text-ink/60">
  ผ่าน <?= $summary['ok'] ?> · ควรแก้ <?= $summary['warn'] ?> · ไม่ผ่าน <?= $summary['fail'] ?>
  <?php if ($summary['fail'] === 0): ?> — <span class="chip-green">พร้อมใช้งาน</span><?php endif; ?>
</p>

<section class="mt-6 card p-4">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h2 class="text-base font-semibold flex items-center gap-2"><?= trk_icon('clock', 'h-[18px] w-[18px] text-navy/70') ?>วัดเวลาหน้าภาพรวม</h2>
      <p class="mt-0.5 text-xs text-ink/60">คำนวณทุกส่วนของหน้าภาพรวมแยกกันแล้วจับเวลา ใช้เวลาเท่ากับการเปิดหน้าภาพรวม 1 ครั้ง ไม่แก้ข้อมูลใด ๆ</p>
    </div>
    <a href="<?= e(trk_url('admin/system.php?profile=dashboard#profile')) ?>" class="btn btn-blue btn-sm"><?= trk_icon('activity') ?>เริ่มวัด</a>
  </div>
  <?php if ($profile): ?>
    <?php $totalMs = array_sum(array_column($profile['steps'], 'ms')); ?>
    <div id="profile" class="mt-4 scroll-mt-40">
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>ส่วนของหน้า</th><th class="text-right">เวลา</th><th class="text-right">ในฐานข้อมูล</th><th class="text-right">query</th><th>ผลลัพธ์</th></tr></thead>
          <tbody>
            <?php foreach ($profile['steps'] as $st): ?>
              <tr>
                <td><?= e($st['label']) ?></td>
                <td class="text-right tabular-nums font-medium"><?= number_format($st['ms'] / 1000, 2) ?> วิ</td>
                <td class="text-right tabular-nums text-ink/60"><?= number_format($st['db_ms'] / 1000, 2) ?> วิ</td>
                <td class="text-right tabular-nums text-ink/60"><?= number_format($st['queries']) ?></td>
                <td class="text-ink/60"><?= e($st['note']) ?></td>
              </tr>
            <?php endforeach; ?>
            <tr class="font-semibold"><td>รวม</td><td class="text-right tabular-nums"><?= number_format($totalMs / 1000, 2) ?> วิ</td><td colspan="3"></td></tr>
          </tbody>
        </table>
      </div>

      <h3 class="mt-4 text-sm font-semibold">query ที่ใช้เวลารวมมากที่สุด</h3>
      <div class="mt-2 table-wrap">
        <table class="table">
          <thead><tr><th class="text-right">เวลารวม</th><th class="text-right">ครั้ง</th><th class="text-right">นานสุด/ครั้ง</th><th class="text-right">แถว</th><th>SQL (ย่อ)</th></tr></thead>
          <tbody>
            <?php foreach ($profile['slowest'] as $sql => $q): ?>
              <tr>
                <td class="text-right tabular-nums font-medium"><?= number_format($q['ms'] / 1000, 2) ?> วิ</td>
                <td class="text-right tabular-nums"><?= number_format($q['calls']) ?></td>
                <td class="text-right tabular-nums text-ink/60"><?= number_format($q['max_ms'] / 1000, 3) ?> วิ</td>
                <td class="text-right tabular-nums text-ink/60"><?= number_format($q['rows']) ?></td>
                <td class="font-mono text-xs text-ink/70"><?= e(mb_substr($sql, 0, 160)) ?>…</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <h3 class="mt-4 text-sm font-semibold">ปริมาณข้อมูล</h3>
      <dl class="mt-2 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-[18rem_1fr]">
        <?php foreach ($profile['volume'] as $label => $n): ?>
          <dt class="text-ink/60"><?= e($label) ?></dt><dd class="tabular-nums"><?= number_format($n) ?></dd>
        <?php endforeach; ?>
      </dl>
      <p class="mt-3 text-xs text-ink/60">ถ่ายภาพหน้าจอส่วนนี้ส่งให้ผู้พัฒนา (ไม่มีข้อมูลส่วนตัวของนักเรียน)</p>
    </div>
  <?php endif; ?>
</section>

<?php foreach ($checks as $group => $items): ?>
  <section class="mt-6">
    <h2 class="text-base font-semibold"><?= e($group) ?></h2>
    <div class="mt-2 table-wrap">
      <table class="table">
        <tbody>
          <?php foreach ($items as $c): ?>
            <?php [$label, $cls, $icon] = $badge[$c['status']]; ?>
            <tr>
              <td class="w-28 px-4 py-2">
                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium <?= $cls ?>">
                  <?= trk_icon($icon, 'h-3.5 w-3.5') ?><?= $label ?>
                </span>
              </td>
              <td class="px-4 py-2 font-medium"><?= e($c['name']) ?></td>
              <td class="px-4 py-2 text-ink/60"><?= e($c['detail']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endforeach; ?>
<?php
trk_render_footer();
