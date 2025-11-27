<?php
// FILE: dashboard.php (Revised Frontend for PostgreSQL/Supabase)

// Note: Assumes 'config.php' establishes $pdo and starts the session.
require_once 'config.php';

// --- Session & Security Checks ---
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}
// Inactivity Timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) { 
    header("Location: logout.php"); 
    exit(); 
}
$_SESSION['last_activity'] = time();

// --- Configuration & Validation Setup ---
$max_task_length = 1500;
$allowed_projects = ['General / Other', 'Q4 Financial Audit', 'Lusaka Branch Operations', 'IT Systems Upgrade', 'HR & Recruitment', 'Field Work'];
$allowed_knowledge_categories = ['General', 'SOP / Procedure', 'Client Notes', 'Templates', 'Lessons Learned', 'Contacts', 'IT / Tech'];

// --- Handle POST Request (Simplified: Should move to AJAX endpoint) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = trim($_POST['task'] ?? '');
    $project_raw = $_POST['project'] ?? 'General / Other';
    $is_knowledge = isset($_POST['save_as_knowledge']) ? 1 : 0;
    $category_raw = $_POST['category'] ?? 'General';

    if (empty($task) || strlen($task) > $max_task_length) {
        $_SESSION['error'] = "Activity description must be between 1 and $max_task_length characters.";
    } else {
        // Validation (remains compatible)
        $project = in_array($project_raw, $allowed_projects) ? $project_raw : 'General / Other';
        $category = $is_knowledge && in_array($category_raw, $allowed_knowledge_categories) ? $category_raw : 'General';
        
        try {
            // INSERT is compatible with PostgreSQL
            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, task, project, is_knowledge, category) VALUES (?, ?, ?, ?, ?)");
            // Note: PostgreSQL BOOLEAN columns (is_knowledge) accept integers (1/0) from PDO binding.
            $stmt->execute([$_SESSION['user_id'], $task, $project, $is_knowledge, $category]);
            $_SESSION['success'] = 'Activity logged successfully!';
        } catch (PDOException $e) {
            error_log("Database error on ticket insert: " . $e->getMessage()); 
            $_SESSION['error'] = 'Could not log activity due to a system error.';
        }
    }
    
    header("Location: dashboard.php");
    exit();
}

// --- Fetch today's tasks ---
$today = date('l, j F Y');
$today_db = date('Y-m-d');

// *** PostgreSQL Compatibility FIX: Use ::date cast instead of MySQL's DATE() function ***
$stmt = $pdo->prepare("SELECT id, task, project, is_knowledge, category, created_at FROM tickets WHERE user_id = ? AND created_at::date = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id'], $today_db]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Handle Flash Messages ---
$success_message = $_SESSION['success'] ?? null;
$error_message = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ExpedientLog • <?= htmlspecialchars($_SESSION['username']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"> 
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #004080; } /* Darker Blue */
        body { font-family: 'Inter', sans-serif; background: #f0f3f8; color: #1e293b; }
        .header-bar { background: var(--primary-color); color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .task-item:hover { background: #e0e9f1; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.2rem rgba(0,64,128,0.15); }
        .delete-btn { opacity: 0; transition: opacity 0.2s; }
        .task-item:hover .delete-btn { opacity: 0.7; }
        .task-item:hover .delete-btn:hover { opacity: 1; color: #dc3545 !important; }
        .log-time { flex-shrink: 0; }
    </style>
</head>
<body>

<div class="header-bar py-3 shadow-sm">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i> ExpedientLog</h3>
            <div class="text-end d-flex align-items-center gap-3">
                <span class="text-white opacity-75 fw-medium" id="clock"></span>
                <div class="dropdown">
                    <a class="text-white text-decoration-none fw-medium" data-bs-toggle="dropdown">
                        <?= htmlspecialchars($_SESSION['username']) ?> <i class="fas fa-caret-down ms-1"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header text-muted">Employee Access</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4 align-items-end">
        <div class="col">
            <h2 class="fw-semibold text-dark"><i class="fas fa-calendar-day me-2 text-primary"></i> Today — <?= $today ?></h2>
            <p class="text-muted"><span id="task-count"><?= count($tasks) ?></span> activities logged so far</p>
        </div>
        <div class="col-auto">
            <a href="knowledge.php" class="btn btn-outline-primary fw-medium"><i class="fas fa-book me-2"></i> View Knowledge Base</a>
        </div>
    </div>

    <div class="card mb-5">
        <div class="card-body p-4">
            <form method="post" id="task-form">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-7">
                        <label class="form-label fw-medium">What have you accomplished? (max <?= $max_task_length ?> chars)</label>
                        <input type="text" name="task" id="task-input" class="form-control form-control-lg" 
                            placeholder="Type the your work here" required maxlength="<?= $max_task_length ?>">
                        <small class="text-muted float-end mt-1" id="char-count">0 / <?= $max_task_length ?></small>
                    </div>
                    
                    <div class="col-lg-3">
                        <label class="form-label fw-medium">Project / Category</label>
                        <select name="project" class="form-select form-select-lg">
                            <?php foreach ($allowed_projects as $p): ?>
                                <option><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold"><i class="fas fa-plus me-1"></i> Log Activity</button>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-light rounded">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="save_as_knowledge" id="kb" value="1">
                        <label class="form-check-label fw-semibold text-success" for="kb">
                            <i class="fas fa-lightbulb me-1"></i> Save as Reusable Knowledge
                        </label>
                    </div>
                    <small class="text-muted">Make this available to the entire team (procedures, templates, contacts, etc.)</small>

                    <select name="category" class="form-select mt-2" id="kb_cat" style="display:none;">
                        <?php foreach ($allowed_knowledge_categories as $c): ?>
                            <option><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0 fw-semibold"><i class="fas fa-clipboard-list me-2"></i> Your Activity Log</h5>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush" id="activity-list">
                <?php if ($tasks): ?>
                    <?php foreach ($tasks as $t): ?>
                        <div class="list-group-item task-item px-4 py-3 d-flex justify-content-between align-items-center" data-id="<?= $t['id'] ?>">
                            <div class="d-flex align-items-center flex-grow-1 me-3">
                                <div>
                                    <div class="fw-medium">
                                        <?= htmlspecialchars($t['task']) ?>
                                        <?php if ($t['is_knowledge']): ?>
                                            <span class="badge bg-success-subtle text-success ms-2 fw-normal">
                                                💡 KB: <?= htmlspecialchars($t['category']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php 
                                        $project_display = $t['project'] ?? 'General / Other'; 
                                        if ($project_display !== 'General / Other'): 
                                    ?>
                                        <small class="text-primary fw-medium">Project: <?= htmlspecialchars($project_display) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="log-time d-flex align-items-center gap-3">
                                <small class="text-muted fw-medium"><?= substr($t['created_at'], 11, 5) ?></small>
                                <button type="button" class="btn-link text-danger p-0 delete-btn" title="Delete entry" data-id="<?= $t['id'] ?>">
                                    <i class="fas fa-trash fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted" id="empty-log-message">
                        <p class="fs-5">No activities logged yet today.</p>
                        <p>Start now — every task moves the company forward.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
    // --- Clock and Header Utility ---
    const clockElement = document.getElementById('clock');
    const updateClock = () => {
        clockElement.textContent = new Date().toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit', second: '2-digit'});
    };
    updateClock();
    setInterval(updateClock, 1000);

    // --- Knowledge Category Toggle ---
    const kbCheckbox = document.getElementById('kb');
    const kbCategory = document.getElementById('kb_cat');
    function toggleCategory() {
        kbCategory.style.display = kbCheckbox.checked ? 'block' : 'none';
    }
    kbCheckbox.addEventListener('change', toggleCategory);
    toggleCategory(); // Set initial state

    // --- Character Counter (Fix #2) ---
    const taskInput = document.getElementById('task-input');
    const charCountSpan = document.getElementById('char-count');
    const maxLength = <?= $max_task_length ?>;

    taskInput.addEventListener('input', function() {
        const currentLength = this.value.length;
        charCountSpan.textContent = `${currentLength} / ${maxLength}`;
        // Optional: Change color when approaching limit
        if (currentLength > maxLength * 0.9) {
            charCountSpan.style.color = '#dc3545'; // Red
        } else {
            charCountSpan.style.color = '#6c757d'; // Muted
        }
    });
    taskInput.dispatchEvent(new Event('input')); // Set initial count

    // --- Delete Button Placeholder (Fix #3 - Ready for AJAX) ---
    document.getElementById('activity-list').addEventListener('click', function(e) {
        if (e.target.closest('.delete-btn')) {
            const button = e.target.closest('.delete-btn');
            const taskId = button.dataset.id;
            
            if (confirm(`Are you sure you want to delete Task ID ${taskId}? This cannot be undone.`)) {
                // *** PLACEHOLDER FOR AJAX DELETION ***
                // In a real application, you would send an AJAX request here:
                // fetch('delete_task.php', { method: 'POST', body: JSON.stringify({id: taskId}) }).then(...)
                
                // For demonstration, remove the element immediately
                button.closest('.task-item').remove();
                
                // Update task count and remove empty message if log is now empty
                const taskCountElement = document.getElementById('task-count');
                taskCountElement.textContent = parseInt(taskCountElement.textContent) - 1;

                const activityList = document.getElementById('activity-list');
                if (activityList.children.length === 0) {
                     const emptyMessage = document.getElementById('empty-log-message');
                     if (emptyMessage) emptyMessage.style.display = 'block';
                }
            }
        }
    });

    // --- AJAX Submission Placeholder (Fix #1 - Optional but highly recommended) ---
    // document.getElementById('task-form').addEventListener('submit', function(e) {
    //     e.preventDefault();
    //     // *** AJAX SUBMISSION LOGIC HERE ***
    // });

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>