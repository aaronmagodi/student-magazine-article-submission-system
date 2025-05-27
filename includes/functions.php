<?php
declare(strict_types=1);

/**
 * Sanitizes input data to prevent XSS attacks
 * @param mixed $data Input data to sanitize
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validates email format without domain restrictions
 * @param string $email Email to validate
 * @return bool|string Returns validated email or false if invalid
 */
function validateEmail(string $email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/**
 * Redirects to specified URL with optional HTTP status code
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code (default: 303 See Other)
 */
function redirect(string $url, int $statusCode = 303): void {
    if (!headers_sent()) {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
    
    // Fallback JavaScript redirect if headers already sent
    echo '<script>window.location.href="' . $url . '";</script>';
    exit;
}

/**
 * Formats date string according to specified format
 * @param string $dateString Input date string
 * @param string $format Output format (default: 'F j, Y')
 * @return string Formatted date
 */
function formatDate(string $dateString, string $format = 'F j, Y'): string {
    try {
        $date = new DateTime($dateString);
        return $date->format($format);
    } catch (Exception $e) {
        error_log("Date formatting error: " . $e->getMessage());
        return 'Invalid date';
    }
}

/**
 * Gets appropriate Font Awesome icon for file type
 * @param string $mimeType File MIME type
 * @return string Font Awesome icon class
 */
function getFileIcon(string $mimeType): string {
    $icons = [
        'application/msword' => 'fa-file-word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
        'application/pdf' => 'fa-file-pdf',
        'image/jpeg' => 'fa-file-image',
        'image/png' => 'fa-file-image',
        'image/gif' => 'fa-file-image',
        'image/tiff' => 'fa-file-image',
        'image/svg+xml' => 'fa-file-image',
        'text/plain' => 'fa-file-alt',
        'text/csv' => 'fa-file-csv',
        'application/zip' => 'fa-file-archive',
        'default' => 'fa-file'
    ];
    
    return $icons[strtolower($mimeType)] ?? $icons['default'];
}

/**
 * Generates and stores CSRF token in session
 * @return string Generated CSRF token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        // Generate timestamp for token expiration (1 hour)
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies CSRF token with timing attack protection
 * @param string $token Token to verify
 * @param int $timeout Token timeout in seconds (default: 3600 = 1 hour)
 * @return bool True if token is valid
 */
function verifyCsrfToken(string $token, int $timeout = 3600): bool {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check token expiration
    if ((time() - $_SESSION['csrf_token_time']) > $timeout) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Gets system setting from database with caching
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value or default
 */
function getSetting(string $key, $default = null) {
    static $settings = null;

    if ($settings === null) {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->query("SELECT * FROM system_settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            error_log("Settings error: " . $e->getMessage());
            $settings = [];
        }
    }

    return $settings[$key] ?? $default;
}

/**
 * Checks if current date is past submission deadline
 * @param int $academicYearId Optional academic year ID
 * @return bool True if submission period is closed
 */
function isSubmissionClosed(?int $academicYearId = null): bool {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT submission_deadline FROM academic_years ";
        $params = [];
        
        if ($academicYearId) {
            $query .= "WHERE id = ?";
            $params[] = $academicYearId;
        } else {
            $query .= "WHERE is_current = TRUE";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $deadline = $stmt->fetchColumn();
        
        return $deadline ? (new DateTime()) > new DateTime($deadline) : true;
    } catch (Exception $e) {
        error_log("Submission check error: " . $e->getMessage());
        return true; // Default to closed if error occurs
    }
}

/**
 * Logs system activity
 * @param string $action Description of the action
 * @param int|null $userId Optional user ID
 * @param array $data Additional data to log
 */
function logActivity(string $action, ?int $userId = null, array $data = []): void {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO system_logs 
            (user_id, action, data, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $action,
            json_encode($data),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

/**
 * Sends notification email (placeholder implementation)
 * @param string $recipientEmail Email address
 * @param string $subject Email subject
 * @param string $message Email body
 * @return bool True if email was sent successfully
 */
function sendNotificationEmail(string $recipientEmail, string $subject, string $message): bool {
    // In a real implementation, you would use PHPMailer or similar
    $headers = [
        'From' => getSetting('system_email', 'noreply@university.edu'),
        'Reply-To' => getSetting('admin_email', 'admin@university.edu'),
        'X-Mailer' => 'PHP/' . phpversion(),
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    // This is a placeholder - implement proper email sending in production
    error_log("Email would be sent to: $recipientEmail\nSubject: $subject\nMessage: $message");
    
    return true;
}