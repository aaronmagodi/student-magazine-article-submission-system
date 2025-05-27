<?php
declare(strict_types=1);
 session_start();

 if (isset($_SESSION['registration_errors']['general'])) {
    echo '<div class="alert alert-danger">';
    echo htmlspecialchars($_SESSION['registration_errors']['general']);
    echo '</div>';

    // Clear after displaying
    unset($_SESSION['registration_errors']['general']);
}


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
   
}





require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Check for errors from previous submission
$errors = $_SESSION['registration_errors'] ?? [];
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['registration_errors']);
unset($_SESSION['old_input']);

// Check for success message
$registrationSuccess = $_SESSION['registration_success'] ?? false;
unset($_SESSION['registration_success']);

try {
    $pdo = Database::getInstance()->getConnection(); // ✅ using your class
    $facultiesStmt = $pdo->prepare("SELECT id as faculty_id, name as faculty_name FROM faculties ORDER BY name");
    $facultiesStmt->execute();
    $faculties = $facultiesStmt->fetchAll(PDO::FETCH_ASSOC);
    usort($faculties, fn($a, $b) => (int)$a['faculty_id'] <=> (int)$b['faculty_id']);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $faculties = [];
}



// Manager registration typically requires admin approval or special access
// Here we'll implement a basic form but in production you might want additional verification
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Registration - University Magazine System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>../assets/css/styles.css">
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
        .btn-submit {
            width: 100%;
            padding: 0.75rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
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
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .form-row {
            display: flex;
            gap: 1rem;
        }
        .form-row .form-group {
            flex: 1;
        }
        @media (max-width: 576px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="registration-form-container">
        <div class="form-header">
            <h1><i class="fas fa-tasks"></i> Manager Registration</h1>
            <p>Request access to manage the university magazine publication process</p>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Note: Manager accounts require administrative approval. 
            Your registration will be reviewed before access is granted.
        </div>
        
        <?php if ($registrationSuccess): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Registration request submitted! You'll be notified once your account is approved.
            </div>
        <?php endif; ?>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="process_registration.php" class="registration-form">
            <input type="hidden" name="role" value="marketing_manager">
            <input type="hidden" name="requires_approval" value="1">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" 
                           value="<?php echo htmlspecialchars($oldInput['first_name'] ?? ''); ?>" required>
                    <?php if (isset($errors['first_name'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" 
                           value="<?php echo htmlspecialchars($oldInput['last_name'] ?? ''); ?>" required>
                    <?php if (isset($errors['last_name'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['last_name']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">University Email</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($oldInput['email'] ?? ''); ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?php echo htmlspecialchars($oldInput['username'] ?? ''); ?>" required>
                <?php if (isset($errors['username'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['username']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="position" class="form-label">Position/Title</label>
                <input type="text" id="position" name="position" class="form-control" 
                       value="<?php echo htmlspecialchars($oldInput['position'] ?? ''); ?>" required>
                <?php if (isset($errors['position'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['position']); ?></div>
                <?php endif; ?>
            </div>

            <label for="faculty_id" class="form-label">Select Faculty</label>
            <?php if (!empty($faculties)): ?>
             <select name="faculty_id" id="faculty_id" class="form-select" required>
            <option value="">-- Select Faculty --</option>
            <?php foreach ($faculties as $faculty): ?>
                <option value="<?= $faculty['faculty_id'] ?>">
                    <?= htmlspecialchars($faculty['faculty_name']) ?>
                </option>
            <?php endforeach; ?>
            </select>
              <?php else: ?>
             <select name="id" id="id" class="form-select" disabled>
             <option value="">No faculties available</option>
              </select>
            <?php endif; ?>
    
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <?php if (isset($errors['password'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="reason" class="form-label">Reason for Access</label>
                <textarea id="reason" name="reason" class="form-control" rows="3" required><?php echo htmlspecialchars($oldInput['reason'] ?? ''); ?></textarea>
                <?php if (isset($errors['reason'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['reason']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
            </div>

            <div class="form-group checkbox-group">
                        <input type="checkbox" id="terms_accepted" name="terms_accepted" <?php echo isset($_POST['terms_accepted']) ? 'checked' : ''; ?> required>
                        <label for="terms_accepted">I agree to the <a href="terms.php">terms and conditions</a></label>
                        <?php if (!empty($errors['terms_accepted'])): ?>
                            <div class="error"><?php echo htmlspecialchars($errors['terms_accepted']); ?></div>
                        <?php endif; ?>
                    </div>

        </form>
       
        
        <div style="text-align: center; margin-top: 1rem;">
            <p>Already have an account? <a href="login.php?role=manager">Login here</a></p>
            <p>Not a manager? <a href="register.php">Select your role</a></p>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>