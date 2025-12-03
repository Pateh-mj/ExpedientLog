<?php
// logout.php - Secure Logout for ExpedientLog
session_start();

// Destroy ALL session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login with success message
// Redirect to appropriate login page based on a role parameter (defaults to user)
$role = isset($_GET['role']) ? strtolower($_GET['role']) : (isset($_POST['role']) ? strtolower($_POST['role']) : "user");
if ($role === 'admin' || $role === 'supervisor') {
    header('Location: admin_login.php?logout=success');
}
    elseif ($role === 'user' || $role === 'employee') {
    header('Location: login.php?logout=success');
} else {
    header('Location: login.php?logout=success');
}
exit();
?>