<?php
// FILE: edit_task.php - Secure AJAX endpoint for updating employee tasks
require_once 'config.php';

// --- Session & Security Checks ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized access.']));
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed.']));
}

header('Content-Type: application/json');

// --- Input Validation and Filtering ---
$task_id = $_POST['id'] ?? null;
$task = trim($_POST['task'] ?? '');
$project = $_POST['project'] ?? 'General / Other';
$is_knowledge = isset($_POST['save_as_knowledge']) ? 1 : 0;
$category = $is_knowledge ? ($_POST['category'] ?? null) : null;
$error = null;

if (empty($task_id) || !is_numeric($task_id)) {
    $error = "Invalid task ID.";
} elseif (empty($task) || strlen($task) > 1500) {
    $error = "Task cannot be empty or exceed 1500 characters.";
}

if (!$error) {
    try {
        // 1. Check ownership and creation date (Crucial Audit Rule: Only allow edit if created today)
        $stmt_check = $pdo->prepare("SELECT user_id, DATE(created_at) as created_date FROM tickets WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $ticket = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            $error = "Task not found.";
        } elseif ($ticket['user_id'] != $_SESSION['user_id']) {
            $error = "You do not own this task.";
        } elseif ($ticket['created_date'] !== date('Y-m-d')) {
            $error = "Editing denied. Logs older than today cannot be modified.";
        }

    } catch (PDOException $e) {
        $error = "Database check error.";
    }
}

// --- Database Update ---
if (!$error) {
    try {
        // NOTE: The updated_at column is now set to NOW()
        $sql = "UPDATE tickets SET task = ?, project = ?, is_knowledge = ?, category = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$task, $project, $is_knowledge, $category, $task_id, $_SESSION['user_id']]);

        // Return the new data to update the dashboard display
        $new_data = [
            'id' => $task_id,
            'task' => $task,
            'project' => $project,
            'is_knowledge' => $is_knowledge,
            'category' => $category,
            'time' => date('H:i') // Use current time for visual feedback
        ];

        echo json_encode(['success' => true, 'message' => 'Task updated successfully.', 'data' => $new_data]);

    } catch (PDOException $e) {
        error_log("Task update failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'A database error occurred during update.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => $error]);
}
?>