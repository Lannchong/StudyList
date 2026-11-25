<?php
// PROSES TAMBAH MATA PELAJARAN BARU (INSERT)
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

// Deteksi apakah pemanggilan melalui AJAX (JavaScript fetch)
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Hanya menerima request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
    } else {
        header('Location: index.php');
    }
    exit;
}

$returnView = trim($_POST['view'] ?? 'all');
if (!in_array($returnView, ['all', 'today', 'completed', 'calendar'], true)) {
    $returnView = 'all';
}

if (!$pdo) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Koneksi database tidak tersedia.']);
    } else {
        setFlashMessage('error', 'Koneksi database gagal. Pastikan MySQL sudah aktif.');
        header('Location: index.php?view=' . urlencode($returnView));
    }
    exit;
}

// Ambil input nama mata pelajaran
$subjectName = trim($_POST['subject_name'] ?? '');

// Validasi input
if ($subjectName === '') {
    $msg = 'Nama mata pelajaran wajib diisi.';
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $msg]);
    } else {
        setFlashMessage('error', $msg);
        header('Location: index.php?view=' . urlencode($returnView));
    }
    exit;
}

if (mb_strlen($subjectName) > 80) {
    $msg = 'Nama mata pelajaran maksimal 80 karakter.';
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $msg]);
    } else {
        setFlashMessage('error', $msg);
        header('Location: index.php?view=' . urlencode($returnView));
    }
    exit;
}

try {
    // Cek apakah mata pelajaran sudah ada (case-insensitive)
    $checkStmt = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE LOWER(subject_name) = LOWER(?)");
    $checkStmt->execute([$subjectName]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success'      => true,
                'message'      => "Mata pelajaran \"{$existing['subject_name']}\" sudah ada.",
                'id'           => (int)$existing['id'],
                'subject_name' => $existing['subject_name'],
                'is_existing'  => true
            ], JSON_UNESCAPED_UNICODE);
        } else {
            setFlashMessage('info', "Mata pelajaran \"{$existing['subject_name']}\" sudah ada dalam daftar.");
            header('Location: index.php?view=' . urlencode($returnView));
        }
        exit;
    }

    // === SCRIPT QUERY SIMPAN MATA PELAJARAN KE DATABASE (INSERT) ===
    $stmt = $pdo->prepare("INSERT INTO subjects (subject_name) VALUES (?)"); // <-- PREPARE QUERY INSERT
    $stmt->execute([$subjectName]);                                         // <-- EKSEKUSI SIMPAN
    $newId = (int)$pdo->lastInsertId();

    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success'      => true,
            'message'      => "Mata pelajaran \"{$subjectName}\" berhasil ditambahkan.",
            'id'           => $newId,
            'subject_name' => $subjectName,
            'is_existing'  => false
        ], JSON_UNESCAPED_UNICODE);
    } else {
        setFlashMessage('success', "Mata pelajaran \"{$subjectName}\" berhasil ditambahkan.");
        header('Location: index.php?view=' . urlencode($returnView));
    }
    exit;

} catch (Throwable $e) {
    error_log("[StudyList CREATE SUBJECT Error] " . $e->getMessage());
    $msg = 'Gagal menyimpan mata pelajaran: ' . $e->getMessage();
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $msg]);
    } else {
        setFlashMessage('error', $msg);
        header('Location: index.php?view=' . urlencode($returnView));
    }
    exit;
}
