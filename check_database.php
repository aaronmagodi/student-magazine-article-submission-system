<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "=== Database Connection Test ===\n";
    echo "Connected to: " . $conn->query("SELECT DATABASE()")->fetchColumn() . "\n\n";
    
    echo "=== Faculties Table Check ===\n";
    $tableExists = $conn->query("SHOW TABLES LIKE 'faculties'")->rowCount() > 0;
    echo "Table exists: " . ($tableExists ? "YES" : "NO") . "\n";
    
    if ($tableExists) {
        $count = $conn->query("SELECT COUNT(*) FROM faculties")->fetchColumn();
        echo "Records found: $count\n";
        
        $faculties = $conn->query("SELECT * FROM faculties LIMIT 5")->fetchAll();
        echo "\nSample data:\n";
        print_r($faculties);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}