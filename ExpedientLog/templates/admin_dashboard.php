<?php
require_once 'config.php';

// Security: Only supervisors & admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['supervisor', 'admin'])) {
    header("Location: login.php");
    exit();
}

// Auto logout after 30 mins
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    header("Location: logout.php");
    exit();
}
$_SESSION['last_activity'] = time();

// Today's date
$today = date('Y-m-d');
$today_display = date('l, j F Y');

// Stats
$total_employees = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetchColumn();
$active_today = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM tickets WHERE DATE(created_at) = '$today'")->fetchColumn();
$total_logs_today = $pdo->query("SELECT COUNT(*) FROM tickets WHERE DATE(created_at) = '$today'")->fetchColumn();
$avg_logs = $active_today > 0 ? round($total_logs_today / $active_today, 1) : 0;

// Department breakdown
$dept_stmt = $pdo->query("SELECT department, COUNT(*) as count FROM users WHERE role = 'employee' GROUP BY department");
$dept_breakdown = $dept_stmt->fetchAll();

// Today's activity
$activity_stmt = $pdo->prepare("
    SELECT u.username, u.department, 
           COUNT(t.id) as logs,
           MAX(t.created_at) as last_log
    FROM users u
    LEFT JOIN tickets t ON u.id = t.user_id AND DATE(t.created_at) = ?
    WHERE u.role = 'employee'
    GROUP BY u.id
    ORDER BY logs DESC, last_log DESC
");
$activity_stmt->execute([$today]);
$today_activity = $activity_stmt->fetchAll();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Supervisor Dashboard • ExpedientLog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" href="logo.png" type="image/png">
  <style>
    body { font-family: 'Inter', sans-serif; background: #f4f6f9; }
    .navbar { background: #001f3f !important; }
    .card { transition: 0.3s; }
    .card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.15) !important; }
    .status-excellent { color: #006633; font-weight: bold; }
    .status-active { color: #FF9933; }
    .status-inactive { color: #d32f2f; font-weight: bold; }
    .dept-box { background: #e3f2fd; padding: 1rem; border-radius: 1rem; text-align: center; }
    .live-time { font-weight: bold; color: #006633; }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-dark shadow-lg">
    <div class="container-fluid">
      <span class="navbar-brand fw-bold">ExpedientLog • Supervisor</span>
      <div class="d-flex align-items-center gap-3">
        <span class="text-white">Hi, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
        <div class="dropdown d-inline-block me-2">
          <button class="btn btn-warning btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
            Admin Tools
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addUserModal">
              <i class="bi bi-person-plus"></i> Add New Employee
            </a></li>
            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#manageUsersModal">
              <i class="bi bi-people"></i> Manage Users
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="container py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h2 fw-bold text-primary">Supervisor Dashboard</h1>
        <p class="text-muted">Real-time team performance • <?= htmlspecialchars($today_display) ?></p>
      </div>
      <div class="text-end">
        <span class="badge bg-success fs-5 px-4 py-2">ZNBC Internal • LIVE</span>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="row g-4 mb-5">
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-primary text-white">
          <div class="card-body text-center">
            <h5>Total Staff</h5>
            <h2 class="fw-bold"><?= $total_employees ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-success text-white">
          <div class="card-body text-center">
            <h5>Active Today</h5>
            <h2 class="fw-bold"><?= $active_today ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-warning text-white">
          <div class="card-body text-center">
            <h5>Logs Today</h5>
            <h2 class="fw-bold"><?= $total_logs_today ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-info text-white">
          <div class="card-body text-center">
            <h5>Avg. per Active</h5>
            <h2 class="fw-bold"><?= $avg_logs ?></h2>
          </div>
        </div>
      </div>
    </div>

    <!-- Department Breakdown -->
    <div class="card shadow-sm mb-5">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Department Overview</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <?php foreach ($dept_breakdown as $dept): ?>
          <div class="col-md-3">
            <div class="dept-box">
              <strong><?= htmlspecialchars($dept['department']) ?></strong><br>
              <span class="text-primary fs-4 fw-bold"><?= $dept['count'] ?></span> staff
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Live Activity Table -->
    <div class="card shadow-sm">
      <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Today's Activity • <span class="live-time" id="live-time"></span></h5>
        <small>Auto-refreshes every 30s</small>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Employee</th>
              <th>Department</th>
              <th>Logs</th>
              <th>Last Entry</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($today_activity as $row): ?>
            <tr>
              <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
              <td><span class="text-muted"><?= htmlspecialchars($row['department']) ?></span></td>
              <td>
                <span class="badge bg-<?= $row['logs'] >= 3 ? 'success' : ($row['logs'] > 0 ? 'warning' : 'secondary') ?>">
                  <?= $row['logs'] ?>
                </span>
              </td>
              <td><?= $row['last_log'] ? substr($row['last_log'], 11, 5) : '—' ?></td>
              <td>
                <?php if ($row['logs'] >= 3): ?>
                  <span class="status-excellent">Excellent</span>
                <?php elseif ($row['logs'] > 0): ?>
                  <span class="status-active">Active</span>
                <?php else: ?>
                  <span class="status-inactive">Inactive</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="text-center mt-4">
      <a href="export_today.php" class="btn btn-success">Export Today's Logs (Excel)</a>
    </div>
  </div>

  <script>
    // Live Zambian Time
    function updateTime() {
      const now = new Date().toLocaleString('en-US', { timeZone: 'Africa/Lusaka' });
      document.getElementById('live-time').textContent = now.split(', ')[1].slice(0, -3);
    }
    updateTime();
    setInterval(updateTime, 1000);

    // Auto refresh dashboard
    setInterval(() => location.reload(), 30000);
  </script>

  <!-- Add User Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add New Employee</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form action="add_user.php" method="post">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Initial Password</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Department</label>
                <select name="department" class="form-select" required>
                  <?php foreach($allowed_departments as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Add Employee</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Manage Users Modal -->
  <div class="modal fade" id="manageUsersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Manage Users</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Username</th>
                  <th>Department</th>
                  <th>Role</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $users_stmt = $pdo->query("SELECT id, username, department, role FROM users ORDER BY username");
                while($user = $users_stmt->fetch()):
                ?>
                <tr>
                  <td><?= htmlspecialchars($user['username']) ?></td>
                  <td><?= htmlspecialchars($user['department']) ?></td>
                  <td><span class="badge bg-<?= $user['role'] === 'supervisor' ? 'danger' : 'primary' ?>">
                    <?= ucfirst(htmlspecialchars($user['role'])) ?>
                  </span></td>
                  <td>
                    <button class="btn btn-sm btn-warning" onclick="resetPassword(<?= $user['id'] ?>)">
                      Reset Password
                    </button>
                    <?php if ($user['role'] !== 'supervisor'): ?>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $user['id'] ?>)">
                      Delete
                    </button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // User management functions
    function resetPassword(userId) {
      if (confirm('Reset password for this user?')) {
        location.href = `reset_password.php?id=${userId}`;
      }
    }

    function deleteUser(userId) {
      if (confirm('Are you sure you want to delete this user? This cannot be undone.')) {
        location.href = `delete_user.php?id=${userId}`;
      }
    }

    // Handle server-side messages
    <?php if (isset($_GET['message'])): ?>
      alert(<?= json_encode($_GET['message']) ?>);
    <?php endif; ?>
  </script>
</body>
</html>