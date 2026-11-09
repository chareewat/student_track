-- เฟส 2: บันทึกผลติดตาม (1 ครั้ง = การติดตาม 1 ครั้ง ใช้ออก ขร.3 และนับสำหรับเคส 3)

CREATE TABLE IF NOT EXISTS `trk_followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `round_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `std_student_id` int(11) NOT NULL,
  `followup_date` date NOT NULL,
  `method` varchar(50) NOT NULL COMMENT 'เช่น เยี่ยมบ้าน',
  `visitors` varchar(255) NOT NULL COMMENT 'ผู้ร่วมติดตาม',
  `findings` text NOT NULL,
  `document_id` int(11) DEFAULT NULL COMMENT 'ขร.3 ที่ออกจากบันทึกนี้',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, cancelled',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_round` (`round_id`),
  KEY `idx_student_semester` (`std_student_id`, `semester_id`, `status`),
  KEY `idx_document` (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trk_followup_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `followup_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL COMMENT 'ภายใต้ storage/uploads',
  `original_name` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_followup` (`followup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
