<?php
// PROSES HAPUS TUGAS DARI DATABASE (DELETE) 
require_once __DIR__ . '/config/database.php';

$returnView = trim($_POST['view'] ?? 'all');
if (!in_array($returnView, ['all', 'today', 'completed', 'calendar'], true)) {
    $returnView = 'all';
}

// Hanya izinkan request method POST (keamanan form)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Metode request tidak diizinkan.');
    header('Location: index.php?view=' . urlencode($returnView));
    exit;
}

if (!$pdo) {
    setFlashMessage('error', 'Koneksi database tidak tersedia.');
    header('Location: index.php?view=' . urlencode($returnView));
    exit;
}

// Ambil ID tugas yang akan dihapus
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage('error', 'ID tugas tidak valid.');
    header('Location: index.php?view=' . urlencode($returnView));
    exit;
}

try {
    // Ambil judul tugas untuk pesan notifikasi
    $checkStmt = $pdo->prepare("SELECT tasks.title FROM tasks WHERE tasks.id = ?");
    $checkStmt->execute([$id]);
    $task = $checkStmt->fetch();

    if (!$task) {
        setFlashMessage('error', 'Tugas tidak ditemukan.');
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    }

    $title = $task['title'];

    // === SCRIPT QUERY HAPUS DATA DARI DATABASE MYSQL ===
    $deleteStmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?"); // <-- QUERY DELETE
    $deleteStmt->execute([$id]); // Eksekusi hapus data

    setFlashMessage('success', "Tugas \"{$title}\" berhasil dihapus.");
} catch (PDOException $e) {
    error_log("[StudyList DELETE Error] " . $e->getMessage());
    setFlashMessage('error', 'Gagal menghapus tugas dari database: ' . $e->getMessage());
}

// Kembali ke halaman daftar tugas
header('Location: index.php?view=' . urlencode($returnView));
exit;
