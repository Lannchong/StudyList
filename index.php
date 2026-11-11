<?php
/**
 * StudyList — Editorial Study Planner
 * Main Dashboard, Horizontal Task List & Interactive Calendar
 */
require_once __DIR__ . '/config/database.php';

// Determine view mode
$currentView = $_GET['view'] ?? 'overview';
$allowedViews = ['overview', 'all', 'today', 'completed', 'calendar'];
if (!in_array($currentView, $allowedViews, true)) {
    $currentView = 'overview';
}

// Page Titles according to view in pure Indonesian
switch ($currentView) {
    case 'all':
        $pageTitle = 'Semua Tugas';
        $pageSubtitle = 'Daftar lengkap seluruh tugas dan catatan belajar sekolah';
        break;
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
    case 'overview':
    default:
        $pageTitle = 'Ruang Belajar';
        $pageSubtitle = 'Kelola tugas dan jadwal belajar sekolahmu dengan tenang dan teratur.';
        break;
}

// Statistics Counters directly from MySQL
$totalTasks = 0;
$activeTasks = 0;
$completedTasks = 0;

// Fetch all subjects for the filter dropdown & forms
$subjects = getAllSubjects($pdo);

// Query conditions & parameters for list view
$whereConditions = [];
$params = [];

if ($currentView === 'today') {
    $whereConditions[] = "tasks.due_date = CURDATE()";
} elseif ($currentView === 'completed') {
    $whereConditions[] = "tasks.status = 'completed'";
}

// Filter by Status Tab: Semua / Aktif / Selesai
$statusTab = trim($_GET['tab'] ?? 'all');
if (!in_array($statusTab, ['all', 'active', 'completed'], true)) {
    $statusTab = 'all';
}

if ($statusTab === 'active' && $currentView !== 'completed') {
    $whereConditions[] = "tasks.status IN ('pending', 'in_progress')";
} elseif ($statusTab === 'completed' && $currentView !== 'completed') {
    $whereConditions[] = "tasks.status = 'completed'";
}

// Filter by Search Query
$searchQuery = trim($_GET['q'] ?? '');
if ($searchQuery !== '') {
    $whereConditions[] = "(tasks.title LIKE ? OR tasks.description LIKE ?)";
    $params[] = '%' . $searchQuery . '%';
    $params[] = '%' . $searchQuery . '%';
}

// Filter by Subject
$subjectFilter = (int)($_GET['subject_id'] ?? 0);
if ($subjectFilter > 0) {
    $whereConditions[] = "tasks.subject_id = ?";
    $params[] = $subjectFilter;
}

// Filter by Status Dropdown (ignored if on completed view)
$statusFilter = trim($_GET['status'] ?? '');
if ($currentView !== 'completed' && in_array($statusFilter, ['pending', 'in_progress', 'completed'], true)) {
    $whereConditions[] = "tasks.status = ?";
    $params[] = $statusFilter;
}

// Filter by Priority
$priorityFilter = trim($_GET['priority'] ?? '');
if (in_array($priorityFilter, ['low', 'medium', 'high'], true)) {
    $whereConditions[] = "tasks.priority = ?";
    $params[] = $priorityFilter;
}

$tasks = [];
$calendarTasks = [];

if ($pdo) {
    try {
        // 1. Live Statistics directly from MySQL tasks table
        $statTotalStmt = $pdo->query("SELECT COUNT(*) FROM tasks");
        $totalTasks = (int)$statTotalStmt->fetchColumn();

        $statActiveStmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pending', 'in_progress')");
        $activeTasks = (int)$statActiveStmt->fetchColumn();

        $statCompletedStmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
        $completedTasks = (int)$statCompletedStmt->fetchColumn();

        // 2. If Calendar view, fetch all tasks with due_date
        if ($currentView === 'calendar') {
            $calStmt = $pdo->query("SELECT tasks.*, subjects.subject_name 
                                    FROM tasks 
                                    JOIN subjects ON tasks.subject_id = subjects.id 
                                    WHERE tasks.due_date IS NOT NULL 
                                    ORDER BY tasks.due_date ASC, 
                                             CASE tasks.priority 
                                                 WHEN 'high' THEN 1 
                                                 WHEN 'medium' THEN 2 
                                                 ELSE 3 
                                             END ASC");
            $calendarTasks = $calStmt->fetchAll();
        }

        // 3. Fetch Tasks with Joined Subject Name for list views
        $sql = "SELECT tasks.*, subjects.subject_name
                FROM tasks
                JOIN subjects ON tasks.subject_id = subjects.id";

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }

        // Sorting: Incomplete tasks first, then by due date ascending, then ID descending
        $sql .= " ORDER BY 
                  CASE WHEN tasks.status = 'completed' THEN 2 ELSE 1 END ASC,
                  CASE WHEN tasks.due_date IS NULL THEN 1 ELSE 0 END ASC,
                  tasks.due_date ASC,
                  tasks.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();
    } catch (PDOException $e) {
        $dbError = "Gagal memuat data dari database MySQL: " . $e->getMessage();
    }
}

// Load Header (includes horizontal top navbar)
require_once __DIR__ . '/includes/header.php';
?>

<!-- ======================================================== -->
<!-- 1. HEADER HALAMAN                                        -->
<!-- ======================================================== -->
<section class="editorial-page-header">
  <div class="header-intro">
    <h1 class="page-title"><?= e($pageTitle) ?></h1>
    <p class="page-description"><?= e($pageSubtitle) ?></p>
  </div>
</section>

<!-- ======================================================== -->
<!-- 2. RINGKASAN STATISTIK (Horizontal Summary Area)        -->
<!-- ======================================================== -->
<section class="editorial-stats-strip">
  <div class="stat-item">
    <span class="stat-number"><?= $totalTasks ?></span>
    <span class="stat-label">Total Tugas</span>
  </div>
  <div class="stat-divider"></div>
  <div class="stat-item">
    <span class="stat-number stat-active"><?= $activeTasks ?></span>
    <span class="stat-label">Belum Selesai</span>
  </div>
  <div class="stat-divider"></div>
  <div class="stat-item">
    <span class="stat-number stat-completed"><?= $completedTasks ?></span>
    <span class="stat-label">Selesai</span>
  </div>
</section>

<?php if ($currentView === 'calendar'): ?>
  <!-- ======================================================== -->
  <!-- 3. HALAMAN KALENDER                                      -->
  <!-- ======================================================== -->
  <section class="editorial-calendar-wrapper">
    <!-- Embedded JSON Data from MySQL -->
    <script id="calendarTasksData" type="application/json">
      <?= json_encode($calendarTasks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
    </script>

    <div class="editorial-calendar-layout">
      <!-- Calendar Main Card -->
      <div class="calendar-card">
        <div class="calendar-header-toolbar">
          <div class="calendar-title-group">
            <h3 id="calMonthYearTitle" class="calendar-month-title">Bulan Tahun</h3>
            <span class="calendar-count-pill" id="calMonthTaskCount">0 Tugas</span>
          </div>

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

        <!-- Legend -->
        <div class="calendar-legend-bar">
          <div class="legend-chip">
            <span class="legend-dot dot-active"></span>
            <span>Belum Selesai</span>
          </div>
          <div class="legend-chip">
            <span class="legend-dot dot-completed"></span>
            <span>Selesai</span>
          </div>
          <div class="legend-chip">
            <span class="legend-dot dot-today"></span>
            <span>Hari Ini</span>
          </div>
        </div>

        <!-- Weekdays: Senin - Minggu -->
        <div class="calendar-weekdays-row">
          <div>Sen</div>
          <div>Sel</div>
          <div>Rab</div>
          <div>Kam</div>
          <div>Jum</div>
          <div>Sab</div>
          <div>Min</div>
        </div>

        <!-- Days Grid -->
        <div class="calendar-days-grid" id="calendarDaysGrid">
          <!-- Populated by JavaScript -->
        </div>
      </div>

      <!-- Selected Date Panel -->
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

        <div class="panel-tasks-list" id="selectedDateTasksList">
          <!-- Populated by JavaScript -->
        </div>
      </div>
    </div>
  </section>

<?php else: ?>
  <!-- ======================================================== -->
  <!-- 4. PENCARIAN DAN FILTER (Single Clean Area)             -->
  <!-- ======================================================== -->
  <section class="editorial-filter-section">
    <!-- Tabs Sederhana: Semua / Aktif / Selesai -->
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

    <!-- Filter Form -->
    <form id="filterForm" action="index.php" method="GET" class="editorial-filter-controls">
      <input type="hidden" name="view" value="<?= e($currentView) ?>">
      <input type="hidden" name="tab" value="<?= e($statusTab) ?>">

      <!-- Input Pencarian -->
      <div class="filter-search-box">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="searchInput" name="q" class="editorial-search-input" placeholder="Cari tugas..." value="<?= e($searchQuery) ?>" autocomplete="off">
      </div>

      <div class="filter-dropdowns">
        <!-- Filter Mata Pelajaran -->
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

        <!-- Filter Status (sembunyikan jika di halaman Selesai) -->
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

        <!-- Filter Prioritas -->
        <div class="select-wrapper">
          <select id="filterPriority" name="priority" class="editorial-select">
            <option value="">Semua Prioritas</option>
            <option value="high" <?= ($priorityFilter === 'high') ? 'selected' : '' ?>>Tinggi</option>
            <option value="medium" <?= ($priorityFilter === 'medium') ? 'selected' : '' ?>>Sedang</option>
            <option value="low" <?= ($priorityFilter === 'low') ? 'selected' : '' ?>>Rendah</option>
          </select>
        </div>

        <!-- Tombol Reset / Atur Ulang jika ada filter aktif -->
        <?php if ($searchQuery !== '' || $subjectFilter > 0 || $statusFilter !== '' || $priorityFilter !== '' || $statusTab !== 'all'): ?>
          <a href="index.php?view=<?= e($currentView) ?>" class="filter-reset-btn">Atur Ulang</a>
        <?php endif; ?>
      </div>
    </form>
  </section>

  <!-- ======================================================== -->
  <!-- 5. DAFTAR TUGAS (Modern Horizontal List / Table-Like)    -->
  <!-- ======================================================== -->
  <section class="editorial-tasks-section">
    <?php if (empty($tasks)): ?>
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
      <div class="editorial-task-list">
        <?php foreach ($tasks as $task): ?>
          <?php
            $isCompleted = ($task['status'] === 'completed');

            // Prioritas Bahasa Indonesia
            $priorityLabels = [
                'low' => 'Rendah',
                'medium' => 'Sedang',
                'high' => 'Tinggi'
            ];
            $priorityLabel = $priorityLabels[$task['priority']] ?? 'Sedang';

            // Status Bahasa Indonesia
            $statusLabels = [
                'pending' => 'Belum Dikerjakan',
                'in_progress' => 'Sedang Dikerjakan',
                'completed' => 'Selesai'
            ];
            $statusLabel = $statusLabels[$task['status']] ?? 'Belum Dikerjakan';

            // Due date calculations
            $dueDateClass = '';
            if (!empty($task['due_date'])) {
                $todayTimestamp = strtotime(date('Y-m-d'));
                $taskTimestamp = strtotime($task['due_date']);
                if ($taskTimestamp === $todayTimestamp) {
                    $dueDateClass = 'is-today';
                } elseif ($taskTimestamp < $todayTimestamp && !$isCompleted) {
                    $dueDateClass = 'is-overdue';
                }
            }
          ?>
          <article class="editorial-task-row <?= $isCompleted ? 'is-completed' : '' ?>">
            <!-- Indikator Status Minimalis -->
            <div class="row-indicator">
              <span class="status-indicator-dot status-<?= e($task['status']) ?>" title="<?= e($statusLabel) ?>"></span>
            </div>

            <!-- Konten Utama: Nama Tugas & Deskripsi -->
            <div class="row-main">
              <div class="row-title-bar">
                <h3 class="task-title"><?= e($task['title']) ?></h3>
                <span class="subject-badge"><?= e($task['subject_name']) ?></span>
              </div>

              <?php if (!empty($task['description'])): ?>
                <p class="task-description"><?= e($task['description']) ?></p>
              <?php endif; ?>
            </div>

            <!-- Tanggal Pengumpulan -->
            <div class="row-date">
              <span class="date-chip <?= $dueDateClass ?>" title="Tenggat Pengumpulan">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span><?= formatDueDate($task['due_date']) ?></span>
              </span>
            </div>

            <!-- Prioritas Tag -->
            <div class="row-priority">
              <span class="priority-chip priority-<?= e($task['priority']) ?>">
                <?= e($priorityLabel) ?>
              </span>
            </div>

            <!-- Status Badge Ringkas -->
            <div class="row-status">
              <span class="status-chip status-<?= e($task['status']) ?>">
                <?= e($statusLabel) ?>
              </span>
            </div>

            <!-- Tombol Aksi: Edit & Hapus -->
            <div class="row-actions">
              <button type="button"
                      class="row-action-btn edit js-edit-task"
                      title="Edit Tugas"
                      data-id="<?= (int)$task['id'] ?>"
                      data-title="<?= e($task['title']) ?>"
                      data-subject-id="<?= (int)$task['subject_id'] ?>"
                      data-description="<?= e($task['description'] ?? '') ?>"
                      data-due-date="<?= e($task['due_date'] ?? '') ?>"
                      data-priority="<?= e($task['priority']) ?>"
                      data-status="<?= e($task['status']) ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>

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
// Load Footer (includes drawers and scripts)
$modalSubjects = $subjects;
require_once __DIR__ . '/includes/footer.php';
?>
