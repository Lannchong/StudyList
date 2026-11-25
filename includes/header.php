<?php
// HEADER WEBSITE: TAG HEAD, SCRIPT TEMA & NOTIFIKASI 
require_once __DIR__ . '/../config/database.php';

$pageTitle = $pageTitle ?? 'StudyList';
$pageSubtitle = $pageSubtitle ?? 'Daftar lengkap seluruh tugas dan catatan belajar sekolah';
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — StudyList</title>
  
  <!-- Script cek tema tersimpan (Light/Dark Mode) agar tidak berkedip saat refresh -->
  <script>
    (function() {
      var savedTheme = localStorage.getItem('studylist_theme') || 'light'; // Ambil tema tersimpan
      document.documentElement.setAttribute('data-theme', savedTheme);     // Pasang tema ke HTML
    })();
  </script>

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css"> <!-- File CSS Desain Website -->
</head>
<body class="editorial-body">

<div class="editorial-site-wrapper">
  <!-- Navigasi horizontal bagian atas -->
  <?php require_once __DIR__ . '/navbar.php'; ?>

  <!-- Area konten utama -->
  <main class="editorial-main-content">
    <div class="editorial-container">

      <!-- Notifikasi jika koneksi database MySQL terputus -->
      <?php if (!empty($dbError)): ?>
        <div class="alert-banner error">
          <div class="alert-content">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><strong>Kesalahan Database:</strong> <?= e($dbError) ?></span>
          </div>
        </div>
      <?php endif; ?>

      <!-- Notifikasi pesan aksi (sukses / gagal simpan data) -->
      <?php if ($flash): ?>
        <div class="alert-banner <?= e($flash['type']) ?>">
          <div class="alert-content">
            <?php if ($flash['type'] === 'success'): ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
            <?php else: ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            <?php endif; ?>
            <span><?= e($flash['message']) ?></span>
          </div>
          <button type="button" class="alert-close" aria-label="Tutup Notifikasi">&times;</button>
        </div>
      <?php endif; ?>
