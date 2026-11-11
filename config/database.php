<?php
/**
 * StudyList — School Task Manager
 * Database Connection & Configuration File
 * Uses PHP Data Objects (PDO) with MySQL
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent browser from caching dynamic database contents
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
}

// Database Credentials (Standard XAMPP / Local MySQL setup)
$dbHost     = '127.0.0.1'; // 127.0.0.1 avoids IPv6 localhost resolution delay on Windows
$dbPort     = '3306';
$dbName     = 'school_todolist_db';
$dbUser     = 'root';
$dbPass     = '';
$dbCharset  = 'utf8mb4';

$pdo = null;
$dbError = null;

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_AUTOCOMMIT         => true,
    ];

    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // Save error message cleanly without leaking database credentials
    $dbError = "Gagal terhubung ke database '{$dbName}'. Pastikan MySQL pada XAMPP sudah aktif dan file 'database/school_todolist_db.sql' sudah diimpor ke phpMyAdmin.";
}

/**
 * Retrieve all subjects from the database
 *
 * @param PDO|null $pdo
 * @return array
 */
function getAllSubjects($pdo) {
    if (!$pdo) {
        return [];
    }
    try {
        $stmt = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY id ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Helper to escape output and prevent XSS (Cross-Site Scripting)
 *
 * @param mixed $value
 * @return string
 */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format a SQL date (YYYY-MM-DD) into Indonesian readable format
 *
 * @param string|null $dateStr
 * @return string
 */
function formatDueDate($dateStr) {
    if (empty($dateStr)) {
        return 'Tanpa tenggat';
    }

    $targetDate = strtotime($dateStr);
    if (!$targetDate) {
        return e($dateStr);
    }

    $today = strtotime(date('Y-m-d'));
    $diffDays = (int)round(($targetDate - $today) / 86400);

    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    $d = date('j', $targetDate);
    $m = $months[(int)date('n', $targetDate)];
    $y = date('Y', $targetDate);
    $formatted = "{$d} {$m} {$y}";

    if ($diffDays === 0) {
        return "Hari ini ({$formatted})";
    } elseif ($diffDays === 1) {
        return "Besok ({$formatted})";
    } elseif ($diffDays === -1) {
        return "Kemarin ({$formatted})";
    }

    return $formatted;
}

/**
 * Set a session flash message
 *
 * @param string $type 'success' | 'error' | 'warning' | 'info'
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear the session flash message
 *
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}
