<?php
// transactions.php - FIXED VERSION
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

// Set active page
$activePage = 'transactions';

// Initialize filter variables
$searchQuery = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$dateFromFilter = $_GET['date_from'] ?? '';
$dateToFilter = $_GET['date_to'] ?? '';

// Build WHERE clause
$whereClauses = ["user_id = ?"];
$params = [$userId];

if (!empty($searchQuery)) {
    $whereClauses[] = "(description LIKE ?)";
    $params[] = "%$searchQuery%";
}

if (!empty($categoryFilter) && $categoryFilter !== 'all') {
    $whereClauses[] = "category = ?";
    $params[] = $categoryFilter;
}

if (!empty($dateFromFilter)) {
    $whereClauses[] = "expense_date >= ?";
    $params[] = $dateFromFilter;
}

if (!empty($dateToFilter)) {
    $whereClauses[] = "expense_date <= ?";
    $params[] = $dateToFilter;
}

$whereSQL = implode(' AND ', $whereClauses);

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM elm_expenses WHERE $whereSQL");
$countStmt->execute($params);
$totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pagination
$perPage = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// Get transactions
$transactionsStmt = $pdo->prepare("
    SELECT * FROM elm_expenses 
    WHERE $whereSQL 
    ORDER BY expense_date DESC, created_at DESC 
    LIMIT ? OFFSET ?
");
$paramsWithLimit = array_merge($params, [$perPage, $offset]);
$transactionsStmt->execute($paramsWithLimit);
$transactions = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary
$summaryStmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total_expenses,
        COUNT(*) as transaction_count
    FROM elm_expenses 
    WHERE $whereSQL
");
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

// Get categories for filter
$categoriesStmt = $pdo->prepare("
    SELECT DISTINCT category FROM elm_expenses WHERE user_id = ? ORDER BY category
");
$categoriesStmt->execute([$userId]);
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Get today's expenses for sidebar
$todayExpenses = getTodayExpenses($pdo, $userId);

$pageTitle = 'Transactions | Elm Finance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <!-- Include Sidebar -->
    <?php 
    $activePage = 'transactions'; 
    include 'includes/sidebar.php'; 
    ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="transactions-container">
            <!-- Header -->
            <div class="transactions-header">
                <h1>
                    <i class="fas fa-exchange-alt"></i> Transactions
                </h1>
                <div class="top-actions">
                    <button class="theme-toggle" id="themeToggle">
                        <span id="themeIcon">🌙</span>
                    </button>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="transactions-stats-grid">
                <div class="transaction-stat-card glass-card">
                    <div class="transaction-stat-value">
                        <?php echo $summary['transaction_count']; ?>
                    </div>
                    <div class="transaction-stat-label">Total Transactions</div>
                </div>
                
                <div class="transaction-stat-card glass-card">
                    <div class="transaction-stat-value" style="color: var(--error);">
                        GHS <?php echo number_format($summary['total_expenses'], 2); ?>
                    </div>
                    <div class="transaction-stat-label">Total Expenses</div>
                </div>
            </div>
            
            <!-- Filters Card -->
            <div class="filters-card glass-card">
                <h2>
                    <i class="fas fa-filter"></i> Filter Transactions
                </h2>
                <form method="GET" class="filters-grid">
                    <div class="form-group">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-input" 
                               placeholder="Search descriptions..." 
                               value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" 
                                <?php echo $categoryFilter === $category ? 'selected' : ''; ?>>
                                <?php echo ucfirst($category); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-input" 
                               value="<?php echo htmlspecialchars($dateFromFilter); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-input" 
                               value="<?php echo htmlspecialchars($dateToFilter); ?>">
                    </div>
                    
                    <div style="display: flex; gap: 1rem; align-self: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Table Card -->
            <div class="table-card glass-card">
                <div class="table-header">
                    <h2>
                        <i class="fas fa-list"></i> Transaction History
                    </h2>
                    <div class="table-count">
                        (<?php echo $totalCount; ?> records found)
                    </div>
                </div>
                
                <?php if (!empty($transactions)): ?>
                <div style="overflow-x: auto;">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th class="date-cell">Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th class="amount-cell">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td class="date-cell">
                                    <div class="date-main"><?php echo date('M d', strtotime($transaction['expense_date'])); ?></div>
                                    <div class="date-year"><?php echo date('Y', strtotime($transaction['expense_date'])); ?></div>
                                </td>
                                <td class="description-cell">
                                    <div class="description-text">
                                        <?php echo htmlspecialchars($transaction['description'] ?: 'No description'); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="category-badge <?php echo getCategoryBadgeClass($transaction['category']); ?>">
                                        <i class="fas fa-<?php echo getCategoryIcon($transaction['category']); ?>"></i>
                                        <?php echo ucfirst($transaction['category']); ?>
                                    </span>
                                </td>
                                <td class="amount-cell">
                                    - GHS <?php echo number_format($transaction['amount'], 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <?php if ($page > 1): ?>
                    <button class="pagination-btn" onclick="goToPage(<?php echo $page - 1; ?>)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <?php endif; ?>
                    
                    <span class="pagination-info">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                    <button class="pagination-btn" onclick="goToPage(<?php echo $page + 1; ?>)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="transactions-empty-state">
                    <div class="transactions-empty-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h3 class="transactions-empty-title">No Transactions Found</h3>
                    <p class="transactions-empty-description">
                        <?php echo empty($_GET) ? 'Add your first transaction to get started!' : 'No transactions match your filters.'; ?>
                    </p>
                    <?php if (!empty($_GET)): ?>
                    <button class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="public/js/theme-manager.js"></script>
    <script>
        
        function clearFilters() {
            window.location.href = 'transactions.php';
        }
        
        function goToPage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>