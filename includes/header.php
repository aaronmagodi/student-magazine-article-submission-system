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
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/<?= basename($_SERVER['PHP_SELF'], '.php') ?>.css">
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
        <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            $user_role = $_SESSION['user_role'];
            $base = BASE_URL;
        ?>

        <?php if ($user_role === 'student'): ?>
            <?php if (in_array($current_page, ['submit.php', 'view_contribution.php'])): ?>
                <a href="<?= $base ?>../student/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <?php else: ?>
                <a href="<?= $base ?>../index.php"><i class="fas fa-home"></i> Home</a>
            <?php endif; ?>

        <?php elseif ($user_role === 'marketing_coordinator'): ?>
            <?php if ($current_page === 'view_contribution.php'): ?>
                <a href="<?= $base ?>dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <?php else: ?>
                <a href="<?= $base ?>../index.php"><i class="fas fa-home"></i> Home</a>
                <a href="<?= $base ?>dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <?php endif; ?>

        <?php elseif ($user_role === 'marketing_manager'): ?>
            <?php if ($current_page === 'view_contribution.php'): ?>
                <a href="<?= $base ?>dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <?php else: ?>
                <a href="<?= $base ?>../index.php"><i class="fas fa-home"></i> Home</a>
                <a href="<?= $base ?>view_contribution.php"><i class="fas fa-eye"></i> View Contributions</a>
                <a href="<?= $base ?>report_contribution.php"><i class="fas fa-chart-bar"></i>Reports</a>
            <?php endif; ?>

        <?php elseif ($user_role === 'admin'): ?>
            <?php if (in_array($current_page, ['view_contribution.php', 'reports.php'])): ?>
                <a href="<?= $base ?>dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <?php else: ?>
                <a href="<?= $base ?>../index.php"><i class="fas fa-home"></i> Home</a>
                <a href="<?= $base ?>../admin/users.php"><i class="fas fa-users"></i> Users</a>
                <a href="<?= $base ?>../admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Logout button always -->
        <a href="<?= $base ?>../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>

    <?php else: ?>
        <!-- If not logged in -->
        <a href="<?= $base ?>../login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        <a href="<?= $base ?>../register.php"><i class="fas fa-user-plus"></i> Register</a>
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