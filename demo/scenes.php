<?php
/**
 * บทนำเสนอ: ฉากทั้งหมดเรียงตาม workflow
 * แต่ละฉาก = เข้าระบบเป็นใคร + เปิดหน้าไหน + ประเด็นที่ควรพูด + ฉากนี้ยังพร้อมสาธิตสดหรือไม่
 *
 * ready: true = ข้อมูลยังค้างอยู่ก่อนขั้นนี้ (สาธิตสดได้), false = ทำไปแล้ว (กดรีเซ็ตก่อนถ้าจะทำซ้ำ), null = ฉากดูอย่างเดียว
 */

function trk_demo_state(): ?array
{
    $file = TRK_DEMO_ROOT . '/state.json';
    return is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
}

/** ชื่อ-สกุลของบุคลากรจาก username */
function trk_demo_member_name(string $username): string
{
    $row = trk_db_one(
        "SELECT CONCAT(IFNULL(t.psn_title_name, ''), m.psn_mem_name, ' ', m.psn_mem_surname) AS name
         FROM psn_member m LEFT JOIN psn_title t ON t.psn_title_id = m.psn_title_id WHERE m.psn_mem_username = ?",
        [$username]
    );
    return $row ? $row['name'] : $username;
}

function trk_demo_student_name(string $code): string
{
    $row = trk_db_one(
        "SELECT CONCAT(IFNULL(t.psn_title_name, ''), st.std_student_name, ' ', st.std_student_surname, ' ม.', c.std_classroom_level, '/', c.std_classroom_room) AS name
         FROM std_student st LEFT JOIN psn_title t ON t.psn_title_id = st.psn_title_id
         JOIN std_classroom c ON c.std_classroom_id = st.std_classroom_id WHERE st.std_student_code = ?",
        [$code]
    );
    return $row ? $row['name'] : $code;
}

function trk_demo_round_stage(int $roundId): ?string
{
    $round = $roundId ? trk_round_get($roundId) : null;
    return $round ? $round['stage'] : null;
}

function trk_demo_round_student(int $roundId): string
{
    $round = $roundId ? trk_round_get($roundId) : null;
    if (!$round) {
        return '-';
    }
    $what = (int) $round['case_type'] === 1 ? $round['vic_subject_code'] . ' ' . $round['vic_subject_name'] : 'ขาดติดต่อกัน ครั้งที่ ' . (int) $round['round_no'];
    return $round['full_name'] . ' ' . trk_classroom_label($round) . ' (' . $what . ')';
}

function trk_demo_student_id(string $code): int
{
    return (int) trk_db_value('SELECT std_student_id FROM std_student WHERE std_student_code = ?', [$code]);
}

/** กลุ่มฉาก [['title', 'icon', 'flow' => [...], 'scenes' => [[key, title, user, path, points[], ready, live, ป้ายหน้าที่ (ถ้าต่างจากปกติ)]]]] */
function trk_demo_scenes(array $s): array
{
    $m = 'student_affairs/student_track/';
    $sem = trk_semester_id();

    // ความพร้อมของฉากสาธิตสด
    $kr1Ready = false;
    $kr1Student = trk_demo_student_id($s['kr1_student']);
    foreach (trk_dashboard_case1_waiting_rows($sem, 1) as $row) {
        if ((int) $row['std_student_id'] === $kr1Student && strpos($s['kr1_subject'], $row['vic_subject_code']) === 0) {
            $kr1Ready = true;
        }
    }
    $kr5Ready = false;
    foreach (trk_case2_candidates(1, $sem) as $c) {
        if ($c['std_student_code'] === $s['kr5_student'] && $c['eligible']) {
            $kr5Ready = true;
        }
    }
    $kr6Ready = false;
    foreach (trk_case3_candidates(4, $sem) as $c) {
        if ($c['std_student_code'] === $s['kr6_student'] && $c['eligible']) {
            $kr6Ready = true;
        }
    }
    $kr6Docs = (int) trk_db_value("SELECT COUNT(*) FROM trk_documents WHERE doc_type = 'KR6' AND status = 'active' AND semester_id = ?", [$sem]);

    return [
        [
            'title' => 'เริ่มต้น: ผู้ดูแลระบบ',
            'icon' => 'shield',
            'flow' => [],
            'scenes' => [
                ['roles', 'กำหนดสิทธิ์ผู้ใช้ครั้งแรก', 'admin', $m . 'admin/roles.php', [
                    'ระบบแนะนำสิทธิ์จากประเภทผู้ใช้ในระบบเดิม (ผู้บริหาร วิชาการ กิจการนักเรียน) ให้ admin ติ๊กยืนยัน',
                    'ครูทุกคนได้สิทธิ์ "ครู" หน้าที่ครูประจำวิชา/ครูที่ปรึกษาดึงจากตารางสอนและข้อมูลครูที่ปรึกษาเดิมอัตโนมัติ',
                ], null, false],
                ['settings', 'ตั้งค่าเกณฑ์และผู้ลงนาม', 'admin', $m . 'admin/settings.php', [
                    'เกณฑ์ ขร.1 ครั้งที่ 1/2/3 = 10/20/30% ของเวลาเรียน (หน่วยกิต × 40 ชั่วโมง)',
                    'ขาดติดต่อกัน 5 วัน = ออก ขร.5 · วิชาที่ไม่มีหน่วยกิต (แนะแนว) กำหนดชั่วโมงเองได้',
                    'ชื่อผู้อำนวยการ/รองฯ เลขที่หนังสือ และกำหนดวันงานค้างเกินกำหนด',
                ], null, false],
                ['system', 'ตรวจความพร้อมของระบบ', 'admin', $m . 'admin/system.php', [
                    'ตรวจ PHP, ฐานข้อมูล, ฟอนต์ไทย, การสร้าง PDF/Word และโฟลเดอร์อัปโหลดรูปในหน้าเดียว',
                ], null, false],
            ],
        ],
        [
            'title' => 'เคส 1: ขาดเรียนรายวิชาถึงเกณฑ์ 10/20/30%',
            'icon' => 'book',
            'flow' => ['ครูประจำวิชาออก ขร.1', 'ครูที่ปรึกษาออก ขร.2 (+ บค.14 ม.ต้น)', 'ลงพื้นที่ติดตาม + ขร.3', 'ปิดรอบ'],
            'scenes' => [
                ['kr1', 'ครูประจำวิชาออก ขร.1', $s['kr1_teacher'], $m . 'case1/index.php', [
                    'ระบบคำนวณจากการเช็คชื่อรายวิชาเดิม: ขาด + ลา + หนี เทียบกับเวลาเรียนทั้งหมด',
                    'ให้ออก ขร.1 ของ ' . trk_demo_student_name($s['kr1_student']) . ' วิชา ' . $s['kr1_subject'],
                    'ออกแล้วครูที่ปรึกษาได้รับแจ้งเตือนทันที · เอกสารพิมพ์ PDF/Word ได้ มีรายการวันที่ขาดแนบ',
                ], $kr1Ready, true, 'ครูประจำวิชา ' . $s['kr1_subject']],
                ['kr2', 'ครูที่ปรึกษารับแจ้งเตือน แล้วออก ขร.2 + บค.14', 't01', $m . 'advisor/index.php', [
                    'เปิดกระดิ่งแจ้งเตือน → เห็น ขร.1 ที่ครูประจำวิชาส่งมา',
                    'ออก ขร.2 แจ้งผู้ปกครองของ ' . trk_demo_round_student((int) $s['kr2_round']),
                    'ม.1–3 ระบบออก บค.14 คู่กันให้อัตโนมัติ ต้องใส่เลขหนังสือออกจากงานสารบรรณ',
                ], trk_demo_round_stage((int) $s['kr2_round']) === 'need_kr2', true],
                ['kr2hs', 'ขร.2 ระดับ ม.ปลาย (ไม่มี บค.14) และงานค้างเกินกำหนด', 't06', $m . 'advisor/index.php', [
                    trk_demo_round_student((int) $s['kr2_hs_round']) . ' รอ ขร.2 มาเกิน 7 วัน (ขึ้นในงานค้างของหน้าภาพรวม)',
                    'ม.4–6 ออกเฉพาะ ขร.2',
                ], trk_demo_round_stage((int) $s['kr2_hs_round']) === 'need_kr2', true],
                ['followup1', 'บันทึกผลติดตาม → ออก ขร.3 → ปิดรอบ', 't03', $m . 'advisor/index.php', [
                    'บันทึกผลของ ' . trk_demo_round_student((int) $s['followup1_round']),
                    'เลือกวิธีติดตาม (โทรศัพท์ ไม่ต้องแนบรูป) ใส่ผลที่พบ ระบบออก ขร.3 และปิดรอบให้',
                    'ถ้าเลือก "เยี่ยมบ้าน" ต้องแนบรูป ใช้รูปตัวอย่างในโฟลเดอร์ demo/photos ได้',
                ], trk_demo_round_stage((int) $s['followup1_round']) === 'need_followup', true],
                ['kr3', 'ดูเอกสาร ขร.3 ที่ปิดรอบแล้ว (พร้อมรูปเยี่ยมบ้าน)', 't03', $m . 'documents/view.php?id=' . (int) $s['closed_kr3'], [
                    'เอกสารทุกฉบับดาวน์โหลดได้ทั้ง PDF และ Word ตัวเลขไทยตามแบบฟอร์มราชการ',
                    'ดูลำดับเอกสารของรอบ: ขร.1 → ขร.2 + บค.14 → ขร.3',
                    'ยกเลิกเอกสารได้ทีละขั้นจากขั้นล่าสุด (ผู้ออกยกเลิกได้ก่อนมีขั้นถัดไป หลังจากนั้นเฉพาะ admin)',
                ], null, false],
            ],
        ],
        [
            'title' => 'เคส 2: ขาดเรียนติดต่อกัน 5 วันขึ้นไป',
            'icon' => 'calendar-x',
            'flow' => ['ครูที่ปรึกษาออก ขร.5', 'เยี่ยมบ้าน + ขร.3', 'ปิดรอบ'],
            'scenes' => [
                ['kr5', 'ออก ขร.5 ครั้งที่ 2 (กลับมาเรียนแล้วขาดใหม่อีก 5 วัน)', 't01', $m . 'advisor/case2.php', [
                    trk_demo_student_name($s['kr5_student']) . ' ครั้งที่ 1 ปิดรอบแล้ว กลับมาเรียนแล้วขาดใหม่อีก 5 วัน',
                    'นับจากการเช็คชื่อรายวันของครูที่ปรึกษา วันที่ไม่มีการเช็คชื่อ (วันหยุด) ไม่นับและไม่ตัดช่วง',
                    'ใส่เลขหนังสือออกและวันที่ให้ผู้ปกครองติดต่อกลับ',
                ], $kr5Ready, true],
                ['followup2', 'เยี่ยมบ้านพร้อมแนบรูปจากมือถือ', 't03', $m . 'advisor/index.php', [
                    'บันทึกผลเยี่ยมบ้านของ ' . trk_demo_round_student((int) $s['followup2_round']),
                    'แนบรูปได้สูงสุด 4 รูป ถ่ายจากมือถือได้ทันที (ระบบย่อรูปและตัดข้อมูล GPS ออก)',
                    'รูปตัวอย่าง: demo/photos/home-visit-1.jpg',
                ], trk_demo_round_stage((int) $s['followup2_round']) === 'need_followup', true],
            ],
        ],
        [
            'title' => 'เคส 3: ติดตามครบ 3 ครั้งแล้วยังไม่มาเรียน',
            'icon' => 'user-x',
            'flow' => ['ติดตามครบ 3 ครั้ง (เคส 1 + เคส 2)', 'ยังไม่มาเรียน', 'ครูที่ปรึกษายืนยันส่ง ขร.6', 'เจ้าหน้าที่วิชาการรับเรื่อง'],
            'scenes' => [
                ['kr6', 'ครูที่ปรึกษายืนยันส่ง ขร.6 เสนอจำหน่าย', 't06', $m . 'advisor/case3.php', [
                    trk_demo_student_name($s['kr6_student']) . ' ติดตามแล้ว 3 ครั้ง (ขร.5 + ขร.1 สองวิชา) หลังจากนั้นไม่มาเรียนเลย',
                    'ระบบตรวจให้: ต้องมีการเช็คชื่อหลังติดตามครั้งล่าสุด และไม่มีวันไหนที่มาโรงเรียน',
                    'ครูต้องติ๊กยืนยันก่อนส่ง ส่งแล้วเจ้าหน้าที่วิชาการได้รับแจ้งเตือน',
                ], $kr6Ready, true],
                ['academic', 'เจ้าหน้าที่วิชาการรับ ขร.6', 'acad', $m . 'academic/kr6.php', [
                    'ขร.6 ที่ส่งมาแล้ว ' . $kr6Docs . ' ฉบับ มีสรุปการติดตามทั้ง 3 ครั้งในเอกสาร',
                    'ดำเนินการจำหน่ายต่อในระบบงานทะเบียนเดิม (ระบบนี้ไม่มีขั้นอนุมัติ)',
                ], null, false],
            ],
        ],
        [
            'title' => 'ภาพรวมสำหรับผู้บริหารและเจ้าหน้าที่',
            'icon' => 'dashboard',
            'flow' => [],
            'scenes' => [
                ['exec', 'ผู้บริหารดูภาพรวมทั้งโรงเรียน', 'exec', $m . 'dashboard/index.php', [
                    'ตัวเลขทุกขั้นของทุกห้อง งานค้างเกินกำหนด และเตือนเมื่อเลยสัปดาห์ที่ 8',
                    'ผู้บริหารดูภาพรวมได้ แต่ไม่เห็นรายชื่อรายห้อง',
                ], null, false],
                ['room', 'เจ้าหน้าที่กิจการนักเรียนดูรายชื่อที่รอออกเอกสารรายห้อง', 'affairs', $m . 'dashboard/room.php?id=' . (int) $s['room_id'], [
                    'รายชื่อรอ ขร.1, ขร.2, ขร.5 ของห้อง พร้อมผู้ที่ต้องออก ใช้ประสานครูได้ทันที',
                    'admin และเจ้าหน้าที่วิชาการก็เปิดได้',
                ], null, false],
                ['history', 'ค้นหาประวัติการติดตามรายนักเรียน', 'acad', $m . 'dashboard/student.php?id=' . trk_demo_student_id($s['history_student']), [
                    'ไทม์ไลน์ทุกรอบ ทุกเอกสาร และผลการติดตามของ ' . trk_demo_student_name($s['history_student']),
                    'ดาวน์โหลดสรุปเป็น CSV ได้จากหน้าภาพรวม',
                ], null, false],
            ],
        ],
    ];
}
