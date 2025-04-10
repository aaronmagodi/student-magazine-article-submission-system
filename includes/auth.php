<?php
declare(strict_types=1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Include configuration for DB constants and BASE_URL
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class Auth {
    private $pdo;

    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
    }

    /**
     * Login a user with email, password, and role.
     */
    // In your Auth class (includes/auth.php), ensure the login method handles students properly:
public function login($email, $password, $role) {
    $db = new Database();
    $conn = $db->getConnection();
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email AND role = :role");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return "No account found with these credentials";
        }
        
        if (!password_verify($password, $user['password'])) {
            return "Invalid password";
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['faculty_id'] = $user['faculty_id'];
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        return true;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return "Database error occurred";
    }
}

    /**
     * Update last login timestamp for user.
     */
    private function updateLastLogin(int $userId): void {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET last_login = NOW() 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Last login update error: " . $e->getMessage());
        }
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    public function getUserRole(): ?string {
        return $_SESSION['role'] ?? null;
    }

    public function getUserFaculty(): ?string {
        return $_SESSION['faculty_id'] ?? null;
    }

    public function getLastLogin(): ?string {
        return $_SESSION['last_login'] ?? null;
    }

    /**
     * Redirect user to login if not authenticated.
     */
    public function redirectIfNotLoggedIn(): void {
        if (!$this->isLoggedIn()) {
            header("Location: " . BASE_URL . "login.php");
            exit();
        }
    }

    /**
     * Redirect user if their role is not authorized.
     */
    public function redirectIfNotAuthorized(array $allowedRoles): void {
        $this->redirectIfNotLoggedIn();
        if (!in_array($this->getUserRole(), $allowedRoles)) {
            header("Location: " . BASE_URL . "unauthorized.php");
            exit();
        }
    }

    /**
     * Logout the user and destroy session.
     */
    public function logout(): void {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}
