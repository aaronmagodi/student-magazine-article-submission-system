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
        'secure' => true,
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

// Initialize variables
$auth = new Auth();
$error = '';
$role = $_GET['role'] ?? '';

// Allowed user roles
$allowedRoles = ['student', 'coordinator', 'manager', 'admin'];

// Display-friendly role names
$roleNames = [
    'student' => 'Student',
    'coordinator' => 'Marketing Coordinator',
    'manager' => 'Marketing Manager',
    'admin' => 'System Administrator'
];

// Redirect if an invalid role is provided
if (!empty($role) && !in_array($role, $allowedRoles, true)) {
    header('Location: login.php');
    exit();
}

// Generate CSRF token if needed
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $remember = isset($_POST['remember']); // For 'Remember Me' functionality

        // Validate inputs
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (empty($password)) {
            $error = 'Password is required.';
        } elseif (!in_array($role, $allowedRoles, true)) {
            $error = 'Invalid user role specified.';
        } else {
            // Attempt authentication
            $loginResult = $auth->login($email, $password, $role); // Pass role here, not remember

            if ($loginResult === true) {
                $userRole = $auth->getUserRole();

                if ($userRole !== $role) {
                    $error = 'You do not have permission to access this role.';
                    $auth->logout();
                } else {
                    session_regenerate_id(true);
                    $_SESSION['last_login'] = time();
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    // Redirect map based on user role
                    $redirectMap = [
                        'student' => 'student/dashboard.php',
                        'coordinator' => 'coordinator/dashboard.php',
                        'manager' => 'manager/dashboard.php',
                        'admin' => 'admin/dashboard.php'
                    ];

                    // Handle "Remember Me" logic if checked
                    if ($remember) {
                        setcookie('user_email', $email, time() + (86400 * 30), "/"); // 30 days
                        setcookie('user_role', $role, time() + (86400 * 30), "/");
                    } else {
                        setcookie('user_email', '', time() - 3600, "/");
                        setcookie('user_role', '', time() - 3600, "/");
                    }

                    header('Location: ' . BASE_URL . $redirectMap[$userRole]);
                    exit;
                }
            } else {
                $error = $loginResult === false ? 'Invalid email or password.' : $loginResult;
                error_log("Login failed for email: $email, role: $role");
            }
        }
    }
}

// Set page title
$pageTitle = $roleNames[$role] ?? 'Login';
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
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
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
        .form-label {
            font-size: 14px;
            color: #333;
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
        .btn-login:hover {
            background-color: #45a049;
        }
        .login-links {
            text-align: center;
            margin-top: 20px;
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
<?php include 'includes/header.php'; ?>

<main class="main-content">
    <div class="login-container">
        <?php if (isset($roleNames[$role])): ?>
            <div class="role-badge">
                <i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($roleNames[$role]); ?>
            </div>
        <?php endif; ?>

        <h1 class="login-title">University Magazine System</h1>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form" novalidate>
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="your.email@university.edu" required autocomplete="username">
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
            <a href="register.php?role=<?php echo htmlspecialchars($role); ?>">
                <i class="fas fa-user-plus"></i> Create an account
            </a>
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> Back to role selection
            </a>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

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
</body>
</html>
