<?php
$user     = auth();
$initials = strtoupper(substr($user['full_name'] ?: $user['username'], 0, 2));
$current  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base     = BASE_PATH;

$nav_active = function(string $path) use ($current, $base): string {
    return str_starts_with($current, $base . $path) ? ' active' : '';
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(\App\Core\CSRF::token()) ?>">
  <meta name="base-url"   content="<?= e(BASE_PATH) ?>">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
</head>
<body>

<div id="sidebar-overlay" class="sidebar-overlay"></div>

<div class="app-wrapper">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <a href="<?= url('dashboard') ?>" class="sidebar-brand">
      <div class="sidebar-brand-icon"><i class="fas fa-bolt"></i></div>
      <div>
        <div class="sidebar-brand-name"><?= e(APP_NAME) ?></div>
        <span class="sidebar-brand-tag">Employee Portal</span>
      </div>
    </a>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Workspace</div>
      <a href="<?= url('dashboard') ?>" class="sidebar-link<?= $nav_active('/dashboard') ?>">
        <i class="fas fa-table-cells-large"></i> Dashboard
      </a>
      <a href="<?= url('knowledge') ?>" class="sidebar-link<?= $nav_active('/knowledge') ?>">
        <i class="fas fa-lightbulb"></i> Knowledge Base
      </a>

      <div class="nav-section-label" style="margin-top:.5rem">Company</div>
      <a href="<?= url('announcements') ?>" class="sidebar-link<?= $nav_active('/announcements') ?>">
        <i class="fas fa-bullhorn"></i> Announcements
      </a>

      <div class="nav-section-label" style="margin-top:.5rem">Account</div>
      <a href="<?= url('profile') ?>" class="sidebar-link<?= $nav_active('/profile') ?>">
        <i class="fas fa-user-circle"></i> My Profile
      </a>
      <a href="<?= url('logout') ?>" class="sidebar-link">
        <i class="fas fa-sign-out-alt"></i> Sign Out
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-avatar"><?= e($initials) ?></div>
        <div>
          <div class="sidebar-user-name"><?= e($user['full_name'] ?: $user['username']) ?></div>
          <div class="sidebar-user-role"><?= e($user['department']) ?></div>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="main-area">
    <header class="topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Menu">
          <i class="fas fa-bars"></i>
        </button>
        <div>
          <span class="topbar-title"><?= isset($pageTitle) ? e($pageTitle) : e(APP_NAME) ?></span>
          <?php if (isset($pageSubtitle)): ?>
            <span class="topbar-subtitle"><?= e($pageSubtitle) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="topbar-right">
        <span class="topbar-clock" id="topbar-clock"></span>
      </div>
    </header>

    <main class="page-content">
      <?php if (\App\Core\Session::hasFlash('success')): ?>
        <div class="alert alert-success" data-dismiss>
          <i class="fas fa-check-circle"></i>
          <span><?= \App\Core\Session::getFlash('success') ?></span>
          <button class="alert-close ms-auto" style="background:none;border:none;cursor:pointer;padding:0;margin-left:auto">×</button>
        </div>
      <?php endif; ?>
      <?php if (\App\Core\Session::hasFlash('error')): ?>
        <div class="alert alert-danger" data-dismiss>
          <i class="fas fa-exclamation-circle"></i>
          <span><?= e(\App\Core\Session::getFlash('error')) ?></span>
          <button class="alert-close" style="background:none;border:none;cursor:pointer;padding:0;margin-left:auto">×</button>
        </div>
      <?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
