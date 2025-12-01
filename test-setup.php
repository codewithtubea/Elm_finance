<?php
require_once 'config/database.php';

echo "<h2>🚀 Elm Finance - Complete Setup Test</h2>";
echo "<style>
    body { font-family: Arial; margin: 40px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .success { color: #10b981; font-weight: bold; }
    .warning { color: #f59e0b; font-weight: bold; }
    .error { color: #ef4444; font-weight: bold; }
    .info { background: #dbeafe; padding: 15px; border-radius: 8px; margin: 10px 0; }
</style>";

echo "<div class='container'>";

try {
    $database = new Database();
    
    if ($database->testConnection()) {
        echo "<p class='success'>✅ Connected to database!</p>";
        
        $missing_tables = $database->testElmTables();
        
        if (empty($missing_tables)) {
            echo "<p class='success'>✅ All core tables exist!</p>";
            
            // TEST REFERENCE DATA
            $pdo = $database->getConnection();
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM elm_campus_costs WHERE university = 'Ashesi University'");
            $ref_data_count = $stmt->fetch()['count'];
            
            if ($ref_data_count > 0) {
                echo "<p class='success'>✅ Ashesi reference data: $ref_data_count items</p>";
                
                // Show breakdown by category
                $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM elm_campus_costs WHERE university = 'Ashesi University' GROUP BY category");
                $categories = $stmt->fetchAll();
                
                echo "<div class='info'>";
                echo "<h3>📊 Reference Data Loaded:</h3>";
                foreach ($categories as $cat) {
                    echo "<p>• " . ucfirst($cat['category']) . ": {$cat['count']} items</p>";
                }
                echo "</div>";
                
            } else {
                echo "<p class='warning'>⚠️ No reference data found</p>";
                echo "<p>Run the Ashesi data SQL to enable intelligent features</p>";
            }
            
            echo "<h3 class='success'>🎉 Setup Complete!</h3>";
            echo "<p>Your application is ready for real user data with intelligent campus insights!</p>";
            
        } else {
            echo "<p class='error'>❌ Missing tables: " . implode(', ', $missing_tables) . "</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Database connection failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div>";
?>