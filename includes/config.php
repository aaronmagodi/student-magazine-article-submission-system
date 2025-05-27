<?php
declare(strict_types=1);

// ===================================================================
// Security Check & Initial Setup
// ===================================================================
defined('ROOT_PATH') || define('ROOT_PATH', realpath(__DIR__ . '/../'));
defined('MAGAZINE_CONFIG_LOADED') || define('MAGAZINE_CONFIG_LOADED', true);

// ===================================================================
// Environment Configuration
// ===================================================================
define('ENVIRONMENT', 'development'); // 'development', 'staging', 'production'

// Error reporting configuration - fixed ini_set() calls
error_reporting(E_ALL);
ini_set('display_errors', ENVIRONMENT !== 'production' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/logs/error_' . date('Y-m-d') . '.log');

// ===================================================================
// Application Constants
// ===================================================================
define('APP_NAME', 'PlanB University Magazine System');
define('APP_VERSION', '2.0.0');
define('APP_BUILD', '2023.12');
define('COPYRIGHT_YEAR', date('Y'));

// ===================================================================
// Path & URL Configuration
// ===================================================================
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = rtrim($basePath, '/') . '/';

define('ROOT_URL', $protocol . $host . '/dashboard/SchoolProjectMS/');
//define('BASE_URL', 'http://localhost/dashboard/SchoolProjectMS/');

define('BASE_URL', $protocol . $host . $basePath);
define('ASSETS_URL', BASE_URL . 'assets/');
define('ADMIN_EMAIL', 'magazine-admin@university.edu');
define('SUPPORT_EMAIL', 'magazine-support@university.edu');

// ===================================================================
// Time & Date Configuration
// ===================================================================
define('DEFAULT_TIMEZONE', 'America/New_York');
date_default_timezone_set(DEFAULT_TIMEZONE);
define('DATE_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'F j, Y g:i a');

// ===================================================================
// Submission System Configuration
// ===================================================================
define('SUBMISSION_DEADLINE', ENVIRONMENT === 'testing' 
    ? '2030-12-31 23:59:59' 
    : '2024-04-30 23:59:59');

define('FINAL_DEADLINE', ENVIRONMENT === 'testing'
    ? '2031-01-15 23:59:59'
    : '2024-05-15 23:59:59');

define('REVIEW_PERIOD_DAYS', 14);
define('MAX_SUBMISSIONS_PER_STUDENT', 10);
define('MAX_COMMENTS_PER_CONTRIBUTION', 5);

// ===================================================================
// Database Configuration (PlanB)
// ===================================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'planb');
define('DB_USER', 'root');
define('DB_PASS', '');  // Replace with actual password
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');
define('DB_PORT', '3306');  // Ensure this is a string
define('DB_SOCKET', '');
define('DB_ENGINE', 'InnoDB');

$dbOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    PDO::MYSQL_ATTR_FOUND_ROWS   => true
];

// ===================================================================
// Security Configuration
// ===================================================================
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 30);
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
define('PASSWORD_RESET_EXPIRY', 3600); // 1 hour
define('TERMS_VERSION', '2.0');
define('SESSION_TIMEOUT', 86400); // 24 hours
define('PASSWORD_HASH_COST', 12);
define('ENCRYPTION_KEY', 'your-32-byte-secure-key-here'); // Change this!

// Content Security Policy (CSP) Configuration
define('CSP_DEFAULT_SRC', "'self'");
define('CSP_SCRIPT_SRC', "'self' 'unsafe-inline' cdnjs.cloudflare.com");
define('CSP_STYLE_SRC', "'self' 'unsafe-inline' cdnjs.cloudflare.com");
define('CSP_IMG_SRC', "'self' data:");

// ===================================================================
// Email Configuration
// ===================================================================
define('SMTP_HOST', 'smtp.university.edu');
define('SMTP_PORT', 587);
define('SMTP_USER', 'magazine-system@university.edu');
define('SMTP_PASS', 'secure_smtp_password');
define('SMTP_SECURE', 'tls');
define('SMTP_TIMEOUT', 30);
define('SMTP_DEBUG', ENVIRONMENT === 'development');

// Email templates
define('EMAIL_HEADER', ROOT_PATH . '/includes/email_templates/header.html');
define('EMAIL_FOOTER', ROOT_PATH . '/includes/email_templates/footer.html');

// ===================================================================
// File Upload Configuration
// ===================================================================
define('MAX_FILE_SIZE', 25 * 1024 * 1024); // 25MB
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_TOTAL_UPLOAD_SIZE', 100 * 1024 * 1024); // 100MB
define('UPLOAD_DIR', ROOT_PATH . '/uploads/');
define('BACKUP_DIR', ROOT_PATH . '/backups/');

// Allowed MIME types and extensions
define('ALLOWED_DOC_TYPES', [
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/pdf'
]);

define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/tiff',
    'image/svg+xml'
]);

// ===================================================================
// Session Configuration
// ===================================================================
if (session_status() === PHP_SESSION_NONE) {
    $sessionName = 'PlanBMagazineSystem_' . substr(md5(ROOT_PATH), 0, 8);
    $secure = ENVIRONMENT === 'production';
    $httpOnly = true;
    
    session_name($sessionName);
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'secure' => $secure,
        'httponly' => $httpOnly,
        'samesite' => 'Strict'
    ]);

    // Additional session security settings
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', 'secure');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.sid_length', '128');
    ini_set('session.sid_bits_per_character', '6');
    ini_set('session.hash_function', 'sha256');
  //  ini_set('session.gc_maxlifetime', 'SESSION_TIMEOUT');
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);  // Correct


    session_start();
}

// ===================================================================
// Debugging & Development Tools
// ===================================================================
if (ENVIRONMENT === 'development') {
    // Whoops error handler
    if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
        require_once ROOT_PATH . '/vendor/autoload.php';
        
        if (class_exists('Whoops\Run')) {
            $whoops = new Whoops\Run;
            $handler = new Whoops\Handler\PrettyPageHandler;
            
            // Add custom data to error pages
            $handler->addDataTable('Application', [
                'Version' => APP_VERSION,
                'Environment' => ENVIRONMENT,
                'Base URL' => BASE_URL
            ]);
            
            $whoops->pushHandler($handler);
            $whoops->register();
        }
    }

    // Execution time debug
    function shutdownDebug() {
        $executionTime = round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4);
        $memoryUsage = round(memory_get_peak_usage(true) / (1024 * 1024), 2);
        echo "<!-- Page generated in {$executionTime}s using {$memoryUsage}MB -->";
    }
    register_shutdown_function('shutdownDebug');
}

// ===================================================================
// Custom Error Handler
// ===================================================================
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorTypes = [
        E_ERROR             => 'Error',
        E_WARNING           => 'Warning',
        E_PARSE             => 'Parse Error',
        E_NOTICE            => 'Notice',
        E_CORE_ERROR        => 'Core Error',
        E_CORE_WARNING      => 'Core Warning',
        E_COMPILE_ERROR     => 'Compile Error',
        E_COMPILE_WARNING   => 'Compile Warning',
        E_USER_ERROR        => 'User Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',
        E_STRICT            => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED        => 'Deprecated',
        E_USER_DEPRECATED   => 'User Deprecated'
    ];
    
    $errorType = $errorTypes[$errno] ?? 'Unknown Error';
    $message = "[$errorType] $errstr in $errfile on line $errline";
    
    error_log($message);
    
    if (ENVIRONMENT === 'development' && ini_get('display_errors')) {
        echo "<div class='error-alert'><strong>$errorType:</strong> $errstr in <em>$errfile</em> on line $errline</div>";
    }
    
    return true;
});

// ===================================================================
// Autoloader Configuration (if not using Composer)
// ===================================================================
function autoloadClasses($class) {
    $paths = [
        ROOT_PATH . '/classes/' . $class . '.php',
        ROOT_PATH . '/classes/' . strtolower($class) . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return true;
        }
    }

    return false;
}

spl_autoload_register('autoloadClasses');

// ===================================================================
// Maintenance Mode Configuration
// ===================================================================
define('MAINTENANCE_MODE', false);
if (MAINTENANCE_MODE) {
    header("HTTP/1.1 503 Service Unavailable");
    echo "<h1>We are currently undergoing maintenance. Please try again later.</h1>";
    exit;
}
?>
