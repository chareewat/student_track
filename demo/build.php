<?php
/**
 * สร้างข้อมูลสาธิตใหม่ทั้งหมด: ล้าง + seed ข้อมูลจำลอง แล้วเดินงานจริงผ่าน lib ของโมดูล
 * ให้ทุกเคสค้างอยู่ "ก่อนขั้นที่จะสาธิตสด" พอดี (ดูรายการฉากใน scenes.php)
 *
 *   php demo/build.php        (หรือกดปุ่ม "รีเซ็ตข้อมูลสาธิต" ในหน้า demo/)
 *
 * วันที่ในเรื่องราวย้อนหลังเป็นต้นเดือน พ.ย. 2569 ส่วนการเช็คชื่อ seed ถึงวันนี้
 */

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    require __DIR__ . '/boot.php';
    foreach (trk_demo_build() as $line) {
        echo $line, "\n";
    }
}

function trk_demo_build(): array
{
    @set_time_limit(300);
    $log = [];

    // ------------------------------------------------------------ 1. ข้อมูลจำลอง + ล้างงานติดตามเดิม
    if (!defined('TRK_DEMO_SEED')) {
        define('TRK_DEMO_SEED', true);
    }
    $GLOBALS['TRK_SEED_ARGS'] = ['--reset-tracking'];
    ob_start();
    (static function () {
        require TRK_LEGACY_ROOT . '/dev/seed.php';
    })();
    $log[] = trim((string) ob_get_clean());

    trk_db_exec('TRUNCATE TABLE trk_audit_log');
    trk_demo_clear_dir(TRK_ROOT . '/storage/uploads/followups');

    // ตั้งค่าตามค่ามาตรฐานของโรงเรียน
    $settings = [
        'kr1_threshold_1' => '10', 'kr1_threshold_2' => '20', 'kr1_threshold_3' => '30',
        'hours_per_credit' => '40', 'consecutive_absent_days' => '5',
        'overdue_kr2_days' => '7', 'overdue_followup_days' => '14',
    ];
    foreach ($settings as $k => $v) {
        trk_db_exec('INSERT INTO trk_settings (setting_key, setting_value, updated_by, updated_at) VALUES (?, ?, 1, NOW())
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$k, $v]);
    }
    // แนะแนวไม่มีหน่วยกิต → กำหนดชั่วโมงเรียนเอง 20 ชั่วโมง
    trk_db_exec('DELETE FROM trk_subject_hours');
    foreach (trk_db_all("SELECT vic_subject_id FROM vic_subject WHERE vic_subject_credit = 0") as $s) {
        trk_db_exec('INSERT INTO trk_subject_hours (vic_subject_id, hours, updated_by, updated_at) VALUES (?, 20, 1, NOW())',
            [(int) $s['vic_subject_id']]);
    }

    // สิทธิ์: ทุกคนเป็นครู + บทบาทพิเศษของบัญชีเจ้าหน้าที่
    trk_db_exec('DELETE FROM trk_user_roles');
    $special = ['admin' => 'admin', 'exec' => 'executive', 'acad' => 'academic', 'affairs' => 'affairs'];
    foreach (trk_db_all('SELECT psn_mem_id, psn_mem_username FROM psn_member') as $m) {
        $roles = ['teacher'];
        if (isset($special[$m['psn_mem_username']])) {
            $roles[] = $special[$m['psn_mem_username']];
        }
        foreach ($roles as $role) {
            trk_db_exec('INSERT INTO trk_user_roles (psn_mem_id, role, assigned_by, assigned_at) VALUES (?, ?, 1, NOW())',
                [(int) $m['psn_mem_id'], $role]);
        }
    }
    $_SESSION['psn_mem_id'] = 1; // บันทึก audit ระหว่างสร้างข้อมูลในนามผู้ดูแลระบบ

    // รูปตัวอย่างสำหรับอัปโหลดตอนสาธิตสด
    if (!is_dir(TRK_DEMO_ROOT . '/photos')) {
        mkdir(TRK_DEMO_ROOT . '/photos', 0775, true);
    }
    trk_demo_make_photo(TRK_DEMO_ROOT . '/photos/home-visit-1.jpg', 1);
    trk_demo_make_photo(TRK_DEMO_ROOT . '/photos/home-visit-2.jpg', 2);

    $state = ['built_at' => date('Y-m-d H:i:s')];

    // ------------------------------------------------------------ 2. เคส 1 ขาดเรียนรายวิชา
    // 1.1 รอครูประจำวิชาออก ขร.1: 900002 วิทยาศาสตร์ ม.1/1 (ไม่ต้องทำอะไร แค่หาครูผู้สอน)
    $w = trk_demo_waiting('900002', 'ว');
    $state['kr1_student'] = '900002';
    $state['kr1_subject'] = $w['vic_subject_code'] . ' ' . $w['vic_subject_name'];
    $state['kr1_teacher'] = trk_demo_username(trk_demo_teacher((int) $w['vic_subject_id'], (int) $w['std_classroom_id']));

    // 1.2 รอครูที่ปรึกษาออก ขร.2 + บค.14 (ม.1/1): 900003 คณิตศาสตร์ + ภาษาไทย (รวมใน ขร.2 ใบเดียวได้)
    $state['kr2_round'] = trk_demo_kr1('900003', 'ค', '2026-11-06');
    trk_demo_kr1('900003', 'ท', '2026-11-06');

    // 1.3 รอบันทึกผลติดตาม (ม.1/2): 900017 ภาษาไทย + สุขศึกษา ได้ ขร.2 ใบเดียว + บค.14 แล้ว
    $r = trk_demo_kr1('900017', 'ท', '2026-11-04');
    $r2 = trk_demo_kr1('900017', 'พ', '2026-11-04');
    trk_demo_kr2([$r, $r2], '2026-11-05');
    $state['followup1_round'] = $r;

    // 1.4 ปิดรอบแล้ว มี ขร.3 พร้อมรูปเยี่ยมบ้าน (ม.1/2): 900016 สุขศึกษา
    $r = trk_demo_kr1('900016', 'พ', '2026-11-02');
    trk_demo_kr2($r, '2026-11-03');
    $state['closed_kr3'] = trk_demo_followup($r, '2026-11-05', 'เยี่ยมบ้าน',
        'พบผู้ปกครอง นักเรียนช่วยงานที่บ้านช่วงเช้าทำให้มาไม่ทันคาบเรียน ผู้ปกครองรับปากจะดูแลให้มาเรียนตรงเวลา', true);

    // ------------------------------------------------------------ 3. เคส 2 ขาดเรียนติดต่อกัน
    // 2.1 ครั้งที่ 1 ปิดรอบแล้ว ครั้งที่ 2 รอออก ขร.5 (ม.1/1): 900003
    $r = trk_demo_kr5('900003', '2026-11-02');
    trk_demo_followup($r, '2026-11-02', 'ติดตามทางโทรศัพท์',
        'ผู้ปกครองแจ้งว่านักเรียนป่วยเป็นไข้ แต่ไม่ได้ส่งใบลา จะพามาเรียนเมื่อหายป่วย', false);
    $state['kr5_student'] = '900003';

    // 2.2 ได้ ขร.5 แล้ว รอเยี่ยมบ้าน (ม.1/2): 900016
    $state['followup2_round'] = trk_demo_kr5('900016', '2026-10-27');

    // ------------------------------------------------------------ 4. เคส 3 ติดตามครบ 3 ครั้งยังไม่มา
    // 3.1 ม.4/1 900048: ติดตามครบ 3 ครั้ง (ขร.5 + ขร.1 สองวิชา) รอครูที่ปรึกษาส่ง ขร.6
    $r = trk_demo_kr5('900048', '2026-11-02');
    trk_demo_followup($r, '2026-11-03', 'เยี่ยมบ้าน', 'ไม่พบนักเรียนที่บ้าน ญาติแจ้งว่าไปทำงานรับจ้างต่างอำเภอ ฝากเรื่องถึงผู้ปกครองแล้ว', true);
    $r = trk_demo_kr1('900048', 'ท', '2026-11-04');
    trk_demo_kr2($r, '2026-11-04');
    trk_demo_followup($r, '2026-11-05', 'ติดตามทางโทรศัพท์', 'ติดต่อผู้ปกครองได้ ผู้ปกครองไม่สามารถติดต่อนักเรียนได้เช่นกัน', false);
    $r = trk_demo_kr1('900048', 'ว', '2026-11-05');
    trk_demo_kr2($r, '2026-11-05');
    trk_demo_followup($r, '2026-11-06', 'เชิญผู้ปกครองมาโรงเรียน', 'ผู้ปกครองมาพบ แจ้งว่านักเรียนไม่ประสงค์จะกลับมาเรียน', false);
    $state['kr6_student'] = '900048';
    // 1.5 ม.ปลาย รอ ขร.2 (ไม่มี บค.14) และค้างเกินกำหนด: 900048 คณิตศาสตร์
    $state['kr2_hs_round'] = trk_demo_kr1('900048', 'ค', '2026-11-02');

    // 3.2 ม.6/1 900077: ส่ง ขร.6 ถึงเจ้าหน้าที่วิชาการแล้ว (และสุขศึกษาถึงเกณฑ์ครั้งที่ 2 แล้ว)
    $r = trk_demo_kr5('900077', '2026-10-26');
    trk_demo_followup($r, '2026-10-28', 'เยี่ยมบ้าน', 'บ้านปิด เพื่อนบ้านแจ้งว่าครอบครัวย้ายไปทำงานต่างจังหวัด', true);
    $r = trk_demo_kr1('900077', 'ท', '2026-10-30');
    trk_demo_kr2($r, '2026-11-02');
    trk_demo_followup($r, '2026-11-03', 'ติดตามทางโทรศัพท์', 'โทรศัพท์ผู้ปกครองไม่มีผู้รับสาย 3 ครั้ง', false);
    $r = trk_demo_kr1('900077', 'พ', '2026-11-02');
    trk_demo_kr2($r, '2026-11-03');
    trk_demo_followup($r, '2026-11-04', 'ติดตามทาง LINE', 'ส่งข้อความถึงผู้ปกครองทาง LINE ผู้ปกครองอ่านแล้วแต่ไม่ตอบกลับ', false);
    $state['kr6_doc'] = trk_demo_kr6('900077', '2026-11-09');

    // ------------------------------------------------------------ 5. ข้อมูลที่ใช้ในฉากภาพรวม
    $state['room_id'] = 1;
    $state['history_student'] = '900048';

    file_put_contents(TRK_DEMO_ROOT . '/state.json', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $log[] = 'สร้างข้อมูลสาธิตแล้ว: ' . (int) trk_db_value('SELECT COUNT(*) FROM trk_documents') . ' เอกสาร, '
        . (int) trk_db_value('SELECT COUNT(*) FROM trk_rounds') . ' รอบติดตาม';
    return $log;
}

// ---------------------------------------------------------------- ตัวช่วย

function trk_demo_student(string $code): array
{
    return trk_db_one('SELECT std_student_id AS id, std_classroom_id AS classroom_id FROM std_student WHERE std_student_code = ?', [$code]);
}

function trk_demo_advisor(int $classroomId): int
{
    return (int) trk_classroom_advisors($classroomId, trk_semester_id())[0]['psn_mem_id'];
}

function trk_demo_teacher(int $subjectId, int $classroomId): int
{
    return (int) trk_db_value(
        'SELECT psn_mem_id FROM t_teachtable WHERE vic_subject_id = ? AND std_classroom_id = ? AND std_semester_id = ? LIMIT 1',
        [$subjectId, $classroomId, trk_semester_id()]
    );
}

function trk_demo_username(int $memberId): string
{
    return (string) trk_db_value('SELECT psn_mem_username FROM psn_member WHERE psn_mem_id = ?', [$memberId]);
}

/** แถวรอออก ขร.1 ของนักเรียนในกลุ่มสาระที่ขึ้นต้นด้วย $prefix (ท ค ว ส พ ก) */
function trk_demo_waiting(string $code, string $prefix): array
{
    $st = trk_demo_student($code);
    foreach (trk_dashboard_case1_waiting_rows(trk_semester_id(), (int) $st['classroom_id']) as $row) {
        if ($row['std_student_code'] === $code && mb_substr($row['vic_subject_code'], 0, 1) === $prefix) {
            return $row;
        }
    }
    throw new RuntimeException("ข้อมูลจำลองเปลี่ยนไป: นักเรียน $code ยังไม่ถึงเกณฑ์ ขร.1 วิชากลุ่ม $prefix");
}

function trk_demo_doc_no(): string
{
    static $n = 1200;
    return (string) ++$n;
}

/** เลื่อนวันที่ของเอกสาร (และแจ้งเตือนที่ผูกกับเอกสาร) ไปเป็นวันในเรื่องราว */
function trk_demo_backdate_doc(int $docId, string $date, string $time): void
{
    $ts = "$date $time";
    trk_db_exec("UPDATE trk_documents SET issued_at = ?, data = JSON_SET(data, '$.issued_date', ?) WHERE id = ?", [$ts, $date, $docId]);
    trk_db_exec('UPDATE trk_notifications SET created_at = ? WHERE document_id = ?', [$ts, $docId]);
}

/** ครูประจำวิชาออก ขร.1 ให้นักเรียน 1 คน คืน id รอบติดตาม */
function trk_demo_kr1(string $code, string $prefix, string $date): int
{
    $w = trk_demo_waiting($code, $prefix);
    $teacher = trk_demo_teacher((int) $w['vic_subject_id'], (int) $w['std_classroom_id']);
    foreach (trk_case1_issue($teacher, (int) $w['vic_subject_id'], (int) $w['std_classroom_id'], [(int) $w['std_student_id']], '') as $docId) {
        trk_demo_backdate_doc($docId, $date, '10:15:00');
    }
    $roundId = (int) trk_db_value(
        "SELECT id FROM trk_rounds WHERE case_type = 1 AND std_student_id = ? AND vic_subject_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1",
        [(int) $w['std_student_id'], (int) $w['vic_subject_id']]
    );
    trk_db_exec('UPDATE trk_rounds SET opened_at = ? WHERE id = ?', ["$date 10:15:00", $roundId]);
    return $roundId;
}

/** ครูที่ปรึกษาออก ขร.2 (+ บค.14 ถ้า ม.ต้น) ใบเดียวให้รอบที่ระบุ (หลายรายวิชาได้) */
function trk_demo_kr2($roundIds, string $date): void
{
    $rounds = array_map('trk_round_get', (array) $roundIds);
    $round = $rounds[0];
    $guardian = trk_student_guardian_name((int) $round['std_student_id']) ?: 'ผู้ปกครอง';
    [$kr2, $bk14] = trk_issue_kr2($rounds, trk_demo_advisor((int) $round['std_classroom_id']), trk_demo_doc_no(),
        trk_requires_bk14($round) ? trk_demo_doc_no() : '', $guardian);
    trk_demo_backdate_doc($kr2, $date, '13:00:00');
    if ($bk14) {
        trk_demo_backdate_doc($bk14, $date, '13:00:00');
    }
}

/** ครูที่ปรึกษาบันทึกผลติดตาม + ออก ขร.3 คืน id ของ ขร.3 */
function trk_demo_followup(int $roundId, string $date, string $method, string $findings, bool $withPhoto): int
{
    $round = trk_round_get($roundId);
    $advisors = trk_classroom_advisors((int) $round['std_classroom_id'], (int) $round['semester_id']);
    $photos = [];
    if ($withPhoto) {
        $rel = 'followups/demo/' . bin2hex(random_bytes(8)) . '.jpg';
        if (!is_dir(TRK_ROOT . '/storage/uploads/followups/demo')) {
            mkdir(TRK_ROOT . '/storage/uploads/followups/demo', 0775, true);
        }
        trk_demo_make_photo(TRK_ROOT . '/storage/uploads/' . $rel, $roundId % 2 + 1);
        $photos[] = ['path' => $rel, 'name' => 'เยี่ยมบ้าน.jpg'];
    }
    $batch = trk_round_batch($round);
    $kr3 = trk_record_followup($batch, (int) $advisors[0]['psn_mem_id'], [
        'followup_date' => $date,
        'method' => $method,
        'visitors' => implode(', ', array_column($advisors, 'full_name')) . ', เจ้าหน้าที่ฝ่ายกิจการนักเรียน',
        'findings' => $findings,
        'doc_no' => '',
    ], $photos);
    $ts = "$date 15:30:00";
    trk_demo_backdate_doc($kr3, $date, '15:30:00');
    foreach ($batch as $b) {
        trk_db_exec('UPDATE trk_rounds SET closed_at = ? WHERE id = ?', [$ts, (int) $b['id']]);
    }
    trk_db_exec('UPDATE trk_followups SET created_at = ? WHERE round_id = ?', [$ts, $roundId]);
    return $kr3;
}

/** ครูที่ปรึกษาออก ขร.5 ให้ช่วงขาดแรกที่ยังไม่ออก คืน id รอบติดตาม */
function trk_demo_kr5(string $code, string $date): int
{
    $st = trk_demo_student($code);
    $created = trk_case2_issue(trk_demo_advisor((int) $st['classroom_id']), (int) $st['classroom_id'],
        [(int) $st['id'] => trk_demo_doc_no()], date('Y-m-d', strtotime("$date +7 days")));
    $docId = (int) $created[(int) $st['id']];
    trk_demo_backdate_doc($docId, $date, '11:00:00');
    $roundId = (int) trk_db_value('SELECT round_id FROM trk_document_rounds WHERE document_id = ?', [$docId]);
    trk_db_exec('UPDATE trk_rounds SET opened_at = ? WHERE id = ?', ["$date 11:00:00", $roundId]);
    return $roundId;
}

function trk_demo_kr6(string $code, string $date): int
{
    $st = trk_demo_student($code);
    $docId = trk_case3_issue(trk_demo_advisor((int) $st['classroom_id']), (int) $st['id'], trk_demo_doc_no());
    trk_demo_backdate_doc($docId, $date, '14:00:00');
    return $docId;
}

/** ภาพจำลองการเยี่ยมบ้าน (วาดด้วย GD ไม่มีบุคคลจริง) */
function trk_demo_make_photo(string $path, int $variant): void
{
    if (!function_exists('imagecreatetruecolor')) {
        return;
    }
    $w = 1200;
    $h = 800;
    $im = imagecreatetruecolor($w, $h);
    for ($y = 0; $y < $h; $y++) { // ท้องฟ้าไล่สี
        $t = $y / $h;
        $c = imagecolorallocate($im, (int) (150 + 80 * $t), (int) (200 + 40 * $t), 245);
        imageline($im, 0, $y, $w, $y, $c);
    }
    $ground = $variant === 1 ? [120, 170, 90] : [150, 165, 95];
    imagefilledrectangle($im, 0, 560, $w, $h, imagecolorallocate($im, ...$ground));
    $x0 = $variant === 1 ? 360 : 520;
    // บ้าน
    imagefilledrectangle($im, $x0, 340, $x0 + 420, 600, imagecolorallocate($im, 214, 201, 190));
    imagefilledpolygon($im, [$x0 - 40, 350, $x0 + 210, 190, $x0 + 460, 350], 3, imagecolorallocate($im, 160, 70, 40));
    imagefilledrectangle($im, $x0 + 170, 450, $x0 + 250, 600, imagecolorallocate($im, 110, 70, 40));
    imagefilledrectangle($im, $x0 + 50, 410, $x0 + 130, 480, imagecolorallocate($im, 240, 239, 236));
    imagefilledrectangle($im, $x0 + 290, 410, $x0 + 370, 480, imagecolorallocate($im, 240, 239, 236));
    // ต้นไม้
    $tx = $variant === 1 ? 180 : 1050;
    imagefilledrectangle($im, $tx - 15, 430, $tx + 15, 600, imagecolorallocate($im, 100, 70, 45));
    imagefilledellipse($im, $tx, 400, 200, 180, imagecolorallocate($im, 70, 140, 70));
    // ป้ายบอกว่าเป็นภาพจำลอง
    imagefilledrectangle($im, 0, $h - 90, $w, $h, imagecolorallocate($im, 18, 52, 88));
    // GD เปิดฟอนต์จาก path ที่มีอักษรไทยไม่ได้ (เช่น โฟลเดอร์ "ระบบติดตามนักเรียน") จึงคัดลอกไปไว้ใน temp ก่อน
    $font = sys_get_temp_dir() . '/trk-demo-sarabun-bold.ttf';
    if (!is_file($font)) {
        @copy(TRK_ROOT . '/assets/fonts/Sarabun-Bold.ttf', $font);
    }
    if (function_exists('imagettftext') && is_file($font)) {
        imagettftext($im, 30, 0, 40, $h - 32, imagecolorallocate($im, 255, 255, 255), $font, 'ภาพจำลองสำหรับสาธิตระบบ (ไม่ใช่ภาพจริง)');
    }
    imagejpeg($im, $path, 82);
    imagedestroy($im);
}

function trk_demo_clear_dir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
}
