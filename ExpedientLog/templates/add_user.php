<?php
require_once 'config.php';

// Security: Only supervisors & admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['supervisor', 'admin'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $department = $_POST['department'] ?? 'General';
    
    try {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert the new user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, department) VALUES (?, ?, 'employee', ?)");
        $stmt->execute([$username, $password_hash, $department]);
        
        header("Location: admin_dashboard.php?message=" . urlencode("User $username added successfully"));
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            header("Location: admin_dashboard.php?message=" . urlencode("Username already exists"));
        } else {
            header("Location: admin_dashboard.php?message=" . urlencode("Error adding user"));
        }
    }
    exit();
}