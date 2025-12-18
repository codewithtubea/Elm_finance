<?php
class DatabaseConfig {

    const DB_HOST = 'localhost';
    const DB_NAME = 'elm_finance_local';  
    const DB_USER = 'princess.agyemfra';              
    const DB_PASS = 'Tracytubea2565';                   
    const DB_CHARSET = 'utf8mb4';
}

class Database {
    private $pdo;
    
    public function __construct() {
        $dsn = "mysql:host=" . DatabaseConfig::DB_HOST . ";dbname=" . DatabaseConfig::DB_NAME . ";charset=" . DatabaseConfig::DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, DatabaseConfig::DB_USER, DatabaseConfig::DB_PASS, $options);
        } catch (PDOException $e) {
            throw new Exception("Local database connection error: " . $e->getMessage());
        }
    }

    public function getPDO() {
        return $this->pdo;
    }
    
    public function getConnection() { return $this->pdo; }
    
    public function testConnection() {
        try {
            $stmt = $this->pdo->query("SELECT 1");
            return $stmt->fetchColumn() === 1;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function testElmTables() {
        $tables = ['elm_users', 'elm_expenses', 'elm_budgets'];
        $missing_tables = [];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() === 0) {
                    $missing_tables[] = $table;
                }
            } catch (PDOException $e) {
                $missing_tables[] = $table;
            }
        }
        
        return $missing_tables;
    }
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    $pdo = null;
}
?>