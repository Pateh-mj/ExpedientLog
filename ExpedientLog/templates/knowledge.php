<?php require_once 'config.php'; 
if (!isset($_SESSION['user_id'])) header("Location: login.php");

$search = $_GET['q'] ?? '';
$cat = $_GET['cat'] ?? '';

$sql = "SELECT t.*, u.username FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.is_knowledge = 1";
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #f4f6f9; }
    .header-bg { background-color: #3f51b5; color: white; }
    .kb-card { border-left: 5px solid #3f51b5; }
    .badge-cat { background-color: #e8eaf6; color: #3f51b5; font-weight: 500; }
  </style>
</head>
<body>
<div class="header-bg py-4 mb-4 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <h3 class="mb-0 fw-bold">Knowledge Base</h3>
        <a href="dashboard.php" class="btn btn-sm btn-outline-light">← Back to Dashboard</a>
    </div>
</div>

<div class="container">
    <form class="mb-5" method="GET">
      <div class="row g-3 align-items-end">
        <div class="col-md-7">
          <label for="search-query" class="form-label fw-semibold">Search Tasks/Solutions</label>
          <input type="text" id="search-query" name="q" class="form-control form-control-lg" 
                 placeholder="Search by keyword..." value="<?=htmlspecialchars($search)?>">
        </div>
        <div class="col-md-3">
          <label for="category-filter" class="form-label fw-semibold">Filter Category</label>
          <select id="category-filter" name="cat" class="form-select form-select-lg">
            <option value="all">All Categories</option>
            <?php 
            // List of hardcoded categories (should match those in dashboard.php)
            $categories = ['SOP / Procedure', 'Client Notes', 'Templates', 'Lessons Learned', 'Contacts', 'IT / Tech'];
            foreach ($categories as $c) {
                $selected = ($cat === $c) ? 'selected' : '';
                echo "<option value=\"".htmlspecialchars($c)."\" $selected>".htmlspecialchars($c)."</option>";
            }
            ?>
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
            <small class="text-success">Added by <?=htmlspecialchars($item['username'])?></small>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (empty($kb)): ?>
        <div class="alert alert-info text-center mt-5">
            <h4 class="alert-heading">No Results Found</h4>
            <p>Try refining your search query or selecting a different category.</p>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>