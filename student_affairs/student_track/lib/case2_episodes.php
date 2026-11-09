<?php
/**
 * เคส 2: หาช่วงขาดติดต่อกัน (ฟังก์ชันล้วน ไม่แตะฐานข้อมูล เพื่อให้ทดสอบได้)
 *
 * กติกา (CONTEXT.md):
 *  - นับเฉพาะสถานะ "ขาด" จากการเช็คชื่อรายวันของครูที่ปรึกษา
 *  - สถานะอื่นทุกชนิด (มา, ลา, กิจกรรม, ...) ทำให้ความต่อเนื่องขาดลง
 *  - วันที่ไม่มีการเช็คชื่อ (วันหยุด, ครูไม่ได้เช็ค) ไม่อยู่ในข้อมูล จึงถูกข้ามไปเอง ไม่นับและไม่ตัด
 *  - ช่วงขาดหนึ่งช่วงที่ยาวถึงเกณฑ์ = 1 ครั้ง ขาดต่อเนื่องจาก 5 เป็น 10 วันในช่วงเดียวกันไม่นับเป็นครั้งใหม่
 *  - ครั้งถัดไปนับเมื่อนักเรียนกลับมาเรียน (มีสถานะอื่น) แล้วขาดใหม่จนถึงเกณฑ์อีกครั้ง
 */

const TRK_DAILY_ABSENT = 0; // sch_attendance_type: 0 = ไม่มาโรงเรียน (ขาด)

/**
 * @param array $records รายการเช็คชื่อของนักเรียน 1 คน [['day' => 'Y-m-d', 'status' => int], ...] (เรียงหรือไม่ก็ได้)
 * @param int   $minDays เกณฑ์จำนวนวันขาดติดต่อกัน
 * @return array ช่วงที่ถึงเกณฑ์ [['from' => วันแรก, 'to' => วันขาดล่าสุด, 'days' => จำนวนวัน, 'ongoing' => ยังขาดอยู่ถึงข้อมูลล่าสุดหรือไม่], ...]
 */
function trk_case2_episodes(array $records, int $minDays): array
{
    usort($records, function ($a, $b) {
        return strcmp($a['day'], $b['day']);
    });

    $episodes = [];
    $current = null;
    foreach ($records as $r) {
        if ((int) $r['status'] === TRK_DAILY_ABSENT) {
            if ($current === null) {
                $current = ['from' => $r['day'], 'to' => $r['day'], 'days' => 0];
            }
            $current['to'] = $r['day'];
            $current['days']++;
        } else {
            if ($current !== null && $current['days'] >= $minDays) {
                $episodes[] = $current + ['ongoing' => false];
            }
            $current = null;
        }
    }
    if ($current !== null && $current['days'] >= $minDays) {
        $episodes[] = $current + ['ongoing' => true];
    }
    return $episodes;
}
