<?php
// admin.php - Admin Dashboard
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

// Require admin access
$auth->requireAdmin();

$user = $auth->getCurrentUser();
if (!$user) {
    $auth->logout();
    header('Location: login.php');
    exit();
}

$userName = $user['username'];
$userRole = $user['role'] ?? 'student';
$activePage = 'admin';

// Get admin stats
$stats = [];

// Total users
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM elm_users")->fetchColumn();

// Total expenses
$stats['total_expenses'] = $pdo->query("SELECT COUNT(*) FROM elm_expenses")->fetchColumn();

// Total expenses amount
$stats['total_expenses_amount'] = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM elm_expenses")->fetchColumn();

// Active users (last 30 days)
$stats['active_users'] = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM elm_expenses WHERE expense_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

// Average expense per user
$stats['avg_expense'] = $stats['total_users'] > 0 ? $stats['total_expenses_amount'] / $stats['total_users'] : 0;

// Today's expenses
$stats['today_expenses'] = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM elm_expenses WHERE DATE(expense_date) = CURDATE()")->fetchColumn();

// Monthly growth
$currentMonthTotal = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM elm_expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")->fetchColumn();
$lastMonthTotal = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM elm_expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')")->fetchColumn();
$stats['monthly_growth'] = $lastMonthTotal > 0 ? (($currentMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100 : 0;

// Get recent users
$recentUsers = $pdo->query("SELECT username, email, university, role, created_at FROM elm_users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Get recent expenses
$recentExpenses = $pdo->query("
    SELECT e.*, u.username 
    FROM elm_expenses e 
    JOIN elm_users u ON e.user_id = u.id 
    ORDER BY e.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get category distribution
$categories = $pdo->query("
    SELECT category, COUNT(*) as count, SUM(amount) as total 
    FROM elm_expenses 
    GROUP BY category 
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get unusual activity (large expenses > 500)
$unusualExpenses = $pdo->query("
    SELECT e.*, u.username 
    FROM elm_expenses e 
    JOIN elm_users u ON e.user_id = u.id 
    WHERE e.amount > 500 
    ORDER BY e.amount DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Admin Dashboard | Elm Finance';
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="admin-container">
            <!-- Admin Header -->
            <div class="admin-header glass-card">
                <h1>👑 Admin Dashboard</h1>
                <p>
                    Welcome back, <?php echo htmlspecialchars($userName); ?>! 
                    <span class="admin-role-badge"><?php echo ucfirst($userRole); ?></span>
                </p>
            </div>
            
            <!-- Admin Navigation -->
            <div class="admin-nav">
                <a href="admin.php" class="admin-nav-btn active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="adminusers.php" class="admin-nav-btn">
                    <i class="fas fa-users"></i> User Management
                </a>
                <a href="adminreports.php" class="admin-nav-btn">
                    <i class="fas fa-file-contract"></i> Financial Reports
                </a>
                <a href="adminsecurity.php" class="admin-nav-btn">
                    <i class="fas fa-shield-alt"></i> Security Dashboard
                </a>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="adminusers.php" class="quick-action-card glass-card">
                    <div class="quick-action-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="quick-action-title">User Management</div>
                    <div class="quick-action-desc">
                        Manage all registered users, update roles, and view user details
                    </div>
                </a>
                
                <a href="adminreports.php" class="quick-action-card glass-card">
                    <div class="quick-action-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="quick-action-title">Financial Reports</div>
                    <div class="quick-action-desc">
                        View financial summaries and export data to CSV
                    </div>
                </a>
            </div>
            
            <!-- System Overview Stats -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card glass-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="admin-stat-value"><?php echo $stats['total_users']; ?></div>
                    <div class="admin-stat-label">Total Users</div>
                </div>
                
                <div class="admin-stat-card glass-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="admin-stat-value"><?php echo $stats['total_expenses']; ?></div>
                    <div class="admin-stat-label">Total Expenses</div>
                </div>
                
                <div class="admin-stat-card glass-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="admin-stat-value">GHS <?php echo number_format($stats['total_expenses_amount'], 0); ?></div>
                    <div class="admin-stat-label">Total Spent</div>
                </div>
                
                <div class="admin-stat-card glass-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="admin-stat-value"><?php echo $stats['active_users']; ?></div>
                    <div class="admin-stat-label">Active Users (30d)</div>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="admin-table-container glass-card">
                <div class="admin-table-header">
                    <h2>
                        <i class="fas fa-user-plus"></i> Recently Registered Users
                    </h2>
                    <a href="adminusers.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Users
                    </a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>University</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $userRow): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($userRow['username']); ?></td>
                            <td><?php echo htmlspecialchars($userRow['email']); ?></td>
                            <td><?php echo htmlspecialchars($userRow['university']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $userRow['role']; ?>">
                                    <?php echo ucfirst($userRow['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($userRow['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Financial Oversight -->
            <div class="admin-table-container glass-card">
                <div class="admin-table-header">
                    <h2>
                        <i class="fas fa-receipt"></i> Recent Expenses
                    </h2>
                    <a href="#" class="btn btn-secondary">
                        <i class="fas fa-download"></i> Export Data
                    </a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentExpenses as $expense): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($expense['username']); ?></td>
                            <td style="color: var(--error); font-weight: 600;">
                                GHS <?php echo number_format($expense['amount'], 2); ?>
                            </td>
                            <td>
                                <span class="category-badge <?php echo getCategoryBadgeClass($expense['category']); ?>">
                                    <?php echo ucfirst($expense['category']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($expense['description'] ?: 'No description'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Category Distribution -->
            <div class="admin-table-container glass-card">
                <div class="admin-table-header">
                    <h2>
                        <i class="fas fa-chart-pie"></i> Category Distribution
                    </h2>
                    <span style="color: var(--text-secondary); font-size: 1.2rem;">
                        Total: GHS <?php echo number_format($stats['total_expenses_amount'], 2); ?>
                    </span>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Transactions</th>
                            <th>Total Amount</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): 
                            $percentage = $stats['total_expenses_amount'] > 0 ? ($category['total'] / $stats['total_expenses_amount']) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <span class="category-badge <?php echo getCategoryBadgeClass($category['category']); ?>">
                                    <?php echo ucfirst($category['category']); ?>
                                </span>
                            </td>
                            <td><?php echo $category['count']; ?></td>
                            <td style="font-weight: 600;">GHS <?php echo number_format($category['total'], 2); ?></td>
                            <td><?php echo round($percentage, 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Security Monitoring -->
            <?php if (!empty($unusualExpenses)): ?>
            <div class="admin-table-container glass-card">
                <div class="admin-table-header">
                    <h2 style="color: var(--warning);">
                        <i class="fas fa-exclamation-triangle"></i> Unusual Activity
                    </h2>
                    <span style="color: var(--warning); font-size: 1.2rem;">
                        Large expenses detected (> GHS 500)
                    </span>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unusualExpenses as $expense): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($expense['username']); ?></td>
                            <td style="color: var(--error); font-weight: 700;">
                                GHS <?php echo number_format($expense['amount'], 2); ?>
                            </td>
                            <td>
                                <span class="category-badge <?php echo getCategoryBadgeClass($expense['category']); ?>">
                                    <?php echo ucfirst($expense['category']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($expense['description'] ?: 'No description'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                            <td>
                                <button class="action-btn warning">
                                    <i class="fas fa-eye"></i> Review
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- System Analytics -->
            <div class="admin-alert info">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-chart-line" style="font-size: 1.4rem;"></i>
                    <strong style="font-size: 1.2rem;">System Analytics</strong>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                    <div>
                        <div style="font-size: 1.1rem; color: var(--text-secondary);">Monthly Growth</div>
                        <div style="font-size: 1.6rem; font-weight: 600; color: <?php echo $stats['monthly_growth'] >= 0 ? 'var(--success)' : 'var(--error)'; ?>">
                            <?php echo $stats['monthly_growth'] >= 0 ? '+' : ''; ?><?php echo round($stats['monthly_growth'], 1); ?>%
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 1.1rem; color: var(--text-secondary);">Avg Expense/User</div>
                        <div style="font-size: 1.6rem; font-weight: 600;">GHS <?php echo number_format($stats['avg_expense'], 2); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 1.1rem; color: var(--text-secondary);">Today's Expenses</div>
                        <div style="font-size: 1.6rem; font-weight: 600;">GHS <?php echo number_format($stats['today_expenses'], 2); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="public/js/theme-manager.js"></script>
    <script>
        // Theme toggle
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('elm-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Add theme toggle if button exists
            const themeToggle = document.querySelector('.theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const currentTheme = document.documentElement.getAttribute('data-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('elm-theme', newTheme);
                });
            }
        });
    </script>
</body>
</html>