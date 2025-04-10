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
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input data
    $role = $_POST['role'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $facultyId = $_POST['faculty_id'] ?? null;

    // Validation
    $errors = [];

    if (empty($firstName)) $errors['first_name'] = 'First name is required';
    if (empty($lastName)) $errors['last_name'] = 'Last name is required';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Valid email is required';
    }

    if (empty($username)) $errors['username'] = 'Username is required';
    elseif (strlen($username) < 4) $errors['username'] = 'Username must be at least 4 characters';

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // Role-specific validation for faculty ID
    // Validate faculty
if (empty($input['faculty_id']) || !is_numeric($input['faculty_id'])) {
    $errors['faculty_id'] = 'Please select your faculty';
} else {
    // Verify faculty exists
    try {
        $stmt = $conn->prepare("SELECT 1 FROM faculties WHERE id = ?");
        $stmt->execute([$input['faculty_id']]);
        if (!$stmt->fetch()) {
            $errors['faculty_id'] = 'Invalid faculty selected';
        }
    } catch (PDOException $e) {
        error_log("Faculty validation error: " . $e->getMessage());
        $errors['general'] = 'System error during faculty validation';
    }
}

    // Check for duplicates
    if (empty($errors)) {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $checkStmt->execute([$email, $username]);

        if ($checkStmt->fetch()) {
            $errors['general'] = 'Email or username already exists';
        }
    }

    // Redirect with errors if any
    if (!empty($errors)) {
        $_SESSION['registration_errors'] = $errors;
        $_SESSION['old_input'] = $_POST;
        header("Location: register_" . $role . ".php");
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $conn->beginTransaction();

        // Insert into users table (only matching columns)
        $insertStmt = $conn->prepare("
            INSERT INTO users (
                username, 
                email, 
                password, 
                role, 
                faculty_id, 
                first_name, 
                last_name
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $insertStmt->execute([
            $username,
            $email,
            $hashedPassword,
            $role,
            $facultyId,
            $firstName,
            $lastName
        ]);

        $conn->commit();

        $_SESSION['registration_success'] = true;
        header("Location: login.php?role=" . urlencode($role) . "&new=1");
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Registration error: " . $e->getMessage());
        $_SESSION['registration_errors'] = ['general' => 'Registration failed. Please try again.'];
        $_SESSION['old_input'] = $_POST;
        header("Location: register_" . $role . ".php");
        exit;
    }

} else {
    header("Location: index.php");
    exit;
}
?>
