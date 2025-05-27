<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Secure session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => false, // Set to true with HTTPS
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
    // Collect and sanitize input
    $role = $_POST['role'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $facultyId = $_POST['faculty_id'] ?? null;
    $termsAccepted = isset($_POST['terms_accepted']);

    $errors = [];

    if (empty($firstName)) $errors['first_name'] = 'First name is required';
    if (empty($lastName)) $errors['last_name'] = 'Last name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required';
    if (empty($username)) $errors['username'] = 'Username is required';
    if (empty($role)) $errors['role'] = 'role is required';
    elseif (strlen($username) < 4) $errors['username'] = 'Username must be at least 4 characters';
    if (empty($password)) $errors['password'] = 'Password is required';
    elseif (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters';
    if ($password !== $confirmPassword) $errors['confirm_password'] = 'Passwords do not match';
    if (!$termsAccepted) { $errors['terms_accepted'] = 'You must accept the terms and conditions';}

    // Validate faculty
    if (empty($facultyId) || !is_numeric($facultyId)) {
        $errors['faculty_id'] = 'Please select your faculty';
    } else {
        try {
            $stmt = $conn->prepare("SELECT 1 FROM faculties WHERE id = ?");
            $stmt->execute([$facultyId]);
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

    if (!empty($errors)) {
        $_SESSION['registration_errors'] = $errors;
        $_SESSION['old_input'] = $_POST;
        header("Location: register_" . $role . ".php");
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $requiresApproval = ($role === 'marketing_manager') ? 1 : 0;

// Insert User details in users table
    try {
        $conn->beginTransaction();
        $insertStmt = $conn->prepare("
            INSERT INTO users (
                username, 
                email, 
                password, 
                role, 
                faculty_id, 
                first_name, 
                last_name, 
                approved
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insertStmt->execute([
            $username,
            $email,
            $hashedPassword,
            $role,
            $facultyId,
            $firstName,
            $lastName,
            $requiresApproval
        ]);


        $userId = $conn->lastInsertId(); // ✅ fix: capture the inserted user's ID
$termsId = 1; // or whatever your current terms version is
        // Record terms acceptance
        $termsStmt = $conn->prepare("
        INSERT INTO user_terms (user_id, terms_id)
        VALUES (?, ?)
    ");
    $termsStmt->execute([$userId, $termsId]);

        $conn->commit();

        $_SESSION['registration_success'] = true;
        header("Location: login.php?role=" . urlencode($role) . "&new=1");
        exit;


        
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Registration error: " . $e->getMessage());
        $_SESSION['registration_errors'] = ['general' => 'Registration failed. Please try again.'];
        $_SESSION['old_input'] = $_POST;

       echo "<pre>";
       print_r($errors);
       print_r($_POST);
       exit;

       // header("Location: register_" . $role . ".php");
      //  exit;
    }
catch (PDOException $e) {
    $conn->rollBack();

    // Log the actual error to error log
    error_log("Registration error: " . $e->getMessage());

    

    // Display the actual error back on the form (for development)
    $_SESSION['registration_errors'] = [
        'general' => 'Registration failed: ' . $e->getMessage() // <-- Actual error shown
    ];
    $_SESSION['old_input'] = $_POST;
    header("Location: register_" . $role . ".php");
    exit;
}

    

} else {
    header("Location: index.php");
    exit;
}
