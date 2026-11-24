<?php
// === PROSES SIMPAN PERUBAHAN TUGAS (UPDATE) ===
require_once __DIR__ . '/config/database.php';

// Ambil daftar mata pelajaran untuk pilihan dropdown
$subjects = getAllSubjects($pdo);

// Proses saat tombol simpan perubahan ditekan (Method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnView = trim($_POST['view'] ?? 'all');
    if (!in_array($returnView, ['all', 'today', 'completed', 'calendar'], true)) {
        $returnView = 'all';
    }

    if (!$pdo) {
        setFlashMessage('error', 'Koneksi database tidak tersedia.');
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    }

    // Ambil data yang dikirim dari form edit
    $id = (int)($_POST['id'] ?? 0);                     // ID tugas yang diedit
    $title = trim($_POST['title'] ?? '');               // Nama tugas baru
    $subjectId = (int)($_POST['subject_id'] ?? 0);      // ID mata pelajaran baru
    $description = trim($_POST['description'] ?? '');  // Deskripsi baru
    $dueDate = trim($_POST['due_date'] ?? '');          // Tanggal tenggat baru
    $dueTime = trim($_POST['due_time'] ?? '');          // Jam pengumpulan baru
    $priority = strtolower(trim($_POST['priority'] ?? 'medium')); // Prioritas baru
    $status = strtolower(trim($_POST['status'] ?? 'pending'));    // Status baru

    // Validasi data input
    $errors = [];

    // Validasi ID tugas
    if ($id <= 0) {
        $errors[] = 'ID tugas tidak valid.';
    } else {
        $checkTask = $pdo->prepare("SELECT id FROM tasks WHERE id = ?");
        $checkTask->execute([$id]);
        if (!$checkTask->fetch()) {
            $errors[] = 'Tugas yang ingin diperbarui tidak ditemukan di database.';
        }
    }

    // Validasi nama tugas
    if ($title === '') {
        $errors[] = 'Nama tugas wajib diisi.';
    } elseif (mb_strlen($title) > 150) {
        $errors[] = 'Nama tugas maksimal 150 karakter.';
    }

    // Validasi mata pelajaran
    if ($subjectId <= 0) {
        $errors[] = 'Mata pelajaran wajib dipilih.';
    } else {
        $checkSubj = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE id = ?");
        $checkSubj->execute([$subjectId]);
        $subjectRow = $checkSubj->fetch();
        if (!$subjectRow) {
            $errors[] = 'Mata pelajaran yang dipilih tidak valid di database.';
        }
    }

    if (!in_array($priority, ['low', 'medium', 'high'], true)) {
        $priority = 'medium';
    }

    if (!in_array($status, ['pending', 'in_progress', 'completed'], true)) {
        $status = 'pending';
    }

    // Validasi tanggal pengumpulan
    $dueDateVal = null;
    if ($dueDate !== '') {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) && strtotime($dueDate)) {
            $dueDateVal = $dueDate;
        } else {
            $errors[] = 'Format tanggal pengumpulan tidak valid (gunakan YYYY-MM-DD).';
        }
    }

    // Validasi jam pengumpulan: jika jam diisi, tanggal wajib dipilih
    $dueTimeVal = null;
    if ($dueTime !== '') {
        if ($dueDateVal === null) {
            $errors[] = 'Tanggal pengumpulan wajib diisi jika jam pengumpulan ditentukan.';
        } elseif (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $dueTime)) {
            $dueTimeVal = substr($dueTime, 0, 5) . ':00';
        } else {
            $errors[] = 'Format jam pengumpulan tidak valid (gunakan format 24 jam HH:MM).';
        }
    }

    $descVal = ($description === '') ? null : $description;

    // Jika ada error, kembali dan tampilkan pesan kesalahan
    if (!empty($errors)) {
        setFlashMessage('error', implode(' ', $errors));
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    }

    try {
        // === SCRIPT QUERY PERBARUI DATA TUGAS DI DATABASE MYSQL (UPDATE) ===
        $sql = "UPDATE tasks
                SET subject_id = :subject_id,
                    title = :title,
                    description = :description,
                    due_date = :due_date,
                    due_time = :due_time,
                    priority = :priority,
                    status = :status
                WHERE id = :id";
        $stmt = $pdo->prepare($sql); // <-- PREPARE QUERY UPDATE
        $stmt->execute([             // <-- EKSEKUSI PERUBAHAN KE DATABASE
            ':subject_id'  => $subjectId,
            ':title'       => $title,
            ':description' => $descVal,
            ':due_date'    => $dueDateVal,
            ':due_time'    => $dueTimeVal,
            ':priority'    => $priority,
            ':status'      => $status,
            ':id'          => $id,
        ]);

        setFlashMessage('success', "Tugas \"{$title}\" berhasil diperbarui.");
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    } catch (Throwable $e) {
        error_log("[StudyList UPDATE Error] " . $e->getMessage());
        setFlashMessage('error', 'Gagal memperbarui tugas: ' . $e->getMessage());
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    }
}

// Mengambil data tugas untuk form fallback (GET)
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0 || !$pdo) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch();

    if (!$task) {
        setFlashMessage('error', 'Tugas tidak ditemukan.');
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    setFlashMessage('error', 'Error database: ' . $e->getMessage());
    header('Location: index.php');
    exit;
}

$pageTitle = 'Edit Tugas';
$pageSubtitle = 'Perbarui informasi dan jadwal pengerjaan tugas';
require_once __DIR__ . '/includes/header.php';
?>

<div class="editorial-standalone-card">
  <div class="standalone-header">
    <h2 class="standalone-title">Edit Tugas</h2>
    <p class="standalone-subtitle">Perbarui informasi dan jadwal pengerjaan tugas</p>
  </div>

  <form action="update.php" method="POST" class="standalone-form">
    <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
    <input type="hidden" name="view" value="all">

    <div class="form-group">
      <label class="form-label" for="taskTitle">Nama Tugas <span class="required">*</span></label>
      <input type="text" id="taskTitle" name="title" class="form-control" value="<?= e($task['title']) ?>" required maxlength="150" autocomplete="off">
    </div>

    <div class="form-group">
      <label class="form-label" for="taskSubjectId">Mata Pelajaran <span class="required">*</span></label>
      <div class="select-wrapper">
        <select id="taskSubjectId" name="subject_id" class="form-control" required>
          <option value="">Pilih Mata Pelajaran</option>
          <?php foreach ($subjects as $subj): ?>
            <option value="<?= (int)$subj['id'] ?>" <?= ((int)$task['subject_id'] === (int)$subj['id']) ? 'selected' : '' ?>>
              <?= e($subj['subject_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="taskDesc">Deskripsi</label>
      <textarea id="taskDesc" name="description" class="form-control" rows="4"><?= e($task['description'] ?? '') ?></textarea>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label class="form-label" for="taskDueDate">Tanggal Pengumpulan</label>
        <input type="date" id="taskDueDate" name="due_date" class="form-control" value="<?= e($task['due_date'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="taskPriority">Prioritas</label>
        <div class="select-wrapper">
          <select id="taskPriority" name="priority" class="form-control">
            <option value="low" <?= ($task['priority'] === 'low') ? 'selected' : '' ?>>Rendah</option>
            <option value="medium" <?= ($task['priority'] === 'medium') ? 'selected' : '' ?>>Sedang</option>
            <option value="high" <?= ($task['priority'] === 'high') ? 'selected' : '' ?>>Tinggi</option>
          </select>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="taskStatus">Status Pengerjaan</label>
      <div class="select-wrapper">
        <select id="taskStatus" name="status" class="form-control">
          <option value="pending" <?= ($task['status'] === 'pending') ? 'selected' : '' ?>>Belum Dikerjakan</option>
          <option value="in_progress" <?= ($task['status'] === 'in_progress') ? 'selected' : '' ?>>Sedang Dikerjakan</option>
          <option value="completed" <?= ($task['status'] === 'completed') ? 'selected' : '' ?>>Selesai</option>
        </select>
      </div>
    </div>

    <div class="standalone-actions">
      <a href="index.php" class="btn btn-editorial-outline">Batal</a>
      <button type="submit" class="btn btn-editorial-primary">Simpan Perubahan</button>
    </div>
  </form>
</div>

<?php
$modalSubjects = $subjects;
require_once __DIR__ . '/includes/footer.php';
?>
