<?php
// FILE: fetch_analytics.php (Revised for PostgreSQL/Supabase)

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
    
    // FIX 1: Replaced DATE(created_at) with created_at::date
    $sql_volume = "
        SELECT created_at::date as log_date, COUNT(*) as logs 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id
        WHERE created_at::date IN ({$activity_placeholders}) AND u.role = 'employee' " . $dept_clause . "
        GROUP BY created_at::date
    ";
    
    $volume_params = $date_array;
    if ($department_filter !== 'All') {
        $volume_params[] = $department_filter;
    }

    $stmt_volume = $pdo->prepare($sql_volume);
    $stmt_volume->execute($volume_params);
    $volume_results = $stmt_volume->fetchAll(PDO::FETCH_KEY_PAIR); // Fetch as associative array: date => logs

    // Map results to the 7-day structure
    foreach ($date_array as $date) {
        $activity_volume_data['data'][] = $volume_results[$date] ?? 0;
    }


    // --- 2. Project Breakdown Chart (Only for the current filtered day) ---
    $project_breakdown_data = ['labels' => [], 'data' => []];
    
    // FIX 2: Replaced DATE(t.created_at) with t.created_at::date
    $sql_breakdown = "
        SELECT project, COUNT(*) as tasks 
        FROM tickets t 
        JOIN users u ON t.user_id = u.id
        WHERE t.created_at::date = :date_filter AND u.role = 'employee' " . $dept_clause . " AND project NOT IN ('General','Personal','')
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

    // Add 'General' to the breakdown data for clarity if no specific projects exist
    $general_count = $pdo->prepare("
        SELECT COUNT(*) as tasks
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        WHERE t.created_at::date = :date_filter AND u.role = 'employee' " . $dept_clause . " AND project IN ('General','Personal','')
    ");
    $general_count->execute($params);
    $general_tasks = (int)$general_count->fetchColumn();

    if ($general_tasks > 0 || empty($breakdown_results)) {
        // Prepend the general tasks if they exist or if the specific project list is empty
        array_unshift($project_breakdown_data['labels'], 'General / Other');
        array_unshift($project_breakdown_data['data'], $general_tasks);
    }


    foreach ($breakdown_results as $row) {
        $project_breakdown_data['labels'][] = $row['project'];
        $project_breakdown_data['data'][] = (int)$row['tasks'];
    }


    echo json_encode([
        'activity_volume_chart' => $activity_volume_data,
        'project_breakdown_chart' => $project_breakdown_data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}
?>