<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$errors = [];
$success = false;



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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Registration - University Magazine System</title>
    <link href="<?php echo BASE_URL; ?>assets/css/styles.css" rel="stylesheet">
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
        <div class="registration-form">
            <h1><i class="fas fa-user-graduate"></i> Coordinator Registration</h1>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    Registration successful! <a href="login.php">Login here</a>.
                </div>
            <?php else: ?>
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($errors['general']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="process_registration.php" class="registration-form" id="registrationForm">
                <input type="hidden" name="role" value="marketing_coordinator">

                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        <?php if (!empty($errors['username'])): ?>
                            <div class="error"><?php echo htmlspecialchars($errors['username']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <?php if (!empty($errors['email'])): ?>
                            <div class="error"><?php echo htmlspecialchars($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="first_name"><i class="fas fa-id-card"></i> First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                        <?php if (!empty($errors['first_name'])): ?>
                            <div class="error"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="last_name"><i class="fas fa-id-card"></i> Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                        <?php if (!empty($errors['last_name'])): ?>
                            <div class="error"><?php echo htmlspecialchars($errors['last_name']); ?></div>
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
                </div>
 
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="password" name="password" required>
                        <?php if (!empty($errors['password'])): ?>
                            <div class="error"><?php echo htmlspecialchars($errors['password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <?php if (!empty($errors['confirm_password'])): ?>
                            <div class="error"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="terms_accepted" name="terms_accepted" <?php echo isset($_POST['terms_accepted']) ? 'checked' : ''; ?>>
                        <label for="terms_accepted">I agree to the <a href="terms.php">terms and conditions</a></label>
                        <?php if (!empty($errors['terms_accepted'])): ?>
                            <div class="error"><?php echo htmlspecialchars($errors['terms_accepted']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-submit">
                         <i class="fas fa-paper-plane"></i> Submit Request
                         </button>
                    </div>

                   
            <div style="text-align: center; margin-top: 1rem;">
            <p>Already have an account? <a href="login.php?role=student">Login here</a></p>
            <p>Not a Student? <a href="register.php">Select your role</a></p>
             </div>
    </form>
     <?php endif; ?>
    </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
