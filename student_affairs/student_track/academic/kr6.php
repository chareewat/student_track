<?php
/**
 * เจ้าหน้าที่วิชาการ (และ role ที่เห็นทุกห้อง): ขร.6 เสนอจำหน่ายนักเรียนที่ครูที่ปรึกษาส่งมา
 */
require __DIR__ . '/../bootstrap.php';
trk_require_role(...TRK_ROLES_SEE_ALL);

$semesterId = trk_semester_id();
$docs = trk_db_all(
    "SELECT d.id, d.doc_no, d.status, d.issued_at, d.cancel_reason, d.data,
            c.std_classroom_level, c.std_classroom_room,
            CONCAT(IFNULL(t.psn_title_name, ''), m.psn_mem_name, ' ', m.psn_mem_surname) AS issued_by_name
     FROM trk_documents d
     JOIN std_classroom c ON c.std_classroom_id = d.std_classroom_id
     JOIN psn_member m ON m.psn_mem_id = d.issued_by
     LEFT JOIN psn_title t ON t.psn_title_id = m.psn_title_id
     WHERE d.doc_type = 'KR6' AND d.semester_id = ?
     ORDER BY d.status = 'cancelled', d.id DESC",
    [$semesterId]
);
$unread = [];
foreach (trk_db_all('SELECT document_id FROM trk_notifications WHERE recipient_id = ? AND read_at IS NULL AND document_id IS NOT NULL',
    [trk_user()['id']]) as $n) {
    $unread[(int) $n['document_id']] = true;
}

trk_render_header('ขร.6 เสนอจำหน่าย', 'kr6');
?>
<h1 class="text-xl font-semibold">ขร.6 ขอให้จำหน่ายนักเรียน</h1>
<p class="mt-1 text-sm text-slate-600">ครูที่ปรึกษาส่งมาหลังติดตามครบ 3 ครั้งแล้วนักเรียนยังไม่มาเรียน · ภาคเรียน <?= e(trk_semester_label()) ?></p>

<?php if (!$docs): ?>
  <p class="mt-6 text-sm text-slate-500">ยังไม่มี ขร.6 ในภาคเรียนนี้</p>
<?php else: ?>
  <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-left text-xs text-slate-500">
        <tr>
          <th class="px-4 py-2 font-medium">วันที่ส่ง</th>
          <th class="px-4 py-2 font-medium">นักเรียน</th>
          <th class="px-4 py-2 font-medium">ห้อง</th>
          <th class="px-4 py-2 font-medium">ครูที่ปรึกษา</th>
          <th class="px-4 py-2 text-right font-medium">ติดตาม</th>
          <th class="px-4 py-2 font-medium">สถานะ</th>
          <th class="px-4 py-2"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($docs as $d): ?>
          <?php $data = json_decode($d['data'], true); ?>
          <tr class="<?= $d['status'] === 'cancelled' ? 'text-slate-400' : '' ?>">
            <td class="px-4 py-2">
              <?php if (isset($unread[(int) $d['id']])): ?><span class="mr-1 inline-block h-2 w-2 rounded-full bg-blue-600"></span><?php endif; ?>
              <?= e(trk_thai_date($d['issued_at'], true)) ?>
            </td>
            <td class="px-4 py-2"><?= e($data['student']['name']) ?> <span class="text-xs text-slate-400"><?= e($data['student']['code']) ?></span></td>
            <td class="px-4 py-2"><?= e(trk_classroom_label($d)) ?></td>
            <td class="px-4 py-2"><?= e($d['issued_by_name']) ?></td>
            <td class="px-4 py-2 text-right"><?= (int) $data['followup_count'] ?> ครั้ง</td>
            <td class="px-4 py-2 text-xs"><?= $d['status'] === 'cancelled' ? 'ยกเลิก: ' . e($d['cancel_reason']) : 'รอพิจารณา' ?></td>
            <td class="px-4 py-2 text-right whitespace-nowrap">
              <a href="<?= e(trk_url('documents/view.php?id=' . (int) $d['id'])) ?>" class="text-blue-700 hover:underline">ดู</a>
              <a href="<?= e(trk_url('documents/print.php?id=' . (int) $d['id'])) ?>" target="_blank" class="ml-3 text-blue-700 hover:underline">พิมพ์</a>
              <a href="<?= e(trk_url('documents/docx.php?id=' . (int) $d['id'])) ?>" class="ml-3 text-blue-700 hover:underline">Word</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php
trk_render_footer();
