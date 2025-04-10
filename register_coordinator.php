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
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get all faculties for dropdown
try {
    $facultiesStmt = $conn->query("SELECT faculty_id, faculty_name FROM faculties ORDER BY faculty_name");
    $faculties = $facultiesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $faculties = [];
}

// Check for errors from previous submission
$errors = $_SESSION['registration_errors'] ?? [];
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['registration_errors'], $_SESSION['old_input']);

// Check for success message
$registrationSuccess = $_SESSION['registration_success'] ?? false;
unset($_SESSION['registration_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Registration - University Magazine System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .registration-form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            background-color: white;
        }
        .btn-submit {
            width: 100%;
            padding: 0.75rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-submit:hover {
            background: #2980b9;
        }
        .error-message {
            color: #e74c3c;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-row {
            display: flex;
            gap: 1rem;
        }
        .form-row .form-group {
            flex: 1;
        }
        .password-group {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
        }
        .password-strength {
            height: 5px;
            background: #eee;
            margin-top: 5px;
            border-radius: 3px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background 0.3s;
        }
        @media (max-width: 576px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="registration-form-container">
        <div class="form-header">
            <h1><i class="fas fa-users-cog"></i> Coordinator Registration</h1>
            <p>Create your account to manage faculty submissions for the university magazine</p>
        </div>
        
        <?php if ($registrationSuccess): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Registration successful! Please <a href="login.php">login</a>.
            </div>
        <?php endif; ?>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo BASE_URL; ?>process_registration.php" class="registration-form" id="registrationForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="role" value="marketing_coordinator">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" 
                           value="<?php echo htmlspecialchars($oldInput['first_name'] ?? ''); ?>" required
                           pattern="[A-Za-z\s]{2,50}" title="Only letters and spaces (2-50 characters)">
                    <?php if (isset($errors['first_name'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" 
                           value="<?php echo htmlspecialchars($oldInput['last_name'] ?? ''); ?>" required
                           pattern="[A-Za-z\s]{2,50}" title="Only letters and spaces (2-50 characters)">
                    <?php if (isset($errors['last_name'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['last_name']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">University Email</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($oldInput['email'] ?? ''); ?>" required
                       pattern=".+@university\.edu$" title="Must be a valid university email address">
                <small class="form-text">Must end with @university.edu</small>
                <?php if (isset($errors['email'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?php echo htmlspecialchars($oldInput['username'] ?? ''); ?>" required
                       pattern="[A-Za-z0-9_]{4,20}" title="4-20 characters (letters, numbers, underscores)">
                <?php if (isset($errors['username'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['username']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="faculty_id" class="form-label">Faculty</label>
                <select id="faculty_id" name="faculty_id" class="form-select" required>
                    <option value="">Select your faculty</option>
                    <?php foreach ($faculties as $faculty): ?>
                        <option value="<?php echo $faculty['faculty_id']; ?>" 
                            <?php echo ($oldInput['faculty_id'] ?? '') == $faculty['faculty_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['faculty_id'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['faculty_id']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group password-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-input-wrapper">
                    <input type="password" id="password" name="password" class="form-control" required
                           minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                           title="Must contain at least one number, one uppercase and lowercase letter, and at least 8 characters">
                    <button type="button" class="toggle-password" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                </div>
                <small class="form-text">Minimum 8 characters with at least one uppercase, one lowercase, and one number</small>
                <?php if (isset($errors['password'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group password-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="password-input-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    <button type="button" class="toggle-password" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-submit">Register</button>
            </div>
        </form>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Password visibility toggle
        document.querySelectorAll('.toggle-password').forEach((button) => {
            button.addEventListener('click', () => {
                const passwordField = button.previousElementSibling;
                if (passwordField.type === "password") {
                    passwordField.type = "text";
                    button.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passwordField.type = "password";
                    button.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
        });
        
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const passwordStrengthBar = document.getElementById('passwordStrengthBar');
        passwordInput.addEventListener('input', () => {
            const strength = getPasswordStrength(passwordInput.value);
            passwordStrengthBar.style.width = strength + '%';
            passwordStrengthBar.style.backgroundColor = getStrengthColor(strength);
        });

        function getPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength += 20;
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[a-z]/.test(password)) strength += 20;
            if (/\d/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;
            return strength;
        }

        function getStrengthColor(strength) {
            if (strength < 40) return '#e74c3c';
            if (strength < 60) return '#f39c12';
            if (strength < 80) return '#f1c40f';
            return '#2ecc71';
        }
    </script>
</body>
</html>
