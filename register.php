<?php
declare(strict_types=1);
// register.php

// Load configuration first
require_once __DIR__ . '/includes/config.php';

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

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Get allowed registration roles (exclude admin and guest)
$allowedRoles = ['student', 'coordinator', 'manager'];
$currentRole = $_GET['role'] ?? null;

// If specific role is requested and valid, redirect to specific form
if ($currentRole && in_array($currentRole, $allowedRoles)) {
    header("Location: register_{$currentRole}.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - University Magazine System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .registration-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .role-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            text-align: center;
            border: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            border-color: #3498db;
        }
        
        .role-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #3498db;
        }
        
        .btn-role {
            display: inline-block;
            margin-top: auto;
            padding: 0.5rem 1rem;
            background: #3498db;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        
        .btn-role:hover {
            background: #2980b9;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .login-prompt {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }
        
        .admin-notice {
            margin-top: 1rem;
            padding: 1rem;
            background: #fff3cd;
            border-radius: 4px;
            text-align: center;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .role-cards {
                grid-template-columns: 1fr;
            }
            
            .registration-container {
                margin: 1rem;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="registration-container">
        <div class="page-header">
            <h1><i class="fas fa-user-plus"></i> Create Your Account</h1>
            <p>Select your role to begin the registration process</p>
        </div>
        
        <div class="role-cards">
            <!-- Student Registration -->
            <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3>Student</h3>
                <p>Submit articles and images for the university magazine. Track your submissions and receive feedback.</p>
                <a href="register_student.php" class="btn-role">
                    <i class="fas fa-user-plus"></i> Register as Student
                </a>
            </div>
            
            <!-- Coordinator Registration -->
            <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3>Marketing Coordinator</h3>
                <p>Manage submissions for your faculty. Review, comment, and select contributions.</p>
                <a href="register_coordinator.php" class="btn-role" style="background: #9b59b6;">
                    <i class="fas fa-user-plus"></i> Register as Coordinator
                </a>
            </div>
            
            <!-- Manager Registration -->
            <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <h3>Marketing Manager</h3>
                <p>Oversee the entire magazine process. View selected contributions and generate reports.</p>
                <a href="register_manager.php" class="btn-role" style="background: #e67e22;">
                    <i class="fas fa-user-plus"></i> Register as Manager
                </a>
            </div>
        </div>
        
        <div class="admin-notice">
            <i class="fas fa-info-circle"></i> Note: Administrator accounts can only be created by existing administrators.
        </div>
        
        <div class="login-prompt">
            <p>Already have an account? <a href="login.php" style="color: #3498db;">Login here</a></p>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>