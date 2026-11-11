<?php
/**
 * StudyList — Editorial Study Planner
 * Create Task Handler & Standalone Form
 * Ensures data is 100% saved to MySQL tasks table with valid subject_id
 */
require_once __DIR__ . '/config/database.php';

// Fetch all subjects for validation & form
$subjects = getAllSubjects($pdo);

// Process POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnView = trim($_POST['view'] ?? 'overview');
    if (!in_array($returnView, ['overview', 'all', 'today', 'completed', 'calendar'], true)) {
        $returnView = 'overview';
    }

    if (!$pdo) {
        setFlashMessage('error', 'Koneksi database tidak tersedia. Pastikan MySQL sudah aktif.');
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $dueDate = trim($_POST['due_date'] ?? '');
    $priority = strtolower(trim($_POST['priority'] ?? 'medium'));
    $status = strtolower(trim($_POST['status'] ?? 'pending'));

    $errors = [];

    // 1. Validate Task Title
    if ($title === '') {
        $errors[] = 'Nama tugas wajib diisi.';
    } elseif (mb_strlen($title) > 150) {
        $errors[] = 'Nama tugas maksimal 150 karakter.';
    }

    // 2. Validate Subject directly from MySQL
    if ($subjectId <= 0) {
        $errors[] = 'Mata pelajaran wajib dipilih.';
    } else {
        $checkSubj = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE id = ?");
        $checkSubj->execute([$subjectId]);
        $subjectRow = $checkSubj->fetch();
        if (!$subjectRow) {
            $errors[] = 'Mata pelajaran yang dipilih tidak valid atau belum terdaftar di database MySQL.';
        }
    }

    // 3. Validate Priority
    if (!in_array($priority, ['low', 'medium', 'high'], true)) {
        $priority = 'medium';
    }

    // 4. Validate Status
    if (!in_array($status, ['pending', 'in_progress', 'completed'], true)) {
        $status = 'pending';
    }

    // 5. Validate & Format Due Date
    $dueDateVal = null;
    if ($dueDate !== '') {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) && strtotime($dueDate)) {
            $dueDateVal = $dueDate;
        } else {
            $errors[] = 'Format tanggal pengumpulan tidak valid (gunakan format YYYY-MM-DD).';
        }
    }

    $descVal = ($description === '') ? null : $description;

    if (!empty($errors)) {
        setFlashMessage('error', implode(' ', $errors));
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    }

    // 6. Execute INSERT into MySQL tasks table
    try {
        $sql = "INSERT INTO tasks (subject_id, title, description, due_date, priority, status)
                VALUES (:subject_id, :title, :description, :due_date, :priority, :status)";
        $stmt = $pdo->prepare($sql);
        $executed = $stmt->execute([
            ':subject_id'  => $subjectId,
            ':title'       => $title,
            ':description' => $descVal,
            ':due_date'    => $dueDateVal,
            ':priority'    => $priority,
            ':status'      => $status,
        ]);

        $newTaskId = (int)$pdo->lastInsertId();

        if (!$executed || $newTaskId <= 0) {
            throw new Exception("Query penyimpanan tidak menghasilkan ID baru.");
        }

        // 7. Verify data actually exists in MySQL
        $verifyStmt = $pdo->prepare("SELECT tasks.id, tasks.title, subjects.subject_name 
                                     FROM tasks 
                                     JOIN subjects ON tasks.subject_id = subjects.id 
                                     WHERE tasks.id = ?");
        $verifyStmt->execute([$newTaskId]);
        $verifiedRow = $verifyStmt->fetch();

        if (!$verifiedRow) {
            throw new Exception("Verifikasi penyimpanan gagal: Tugas tidak ditemukan setelah proses simpan.");
        }

        setFlashMessage('success', "Tugas \"" . $verifiedRow['title'] . "\" untuk mata pelajaran " . $verifiedRow['subject_name'] . " berhasil disimpan.");
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    } catch (Throwable $e) {
        error_log("[StudyList CREATE Error] " . $e->getMessage());
        setFlashMessage('error', 'Gagal menyimpan tugas ke database: ' . $e->getMessage());
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    }
}

// Fallback: If accessed via GET, render a dedicated create page
$pageTitle = 'Tambah Tugas';
$pageSubtitle = 'Catat tugas baru dan atur jadwal pengerjaannya';
require_once __DIR__ . '/includes/header.php';
?>

<div class="editorial-standalone-card">
  <div class="standalone-header">
    <h2 class="standalone-title">Tambah Tugas</h2>
    <p class="standalone-subtitle">Catat tugas baru dan atur jadwal pengerjaannya</p>
  </div>

  <form action="create.php" method="POST" class="standalone-form">
    <input type="hidden" name="view" value="overview">

    <div class="form-group">
      <label class="form-label" for="taskTitle">Nama Tugas <span class="required">*</span></label>
      <input type="text" id="taskTitle" name="title" class="form-control" required maxlength="150" autocomplete="off">
    </div>

    <div class="form-group">
      <label class="form-label" for="taskSubjectId">Mata Pelajaran <span class="required">*</span></label>
      <div class="select-wrapper">
        <select id="taskSubjectId" name="subject_id" class="form-control" required>
          <option value="">Pilih Mata Pelajaran</option>
          <?php foreach ($subjects as $subj): ?>
            <option value="<?= (int)$subj['id'] ?>"><?= e($subj['subject_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="taskDesc">Deskripsi</label>
      <textarea id="taskDesc" name="description" class="form-control" rows="4"></textarea>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label class="form-label" for="taskDueDate">Tanggal Pengumpulan</label>
        <input type="date" id="taskDueDate" name="due_date" class="form-control">
      </div>
      <div class="form-group">
        <label class="form-label" for="taskPriority">Prioritas</label>
        <div class="select-wrapper">
          <select id="taskPriority" name="priority" class="form-control">
            <option value="low">Rendah</option>
            <option value="medium" selected>Sedang</option>
            <option value="high">Tinggi</option>
          </select>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="taskStatus">Status Pengerjaan</label>
      <div class="select-wrapper">
        <select id="taskStatus" name="status" class="form-control">
          <option value="pending" selected>Belum Dikerjakan</option>
          <option value="in_progress">Sedang Dikerjakan</option>
          <option value="completed">Selesai</option>
        </select>
      </div>
    </div>

    <div class="standalone-actions">
      <a href="index.php" class="btn btn-editorial-outline">Batal</a>
      <button type="submit" class="btn btn-editorial-primary">Simpan Tugas</button>
    </div>
  </form>
</div>

<?php
$modalSubjects = $subjects;
require_once __DIR__ . '/includes/footer.php';
?>
