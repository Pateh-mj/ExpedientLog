<?php
// FILE: export_today.php (Updated to handle date and department filters)
require_once 'config.php';

// --- Security Check ---
// Checks if the user is logged in and is a supervisor/admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['supervisor','admin'])) {
    http_response_code(403);
    die("Access denied. Supervisor/Admin access required.");
}

// --- Filter Handling ---
// Get filters from the URL query string
$date_filter = $_GET['date'] ?? date('Y-m-d');
$department_filter = $_GET['dept'] ?? 'All';

// Basic validation and setup
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
    $date_filter = date('Y-m-d'); 
}

$dept_clause = ($department_filter !== 'All') ? " AND u.department = :department_filter" : "";
$filename_suffix = ($department_filter !== 'All') ? '_' . str_replace(' ', '_', $department_filter) : '';

// --- Database Query ---
try {
    $sql = "
        SELECT u.username, u.department, t.task, t.project, t.created_at, t.is_knowledge
        FROM tickets t 
        JOIN users u ON t.user_id = u.id 
        WHERE DATE(t.created_at) = :date_filter 
        AND u.role = 'employee' " . $dept_clause . " 
        ORDER BY t.created_at ASC
    ";

    $params = [':date_filter' => $date_filter];
    if ($department_filter !== 'All') {
        $params[':department_filter'] = $department_filter;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500);
    die("Database Error: " . $e->getMessage());
}

// --- CSV/Excel Output ---
// Set headers for file download
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=ExpedientLog_Export_{$date_filter}{$filename_suffix}.xls");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");

// PHP output stream to write data
$output = fopen('php://output', 'w');

// Set headers (use tab delimiter for .xls)
$headers = ['Username', 'Department', 'Task', 'Project', 'Time', 'Is Knowledge Base'];
fputcsv($output, $headers, "\t");

// Write data rows
foreach ($logs as $log) {
    // Convert is_knowledge tinyint to Yes/No for readability
    $log['is_knowledge'] = $log['is_knowledge'] ? 'Yes' : 'No';
    
    // Prepare data array, ensuring order matches headers
    $row = [
        $log['username'],
        $log['department'],
        // Clean up task for single-line display in Excel
        str_replace(["\r", "\n", "\t"], " ", $log['task']), 
        $log['project'] ?: 'General',
        $log['created_at'],
        $log['is_knowledge']
    ];
    
    fputcsv($output, $row, "\t");
}

fclose($output);
exit();
?>