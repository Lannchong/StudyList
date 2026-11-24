<?php
// === NAVBAR ATAS: LOGO, MENU, TOMBOL TEMA & TAMBAH TUGAS ===
$currentView = $currentView ?? 'all';

// Hitung jumlah tugas untuk angka badge di menu
$todayCount = 0;
$completedCount = 0;

if (!empty($pdo)) {
    try {
        $todayStmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE due_date = CURDATE() AND status != 'completed'");
        $todayCount = (int)$todayStmt->fetchColumn();

        $compStmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
        $completedCount = (int)$compStmt->fetchColumn();
    } catch (Exception $e) {
        // Abaikan jika database belum siap
    }
}
?>
<header class="editorial-navbar">
  <div class="navbar-container">
    
    <!-- Logo & Nama Website -->
    <a href="index.php" class="navbar-brand">
      <div class="brand-glyph">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
      </div>
      <div class="brand-info">
        <span class="brand-title">StudyList</span>
        <span class="brand-tagline">Study Planner</span>
      </div>
    </a>

    <!-- Menu Navigasi Utama (Semua Tugas, Kalender, Hari Ini, Selesai) -->
    <nav class="navbar-nav">
      <ul class="nav-links-list">
        <!-- 1. Semua Tugas (Halaman Utama) -->
        <li>
          <a href="index.php?view=all" class="nav-link <?= ($currentView === 'all') ? 'is-active' : '' ?>">
            <span>Semua Tugas</span>
          </a>
        </li>
        <!-- 2. Kalender -->
        <li>
          <a href="index.php?view=calendar" class="nav-link <?= ($currentView === 'calendar') ? 'is-active' : '' ?>">
            <span>Kalender</span>
          </a>
        </li>
        <!-- 3. Hari Ini -->
        <li>
          <a href="index.php?view=today" class="nav-link <?= ($currentView === 'today') ? 'is-active' : '' ?>">
            <span>Hari Ini</span>
            <?php if ($todayCount > 0): ?>
              <span class="nav-counter"><?= $todayCount ?></span>
            <?php endif; ?>
          </a>
        </li>
        <!-- 4. Selesai -->
        <li>
          <a href="index.php?view=completed" class="nav-link <?= ($currentView === 'completed') ? 'is-active' : '' ?>">
            <span>Selesai</span>
            <?php if ($completedCount > 0): ?>
              <span class="nav-counter"><?= $completedCount ?></span>
            <?php endif; ?>
          </a>
        </li>
      </ul>
    </nav>

    <!-- Tombol Aksi: Ganti Tema & Tambah Tugas -->
    <div class="navbar-actions">
      <!-- Tombol Ganti Tema (Dark Mode / Light Mode) -->
      <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Ganti Tema Tampilan" title="Ganti Tema (Dark / Light)">
        <!-- Ikon Matahari (muncul saat Dark Mode untuk ganti ke Light Mode) -->
        <svg class="theme-icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="5"></circle>
          <line x1="12" y1="1" x2="12" y2="3"></line>
          <line x1="12" y1="21" x2="12" y2="23"></line>
          <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
          <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
          <line x1="1" y1="12" x2="3" y2="12"></line>
          <line x1="21" y1="12" x2="23" y2="12"></line>
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
          <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        </svg>
        <!-- Ikon Bulan (muncul saat Light Mode untuk ganti ke Dark Mode) -->
        <svg class="theme-icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
      </button>

      <!-- Tombol Tambah Tugas (Buka Drawer) -->
      <button type="button" class="btn btn-editorial-primary js-open-add-drawer" title="Tambah Tugas Baru">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Tugas</span>
      </button>

      <!-- Tombol Menu Mobile (layar kecil) -->
      <button type="button" class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Buka Menu Navigasi">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Tugas</span>
      </button>

      <!-- Tombol Menu Mobile (Layar Kecil) -->
      <button type="button" class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Buka Menu Navigasi">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
    </div>
  </div>

  <!-- Menu Navigasi Mobile (4 Menu) -->
  <div class="mobile-nav-menu" id="mobileNavMenu">
    <ul class="mobile-nav-list">
      <li>
        <a href="index.php?view=all" class="mobile-nav-link <?= ($currentView === 'all') ? 'is-active' : '' ?>">
          <span>Semua Tugas</span>
        </a>
      </li>
      <li>
        <a href="index.php?view=calendar" class="mobile-nav-link <?= ($currentView === 'calendar') ? 'is-active' : '' ?>">
          <span>Kalender</span>
        </a>
      </li>
      <li>
        <a href="index.php?view=today" class="mobile-nav-link <?= ($currentView === 'today') ? 'is-active' : '' ?>">
          <span>Hari Ini</span>
          <?php if ($todayCount > 0): ?>
            <span class="nav-counter"><?= $todayCount ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li>
        <a href="index.php?view=completed" class="mobile-nav-link <?= ($currentView === 'completed') ? 'is-active' : '' ?>">
          <span>Selesai</span>
          <?php if ($completedCount > 0): ?>
            <span class="nav-counter"><?= $completedCount ?></span>
          <?php endif; ?>
        </a>
      </li>
    </ul>
  </div>
</header>
