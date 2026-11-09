-- เฟส 0: ตารางหลักของระบบติดตามนักเรียน (prefix trk_)

CREATE TABLE IF NOT EXISTS `trk_migrations` (
  `name` varchar(100) NOT NULL,
  `applied_at` datetime NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- บุคลากร 1 คนมีได้หลาย role
CREATE TABLE IF NOT EXISTS `trk_user_roles` (
  `psn_mem_id` int(11) NOT NULL,
  `role` varchar(20) NOT NULL COMMENT 'admin, teacher, executive, academic, affairs',
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime NOT NULL,
  PRIMARY KEY (`psn_mem_id`, `role`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ค่าที่ admin แก้ได้ (ถ้าไม่มีแถว ใช้ค่าเริ่มต้นในโค้ด)
CREATE TABLE IF NOT EXISTS `trk_settings` (
  `setting_key` varchar(60) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ชั่วโมงเรียนต่อภาคของรายวิชาที่ไม่มีหน่วยกิต (หรือที่ต้องการกำหนดเอง)
CREATE TABLE IF NOT EXISTS `trk_subject_hours` (
  `vic_subject_id` int(11) NOT NULL,
  `hours` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`vic_subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trk_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `psn_mem_id` int(11) NOT NULL,
  `action` varchar(60) NOT NULL,
  `detail` text DEFAULT NULL COMMENT 'JSON',
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_member` (`psn_mem_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
