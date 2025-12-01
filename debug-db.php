<?php
$host = '169.239.251.102:341';
$dbname = 'webtech_2025A_princess_agyemfra';
$username = 'princess.agyemfra';
$password = 'Tracytubea2565';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "✅ Database connected successfully!";
    
    // Test if we can query
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<br> Tables found: " . implode(', ', $tables);
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>