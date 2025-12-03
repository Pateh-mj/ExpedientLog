<?php
// FILE: fetch_analytics.php

require_once 'config.php';
header('Content-Type: application/json');

// Ensure authorized access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['supervisor','admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

// --- Date and Filter Handling ---
$date_filter = $_GET['date'] ?? date('Y-m-d');
$department_filter = $_GET['dept'] ?? 'All';

// Build WHERE clause based on department filter
$dept_clause = ($department_filter !== 'All') ? " AND u.department = :department_filter" : "";

try {
    
    // --- 1. Activity Volume Chart (Last 7 Days) ---
    $activity_volume_data = ['labels' => [], 'data' => []];
    $date_array = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i day", strtotime($date_filter)));
        $date_array[] = $date;
        $activity_volume_data['labels'][] = date('D, M j', strtotime($date));
    }

    $activity_placeholders = implode(',', array_fill(0, 7, '?'));
    $sql_volume = "
        SELECT DATE(created_at) as log_date, COUNT(*) as log_count 
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        WHERE DATE(created_at) IN ({$activity_placeholders}) AND u.role = 'employee' " . $dept_clause . "
        GROUP BY log_date
    ";
    
    // Adjusting parameters for volume query
    $bind_volume_params = [];
    $param_index = 1;
    foreach ($date_array as $date_val) {
        $bind_volume_params[":date{$param_index}"] = $date_val;
        $param_index++;
    }
    if ($department_filter !== 'All') {
        $bind_volume_params[':department_filter'] = $department_filter;
        // Use named parameters instead of question marks (requires replacement)
        $sql_volume = str_replace($dept_clause, " AND u.department = :department_filter", $sql_volume);
    } else {
        $sql_volume = str_replace($dept_clause, "", $sql_volume);
    }

    // Replace placeholders with named parameters in the IN clause
    $named_placeholders = implode(',', array_keys($bind_volume_params));
    // Since we're using prepared statements, we use placeholders like :date1, :date2, etc.
    $sql_volume = str_replace($activity_placeholders, $named_placeholders, $sql_volume);
    
    $stmt_volume = $pdo->prepare($sql_volume);
    $stmt_volume->execute($bind_volume_params);
    $volume_results = $stmt_volume->fetchAll(PDO::FETCH_KEY_PAIR); // log_date => log_count

    // Map results to the 7-day structure
    foreach ($date_array as $date) {
        $activity_volume_data['data'][] = $volume_results[$date] ?? 0;
    }


    // --- 2. Project Breakdown Chart (Only for the current filtered day) ---
    $project_breakdown_data = ['labels' => [], 'data' => []];
    $sql_breakdown = "
        SELECT project, COUNT(*) as tasks 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id
        WHERE DATE(t.created_at) = :date_filter AND u.role = 'employee' " . $dept_clause . " AND project NOT IN ('General','Personal','')
        GROUP BY project 
        ORDER BY tasks DESC 
        LIMIT 6
    ";
    
    $params = [':date_filter' => $date_filter];
    if ($department_filter !== 'All') {
        $params[':department_filter'] = $department_filter;
    }

    $stmt_breakdown = $pdo->prepare($sql_breakdown);
    $stmt_breakdown->execute($params);
    $breakdown_results = $stmt_breakdown->fetchAll(PDO::FETCH_ASSOC);

    foreach ($breakdown_results as $row) {
        $project_breakdown_data['labels'][] = $row['project'];
        $project_breakdown_data['data'][] = (int)$row['tasks'];
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// --- Final JSON Output ---
echo json_encode([
    'activity_volume_chart' => $activity_volume_data,
    'project_breakdown_chart' => $project_breakdown_data,
]);
?>