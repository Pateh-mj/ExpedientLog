<?php
require_once 'config.php';
if ($_SESSION['role'] !== 'supervisor') die("Access denied");

$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT u.username, u.department, t.task, t.created_at 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE DATE(t.created_at) = ? 
    ORDER BY t.created_at DESC
");
$stmt->execute([$today]);
$logs = $stmt->fetchAll();

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=ExpedientLog_$today.xls");
echo "Username\tDepartment\tTask\tTime\n";
foreach ($logs as $log) {
    echo "{$log['username']}\t{$log['department']}\t{$log['task']}\t{$log['created_at']}\n";
}
?>