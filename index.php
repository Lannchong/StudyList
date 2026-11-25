<?php
// HALAMAN UTAMA: DAFTAR TUGAS, FILTER & KALENDER 
require_once __DIR__ . '/config/database.php';

// Halaman yang sedang aktif (default: semua tugas)
$currentView = $_GET['view'] ?? 'all';
$allowedViews = ['all', 'calendar', 'today', 'this_week', 'this_month', 'this_year', 'completed'];
if (!in_array($currentView, $allowedViews, true)) {
    $currentView = 'all';
}

// 4 Filter Waktu (Hari Ini, Minggu Ini, Bulan Ini, Tahun Ini)
$timeFilter = trim($_GET['time'] ?? '');
if (!in_array($timeFilter, ['today', 'this_week', 'this_month', 'this_year'], true)) {
    $timeFilter = '';
}
if ($timeFilter === '' && in_array($currentView, ['today', 'this_week', 'this_month', 'this_year'], true) && !isset($_GET['time'])) {
    $timeFilter = $currentView;
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
    case 'this_week':
        $pageTitle = 'Minggu Ini';
        $pageSubtitle = 'Daftar tugas sekolah dengan tenggat waktu minggu ini (Senin – Minggu)';
        break;
    case 'this_month':
        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $curMonth = $monthNames[(int)date('n')] ?? date('F');
        $pageTitle = 'Bulan Ini';
        $pageSubtitle = 'Daftar tugas sekolah dengan tenggat waktu bulan ini (' . $curMonth . ' ' . date('Y') . ')';
        break;
    case 'this_year':
        $pageTitle = 'Tahun Ini';
        $pageSubtitle = 'Daftar tugas sekolah dengan tenggat waktu tahun ini (' . date('Y') . ')';
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

// === 4 FILTER WAKTU (HARI INI, MINGGU INI, BULAN INI, TAHUN INI) ===
if ($timeFilter === 'today') {
    $whereConditions[] = "tasks.due_date = CURDATE()"; // 1. Filter Hari Ini
} elseif ($timeFilter === 'this_week') {
    $whereConditions[] = "YEARWEEK(tasks.due_date, 1) = YEARWEEK(CURDATE(), 1)"; // 2. Filter Minggu Ini (Senin - Minggu)
} elseif ($timeFilter === 'this_month') {
    $whereConditions[] = "YEAR(tasks.due_date) = YEAR(CURDATE()) AND MONTH(tasks.due_date) = MONTH(CURDATE())"; // 3. Filter Bulan Ini
} elseif ($timeFilter === 'this_year') {
    $whereConditions[] = "YEAR(tasks.due_date) = YEAR(CURDATE())"; // 4. Filter Tahun Ini
}

if ($currentView === 'completed') {
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

// Drag & drop hanya aktif di Semua Tugas tanpa filter waktu, status, atau pencarian
$canReorder = ($currentView === 'all' && $timeFilter === '' && $searchQuery === '' && $subjectFilter <= 0 && $statusFilter === '' && $priorityFilter === '' && $statusTab === 'all');

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

// Cek apakah sedang di tampilan Tahun Ini
$isYearView = ($currentView === 'this_year' || $timeFilter === 'this_year');

$currentYear = (int)date('Y');     // Tahun aktif (misal 2026)
$currentMonthNum = (int)date('n'); // Bulan aktif saat ini (1 - 12)
$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Kelompokkan tugas ke 12 bulan (Januari s.d. Desember)
$tasksByMonth = [];
for ($m = 1; $m <= 12; $m++) {
    $tasksByMonth[$m] = [];
}

if ($isYearView && !empty($tasks)) {
    foreach ($tasks as $task) {
        if (!empty($task['due_date'])) {
            $mNum = (int)date('n', strtotime($task['due_date']));
            $yNum = (int)date('Y', strtotime($task['due_date']));
            if ($yNum === $currentYear && $mNum >= 1 && $mNum <= 12) {
                $tasksByMonth[$mNum][] = $task;
            }
        }
    }
}

// Hitung total dan status tugas di tiap bulan
$monthStats = [];
for ($m = 1; $m <= 12; $m++) {
    $tot = count($tasksByMonth[$m]);
    $act = 0;
    $comp = 0;
    foreach ($tasksByMonth[$m] as $t) {
        if (($t['status'] ?? '') === 'completed') {
            $comp++;
        } else {
            $act++;
        }
    }
    $monthStats[$m] = [
        'total' => $tot,
        'active' => $act,
        'completed' => $comp,
    ];
}

// --- DATA GRAFIK KURVA PRODUKTIVITAS BULANAN (JANUARI - DESEMBER) ---
$chartYear = (int)date('Y'); // Tahun kurva
$chartShortMonths = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
    9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
];

$chartMonthlyData = [];
for ($m = 1; $m <= 12; $m++) {
    $chartMonthlyData[$m] = [
        'total'       => 0,
        'completed'   => 0,
        'in_progress' => 0,
        'pending'     => 0,
    ];
}

$chartTotals = [
    'total'       => 0,
    'completed'   => 0,
    'in_progress' => 0,
    'pending'     => 0,
];

if ($pdo) {
    try {
        $chartStmt = $pdo->prepare("SELECT MONTH(due_date) as m, status, COUNT(*) as cnt 
                                    FROM tasks 
                                    WHERE YEAR(due_date) = ? 
                                    GROUP BY MONTH(due_date), status");
        $chartStmt->execute([$chartYear]);
        while ($row = $chartStmt->fetch()) {
            $m = (int)$row['m'];
            $st = $row['status'];
            $cnt = (int)$row['cnt'];
            if ($m >= 1 && $m <= 12) {
                $chartMonthlyData[$m]['total'] += $cnt;
                if ($st === 'completed') {
                    $chartMonthlyData[$m]['completed'] += $cnt;
                } elseif ($st === 'in_progress') {
                    $chartMonthlyData[$m]['in_progress'] += $cnt;
                } else {
                    $chartMonthlyData[$m]['pending'] += $cnt;
                }
            }
        }
        foreach ($chartMonthlyData as $m => $d) {
            $chartTotals['total'] += $d['total'];
            $chartTotals['completed'] += $d['completed'];
            $chartTotals['in_progress'] += $d['in_progress'];
            $chartTotals['pending'] += $d['pending'];
        }
    } catch (Exception $e) {
        // Fallback jika query gagal
    }
}

// Fungsi membuat kurva halus (Catmull-Rom to Cubic Bezier)
function generateSplinePath($pts, $tension = 0.2) {
    $n = count($pts);
    if ($n === 0) return '';
    if ($n === 1) return "M {$pts[0][0]},{$pts[0][1]}";

    $d = "M {$pts[0][0]},{$pts[0][1]}";
    for ($i = 0; $i < $n - 1; $i++) {
        $pPrev = ($i > 0) ? $pts[$i - 1] : $pts[$i];
        $pCur = $pts[$i];
        $pNext = $pts[$i + 1];
        $pNextNext = ($i + 2 < $n) ? $pts[$i + 2] : $pNext;

        $cp1x = round($pCur[0] + ($pNext[0] - $pPrev[0]) * $tension, 1);
        $cp1y = round($pCur[1] + ($pNext[1] - $pPrev[1]) * $tension, 1);
        $cp2x = round($pNext[0] - ($pNextNext[0] - $pCur[0]) * $tension, 1);
        $cp2y = round($pNext[1] - ($pNextNext[1] - $pCur[1]) * $tension, 1);
        $nx = round($pNext[0], 1);
        $ny = round($pNext[1], 1);

        $d .= " C {$cp1x},{$cp1y} {$cp2x},{$cp2y} {$nx},{$ny}";
    }
    return $d;
}

// Hitung koordinat SVG untuk 12 titik bulan
$chartMaxVal = 0;
for ($m = 1; $m <= 12; $m++) {
    if ($chartMonthlyData[$m]['total'] > $chartMaxVal) {
        $chartMaxVal = $chartMonthlyData[$m]['total'];
    }
}
$chartScaleMax = max($chartMaxVal, 5);
if ($chartScaleMax > 5 && $chartScaleMax % 2 !== 0) {
    $chartScaleMax++;
}

$chartPadL = 48;
$chartPadR = 48;
$chartPadT = 32;
$chartBaseY = 168;
$chartHeight = 136;
$chartWidth = 960 - ($chartPadL + $chartPadR);
$chartStepX = $chartWidth / 11;

$ptsTotal = [];
$ptsCompleted = [];
$ptsInProgress = [];
$ptsPending = [];
$chartMonthCoords = [];

for ($m = 1; $m <= 12; $m++) {
    $cx = round($chartPadL + ($m - 1) * $chartStepX, 1);
    
    $valTot = $chartMonthlyData[$m]['total'];
    $valComp = $chartMonthlyData[$m]['completed'];
    $valProg = $chartMonthlyData[$m]['in_progress'];
    $valPend = $chartMonthlyData[$m]['pending'];

    $cyTot = round($chartBaseY - ($valTot / $chartScaleMax) * $chartHeight, 1);
    $cyComp = round($chartBaseY - ($valComp / $chartScaleMax) * $chartHeight, 1);
    $cyProg = round($chartBaseY - ($valProg / $chartScaleMax) * $chartHeight, 1);
    $cyPend = round($chartBaseY - ($valPend / $chartScaleMax) * $chartHeight, 1);

    $ptsTotal[] = [$cx, $cyTot];
    $ptsCompleted[] = [$cx, $cyComp];
    $ptsInProgress[] = [$cx, $cyProg];
    $ptsPending[] = [$cx, $cyPend];

    $chartMonthCoords[$m] = [
        'x'           => $cx,
        'y_total'     => $cyTot,
        'y_completed' => $cyComp,
        'y_progress'  => $cyProg,
        'y_pending'   => $cyPend,
        'data'        => $chartMonthlyData[$m],
        'short_name'  => $chartShortMonths[$m],
        'full_name'   => $monthNames[$m]
    ];
}

$pathTotal = generateSplinePath($ptsTotal);
$pathAreaTotal = $pathTotal . " L {$ptsTotal[11][0]},{$chartBaseY} L {$ptsTotal[0][0]},{$chartBaseY} Z";

$pathCompleted = generateSplinePath($ptsCompleted);
$pathAreaCompleted = $pathCompleted . " L {$ptsCompleted[11][0]},{$chartBaseY} L {$ptsCompleted[0][0]},{$chartBaseY} Z";

$pathInProgress = generateSplinePath($ptsInProgress);
$pathPending = generateSplinePath($ptsPending);

// Fungsi pembantu render baris kartu tugas (DRY & konsisten)
$renderTaskRow = function($task, $canReorder = false) {
    $isCompleted = ($task['status'] === 'completed');
    $priorityLabels = ['low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi'];
    $priorityLabel = $priorityLabels[$task['priority']] ?? 'Sedang';
    $statusLabels = ['pending' => 'Belum Dikerjakan', 'in_progress' => 'Sedang Dikerjakan', 'completed' => 'Selesai'];
    $statusLabel = $statusLabels[$task['status']] ?? 'Belum Dikerjakan';
    $dueDateClass = getTaskDueStatus($task['due_date'] ?? null, $task['due_time'] ?? null, $isCompleted);
    ?>
    <article class="editorial-task-row <?= $isCompleted ? 'is-completed' : '' ?>"
             data-id="<?= (int)$task['id'] ?>"
             data-sort-order="<?= (int)$task['sort_order'] ?>">
      <?php if ($canReorder): ?>
        <!-- Ikon pegangan drag & drop -->
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

      <!-- Nama tugas & badge mapel -->
      <div class="row-main">
        <div class="row-title-bar">
          <h3 class="task-title"><?= e($task['title']) ?></h3>
          <span class="subject-badge"><?= e($task['subject_name']) ?></span>
        </div>

        <?php if (!empty($task['description'])): ?>
          <p class="task-description"><?= e($task['description']) ?></p>
        <?php endif; ?>
      </div>

      <!-- Tanggal & jam pengumpulan -->
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

      <!-- Badge prioritas -->
      <div class="row-priority">
        <span class="priority-chip priority-<?= e($task['priority']) ?>">
          <?= e($priorityLabel) ?>
        </span>
      </div>

      <!-- Badge status -->
      <div class="row-status">
        <span class="status-chip status-<?= e($task['status']) ?>">
          <?= e($statusLabel) ?>
        </span>
      </div>

      <!-- Tombol Edit & Hapus -->
      <div class="row-actions">
        <!-- Tombol edit -->
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

        <!-- Tombol hapus -->
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
    <?php
};

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

<!-- === GRAFIK KURVA PRODUKTIVITAS BULANAN (JANUARI - DESEMBER) === -->
<section class="editorial-chart-section">
  <div class="productivity-chart-card" id="productivityChartCard">
    
    <!-- Header grafik: Judul & Tombol Filter Status -->
    <div class="chart-header">
      <div class="chart-title-group">
        <div class="chart-title-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
            <polyline points="17 6 23 6 23 12"></polyline>
          </svg>
        </div>
        <div>
          <h2 class="chart-title">Grafik Kurva Produktivitas Belajar <?= $chartYear ?></h2>
          <p class="chart-subtitle">Tren beban tugas sekolah dan status penyelesaian dari Januari hingga Desember</p>
        </div>
      </div>

      <!-- Tombol filter kurva status tugas -->
      <div class="chart-controls">
        <div class="chart-legend-filters">
          <!-- Total tugas -->
          <button type="button" class="chart-filter-btn is-active js-chart-filter" data-curve="total" title="Tampilkan kurva total tugas">
            <span class="legend-color-dot dot-total"></span>
            <span class="filter-name">Semua (Total)</span>
            <span class="filter-count"><?= $chartTotals['total'] ?></span>
          </button>

          <!-- Tugas selesai -->
          <button type="button" class="chart-filter-btn js-chart-filter" data-curve="completed" title="Tampilkan kurva tugas selesai">
            <span class="legend-color-dot dot-completed"></span>
            <span class="filter-name">Selesai</span>
            <span class="filter-count"><?= $chartTotals['completed'] ?></span>
          </button>

          <!-- Sedang dikerjakan -->
          <button type="button" class="chart-filter-btn js-chart-filter" data-curve="in_progress" title="Tampilkan kurva sedang dikerjakan">
            <span class="legend-color-dot dot-progress"></span>
            <span class="filter-name">Sedang Dikerjakan</span>
            <span class="filter-count"><?= $chartTotals['in_progress'] ?></span>
          </button>

          <!-- Belum dikerjakan -->
          <button type="button" class="chart-filter-btn js-chart-filter" data-curve="pending" title="Tampilkan kurva belum dikerjakan">
            <span class="legend-color-dot dot-pending"></span>
            <span class="filter-name">Belum Dikerjakan</span>
            <span class="filter-count"><?= $chartTotals['pending'] ?></span>
          </button>
        </div>
      </div>
    </div>

    <!-- Area Kanvas Grafik SVG -->
    <div class="chart-canvas-wrapper" id="chartCanvasWrapper">
      <svg viewBox="0 0 960 215" class="productivity-svg" preserveAspectRatio="none" id="productivitySvg">
        <defs>
          <!-- Gradient kurva utama total tugas -->
          <linearGradient id="totalCurveGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#2563EB" stop-opacity="0.32" />
            <stop offset="100%" stop-color="#2563EB" stop-opacity="0.01" />
          </linearGradient>

          <!-- Gradient kurva tugas selesai -->
          <linearGradient id="completedCurveGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#10B981" stop-opacity="0.28" />
            <stop offset="100%" stop-color="#10B981" stop-opacity="0.01" />
          </linearGradient>

          <!-- Gradient kurva sedang dikerjakan -->
          <linearGradient id="progressCurveGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.24" />
            <stop offset="100%" stop-color="#3B82F6" stop-opacity="0.01" />
          </linearGradient>

          <!-- Gradient kurva belum dikerjakan -->
          <linearGradient id="pendingCurveGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#F59E0B" stop-opacity="0.24" />
            <stop offset="100%" stop-color="#F59E0B" stop-opacity="0.01" />
          </linearGradient>
        </defs>

        <!-- Garis horizontal panduan (Grid Lines) -->
        <g class="chart-grid-lines">
          <!-- Garis Max -->
          <line x1="<?= $chartPadL ?>" y1="<?= $chartPadT ?>" x2="<?= 960 - $chartPadR ?>" y2="<?= $chartPadT ?>" stroke="var(--border)" stroke-dasharray="3,3" stroke-width="1" />
          <!-- Garis Tengah (50%) -->
          <line x1="<?= $chartPadL ?>" y1="<?= round($chartPadT + $chartHeight / 2) ?>" x2="<?= 960 - $chartPadR ?>" y2="<?= round($chartPadT + $chartHeight / 2) ?>" stroke="var(--border)" stroke-dasharray="3,3" stroke-width="1" opacity="0.6" />
          <!-- Garis Baseline (0) -->
          <line x1="<?= $chartPadL ?>" y1="<?= $chartBaseY ?>" x2="<?= 960 - $chartPadR ?>" y2="<?= $chartBaseY ?>" stroke="var(--border)" stroke-width="1.2" />
        </g>

        <!-- Label angka sumbu Y -->
        <g class="chart-y-labels">
          <text x="<?= $chartPadL - 8 ?>" y="<?= $chartPadT + 4 ?>" text-anchor="end" class="chart-axis-text"><?= $chartScaleMax ?></text>
          <text x="<?= $chartPadL - 8 ?>" y="<?= round($chartPadT + $chartHeight / 2) + 4 ?>" text-anchor="end" class="chart-axis-text"><?= round($chartScaleMax / 2) ?></text>
          <text x="<?= $chartPadL - 8 ?>" y="<?= $chartBaseY + 4 ?>" text-anchor="end" class="chart-axis-text">0</text>
        </g>

        <!-- Garis vertikal & label bulan sumbu X -->
        <g class="chart-x-grid">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <?php 
              $cx = $chartMonthCoords[$m]['x'];
              $isCurM = ($m === $currentMonthNum);
            ?>
            <line x1="<?= $cx ?>" y1="<?= $chartPadT ?>" x2="<?= $cx ?>" y2="<?= $chartBaseY ?>" stroke="var(--border)" stroke-dasharray="2,3" stroke-width="0.8" opacity="<?= $isCurM ? '0.9' : '0.4' ?>" />
            <text x="<?= $cx ?>" y="<?= $chartBaseY + 20 ?>" text-anchor="middle" class="chart-x-label <?= $isCurM ? 'is-current' : '' ?>">
              <?= $chartShortMonths[$m] ?>
            </text>
          <?php endfor; ?>
        </g>

        <!-- 1. Area Gradient Kurva Total -->
        <path d="<?= $pathAreaTotal ?>" fill="url(#totalCurveGrad)" class="chart-area-path area-total js-chart-area" />

        <!-- 2. Garis Kurva Selesai (Hijau) -->
        <path d="<?= $pathCompleted ?>" fill="none" stroke="#10B981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="chart-curve-path curve-completed js-curve-line" data-curve="completed" />

        <!-- 3. Garis Kurva Sedang Dikerjakan (Biru Aksen) -->
        <path d="<?= $pathInProgress ?>" fill="none" stroke="#3B82F6" stroke-width="2" stroke-dasharray="4,3" stroke-linecap="round" stroke-linejoin="round" class="chart-curve-path curve-progress js-curve-line" data-curve="in_progress" />

        <!-- 4. Garis Kurva Belum Dikerjakan (Oranye Amber) -->
        <path d="<?= $pathPending ?>" fill="none" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="chart-curve-path curve-pending js-curve-line" data-curve="pending" />

        <!-- 5. Garis Kurva Utama Total Tugas (Biru Utama Tebal) -->
        <path d="<?= $pathTotal ?>" fill="none" stroke="var(--primary)" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round" class="chart-curve-path curve-total js-curve-line" data-curve="total" />

        <!-- Garis indikator penunjuk saat hover (Vertical crosshair) -->
        <line id="chartHoverLine" x1="0" y1="<?= $chartPadT ?>" x2="0" y2="<?= $chartBaseY ?>" stroke="var(--primary)" stroke-width="1.5" stroke-dasharray="3,3" style="display: none;" />

        <!-- Titik data (Dots) pada setiap bulan -->
        <g class="chart-points-group">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <?php 
              $c = $chartMonthCoords[$m];
              $tot = $c['data']['total'];
              $isCurM = ($m === $currentMonthNum);
            ?>
            <!-- Titik Total -->
            <circle cx="<?= $c['x'] ?>" cy="<?= $c['y_total'] ?>" r="<?= ($tot > 0) ? '5.5' : '3.5' ?>"
                    class="chart-dot dot-total js-chart-dot <?= ($tot > 0) ? 'has-val' : 'is-zero' ?> <?= $isCurM ? 'is-cur-month' : '' ?>"
                    data-month="<?= $m ?>"
                    data-name="<?= $c['full_name'] ?>"
                    data-total="<?= $tot ?>"
                    data-completed="<?= $c['data']['completed'] ?>"
                    data-progress="<?= $c['data']['in_progress'] ?>"
                    data-pending="<?= $c['data']['pending'] ?>" />
          <?php endfor; ?>
        </g>

        <!-- Area interaksi hover kolom per bulan (Transparan) -->
        <g class="chart-hover-zones">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <?php 
              $cx = $chartMonthCoords[$m]['x'];
              $hw = $chartStepX;
              $hx = $cx - ($hw / 2);
              $c = $chartMonthCoords[$m];
            ?>
            <rect x="<?= $hx ?>" y="0" width="<?= $hw ?>" height="215" fill="transparent" class="chart-hover-col js-chart-col"
                  data-month="<?= $m ?>"
                  data-name="<?= $c['full_name'] ?>"
                  data-x="<?= $cx ?>"
                  data-y="<?= $c['y_total'] ?>"
                  data-total="<?= $c['data']['total'] ?>"
                  data-completed="<?= $c['data']['completed'] ?>"
                  data-progress="<?= $c['data']['in_progress'] ?>"
                  data-pending="<?= $c['data']['pending'] ?>" />
          <?php endfor; ?>
        </g>
      </svg>

      <!-- Tooltip mengambang interaktif -->
      <div class="chart-tooltip" id="chartTooltip" style="display: none;" role="tooltip">
        <div class="tooltip-header" id="tooltipHeader">November 2026</div>
        <div class="tooltip-total-row">
          <span class="tooltip-label">Total Beban Tugas:</span>
          <span class="tooltip-val" id="tooltipTotal">9 Tugas</span>
        </div>
        <div class="tooltip-divider"></div>
        <div class="tooltip-breakdown">
          <div class="tooltip-row status-done">
            <span class="tt-dot dot-done"></span>
            <span class="tt-text">Selesai:</span>
            <span class="tt-num" id="tooltipCompleted">7</span>
          </div>
          <div class="tooltip-row status-prog">
            <span class="tt-dot dot-prog"></span>
            <span class="tt-text">Sedang Dikerjakan:</span>
            <span class="tt-num" id="tooltipProgress">0</span>
          </div>
          <div class="tooltip-row status-pend">
            <span class="tt-dot dot-pend"></span>
            <span class="tt-text">Belum Dikerjakan:</span>
            <span class="tt-num" id="tooltipPending">2</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Baris status bar mini di bawah grafik untuk tiap bulan -->
    <div class="chart-months-strip">
      <?php for ($m = 1; $m <= 12; $m++): ?>
        <?php 
          $d = $chartMonthlyData[$m];
          $tot = $d['total'];
          $isCur = ($m === $currentMonthNum);
        ?>
        <div class="month-strip-item js-strip-item <?= $isCur ? 'is-current' : '' ?>" data-month="<?= $m ?>" title="<?= $monthNames[$m] ?>: <?= $tot ?> tugas">
          <span class="strip-month-name"><?= $chartShortMonths[$m] ?></span>
          <span class="strip-month-total <?= ($tot > 0) ? 'has-tasks' : '' ?>">
            <?= $tot ?>
          </span>
          <!-- Bar mini proporsi status tugas -->
          <div class="strip-mini-bar">
            <?php if ($tot > 0): ?>
              <?php 
                $pctComp = round(($d['completed'] / $tot) * 100);
                $pctProg = round(($d['in_progress'] / $tot) * 100);
                $pctPend = 100 - ($pctComp + $pctProg);
              ?>
              <?php if ($pctComp > 0): ?><div class="bar-seg seg-comp" style="width: <?= $pctComp ?>%;"></div><?php endif; ?>
              <?php if ($pctProg > 0): ?><div class="bar-seg seg-prog" style="width: <?= $pctProg ?>%;"></div><?php endif; ?>
              <?php if ($pctPend > 0): ?><div class="bar-seg seg-pend" style="width: <?= $pctPend ?>%;"></div><?php endif; ?>
            <?php else: ?>
              <div class="bar-seg seg-empty" style="width: 100%;"></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endfor; ?>
    </div>

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
    <!-- Tab filter status (Semua / Aktif / Selesai) & 4 Filter Waktu -->
    <div class="editorial-tabs-row">
      <div class="editorial-tabs">
        <a href="index.php?view=<?= e($currentView) ?>&tab=all<?= ($timeFilter ? '&time='.$timeFilter : '') ?><?= ($subjectFilter ? '&subject_id='.$subjectFilter : '') ?><?= ($priorityFilter ? '&priority='.$priorityFilter : '') ?><?= ($searchQuery ? '&q='.urlencode($searchQuery) : '') ?>"
           class="editorial-tab <?= ($statusTab === 'all') ? 'is-active' : '' ?>">
          Semua
        </a>
        <a href="index.php?view=<?= e($currentView) ?>&tab=active<?= ($timeFilter ? '&time='.$timeFilter : '') ?><?= ($subjectFilter ? '&subject_id='.$subjectFilter : '') ?><?= ($priorityFilter ? '&priority='.$priorityFilter : '') ?><?= ($searchQuery ? '&q='.urlencode($searchQuery) : '') ?>"
           class="editorial-tab <?= ($statusTab === 'active') ? 'is-active' : '' ?>">
          Aktif
        </a>
        <a href="index.php?view=<?= e($currentView) ?>&tab=completed<?= ($timeFilter ? '&time='.$timeFilter : '') ?><?= ($subjectFilter ? '&subject_id='.$subjectFilter : '') ?><?= ($priorityFilter ? '&priority='.$priorityFilter : '') ?><?= ($searchQuery ? '&q='.urlencode($searchQuery) : '') ?>"
           class="editorial-tab <?= ($statusTab === 'completed') ? 'is-active' : '' ?>">
          Selesai
        </a>
      </div>

      <!-- 4 Filter Waktu: Hari Ini, Minggu Ini, Bulan Ini, Tahun Ini -->
      <div class="time-filter-pills">
        <a href="index.php?view=all<?= ($statusTab !== 'all' ? '&tab='.$statusTab : '') ?><?= ($subjectFilter ? '&subject_id='.$subjectFilter : '') ?><?= ($priorityFilter ? '&priority='.$priorityFilter : '') ?><?= ($searchQuery ? '&q='.urlencode($searchQuery) : '') ?>"
           class="time-pill <?= ($timeFilter === '') ? 'is-active' : '' ?>" title="Tampilkan Semua Waktu">
          Semua Waktu
        </a>
        <a href="index.php?view=today<?= ($statusTab !== 'all' ? '&tab='.$statusTab : '') ?><?= ($subjectFilter ? '&subject_id='.$subjectFilter : '') ?><?= ($priorityFilter ? '&priority='.$priorityFilter : '') ?><?= ($searchQuery ? '&q='.urlencode($searchQuery) : '') ?>"
           class="time-pill <?= ($timeFilter === 'today') ? 'is-active' : '' ?>" title="Filter Tugas Hari Ini">
          Hari Ini
        </a>
        <a href="index.php?view=this_week<?= ($statusTab !== 'all' ? '&tab='.$statusTab : '') ?><?= ($subjectFilter ? '&subject_id='.$subjectFilter : '') ?><?= ($priorityFilter ? '&priority='.$priorityFilter : '') ?><?= ($searchQuery ? '&q='.urlencode($searchQuery) : '') ?>"
           class="time-pill <?= ($timeFilter === 'this_week') ? 'is-active' : '' ?>" title="Filter Tugas Minggu Ini">
          Minggu Ini
        </a>
        <a href="index.php?view=this_month<?= ($statusTab !== 'all' ? '&tab='.$statusTab : '') ?><?= ($subjectFilter ? '&subject_id='.$subjectFilter : '') ?><?= ($priorityFilter ? '&priority='.$priorityFilter : '') ?><?= ($searchQuery ? '&q='.urlencode($searchQuery) : '') ?>"
           class="time-pill <?= ($timeFilter === 'this_month') ? 'is-active' : '' ?>" title="Filter Tugas Bulan Ini">
          Bulan Ini
        </a>
        <a href="index.php?view=this_year<?= ($statusTab !== 'all' ? '&tab='.$statusTab : '') ?><?= ($subjectFilter ? '&subject_id='.$subjectFilter : '') ?><?= ($priorityFilter ? '&priority='.$priorityFilter : '') ?><?= ($searchQuery ? '&q='.urlencode($searchQuery) : '') ?>"
           class="time-pill <?= ($timeFilter === 'this_year') ? 'is-active' : '' ?>" title="Filter Tugas Tahun Ini">
          Tahun Ini
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
        <!-- Filter dropdown waktu (Hari Ini, Minggu Ini, Bulan Ini, Tahun Ini) -->
        <div class="select-wrapper">
          <select id="filterTime" name="time" class="editorial-select">
            <option value="">Semua Waktu</option>
            <option value="today" <?= ($timeFilter === 'today') ? 'selected' : '' ?>>Hari Ini</option>
            <option value="this_week" <?= ($timeFilter === 'this_week') ? 'selected' : '' ?>>Minggu Ini</option>
            <option value="this_month" <?= ($timeFilter === 'this_month') ? 'selected' : '' ?>>Bulan Ini</option>
            <option value="this_year" <?= ($timeFilter === 'this_year') ? 'selected' : '' ?>>Tahun Ini</option>
          </select>
        </div>

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

  <!-- === 4. DAFTAR BARIS TUGAS / KOTAK BULAN TAHUN INI === -->
  <section class="editorial-tasks-section">
    <?php if ($isYearView): ?>
      <!-- === TAMPILAN KHUSUS TAHUN INI: KOTAK-KOTAK 12 BULAN (JANUARI - DESEMBER) === -->
      <div class="year-view-container">
        
        <!-- Grid kotak-kotak 12 bulan -->
        <div class="year-months-grid" id="yearMonthsGrid">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <?php
              $mCount = $monthStats[$m]['total'];
              $mActive = $monthStats[$m]['active'];
              $mDone = $monthStats[$m]['completed'];
              $isCurMonth = ($m === $currentMonthNum);
              $mPad = str_pad($m, 2, '0', STR_PAD_LEFT);
              $mName = $monthNames[$m];
            ?>
            <!-- Kotak Bulan (klik untuk melihat tugas bulan ini) -->
            <div class="year-month-card js-year-month-card <?= $isCurMonth ? 'is-current-month' : '' ?>"
                 data-month="<?= $m ?>"
                 data-month-pad="<?= $mPad ?>"
                 data-month-name="<?= e($mName) ?>"
                 data-total="<?= $mCount ?>"
                 data-active="<?= $mActive ?>"
                 data-completed="<?= $mDone ?>"
                 role="button"
                 tabindex="0"
                 aria-label="Bulan <?= e($mName) ?>, <?= $mCount ?> tugas">
              
              <div class="month-card-header">
                <span class="month-card-num"><?= $mPad ?></span>
                <?php if ($isCurMonth): ?>
                  <span class="month-badge-current">Bulan Ini</span>
                <?php endif; ?>
              </div>

              <div class="month-card-body">
                <h3 class="month-card-name"><?= e($mName) ?></h3>
                <span class="month-card-year"><?= $currentYear ?></span>
              </div>

              <div class="month-card-footer">
                <span class="month-task-pill <?= ($mCount > 0) ? 'has-tasks' : 'is-empty' ?>">
                  <?= $mCount ?> Tugas
                </span>
                <span class="month-subtext">
                  <?php if ($mCount > 0): ?>
                    <?= $mActive ?> aktif &bull; <?= $mDone ?> selesai
                  <?php else: ?>
                    Kosong
                  <?php endif; ?>
                </span>
              </div>
            </div>
          <?php endfor; ?>
        </div>

        <!-- Banner petunjuk sebelum kotak bulan diklik -->
        <div class="year-month-prompt-banner" id="yearMonthPromptBanner">
          <div class="prompt-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <div class="prompt-text">
            <h4>Pilih Salah Satu Bulan di Atas</h4>
            <p>Klik kotak bulan (Januari – Desember) untuk membuka dan melihat daftar tugas pada bulan tersebut.</p>
          </div>
        </div>

        <!-- Wadah detail tugas dari bulan yang dipilih -->
        <div class="year-month-detail-panel" id="yearMonthDetailPanel" style="display: none;">
          <!-- Header info bulan terpilih -->
          <div class="month-detail-header">
            <div class="month-detail-title-group">
              <div class="month-detail-badge" id="activeMonthBadge">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span id="activeMonthBadgeNum">Bulan 01</span>
              </div>
              <div>
                <h2 class="month-detail-title" id="activeMonthTitle">Tugas Bulan Januari <?= $currentYear ?></h2>
                <p class="month-detail-summary" id="activeMonthSummary">0 Tugas</p>
              </div>
            </div>

            <div class="month-detail-actions">
              <!-- Tombol tambah tugas langsung di bulan ini -->
              <button type="button" class="btn btn-primary btn-sm js-add-task-month-btn" id="btnAddTaskForMonth">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>+ Tambah Tugas di Bulan Ini</span>
              </button>
              <!-- Tombol tutup/sembunyikan daftar tugas bulan -->
              <button type="button" class="btn btn-editorial-outline btn-sm" id="btnCloseMonthDetail" title="Tutup rincian bulan">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                <span>Tutup</span>
              </button>
            </div>
          </div>

          <!-- 12 Grup Tugas untuk Masing-Masing Bulan (1..12) -->
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <?php
              $mTasks = $tasksByMonth[$m];
              $mCount = count($mTasks);
              $mName = $monthNames[$m];
              $mPad = str_pad($m, 2, '0', STR_PAD_LEFT);
            ?>
            <div class="month-tasks-group" id="monthTasksGroup-<?= $m ?>" data-month="<?= $m ?>" data-month-pad="<?= $mPad ?>" data-month-name="<?= e($mName) ?>" style="display: none;">
              <?php if (empty($mTasks)): ?>
                <!-- Tampilan kosong jika belum ada tugas di bulan ini -->
                <div class="month-empty-state">
                  <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                  </div>
                  <h4>Belum Ada Tugas di Bulan <?= e($mName) ?> <?= $currentYear ?></h4>
                  <p>Belum ada tenggat tugas sekolah yang tercatat untuk bulan ini.</p>
                  <button type="button" class="btn btn-primary btn-sm js-add-task-month-btn" data-month-pad="<?= $mPad ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="12" y1="5" x2="12" y2="19"></line>
                      <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>+ Buat Tugas Bulan <?= e($mName) ?></span>
                  </button>
                </div>
              <?php else: ?>
                <!-- Daftar tugas yang ada di bulan ini -->
                <div class="editorial-task-list">
                  <?php foreach ($mTasks as $task): ?>
                    <?php $renderTaskRow($task, false); ?>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endfor; ?>
        </div>

      </div>

    <?php else: ?>
      <!-- === TAMPILAN DAFTAR TUGAS STANDAR (SEMUA TUGAS, HARI INI, MINGGU INI, BULAN INI, SELESAI) === -->
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
            <?php if ($searchQuery !== '' || $subjectFilter > 0 || $statusFilter !== '' || $priorityFilter !== '' || $statusTab !== 'all' || $timeFilter !== ''): ?>
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
            <?php $renderTaskRow($task, $canReorder); ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php
// Tampilkan footer dan drawer formulir
$modalSubjects = $subjects;
require_once __DIR__ . '/includes/footer.php';
?>
