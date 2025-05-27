<?php
declare(strict_types=1);

class Database {
    private string $host;
    private string $name;
    private string $user;
    private string $pass;
    private string $port;
    private string $charset;
    private ?PDO $pdo = null;

    private static ?self $instance = null;

    public function __construct(
        string $host = 'localhost',
        string $name = 'planb',
        string $user = 'root',
        string $pass = '',
        string $port = '3306',
        string $charset = 'utf8mb4'
    ) {
        $this->host    = $host;
        $this->name    = $name;
        $this->user    = $user;
        $this->pass    = $pass;
        $this->port    = defined('DB_PORT') ? (string) DB_PORT : $port;
        $this->charset = defined('DB_CHARSET') ? (string) DB_CHARSET : $charset;

        $this->connect();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->name};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE " . 
                                            (defined('DB_COLLATION') ? DB_COLLATION : 'utf8mb4_unicode_ci'),
            PDO::MYSQL_ATTR_FOUND_ROWS   => true
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $errorMessage  = "Database connection failed: " . $e->getMessage() . "\n";
            $errorMessage .= "Attempted to connect with:\n";
            $errorMessage .= "Host: {$this->host}\n";
            $errorMessage .= "Database: {$this->name}\n";
            $errorMessage .= "User: {$this->user}\n";

            error_log($errorMessage);

            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                die("<h2>Database Connection Error</h2><pre>{$errorMessage}</pre>");
            } else {
                die("Database connection error. Please try again later.");
            }
        }
    }

    public function getConnection(): PDO {
        if ($this->pdo === null || !$this->testConnection()) {
            $this->connect();
        }
        return $this->pdo;
    }

    private function testConnection(): bool {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function query(string $sql, array $params = []): array {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
