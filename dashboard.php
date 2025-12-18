<?php
// dashboard.php - COMPLETELY FIXED VERSION
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

try {
    $db = new Database();
    $pdo = $db->getPDO();
    $auth = new Auth($pdo);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = $auth->getCurrentUser();
if (!$user) {
    $auth->logout();
    header('Location: login.php');
    exit();
}

$userId = $user['id'];
$userName = $user['username'];
$userRole = $user['role'] ?? 'student';
$userUniversity = $user['university'] ?? 'Student';

// Set active page for sidebar
$activePage = 'dashboard';

// Get today's expenses for badge
$todayExpenses = getTodayExpenses($pdo, $userId);

// Get current month
$currentMonth = date('Y-m-01');
$yearMonth = date('F Y');

// Get budget vs expense comparison
$comparison = getBudgetExpenseComparison($pdo, $userId, $currentMonth);

// Extract totals
$monthlyBudget = $comparison['total_budget'];
$monthlyExpenses = $comparison['total_spent'];
$remainingBudget = $comparison['total_remaining'];
$budgetPercentage = $comparison['total_percentage'];

// Get daily average
$daysPassed = date('j');
$dailyAverage = $daysPassed > 0 ? $monthlyExpenses / $daysPassed : 0;
$projectedSpend = $dailyAverage * 30;

// Get recent expenses
$recentStmt = $pdo->prepare("
    SELECT * FROM elm_expenses 
    WHERE user_id = ? 
    ORDER BY expense_date DESC, id DESC 
    LIMIT 5
");
$recentStmt->execute([$userId]);
$recentExpenses = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// Get month-over-month change
$prevMonth = date('Y-m-01', strtotime('-1 month'));
$prevComparison = getBudgetExpenseComparison($pdo, $userId, $prevMonth);
$prevMonthExpenses = $prevComparison['total_spent'];
$momChange = $prevMonthExpenses > 0 ? 
    (($monthlyExpenses - $prevMonthExpenses) / $prevMonthExpenses) * 100 : 0;

// Category icons
$categoryIcons = [
    'food' => ['icon' => 'fas fa-utensils', 'color' => 'var(--success)'],
    'transport' => ['icon' => 'fas fa-bus', 'color' => 'var(--info)'],
    'essentials' => ['icon' => 'fas fa-shopping-basket', 'color' => 'var(--warning)'],
    'entertainment' => ['icon' => 'fas fa-gamepad', 'color' => '#a855f7'],
    'other' => ['icon' => 'fas fa-receipt', 'color' => 'var(--error)']
];

$pageTitle = 'Dashboard | Elm Finance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <!-- Include Universal Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main -->
    <div class="main-content" id="mainContent">
        <div class="container">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Dashboard Overview</h1>
                <div class="top-actions">
                    <button class="theme-toggle" id="themeToggle">
                        <span id="themeIcon">🌙</span>
                    </button>
                </div>
            </div>
            
            <!-- Welcome Banner -->
            <div class="welcome-banner fade-in-up">
                <h2>Welcome, <?php echo htmlspecialchars($userName); ?>!</h2>
                <p>Here's your financial overview for <?php echo $yearMonth; ?></p>
                <div class="welcome-stats">
                    <div class="welcome-stat">
                        <div class="welcome-stat-label">University</div>
                        <div class="welcome-stat-value"><?php echo htmlspecialchars($userUniversity); ?></div>
                    </div>
                    <div class="welcome-stat">
                        <div class="welcome-stat-label">Role</div>
                        <div class="welcome-stat-value"><?php echo ucfirst($userRole); ?></div>
                    </div>
                    <div class="welcome-stat">
                        <div class="welcome-stat-label">Member Since</div>
                        <div class="welcome-stat-value"><?php echo date('M Y', strtotime($user['created_at'] ?? 'now')); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="dashboard-stats-grid">
                <!-- Monthly Budget -->
                <div class="stat-card-highlight fade-in-up">
                    <div class="stat-title-large">Monthly Budget</div>
                    <div class="stat-value-large">GHS <?php echo number_format($monthlyBudget, 2); ?></div>
                    <div class="stat-change-large">
                        <i class="fas fa-wallet"></i>
                        <span>Your total spending limit</span>
                    </div>
                </div>
                
                <!-- Spent This Month -->
                <div class="stat-card-highlight fade-in-up delay-1">
                    <div class="stat-title-large">Spent This Month</div>
                    <div class="stat-value-large" style="color: var(--error);">GHS <?php echo number_format($monthlyExpenses, 2); ?></div>
                    <div class="stat-change-large">
                        <?php if ($momChange > 0): ?>
                            <i class="fas fa-arrow-up" style="color: var(--error);"></i>
                            <span style="color: var(--error);">+<?php echo round($momChange, 1); ?>% vs last month</span>
                        <?php else: ?>
                            <i class="fas fa-arrow-down" style="color: var(--success);"></i>
                            <span style="color: var(--success);"><?php echo round($momChange, 1); ?>% vs last month</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Remaining Budget -->
                <div class="stat-card-highlight fade-in-up delay-2">
                    <div class="stat-title-large">Remaining Budget</div>
                    <div class="stat-value-large" style="color: <?php echo $remainingBudget >= 0 ? 'var(--success)' : 'var(--error)'; ?>;">
                        GHS <?php echo number_format($remainingBudget, 2); ?>
                    </div>
                    <div class="stat-change-large">
                        <i class="fas fa-chart-line"></i>
                        <span>
                            <?php if ($remainingBudget >= 0): ?>
                                <span style="color: var(--success);">✅ On track</span>
                            <?php else: ?>
                                <span style="color: var(--error);">⚠️ Over budget</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <!-- Progress Bar -->
                    <div style="margin-top: 1.5rem;">
                        <div class="progress-container">
                            <div class="progress-fill" style="width: <?php echo min($budgetPercentage, 100); ?>%; background: var(--gradient-primary);"></div>
                        </div>
                        <span style="margin-left: 1rem; font-size: 1.4rem; color: var(--text-secondary);">
                            <?php echo round($budgetPercentage, 1); ?>% used
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Budget vs Expenses -->
            <div class="budget-table-container fade-in-up">
                <div class="section-header-large">
                    <h2 class="section-title-large">
                        <i class="fas fa-chart-bar"></i> Budget vs Expenses
                    </h2>
                    <a href="budgets.php" class="btn btn-secondary">
                        <i class="fas fa-edit"></i> Manage Budgets
                    </a>
                </div>
                
                <?php if (!empty($comparison['categories'])): ?>
                <div class="table-container">
                    <table class="budget-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th style="text-align: right;">Budget</th>
                                <th style="text-align: right;">Spent</th>
                                <th style="text-align: right;">Remaining</th>
                                <th style="text-align: center;">Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comparison['categories'] as $category => $data): 
                                $icon = $categoryIcons[$category] ?? $categoryIcons['other'];
                                $progressColor = $data['percentage'] >= 100 ? 'var(--error)' : 
                                               ($data['percentage'] >= 80 ? 'var(--warning)' : 'var(--success)');
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <div class="category-icon-large" style="background: <?php echo str_replace('var(--', 'rgba(', $icon['color']); ?>0.1); color: <?php echo $icon['color']; ?>;">
                                            <i class="<?php echo $icon['icon']; ?>"></i>
                                        </div>
                                        <span style="font-size: 1.6rem; font-weight: 500;"><?php echo ucfirst($category); ?></span>
                                    </div>
                                </td>
                                <td style="text-align: right; font-weight: 600; font-size: 1.6rem;">
                                    GHS <?php echo number_format($data['budget'], 2); ?>
                                </td>
                                <td style="text-align: right; font-weight: 600; color: var(--error); font-size: 1.6rem;">
                                    GHS <?php echo number_format($data['spent'], 2); ?>
                                </td>
                                <td style="text-align: right; font-weight: 600; color: <?php echo $data['remaining'] >= 0 ? 'var(--success)' : 'var(--error)'; ?>; font-size: 1.6rem;">
                                    GHS <?php echo number_format($data['remaining'], 2); ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: flex; align-items: center; gap: 1rem; justify-content: center;">
                                        <div class="progress-container">
                                            <div class="progress-fill" style="width: <?php echo min($data['percentage'], 100); ?>%; background: <?php echo $progressColor; ?>;"></div>
                                        </div>
                                        <span style="font-size: 1.4rem; font-weight: 600; color: <?php echo $progressColor; ?>;">
                                            <?php echo round($data['percentage'], 1); ?>%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state-large">
                    <div class="empty-icon-large">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3 class="empty-title-large">No Budgets Set Yet</h3>
                    <p class="empty-desc-large">Set your first budget to start tracking your spending</p>
                    <a href="budgets.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create First Budget
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Transactions -->
            <div class="recent-transactions-container fade-in-up">
                <div class="section-header-large">
                    <h2 class="section-title-large">
                        <i class="fas fa-history"></i> Recent Transactions
                    </h2>
                    <a href="transactions.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i> View All
                    </a>
                </div>
                
                <?php if (!empty($recentExpenses)): ?>
                <div class="transaction-list-large">
                    <?php foreach ($recentExpenses as $expense): 
                        $icon = $categoryIcons[$expense['category']] ?? $categoryIcons['other'];
                    ?>
                    <div class="transaction-item-large">
                        <div class="transaction-category-large" style="background: <?php echo str_replace('var(--', 'rgba(', $icon['color']); ?>0.1); color: <?php echo $icon['color']; ?>;">
                            <i class="<?php echo $icon['icon']; ?>"></i>
                        </div>
                        <div style="flex: 1;">
                            <div class="transaction-title-large"><?php echo ucfirst($expense['category']); ?></div>
                            <div class="transaction-description-large">
                                <?php echo htmlspecialchars($expense['description'] ?: 'No description'); ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="transaction-amount-large" style="color: var(--error);">
                                - GHS <?php echo number_format($expense['amount'], 2); ?>
                            </div>
                            <div class="transaction-date-large">
                                <?php echo date('M d, Y', strtotime($expense['expense_date'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state-large">
                    <div class="empty-icon-large">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h3 class="empty-title-large">No Transactions Yet</h3>
                    <p class="empty-desc-large">Start tracking your expenses to see insights</p>
                    <a href="expenses.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Expense
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- JS -->
    <script src="public/js/theme-manager.js"></script>
    
</body>
</html>