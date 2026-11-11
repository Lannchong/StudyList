<?php
/**
 * Header Component — Editorial Study Planner
 * StudyList — School Task Manager
 */
require_once __DIR__ . '/../config/database.php';

$pageTitle = $pageTitle ?? 'StudyList';
$pageSubtitle = $pageSubtitle ?? 'Kelola tugas dan aktivitas sekolah dengan tenang dan terorganisir';
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — StudyList</title>
  <!-- Google Fonts for Editorial Typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="editorial-body">

<div class="editorial-site-wrapper">
  <!-- Horizontal Top Navigation Bar -->
  <?php require_once __DIR__ . '/navbar.php'; ?>

  <!-- Main Content Body -->
  <main class="editorial-main-content">
    <div class="editorial-container">

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

      <?php if ($flash): ?>
        <div class="alert-banner <?= e($flash['type']) ?>">
          <div class="alert-content">
            <?php if ($flash['type'] === 'success'): ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
            <?php elseif ($flash['type'] === 'error'): ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
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
          <button type="button" class="alert-close" aria-label="Tutup Pemberitahuan">&times;</button>
        </div>
      <?php endif; ?>
