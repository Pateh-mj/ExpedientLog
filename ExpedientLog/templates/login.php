<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            // NOTE: This SQL SELECT query is standard and works perfectly with PostgreSQL.
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'employee';

                // Redirect based on role
                if ($_SESSION['role'] === 'supervisor' || $_SESSION['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } catch (Exception $e) {
            $error = "Login error. Try again.";
            // You should log $e->getMessage() for debugging purposes
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ExpedientLog • Internal Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg, #003366, #001f3f); font-family: 'Inter', sans-serif; }
    .login-card { border-radius: 1rem; }
    .btn-primary { background: #006633; border: none; }
    .btn-primary:hover { background: #004d26; }
  </style>
</head>
<body class="d-flex align-items-center min-vh-100">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5 col-lg-4">
        <div class="card shadow login-card border-0">
          <div class="card-body p-5">
            <div class="text-center mb-4">
              <h1 class="h3 fw-bold text-primary">ExpedientLog</h1>
              <p class="text-muted">Internal Work Logging System</p>
            </div>

            <?php if ($error): ?>
              <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post">
              <div class="mb-3">
                <input type="text" name="username" class="form-control form-control-lg" 
                       placeholder="Username" required autofocus>
              </div>
              <div class="mb-3">
                <input type="password" name="password" class="form-control form-control-lg" 
                       placeholder="Password" required>
              </div>
              <button type="submit" class="btn btn-primary btn-lg w-100">
                Enter ExpedientLog
              </button>
            </form>

            <p class="text-center mt-4 text-muted small">
              New user? <a href="register.php" class="text-decoration-none fw-bold">Register here</a>
            </p>
            <div class="text-center mt-3">
              <a href="landing.html" class="btn btn-primary btn-sm w-10"> Return to Home</a>
<br><br>
              <small class="text-muted">Expedia Internal System • Lusaka, Zambia</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>