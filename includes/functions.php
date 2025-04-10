<?php
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function redirect($url, $statusCode = 303) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

function formatDate($dateString, $format = 'F j, Y') {
    $date = new DateTime($dateString);
    return $date->format($format);
}

function getFileIcon($mimeType) {
    $icons = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
        'image/jpeg' => 'fa-file-image',
        'image/png' => 'fa-file-image',
        'image/tiff' => 'fa-file-image',
        'default' => 'fa-file'
    ];
    
    return $icons[$mimeType] ?? $icons['default'];
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getSetting($key, $default = null) {
    static $settings;

    if (!isset($settings)) {
        global $db;
        $stmt = $db->query("SELECT * FROM settings LIMIT 1");
        $settings = $stmt->fetch();
    }

    return $settings[$key] ?? $default;
}
?>
