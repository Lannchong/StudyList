<?php
// Atur zona waktu ke Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Mulai session untuk simpan pesan notifikasi
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Cegah browser simpan cache halaman lama
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
}

// === SETTING DATABASE MYSQL (Ubah di sini jika nama DB / password berbeda) ===
$dbHost     = '127.0.0.1';           // Alamat server MySQL
$dbPort     = '3306';                // Port MySQL
$dbName     = 'school_todolist_db';  // Nama database
$dbUser     = 'root';                // Username MySQL (default: root)
$dbPass     = '';                    // Password MySQL (default: kosong)
$dbCharset  = 'utf8mb4';             // Karakter teks UTF-8

$pdo = null;
$dbError = null;

// === SCRIPT UTAMA PENGHUBUNG KE DATABASE MYSQL (PDO) ===
try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Tampilkan pesan error jika query gagal
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Ambil data dalam bentuk array
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Mencegah SQL Injection
        PDO::ATTR_AUTOCOMMIT         => true,                   // Otomatis simpan perubahan data
    ];

    // Script penghubung PHP ke database MySQL
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options); // <-- INI SCRIPT KONEKSI KE DATABASE

    // Pastikan kolom sort_order dan due_time sudah ada di tabel tasks
    ensureTasksTableColumns($pdo);

} catch (PDOException $e) {
    // Pesan jika koneksi database gagal
    $dbError = "Gagal terhubung ke database '{$dbName}'. Pastikan MySQL pada XAMPP/Laragon sudah aktif.";
}

// Tambah kolom sort_order & due_time otomatis jika belum ada di database
function ensureTasksTableColumns($pdo) {
    if (!$pdo) return;
    try {
        $checkSort = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'sort_order'");
        if (!$checkSort->fetch()) {
            $pdo->exec("ALTER TABLE tasks ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER status");
            $pdo->exec("ALTER TABLE tasks ADD INDEX idx_tasks_sort_order (sort_order)");
            $pdo->exec("UPDATE tasks SET sort_order = id WHERE sort_order = 0");
        }

        $checkTime = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'due_time'");
        if (!$checkTime->fetch()) {
            $pdo->exec("ALTER TABLE tasks ADD COLUMN due_time TIME NULL AFTER due_date");
        }
    } catch (Throwable $e) {
        // Abaikan jika database belum siap
    }
}

// Ambil semua data mata pelajaran dari database
function getAllSubjects($pdo) {
    if (!$pdo) return [];
    try {
        $stmt = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY id ASC"); // Query ambil mata pelajaran
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// Bersihkan teks agar aman dari script berbahaya (Anti-XSS)
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Format jam jadi "14.30 WIB"
function formatDueTime($timeStr) {
    if (empty($timeStr)) return '';
    $clean = substr(trim($timeStr), 0, 5); // Ambil format jam menit HH:MM
    if (!preg_match('/^\d{2}:\d{2}$/', $clean)) return e($timeStr);
    $formatted = str_replace(':', '.', $clean);
    return "{$formatted} WIB";
}

// Format tanggal ke bahasa Indonesia (contoh: "Hari ini · 12 Nov 2026")
function formatDueDate($dateStr, $timeStr = null) {
    if (empty($dateStr)) return 'Tanpa tenggat';

    $cleanDate = substr(trim($dateStr), 0, 10);
    $parts = explode('-', $cleanDate);
    if (count($parts) !== 3) return e($dateStr);

    $year = (int)$parts[0];
    $month = (int)$parts[1];
    $day = (int)$parts[2];

    if (!checkdate($month, $day, $year)) return e($dateStr);

    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    $monthName = $months[$month] ?? '';
    $formatted = "{$day} {$monthName} {$year}";

    // Cek apakah tanggal hari ini, besok, atau kemarin
    $todayDate = date('Y-m-d');
    $tomorrowDate = date('Y-m-d', strtotime('+1 day'));
    $yesterdayDate = date('Y-m-d', strtotime('-1 day'));

    if ($cleanDate === $todayDate) {
        $datePart = "Hari ini · {$formatted}";
    } elseif ($cleanDate === $tomorrowDate) {
        $datePart = "Besok · {$formatted}";
    } elseif ($cleanDate === $yesterdayDate) {
        $datePart = "Kemarin · {$formatted}";
    } else {
        $datePart = $formatted;
    }

    if (!empty($timeStr)) {
        $timePart = formatDueTime($timeStr);
        if ($timePart !== '') {
            return "{$datePart} · {$timePart}";
        }
    }

    return $datePart;
}

// Cek status deadline (merah/overdue jika lewat waktu, biru/today jika hari ini)
function getTaskDueStatus($dueDate, $dueTime = null, $isCompleted = false) {
    if (empty($dueDate) || $isCompleted) return ''; // Tugas selesai tidak dihitung overdue

    $cleanDate = substr(trim($dueDate), 0, 10);
    $now = time();
    $todayDate = date('Y-m-d');

    if (!empty($dueTime)) {
        $cleanTime = substr(trim($dueTime), 0, 5);
        $taskTimestamp = strtotime("{$cleanDate} {$cleanTime}:00"); // Hitung sampai jam menit
    } else {
        $taskTimestamp = strtotime("{$cleanDate} 23:59:59"); // Jika tanpa jam, batasnya akhir hari
    }

    if ($taskTimestamp < $now) {
        return 'is-overdue'; // Tenggat sudah lewat (merah)
    }

    if ($cleanDate === $todayDate) {
        return 'is-today'; // Tenggat hari ini (biru)
    }

    return '';
}

// Simpan pesan notifikasi sementara (flash message)
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Ambil pesan notifikasi lalu hapus dari session
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}
