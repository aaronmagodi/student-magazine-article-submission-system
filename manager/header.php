<?php
$currentTheme = $_SESSION['theme'] ?? 'light';
$pageTitle = $pageTitle ?? 'University Magazine System';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $currentTheme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="<assets/css/<?= basename($_SERVER['PHP_SELF'], '.php') ?>.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container nav-container">
            <a href="<?= BASE_URL ?>" class="logo">
                <i class="fas fa-book-open"></i> University Magazine
            </a>
            
            <nav class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] === 'student'): ?>
                        <a href="<?= BASE_URL ?>/student/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        <a href="<?= BASE_URL ?>/student/submit.php"><i class="fas fa-upload"></i> Submit</a>
                    <?php elseif ($_SESSION['user_role'] === 'coordinator'): ?>
                        <a href="<?= BASE_URL ?>/coordinator/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        <a href="<?= BASE_URL ?>/coordinator/submissions.php"><i class="fas fa-file-alt"></i> Submissions</a>
                    <?php elseif (in_array($_SESSION['user_role'], ['admin', 'manager'])): ?>
                        <a href="<?= BASE_URL ?>/admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        <a href="<?= BASE_URL ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="<?= BASE_URL ?>/admin/users.php"><i class="fas fa-users"></i> Users</a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="<?= BASE_URL ?>/register.php"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </nav>
            
            <div class="theme-switcher">
                <button class="theme-btn light" onclick="setTheme('light')" aria-label="Light theme">
                    <i class="fas fa-sun"></i>
                </button>
                <button class="theme-btn dark" onclick="setTheme('dark')" aria-label="Dark theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="container">
        <?php if (!empty($successMessage)): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> <?= $successMessage ?>
                <button class="close-alert" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> <?= $errorMessage ?>
                <button class="close-alert" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>