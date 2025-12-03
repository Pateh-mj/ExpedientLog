<?php
require_once 'config.php';

// Security: Only supervisors & admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['supervisor', 'admin'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    try {
        // Generate a random temporary password
        $temp_password = bin2hex(random_bytes(4)); // 8 characters
        $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
        
        // Update the user's password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$password_hash, $user_id]);
        
        header("Location: admin_dashboard.php?message=" . urlencode(
            "Password reset successful. Temporary password: $temp_password"
        ));
    } catch (Exception $e) {
        header("Location: admin_dashboard.php?message=" . urlencode("Error resetting password"));
    }
    exit();
}