<?php
// FILE: dashboard.php (Updated with Edit feature)

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
$max_file_size = 5000000; // 5MB
$upload_dir = 'uploads/'; // Ensure this folder exists and has write permissions (chmod 755)
$allowed_projects = ['General / Other', 'Q4 Financial Audit', 'Lusaka Branch Operations', 'IT Systems Upgrade', 'HR & Recruitment', 'Field Work'];
$allowed_knowledge_categories = ['General', 'SOP / Procedure', 'Client Notes', 'Templates', 'Lessons Learned', 'Contacts', 'IT / Tech'];

// --- Handle POST Request (File Upload and Database Insert) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $task = trim($_POST['task'] ?? '');
    $project = $_POST['project'] ?? 'General / Other';
    $is_knowledge = isset($_POST['save_as_knowledge']) ? 1 : 0;
    $category = $is_knowledge ? ($_POST['category'] ?? null) : null;
    $image_path = null;
    $error = null;

    // 1. Basic Validation
    if (empty($task) || strlen($task) > $max_task_length) {
        $error = "Task cannot be empty or exceed {$max_task_length} characters.";
    }

    // 2. File Upload Handling
    if (!$error && isset($_FILES['task_image']) && $_FILES['task_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['task_image'];
        
        // Ensure uploads directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = time() . '_' . basename($file['name']);
        $target_file = $upload_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if file is an actual image
        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            $error = "File is not a valid image.";
        }
        
        // Check file size (5MB limit)
        if ($file["size"] > $max_file_size) { 
            $error = "File is too large (max " . round($max_file_size / 1000000) . "MB).";
        }
        
        // Allow certain file formats
        if (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
        
        // Attempt to move file
        if (!$error) {
            if (move_uploaded_file($file["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                $error = "Failed to save image. Check folder permissions (chmod 755) and ensure the 'uploads/' folder exists in the root directory.";
            }
        }
    }

    // 3. Database Insertion
    if ($error) {
        $_SESSION['error'] = $error;
    } else {
        try {
            // Updated query to include image_path
            $sql = "INSERT INTO tickets (user_id, task, project, is_knowledge, category, image_path) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $task, $project, $is_knowledge, $category, $image_path]);
            
            $_SESSION['success'] = "Activity successfully logged!" . ($image_path ? " Image attached." : "");
        } catch (PDOException $e) {
            error_log("Task insertion failed: " . $e->getMessage());
            $_SESSION['error'] = "A database error occurred while logging the activity.";
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: dashboard.php");
    exit();
}

// --- Fetch today's tasks ---
$today = date('l, j F Y');
$today_db = date('Y-m-d');

// NOTE: Added 'updated_at' to the SELECT statement for display
$stmt = $pdo->prepare("SELECT id, task, project, is_knowledge, category, created_at, updated_at, image_path FROM tickets WHERE user_id = ? AND DATE(created_at) = ? ORDER BY created_at DESC");
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
        .delete-btn, .edit-btn { opacity: 0; transition: opacity 0.2s; }
        .task-item:hover .delete-btn, .task-item:hover .edit-btn { opacity: 0.7; }
        .task-item:hover .delete-btn:hover { opacity: 1; color: #dc3545 !important; }
        .task-item:hover .edit-btn:hover { opacity: 1; color: #004080 !important; }
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
            <form method="post" id="task-form" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-lg-12">
                        <label class="form-label fw-medium">What have you accomplished? (max <?= $max_task_length ?> chars)</label>
                        <input type="text" name="task" id="task-input" class="form-control form-control-lg" 
                            placeholder="Type the your work here" required maxlength="<?= $max_task_length ?>">
                        <small class="text-muted float-end mt-1" id="char-count">0 / <?= $max_task_length ?></small>
                    </div>
                    
                    <div class="col-lg-6">
                        <label class="form-label fw-medium">Attach Image (Optional)</label>
                        <input type="file" name="task_image" id="task-image" class="form-control" accept="image/jpeg,image/png,image/gif">
                        <small class="text-muted">Max file size 5MB. Accepts JPG, JPEG, PNG, GIF.</small>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label fw-medium">Project / Category</label>
                        <select name="project" class="form-select" id="log_project">
                            <?php foreach ($allowed_projects as $p): ?>
                                <option><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-3 d-flex align-items-end">
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
                                <div class="task-content">
                                    <div class="fw-medium task-text-<?= $t['id'] ?>">
                                        <?= htmlspecialchars($t['task']) ?>
                                        <?php if ($t['is_knowledge']): ?>
                                            <span class="badge bg-success-subtle text-success ms-2 fw-normal kb-badge-<?= $t['id'] ?>">
                                                💡 KB: <span class="kb-category-<?= $t['id'] ?>"><?= htmlspecialchars($t['category']) ?></span>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($t['image_path']): // Added image indicator ?>
                                            <a href="<?= htmlspecialchars($t['image_path']) ?>" target="_blank" class="ms-2 text-info" title="View Attachment">
                                                <i class="fas fa-image"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php 
                                        $project_display = $t['project'] ?? 'General / Other'; 
                                        $project_color = ($project_display === 'General / Other') ? 'text-muted' : 'text-primary';
                                    ?>
                                    <small class="<?= $project_color ?> fw-medium task-project-<?= $t['id'] ?>"><?= htmlspecialchars($project_display) ?></small>
                                </div>
                            </div>
                            
                            <div class="log-time d-flex align-items-center gap-3">
                                
                                <button type="button" class="btn-link text-primary p-0 edit-btn" title="Edit entry"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editTaskModal" 
                                        data-id="<?= $t['id'] ?>"
                                        data-task="<?= htmlspecialchars($t['task']) ?>"
                                        data-project="<?= htmlspecialchars($t['project'] ?? '') ?>"
                                        data-isknowledge="<?= $t['is_knowledge'] ?>"
                                        data-category="<?= htmlspecialchars($t['category'] ?? '') ?>">
                                    <i class="fas fa-edit fa-sm"></i>
                                </button>

                                <button type="button" class="btn-link text-danger p-0 delete-btn" title="Delete entry" data-id="<?= $t['id'] ?>">
                                    <i class="fas fa-trash fa-sm"></i>
                                </button>
                                
                                <small class="text-muted fw-medium task-time-<?= $t['id'] ?>">
                                    <?= substr($t['created_at'], 11, 5) ?>
                                    <?php if ($t['updated_at']): ?><small class="text-secondary">(edited)</small><?php endif; ?>
                                </small>
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


<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="editTaskModalLabel"><i class="fas fa-edit me-2"></i> Edit Activity</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="edit-task-form">
        <input type="hidden" name="id" id="edit-task-id">
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit-task-input" class="form-label fw-medium">Task Description (max <?= $max_task_length ?> chars)</label>
            <textarea class="form-control" name="task" id="edit-task-input" rows="3" required maxlength="<?= $max_task_length ?>"></textarea>
          </div>
          
          <div class="mb-3">
            <label for="edit-project" class="form-label fw-medium">Project / Category</label>
            <select name="project" id="edit-project" class="form-select">
                <?php foreach ($allowed_projects as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                <?php endforeach; ?>
            </select>
          </div>

          <div class="p-3 bg-light rounded">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="save_as_knowledge" id="edit-kb" value="1">
                <label class="form-check-label fw-semibold text-success" for="edit-kb">
                    <i class="fas fa-lightbulb me-1"></i> Save as Reusable Knowledge
                </label>
            </div>
            <select name="category" class="form-select mt-2" id="edit-kb-cat">
                <?php foreach ($allowed_knowledge_categories as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-bold" id="edit-submit-btn">Save Changes</button>
        </div>
      </form>
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

    // --- Knowledge Category Toggle (for NEW task form) ---
    const kbCheckbox = document.getElementById('kb');
    const kbCategory = document.getElementById('kb_cat');
    function toggleCategory() {
        kbCategory.style.display = kbCheckbox.checked ? 'block' : 'none';
    }
    kbCheckbox.addEventListener('change', toggleCategory);
    toggleCategory(); // Set initial state

    // --- Knowledge Category Toggle (for EDIT modal) ---
    const editKbCheckbox = document.getElementById('edit-kb');
    const editKbCategory = document.getElementById('edit-kb-cat');
    function toggleEditCategory() {
        editKbCategory.style.display = editKbCheckbox.checked ? 'block' : 'none';
    }
    editKbCheckbox.addEventListener('change', toggleEditCategory);


    // --- Character Counter ---
    const taskInput = document.getElementById('task-input');
    const charCountSpan = document.getElementById('char-count');
    const maxLength = <?= $max_task_length ?>;

    const updateCharCount = (input, span) => {
        const currentLength = input.value.length;
        span.textContent = `${currentLength} / ${maxLength}`;
        if (currentLength > maxLength * 0.9) {
            span.style.color = '#dc3545';
        } else {
            span.style.color = '#6c757d';
        }
    }

    taskInput.addEventListener('input', () => updateCharCount(taskInput, charCountSpan));
    taskInput.dispatchEvent(new Event('input')); 


    // --- Delete Button Logic ---
    document.getElementById('activity-list').addEventListener('click', function(e) {
        if (e.target.closest('.delete-btn')) {
            const button = e.target.closest('.delete-btn');
            const taskId = button.dataset.id;
            
            if (confirm(`Are you sure you want to delete Task ID ${taskId}? This cannot be undone.`)) {
                // *** PLACEHOLDER FOR AJAX DELETION ***
                // In a real application, you would send an AJAX request here to delete the item from the DB
                // fetch('delete_task.php', { method: 'POST', body: JSON.stringify({id: taskId}) }).then(...)
                
                // For demonstration, remove the element immediately
                button.closest('.task-item').remove();
                
                // Update task count
                const taskCountElement = document.getElementById('task-count');
                const newCount = parseInt(taskCountElement.textContent) - 1;
                taskCountElement.textContent = newCount;

                const activityList = document.getElementById('activity-list');
                const emptyMessage = document.getElementById('empty-log-message');
                if (newCount === 0 && !document.getElementById('empty-log-message')) {
                    activityList.innerHTML = `
                        <div class="text-center py-5 text-muted" id="empty-log-message">
                            <p class="fs-5">No activities logged yet today.</p>
                            <p>Start now — every task moves the company forward.</p>
                        </div>
                    `;
                }
            }
        }
    });

    // --- EDIT BUTTON & MODAL LOGIC ---
    const editTaskModal = document.getElementById('editTaskModal');
    const editForm = document.getElementById('edit-task-form');
    const editTaskInput = document.getElementById('edit-task-input');
    const editProjectSelect = document.getElementById('edit-project');
    const editKbCatSelect = document.getElementById('edit-kb-cat');

    // 1. Populate Modal when Edit button is clicked
    editTaskModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const taskId = button.getAttribute('data-id');
        const taskText = button.getAttribute('data-task');
        const project = button.getAttribute('data-project');
        const isKnowledge = button.getAttribute('data-isknowledge');
        const category = button.getAttribute('data-category');

        // Set form values
        document.getElementById('edit-task-id').value = taskId;
        editTaskInput.value = taskText;
        editProjectSelect.value = project;
        
        editKbCheckbox.checked = isKnowledge == 1;
        editKbCatSelect.value = category;
        
        toggleEditCategory(); // Show/hide category dropdown based on checkbox
        
        // Optional: Update char count for edit input (if implemented)
        // const editCharCountSpan = document.getElementById('edit-char-count'); 
        // updateCharCount(editTaskInput, editCharCountSpan);
    });

    // 2. Handle AJAX Submission
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const taskId = formData.get('id');
        const submitBtn = document.getElementById('edit-submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...';

        fetch('edit_task.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the visible log item on the dashboard
                const item = document.querySelector(`.list-group-item[data-id="${taskId}"]`);
                if (item) {
                    const taskContent = item.querySelector('.task-content');
                    const isKB = data.data.is_knowledge;
                    const projectDisplay = data.data.project === 'General / Other' ? 'text-muted' : 'text-primary';

                    // Update Task Text
                    item.querySelector(`.task-text-${taskId}`).innerHTML = `
                        ${data.data.task}
                        ${isKB ? `<span class="badge bg-success-subtle text-success ms-2 fw-normal kb-badge-${taskId}">
                            💡 KB: <span class="kb-category-${taskId}">${data.data.category}</span>
                        </span>` : ''}
                    `;
                    // Update Project
                    item.querySelector(`.task-project-${taskId}`).className = `small fw-medium ${projectDisplay} task-project-${taskId}`;
                    item.querySelector(`.task-project-${taskId}`).textContent = data.data.project;
                    
                    // Add edited label
                    item.querySelector(`.task-time-${taskId}`).innerHTML = `${data.data.time} <small class="text-secondary">(edited)</small>`;
                    
                    // OPTIONAL: Show success alert on dashboard
                    // alert('Success: ' + data.message);
                }
                
                // Close modal
                const modalInstance = bootstrap.Modal.getInstance(editTaskModal);
                modalInstance.hide();
                
            } else {
                alert('Edit Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('An unexpected error occurred. Check the console.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Save Changes';
        });
    });

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>