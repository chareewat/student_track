<?php
/**
 * รายชื่อนักเรียนที่รอออก ขร.1, ขร.2, ขร.5 และเอกสารที่ออกแล้วของห้องที่เลือก (admin, เจ้าหน้าที่วิชาการ, เจ้าหน้าที่กิจการนักเรียน)
 * การออกเอกสารยังเป็นหน้าที่ของครูประจำวิชา/ครูที่ปรึกษา ส่วนการยกเลิกทำที่หน้าเอกสาร (ปุ่ม "ยกเลิก" พาไปที่กล่องยกเลิก)
 */
require __DIR__ . '/../bootstrap.php';
require_once TRK_ROOT . '/lib/dashboard.php';
trk_require_role(...TRK_ROLES_ROOM_DETAIL);

$semester = trk_current_semester();
$semesterId = trk_semester_id();
$roomId = (int) ($_GET['id'] ?? 0);
$room = null;
foreach (trk_dashboard_classrooms($semesterId) as $r) {
    if ((int) $r['std_classroom_id'] === $roomId) {
        $room = $r;
    }
}
if ($room === null) {
    http_response_code(404);
    trk_render_message('ไม่พบห้องเรียน', 'ห้องเรียนนี้ไม่มีอยู่หรือปิดใช้งานแล้ว');
    exit;
}

$kr1 = trk_dashboard_case1_waiting_rows($semesterId, $roomId);
$kr2 = array_values(array_filter(trk_rounds_for_classrooms([$roomId], $semesterId), function ($r) {
    return $r['stage'] === 'need_kr2';
}));
$kr5 = array_values(array_filter(trk_case2_candidates($roomId, $semesterId), function ($c) {
    return $c['eligible'];
}));

// เอกสารที่ออกแล้วของห้องในภาคเรียนนี้ (ใหม่สุดก่อน) รวมที่ยกเลิกแล้ว
$issued = trk_db_all(
    "SELECT d.id, d.doc_type, d.round_no, d.doc_no, d.status, d.issued_at, d.data,
            CONCAT(m.psn_mem_name, ' ', m.psn_mem_surname) AS issued_by_name,
            (SELECT COUNT(*) FROM trk_document_rounds dr WHERE dr.document_id = d.id) AS round_count
     FROM trk_documents d JOIN psn_member m ON m.psn_mem_id = d.issued_by
     WHERE d.std_classroom_id = ? AND d.semester_id = ?
     ORDER BY d.issued_at DESC, d.id DESC",
    [$roomId, $semesterId]
);
$issuedActive = count(array_filter($issued, function ($d) {
    return $d['status'] === 'active';
}));

$kr2Days = (int) trk_setting('overdue_kr2_days');
$today = new DateTime(date('Y-m-d'));
$roomLabel = trk_classroom_label($room);
$backUrl = trk_url('dashboard/index.php?level=' . rawurlencode((string) $room['std_classroom_level']));

/** ส่วนหัวของแต่ละตาราง */
$sectionHead = function (string $id, string $icon, string $title, string $who, int $count) {
    ?>
    <div id="<?= e($id) ?>" class="flex flex-wrap items-center gap-2 scroll-mt-40">
      <h2 class="text-base font-semibold flex items-center gap-2"><?= trk_icon($icon, 'h-[18px] w-[18px] text-navy/70') ?><?= e($title) ?></h2>
      <span class="<?= $count ? 'chip-orange' : 'chip-sand' ?>"><?= $count ?> รายการ</span>
      <span class="text-xs text-ink/55">ผู้ออก: <?= e($who) ?></span>
    </div>
    <?php
};
$empty = function (string $text) {
    ?>
    <p class="mt-3 card px-4 py-4 text-sm text-ink/55 flex items-center gap-2"><?= trk_icon('check-circle', 'h-4 w-4 text-emerald-600') ?><?= e($text) ?></p>
    <?php
};

trk_render_header('รอออกเอกสาร ' . $roomLabel, 'dashboard');
?>
<div class="flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="text-2xl font-semibold tracking-tight flex items-center gap-3"><span class="title-icon"><?= trk_icon('users', 'h-5 w-5') ?></span>รอออกเอกสาร <?= e($roomLabel) ?></h1>
    <p class="mt-1 text-sm text-ink/60">
      ครูที่ปรึกษา: <?= e($room['advisors'] ?: '-') ?> · ภาคเรียน <?= e(trk_semester_label()) ?> · ข้อมูล ณ <?= e(trk_thai_date(date('Y-m-d'))) ?> <?= date('H:i') ?> น.
    </p>
  </div>
  <div class="flex flex-wrap gap-2">
    <a href="#kr1" class="btn btn-soft-navy btn-sm"><?= trk_icon('book') ?>ขร.1 (<?= count($kr1) ?>)</a>
    <a href="#kr2" class="btn btn-soft-navy btn-sm"><?= trk_icon('file-plus') ?>ขร.2 (<?= count($kr2) ?>)</a>
    <a href="#kr5" class="btn btn-soft-navy btn-sm"><?= trk_icon('calendar-x') ?>ขร.5 (<?= count($kr5) ?>)</a>
    <a href="#issued" class="btn btn-soft-navy btn-sm"><?= trk_icon('folder-check') ?>ออกแล้ว (<?= $issuedActive ?>)</a>
    <a href="<?= e($backUrl) ?>" class="btn btn-navy btn-sm"><?= trk_icon('arrow-left') ?>กลับภาพรวม</a>
  </div>
</div>

<section class="mt-6">
  <?php $sectionHead('kr1', 'book', 'รอออก ขร.1 (ขาดเรียนรายวิชาถึงเกณฑ์)', 'ครูประจำวิชา', count($kr1)); ?>
  <?php if (!$kr1): ?>
    <?php $empty('ไม่มีนักเรียนที่รอออก ขร.1'); ?>
  <?php else: ?>
    <div class="mt-3 table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="w-14 whitespace-nowrap">เลขที่</th>
            <th>นักเรียน</th>
            <th>รายวิชา</th>
            <th class="hidden md:table-cell">ครูประจำวิชา</th>
            <th class="text-right">ขาด/ลา/หนี</th>
            <th class="text-right">รอออก</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($kr1 as $r): ?>
            <tr>
              <td class="tabular-nums text-ink/60"><?= (int) $r['std_student_number'] ?></td>
              <td><?= e($r['full_name']) ?> <span class="block text-xs text-ink/60"><?= e($r['std_student_code']) ?></span></td>
              <td><span class="font-medium"><?= e($r['vic_subject_code']) ?></span> <span class="block text-xs text-ink/55"><?= e($r['vic_subject_name']) ?></span></td>
              <td class="hidden text-ink/60 md:table-cell"><?= e($r['teachers'] ?: '-') ?></td>
              <td class="whitespace-nowrap text-right tabular-nums">
                <?= (int) $r['absent_hours'] ?>/<?= (int) $r['total_hours'] ?> ชม.
                <span class="block text-xs text-ink/55"><?= e((string) $r['percent']) ?>%</span>
              </td>
              <td class="text-right"><span class="chip-yellow">ครั้งที่ <?= (int) $r['next_round'] ?></span></td>
              <td class="text-right"><a href="<?= e(trk_url('dashboard/student.php?id=' . (int) $r['std_student_id'])) ?>" class="btn btn-soft-blue btn-sm"><?= trk_icon('history') ?>ประวัติ</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="mt-8">
  <?php $sectionHead('kr2', 'file-plus', 'รอออก ขร.2 (แจ้งผู้ปกครอง)', 'ครูที่ปรึกษา', count($kr2)); ?>
  <?php if (!$kr2): ?>
    <?php $empty('ไม่มีรอบติดตามที่รอออก ขร.2'); ?>
  <?php else: ?>
    <div class="mt-3 table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="w-14 whitespace-nowrap">เลขที่</th>
            <th>นักเรียน</th>
            <th>รายวิชา</th>
            <th class="text-right">ครั้งที่</th>
            <th class="hidden sm:table-cell">ได้รับ ขร.1</th>
            <th class="text-right">ค้างนาน</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($kr2 as $r): ?>
            <?php $age = (int) $today->diff(new DateTime(substr($r['opened_at'], 0, 10)))->days; ?>
            <tr>
              <td class="tabular-nums text-ink/60"><?= (int) $r['std_student_number'] ?></td>
              <td><?= e($r['full_name']) ?></td>
              <td><span class="font-medium"><?= e($r['vic_subject_code']) ?></span> <span class="block text-xs text-ink/55"><?= e($r['vic_subject_name']) ?></span></td>
              <td class="text-right tabular-nums"><?= (int) $r['round_no'] ?></td>
              <td class="hidden whitespace-nowrap text-ink/60 sm:table-cell"><?= e(trk_thai_date(substr($r['opened_at'], 0, 10), true)) ?></td>
              <td class="whitespace-nowrap text-right">
                <span class="<?= $age > $kr2Days ? 'chip-orange' : 'chip-sand' ?>"><?= trk_icon('clock', 'h-3.5 w-3.5') ?><?= $age ?> วัน</span>
              </td>
              <td class="text-right"><a href="<?= e(trk_url('advisor/round.php?id=' . (int) $r['id'])) ?>" class="btn btn-soft-blue btn-sm"><?= trk_icon('eye') ?>ดูรอบ</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="mt-8">
  <?php $sectionHead('kr5', 'calendar-x', 'รอออก ขร.5 (ขาดเรียนติดต่อกัน ' . (int) trk_setting('consecutive_absent_days') . ' วันขึ้นไป)', 'ครูที่ปรึกษา', count($kr5)); ?>
  <?php if (!$kr5): ?>
    <?php $empty('ไม่มีนักเรียนที่รอออก ขร.5'); ?>
  <?php else: ?>
    <div class="mt-3 table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="w-14 whitespace-nowrap">เลขที่</th>
            <th>นักเรียน</th>
            <th>ช่วงที่ขาด</th>
            <th class="text-right">จำนวนวัน</th>
            <th class="text-right">รอออก</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($kr5 as $c): ?>
            <?php $ep = $c['next_episode']; ?>
            <tr>
              <td class="tabular-nums text-ink/60"><?= (int) $c['std_student_number'] ?></td>
              <td><?= e($c['full_name']) ?> <span class="block text-xs text-ink/60"><?= e($c['std_student_code']) ?></span></td>
              <td class="whitespace-nowrap">
                <?= e(trk_thai_date($ep['from'], true)) ?> – <?= e(trk_thai_date($ep['to'], true)) ?>
                <?php if (!empty($ep['ongoing'])): ?><span class="chip-red ml-1">ยังขาดอยู่</span><?php endif; ?>
              </td>
              <td class="text-right tabular-nums"><?= (int) $ep['days'] ?></td>
              <td class="text-right"><span class="chip-yellow">ครั้งที่ <?= (int) $c['next_round'] ?></span></td>
              <td class="text-right"><a href="<?= e(trk_url('dashboard/student.php?id=' . (int) $c['std_student_id'])) ?>" class="btn btn-soft-blue btn-sm"><?= trk_icon('history') ?>ประวัติ</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="mt-8">
  <div id="issued" class="flex flex-wrap items-center gap-2 scroll-mt-40">
    <h2 class="text-base font-semibold flex items-center gap-2"><?= trk_icon('folder-check', 'h-[18px] w-[18px] text-navy/70') ?>เอกสารที่ออกแล้ว</h2>
    <span class="chip-sand"><?= $issuedActive ?> ฉบับที่ใช้งาน</span>
    <span class="text-xs text-ink/55">กด "ยกเลิก" เพื่อไปที่กล่องยกเลิกในหน้าเอกสาร</span>
  </div>
  <?php if (!$issued): ?>
    <p class="mt-3 card px-4 py-4 text-sm text-ink/55 flex items-center gap-2"><?= trk_icon('inbox', 'h-4 w-4 text-ink/35') ?>ยังไม่มีเอกสารที่ออกในภาคเรียนนี้</p>
  <?php else: ?>
    <div class="mt-3 table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="whitespace-nowrap">วันที่ออก</th>
            <th>เอกสาร</th>
            <th>นักเรียน / รายวิชา</th>
            <th class="hidden md:table-cell">ผู้ออก</th>
            <th>สถานะ</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($issued as $d): ?>
            <?php $data = json_decode($d['data'], true) ?: []; $isActive = $d['status'] === 'active'; ?>
            <tr class="<?= $isActive ? '' : 'opacity-60' ?>">
              <td class="whitespace-nowrap text-ink/60"><?= e(trk_thai_date(substr($d['issued_at'], 0, 10), true)) ?></td>
              <td class="whitespace-nowrap">
                <span class="font-medium"><?= e(trk_document_title($d)) ?></span>
                <span class="block text-xs text-ink/55">#<?= (int) $d['id'] ?><?= $d['doc_no'] ? ' · ที่ ' . e($d['doc_no']) : '' ?></span>
              </td>
              <td>
                <?php if ($d['doc_type'] === 'KR1'): ?>
                  <?= e(($data['subject']['code'] ?? '') . ' ' . ($data['subject']['name'] ?? '')) ?>
                  <span class="block text-xs text-ink/55"><?= (int) $d['round_count'] ?> คน</span>
                <?php else: ?>
                  <?= e($data['student']['name'] ?? '-') ?>
                  <span class="block text-xs text-ink/55"><?= e($data['student']['code'] ?? '') ?></span>
                <?php endif; ?>
              </td>
              <td class="hidden text-ink/60 md:table-cell"><?= e($d['issued_by_name']) ?></td>
              <td><span class="<?= $isActive ? 'chip-green' : 'chip-red' ?>"><?= $isActive ? 'ใช้งาน' : 'ยกเลิกแล้ว' ?></span></td>
              <td class="text-right">
                <div class="flex flex-col items-end gap-1.5 sm:flex-row sm:justify-end">
                  <a href="<?= e(trk_url('documents/view.php?id=' . (int) $d['id'])) ?>" class="btn btn-soft-blue btn-sm"><?= trk_icon('eye') ?>ดู</a>
                  <?php if ($isActive): ?>
                    <a href="<?= e(trk_url('documents/view.php?id=' . (int) $d['id'] . '#cancel')) ?>" class="btn btn-soft-red btn-sm"><?= trk_icon('x-circle') ?>ยกเลิก</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php
trk_render_footer();
