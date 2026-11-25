<?php
// PROSES SIMPAN TUGAS BARU (INSERT) 
require_once __DIR__ . '/config/database.php';

// Ambil daftar mata pelajaran untuk pilihan dropdown
$subjects = getAllSubjects($pdo);

// Proses saat tombol submit ditekan (Method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnView = trim($_POST['view'] ?? 'all');
    if (!in_array($returnView, ['all', 'today', 'completed', 'calendar'], true)) {
        $returnView = 'all';
    }

    if (!$pdo) {
        setFlashMessage('error', 'Koneksi database tidak tersedia. Pastikan MySQL sudah aktif.');
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    }

    // Ambil data yang dikirim dari form
    $title = trim($_POST['title'] ?? '');               // Nama tugas
    $subjectId = (int)($_POST['subject_id'] ?? 0);      // ID mata pelajaran
    $description = trim($_POST['description'] ?? '');  // Deskripsi tugas
    $dueDate = trim($_POST['due_date'] ?? '');          // Tanggal tenggat
    $dueTime = trim($_POST['due_time'] ?? '');          // Jam pengumpulan
    $priority = strtolower(trim($_POST['priority'] ?? 'medium')); // Prioritas (low, medium, high)
    $status = strtolower(trim($_POST['status'] ?? 'pending'));    // Status pengerjaan

    // Validasi data input
    $errors = [];

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
            $errors[] = 'Mata pelajaran yang dipilih tidak valid.';
        }
    }

    if (!in_array($priority, ['low', 'medium', 'high'], true)) {
        $priority = 'medium';
    }

    if (!in_array($status, ['pending', 'in_progress', 'completed'], true)) {
        $status = 'pending';
    }

    // Validasi tanggal tenggat
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
        // Mulai transaksi database
        $pdo->beginTransaction();

        // Geser nomor urut tugas lama ke bawah (+1) agar tugas baru di posisi 1 (paling atas)
        $pdo->exec("UPDATE tasks SET sort_order = COALESCE(sort_order, 0) + 1");

        // === SCRIPT QUERY SIMPAN TUGAS KE DATABASE MYSQL (INSERT) ===
        $sql = "INSERT INTO tasks (subject_id, title, description, due_date, due_time, priority, status, sort_order)
                VALUES (:subject_id, :title, :description, :due_date, :due_time, :priority, :status, 1)";
        $stmt = $pdo->prepare($sql); // <-- PREPARE QUERY INSERT
        $stmt->execute([             // <-- EKSEKUSI SIMPAN KE DATABASE
            ':subject_id'  => $subjectId,
            ':title'       => $title,
            ':description' => $descVal,
            ':due_date'    => $dueDateVal,
            ':due_time'    => $dueTimeVal,
            ':priority'    => $priority,
            ':status'      => $status,
        ]);

        $newTaskId = (int)$pdo->lastInsertId();
        $pdo->commit(); // Simpan transaksi ke database

        setFlashMessage('success', "Tugas \"{$title}\" berhasil disimpan.");
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    } catch (Throwable $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack(); // Batalkan jika terjadi error
        }
        error_log("[StudyList CREATE Error] " . $e->getMessage());
        setFlashMessage('error', 'Gagal menyimpan tugas ke database: ' . $e->getMessage());
        header('Location: index.php?view=' . urlencode($returnView));
        exit;
    }
}

// Tampilan form fallback jika halaman diakses langsung melalui GET
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
    <input type="hidden" name="view" value="all">

    <div class="form-group">
      <label class="form-label" for="taskTitle">Nama Tugas <span class="required">*</span></label>
      <input type="text" id="taskTitle" name="title" class="form-control" required maxlength="150" autocomplete="off">
    </div>

    <div class="form-group">
      <div class="form-label-row">
        <label class="form-label" for="taskSubjectId">Mata Pelajaran <span class="required">*</span></label>
        <button type="button" class="form-action-link js-open-subject-modal" title="Tambah Mata Pelajaran Baru">+ Tambah Mapel</button>
      </div>
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
