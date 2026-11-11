-- ========================================================
-- DATABASE: school_todolist_db
-- StudyList - School To Do List Website
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ========================================================

-- 1. CREATE DATABASE
CREATE DATABASE IF NOT EXISTS `school_todolist_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `school_todolist_db`;

-- 2. CREATE TABLE: subjects
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subject_name` (`subject_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. CREATE TABLE: tasks
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `due_date` DATE NULL,
  `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
  `status` ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_subject_id` (`subject_id`),
  KEY `idx_tasks_status` (`status`),
  KEY `idx_tasks_priority` (`priority`),
  KEY `idx_tasks_due_date` (`due_date`),
  CONSTRAINT `fk_tasks_subject`
    FOREIGN KEY (`subject_id`)
    REFERENCES `subjects` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. INSERT DATA AWAL: subjects
-- Menggunakan INSERT IGNORE agar impor ulang tidak menggandakan data
INSERT IGNORE INTO `subjects` (`id`, `subject_name`) VALUES
(1, 'Matematika'),
(2, 'Bahasa Indonesia'),
(3, 'Bahasa Inggris'),
(4, 'Informatika'),
(5, 'Pendidikan Pancasila'),
(6, 'Produktif Kejuruan'),
(7, 'PJOK'),
(8, 'Seni Budaya'),
(9, 'Sejarah');

-- 5. INSERT DATA AWAL: tasks (Minimal 8 contoh tugas sekolah)
-- Tanggal pengumpulan bervariasi dengan CURDATE(), DATE_ADD(), dan DATE_SUB()
INSERT IGNORE INTO `tasks` (`id`, `subject_id`, `title`, `description`, `due_date`, `priority`, `status`) VALUES
(1, 1, 'Latihan Persamaan Kuadrat', 'Mengerjakan 10 soal latihan persamaan kuadrat di buku paket halaman 45 nomor 1 sampai 10.', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'high', 'pending'),
(2, 2, 'Merangkum Teks Eksplanasi', 'Membaca dan merangkum struktur serta ciri kebahasaan teks eksplanasi tentang fenomena alam.', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'medium', 'in_progress'),
(3, 3, 'Vocabulary Exercise', 'Menghafal dan membuat 15 kalimat contoh menggunakan kosakata Unit 3 Academic English.', CURDATE(), 'low', 'pending'),
(4, 4, 'Membuat Halaman HTML', 'Membuat layout halaman web sederhana semantik dengan tag header, nav, main, section, dan footer.', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'high', 'in_progress'),
(5, 5, 'Presentasi Nilai Pancasila', 'Menyusun materi slide PowerPoint dan studi kasus penerapan sila ke-3 dalam kehidupan bermasyarakat.', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'medium', 'pending'),
(6, 6, 'Praktik Dasar Jaringan', 'Konfigurasi IP Address Static, Subnetting IPv4 Class C, dan uji konektivitas ping antar 2 PC.', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'high', 'pending'),
(7, 7, 'Laporan Aktivitas Kebugaran', 'Mencatat hasil tes push up, sit up, dan shuttle run beserta foto dokumentasi kegiatan.', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'low', 'completed'),
(8, 8, 'Membuat Rancangan Poster Batik', 'Menggambar sketsa pola batik motif nusantara pada kertas A3 dengan teknik gradasi pensil warna.', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'medium', 'completed');
