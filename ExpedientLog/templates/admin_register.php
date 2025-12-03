<?php
require_once 'config.php';

// This should be a strong, random key. Store this securely and share it only with trusted admins
define('ADMIN_REGISTRATION_KEY', 'exp_2025_secure_key');

// Allowed departments (whitelist) - same as regular registration
$allowed_departments = [
    'General' => 'General Operations',
    'News' => 'News & Editorial',
    'Technical' => 'Technical Support',
    'Finance' => 'Finance & Accounting',
    'HR' => 'Human Resources'
];

if (empty($_SESSION['csrf'])) {
    try {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password_raw = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $department = array_key_exists($_POST['department'] ?? 'General', $allowed_departments) 
            ? $_POST['department'] 
            : 'General';
        $admin_key = trim($_POST['admin_key'] ?? '');

        // Verify admin registration key
        if ($admin_key !== ADMIN_REGISTRATION_KEY) {
            $errors[] = 'Invalid administrator registration key.';
        }

        if (empty($username)) {
            $errors[] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long.';
        }

        if (empty($password_raw)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password_raw) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $password_raw)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $password_raw)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $password_raw)) {
            $errors[] = 'Password must contain at least one number.';
        }

        if ($password_raw !== $password_confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);
            $role = 'supervisor'; // This account will be created as supervisor

            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, department) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $password, $role, $department]);
                unset($_SESSION['csrf']);
                header("Location: login.php?registered=1&type=admin");
                exit();
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    $errors[] = 'This username is already taken. Please choose another.';
                } else {
                    error_log('Admin registration error: ' . $e->getMessage());
                    $errors[] = 'Registration failed. Please try again later.';
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Administrator Account • ExpedientLog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { 
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #1e2024 0%, #17181c 100%);
            min-height: 100vh;
            color: #fff;
        }
        .form-control:focus, .form-select:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.15);
        }
        .password-strength {
            height: 4px;
            transition: all 0.3s;
            border-radius: 2px;
        }
        .strength-weak { background: #dc3545; width: 25%; }
        .strength-medium { background: #ffc107; width: 50%; }
        .strength-strong { background: #198754; width: 100%; }
        .card {
            border: 0;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 40px -12px rgba(0,0,0,0.35);
        }
        .input-group-text {
            background: transparent;
            border-left: 0;
            color: #adb5bd;
        }
        .form-control, .form-select {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .form-control::placeholder {
            color: #6c757d;
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        }
        .form-select option {
            background: #343a40;
            color: #fff;
        }
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
        }
        .btn-warning {
            color: #000;
        }
        .text-muted {
            color: #adb5bd !important;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold mb-1">Create Administrator Account</h2>
                    <p class="text-muted">Secure registration for ExpedientLog supervisors</p>
                </div>

                <div class="card">
                    <div class="card-body p-4 p-md-5">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0 ps-3">
                                    <?php foreach($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                            
                            <!-- Admin Key -->
                            <div class="mb-4">
                                <label class="form-label" for="admin_key">Administrator Key</label>
                                <div class="input-group">
                                    <span class="input-group-text border-end-0"><i class="bi bi-key"></i></span>
                                    <input type="password" id="admin_key" name="admin_key" 
                                           class="form-control border-start-0" 
                                           placeholder="Enter administrator key"
                                           required>
                                </div>
                            </div>

                            <!-- Username -->
                            <div class="mb-4">
                                <label class="form-label" for="username">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text border-end-0"><i class="bi bi-person"></i></span>
                                    <input type="text" id="username" name="username" 
                                           class="form-control border-start-0" 
                                           placeholder="Choose a username"
                                           required minlength="3"
                                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label class="form-label" for="password">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text border-end-0"><i class="bi bi-lock"></i></span>
                                    <input type="password" id="password" name="password" 
                                           class="form-control border-start-0" 
                                           placeholder="Create a strong password"
                                           required minlength="8">
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength mt-2"></div>
                                <div class="form-text text-muted">Must be at least 8 characters with numbers & letters</div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-4">
                                <label class="form-label" for="password_confirm">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text border-end-0"><i class="bi bi-shield-lock"></i></span>
                                    <input type="password" id="password_confirm" name="password_confirm" 
                                           class="form-control border-start-0" 
                                           placeholder="Confirm your password"
                                           required>
                                </div>
                            </div>

                            <!-- Department -->
                            <div class="mb-4">
                                <label class="form-label" for="department">Department</label>
                                <select id="department" name="department" class="form-select" required>
                                    <?php foreach($allowed_departments as $key => $label): ?>
                                        <option value="<?= htmlspecialchars($key) ?>" <?= ($key === ($_POST['department'] ?? 'General') ? 'selected' : '') ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-warning w-100 py-2 fw-semibold mb-3">
                                Create Administrator Account
                            </button>

                            <p class="text-center mb-0">
                                <a href="admin_login.php" class="text-warning text-decoration-none">Return to Login</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirm');
        const strengthIndicator = document.querySelector('.password-strength');
        const togglePassword = document.getElementById('togglePassword');
        
        // Password visibility toggle
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? 
                '<i class="bi bi-eye"></i>' : 
                '<i class="bi bi-eye-slash"></i>';
        });

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^A-Za-z0-9]/)) strength++;
            
            strengthIndicator.className = 'password-strength';
            if (strength < 2) {
                strengthIndicator.classList.add('strength-weak');
            } else if (strength < 4) {
                strengthIndicator.classList.add('strength-medium');
            } else {
                strengthIndicator.classList.add('strength-strong');
            }
        }

        passwordInput.addEventListener('input', () => {
            checkPasswordStrength(passwordInput.value);
        });

        // Real-time password match validation
        function checkPasswordMatch() {
            if (confirmInput.value === passwordInput.value) {
                confirmInput.setCustomValidity('');
            } else {
                confirmInput.setCustomValidity('Passwords do not match');
            }
        }

        passwordInput.addEventListener('input', checkPasswordMatch);
        confirmInput.addEventListener('input', checkPasswordMatch);

        // Bootstrap form validation
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    </script>
</body>
</html>