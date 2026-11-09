-- เฟส 1: รอบติดตาม, เอกสาร ขร., การแจ้งเตือนในระบบ

-- รอบติดตาม: นักเรียน 1 คน × เคส × (รายวิชา) × ครั้งที่
CREATE TABLE IF NOT EXISTS `trk_rounds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_type` tinyint(4) NOT NULL COMMENT '1=ขาดรายวิชา 2=ขาดติดต่อกัน',
  `semester_id` int(11) NOT NULL,
  `std_student_id` int(11) NOT NULL,
  `std_student_code` varchar(10) NOT NULL,
  `std_classroom_id` int(11) NOT NULL COMMENT 'ห้อง ณ ตอนเปิดรอบ',
  `classroom_label` varchar(20) NOT NULL COMMENT 'เช่น ม.1/1 ณ ตอนเปิดรอบ',
  `vic_subject_id` int(11) DEFAULT NULL COMMENT 'เคส 1',
  `round_no` tinyint(4) NOT NULL COMMENT 'ครั้งที่ 1-3',
  `absent_hours` int(11) DEFAULT NULL COMMENT 'เคส 1: ชั่วโมงขาดสะสม ณ ตอนเปิดรอบ',
  `total_hours` int(11) DEFAULT NULL COMMENT 'เคส 1: เวลาเรียนทั้งหมด',
  `absent_from` date DEFAULT NULL COMMENT 'เคส 2',
  `absent_to` date DEFAULT NULL COMMENT 'เคส 2',
  `status` varchar(20) NOT NULL DEFAULT 'open' COMMENT 'open, closed, cancelled',
  `opened_by` int(11) NOT NULL,
  `opened_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_case_lookup` (`semester_id`, `case_type`, `std_student_id`, `vic_subject_id`),
  KEY `idx_classroom_status` (`std_classroom_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- เอกสาร ขร. ทุกชนิด ข้อมูลที่ใช้พิมพ์เก็บเป็น snapshot ไว้ใน data (JSON) เพื่อพิมพ์ซ้ำได้เหมือนเดิม
CREATE TABLE IF NOT EXISTS `trk_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc_type` varchar(10) NOT NULL COMMENT 'KR1, KR2, BK14, KR3, KR5, KR6',
  `semester_id` int(11) NOT NULL,
  `std_classroom_id` int(11) NOT NULL,
  `vic_subject_id` int(11) DEFAULT NULL,
  `round_no` tinyint(4) DEFAULT NULL,
  `doc_no` varchar(30) DEFAULT NULL COMMENT 'เลขหนังสือออก',
  `data` longtext NOT NULL COMMENT 'JSON snapshot',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, cancelled',
  `issued_by` int(11) NOT NULL,
  `issued_at` datetime NOT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type_semester` (`doc_type`, `semester_id`),
  KEY `idx_issued_by` (`issued_by`),
  KEY `idx_classroom` (`std_classroom_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trk_document_rounds` (
  `document_id` int(11) NOT NULL,
  `round_id` int(11) NOT NULL,
  PRIMARY KEY (`document_id`, `round_id`),
  KEY `idx_round` (`round_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trk_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_recipient_read` (`recipient_id`, `read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
