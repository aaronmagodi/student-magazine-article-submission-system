<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class Auth {
    private PDO $pdo;
    private int $maxLoginAttempts = 5;
    private int $lockoutDuration = 1800; // 30 minutes

    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
        $this->initSession();
    }

    private function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('SchoolProjectAuth');
            session_set_cookie_params([
                'lifetime' => SESSION_TIMEOUT,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
                'secure' => ENVIRONMENT === 'production',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }

        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }

    public function getUserRole(): ?string {
        return $_SESSION['user_role'] ?? null;
    }

    public function updatePassword(int $userId, string $newPassword): void {
        $stmt = $this->pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt->execute([
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $userId
        ]);
    }

    public function updateLastLogin(int $userId): void {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $stmt->execute(['id' => $userId]);
    }

    private function isLockedOut(string $email): bool {
        $stmt = $this->pdo->prepare("SELECT failed_attempts, last_failed_at FROM login_attempts WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $failedAttempts = (int) $result['failed_attempts'];
            $lastFailedAt = strtotime($result['last_failed_at']);
            $elapsed = time() - $lastFailedAt;

            if ($failedAttempts >= $this->maxLoginAttempts && $elapsed < $this->lockoutDuration) {
                return true;
            }
        }

        return false;
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }
    

    private function logFailedAttempt(string $email): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO login_attempts (email, failed_attempts, last_failed_at)
            VALUES (:email, 1, NOW())
            ON DUPLICATE KEY UPDATE 
                failed_attempts = failed_attempts + 1,
                last_failed_at = NOW()
        ");
        $stmt->execute(['email' => $email]);
    }

    private function resetFailedAttempts(string $email): void {
        $stmt = $this->pdo->prepare("DELETE FROM login_attempts WHERE email = :email");
        $stmt->execute(['email' => $email]);
    }

    public function login(string $email, string $password): bool|string {
        if ($this->isLockedOut($email)) {
            return "Account temporarily locked. Please try again later.";
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, f.name AS faculty_name 
                FROM users u
                LEFT JOIN faculties f ON u.faculty_id = f.id
                WHERE u.email = :email
                LIMIT 1
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                $this->logFailedAttempt($email);
                return "Invalid email or password.";
            }

            // Set session values
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'] ?? '';
            $_SESSION['user_role'] = $user['role'] ?? '';
            $_SESSION['faculty_name'] = $user['faculty_name'] ?? '';
            $_SESSION['faculty_id'] =(int) $user['faculty_id'];

            $this->resetFailedAttempts($email);
            $this->updateLastLogin((int) $user['id']);

            // Associative array for role-based redirection
            $roleRedirects = [
                'admin' => 'admin/dashboard.php',
                'marketing_coordinator' => 'coordinator/dashboard.php',
                'student' => 'student/dashboard.php',
                'marketing_manager' => 'manager/dashboard.php'
            ];

            $role = strtolower($user['role'] ?? '');

            // Check if the role exists and redirect
            if (array_key_exists($role, $roleRedirects)) {
                header("Location: " . $roleRedirects[$role]);
                exit;
            } else {
                return "Unauthorized user role.";
            }

    

        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return "An error occurred during login. Please try again.";
        }
    }

    public function redirectIfNotAuthorized(array $allowedRoles = []): void {
        $userRole = $_SESSION['user_role'] ?? null;

        if (!$userRole || (!empty($allowedRoles) && !in_array($userRole, $allowedRoles))) {
            // Display Unauthorized Access message
            header('HTTP/1.1 403 Forbidden');
            echo '<h1>Unauthorized Access</h1>';
            echo '<p>You do not have permission to access this page.</p>';
            exit; // Stop further execution
        }
    }
}
?>
