<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Initialize secure session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$auth = new Auth();
$error = '';
$success = '';
$validToken = false;
$token = $_GET['token'] ?? '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Validate token
if (!empty($token)) {
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("
            SELECT pr.*, u.email 
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used_at IS NULL
        ");
        $stmt->execute([$token]);
        $resetRequest = $stmt->fetch();

        if ($resetRequest) {
            $validToken = true;
            $_SESSION['reset_user_id'] = $resetRequest['user_id'];
            $_SESSION['reset_token'] = $token;
        } else {
            $error = 'Invalid or expired reset link';
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = 'Error validating reset token';
    }
}

// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Invalid form submission');
        }

        // Validate passwords
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }

        // Update password
        $db = (new Database())->getConnection();
        $db->beginTransaction();

        try {
            // Hash new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user password
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $_SESSION['reset_user_id']]);
            
            // Mark token as used
            $stmt = $db->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?");
            $stmt->execute([$_SESSION['reset_token']]);
            
            $db->commit();
            
            // Clear session and show success
            unset($_SESSION['reset_user_id'], $_SESSION['reset_token']);
            $success = 'Password updated successfully! You can now login with your new password.';
            $validToken = false;
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - University Magazine</title>
    <link href="assets/css/styles.css" rel="stylesheet">
    <link href="assets/css/auth.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <i class="fas fa-book-open"></i> University Magazine
            </div>
        </div>
    </header>

    <main class="auth-container">
        <div class="auth-card">
            <h1><?= $validToken ? 'Reset Your Password' : 'Password Reset' ?></h1>
            
            <?php if ($error): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
                <div class="text-center">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php elseif ($validToken): ?>
                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password (min 8 characters)</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required minlength="8">
                            <button type="button" class="toggle-password" aria-label="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                            <button type="button" class="toggle-password" aria-label="Show password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Update Password
                    </button>
                </form>
            <?php else: ?>
                <div class="alert info">
                    <i class="fas fa-info-circle"></i> Please check your email for a valid password reset link
                </div>
                <div class="text-center">
                    <a href="forgot-password.php" class="btn btn-primary">Request New Link</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> University Magazine. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/auth.js"></script>
</body>
</html>