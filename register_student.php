<?php
// Initialize session and includes
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$faculties = [];
$errors = [];

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Check if faculties table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'faculties'")->fetch();
    if (!$tableCheck) {
        throw new RuntimeException("Faculties table does not exist");
    }

    $stmt = $conn->query("SELECT id, name, code FROM faculties ORDER BY name");
    $faculties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($faculties)) {
        error_log("No faculties found in database");
        $errors['faculty'] = "No faculties available for registration";
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errors['database'] = "System temporarily unavailable. Please try again later.";

    // Remove this debug output in production
    echo "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Initialize form data
$formData = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'username' => '',
    'faculty_id' => '',
    'password' => '',
    'confirm_password' => ''
];

// Check for errors from previous submission
if (isset($_SESSION['registration_errors'])) {
    $errors = $_SESSION['registration_errors'];
    $formData = $_SESSION['old_input'] ?? $formData;
    unset($_SESSION['registration_errors'], $_SESSION['old_input']);
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - University Magazine System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        <?php include __DIR__ . '/assets/css/registration.css'; ?>
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="main-content">
    <div class="registration-container">
        <div class="form-header">
            <h1><i class="fas fa-user-graduate"></i> Student Registration</h1>
            <p>Create your account to submit contributions to the university magazine</p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="process_registration.php" class="registration-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="role" value="student">

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control"
                           value="<?php echo htmlspecialchars($formData['first_name']); ?>" required>
                    <?php if (!empty($errors['first_name'])): ?>
                        <div class="error-message"><i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($errors['first_name']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control"
                           value="<?php echo htmlspecialchars($formData['last_name']); ?>" required>
                    <?php if (!empty($errors['last_name'])): ?>
                        <div class="error-message"><i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($errors['last_name']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">University Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($formData['email']); ?>"
                       placeholder="student@university.edu" required>
                <?php if (!empty($errors['email'])): ?>
                    <div class="error-message"><i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['email']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control"
                       value="<?php echo htmlspecialchars($formData['username']); ?>"
                       placeholder="Choose a unique username" required>
                <?php if (!empty($errors['username'])): ?>
                    <div class="error-message"><i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['username']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="faculty_id" class="form-label">Faculty</label>
                <select id="faculty_id" name="faculty_id" class="form-control form-select" required>
                    <option value="">Select your faculty</option>
                    <?php if (!empty($faculties)): ?>
                        <?php foreach ($faculties as $faculty): ?>
                            <option value="<?= htmlspecialchars($faculty['id']) ?>"
                                <?= ($formData['faculty_id'] == $faculty['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($faculty['name']) ?> (<?= htmlspecialchars($faculty['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>-- No faculties available --</option>
                    <?php endif; ?>
                </select>
                <?php if (!empty($errors['faculty'])): ?>
                    <div class="error-message"><i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($errors['faculty']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <div class="password-hints">
                    Password should be at least 8 characters long and include an uppercase letter, lowercase letter, and a number.
                </div>
                <?php if (!empty($errors['password'])): ?>
                    <div class="error-message"><i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['password']); ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                <?php if (!empty($errors['confirm_password'])): ?>
                    <div class="error-message"><i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-submit"
                    <?= empty($faculties) ? 'disabled' : '' ?>>Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
