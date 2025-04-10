<?php
// Prevent direct access
defined('ROOT_PATH') || define('ROOT_PATH', realpath(dirname(__FILE__) . '/../'));

// Prevent multiple inclusions and duplicate definitions
if (!defined('MAGAZINE_CONFIG_LOADED')) {
    define('MAGAZINE_CONFIG_LOADED', true);

    // ========================
    // Environment Configuration
    // ========================
    define('ENVIRONMENT', 'development'); // 'development', 'testing', or 'production'

    error_reporting(E_ALL);
    ini_set('display_errors', ENVIRONMENT !== 'production');
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');

    // ========================
    // Application Configuration
    // ========================
    define('APP_NAME', 'University Magazine System');
    define('APP_VERSION', '1.1.0');

    // Base URLs and Paths
    define('BASE_PATH', realpath(__DIR__ . '/..'));
    define('BASE_URL', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
        ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/magazine-system/'); // Trailing slash added
    define('ASSETS_URL', BASE_URL . 'assets');
    define('ADMIN_EMAIL', 'admin@university.edu');

    // ========================
    // Timezone
    // ========================
    date_default_timezone_set('America/New_York');

    // ========================
    // Submission System Configuration
    // ========================
    if (ENVIRONMENT === 'testing') {
        define('SUBMISSION_DEADLINE', '2030-12-31 23:59:59');
        define('FINAL_DEADLINE', '2031-12-31 23:59:59');
    } else {
        define('SUBMISSION_DEADLINE', '2023-12-01 23:59:59');
        define('FINAL_DEADLINE', '2023-12-15 23:59:59');
    }

    define('REVIEW_PERIOD_DAYS', 14);
    define('MAX_SUBMISSIONS_PER_STUDENT', 5);
    define('MIN_PASSWORD_LENGTH', 12);

    // ========================
    // Database Configuration
    // ========================
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'magazine_system');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_COLLATION', 'utf8mb4_unicode_ci');
    define('DB_PREFIX', 'mag_');
    define('DB_PORT', '3306');
    define('DB_OPTIONS', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // ========================
    // Security Configuration
    // ========================
    define('MAX_LOGIN_ATTEMPTS', 5);
    define('LOCKOUT_TIME', 15 * 60);
    define('CSRF_TOKEN_LIFETIME', 3600);
    define('PASSWORD_RESET_EXPIRY', 3600);
    define('TERMS_VERSION', '1.1');
    define('SESSION_TIMEOUT', 86400);
    define('PASSWORD_HASH_COST', 12);
    define('ENCRYPTION_KEY', 'your-32-character-encryption-key-here');

    // ========================
    // Email Configuration
    // ========================
    define('EMAIL_FROM', 'no-reply@youruniversity.edu');
    define('EMAIL_FROM_NAME', 'University Magazine System');
    define('EMAIL_REPLY_TO', 'magazine-support@youruniversity.edu');
    define('COORDINATOR_NOTIFICATION_EMAIL', 'magazine-coordinators@youruniversity.edu');

    define('SMTP_HOST', 'smtp.youruniversity.edu');
    define('SMTP_PORT', 587);
    define('SMTP_USER', 'noreply@youruniversity.edu');
    define('SMTP_PASS', 'your_secure_password');
    define('SMTP_SECURE', 'tls');
    define('SMTP_TIMEOUT', 30);

    // ========================
    // File Upload Configuration
    // ========================
    define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20MB
    define('MAX_TOTAL_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB
    define('UPLOAD_DIR', BASE_PATH . '/assets/uploads/');
    define('UPLOAD_BACKUP_DIR', BASE_PATH . '/assets/uploads/backups/');
    define('ALLOWED_FILE_TYPES', [
        'images' => [
            'jpeg' => ['image/jpeg', '.jpg', '.jpeg'],
            'png'  => ['image/png', '.png'],
            'tiff' => ['image/tiff', '.tiff']
        ],
        'documents' => [
            'pdf'  => ['application/pdf', '.pdf'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', '.docx']
        ]
    ]);

    // ========================
    // Session Configuration
    // ========================
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'secure' => ENVIRONMENT === 'production',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', ENVIRONMENT === 'production');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.sid_length', 128);
        ini_set('session.sid_bits_per_character', 6);
        ini_set('session.hash_function', 'sha256');

        session_start();
    }

    // ========================
    // Debugging Configuration
    // ========================
    if (ENVIRONMENT === 'development') {
        if (class_exists('Whoops\Run')) {
            $whoops = new Whoops\Run;
            $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler);
            $whoops->register();
        }

        function shutdown_debug() {
            $executionTime = microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
            echo "\n<!-- Page generated in $executionTime seconds -->";
        }

        register_shutdown_function('shutdown_debug');
    }
}
