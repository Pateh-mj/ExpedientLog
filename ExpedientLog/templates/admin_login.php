<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (in_array($user['role'], ['supervisor', 'admin'])) {
                    // SUCCESS: Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_activity'] = time();

                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    $error = "Access denied. This portal is for Supervisors only.";
                }
            } else {
                $error = "Invalid credentials.";
            }
        } catch (Exception $e) {
            $error = "System error. Contact IT Desk.";
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Supervisor Login • ExpedientLog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" href="logo.png" type="image/png">
  <style>
    body {
      background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
    }
    .login-card {
      border-radius: 1.5rem;
      overflow: hidden;
      box-shadow: 0 20px 50px rgba(0,0,0,0.4);
    }
    .card-header {
      background: #001f3f;
      color: white;
      padding: 2rem;
      text-align: center;
      border: none;
    }
    .znbc-badge {
      background: #006633;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 50px;
      font-size: 0.8rem;
      font-weight: bold;
    }
    .btn-admin {
      background: #FF9933;
      border: none;
      font-weight: bold;
      padding: 0.8rem;
      border-radius: 50px;
    }
    .btn-admin:hover {
      background: #e68900;
    }
    .form-control-lg {
      border-radius: 1rem;
      padding: 0.8rem 1.2rem;
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5 col-lg-4">

        <div class="card login-card border-0">
          
          <!-- Header -->
          <div class="card-header">
            <div class="mb-3">
              <span class="znbc-badge">EXPEDIA INTERNAL SYSTEM</span>
            </div>
            <h1 class="h3 fw-bold mb-2">ExpedientLog</h1>
            <p class="opacity-90">Supervisor & Admin Portal</p>
          </div>

          <!-- Body -->
          <div class="card-body p-5 bg-white">

            <!-- Alerts -->
            <?php if ($error): ?>
              <div class="alert alert-danger alert-dismissible fade show">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout'])): ?>
              <div class="alert alert-success">You have been logged out securely.</div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="post">
              <div class="mb-4">
                <input type="text" name="username" class="form-control form-control-lg" 
                       placeholder="Supervisor Username" required autofocus>
              </div>
              <div class="mb-4">
                <input type="password" name="password" class="form-control form-control-lg" 
                       placeholder="Password" required>
              </div>

              <button type="submit" class="btn btn-admin btn-lg w-100 text-white">
                Enter Supervisor Dashboard
              </button>
            </form>

            <div class="text-center mt-4">
              <small class="text-muted">
                Only authorized supervisors may access this portal.<br>
                <strong>Lusaka • <?php echo date('l, j F Y'); ?> • <?php echo date('h:i A'); ?> CAT</strong>
              </small>
            </div>

            <div class="text-center mt-3">
              <a href="landing.html" class="text-decoration-none small">
                ← Back to Home Page
              </a>
            </div>

            <div class="text-center mt-3">
              <a href="admin_register.php" class="text-decoration-none small">
                ← Go Sign up as Admin
              </a>
            </div>

          </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-white mt-4 opacity-75">
          <small>
            © 2025 ExpedientLog • Proudly Built By ACS<br>
            For support: IT Desk • Ext. 789
          </small>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>