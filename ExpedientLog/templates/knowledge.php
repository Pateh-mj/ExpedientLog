<?php 
require_once 'config.php'; 
if (!isset($_SESSION['user_id'])) header("Location: login.php");

$search = $_GET['q'] ?? '';
$cat = $_GET['cat'] ?? '';

// --- FIX 1: Include image_path in the SELECT statement ---
$sql = "SELECT t.*, u.username, t.image_path FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.is_knowledge = 1";
// --------------------------------------------------------

if ($search) $sql .= " AND t.task LIKE ?";
if ($cat && $cat !== 'all') $sql .= " AND t.category = ?";
$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$params = [];
if ($search) $params[] = "%$search%";
if ($cat && $cat !== 'all') $params[] = $cat;
$stmt->execute($params);
$kb = $stmt->fetchAll();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Knowledge Base • ExpedientLog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #f8f9fa; }
    .kb-card { transition: all 0.3s; border-left: 4px solid #0066FF; }
    .kb-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .badge-cat { background: #0066FF; color: white; }
    </style>
  </head>
  <body>
    <nav class="navbar navbar-dark bg-primary sticky-top shadow">
    <div class="container-fluid">
      <a href="#" class="navbar-brand">ExpedientLog</a>
      <?php
      $dashboard = (
        (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) ||
        (isset($_SESSION['role']) && $_SESSION['role'] === 'supervisor' || $_SESSION['role'] === 'admin') // Corrected role check
      ) ? 'admin_dashboard.php' : 'dashboard.php';
      ?>
      <a href="<?= $dashboard ?>" class="btn btn-outline-light">
      <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
      </a>
    </div>
    </nav>

    <div class="container py-5">
    <div class="row mb-4">
      <div class="col-md-8">
        <h1 class="display-5 fw-bold">Knowledge Base</h1>
        <p class="text-muted">Permanent team memory — everything worth remembering</p>
      </div>
    </div>

    <form class="mb-4">
      <div class="row g-3">
        <div class="col-md-6">
          <input type="text" name="q" class="form-control form-control-lg" placeholder="Search knowledge…" value="<?=htmlspecialchars($search)?>">
        </div>
        <div class="col-md-4">
          <select name="cat" class="form-select form-select-lg">
            <option value="all" <?= ($cat === 'all' || $cat === '') ? 'selected' : '' ?>>All Categories</option>
            <option value="SOP / Procedure" <?= ($cat === 'SOP / Procedure') ? 'selected' : '' ?>>SOP / Procedure</option>
            <option value="Client Notes" <?= ($cat === 'Client Notes') ? 'selected' : '' ?>>Client Notes</option>
            <option value="Templates" <?= ($cat === 'Templates') ? 'selected' : '' ?>>Templates</option>
            <option value="Lessons Learned" <?= ($cat === 'Lessons Learned') ? 'selected' : '' ?>>Lessons Learned</option>
            <option value="Contacts" <?= ($cat === 'Contacts') ? 'selected' : '' ?>>Contacts</option>
            <option value="IT / Tech" <?= ($cat === 'IT / Tech') ? 'selected' : '' ?>>IT / Tech</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary btn-lg w-100">Search</button>
        </div>
      </div>
    </form>

    <div class="row g-4">
      <?php foreach ($kb as $item): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card kb-card h-100 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
              <span class="badge badge-cat"><?=htmlspecialchars($item['category'])?></span>
              <small class="text-muted"><?=date('j M Y', strtotime($item['created_at']))?></small>
            </div>
            <p class="fw-bold mb-2"><?=htmlspecialchars($item['task'])?></p>
            
            <?php if ($item['image_path']): ?>
                <p class="mt-3 mb-3">
                    <a href="<?= htmlspecialchars($item['image_path']) ?>" target="_blank" class="btn btn-sm btn-outline-info fw-medium">
                        <i class="fas fa-image me-1"></i> View Attachment
                    </a>
                </p>
            <?php endif; ?>
            <small class="text-success">Added by <?=htmlspecialchars($item['username'])?></small>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (empty($kb)): ?>
      <div class="text-center py-5">
        <h3 class="text-muted">No knowledge saved yet</h3>
        <p>Start ticking "Save as Reusable Knowledge" when logging!</p>
      </div>
    <?php endif; ?>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>