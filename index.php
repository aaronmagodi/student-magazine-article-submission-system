<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Get current academic year deadlines
$deadlineInfo = '';
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get current academic year with deadlines from planb database
    $yearStmt = $conn->query("SELECT academic_year, submission_deadline, final_closure_date 
                             FROM system_settings
                             ORDER BY id DESC LIMIT 1");
    $currentYearData = $yearStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentYearData) {
        $submissionDate = !empty($currentYearData['submission_deadline']) ? 
            date('F j, Y', strtotime($currentYearData['submission_deadline'])) : 'Not set';
        $finalDate = !empty($currentYearData['final_closure_date']) ? 
            date('F j, Y', strtotime($currentYearData['final_closure_date'])) : 'Not set';
        
        $deadlineInfo = '
        <div class="deadlines">
            <p><strong>Current Academic Year:</strong> ' . htmlspecialchars($currentYearData['academic_year']) . '</p>
            <p><strong>Submission Deadline:</strong> ' . $submissionDate . '</p>
            <p><strong>Final Closure Date:</strong> ' . $finalDate . '</p>
        </div>';
    }
} catch (PDOException $e) {
    // Log error instead of showing to users
    error_log("Database error in home page: " . $e->getMessage());
    $deadlineInfo = '<div class="alert">Deadline information is currently unavailable.</div>';
}

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Magazine System</title>
    <meta name="description" content="Submit and manage contributions for the university magazine">
    
    <!-- CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/styles.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/welcome.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .welcome-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            text-align: center;
        }
        
        .hero {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            padding: 3rem 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .role-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
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
        
        .system-info {
            margin-top: 3rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: left;
        }
        
        .deadlines {
            margin-top: 1rem;
            padding: 1rem;
            background: #e7f5ff;
            border-radius: 4px;
        }
        
        footer {
            text-align: center;
            margin-top: 3rem;
            padding: 1.5rem;
            background: #2c3e50;
            color: white;
        }
        
        .registration-links {
            margin-top: 2rem;
            padding: 1rem;
            background: #f1f8fe;
            border-radius: 8px;
        }
        
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .role-cards {
                grid-template-columns: 1fr;
            }
            
            .hero {
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="hero">
            <h1><i class="fas fa-book-open"></i> University Magazine System</h1>
            <p class="lead">Submit, manage, and oversee contributions for the annual university magazine</p>
        </div>
        
        <?php if (isset($_GET['registered'])) : ?>
        <div class="alert alert-success" style="margin: 1rem auto; max-width: 600px;">
            Registration successful! Please log in with your credentials.
        </div>
        <?php endif; ?>
        
        <h2>Access the System</h2>
        <p>Select your role to continue to the login page</p>
        
        <div class="role-cards">
            <!-- Student Card -->
            <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3>Student</h3>
                <p>Submit your articles and images for the magazine. Track your submissions and coordinator feedback.</p>
                <a href="login.php?role=student" class="btn-role">
                    <i class="fas fa-sign-in-alt"></i> Student Login
                </a>
            </div>
            
            <!-- Marketing Coordinator Card -->
            <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3>Marketing Coordinator</h3>
                <p>Manage submissions for your faculty. Review, comment, and select contributions for publication.</p>
                <a href="login.php?role=marketing_coordinator" class="btn-role">
                    <i class="fas fa-sign-in-alt"></i> Coordinator Login
                </a>
            </div>
            
            <!-- Marketing Manager Card -->
            <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <h3>Marketing Manager</h3>
                <p>Oversee all submissions, monitor progress, and manage deadlines for the magazine publication process.</p>
                <a href="login.php?role=marketing_manager" class="btn-role">
                    <i class="fas fa-sign-in-alt"></i> Manager Login
                </a>
            </div>

            <!-- Admin Card -->
            <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Admin</h3>
                <p>Manage system settings, user roles, and overall operations of the magazine system.</p>
                <a href="login.php?role=admin" class="btn-role">
                    <i class="fas fa-sign-in-alt"></i> Admin Login
                </a>
            </div>
        </div>

         <!-- Guest Card -->
         <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <h3>Guest</h3>
                <p>View selected magazine contributions without logging in.</p>
                <a href="guest/dashboard.php" class="btn-role">
                    <i class="fas fa-external-link-alt"></i> View as Guest
                </a>
            </div>
        </div>
        
        <div class="registration-links">
            <h3><i class="fas fa-user-plus"></i> New to the System?</h3>
            <p>Don't have an account yet? Register for access based on your role:</p>
            <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                <a href="register_student.php?role=student" class="btn-role" style="background: #2ecc71;">
                    <i class="fas fa-user-graduate"></i> Register as Student
                </a>
                <a href="register_coordinator.php?role=marketing_coordinator" class="btn-role" style="background: #9b59b6;">
                    <i class="fas fa-users-cog"></i> Register as Coordinator
                </a>
                <a href="register_manager.php?role=marketing_manager" class="btn-role" style="background: #e67e22;">
                    <i class="fas fa-tasks"></i> Register as Manager
                </a>
            </div>
        </div>

        <div class="system-info">
            <h3>System Information</h3>
            <p>Welcome to the University Magazine System. Please choose your role to proceed.</p>
            
            <!-- Display deadlines -->
            <?php echo $deadlineInfo; ?>

            <!-- Registration Links -->
            <div class="registration-links">
                <p>Not registered yet? <a href="register.php">Sign up here</a> to gain access to the system.</p>
            </div>
        </div>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> University Magazine System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
