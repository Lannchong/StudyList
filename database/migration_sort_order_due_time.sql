-- STUDYLIST — DATABASE MIGRATION
-- Migration: Menambahkan kolom sort_order dan due_time pada tabel tasks
-- Database: school_todolist_db

USE `school_todolist_db`;

-- 1. Tambahkan kolom sort_order (urutan tugas) jika belum ada
SET @col_sort_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'tasks' 
      AND COLUMN_NAME = 'sort_order'
);

SET @sql_sort = IF(@col_sort_exists = 0, 
    'ALTER TABLE `tasks` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0 AFTER `status`, ADD INDEX `idx_tasks_sort_order` (`sort_order`)', 
    'SELECT "Kolom sort_order sudah tersedia"'
);
PREPARE stmt_sort FROM @sql_sort;
EXECUTE stmt_sort;
DEALLOCATE PREPARE stmt_sort;

-- 2. Tambahkan kolom due_time (jam pengumpulan) jika belum ada
SET @col_time_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'tasks' 
      AND COLUMN_NAME = 'due_time'
);

SET @sql_time = IF(@col_time_exists = 0, 
    'ALTER TABLE `tasks` ADD COLUMN `due_time` TIME NULL AFTER `due_date`', 
    'SELECT "Kolom due_time sudah tersedia"'
);
PREPARE stmt_time FROM @sql_time;
EXECUTE stmt_time;
DEALLOCATE PREPARE stmt_time;

-- 3. Inisialisasi nilai awal sort_order berdasarkan ID tugas yang sudah ada
-- Agar urutan tugas tetap rapi dan konsisten sesuai urutan sebelumnya
UPDATE `tasks` SET `sort_order` = `id` WHERE `sort_order` = 0;

