<?php
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Auto logout after 30 minutes inactivity
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    header("Location: logout.php");
    exit();
}
$_SESSION['last_activity'] = time();

// Handle new task submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task = trim($_POST['task']);
    if (!empty($task) && strlen($task) <= 500) {
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, task) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $task]);
    }
    header("Location: dashboard.php");
    exit();
}

// Get today's date (Zambian format)
$today = date('l, j F Y'); // e.g., Monday, 10 November 2025
$today_db = date('Y-m-d');

// Fetch today's tasks
$stmt = $pdo->prepare("
    SELECT task, created_at 
    FROM tickets 
    WHERE user_id = ? AND DATE(created_at) = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $today_db]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ExpedientLog • <?= htmlspecialchars($_SESSION['username'] ?? '') ?>'s Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" href="logo.png" type="image/png">
  <style>
    body { font-family: 'Inter', sans-serif; background: #f8f9fa; }
    .navbar { background: #003366 !important; }
    .sticky-top { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); }
    .task-item { transition: all 0.2s; }
    .task-item:hover { background: #e3f2fd; transform: translateX(5px); }
    .badge-today { background: #006633; }
  </style>
</head>
<body>

  <!-- Top Navbar -->
  <nav class="navbar navbar-dark shadow-sm">
    <div class="container-fluid">
      <span class="navbar-brand fw-bold">ExpedientLog</span>
      <div class="d-flex align-items-center gap-3">
  <span class="text-white small">Hi, <strong><?= htmlspecialchars($_SESSION['username'] ?? '') ?></strong></span>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
  <h1 class="h3 mb-0">Today – <?= htmlspecialchars($today) ?></h1>
  <span class="badge badge-today fs-5 px-3 py-2"><?= count($tasks) ?> logged</span>
    </div>

    <!-- Task Input -->
    <form method="post" class="sticky-top bg-light pt-3 pb-2" style="top: 56px; z-index: 1000; border-bottom: 1px solid #dee2e6;">
      <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
        <input type="text" name="task" class="form-control border-0" 
               placeholder="What did you just do today?" required maxlength="500" autocomplete="off">
        <button class="btn btn-primary px-4 fw-bold">
          <span class="d-none d-sm-inline">Log It</span>
          <span class="d-inline d-sm-none">Send</span>
        </button>
      </div>
      <small class="text-muted d-block text-center mt-2">Tap anywhere to type fast • Max 500 chars</small>
    </form>

    <!-- Task List -->
    <div class="mt-4">
      <?php if ($tasks): ?>
        <div class="list-group list-group-flush shadow-sm rounded-3 overflow-hidden">
          <?php foreach ($tasks as $task): ?>
            <div class="list-group-item task-item px-3 py-4 border-start-0 border-end-0 border-top-0">
              <div class="d-flex justify-content-between align-items-start">
                <div class="me-3 flex-grow-1">
                  <p class="mb-1 fw-medium"><?= htmlspecialchars($task['task']) ?></p>
                </div>
                <small class="text-muted text-nowrap"><?= substr($task['created_at'], 11, 5) ?></small>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center py-5">
          <div class="display-1 text-muted mb-3">Empty</div>
          <p class="text-muted fs-5">No logs yet today.<br>Be the first to log something awesome!</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="text-center mt-5 text-muted small">
  <p>Expedia Internal System • Lusaka, Zambia • <?= date('h:i A') ?> CAT</p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>