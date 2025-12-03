<?php
// FILE: supervisor_lean_analytics.php (Single File Implementation)

// NOTE: Ensure 'config.php' establishes $pdo (PDO connection) and starts the session.
require_once 'config.php'; 

// --- Session & Security Checks ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['supervisor','admin'])) {
    header("Location: login.php"); exit();
}
$_SESSION['last_activity'] = time();

// --- Configuration & Defaults ---
$date_filter = $_GET['date'] ?? date('Y-m-d'); // Default date for initial load
$department_filter = $_GET['dept'] ?? 'All';

// Basic validation
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
    $date_filter = date('Y-m-d'); 
}

$display_date = date('l, j F Y', strtotime($date_filter));
$dept_clause = ($department_filter !== 'All') ? " AND u.department = :department_filter" : "";
$params = [':date_filter' => $date_filter];
if ($department_filter !== 'All') {
    $params[':department_filter'] = $department_filter;
}

// Fetch all departments for the filter dropdown
$departments_query = $pdo->query("SELECT DISTINCT department FROM users WHERE role = 'employee' AND department IS NOT NULL AND department <> '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);
$all_departments = array_merge(['All'], $departments_query);

// --- Data Retrieval (All Data in one pass for simplicity) ---

// 1. Core Daily Stats
$stmt_stats = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT CASE WHEN DATE(t.created_at) = :date_filter THEN t.user_id END) as active_today, 
        COUNT(CASE WHEN DATE(t.created_at) = :date_filter THEN t.id END) as total_logs_today, 
        COUNT(DISTINCT u.id) as total_staff 
    FROM users u 
    LEFT JOIN tickets t ON u.id = t.user_id 
    WHERE u.role = 'employee'
");
$stmt_stats->execute([':date_filter' => $date_filter]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
$stats['active_rate'] = $stats['total_staff'] > 0 ? round(($stats['active_today'] / $stats['total_staff']) * 100, 1) : 0;

// 2. 7-Day Total Logs (For the small analytics card)
$prev_week_date = date('Y-m-d', strtotime('-6 days', strtotime($date_filter)));
$stmt_7day_logs = $pdo->prepare("
    SELECT COUNT(t.id) 
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE DATE(t.created_at) BETWEEN :start_date AND :end_date AND u.role = 'employee' " . $dept_clause
);
$seven_day_params = [
    ':start_date' => $prev_week_date, 
    ':end_date' => $date_filter
];
if ($department_filter !== 'All') {
    $seven_day_params[':department_filter'] = $department_filter;
}
$stmt_7day_logs->execute($seven_day_params);
$seven_day_total = $stmt_7day_logs->fetchColumn();

// 3. Daily Activity Log
$sql_logs = "
    SELECT t.task, t.project, t.created_at, u.username, u.department, t.is_knowledge 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE DATE(t.created_at) = :date_filter AND u.role = 'employee' " . $dept_clause . " 
    ORDER BY t.created_at DESC
";
$stmt_all_logs = $pdo->prepare($sql_logs);
$stmt_all_logs->execute($params);
$all_logs = $stmt_all_logs->fetchAll(PDO::FETCH_ASSOC);

// 4. Top Performers
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
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supervisor Dashboard • Lean Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script> 
    <style>
        :root {
            --primary-color: #004080; 
            --accent-color: #10b981; 
        }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .header-bar { background: var(--primary-color); color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .kpi-number { font-size: 2.2rem; font-weight: 800; color: var(--primary-color); }
        .kpi-label { font-size: 0.95rem; font-weight: 500; color: #6c757d; }
        .log-table th { background: #f8f9fa; font-weight: 600; font-size: 0.85rem; }
        .analytics-card { 
            background: linear-gradient(135deg, var(--accent-color), #099a6c);
            color: white;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .analytics-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4); }
        .modal-xl-custom { max-width: 900px; }
        .chart-container { position: relative; height: 350px; }
    </style>
</head>
<body>

<div class="header-bar sticky-top py-3">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-0 fw-bold">ExpedientLog Supervisor</h3>
            <span class="fw-medium"><?= $display_date ?></span>
        </div>
    </div>
</div>

<div class="container-fluid py-5">
    
    <form method="GET" class="mb-5 bg-white p-4 rounded-3 shadow-sm">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="date-filter" class="form-label fw-bold mb-1"><i class="fas fa-calendar-alt me-2"></i> Target Date</label>
                <input type="date" name="date" id="date-filter" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
            </div>
            <div class="col-md-3">
                <label for="dept-filter" class="form-label fw-bold mb-1"><i class="fas fa-building me-2"></i> Department</label>
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
                <a href="export.php?date=<?= urlencode($date_filter) ?>&dept=<?= urlencode($department_filter) ?>" class="btn btn-success fw-bold" target="_blank"><i class="fas fa-file-excel me-2"></i> Export</a>
            </div>
        </div>
    </form>


    <div class="row g-4 mb-5">
        
        <div class="col-lg-3 col-md-6">
            <div class="card p-4 h-100">
                <div class="d-flex align-items-center"><i class="fas fa-list-check fa-2x me-3 text-primary"></i><div><div class="kpi-number"><?= $stats['total_logs_today'] ?></div><div class="kpi-label">Total Logs Filed Today</div></div></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card p-4 h-100">
                <div class="d-flex align-items-center"><i class="fas fa-user-check fa-2x me-3 text-success"></i><div><div class="kpi-number text-success"><?= $stats['active_today'] ?></div><div class="kpi-label">Staff Active Today</div></div></div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card p-4 h-100">
                <div class="d-flex align-items-center"><i class="fas fa-gauge-high fa-2x me-3 text-info"></i><div><div class="kpi-number text-info"><?= $stats['active_rate'] ?>%</div><div class="kpi-label">Daily Activity Rate</div></div></div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
    <div class="analytics-card p-4 text-center h-100 d-flex flex-column justify-content-center" data-bs-toggle="modal" data-bs-target="#analyticsModal" id="analytics-toggle">
        <i class="fas fa-chart-area fa-3x mb-3"></i>
        <div class="fs-2 fw-bold mb-1"><?= $seven_day_total ?></div>
        <div class="fw-medium">Logs Filed (Last 7 Days)</div>
        <small class="mt-2 opacity-75">Click for full trend analysis</small>
    </div>
</div>
    </div>
    
    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white"><h5 class="mb-0 fw-bold"><i class="fas fa-list-ul me-2"></i> Latest Activity Log</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle log-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Department</th>
                                    <th>Project</th>
                                    <th>Task Snippet</th>
                                    <th>KB</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_logs)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No activity found for this filter.</td></tr>
                                <?php endif; ?>
                                <?php foreach($all_logs as $log): ?>
                                    <tr>
                                        <td class="text-muted fw-medium"><?= substr($log['created_at'],11,5) ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($log['username']) ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($log['department'] ?: '—') ?></small></td>
                                        <td class="text-primary fw-medium"><small><?= htmlspecialchars($log['project'] ?: 'General') ?></small></td>
                                        <td><?= htmlspecialchars(substr($log['task'], 0, 70)) ?><?= (strlen($log['task']) > 70) ? '...' : '' ?></td>
                                        <td><span class="badge <?= $log['is_knowledge'] ? 'bg-success text-white' : 'bg-light text-muted' ?>"><?= $log['is_knowledge'] ? 'Yes' : 'No' ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white"><h5 class="mb-0 fw-bold"><i class="fas fa-medal me-2"></i> Top Daily Performers</h5></div>
                <div class="card-body pt-0">
                    <ol class="list-group list-group-numbered list-group-flush">
                        <?php if (empty($top_performers)): ?>
                            <li class="list-group-item text-center py-4 text-muted">No logs recorded today.</li>
                        <?php endif; ?>
                        <?php foreach($top_performers as $index => $p): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($p['username']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($p['department'] ?: '—') ?></small>
                                </div>
                                <span class="badge bg-primary rounded-pill"><?= $p['logs'] ?> tasks</span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="analyticsModal" tabindex="-1" aria-labelledby="analyticsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl-custom">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold text-primary" id="analyticsModalLabel"><i class="fas fa-chart-bar me-2"></i> Detailed Performance Analytics</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-center py-3 text-muted" id="modal-loading"><i class="fas fa-spinner fa-spin me-2"></i> Loading analytics data...</div>
        <div class="row g-4 d-none" id="analytics-content">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="fas fa-chart-area me-2"></i> 7-Day Activity Volume</h6></div>
                    <div class="card-body">
                        <div class="chart-container"><canvas id="activityVolumeChart"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie me-2"></i> Daily Project Distribution</h6></div>
                    <div class="card-body">
                        <div class="chart-container"><canvas id="projectBreakdownChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const analyticsModal = document.getElementById('analyticsModal');
    const modalLoading = document.getElementById('modal-loading');
    const analyticsContent = document.getElementById('analytics-content');
    const dateFilter = document.getElementById('date-filter');
    const deptFilter = document.getElementById('dept-filter');

    let activityVolumeChart;
    let projectBreakdownChart;

    const chartColors = ['#004080', '#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d'];

    // --- Chart Initialization (Called when modal is shown) ---
    const initCharts = (data) => {
        // Destroy existing charts if they exist
        if (activityVolumeChart) activityVolumeChart.destroy();
        if (projectBreakdownChart) projectBreakdownChart.destroy();

        // Activity Volume Chart (Line)
        const activityCtx = document.getElementById('activityVolumeChart').getContext('2d');
        activityVolumeChart = new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: data.activity_volume_chart.labels,
                datasets: [{
                    label: 'Activities Logged',
                    data: data.activity_volume_chart.data,
                    borderColor: 'rgba(0, 123, 255, 1)',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true } }
            }
        });

        // Project Breakdown Chart (Doughnut)
        const projectCtx = document.getElementById('projectBreakdownChart').getContext('2d');
        projectBreakdownChart = new Chart(projectCtx, {
            type: 'doughnut',
            data: {
                labels: data.project_breakdown_chart.labels,
                datasets: [{
                    data: data.project_breakdown_chart.data,
                    backgroundColor: chartColors.slice(0, data.project_breakdown_chart.labels.length),
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });
    };

    // --- AJAX Fetch for Analytical Data Only ---
    analyticsModal.addEventListener('show.bs.modal', async function () {
        modalLoading.classList.remove('d-none');
        analyticsContent.classList.add('d-none');
        
        const date = dateFilter.value;
        const dept = deptFilter.value;
        const url = `fetch_analytics.php?date=${date}&dept=${dept}`; // Using a dedicated analytics endpoint

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Failed to fetch analytics data.');
            const data = await response.json();

            // Render charts
            initCharts(data); 
            
            // Hide loading, show charts
            modalLoading.classList.add('d-none');
            analyticsContent.classList.remove('d-none');
        } catch (error) {
            console.error("Analytics load failed:", error);
            modalLoading.innerHTML = '<div class="alert alert-danger">Error loading charts. Check server logs.</div>';
        }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>