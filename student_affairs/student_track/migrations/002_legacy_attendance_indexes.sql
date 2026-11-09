-- เพิ่ม index บนตารางเช็คชื่อเดิม เพื่อให้ query ตรวจหานักเรียนเข้าเกณฑ์ทำงานเร็ว
-- ไม่เปลี่ยนข้อมูลหรือโครงสร้างคอลัมน์ ระบบเดิมใช้งานได้ตามปกติ
-- บน production: vic_attendance_subject มีประมาณ 865,000 แถว ควรรันนอกเวลาเช็คชื่อ (เช่น หลัง 17:00)

ALTER TABLE `vic_attendance_teach`
  ADD KEY IF NOT EXISTS `trk_idx_sem_subject_room` (`std_semester_id`, `vic_subject_id`, `vic_att_classroom`),
  ADD KEY IF NOT EXISTS `trk_idx_teacher_sem` (`psn_mem_id`, `std_semester_id`);

ALTER TABLE `vic_attendance_subject`
  ADD KEY IF NOT EXISTS `trk_idx_student_teach` (`std_student_code`, `vic_att_teach_id`);

ALTER TABLE `sch_attendance`
  ADD KEY IF NOT EXISTS `trk_idx_sem_student_day` (`std_semester_id`, `std_student_id`, `sch_att_day`);

ALTER TABLE `t_teachtable`
  ADD KEY IF NOT EXISTS `trk_idx_teacher_sem` (`psn_mem_id`, `std_semester_id`, `t_teachtable_status`);
