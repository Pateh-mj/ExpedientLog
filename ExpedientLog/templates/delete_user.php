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
        // Check if trying to delete a supervisor
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user && $user['role'] !== 'supervisor') {
            // Delete the user and their tickets
            $pdo->beginTransaction();
            
            // Delete tickets first (foreign key constraint)
            $stmt = $pdo->prepare("DELETE FROM tickets WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Then delete the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            header("Location: admin_dashboard.php?message=" . urlencode("User deleted successfully"));
        } else {
            header("Location: admin_dashboard.php?message=" . urlencode("Cannot delete supervisor accounts"));
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: admin_dashboard.php?message=" . urlencode("Error deleting user"));
    }
    exit();
}