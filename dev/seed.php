<?php
/**
 * สร้างข้อมูลจำลองสำหรับพัฒนาบนเครื่อง (ไม่มีข้อมูลจริง)
 *
 *   php dev/seed.php                    เช็คชื่อถึงวันนี้
 *   php dev/seed.php --until=2027-01-29 เช็คชื่อล่วงหน้า เพื่อให้นักเรียนข้ามเกณฑ์ครั้งที่ 2/3
 *
 * ทำงานเฉพาะเมื่อ $dbhost = localhost และจะล้างตารางข้อมูลจำลองก่อนทุกครั้ง
 * รหัสผ่านของทุกบัญชีคือ 1234 (ดูรายชื่อบัญชีใน dev/README.md)
 * เขียนให้รันได้บน PHP 7.4
 */

// เรียกจากโหมดสาธิต (demo/build.php) ได้ด้วย โดยกำหนด TRK_DEMO_SEED และ $GLOBALS['TRK_SEED_ARGS']
if (PHP_SAPI !== 'cli' && !defined('TRK_DEMO_SEED')) {
    exit("CLI only\n");
}

require __DIR__ . '/../global_config.php';
if ($dbhost !== 'localhost' && $dbhost !== '127.0.0.1') {
    exit("Refusing to seed: dbhost is not localhost\n");
}

$until = date('Y-m-d');
$resetTracking = false;
foreach ($GLOBALS['TRK_SEED_ARGS'] ?? ($argv ?? []) as $arg) {
    if (strpos($arg, '--until=') === 0) {
        $until = substr($arg, 8);
    }
    if ($arg === '--reset-tracking') {
        $resetTracking = true; // ล้างรอบติดตาม เอกสาร และแจ้งเตือนที่สร้างระหว่างทดสอบ (role และตั้งค่ายังอยู่)
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
$db->set_charset('utf8mb4');
mt_srand(42);

const SEMESTER_ID = 17;
const SEMESTER_START = '2026-10-19';
define('PASSWORD_MD5', md5('1234')); // ระบบเดิมเก็บรหัสผ่านแบบ md5

/** วันหยุดที่ไม่มีการเช็คชื่อเลย */
$holidays = ['2026-10-23', '2026-12-07', '2026-12-10', '2026-12-31', '2027-01-01'];
/** วันที่ครูที่ปรึกษาห้อง 2/1 ลืมเช็คชื่อรายวัน (แต่มีเช็คชื่อรายวิชา) */
$advisorForgot = ['2/1' => ['2026-11-04']];

function insert(mysqli $db, string $table, array $row): int
{
    $cols = '`' . implode('`,`', array_keys($row)) . '`';
    $marks = implode(',', array_fill(0, count($row), '?'));
    $stmt = $db->prepare("INSERT INTO `$table` ($cols) VALUES ($marks)");
    $types = '';
    foreach ($row as $v) {
        $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
    }
    $vals = array_values($row);
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();
    return (int) $db->insert_id;
}

// ---------------------------------------------------------------- ล้างข้อมูลเดิม
$db->query('SET FOREIGN_KEY_CHECKS=0');
foreach (['vic_attendance_subject', 'vic_attendance_teach', 'sch_attendance', 't_teachtable', 'vic_subject_open',
          'std_advisor', 'std_head_advisor', 'std_family', 'std_student', 'std_classroom', 'vic_subject', 'psn_member'] as $t) {
    $db->query("TRUNCATE TABLE `$t`");
}
if ($resetTracking) {
    foreach (['trk_rounds', 'trk_documents', 'trk_document_rounds', 'trk_notifications', 'trk_followups', 'trk_followup_photos'] as $t) {
        if ($db->query("SHOW TABLES LIKE '$t'")->num_rows) {
            $db->query("TRUNCATE TABLE `$t`");
        }
    }
}
$db->query('SET FOREIGN_KEY_CHECKS=1');

// ---------------------------------------------------------------- บุคลากร
$staff = [
    // username, title_id, ชื่อ, สกุล, sex, admin_type_id
    ['admin',   3, 'ผู้ดูแล',   'ระบบ',      'ชาย', 99],
    ['exec',    3, 'บริหาร',    'ทดสอบ',     'ชาย', 98],
    ['acad',    5, 'วิชาการ',   'ทดสอบ',     'หญิง', 1],
    ['affairs', 3, 'กิจการ',    'ทดสอบ',     'ชาย', 5],
    ['t01',     5, 'สมศรี',     'ใจดี',      'หญิง', 0],
    ['t02',     3, 'สมชาย',     'รักเรียน',   'ชาย', 0],
    ['t03',     4, 'มาลี',      'สอนดี',     'หญิง', 0],
    ['t04',     3, 'ประเสริฐ',  'มั่นคง',     'ชาย', 0],
    ['t05',     5, 'วันดี',     'ศรีสุข',     'หญิง', 0],
    ['t06',     3, 'อนุชา',     'พากเพียร',   'ชาย', 0],
    ['t07',     4, 'กานดา',     'ทองดี',     'หญิง', 0],
    ['t08',     3, 'วีระ',      'กล้าหาญ',    'ชาย', 0],
    ['t09',     5, 'นภา',       'แสงทอง',    'หญิง', 0],
    ['t10',     3, 'ธนา',       'เจริญผล',    'ชาย', 0],
];
$staffId = [];
foreach ($staff as $i => $s) {
    list($user, $title, $name, $surname, $sex, $adminType) = $s;
    $id = $i + 1;
    insert($db, 'psn_member', [
        'psn_mem_id' => $id,
        'psn_mem_name' => $name,
        'psn_mem_surname' => $surname,
        'psn_mem_eng_name' => 'Mock',
        'psn_mem_eng_surname' => 'User' . $id,
        'psn_mem_citizen_number' => sprintf('9%012d', $id),
        'psn_sex' => $sex,
        'psn_mem_birthday' => '1985-01-01',
        'psn_mem_start_work' => '2015-05-16',
        'psn_mem_end_work' => '2045-09-30',
        'psn_mem_id_work' => 'M' . $id,
        'psn_level_teach_id' => 1,
        'psn_mem_email' => $user . '@mock.local',
        'psn_mem_path_image' => '',
        'psn_mem_username' => $user,
        'psn_mem_password' => PASSWORD_MD5,
        'psn_title_id' => $title,
        'psn_title_en_id' => $sex === 'ชาย' ? 1 : 2,
        'psn_position_id' => $adminType === 98 ? 1 : 3,
        'psn_pos_lev_id' => 0,
        'psn_subject_id' => 1,
        'psn_permittion_id' => 3,
        'psn_memberstatus_id' => 1,
        'psn_mem_teach_status' => 0,
        'psn_mem_behavior_permit' => 0,
        'psn_mem_vicharkan_permit' => 0,
        'psn_mem_tkey' => '123456',
        'admin_type_id' => $adminType,
        'psn_mem_tel' => '0900000000',
        'psn_mem_is_admin' => 0,
    ]);
    $staffId[$user] = $id;
}
$titleName = [3 => 'นาย', 4 => 'นาง', 5 => 'นางสาว'];
$fullName = function (string $user) use ($staff, $titleName): string {
    foreach ($staff as $s) {
        if ($s[0] === $user) {
            return $titleName[$s[1]] . $s[2] . ' ' . $s[3];
        }
    }
    return '';
};

// ---------------------------------------------------------------- ห้องเรียน + ครูที่ปรึกษา
$rooms = [
    // id, level, room, advisors
    [1, '1', '1', ['t01', 't02']],
    [2, '1', '2', ['t03', 't04']],
    [3, '2', '1', ['t05']],
    [4, '4', '1', ['t06', 't07']],
    [5, '5', '1', ['t08', 't09']],
    [6, '6', '1', ['t10', 't01', 't02']],
];
foreach ($rooms as $r) {
    list($rid, $level, $room, $advisors) = $r;
    insert($db, 'std_classroom', [
        'std_classroom_id' => $rid,
        'std_classroom_level' => $level,
        'std_classroom_room' => $room,
        'std_classroom_status' => 1,
        'std_classroom_priority' => $rid,
        'std_classroom_degree' => (int) $level <= 3 ? 1 : 2,
    ]);
    foreach ($advisors as $a) {
        insert($db, 'std_advisor', [
            'std_semester_id' => SEMESTER_ID,
            'psn_mem_id' => $staffId[$a],
            'std_classroom_id' => $rid,
        ]);
    }
}

// ---------------------------------------------------------------- นักเรียน
$boys = ['ภูมิ', 'ธนกร', 'กิตติ', 'ณัฐวุฒิ', 'ปกรณ์', 'ศุภชัย', 'อนันต์', 'วรเมธ'];
$girls = ['พิมพ์ชนก', 'ศิริพร', 'กัญญา', 'ปวีณา', 'สุดารัตน์', 'อรอุมา', 'ชุติมา', 'มณีรัตน์'];
$surnames = ['ทดลองดี', 'สมมติชื่อ', 'จำลองศักดิ์', 'ตัวอย่างงาม', 'ไม่มีจริง', 'ข้อมูลเทียม', 'สาธิตผล', 'นามสมมุติ'];
$students = []; // code => [id, roomId, level, roomLabel]
$sid = 0;
foreach ($rooms as $r) {
    list($rid, $level, $room) = $r;
    for ($n = 1; $n <= 15; $n++) {
        $sid++;
        $isBoy = $n % 2 === 1;
        $junior = (int) $level <= 3;
        $code = sprintf('9%05d', $sid);
        $status = ($rid === 1 && $n === 15) ? 6 : 1; // ลาออก 1 คน เพื่อทดสอบว่าถูกกรองออก
        insert($db, 'std_student', [
            'std_student_id' => $sid,
            'std_student_code' => $code,
            'psn_title_id' => $isBoy ? ($junior ? 1 : 3) : ($junior ? 2 : 5),
            'psn_title_en_id' => $isBoy ? 4 : 2,
            'std_student_name' => ($isBoy ? $boys : $girls)[$sid % 8],
            'std_student_surname' => $surnames[($sid * 3) % 8],
            'std_status_id' => $status,
            'std_student_number' => $n,
            'std_student_pwd' => '123456',
            'std_semester_id' => SEMESTER_ID,
            'std_classroom_id' => $rid,
            'std_citizen_number' => sprintf('8%012d', $sid),
            'std_student_sex' => $isBoy ? 'ชาย' : 'หญิง',
            'std_student_eng_name' => 'Student',
            'std_student_eng_surname' => 'Mock' . $sid,
            'std_student_birthday' => (2026 - 12 - (int) $level) . '-06-15',
            'std_student_blood' => 'O',
            'std_student_origin' => 'ไทย',
            'std_student_nationalrity' => 'ไทย',
            'std_student_region' => 'พุทธ',
        ]);
        $students[$code] = ['id' => $sid, 'room' => $rid, 'level' => $level, 'label' => "$level/$room", 'no' => $n];

        // ผู้ปกครองจำลอง (นักเรียนเลขที่ 14 ไม่มีข้อมูลผู้ปกครอง เพื่อทดสอบช่องว่าง)
        if ($n !== 14) {
            $parentSurname = $surnames[($sid * 3) % 8];
            insert($db, 'std_family', [
                'std_student_id' => $sid,
                'std_old_brother' => 0, 'std_young_brother' => 0, 'std_old_sister' => 0, 'std_young_sister' => 0,
                'std_order_child' => 1,
                'std_family_status_id' => 1,
                'std_father_citizen_id' => '', 'std_father_title_id' => 3, 'std_father_name' => 'บิดา', 'std_father_surname' => $parentSurname,
                'std_father_salary' => 0.0, 'std_father_tel' => '',
                'std_mother_citizen_id' => '', 'std_mother_title_id' => 4, 'std_mother_name' => 'มารดา', 'std_mother_surname' => $parentSurname,
                'std_mother_salary' => 0.0, 'std_mother_tel' => '',
                'std_parent_about' => 'มารดา',
                'std_parent_citizen_id' => '', 'std_parent_title_id' => 4,
                'std_parent_name' => 'ผู้ปกครอง' . $n, 'std_parent_surname' => $parentSurname,
                'std_parent_salary' => 0.0, 'std_parent_tel' => '0800000000',
            ]);
        }
    }
}

// ---------------------------------------------------------------- รายวิชา + ตารางสอน
// group_id, ชื่อ, หน่วยกิต, คาบต่อสัปดาห์
$subjectTemplates = [
    ['ท', 1, 'ภาษาไทย', 1.5, 3],
    ['ค', 2, 'คณิตศาสตร์', 1.5, 3],
    ['ว', 3, 'วิทยาศาสตร์', 1.0, 2],
    ['ส', 4, 'สังคมศึกษา', 1.0, 2],
    ['พ', 5, 'สุขศึกษา', 0.5, 1],
    ['ก', 10, 'แนะแนว', 0.0, 1], // ไม่มีหน่วยกิต: admin ต้องกำหนดชั่วโมงเองในหน้าตั้งค่า
];
$teachersPool = ['t01', 't02', 't03', 't04', 't05', 't06', 't07', 't08', 't09', 't10'];
$subjectByLevel = []; // level => [ [id, code, name, credit, perWeek], ... ]
$vsid = 0;
foreach (['1', '2', '4', '5', '6'] as $level) {
    foreach ($subjectTemplates as $k => $tpl) {
        list($prefix, $group, $name, $credit, $perWeek) = $tpl;
        $vsid++;
        $code = sprintf('%s%d%d10%d', $prefix, (int) $level <= 3 ? 2 : 3, (int) $level <= 3 ? (int) $level : (int) $level - 3, 2);
        insert($db, 'vic_subject', [
            'vic_subject_id' => $vsid,
            'vic_subject_code' => $code,
            'vic_subject_name' => $name . ' ' . $level,
            'vic_subject_group_id' => $group,
            'vic_subject_level' => $level,
            'vic_subject_term' => 2,
            'vic_subject_credit' => $credit,
            'vic_subject_status' => 1,
            'vic_subject_type' => 0,
            'vic_subject_amount_limit' => 0,
            'vic_subject_update' => '2026-10-01 08:00:00',
        ]);
        $subjectByLevel[$level][] = ['id' => $vsid, 'code' => $code, 'credit' => $credit, 'perWeek' => $perWeek];
    }
}

// slots[roomId][weekday 1-5] = [period => [subject, teacher]]
$slots = [];
foreach ($rooms as $ri => $r) {
    list($rid, $level) = $r;
    $flat = [];
    foreach ($subjectByLevel[$level] as $j => $subj) {
        $teacher = $teachersPool[($ri * 2 + $j) % count($teachersPool)];
        for ($k = 0; $k < $subj['perWeek']; $k++) {
            $flat[] = [$subj, $teacher];
        }
    }
    // กระจายคาบลงวันจันทร์–ศุกร์ คาบที่ 1–4
    foreach ($flat as $idx => $pair) {
        $day = ($idx % 5) + 1;
        $period = intdiv($idx, 5) + 1;
        $slots[$rid][$day][$period] = $pair;
        insert($db, 't_teachtable', [
            'std_semester_id' => SEMESTER_ID,
            'psn_mem_id' => $staffId[$pair[1]],
            'vic_subject_id' => $pair[0]['id'],
            'std_classroom_id' => $rid,
            't_teachtable_period' => ($day - 1) * 9 + $period,
            't_teachtable_status' => 1,
            't_teachtable_date' => '2026-10-15 08:00:00',
        ]);
    }
}

// ---------------------------------------------------------------- สถานการณ์จำลอง
// รหัสนักเรียนห้อง 1/1 = 900001..900015, 1/2 = 900016.., 2/1 = 900031.., 4/1 = 900046.., 5/1 = 900061.., 6/1 = 900076..
$scenarios = [
    // เคส 1: ขาดคณิตบ่อย (ขาด/ลา/หนี ปนกัน) → ข้าม 10% ก่อน แล้ว 20/30% เมื่อ seed --until ไกลขึ้น
    '900001' => ['subjectAbsent' => ['ค' => 0.55]],
    // เคส 1: ขาดหลายวิชาพร้อมกัน
    '900002' => ['subjectAbsent' => ['ค' => 0.35, 'ว' => 0.45, 'พ' => 0.7]],
    // เคส 1 ระดับ ม.ปลาย (ไม่ต้องออก บค.14)
    '900047' => ['subjectAbsent' => ['ท' => 0.5]],
    // นักเรียนที่ลาออกแล้ว แต่มีประวัติขาด → ต้องไม่แสดงในระบบ
    '900015' => ['subjectAbsent' => ['ค' => 0.9], 'dailyAbsent' => [['2026-10-26', '2026-10-30']]],
    // เคส 2: ขาด 5 วัน → กลับมาเรียน 2 พ.ย. → ขาดใหม่อีก 5 วัน (ครั้งที่ 2)
    '900003' => ['dailyAbsent' => [['2026-10-26', '2026-10-30'], ['2026-11-03', '2026-11-09']]],
    // เคส 2: ขาดคร่อมวันหยุด 23 ต.ค. (วันหยุดไม่นับและไม่ตัด) → ครบ 5 วันในวันที่ 26
    '900016' => ['dailyAbsent' => [['2026-10-19', '2026-10-26']]],
    // ไม่เข้าเคส 2: ขาด 4 วัน → ลา 1 วัน → ขาด 3 วัน
    '900017' => ['dailyAbsent' => [['2026-10-26', '2026-10-29'], ['2026-11-02', '2026-11-04']], 'dailyLeave' => ['2026-10-30']],
    // เคส 2 ห้อง 2/1: ช่วงขาดมีวันที่ครูลืมเช็คชื่อ (4 พ.ย.) อยู่ตรงกลาง → ข้าม
    '900031' => ['dailyAbsent' => [['2026-11-02', '2026-11-03'], ['2026-11-05', '2026-11-09']]],
    // เคส 2 ม.4 ขาดยาวตั้งแต่ 27 ต.ค. ไม่กลับมาเลย (ต้นแบบของเคส 3 เมื่อติดตามครบ 3 ครั้ง)
    '900048' => ['dailyAbsent' => [['2026-10-27', '2027-12-31']]],
    // เคส 3 ม.6 ขาดยาวตั้งแต่วันเปิดภาคเรียน (โหมดสาธิตใช้เป็นตัวอย่างที่ส่ง ขร.6 แล้ว)
    '900077' => ['dailyAbsent' => [['2026-10-19', '2027-12-31']]],
];
$prefixOf = function (string $subjectCode): string {
    return mb_substr($subjectCode, 0, 1);
};
$inRanges = function (string $date, array $ranges): bool {
    foreach ($ranges as $rg) {
        if ($date >= $rg[0] && $date <= $rg[1]) {
            return true;
        }
    }
    return false;
};

// ---------------------------------------------------------------- เช็คชื่อ
$attDaily = 0;
$attTeach = 0;
$day = new DateTime(SEMESTER_START);
$end = new DateTime($until);
while ($day <= $end) {
    $date = $day->format('Y-m-d');
    $weekday = (int) $day->format('N');
    $day->modify('+1 day');
    if ($weekday > 5 || in_array($date, $holidays, true)) {
        continue;
    }

    // สถานะรายวัน: 0=ขาด 1=มา 2=ลา
    $daily = [];
    foreach ($students as $code => $st) {
        $sc = $scenarios[$code] ?? [];
        $status = 1;
        if (isset($sc['dailyAbsent']) && $inRanges($date, $sc['dailyAbsent'])) {
            $status = 0;
        } elseif (isset($sc['dailyLeave']) && in_array($date, $sc['dailyLeave'], true)) {
            $status = 2;
        } elseif (!$sc && mt_rand(1, 100) <= 2) {
            $status = mt_rand(0, 1) ? 0 : 2; // ขาด/ลาประปราย
        }
        $daily[$code] = $status;
    }

    foreach ($rooms as $r) {
        list($rid, $level, $room) = $r;
        $label = "$level/$room";
        $roomStudents = array_filter($students, function ($st) use ($rid) {
            return $st['room'] === $rid;
        });

        // เช็คชื่อรายวันของครูที่ปรึกษา
        if (!in_array($date, $advisorForgot[$label] ?? [], true)) {
            foreach ($roomStudents as $code => $st) {
                insert($db, 'sch_attendance', [
                    'sch_student_code' => (string) $code,
                    'std_student_id' => $st['id'],
                    'sch_att_day' => $date,
                    'sch_att_type_id' => $daily[$code],
                    'std_semester_id' => SEMESTER_ID,
                    'total_now_std' => count($roomStudents),
                ]);
                $attDaily++;
            }
        }

        // เช็คชื่อรายวิชา
        foreach ($slots[$rid][$weekday] ?? [] as $period => $pair) {
            list($subj, $teacher) = $pair;
            $statuses = [];
            foreach ($roomStudents as $code => $st) {
                $sc = $scenarios[$code] ?? [];
                $s = 1;
                if ($daily[$code] === 0) {
                    $s = 2;
                } elseif ($daily[$code] === 2) {
                    $s = 3;
                } elseif (isset($sc['subjectAbsent'][$prefixOf($subj['code'])])
                    && mt_rand(1, 100) <= (int) ($sc['subjectAbsent'][$prefixOf($subj['code'])] * 100)) {
                    $s = [2, 2, 3, 4][mt_rand(0, 3)]; // ขาด ขาด ลา หนี
                }
                $statuses[(string) $code] = $s;
            }
            $count = array_count_values($statuses);
            $teachId = insert($db, 'vic_attendance_teach', [
                'vic_att_period' => $period,
                'vic_subject_id' => $subj['id'],
                'vic_subject_code' => $subj['code'],
                'vic_att_date_teach' => $date,
                'psn_mem_id' => $staffId[$teacher],
                'vic_att_fullname' => $fullName($teacher),
                'vic_att_classroom' => $label,
                'std_semester_id' => SEMESTER_ID,
                'vic_att_amount' => count($statuses),
                'vic_att_detail' => '',
                'vic_att_come' => $count[1] ?? 0,
                'vic_att_absent' => $count[2] ?? 0,
                'vic_att_leave' => $count[3] ?? 0,
                'vic_att_escept' => $count[4] ?? 0,
                'vic_att_activity' => 0,
                'vic_att_online' => 0,
                'vic_att_offline' => 0,
                'vic_att_brief' => '',
            ]);
            $attTeach++;
            foreach ($statuses as $code => $s) {
                insert($db, 'vic_attendance_subject', [
                    'vic_att_teach_id' => $teachId,
                    'std_student_code' => $code,
                    'vic_att_subject_check_status' => $s,
                ]);
            }
        }
    }
}

printf("seeded: %d staff, %d rooms, %d students, %d subjects, %d daily rows, %d class periods (until %s)\n",
    count($staff), count($rooms), count($students), $vsid, $attDaily, $attTeach, $until);
