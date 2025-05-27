<?php
require 'config.php';

try {
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "Successfully connected to database!";
    
    // Test query
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    echo "<pre>Tables: ".print_r($tables, true)."</pre>";
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}