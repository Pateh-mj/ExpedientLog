<?php
// FILE: export_today.php (Revised for PostgreSQL/Supabase)

require_once 'config.php';

// --- Security Check ---
// Only supervisors/admins should access this export feature
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['supervisor', 'admin'])) { 
    die("Access denied. Supervisor or Admin privileges required.");
}

$today = date('Y-m-d');

// FIX: Replaced MySQL's DATE(t.created_at) with PostgreSQL's t.created_at::date
$stmt = $pdo->prepare("
    SELECT u.username, u.department, t.task, t.created_at 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.created_at::date = ? 
    ORDER BY t.created_at DESC
");
$stmt->execute([$today]);
$logs = $stmt->fetchAll();

// --- HTTP Headers for Excel Export ---
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=ExpedientLog_$today.xls");

// --- Output Data ---
echo "Username\tDepartment\tTask\tTime\n";
foreach ($logs as $log) {
    // Note: This simple tab-separated format is a common way to generate simple XLS files.
    echo "{$log['username']}\t{$log['department']}\t{$log['task']}\t{$log['created_at']}\n";
}
exit();
?>