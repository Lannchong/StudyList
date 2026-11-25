<?php
// SCRIPT AJAX SIMPAN URUTAN TUGAS (DRAG & DROP) 
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/database.php';

// Hanya izinkan method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metode permintaan tidak valid. Hanya POST yang diizinkan.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database MySQL tidak tersedia.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Terima data urutan ID tugas dari JavaScript (JSON atau POST)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$taskIds = [];
if (is_array($data) && isset($data['order']) && is_array($data['order'])) {
    $taskIds = $data['order'];
} elseif (isset($_POST['order'])) {
    if (is_array($_POST['order'])) {
        $taskIds = $_POST['order'];
    } elseif (is_string($_POST['order'])) {
        $decoded = json_decode($_POST['order'], true);
        if (is_array($decoded)) {
            $taskIds = $decoded;
        }
    }
}

// Validasi array task IDs
if (empty($taskIds)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Data urutan tugas (order) tidak ditemukan atau kosong.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Bersihkan data ID tugas
$cleanIds = [];
foreach ($taskIds as $id) {
    $intId = (int)$id;
    if ($intId > 0 && !in_array($intId, $cleanIds, true)) {
        $cleanIds[] = $intId;
    }
}

if (empty($cleanIds)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Daftar ID tugas tidak valid.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Mulai transaksi database
    $pdo->beginTransaction();

    // === SCRIPT QUERY UPDATE URUTAN TUGAS DI DATABASE MYSQL ===
    $stmt = $pdo->prepare("UPDATE tasks SET sort_order = ? WHERE id = ?"); // <-- QUERY UPDATE SORT_ORDER

    $orderIndex = 1;
    foreach ($cleanIds as $id) {
        $stmt->execute([$orderIndex, $id]); // Simpan nomor urut (1, 2, 3, ...)
        $orderIndex++;
    }

    $pdo->commit(); // Simpan permanen ke MySQL

    echo json_encode([
        'success' => true,
        'message' => 'Urutan tugas berhasil disimpan.',
        'updated_count' => count($cleanIds)
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Batalkan jika terjadi error
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memperbarui urutan tugas: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

