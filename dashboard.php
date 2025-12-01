<?php
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/auth.php';

// Configure session security FIRST - THIS STARTS THE SESSION
Security::configureSession();

// Check if user is logged in
$auth = new Auth($pdo);
$auth->requireAuth(); // This redirects to login if not authenticated

// Get current user data
$currentUser = $auth->getUser();

// Debug: Check if user data is loaded
if (!$currentUser || !isset($currentUser['id'])) {
    error_log("User authentication failed - redirecting to login");
    header('Location: login.php');
    exit;
}

// Get current month and year for filtering
$currentMonth = date('Y-m');

// Get user's expenses for the current month
try {
    $stmt = $pdo->prepare("
        SELECT category, SUM(amount) as total 
        FROM elm_expenses 
        WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?
        GROUP BY category
    ");
    $stmt->execute([$currentUser['id'], $currentMonth]);
    $monthlyExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array for easy access
    $expensesByCategory = [];
    $totalSpent = 0;
    foreach ($monthlyExpenses as $expense) {
        $expensesByCategory[$expense['category']] = $expense['total'];
        $totalSpent += $expense['total'];
    }
    
} catch (Exception $e) {
    error_log("Dashboard expense error: " . $e->getMessage());
    $expensesByCategory = [];
    $totalSpent = 0;
}

// Get recent transactions
try {
    $stmt = $pdo->prepare("
        SELECT description, amount, category, expense_date 
        FROM elm_expenses 
        WHERE user_id = ? 
        ORDER BY expense_date DESC, created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$currentUser['id']]);
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Dashboard transactions error: " . $e->getMessage());
    $recentTransactions = [];
}

// Get user's budgets
try {
    $stmt = $pdo->prepare("
        SELECT category, amount 
        FROM elm_budgets 
        WHERE user_id = ? AND month_year = ?
    ");
    $stmt->execute([$currentUser['id'], $currentMonth . '-01']);
    $userBudgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array
    $budgetsByCategory = [];
    $totalBudget = 0;
    foreach ($userBudgets as $budget) {
        $budgetsByCategory[$budget['category']] = $budget['amount'];
        $totalBudget += $budget['amount'];
    }
    
} catch (Exception $e) {
    error_log("Dashboard budget error: " . $e->getMessage());
    $budgetsByCategory = [];
    $totalBudget = 0;
}

// Calculate remaining budget
$remainingBudget = $totalBudget - $totalSpent;
$daysInMonth = date('t');
$currentDay = date('j');
$monthProgress = ($currentDay / $daysInMonth) * 100;

// Campus average data (from our reference table)
$campusAverages = [
    'food' => 220,
    'transport' => 120, 
    'essentials' => 200,
    'entertainment' => 80,
    'other' => 100
];

// Calculate insights
$insights = [];
foreach ($expensesByCategory as $category => $spent) {
    $campusAvg = $campusAverages[$category] ?? 0;
    $difference = $campusAvg - $spent;
    
    if ($difference > 0) {
        $insights[] = [
            'category' => $category,
            'message' => "You're spending ₵$difference less than campus average on " . $category,
            'type' => 'positive'
        ];
    } elseif ($difference < 0) {
        $insights[] = [
            'category' => $category,
            'message' => "You're spending ₵" . abs($difference) . " more than campus average on " . $category,
            'type' => 'warning'
        ];
    }
}

// Add budget alerts
foreach ($budgetsByCategory as $category => $budget) {
    $spent = $expensesByCategory[$category] ?? 0;
    $percentage = ($spent / $budget) * 100;
    
    if ($percentage >= 90) {
        $insights[] = [
            'category' => $category,
            'message' => ucfirst($category) . " budget is " . round($percentage) . "% used",
            'type' => 'alert'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Elm Finance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-cyan: #00ffff;
            --neon-purple: #b967ff;
            --dark-bg: #0a0a0a;
            --darker-bg: #050505;
            --card-bg: rgba(15, 15, 15, 0.8);
            --card-border: rgba(0, 255, 136, 0.2);
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --success: #00ff88;
            --warning: #ffb800;
            --alert: #ff4444;
            --gradient-1: linear-gradient(135deg, var(--neon-green), var(--neon-cyan));
            --gradient-2: linear-gradient(135deg, var(--neon-purple), var(--neon-cyan));
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--darker-bg);
            color: var(--text-primary);
            min-height: 100vh;
        }
        
        /* Navigation */
        .navbar {
            background: rgba(10, 10, 10, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-links a:hover {
            color: var(--neon-green);
        }
        
        .user-welcome {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        /* Main Layout */
        .dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            display: grid;
            gap: 2rem;
        }
        
        /* Hero Section - Financial Snapshot */
        .hero-section {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 0 50px rgba(0, 255, 136, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 136, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .hero-section:hover::before {
            left: 100%;
        }
        
        .welcome-message {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff, var(--neon-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .financial-snapshot {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .snapshot-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .snapshot-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .snapshot-value.positive {
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .snapshot-value.warning {
            color: var(--warning);
        }
        
        .snapshot-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .month-progress {
            margin-top: 1.5rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--gradient-1);
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }
        
        .progress-text {
            display: flex;
            justify-content: space-between;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .category-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 255, 136, 0.1);
        }
        
        .category-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .category-icon {
            font-size: 1.5rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .category-name {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .category-amount {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .category-budget {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        /* Recent Transactions */
        .recent-transactions {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 1.5rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .transaction-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .transaction-info {
            display: flex;
            flex-direction: column;
        }
        
        .transaction-desc {
            font-weight: 500;
        }
        
        .transaction-category {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .transaction-amount {
            font-weight: 600;
        }
        
        .transaction-date {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        /* Insights */
        .insights-section {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 1.5rem;
        }
        
        .insight-item {
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-radius: 12px;
            border-left: 4px solid;
        }
        
        .insight-item.positive {
            background: rgba(0, 255, 136, 0.1);
            border-left-color: var(--success);
        }
        
        .insight-item.warning {
            background: rgba(255, 184, 0, 0.1);
            border-left-color: var(--warning);
        }
        
        .insight-item.alert {
            background: rgba(255, 68, 68, 0.1);
            border-left-color: var(--alert);
        }
        
        .insight-message {
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard {
                padding: 1rem;
                gap: 1rem;
            }
            
            .hero-section {
                padding: 1.5rem;
            }
            
            .welcome-message {
                font-size: 1.5rem;
            }
            
            .financial-snapshot {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">Elm</div>
            <div class="nav-links">
                <a href="dashboard.php" style="color: var(--neon-green);">Dashboard</a>
                <a href="expenses.php">Expenses</a>
                <a href="budget.php">Budget</a>
                <a href="insights.php">Insights</a>
                <a href="profile.php">Profile</a>
                <span class="user-welcome">Hi, <?php echo htmlspecialchars($currentUser['first_name'] ?? $currentUser['username']); ?></span>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Dashboard -->
    <div class="dashboard">
        <!-- Hero Section -->
        <section class="hero-section">
            <h1 class="welcome-message">Welcome back, <?php echo htmlspecialchars($currentUser['first_name'] ?? $currentUser['username']); ?>! 👋</h1>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">Here's your financial overview for <?php echo date('F Y'); ?></p>
            
            <!-- Financial Snapshot -->
            <div class="financial-snapshot">
                <div class="snapshot-card">
                    <div class="snapshot-value">₵<?php echo number_format($totalBudget, 2); ?></div>
                    <div class="snapshot-label">Monthly Budget</div>
                </div>
                <div class="snapshot-card">
                    <div class="snapshot-value">₵<?php echo number_format($totalSpent, 2); ?></div>
                    <div class="snapshot-label">Total Spent</div>
                </div>
                <div class="snapshot-card">
                    <div class="snapshot-value <?php echo $remainingBudget >= 0 ? 'positive' : 'warning'; ?>">
                        ₵<?php echo number_format($remainingBudget, 2); ?>
                    </div>
                    <div class="snapshot-label">Remaining</div>
                </div>
            </div>
            
            <!-- Month Progress -->
            <div class="month-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo min($monthProgress, 100); ?>%;"></div>
                </div>
                <div class="progress-text">
                    <span>Month Progress</span>
                    <span><?php echo round($monthProgress); ?>% (Day <?php echo $currentDay; ?> of <?php echo $daysInMonth; ?>)</span>
                </div>
            </div>
        </section>

        <!-- Main Content Grid -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Left Column -->
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <!-- Categories Overview -->
                <section class="categories-grid">
                    <?php
                    $categories = [
                        'food' => ['icon' => '🍔', 'name' => 'Food & Dining'],
                        'transport' => ['icon' => '🚗', 'name' => 'Transport'],
                        'essentials' => ['icon' => '🛍️', 'name' => 'Essentials'],
                        'entertainment' => ['icon' => '🎬', 'name' => 'Entertainment'],
                        'other' => ['icon' => '📦', 'name' => 'Other']
                    ];
                    
                    foreach ($categories as $categoryKey => $categoryInfo): 
                        $spent = $expensesByCategory[$categoryKey] ?? 0;
                        $budget = $budgetsByCategory[$categoryKey] ?? 0;
                        $percentage = $budget > 0 ? ($spent / $budget) * 100 : 0;
                    ?>
                    <div class="category-card">
                        <div class="category-header">
                            <span class="category-icon"><?php echo $categoryInfo['icon']; ?></span>
                            <span class="category-name"><?php echo $categoryInfo['name']; ?></span>
                        </div>
                        <div class="category-amount">₵<?php echo number_format($spent, 2); ?></div>
                        <div class="category-budget">
                            Budget: ₵<?php echo number_format($budget, 2); ?> 
                            <?php if ($budget > 0): ?>
                                • <?php echo round($percentage); ?>% used
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </section>

                <!-- Recent Transactions -->
                <section class="recent-transactions">
                    <h2 class="section-title">Recent Transactions</h2>
                    <div class="transaction-list">
                        <?php if (empty($recentTransactions)): ?>
                            <div style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                                No transactions yet. <a href="expenses.php" style="color: var(--neon-green);">Add your first expense</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentTransactions as $transaction): ?>
                            <div class="transaction-item">
                                <div class="transaction-info">
                                    <div class="transaction-desc"><?php echo htmlspecialchars($transaction['description']); ?></div>
                                    <div class="transaction-category"><?php echo ucfirst($transaction['category']); ?></div>
                                </div>
                                <div style="text-align: right;">
                                    <div class="transaction-amount">₵<?php echo number_format($transaction['amount'], 2); ?></div>
                                    <div class="transaction-date"><?php echo date('M j', strtotime($transaction['expense_date'])); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- Right Column -->
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <!-- Financial Insights -->
                <section class="insights-section">
                    <h2 class="section-title">Financial Insights</h2>
                    <div class="insight-list">
                        <?php if (empty($insights)): ?>
                            <div style="text-align: center; color: var(--text-secondary); padding: 1rem;">
                                No insights yet. Keep tracking your expenses!
                            </div>
                        <?php else: ?>
                            <?php foreach ($insights as $insight): ?>
                            <div class="insight-item <?php echo $insight['type']; ?>">
                                <div class="insight-message"><?php echo htmlspecialchars($insight['message']); ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Quick Actions -->
                <section class="recent-transactions">
                    <h2 class="section-title">Quick Actions</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="expenses.php" style="display: block; padding: 1rem; background: rgba(0, 255, 136, 0.1); border: 1px solid var(--neon-green); border-radius: 12px; text-decoration: none; color: var(--neon-green); text-align: center; font-weight: 500; transition: all 0.3s ease;">
                            ➕ Add Expense
                        </a>
                        <a href="budget.php" style="display: block; padding: 1rem; background: rgba(0, 255, 255, 0.1); border: 1px solid var(--neon-cyan); border-radius: 12px; text-decoration: none; color: var(--neon-cyan); text-align: center; font-weight: 500; transition: all 0.3s ease;">
                            💰 Set Budget
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        // Add any interactive JavaScript here if needed
        console.log('Dashboard loaded successfully');
    </script>
</body>
</html>