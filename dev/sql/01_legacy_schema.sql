CREATE TABLE `act_pms_data` (
  `act_pms_data_id` int(11) NOT NULL,
  `act_pms_data_name` varchar(200) NOT NULL,
  `act_pms_data_details` varchar(1000) NOT NULL,
  `act_pms_data_date1` date NOT NULL COMMENT 'ลงวันที่',
  `act_pms_data_date2` date NOT NULL COMMENT 'วันที่เริ่ม',
  `act_pms_data_date3` date NOT NULL COMMENT 'วันที่จบ',
  `act_pms_data_location` varchar(500) NOT NULL,
  `act_pms_data_work` int(11) NOT NULL COMMENT '0=แก้ไข, 1=ส่ง',
  `act_pms_data_status1` int(11) NOT NULL COMMENT '0=ไม่อนุมัติ, 1=รออนุมัติ, 2=อนุมัติ',
  `act_pms_data_status2` int(11) NOT NULL COMMENT '0=ไม่อนุมัติ, 1=รออนุมัติ, 2= อนุมัติ',
  `act_pms_data_year` varchar(4) NOT NULL COMMENT 'ปีที่ขอ',
  `act_pms_data_numbook` varchar(50) NOT NULL COMMENT 'เลขที่หนังสือ',
  `psn_mem_id` int(11) NOT NULL COMMENT '(FK) ผู้ขอ',
  `std_semester_id` int(11) NOT NULL COMMENT '(FK)',
  `director1` int(11) NOT NULL DEFAULT 0 COMMENT '(FK) act_pms_type (รองวิชาการ)',
  `director2` int(11) NOT NULL DEFAULT 0 COMMENT '(FK) act_pms_type (ผอ)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `act_pms_report_list` (
  `act_pms_report_list_id` int(11) NOT NULL,
  `act_pms_report_list_name` varchar(1000) NOT NULL COMMENT 'ผลการเข้าร่วมกิจกรรม',
  `act_pms_report_id` int(11) NOT NULL COMMENT '(FK) act_pms_report',
  `act_pms_report_type_id` int(11) NOT NULL COMMENT '(FK) act_pms_data_type',
  `act_pms_report_level_id` int(11) NOT NULL COMMENT '(FK) act_pms_report_level',
  `act_pms_report_list_img1` varchar(500) NOT NULL,
  `act_pms_report_list_img2` varchar(500) NOT NULL,
  `act_pms_report_list_date` date NOT NULL,
  `act_pms_report_list_status` int(11) NOT NULL COMMENT '0 = รอ 1 = อนุมัติ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `admin_type` (
  `admin_type_id` int(11) NOT NULL,
  `admin_type_name` varchar(30) NOT NULL,
  `admin_type_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=ปิด 1=เปิด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `ct_teach_data` (
  `ct_teach_data_id` int(11) NOT NULL,
  `ct_teach_data_semester` int(11) NOT NULL,
  `ct_teach_data_type` int(11) NOT NULL COMMENT '1 = สอนแทน 2 = แลกคาบ',
  `ct_teach_data_reason` int(11) NOT NULL COMMENT '1=ลาป่วย, 2=ลากิจ, 3=ปฏิบัติราชการแทน, 0=อื่นๆ',
  `ct_teach_data_location` varchar(1000) NOT NULL,
  `ct_teach_data_date1` date NOT NULL COMMENT 'ลงวันที่',
  `ct_teach_data_date2` date NOT NULL COMMENT 'วันที่ขอ',
  `ct_teach_data_date3` date NOT NULL COMMENT 'วันที่รับแลก',
  `ct_teach_data_tname` varchar(1000) NOT NULL COMMENT 'เรื่องที่สอน',
  `ct_teach_data_classroom` varchar(100) NOT NULL COMMENT 'ระดับชั้น/ห้อง',
  `ct_teach_data_room` varchar(1000) NOT NULL COMMENT 'ห้องสอน',
  `psn_mem_id_1` int(11) NOT NULL COMMENT 'ผู้ขอ',
  `psn_mem_id_2` int(11) NOT NULL COMMENT 'ผู้รับ',
  `ct_teach_data_tteach1` int(11) NOT NULL COMMENT 'คาบที่ขอ 1-45',
  `ct_teach_data_tteach2` int(11) NOT NULL COMMENT 'คาบที่แลก 1-45',
  `ct_teach_data_status1` int(11) NOT NULL COMMENT 'ครูผู้รับแลก 0 =ไม่ 1 =รอ 2= ยืนยัน',
  `ct_teach_data_status2` int(11) NOT NULL COMMENT 'หน.วิชาการ 0=ไม่ 1=รอ 2=อนุมัติ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `enroll_indy_subject` (
  `eis_id` int(11) NOT NULL,
  `std_semester_id` int(11) NOT NULL,
  `vic_subject_id` int(11) NOT NULL,
  `std_student_code` varchar(6) NOT NULL,
  `std_student_name` varchar(60) NOT NULL,
  `std_student_surname` varchar(70) NOT NULL,
  `psn_title_name` varchar(10) NOT NULL,
  `eis_classroom` varchar(7) NOT NULL,
  `eis_status` int(11) NOT NULL COMMENT '0=ไม่แสดงข้อมูล 1=แสดงข้อมูล',
  `eis_mem_add` int(11) NOT NULL COMMENT 'ผู้ลงทะเบียน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `hv_visits` (
  `id` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `school_year` int(11) NOT NULL,
  `class_level` varchar(50) NOT NULL,
  `student_no` varchar(20) DEFAULT NULL,
  `student_firstname` varchar(150) NOT NULL,
  `student_lastname` varchar(150) NOT NULL,
  `student_citizen_id` varchar(13) DEFAULT NULL,
  `living_with` varchar(255) DEFAULT NULL,
  `guardian_firstname` varchar(150) DEFAULT NULL,
  `guardian_lastname` varchar(150) DEFAULT NULL,
  `guardian_phone` varchar(50) DEFAULT NULL,
  `guardian_relation` varchar(150) DEFAULT NULL,
  `guardian_occupation` varchar(150) DEFAULT NULL,
  `guardian_citizen_id` varchar(13) DEFAULT NULL,
  `no_guardian` tinyint(1) NOT NULL DEFAULT 0,
  `family_member_count` int(11) DEFAULT NULL,
  `parent_status` varchar(255) DEFAULT NULL,
  `guardian_jobs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`guardian_jobs`)),
  `household_income_range` varchar(100) DEFAULT NULL,
  `housing_type` varchar(100) DEFAULT NULL,
  `house_condition` varchar(100) DEFAULT NULL,
  `vehicles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vehicles`)),
  `household_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`household_items`)),
  `distance_km` decimal(10,2) DEFAULT NULL,
  `travel_hour` int(11) DEFAULT NULL,
  `travel_minute` int(11) DEFAULT NULL,
  `commute_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`commute_methods`)),
  `free_time_activities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`free_time_activities`)),
  `responsibilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`responsibilities`)),
  `help_needed` text DEFAULT NULL,
  `school_suggestions` text DEFAULT NULL,
  `teacher_advisor_1` varchar(255) DEFAULT NULL,
  `teacher_advisor_2` varchar(255) DEFAULT NULL,
  `guardian_sign_name` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'psn_mem_id จากตาราง psn_member',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hv_visit_photos` (
  `id` int(11) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `onlinebox` (
  `ob_id` int(11) NOT NULL,
  `obt_id` int(11) NOT NULL DEFAULT 0,
  `psn_mem_id` int(11) NOT NULL DEFAULT 0,
  `ob_detail` text NOT NULL,
  `ob_attach` varchar(200) NOT NULL,
  `std_student_code` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `std_student_pass` varchar(6) NOT NULL,
  `mem_action` int(11) NOT NULL DEFAULT 0,
  `ob_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=del,1=send,2=read,3=action,4=success',
  `ob_date_send` datetime NOT NULL,
  `ob_date_action` datetime NOT NULL,
  `ob_classroom` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `onlinebox_type` (
  `obt_id` int(11) NOT NULL,
  `obt_name` varchar(100) NOT NULL,
  `obt_mode` int(11) NOT NULL COMMENT '3=ลับ',
  `obt_status` int(11) NOT NULL COMMENT '0=disable 1=enable'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `psn_level_teach` (
  `psn_level_teach_id` int(11) NOT NULL,
  `psn_level_teach_name` varchar(30) NOT NULL,
  `psn_level_teach_priority` int(11) NOT NULL,
  `psn_level_teach_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=off 1=on'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `psn_member` (
  `psn_mem_id` int(11) NOT NULL,
  `psn_mem_name` varchar(60) DEFAULT NULL,
  `psn_mem_surname` varchar(70) DEFAULT NULL,
  `psn_mem_eng_name` varchar(60) NOT NULL,
  `psn_mem_eng_surname` varchar(70) NOT NULL,
  `psn_mem_citizen_number` varchar(13) NOT NULL,
  `psn_sex` varchar(4) NOT NULL,
  `psn_mem_birthday` date NOT NULL,
  `psn_mem_start_work` date NOT NULL,
  `psn_mem_end_work` date NOT NULL,
  `psn_mem_id_work` varchar(10) NOT NULL,
  `psn_level_teach_id` int(11) NOT NULL,
  `psn_mem_email` varchar(30) NOT NULL,
  `psn_mem_path_image` varchar(100) NOT NULL,
  `psn_mem_username` varchar(100) DEFAULT NULL,
  `psn_mem_password` varchar(50) DEFAULT '25d55ad283aa400af464c76d713c07ad',
  `psn_title_id` int(11) DEFAULT NULL COMMENT 'FK',
  `psn_title_en_id` int(11) NOT NULL COMMENT 'FK',
  `psn_position_id` int(11) DEFAULT NULL COMMENT 'FK',
  `psn_pos_lev_id` int(11) NOT NULL,
  `psn_subject_id` int(11) DEFAULT NULL COMMENT 'FK',
  `psn_permittion_id` int(11) DEFAULT NULL COMMENT 'FK',
  `psn_memberstatus_id` int(11) DEFAULT 1 COMMENT '0=off 1=on (FK)',
  `psn_mem_teach_status` int(11) DEFAULT 0 COMMENT '0=show ,1=hide',
  `psn_mem_behavior_permit` int(11) NOT NULL DEFAULT 0 COMMENT '0=ไม่สามารถใช้งาน 1=สามารถใช้งาน',
  `psn_mem_vicharkan_permit` int(11) NOT NULL DEFAULT 0 COMMENT '0=ไม่สามารถใช้งาน 1=สามารถใช้งาน',
  `psn_mem_tkey` varchar(6) NOT NULL DEFAULT '123456' COMMENT 'รหัสส่งเกรด',
  `admin_type_id` int(11) NOT NULL COMMENT '0=ไม่มีสิทธิ์ 1=วิชาการ 2=บุคคล 3=แผน 98=ฝ่ายบริหาร 99=superadmin (FK)',
  `psn_mem_tel` varchar(10) NOT NULL COMMENT '0901234567',
  `psn_mem_is_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = ผู้ดูแลระบบพฤติกรรม'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `psn_memberstatus` (
  `psn_memberstatus_id` int(11) NOT NULL,
  `psn_memberstatus_name` varchar(30) NOT NULL,
  `psn_memberstatus_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=off 1=on'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `psn_permittion` (
  `psn_permittion_id` int(11) NOT NULL,
  `psn_permittion_name` varchar(50) NOT NULL,
  `psn_permittion_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=off 1=on'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `psn_position` (
  `psn_position_id` int(11) NOT NULL,
  `psn_position_name` varchar(50) NOT NULL,
  `psn_position_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=off 1=on',
  `psn_position_priority` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `psn_position_level` (
  `psn_pos_lev_id` int(11) NOT NULL,
  `psn_pos_lev_name` varchar(50) NOT NULL,
  `psn_pos_lev_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=off,1=on',
  `psn_pos_lev_priority` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `psn_subject` (
  `psn_subject_id` int(11) NOT NULL,
  `psn_subject_name` varchar(50) NOT NULL,
  `psn_subject_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=off 1=on',
  `psn_subject_priorty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `psn_title` (
  `psn_title_id` int(11) NOT NULL,
  `psn_title_name` varchar(20) NOT NULL,
  `psn_title_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=off,1=on',
  `psn_title_priority` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `psn_title_en` (
  `psn_title_en_id` int(11) NOT NULL,
  `psn_title_en_name` varchar(20) NOT NULL,
  `psn_title_en_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=off,1=on',
  `psn_title_en_priority` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `regrade2_count` (
  `reg_count_id` int(11) NOT NULL,
  `reg_count_num` int(11) NOT NULL,
  `reg_count_details` varchar(500) NOT NULL COMMENT 'รายละเอียด',
  `std_semester_id` int(11) NOT NULL COMMENT '(FK) ปีการศึกษา',
  `reg_count_status` int(11) NOT NULL COMMENT '0=ปิด 1=เปิด',
  `reg_count_open` date NOT NULL COMMENT 'วันที่เปิด',
  `reg_count_close` date NOT NULL COMMENT 'วันที่ปิด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `regrade2_stdfollow` (
  `reg_stdflw_id` int(11) NOT NULL,
  `reg_stdflw_count` varchar(100) NOT NULL COMMENT 'ครั้งที่ติดตาม',
  `reg_stdflw_numbook` varchar(20) NOT NULL COMMENT 'เลขหนังสือ',
  `reg_stdflw_date` date NOT NULL COMMENT 'วันที่บันทึก',
  `reg_stdflw_year` varchar(4) NOT NULL COMMENT 'ปีที่ขอ',
  `reg_stdflw_director1` int(11) NOT NULL DEFAULT 1 COMMENT '0=ไม่อนุมัติ 1= รอ 2=อนุมัติ',
  `reg_stdflw_director2` int(11) NOT NULL DEFAULT 1 COMMENT '0=ไม่อนุมัติ 1= รอ 2=อนุมัติ',
  `reg_count_id` int(11) NOT NULL COMMENT '(FK) regrade2_count',
  `psn_mem_id` int(11) NOT NULL COMMENT '(FK) psn_member',
  `std_student_code` varchar(10) NOT NULL COMMENT '(FK) std_student',
  `std_student_fullname` varchar(500) NOT NULL DEFAULT '-',
  `std_classroom_name` varchar(50) NOT NULL,
  `reg_stdflw_status` int(11) NOT NULL DEFAULT 1 COMMENT '0 = off 1=on',
  `reg_director_id_1` int(11) NOT NULL DEFAULT 0 COMMENT 'รองวิชาการ/หน วิชาการ',
  `reg_director_id_2` int(11) NOT NULL DEFAULT 0 COMMENT 'ผอ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `regrade2_std_data` (
  `reg_std_data_id` int(11) NOT NULL,
  `reg_std_code` varchar(10) NOT NULL,
  `reg_std_prefix` varchar(10) NOT NULL,
  `reg_std_name` varchar(100) NOT NULL,
  `reg_std_surname` varchar(100) NOT NULL,
  `reg_std_class_level` varchar(30) NOT NULL COMMENT 'ระดับชั้น',
  `reg_std_class_ordinal` int(11) NOT NULL,
  `reg_std_score_old` varchar(5) NOT NULL COMMENT 'คะแนนเดิม',
  `reg_std_score_new` varchar(5) NOT NULL COMMENT 'คะแนนใหม่',
  `reg_std_grade_old` varchar(5) NOT NULL COMMENT 'เกรดเดิม',
  `reg_std_grade_new` varchar(5) NOT NULL COMMENT 'เกรดใหม่',
  `reg_tc_code` varchar(10) NOT NULL COMMENT '(FK)',
  `vic_subject_code` varchar(50) NOT NULL COMMENT '(FK)',
  `reg_count_id` int(11) NOT NULL COMMENT '(FK) รอบการเเก้เกรด',
  `reg_std_data_regis` int(11) NOT NULL COMMENT 'สถานะ 0=ยัง 1=ลงทะเบียน 2=ผู้สอนยืนยัน 3=วิชาการตรวจสอบ',
  `reg_std_data_datetime` varchar(20) NOT NULL COMMENT 'บันทึกวันที่+เวลา',
  `reg_vic_date_confirm` date NOT NULL,
  `reg_std_data_print_status` varchar(1) NOT NULL DEFAULT '0' COMMENT '1 = print'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `sch_attendance` (
  `sch_att_id` int(11) NOT NULL,
  `sch_student_code` varchar(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `std_student_id` int(11) NOT NULL,
  `sch_att_day` date NOT NULL,
  `sch_att_type_id` int(11) NOT NULL DEFAULT 1,
  `std_semester_id` int(11) NOT NULL,
  `total_now_std` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `sch_attendance_type` (
  `sch_att_type_id` int(11) NOT NULL,
  `sch_att_type_name` varchar(50) NOT NULL,
  `sch_att_type_shortname` varchar(50) NOT NULL COMMENT 'ชื่อย่อสำหรับแสดงผลบนสมาร์ทโฟน',
  `sch_att_type_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=ปิด 1=เปิด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `std_advisor` (
  `std_advisor_id` int(11) NOT NULL,
  `std_semester_id` int(11) NOT NULL COMMENT 'FK',
  `psn_mem_id` int(11) NOT NULL COMMENT 'FK',
  `std_classroom_id` int(11) NOT NULL COMMENT 'FK'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `std_classroom` (
  `std_classroom_id` int(11) NOT NULL,
  `std_classroom_level` varchar(5) NOT NULL,
  `std_classroom_room` varchar(5) NOT NULL,
  `std_classroom_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=ปิด 1=เปิด',
  `std_classroom_priority` int(11) NOT NULL,
  `std_classroom_degree` int(11) NOT NULL DEFAULT 1 COMMENT '1=ม.ต้น 2 = ม.ปลาย'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `std_head_advisor` (
  `std_head_advisor_id` int(11) NOT NULL,
  `std_head_advisor_level` varchar(5) NOT NULL,
  `psn_mem_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `std_semester` (
  `std_semester_id` int(11) NOT NULL,
  `std_semester_term` varchar(1) NOT NULL,
  `std_semester_year` varchar(4) NOT NULL,
  `std_semester_beginday` date NOT NULL COMMENT 'วันเปิดเทอม',
  `std_semester_endday` date NOT NULL COMMENT 'วันปิดเทอม'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `std_status` (
  `std_status_id` int(11) NOT NULL,
  `std_status_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `std_student` (
  `std_student_id` int(11) NOT NULL,
  `std_student_code` varchar(6) NOT NULL,
  `psn_title_id` int(11) NOT NULL COMMENT 'FK',
  `psn_title_en_id` int(11) NOT NULL COMMENT 'FK',
  `std_student_name` varchar(60) NOT NULL,
  `std_student_surname` varchar(70) NOT NULL,
  `std_status_id` int(11) NOT NULL COMMENT 'FK',
  `std_student_number` int(11) NOT NULL,
  `std_student_pwd` varchar(6) NOT NULL DEFAULT '123456',
  `std_semester_id` int(11) NOT NULL DEFAULT 0 COMMENT 'FK',
  `std_classroom_id` int(11) NOT NULL COMMENT 'FK',
  `std_citizen_number` varchar(13) NOT NULL,
  `std_student_sex` varchar(4) NOT NULL,
  `std_student_eng_name` varchar(60) NOT NULL,
  `std_student_eng_surname` varchar(70) NOT NULL,
  `std_student_birthday` date NOT NULL,
  `std_student_blood` varchar(2) NOT NULL,
  `std_student_origin` varchar(10) NOT NULL,
  `std_student_nationalrity` varchar(10) NOT NULL,
  `std_student_region` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `t_teachtable` (
  `t_teachtable_id` int(11) NOT NULL,
  `std_semester_id` int(11) NOT NULL COMMENT 'ปีการศึกษา',
  `psn_mem_id` int(11) NOT NULL COMMENT 'ผู้สอน',
  `vic_subject_id` int(11) NOT NULL COMMENT 'วิชา',
  `std_classroom_id` int(11) NOT NULL COMMENT 'ห้อง',
  `t_teachtable_period` int(11) NOT NULL COMMENT 'คาบ 1-45',
  `t_teachtable_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=del,1=normal',
  `t_teachtable_date` datetime NOT NULL COMMENT 'วันที่เพิ่มข้อมูล'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `vic_attendance_subject` (
  `vic_att_subject_id` int(11) NOT NULL,
  `vic_att_teach_id` int(11) NOT NULL COMMENT 'FK',
  `std_student_code` varchar(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'รหัสนักเรียน FK',
  `vic_att_subject_check_status` int(11) NOT NULL COMMENT '1=มา 2=ขาด 3=ลาป่วย/ลากิจ 4=หนี 5=กิจกรรม 6=online 7=offline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `vic_attendance_teach` (
  `vic_att_teach_id` int(11) NOT NULL,
  `vic_att_period` int(11) NOT NULL,
  `vic_subject_id` int(11) NOT NULL COMMENT 'FK',
  `vic_subject_code` varchar(20) NOT NULL COMMENT 'FK',
  `vic_att_date_teach` date NOT NULL,
  `psn_mem_id` int(11) NOT NULL COMMENT 'FK',
  `vic_att_fullname` varchar(120) NOT NULL COMMENT 'ผู้สอน',
  `vic_att_classroom` varchar(7) NOT NULL,
  `std_semester_id` int(11) NOT NULL COMMENT 'FK',
  `vic_att_amount` int(11) NOT NULL COMMENT 'จำนวนนักเรียน',
  `vic_att_detail` text NOT NULL COMMENT 'เฉพาะไม่เข้าเรียน',
  `vic_att_come` int(11) NOT NULL COMMENT 'มาเรียน',
  `vic_att_absent` int(11) NOT NULL COMMENT 'ขาด',
  `vic_att_leave` int(11) NOT NULL COMMENT 'ลาป่วย/ลากิจ',
  `vic_att_escept` int(11) NOT NULL COMMENT 'หนี',
  `vic_att_activity` int(11) NOT NULL COMMENT 'กิจกรรม',
  `vic_att_online` int(11) NOT NULL DEFAULT 0 COMMENT 'online',
  `vic_att_offline` int(11) NOT NULL DEFAULT 0 COMMENT 'offline',
  `vic_att_brief` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `vic_subject` (
  `vic_subject_id` int(11) NOT NULL,
  `vic_subject_code` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `vic_subject_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `vic_subject_group_id` int(11) NOT NULL COMMENT 'FK',
  `vic_subject_level` varchar(6) NOT NULL DEFAULT '0',
  `vic_subject_term` int(11) NOT NULL,
  `vic_subject_credit` float(3,2) NOT NULL,
  `vic_subject_status` int(11) NOT NULL DEFAULT 1 COMMENT '0=off,1=on',
  `vic_subject_type` int(11) NOT NULL DEFAULT 0 COMMENT '0=วิชาพื้นฐาน 1=วิชาเลือก',
  `vic_subject_amount_limit` int(11) NOT NULL COMMENT 'จำนวนการรับสมัคร',
  `vic_subject_update` datetime NOT NULL COMMENT 'เวลาเพิ่มหรืออัพเดท'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `vic_subject_group` (
  `vic_subject_group_id` int(11) NOT NULL,
  `vic_subject_group_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `vic_subject_open` (
  `subject_open_id` int(11) NOT NULL,
  `vic_subject_id` int(11) NOT NULL,
  `std_semester_id` int(11) NOT NULL,
  `std_classroom_id` int(11) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `act_pms_data`
  ADD PRIMARY KEY (`act_pms_data_id`);

ALTER TABLE `act_pms_report_list`
  ADD PRIMARY KEY (`act_pms_report_list_id`);

ALTER TABLE `admin_type`
  ADD PRIMARY KEY (`admin_type_id`);

ALTER TABLE `ct_teach_data`
  ADD PRIMARY KEY (`ct_teach_data_id`);

ALTER TABLE `enroll_indy_subject`
  ADD PRIMARY KEY (`eis_id`);

ALTER TABLE `hv_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_year` (`school_year`),
  ADD KEY `idx_class_level` (`class_level`),
  ADD KEY `idx_student_name` (`student_firstname`,`student_lastname`),
  ADD KEY `idx_created_by` (`created_by`);

ALTER TABLE `hv_visit_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visit_id` (`visit_id`);

ALTER TABLE `onlinebox`
  ADD PRIMARY KEY (`ob_id`);

ALTER TABLE `onlinebox_type`
  ADD PRIMARY KEY (`obt_id`);

ALTER TABLE `psn_level_teach`
  ADD PRIMARY KEY (`psn_level_teach_id`);

ALTER TABLE `psn_member`
  ADD PRIMARY KEY (`psn_mem_id`),
  ADD KEY `fk1` (`psn_title_id`),
  ADD KEY `fk2` (`psn_position_id`),
  ADD KEY `fk3` (`psn_subject_id`),
  ADD KEY `fk4` (`psn_permittion_id`),
  ADD KEY `fk5` (`psn_memberstatus_id`),
  ADD KEY `fk6` (`admin_type_id`),
  ADD KEY `mem2titleeng` (`psn_title_en_id`),
  ADD KEY `mem2postitionlevel` (`psn_pos_lev_id`),
  ADD KEY `mem2level_teach` (`psn_level_teach_id`);

ALTER TABLE `psn_memberstatus`
  ADD PRIMARY KEY (`psn_memberstatus_id`);

ALTER TABLE `psn_permittion`
  ADD PRIMARY KEY (`psn_permittion_id`);

ALTER TABLE `psn_position`
  ADD PRIMARY KEY (`psn_position_id`);

ALTER TABLE `psn_position_level`
  ADD PRIMARY KEY (`psn_pos_lev_id`);

ALTER TABLE `psn_subject`
  ADD PRIMARY KEY (`psn_subject_id`);

ALTER TABLE `psn_title`
  ADD PRIMARY KEY (`psn_title_id`);

ALTER TABLE `psn_title_en`
  ADD PRIMARY KEY (`psn_title_en_id`);

ALTER TABLE `regrade2_count`
  ADD PRIMARY KEY (`reg_count_id`);

ALTER TABLE `regrade2_stdfollow`
  ADD PRIMARY KEY (`reg_stdflw_id`);

ALTER TABLE `regrade2_std_data`
  ADD PRIMARY KEY (`reg_std_data_id`);

ALTER TABLE `sch_attendance`
  ADD PRIMARY KEY (`sch_att_id`),
  ADD KEY `att2type` (`sch_att_type_id`),
  ADD KEY `att2semester` (`std_semester_id`),
  ADD KEY `idx_att_day_type_student` (`sch_att_day`,`sch_att_type_id`,`std_student_id`);

ALTER TABLE `sch_attendance_type`
  ADD PRIMARY KEY (`sch_att_type_id`);

ALTER TABLE `std_advisor`
  ADD PRIMARY KEY (`std_advisor_id`),
  ADD KEY `advisor2semester` (`std_semester_id`),
  ADD KEY `advisor2member` (`psn_mem_id`),
  ADD KEY `advisor2classroom` (`std_classroom_id`);

ALTER TABLE `std_classroom`
  ADD PRIMARY KEY (`std_classroom_id`);

ALTER TABLE `std_head_advisor`
  ADD PRIMARY KEY (`std_head_advisor_id`),
  ADD KEY `std_head2member` (`psn_mem_id`);

ALTER TABLE `std_semester`
  ADD PRIMARY KEY (`std_semester_id`);

ALTER TABLE `std_status`
  ADD PRIMARY KEY (`std_status_id`);

ALTER TABLE `std_student`
  ADD PRIMARY KEY (`std_student_id`),
  ADD KEY `std_2_classroom` (`std_classroom_id`),
  ADD KEY `std_2_semester` (`std_semester_id`),
  ADD KEY `std_2_std_status` (`std_status_id`),
  ADD KEY `std_2_title` (`psn_title_id`),
  ADD KEY `std_2_titleeng` (`psn_title_en_id`),
  ADD KEY `idx_student_class_status` (`std_classroom_id`,`std_status_id`);

ALTER TABLE `t_teachtable`
  ADD PRIMARY KEY (`t_teachtable_id`);

ALTER TABLE `vic_attendance_subject`
  ADD PRIMARY KEY (`vic_att_subject_id`),
  ADD KEY `att_subject2att_teach` (`vic_att_teach_id`);

ALTER TABLE `vic_attendance_teach`
  ADD PRIMARY KEY (`vic_att_teach_id`),
  ADD KEY `att_teach2subject` (`vic_subject_id`);

ALTER TABLE `vic_subject`
  ADD PRIMARY KEY (`vic_subject_id`),
  ADD KEY `subject2subjectgroup` (`vic_subject_group_id`);

ALTER TABLE `vic_subject_group`
  ADD PRIMARY KEY (`vic_subject_group_id`);

ALTER TABLE `vic_subject_open`
  ADD PRIMARY KEY (`subject_open_id`),
  ADD UNIQUE KEY `uq_open` (`vic_subject_id`,`std_semester_id`,`std_classroom_id`,`section`),
  ADD KEY `idx_subject` (`vic_subject_id`),
  ADD KEY `idx_semester` (`std_semester_id`),
  ADD KEY `idx_classroom` (`std_classroom_id`);

ALTER TABLE `act_pms_data`
  MODIFY `act_pms_data_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `act_pms_report_list`
  MODIFY `act_pms_report_list_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ct_teach_data`
  MODIFY `ct_teach_data_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `enroll_indy_subject`
  MODIFY `eis_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `hv_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `hv_visit_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `onlinebox`
  MODIFY `ob_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `onlinebox_type`
  MODIFY `obt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `psn_level_teach`
  MODIFY `psn_level_teach_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `psn_member`
  MODIFY `psn_mem_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

ALTER TABLE `psn_memberstatus`
  MODIFY `psn_memberstatus_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `psn_permittion`
  MODIFY `psn_permittion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `psn_position`
  MODIFY `psn_position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `psn_subject`
  MODIFY `psn_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

ALTER TABLE `psn_title`
  MODIFY `psn_title_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `psn_title_en`
  MODIFY `psn_title_en_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `regrade2_count`
  MODIFY `reg_count_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `regrade2_stdfollow`
  MODIFY `reg_stdflw_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `regrade2_std_data`
  MODIFY `reg_std_data_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sch_attendance`
  MODIFY `sch_att_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200548;

ALTER TABLE `std_advisor`
  MODIFY `std_advisor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=292;

ALTER TABLE `std_head_advisor`
  MODIFY `std_head_advisor_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `std_semester`
  MODIFY `std_semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

ALTER TABLE `std_status`
  MODIFY `std_status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `std_student`
  MODIFY `std_student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4741;

ALTER TABLE `t_teachtable`
  MODIFY `t_teachtable_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4737;

ALTER TABLE `vic_attendance_subject`
  MODIFY `vic_att_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=865170;

ALTER TABLE `vic_attendance_teach`
  MODIFY `vic_att_teach_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85156;

ALTER TABLE `vic_subject`
  MODIFY `vic_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1289;

ALTER TABLE `vic_subject_group`
  MODIFY `vic_subject_group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

ALTER TABLE `vic_subject_open`
  MODIFY `subject_open_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=494;

ALTER TABLE `hv_visit_photos`
  ADD CONSTRAINT `fk_photo_visit` FOREIGN KEY (`visit_id`) REFERENCES `hv_visits` (`id`) ON DELETE CASCADE;

ALTER TABLE `psn_member`
  ADD CONSTRAINT `fk1` FOREIGN KEY (`psn_title_id`) REFERENCES `psn_title` (`psn_title_id`),
  ADD CONSTRAINT `fk2` FOREIGN KEY (`psn_position_id`) REFERENCES `psn_position` (`psn_position_id`),
  ADD CONSTRAINT `fk3` FOREIGN KEY (`psn_subject_id`) REFERENCES `psn_subject` (`psn_subject_id`),
  ADD CONSTRAINT `fk4` FOREIGN KEY (`psn_permittion_id`) REFERENCES `psn_permittion` (`psn_permittion_id`),
  ADD CONSTRAINT `fk5` FOREIGN KEY (`psn_memberstatus_id`) REFERENCES `psn_memberstatus` (`psn_memberstatus_id`),
  ADD CONSTRAINT `fk6` FOREIGN KEY (`admin_type_id`) REFERENCES `admin_type` (`admin_type_id`),
  ADD CONSTRAINT `mem2level_teach` FOREIGN KEY (`psn_level_teach_id`) REFERENCES `psn_level_teach` (`psn_level_teach_id`),
  ADD CONSTRAINT `mem2postitionlevel` FOREIGN KEY (`psn_pos_lev_id`) REFERENCES `psn_position_level` (`psn_pos_lev_id`),
  ADD CONSTRAINT `mem2titleeng` FOREIGN KEY (`psn_title_en_id`) REFERENCES `psn_title_en` (`psn_title_en_id`);

ALTER TABLE `sch_attendance`
  ADD CONSTRAINT `att2semester` FOREIGN KEY (`std_semester_id`) REFERENCES `std_semester` (`std_semester_id`),
  ADD CONSTRAINT `att2type` FOREIGN KEY (`sch_att_type_id`) REFERENCES `sch_attendance_type` (`sch_att_type_id`);

ALTER TABLE `std_head_advisor`
  ADD CONSTRAINT `std_head2member` FOREIGN KEY (`psn_mem_id`) REFERENCES `psn_member` (`psn_mem_id`);

ALTER TABLE `std_student`
  ADD CONSTRAINT `std_2_classroom` FOREIGN KEY (`std_classroom_id`) REFERENCES `std_classroom` (`std_classroom_id`),
  ADD CONSTRAINT `std_2_std_status` FOREIGN KEY (`std_status_id`) REFERENCES `std_status` (`std_status_id`),
  ADD CONSTRAINT `std_2_title` FOREIGN KEY (`psn_title_id`) REFERENCES `psn_title` (`psn_title_id`),
  ADD CONSTRAINT `std_2_titleeng` FOREIGN KEY (`psn_title_en_id`) REFERENCES `psn_title_en` (`psn_title_en_id`);

ALTER TABLE `vic_attendance_subject`
  ADD CONSTRAINT `att_subject2att_teach` FOREIGN KEY (`vic_att_teach_id`) REFERENCES `vic_attendance_teach` (`vic_att_teach_id`);

ALTER TABLE `vic_attendance_teach`
  ADD CONSTRAINT `att_teach2subject` FOREIGN KEY (`vic_subject_id`) REFERENCES `vic_subject` (`vic_subject_id`);

ALTER TABLE `vic_subject`
  ADD CONSTRAINT `subject2subjectgroup` FOREIGN KEY (`vic_subject_group_id`) REFERENCES `vic_subject_group` (`vic_subject_group_id`);

ALTER TABLE `vic_subject_open`
  ADD CONSTRAINT `fk_open_semester` FOREIGN KEY (`std_semester_id`) REFERENCES `std_semester` (`std_semester_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_open_subject` FOREIGN KEY (`vic_subject_id`) REFERENCES `vic_subject` (`vic_subject_id`) ON UPDATE CASCADE;

