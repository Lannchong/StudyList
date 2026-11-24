<?php
// === HALAMAN UTAMA: DAFTAR TUGAS, FILTER & KALENDER ===
require_once __DIR__ . '/config/database.php';

// Halaman yang sedang aktif (default: semua tugas)
$currentView = $_GET['view'] ?? 'all';
$allowedViews = ['all', 'calendar', 'today', 'completed'];
if (!in_array($currentView, $allowedViews, true)) {
    $currentView = 'all';
}

// Judul halaman berdasarkan menu yang dipilih
switch ($currentView) {
    case 'calendar':
        $pageTitle = 'Kalender Tugas';
        $pageSubtitle = 'Jadwal bulanan tenggat tugas sekolah dan aktivitas belajar';
        break;
    case 'today':
        $pageTitle = 'Hari Ini';
        $pageSubtitle = 'Daftar tugas sekolah dengan tenggat waktu hari ini (' . date('d M Y') . ')';
        break;
    case 'completed':
        $pageTitle = 'Selesai';
        $pageSubtitle = 'Arsip seluruh tugas dan aktivitas sekolah yang telah diselesaikan';
        break;
    case 'all':
    default:
        $pageTitle = 'Semua Tugas';
        $pageSubtitle = 'Daftar lengkap seluruh tugas dan catatan belajar sekolah';
        break;
}

// Ambil daftar mata pelajaran dari database
$subjects = getAllSubjects($pdo);

// Filter kondisi WHERE query
$whereConditions = [];
$params = [];

if ($currentView === 'today') {
    $whereConditions[] = "tasks.due_date = CURDATE()"; // Tugas hari ini
} elseif ($currentView === 'completed') {
    $whereConditions[] = "tasks.status = 'completed'"; // Tugas selesai
}

// Filter tab status: Semua / Aktif / Selesai
$statusTab = trim($_GET['tab'] ?? 'all');
if (!in_array($statusTab, ['all', 'active', 'completed'], true)) {
    $statusTab = 'all';
}

if ($statusTab === 'active' && $currentView !== 'completed') {
    $whereConditions[] = "tasks.status IN ('pending', 'in_progress')";
} elseif ($statusTab === 'completed' && $currentView !== 'completed') {
    $whereConditions[] = "tasks.status = 'completed'";
}

// Filter pencarian nama / deskripsi tugas
$searchQuery = trim($_GET['q'] ?? '');
if ($searchQuery !== '') {
    $whereConditions[] = "(tasks.title LIKE ? OR tasks.description LIKE ?)";
    $params[] = '%' . $searchQuery . '%';
    $params[] = '%' . $searchQuery . '%';
}

// Filter dropdown mata pelajaran
$subjectFilter = (int)($_GET['subject_id'] ?? 0);
if ($subjectFilter > 0) {
    $whereConditions[] = "tasks.subject_id = ?";
    $params[] = $subjectFilter;
}

// Filter dropdown status (pending, in_progress, completed)
$statusFilter = trim($_GET['status'] ?? '');
if ($currentView !== 'completed' && in_array($statusFilter, ['pending', 'in_progress', 'completed'], true)) {
    $whereConditions[] = "tasks.status = ?";
    $params[] = $statusFilter;
}

// Filter dropdown prioritas (low, medium, high)
$priorityFilter = trim($_GET['priority'] ?? '');
if (in_array($priorityFilter, ['low', 'medium', 'high'], true)) {
    $whereConditions[] = "tasks.priority = ?";
    $params[] = $priorityFilter;
}

// Drag & drop hanya aktif di Semua Tugas tanpa filter atau pencarian
$canReorder = ($currentView === 'all' && $searchQuery === '' && $subjectFilter <= 0 && $statusFilter === '' && $priorityFilter === '' && $statusTab === 'all');

$totalTasks = 0;
$activeTasks = 0;
$completedTasks = 0;
$tasks = [];
$calendarTasks = [];

if ($pdo) {
    try {
        // === SCRIPT HITUNG STATISTIK TUGAS DARI DATABASE ===
        $statTotalStmt = $pdo->query("SELECT COUNT(*) FROM tasks"); // Total tugas
        $totalTasks = (int)$statTotalStmt->fetchColumn();

        $statActiveStmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'in_progress')"); // Belum selesai
        $activeTasks = (int)$statActiveStmt->fetchColumn();

        $statCompletedStmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'"); // Selesai
        $completedTasks = (int)$statCompletedStmt->fetchColumn();

        // Ambil data untuk tampilan kalender jika menu kalender dibuka
        if ($currentView === 'calendar') {
            $calStmt = $pdo->query("SELECT tasks.*, subjects.subject_name 
                                    FROM tasks 
                                    JOIN subjects ON tasks.subject_id = subjects.id 
                                    WHERE tasks.due_date IS NOT NULL 
                                    ORDER BY tasks.due_date ASC, 
                                             CASE WHEN tasks.due_time IS NULL OR tasks.due_time = '' THEN 1 ELSE 0 END ASC,
                                             tasks.due_time ASC,
                                             CASE tasks.priority 
                                                 WHEN 'high' THEN 1 
                                                 WHEN 'medium' THEN 2 
                                                 ELSE 3 
                                             END ASC");
            $calendarTasks = $calStmt->fetchAll();
        }

        // === SCRIPT QUERY AMBIL DAFTAR TUGAS DARI DATABASE MYSQL (SELECT) ===
        $sql = "SELECT tasks.*, subjects.subject_name
                FROM tasks
                JOIN subjects ON tasks.subject_id = subjects.id"; // Hubungkan tabel tasks & subjects

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions); // Pasang filter WHERE jika ada
        }

        // Urutan tugas: tugas baru di atas (sort_order 1 & id DESC)
        if ($canReorder) {
            $sql .= " ORDER BY tasks.sort_order ASC, tasks.id DESC";
        } else {
            $sql .= " ORDER BY 
                      CASE WHEN tasks.status = 'completed' THEN 2 ELSE 1 END ASC,
                      tasks.sort_order ASC,
                      CASE WHEN tasks.due_date IS NULL THEN 1 ELSE 0 END ASC,
                      tasks.due_date ASC,
                      tasks.id DESC";
        }

        $stmt = $pdo->prepare($sql); // <-- PREPARE QUERY SELECT
        $stmt->execute($params);     // Eksekusi query dengan parameter
        $tasks = $stmt->fetchAll();  // Simpan data tugas ke variabel $tasks
    } catch (PDOException $e) {
        $dbError = "Gagal memuat data dari database MySQL: " . $e->getMessage();
    }
}

// Tampilkan header website
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header judul halaman -->
<section class="editorial-page-header">
  <div class="header-intro">
    <h1 class="page-title"><?= e($pageTitle) ?></h1>
    <p class="page-description"><?= e($pageSubtitle) ?></p>
  </div>
</section>

<!-- === 1. STATISTIK JUMLAH TUGAS (Total, Belum Selesai, Selesai) === -->
<section class="editorial-stats-strip">
  <!-- Total Tugas -->
  <div class="stat-item">
    <div class="stat-content">
      <div class="stat-header">
        <span class="stat-number"><?= $totalTasks ?></span>
        <div class="stat-icon-box" aria-hidden="true" title="Total Tugas">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
            <path d="M12 11h4"></path>
            <path d="M12 16h4"></path>
            <path d="M8 11h.01"></path>
            <path d="M8 16h.01"></path>
          </svg>
        </div>
      </div>
      <span class="stat-label">Total Tugas</span>
    </div>
  </div>

  <!-- Belum Selesai -->
  <div class="stat-item">
    <div class="stat-content">
      <div class="stat-header">
        <span class="stat-number"><?= $activeTasks ?></span>
        <div class="stat-icon-box" aria-hidden="true" title="Belum Selesai">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
        </div>
      </div>
      <span class="stat-label">Belum Selesai</span>
    </div>
  </div>

  <!-- Selesai -->
  <div class="stat-item">
    <div class="stat-content">
      <div class="stat-header">
        <span class="stat-number"><?= $completedTasks ?></span>
        <div class="stat-icon-box" aria-hidden="true" title="Selesai">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="m9 12 2 2 4-4"></path>
          </svg>
        </div>
      </div>
      <span class="stat-label">Selesai</span>
    </div>
  </div>
</section>

<?php if ($currentView === 'calendar'): ?>
  <!-- === 2. TAMPILAN KALENDER TUGAS === -->
  <section class="editorial-calendar-wrapper">
    <script id="calendarTasksData" type="application/json">
      <?= json_encode($calendarTasks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
    </script>

    <div class="editorial-calendar-layout">
      <!-- Kalender bulanan -->
      <div class="calendar-card">
        <div class="calendar-header-toolbar">
          <div class="calendar-title-group">
            <h3 id="calMonthYearTitle" class="calendar-month-title">Bulan Tahun</h3>
            <span class="calendar-count-pill" id="calMonthTaskCount">0 Tugas</span>
          </div>

          <!-- Tombol navigasi bulan (sebelumnya, hari ini, berikutnya) -->
          <div class="calendar-nav-group">
            <button type="button" class="btn btn-editorial-outline btn-sm" id="calPrevBtn" title="Bulan Sebelumnya">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
              </svg>
            </button>
            <button type="button" class="btn btn-editorial-outline btn-sm" id="calTodayBtn">Hari Ini</button>
            <button type="button" class="btn btn-editorial-outline btn-sm" id="calNextBtn" title="Bulan Berikutnya">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </button>
          </div>
        </div>

        <!-- Penjelas warna titik tanggal kalender -->
        <div class="calendar-legend-bar">
          <div class="legend-chip">
            <span class="legend-dot dot-overdue"></span>
            <span>Terlewat</span>
          </div>
          <div class="legend-chip">
            <span class="legend-dot dot-today"></span>
            <span>Hari Ini</span>
          </div>
          <div class="legend-chip">
            <span class="legend-dot dot-upcoming"></span>
            <span>Mendatang</span>
          </div>
          <div class="legend-chip">
            <span class="legend-dot dot-completed"></span>
            <span>Selesai</span>
          </div>
        </div>

        <!-- Nama hari (Senin - Minggu) -->
        <div class="calendar-weekdays">
          <div class="weekday-col">Sen</div>
          <div class="weekday-col">Sel</div>
          <div class="weekday-col">Rab</div>
          <div class="weekday-col">Kam</div>
          <div class="weekday-col">Jum</div>
          <div class="weekday-col">Sab</div>
          <div class="weekday-col">Min</div>
        </div>

        <!-- Kotak-kotak tanggal kalender (diisi oleh script.js) -->
        <div class="calendar-days-grid" id="calendarDaysGrid"></div>
      </div>

      <!-- Panel detail tugas pada tanggal yang diklik -->
      <div class="calendar-detail-panel">
        <div class="panel-header-editorial">
          <div class="panel-header-titles">
            <span class="panel-eyebrow">Tenggat Waktu</span>
            <h4 id="selectedDateTitle" class="panel-date-heading">Pilih Tanggal</h4>
          </div>
          <button type="button" class="btn btn-editorial-primary btn-sm" id="btnAddTaskOnDate" title="Tambah tugas pada tanggal ini">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Tambah</span>
          </button>
        </div>

        <!-- Rincian kartu tugas di tanggal yang dipilih -->
        <div class="panel-tasks-list" id="selectedDateTasksList"></div>
      </div>
    </div>
  </section>

<?php else: ?>
  <!-- === 3. FILTER & PENCARIAN TUGAS === -->
  <section class="editorial-filter-section">
    <!-- Tab filter status (Semua / Aktif / Selesai) -->
    <div class="editorial-tabs-row">
      <div class="editorial-tabs">
        <a href="index.php?view=<?= e($currentView) ?>&tab=all<?= ($subjectFilter ? '&subject_id='.$subjectFilter : '') ?><?= ($priorityFilter ? '&priority='.$priorityFilter : '') ?><?= ($searchQuery ? '&q='.urlencode($searchQuery) : '') ?>"
           class="editorial-tab <?= ($statusTab === 'all') ? 'is-active' : '' ?>">
          Semua
        </a>
        <a href="index.php?view=<?= e($currentView) ?>&tab=active<?= ($subjectFilter ? '&subject_id='.$subjectFilter : '') ?><?= ($priorityFilter ? '&priority='.$priorityFilter : '') ?><?= ($searchQuery ? '&q='.urlencode($searchQuery) : '') ?>"
           class="editorial-tab <?= ($statusTab === 'active') ? 'is-active' : '' ?>">
          Aktif
        </a>
        <a href="index.php?view=<?= e($currentView) ?>&tab=completed<?= ($subjectFilter ? '&subject_id='.$subjectFilter : '') ?><?= ($priorityFilter ? '&priority='.$priorityFilter : '') ?><?= ($searchQuery ? '&q='.urlencode($searchQuery) : '') ?>"
           class="editorial-tab <?= ($statusTab === 'completed') ? 'is-active' : '' ?>">
          Selesai
        </a>
      </div>
    </div>

    <!-- Form filter pencarian dan pilihan dropdown -->
    <form id="filterForm" action="index.php" method="GET" class="editorial-filter-controls">
      <input type="hidden" name="view" value="<?= e($currentView) ?>">
      <input type="hidden" name="tab" value="<?= e($statusTab) ?>">

      <!-- Input pencarian kata kunci -->
      <div class="filter-search-box">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="searchInput" name="q" class="editorial-search-input" placeholder="Cari tugas..." value="<?= e($searchQuery) ?>" autocomplete="off">
      </div>

      <div class="filter-dropdowns">
        <!-- Filter dropdown mata pelajaran -->
        <div class="select-wrapper">
          <select id="filterSubject" name="subject_id" class="editorial-select">
            <option value="">Semua Mata Pelajaran</option>
            <?php foreach ($subjects as $subj): ?>
              <option value="<?= (int)$subj['id'] ?>" <?= ($subjectFilter === (int)$subj['id']) ? 'selected' : '' ?>>
                <?= e($subj['subject_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Filter dropdown status -->
        <?php if ($currentView !== 'completed'): ?>
          <div class="select-wrapper">
            <select id="filterStatus" name="status" class="editorial-select">
              <option value="">Semua Status</option>
              <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Belum Dikerjakan</option>
              <option value="in_progress" <?= ($statusFilter === 'in_progress') ? 'selected' : '' ?>>Sedang Dikerjakan</option>
              <option value="completed" <?= ($statusFilter === 'completed') ? 'selected' : '' ?>>Selesai</option>
            </select>
          </div>
        <?php endif; ?>

        <!-- Filter dropdown prioritas -->
        <div class="select-wrapper">
          <select id="filterPriority" name="priority" class="editorial-select">
            <option value="">Semua Prioritas</option>
            <option value="high" <?= ($priorityFilter === 'high') ? 'selected' : '' ?>>Tinggi</option>
            <option value="medium" <?= ($priorityFilter === 'medium') ? 'selected' : '' ?>>Sedang</option>
            <option value="low" <?= ($priorityFilter === 'low') ? 'selected' : '' ?>>Rendah</option>
          </select>
        </div>
      </div>
    </form>
  </section>

  <!-- === 4. DAFTAR BARIS TUGAS === -->
  <section class="editorial-tasks-section">
    <?php if ($canReorder && !empty($tasks)): ?>
      <!-- Petunjuk drag & drop -->
      <div class="drag-hint-banner" role="status">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="5" r="1"></circle>
          <circle cx="9" cy="12" r="1"></circle>
          <circle cx="9" cy="19" r="1"></circle>
          <circle cx="15" cy="5" r="1"></circle>
          <circle cx="15" cy="12" r="1"></circle>
          <circle cx="15" cy="19" r="1"></circle>
        </svg>
        <span>Geser ikon untuk mengatur urutan tugas.</span>
      </div>
    <?php endif; ?>

    <?php if (empty($tasks)): ?>
      <!-- Tampilan jika tidak ada tugas -->
      <div class="editorial-empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="8" y1="12" x2="16" y2="12"></line>
          </svg>
        </div>
        <h4>Belum ada tugas yang ditemukan</h4>
        <p>
          <?php if ($searchQuery !== '' || $subjectFilter > 0 || $statusFilter !== '' || $priorityFilter !== '' || $statusTab !== 'all'): ?>
            Coba sesuaikan kata kunci pencarian atau pilihan filter di atas.
          <?php else: ?>
            Belum ada tugas pada kategori ini. Gunakan tombol <strong>Tambah Tugas</strong> di navigasi atas untuk mencatat tugas baru.
          <?php endif; ?>
        </p>
      </div>
    <?php else: ?>
      <!-- Wadah daftar baris tugas -->
      <div class="editorial-task-list" id="taskListContainer" data-can-reorder="<?= $canReorder ? 'true' : 'false' ?>">
        <?php foreach ($tasks as $task): ?>
          <?php
            $isCompleted = ($task['status'] === 'completed');

            $priorityLabels = [
                'low' => 'Rendah',
                'medium' => 'Sedang',
                'high' => 'Tinggi'
            ];
            $priorityLabel = $priorityLabels[$task['priority']] ?? 'Sedang';

            $statusLabels = [
                'pending' => 'Belum Dikerjakan',
                'in_progress' => 'Sedang Dikerjakan',
                'completed' => 'Selesai'
            ];
            $statusLabel = $statusLabels[$task['status']] ?? 'Belum Dikerjakan';

            // Cek status tenggat (overdue, today, atau normal)
            $dueDateClass = getTaskDueStatus($task['due_date'] ?? null, $task['due_time'] ?? null, $isCompleted);
          ?>
          <article class="editorial-task-row <?= $isCompleted ? 'is-completed' : '' ?>"
                   data-id="<?= (int)$task['id'] ?>"
                   data-sort-order="<?= (int)$task['sort_order'] ?>">
            <?php if ($canReorder): ?>
              <!-- Ikon pegangan untuk geser urutan (Drag & Drop) -->
              <div class="row-drag-handle js-drag-handle" title="Tarik untuk mengatur urutan tugas" aria-label="Tarik untuk mengatur urutan">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="9" cy="5" r="1"></circle>
                  <circle cx="9" cy="12" r="1"></circle>
                  <circle cx="9" cy="19" r="1"></circle>
                  <circle cx="15" cy="5" r="1"></circle>
                  <circle cx="15" cy="12" r="1"></circle>
                  <circle cx="15" cy="19" r="1"></circle>
                </svg>
              </div>
            <?php endif; ?>

            <!-- Titik warna status -->
            <div class="row-indicator">
              <span class="status-indicator-dot status-<?= e($task['status']) ?>" title="<?= e($statusLabel) ?>"></span>
            </div>

            <!-- Nama tugas & badge mata pelajaran -->
            <div class="row-main">
              <div class="row-title-bar">
                <h3 class="task-title"><?= e($task['title']) ?></h3>
                <span class="subject-badge"><?= e($task['subject_name']) ?></span>
              </div>

              <?php if (!empty($task['description'])): ?>
                <p class="task-description"><?= e($task['description']) ?></p>
              <?php endif; ?>
            </div>

            <!-- Tanggal dan jam pengumpulan tugas -->
            <div class="row-date">
              <span class="date-chip <?= $dueDateClass ?>" title="Tenggat Pengumpulan">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span><?= formatDueDate($task['due_date'], $task['due_time'] ?? null) ?></span>
              </span>
            </div>

            <!-- Badge prioritas tugas (Rendah / Sedang / Tinggi) -->
            <div class="row-priority">
              <span class="priority-chip priority-<?= e($task['priority']) ?>">
                <?= e($priorityLabel) ?>
              </span>
            </div>

            <!-- Badge status pengerjaan -->
            <div class="row-status">
              <span class="status-chip status-<?= e($task['status']) ?>">
                <?= e($statusLabel) ?>
              </span>
            </div>

            <!-- Tombol aksi Edit dan Hapus -->
            <div class="row-actions">
              <!-- Tombol Edit (membuka drawer edit) -->
              <button type="button"
                      class="row-action-btn edit js-edit-task"
                      title="Edit Tugas"
                      data-id="<?= (int)$task['id'] ?>"
                      data-title="<?= e($task['title']) ?>"
                      data-subject-id="<?= (int)$task['subject_id'] ?>"
                      data-description="<?= e($task['description'] ?? '') ?>"
                      data-due-date="<?= e($task['due_date'] ?? '') ?>"
                      data-due-time="<?= e($task['due_time'] ?? '') ?>"
                      data-priority="<?= e($task['priority']) ?>"
                      data-status="<?= e($task['status']) ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>

              <!-- Tombol Hapus (membuka modal konfirmasi) -->
              <button type="button"
                      class="row-action-btn delete js-delete-task"
                      title="Hapus Tugas"
                      data-id="<?= (int)$task['id'] ?>"
                      data-title="<?= e($task['title']) ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  <line x1="10" y1="11" x2="10" y2="17"></line>
                  <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
              </button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php
// Tampilkan footer dan drawer formulir
$modalSubjects = $subjects;
require_once __DIR__ . '/includes/footer.php';
?>
