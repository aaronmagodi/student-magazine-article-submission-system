<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Secure session initialization
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Includes and dependencies
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$auth = new Auth();
$error = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (empty($password)) {
            $error = 'Password is required.';
        } else {
            $loginResult = $auth->login($email, $password);

            if ($loginResult === true) {
                $userRole = $auth->getUserRole();

                session_regenerate_id(true);
                $_SESSION['last_login'] = time();
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $redirectMap = [
                    'student' => 'student/dashboard.php',
                    'coordinator' => 'coordinator/dashboard.php',
                    'manager' => 'manager/dashboard.php',
                    'admin' => 'admin/dashboard.php'
                ];

                if ($remember) {
                    setcookie('user_email', $email, time() + (86400 * 30), "/");
                } else {
                    setcookie('user_email', '', time() - 3600, "/");
                }

                if (isset($redirectMap[$userRole])) {
                    header('Location: ' . BASE_URL . $redirectMap[$userRole]);
                    exit;
                } else {
                    $error = 'Unknown role. Access denied.';
                }
            } else {
                $error = $loginResult === false ? 'Invalid email or password.' : $loginResult;
                error_log("Login failed for email: $email");
            }
        }
    }
}

// Set page title
$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - University Magazine System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }
        .main-content {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        .form-group {
            margin-bottom: 1rem;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .password-wrapper {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }
        .btn-login {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .btn {
            background-color: #0056b3;
            color: white;
            padding: 0.75rem;
            width: 100%;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-login:hover {
            background-color: #45a049;
        }
        .login-links {
            text-align: center;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #004494;
        }
        .error {
            color: red;
            margin-bottom: 1rem;
        }

        .login-links a {
            font-size: 14px;
            color: #007bff;
            text-decoration: none;
        }
        .login-links a:hover {
            text-decoration: underline;
        }
        .role-badge {
            background-color: #007bff;
            color: #fff;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .remember-me {
            display: flex;
            align-items: center;
        }
        .remember-me input {
            margin-right: 10px;
        }
        .remember-me label {
            font-size: 14px;
            color: #333;
        }


    </style>
</head>
<body>
<div class="main-content">
    <div class="login-container">
    <h1 class="login-title">University Magazine System</h1>
        <?php if (!empty($error)) : ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="" class="login-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="form-group">
                <label for="email" class="form-label">Email address</label>
                <input type="email" name="email" id="email" clas="form-control" required value="<?php echo htmlspecialchars($_COOKIE['user_email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-control" required
                           placeholder="Enter your password" autocomplete="current-password">
                    <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div style="text-align: right; margin-top: 0.5rem;">
                    <a href="forgot-password.php" style="font-size: 0.85rem;">Forgot password?</a>
                    
                </div>
            </div>
            <div class="form-group remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="login-links">
            <a href="register.php?role">
                <i class="fas fa-user-plus"></i> Create an account
            </a>
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> Back to role selection
            </a>
        </div>
    </div>
     
    

</body>
</html>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const passwordToggle = document.querySelector('.password-toggle');
        const passwordInput = document.getElementById('password');

        if (passwordToggle && passwordInput) {
            passwordToggle.addEventListener('click', () => {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                passwordToggle.innerHTML = type === 'password'
                    ? '<i class="fas fa-eye"></i>'
                    : '<i class="fas fa-eye-slash"></i>';
            });
        }

        // Optional: Front-end form validation alert
        const form = document.querySelector('.login-form');
        form?.addEventListener('submit', (e) => {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            if (!email.value.trim() || !password.value.trim()) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
</script>