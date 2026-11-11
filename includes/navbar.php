<?php
/**
 * Top Horizontal Navigation Component — Editorial Study Planner
 * StudyList — School Task Manager
 */
$currentView = $currentView ?? 'overview';

$todayCount = 0;
$completedCount = 0;

if (!empty($pdo)) {
    try {
        $todayStmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE due_date = CURDATE() AND status != 'completed'");
        $todayCount = (int)$todayStmt->fetchColumn();

        $compStmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
        $completedCount = (int)$compStmt->fetchColumn();
    } catch (Exception $e) {
        // Silently handle
    }
}
?>
<header class="editorial-navbar">
  <div class="navbar-container">
    <!-- Brand Logo & Name (Kiri) -->
    <a href="index.php?view=overview" class="navbar-brand">
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

    <!-- Menu Navigasi Horizontal (Tengah) -->
    <nav class="navbar-nav">
      <ul class="nav-links-list">
        <li>
          <a href="index.php?view=overview" class="nav-link <?= ($currentView === 'overview') ? 'is-active' : '' ?>">
            <span>Ringkasan</span>
          </a>
        </li>
        <li>
          <a href="index.php?view=all" class="nav-link <?= ($currentView === 'all') ? 'is-active' : '' ?>">
            <span>Semua Tugas</span>
          </a>
        </li>
        <li>
          <a href="index.php?view=calendar" class="nav-link <?= ($currentView === 'calendar') ? 'is-active' : '' ?>">
            <span>Kalender</span>
          </a>
        </li>
        <li>
          <a href="index.php?view=today" class="nav-link <?= ($currentView === 'today') ? 'is-active' : '' ?>">
            <span>Hari Ini</span>
            <?php if ($todayCount > 0): ?>
              <span class="nav-counter"><?= $todayCount ?></span>
            <?php endif; ?>
          </a>
        </li>
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

    <!-- Tombol Aksi Tambah Tugas (Kanan) -->
    <div class="navbar-actions">
      <button type="button" class="btn btn-editorial-primary js-open-add-drawer">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Tugas</span>
      </button>

      <!-- Mobile Menu Toggle Button -->
      <button type="button" class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Buka Menu Navigasi">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
    </div>
  </div>

  <!-- Mobile Collapsible Navigation Menu -->
  <div class="mobile-nav-menu" id="mobileNavMenu">
    <ul class="mobile-nav-list">
      <li>
        <a href="index.php?view=overview" class="mobile-nav-link <?= ($currentView === 'overview') ? 'is-active' : '' ?>">
          <span>Ringkasan</span>
        </a>
      </li>
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

