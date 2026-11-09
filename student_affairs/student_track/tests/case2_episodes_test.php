<?php
/**
 * ทดสอบอัลกอริทึมหาช่วงขาดติดต่อกัน (เคส 2)
 *   php student_affairs/student_track/tests/case2_episodes_test.php
 */
require __DIR__ . '/../lib/case2_episodes.php';

const ABSENT = 0, PRESENT = 1, LEAVE = 2, ACTIVITY = 3;

/** สร้างข้อมูลจาก ['2026-10-19' => สถานะ, ...] */
function recs(array $days): array
{
    $out = [];
    foreach ($days as $day => $status) {
        $out[] = ['day' => $day, 'status' => $status];
    }
    return $out;
}

$failures = 0;
function check(string $name, $expected, $actual): void
{
    global $failures;
    if ($expected === $actual) {
        echo "ok   $name\n";
    } else {
        $failures++;
        echo "FAIL $name\n  expected: " . json_encode($expected) . "\n  actual:   " . json_encode($actual) . "\n";
    }
}

$summary = function (array $episodes): array {
    return array_map(function ($e) {
        return [$e['from'], $e['to'], $e['days'], $e['ongoing']];
    }, $episodes);
};

check('ขาด 5 วันติดกัน ยังไม่กลับมา = 1 ครั้ง (ongoing)',
    [['2026-10-26', '2026-10-30', 5, true]],
    $summary(trk_case2_episodes(recs([
        '2026-10-23' => PRESENT,
        '2026-10-26' => ABSENT, '2026-10-27' => ABSENT, '2026-10-28' => ABSENT, '2026-10-29' => ABSENT, '2026-10-30' => ABSENT,
    ]), 5)));

check('ขาด 4 วัน ยังไม่ถึงเกณฑ์',
    [],
    $summary(trk_case2_episodes(recs([
        '2026-10-26' => ABSENT, '2026-10-27' => ABSENT, '2026-10-28' => ABSENT, '2026-10-29' => ABSENT, '2026-10-30' => PRESENT,
    ]), 5)));

check('ขาดต่อเนื่อง 10 วัน = ยังเป็นครั้งเดียว',
    [['2026-10-19', '2026-10-30', 10, true]],
    $summary(trk_case2_episodes(recs([
        '2026-10-19' => ABSENT, '2026-10-20' => ABSENT, '2026-10-21' => ABSENT, '2026-10-22' => ABSENT, '2026-10-23' => ABSENT,
        '2026-10-26' => ABSENT, '2026-10-27' => ABSENT, '2026-10-28' => ABSENT, '2026-10-29' => ABSENT, '2026-10-30' => ABSENT,
    ]), 5)));

check('กลับมาเรียนแล้วขาดใหม่อีก 5 วัน = 2 ครั้ง',
    [['2026-10-26', '2026-10-30', 5, false], ['2026-11-03', '2026-11-09', 5, true]],
    $summary(trk_case2_episodes(recs([
        '2026-10-26' => ABSENT, '2026-10-27' => ABSENT, '2026-10-28' => ABSENT, '2026-10-29' => ABSENT, '2026-10-30' => ABSENT,
        '2026-11-02' => PRESENT,
        '2026-11-03' => ABSENT, '2026-11-04' => ABSENT, '2026-11-05' => ABSENT, '2026-11-06' => ABSENT, '2026-11-09' => ABSENT,
    ]), 5)));

check('วันหยุด (ไม่มีข้อมูล) คั่นกลาง ไม่ตัดและไม่นับ',
    [['2026-10-19', '2026-10-26', 5, true]],
    $summary(trk_case2_episodes(recs([
        '2026-10-19' => ABSENT, '2026-10-20' => ABSENT, '2026-10-21' => ABSENT, '2026-10-22' => ABSENT,
        // 23 ต.ค. วันหยุด, 24–25 เสาร์อาทิตย์
        '2026-10-26' => ABSENT,
    ]), 5)));

check('ลา คั่นกลาง ตัดความต่อเนื่อง',
    [],
    $summary(trk_case2_episodes(recs([
        '2026-10-26' => ABSENT, '2026-10-27' => ABSENT, '2026-10-28' => ABSENT, '2026-10-29' => ABSENT,
        '2026-10-30' => LEAVE,
        '2026-11-02' => ABSENT, '2026-11-03' => ABSENT, '2026-11-04' => ABSENT,
    ]), 5)));

check('กิจกรรม คั่นกลาง ตัดความต่อเนื่อง',
    [],
    $summary(trk_case2_episodes(recs([
        '2026-10-26' => ABSENT, '2026-10-27' => ABSENT, '2026-10-28' => ACTIVITY,
        '2026-10-29' => ABSENT, '2026-10-30' => ABSENT, '2026-11-02' => ABSENT,
    ]), 5)));

check('ข้อมูลไม่เรียงวันที่ ก็ได้ผลเหมือนกัน',
    [['2026-10-26', '2026-10-30', 5, false]],
    $summary(trk_case2_episodes(recs([
        '2026-10-30' => ABSENT, '2026-11-02' => PRESENT, '2026-10-26' => ABSENT,
        '2026-10-28' => ABSENT, '2026-10-27' => ABSENT, '2026-10-29' => ABSENT,
    ]), 5)));

check('เกณฑ์ปรับได้ (3 วัน)',
    [['2026-10-26', '2026-10-28', 3, false]],
    $summary(trk_case2_episodes(recs([
        '2026-10-26' => ABSENT, '2026-10-27' => ABSENT, '2026-10-28' => ABSENT, '2026-10-29' => PRESENT,
    ]), 3)));

check('ไม่มีข้อมูล', [], trk_case2_episodes([], 5));

echo $failures ? "\n$failures test(s) failed\n" : "\nall tests passed\n";
exit($failures ? 1 : 0);
