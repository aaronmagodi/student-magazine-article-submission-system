<body?php
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
            <a href="../login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="../register.php"><i class="fas fa-user-plus"></i> Register</a>
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
</body
>
  