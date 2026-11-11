<?php
/**
 * StudyList — School Task Manager
 * Delete Task Handler
 * Safely removes a task from MySQL with validation
 */
require_once __DIR__ . '/config/database.php';

$returnView = trim($_POST['view'] ?? 'overview');
if (!in_array($returnView, ['overview', 'all', 'today', 'completed', 'calendar'], true)) {
    $returnView = 'overview';
}

// Only allow POST request for safety
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

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage('error', 'ID tugas tidak valid.');
    header('Location: index.php?view=' . urlencode($returnView));
    exit;
}

try {
    // Check if task exists and fetch its title for feedback
    $checkStmt = $pdo->prepare("SELECT tasks.title, subjects.subject_name 
                                FROM tasks 
                                JOIN subjects ON tasks.subject_id = subjects.id 
                                WHERE tasks.id = ?");
    $checkStmt->execute([$id]);
    $task = $checkStmt->fetch();

    if (!$task) {
        setFlashMessage('error', 'Tugas tidak ditemukan atau sudah dihapus sebelumnya.');
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    }

    $title = $task['title'];
    $subjectName = $task['subject_name'];

    // Delete record from MySQL
    $deleteStmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $deleteStmt->execute([$id]);

    if ($deleteStmt->rowCount() > 0) {
        setFlashMessage('success', "Tugas \"" . $title . "\" (" . $subjectName . ") berhasil dihapus dari database MySQL.");
    } else {
        setFlashMessage('warning', "Tugas tidak ditemukan saat proses penghapusan.");
    }
} catch (PDOException $e) {
    error_log("[StudyList DELETE Error] " . $e->getMessage());
    setFlashMessage('error', 'Gagal menghapus tugas dari MySQL: ' . $e->getMessage());
}

header('Location: index.php?view=' . urlencode($returnView));
exit;
