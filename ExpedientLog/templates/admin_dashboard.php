<?php
// FILE: admin_dashboard.php - The single, unified, secure dashboard

// NOTE: Ensure 'config.php' establishes $pdo (PDO connection) and starts the session.
require_once 'config.php'; 

// --- Session & Security Checks ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['supervisor','admin'])) {
    header("Location: login.php"); exit();
}

// Inactivity Timeout (30 minutes) - RECOMMENDED FIX 
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) { 
    header("Location: logout.php"); 
    exit(); 
}
$_SESSION['last_activity'] = time();

// --- Define allowed departments for forms ---
$allowed_departments = [
    'General' => 'General Operations',
    'News' => 'News & Editorial',
    'Technical' => 'Technical Support',
    'Finance' => 'Finance & Accounting',
    'HR' => 'Human Resources'
];

// --- Date and Filter Handling ---
$date_filter = $_GET['date'] ?? date('Y-m-d'); 
$department_filter = $_GET['dept'] ?? 'All';

// Basic validation
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
    $date_filter = date('Y-m-d'); 
}

$today_display = date('l, j F Y', strtotime($date_filter));
$dept_clause = ($department_filter !== 'All') ? " AND u.department = :department_filter" : "";

// Fetch all departments for the filter dropdown
$departments_query = $pdo->query("SELECT DISTINCT department FROM users WHERE role = 'employee' AND department IS NOT NULL AND department <> '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);
$all_departments = array_merge(['All'], $departments_query);

// Initialize variables to prevent undefined warnings
$stats = ['total_logs_today' => 0, 'active_today' => 0, 'total_staff' => 0, 'active_rate' => 0];
$all_logs = [];
$recent_logs = [];
$top_performers = [];
$projects = [];
$knowledge_leaders = [];
$max_tasks = 1;

// --- Secure Data Retrieval (All Data in one pass) ---
$params = [':date_filter' => $date_filter];
if ($department_filter !== 'All') {
    $params[':department_filter'] = $department_filter;
}

try {
    // 1. Core Daily Stats (KPIs correctly filtered)
    $sql_stats = "
        SELECT 
            COUNT(DISTINCT CASE WHEN DATE(t.created_at) = :date_filter THEN t.user_id END) as active_today, 
            COUNT(CASE WHEN DATE(t.created_at) = :date_filter THEN t.id END) as total_logs_today, 
            COUNT(DISTINCT u.id) as total_staff 
        FROM users u 
        LEFT JOIN tickets t ON u.id = t.user_id 
        WHERE u.role = 'employee' " . $dept_clause . " 
    ";
    $stmt_stats = $pdo->prepare($sql_stats);
    $stmt_stats->execute($params);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    $stats['active_rate'] = $stats['total_staff'] > 0 ? round(($stats['active_today'] / $stats['total_staff']) * 100, 1) : 0;
    
    // 2. Daily Activity Log - Now selecting t.id and t.image_path
    $sql_logs = "
        SELECT t.id, t.task, t.project, t.created_at, u.username, u.department, t.is_knowledge, t.image_path 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        WHERE DATE(t.created_at) = :date_filter AND u.role = 'employee' " . $dept_clause . " 
        ORDER BY t.created_at DESC
    ";
    $stmt_all_logs = $pdo->prepare($sql_logs);
    $stmt_all_logs->execute($params);
    $all_logs = $stmt_all_logs->fetchAll(PDO::FETCH_ASSOC);
    $recent_logs = array_slice($all_logs, 0, 8); // For notifications

    // 3. Top Performers (Filtered by Date/Dept)
    $sql_performers = "
        SELECT u.username, u.department, COUNT(t.id) as logs 
        FROM users u 
        LEFT JOIN tickets t ON u.id = t.user_id AND DATE(t.created_at) = :date_filter 
        WHERE u.role = 'employee' " . $dept_clause . " 
        GROUP BY u.id, u.username, u.department 
        ORDER BY logs DESC 
        LIMIT 10
    ";
    $stmt_performers = $pdo->prepare($sql_performers);
    $stmt_performers->execute($params);
    $top_performers = $stmt_performers->fetchAll(PDO::FETCH_ASSOC);

    // 4. Project Breakdown (Filtered by Date/Dept)
    $sql_projects = "
        SELECT project, COUNT(*) as tasks 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id
        WHERE DATE(t.created_at) = :date_filter AND u.role = 'employee' " . $dept_clause . " AND project NOT IN ('General','Personal','')
        GROUP BY project 
        ORDER BY tasks DESC 
        LIMIT 6
    ";
    $stmt_projects = $pdo->prepare($sql_projects);
    $stmt_projects->execute($params);
    $projects = $stmt_projects->fetchAll(PDO::FETCH_ASSOC);
    $max_tasks = max(array_column($projects, 'tasks') ?: [1]);

    // 5. Knowledge Leaders (All Time - Static)
    $stmt_kb_leaders = $pdo->prepare("
        SELECT u.username, u.department, COUNT(*) as contrib 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.is_knowledge = 1 AND u.role = 'employee' 
        GROUP BY u.id, u.username, u.department
        ORDER BY contrib DESC 
        LIMIT 8
    ");
    $stmt_kb_leaders->execute();
    $knowledge_leaders = $stmt_kb_leaders->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Admin Dashboard Error: ' . $e->getMessage());
    if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
        echo '<div class="alert alert-danger m-4">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pro Supervisor Dashboard • ExpedientLog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #004080; /* Dark Blue Primary */
            --secondary-color: #f0f3f8; /* Light BG/Header color */
            --accent-color: #10b981; /* Green Success */
            --text-dark: #212529;
            --shadow-subtle: 0 1px 3px rgba(0,0,0,0.05), 0 5px 15px rgba(0,0,0,0.03);
        }
        body { font-family: 'Inter', sans-serif; background: var(--secondary-color); color: var(--text-dark); }
        .header-bar { background: white; border-bottom: 1px solid #e9ecef; box-shadow: var(--shadow-subtle); z-index: 1030; }
        .card { border: none; border-radius: 12px; box-shadow: var(--shadow-subtle); background: white; }
        .kpi-number { font-size: 2.2rem; font-weight: 800; color: var(--primary-color); line-height: 1; }
        .kpi-label { font-size: 0.9rem; font-weight: 500; color: #6c757d; }
        .filter-group { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-subtle); }

        /* Scannable Data Sections */
        .data-header { color: var(--primary-color); font-weight: 700; margin-bottom: 1rem; border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem; }
        .log-table th { background-color: var(--secondary-color); font-weight: 600; color: var(--primary-color); }
        .knowledge-yes { background: #d4edda; color: #155724; font-weight: 600; padding: .3em .6em; border-radius: .3rem; font-size: 0.8rem; }

        /* Performers/Leaders List */
        .list-group-item-pro { 
            border: none; 
            border-bottom: 1px solid #f0f0f0; 
            padding: 1rem 0; 
        }
        .list-group-item-pro:last-child { border-bottom: none; }
        .list-item-badge { background-color: var(--primary-color); font-size: 0.85rem; }
        .list-item-kb-badge { background-color: var(--accent-color); font-size: 0.85rem; }

        /* Project Progress Bar */
        .progress { height: 6px; background-color: #e9ecef; }
        .progress-bar { background-color: var(--primary-color); }
    </style>
</head>
<body>

<div class="header-bar sticky-top py-3">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line me-2 text-primary"></i> ExpedientLog Pro</h3>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted fw-medium"><?= $today_display ?></span>
                <span class="badge bg-secondary-subtle text-dark fw-bold" id="clock"></span>
                <div class="dropdown">
                    <a class="text-dark text-decoration-none fw-medium" data-bs-toggle="dropdown">
                        <?= htmlspecialchars($_SESSION['username']) ?> <i class="fas fa-caret-down ms-1"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                        <li><h6 class="dropdown-header text-muted">Supervisor Access</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php?role=admin"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid py-5">
    
    <form method="GET" class="mb-5 filter-group">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="date-filter" class="form-label fw-bold text-muted mb-1"><i class="fas fa-calendar-alt me-2"></i> Target Date</label>
                <input type="date" name="date" id="date-filter" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
            </div>
            <div class="col-md-3">
                <label for="dept-filter" class="form-label fw-bold text-muted mb-1"><i class="fas fa-building me-2"></i> Department</label>
                <select name="dept" id="dept-filter" class="form-select">
                    <?php foreach($all_departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>" <?= ($dept === $department_filter) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 d-flex justify-content-end align-items-end gap-3">
                <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-filter me-2"></i> Apply Filters</button>
                <a href="knowledge.php" class="btn btn-success fw-bold"> Knowledge Base</a>
                <a href="export_today.php?date=<?= urlencode($date_filter) ?>&dept=<?= urlencode($department_filter) ?>" class="btn btn-success fw-bold" target="_blank"><i class="fas fa-file-excel me-2"></i> Export Log</a>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i> <span class="badge bg-danger ms-1"><?= count($recent_logs) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 320px;">
                        <li><h6 class="dropdown-header text-primary">Recent Activity Logged</h6></li>
                        <?php if (empty($recent_logs)): ?>
                            <li><a class="dropdown-item text-muted" href="#">No recent logs found.</a></li>
                        <?php endif; ?>
                        <?php foreach($recent_logs as $log): ?>
                            <li><a class="dropdown-item py-2 border-bottom" href="#">
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($log['username'] ?? 'N/A') ?></div>
                                <small class="text-muted"><?= htmlspecialchars(substr($log['task'] ?? '', 0, 40)) ?>... (<?= substr($log['created_at'] ?? '00:00:00', 11, 5) ?>)</small>
                            </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </form>


    <div class="row g-4 mb-5">
        
        <div class="col-lg-3 col-md-6">
            <div class="card p-4 h-100">
                <div class="d-flex align-items-center">
                    <i class="fas fa-list-check fa-2x me-3 text-primary opacity-75"></i>
                    <div>
                        <div class="kpi-number"><?= $stats['total_logs_today'] ?></div>
                        <div class="kpi-label">Total Logs Filed (Filtered)</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card p-4 h-100">
                <div class="d-flex align-items-center">
                    <i class="fas fa-user-check fa-2x me-3 text-success opacity-75"></i>
                    <div>
                        <div class="kpi-number text-success"><?= $stats['active_today'] ?></div>
                        <div class="kpi-label">Staff Active Today (Filtered)</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card p-4 h-100">
                <div class="d-flex align-items-center">
                    <i class="fas fa-gauge-high fa-2x me-3 text-info opacity-75"></i>
                    <div>
                        <div class="kpi-number text-info"><?= $stats['active_rate'] ?>%</div>
                        <div class="kpi-label">Daily Activity Rate (Filtered)</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card p-4 h-100">
                <div class="d-flex align-items-center">
                    <i class="fas fa-users fa-2x me-3 text-secondary opacity-75"></i>
                    <div>
                        <div class="kpi-number text-dark"><?= $stats['total_staff'] ?></div>
                        <div class="kpi-label">Total Staff in Department (Filtered)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="data-header mb-0">
                        <i class="fas fa-clock-rotate-left me-2"></i> Activity Log - 
                        <small class="text-muted fw-normal"><?= $today_display ?> 
                            <?php if($department_filter !== 'All'): ?>
                                <span class="badge bg-primary-subtle text-primary ms-2"><?= htmlspecialchars($department_filter) ?></span>
                            <?php endif; ?>
                        </small>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle log-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Department</th>
                                    <th>Project</th>
                                    <th>KB</th>
                                    <th>Task Snippet</th>
                                    <th>Image</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_logs)): ?>
                                    <tr><td colspan="7" class="text-center py-4 text-muted">No activity found for this filter.</td></tr>
                                <?php endif; ?>
                                <?php foreach($all_logs as $log): ?>
                                    <tr>
                                        <td class="text-muted fw-medium"><?= substr($log['created_at'] ?? '00:00:00', 11, 5) ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($log['username'] ?? 'N/A') ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($log['department'] ?: '—') ?></small></td>
                                        <td class="text-primary fw-medium"><small><?= htmlspecialchars($log['project'] ?: 'General') ?></small></td>
                                        <td><span class="badge <?= ($log['is_knowledge'] ?? 0) ? 'knowledge-yes' : 'bg-light text-muted' ?>"><?= ($log['is_knowledge'] ?? 0) ? 'Yes' : 'No' ?></span></td>
                                        <td class="task-snippet-cell">
                                            <button type="button" class="btn btn-link p-0 text-start text-dark fw-normal text-decoration-none" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#taskDetailModal" 
                                                    data-task-id="<?= $log['id'] ?>" 
                                                    data-task-text="<?= htmlspecialchars($log['task'] ?? '') ?>">
                                                <?= htmlspecialchars(substr($log['task'] ?? '', 0, 70)) ?><?= (strlen($log['task'] ?? '') > 70) ? '...' : '' ?>
                                            </button>
                                        </td>
                                        <td>
                                            <?php if ($log['image_path'] ?? null): ?>
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#imageModal" 
                                                        data-bs-image-url="<?= htmlspecialchars($log['image_path']) ?>">
                                                     <i class="fas fa-image"></i> View
                                                </button>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4 h-50-custom">
                <div class="card-header bg-white">
                    <h5 class="data-header mb-0"><i class="fas fa-medal me-2"></i> Top 10 Performers Today</h5>
                </div>
                <div class="card-body pt-0">
                    <ol class="list-group list-group-flush">
                        <?php if (empty($top_performers)): ?>
                            <li class="list-group-item text-center py-4 text-muted">No logs recorded today.</li>
                        <?php endif; ?>
                        <?php foreach($top_performers as $index => $p): ?>
                            <li class="list-group-item list-group-item-pro d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold me-3 text-primary"><?= $index + 1 ?>.</span>
                                    <div>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($p['username'] ?? 'N/A') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($p['department'] ?: '—') ?></small>
                                    </div>
                                </div>
                                <span class="badge list-item-badge rounded-pill"><?= $p['logs'] ?> tasks</span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>

            <div class="card h-50-custom">
                <div class="card-header bg-white">
                    <h5 class="data-header mb-0"><i class="fas fa-project-diagram me-2"></i> Top Projects (Today)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($projects)): ?>
                        <p class="text-muted text-center">No projects logged for this filter.</p>
                    <?php endif; ?>
                    <?php foreach($projects as $p): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <strong class="text-dark"><?= htmlspecialchars($p['project'] ?? 'N/A') ?></strong>
                                <small class="text-muted fw-bold"><?= $p['tasks'] ?> tasks</small>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width:<?= round(($p['tasks'] / $max_tasks) * 100) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="data-header mb-0 text-accent"><i class="fas fa-lightbulb me-2 text-accent"></i> Knowledge Base Contributions (All Time)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (empty($knowledge_leaders)): ?>
                            <div class="col-12 text-center py-3 text-muted">No knowledge contributions recorded yet.</div>
                        <?php endif; ?>
                        <?php foreach($knowledge_leaders as $index => $l): ?>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center p-3 border rounded-3 h-100 bg-light">
                                    <div class="d-flex align-items-center">
                                        <span class="fw-bold me-3 text-primary"><?= $index + 1 ?>.</span>
                                        <div>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($l['username'] ?? 'N/A') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($l['department'] ?: '—') ?></small>
                                        </div>
                                    </div>
                                    <span class="badge list-item-kb-badge rounded-pill"><?= $l['contrib'] ?> KB</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="taskDetailModal" tabindex="-1" aria-labelledby="taskDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="taskDetailModalLabel">Full Task Detail</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="modalTaskText" class="text-break"></p>
      </div>
      <div class="modal-footer">
        <span class="text-muted me-auto small" id="modalTaskId"></span>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Task Attachment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="modalImage" src="" class="img-fluid rounded" alt="Task Attachment">
        <p class="mt-3 text-muted small">Path: <span id="imagePathDisplay"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
// Live Clock
const clockElement = document.getElementById('clock');
setInterval(() => {
    // Uses Intl API for professional, locale-aware time format
    clockElement.textContent = new Date().toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit', second: '2-digit'});
}, 1000);
// Initial set
clockElement.textContent = new Date().toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit', second: '2-digit'});


// JavaScript for Modal Image Loading
document.addEventListener('DOMContentLoaded', function() {
    const imageModal = document.getElementById('imageModal');
    if (imageModal) {
        imageModal.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const button = event.relatedTarget;
            // Extract info from data-bs-* attributes
            const imageUrl = button.getAttribute('data-bs-image-url');
            
            // Update the modal's content.
            const modalImage = imageModal.querySelector('#modalImage');
            const imagePathDisplay = imageModal.querySelector('#imagePathDisplay');
            
            // NOTE: The imageUrl should point to the public path where images are stored (e.g., /uploads/image_name.jpg)
            modalImage.src = imageUrl; 
            imagePathDisplay.textContent = imageUrl;
        });
    }

    // --- Task Detail Modal Handler (NEW) ---
    const detailModal = document.getElementById('taskDetailModal');
    if (detailModal) {
        detailModal.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const button = event.relatedTarget; 
            // Extract info from data-task-* attributes set in the PHP loop
            const taskId = button.getAttribute('data-task-id');
            const taskText = button.getAttribute('data-task-text');

            // Update the modal's content elements
            const modalTaskText = detailModal.querySelector('#modalTaskText');
            const modalTaskId = detailModal.querySelector('#modalTaskId');

            modalTaskText.textContent = taskText;
            modalTaskId.textContent = `Task ID: ${taskId}`;
        });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>