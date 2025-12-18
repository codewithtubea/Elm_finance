<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Initialize database and auth
try {
    $db = new Database();
    $pdo = $db->getPDO();
    $auth = new Auth($pdo);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Get current user data
$user = $auth->getCurrentUser();

if (!$user) {
    $auth->logout();
    header('Location: ../login.php');
    exit();
}

$userId = $user['id'];
$userRole = $user['role'] ?? 'student';
$userName = $user['username'];
$userUniversity = $user['university'] ?? 'Student';

// Get current month/year
$currentMonth = date('Y-m');

// Get recent expenses for sidebar badge
$recentStmt = $pdo->prepare("
    SELECT COUNT(*) as count FROM elm_expenses 
    WHERE user_id = ? 
    AND DATE_FORMAT(expense_date, '%Y-%m') = ?
");
$recentStmt->execute([$userId, $currentMonth]);
$expenseCount = $recentStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

// // Get savings goals for sidebar badge
// $goalsStmt = $pdo->prepare("SELECT COUNT(*) as count FROM elm_goals WHERE user_id = ?");
// $goalsStmt->execute([$userId]);
// $goalsCount = $goalsStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
// ?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Elm Finance'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <!-- Responsive Sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="sidebar-logo">
                    <svg viewBox="0 0 24 24" style="width: 32px; height: 32px;">
                        <path d="M12 4 L16 8 L14 16 L10 18 L6 16 L8 8 Z" fill="#00ff88"/>
                    </svg>
                </div>
                <h2>🌿 Elm Finance</h2>
            </div>
            <button class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?php echo strtoupper(substr($userName, 0, 2)); ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-username"><?php echo htmlspecialchars($userName); ?></div>
                <div class="sidebar-user-role"><?php echo ucfirst($userRole); ?></div>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-section">
                <div class="section-title">DASHBOARD</div>
                <a href="../dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Overview</span>
                </a>
                <a href="analytics.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="reports.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Reports</span>
                </a>
            </div>
            
            <div class="menu-section">
                <div class="section-title">MANAGE</div>
                <a href="expenses.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'expenses.php' ? 'active' : ''; ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Expenses</span>
                    <?php if ($expenseCount > 0): ?>
                    <span class="menu-badge"><?php echo $expenseCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="budgets.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'budgets.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Budgets</span>
                </a>
                <a href="goals.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'goals.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bullseye"></i>
                    <span>Goals</span>
                    <?php if ($goalsCount > 0): ?>
                    <span class="menu-badge"><?php echo $goalsCount; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="menu-section">
                <div class="section-title">TOOLS</div>
                <a href="calculator.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'calculator.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calculator"></i>
                    <span>Finance Calculator</span>
                </a>
                <a href="tips.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'tips.php' ? 'active' : ''; ?>">
                    <i class="fas fa-lightbulb"></i>
                    <span>Student Tips</span>
                </a>
                <a href="campus.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'campus.php' ? 'active' : ''; ?>">
                    <i class="fas fa-university"></i>
                    <span>Campus Deals</span>
                </a>
            </div>
            
            <div class="menu-section">
                <div class="section-title">SETTINGS</div>
                <a href="profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="preferences.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'preferences.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Preferences</span>
                </a>
                <a href="help.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'help.php' ? 'active' : ''; ?>">
                    <i class="fas fa-question-circle"></i>
                    <span>Help & Support</span>
                </a>
            </div>
        </div>
        
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>

    <div class="main-content" id="mainContent">
        <!-- Content will be inserted here -->