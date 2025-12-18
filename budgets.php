<?php
// budgets.php - FIXED VERSION
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php'; // ADD THIS

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

// Set active page for sidebar
$activePage = 'budgets';

// Get today's expenses for badge
$todayExpenses = getTodayExpenses($pdo, $userId);

// Handle month navigation
$currentMonth = date('Y-m');
if (isset($_GET['month'])) {
    $currentMonth = $_GET['month'];
}
$currentMonthDate = $currentMonth . '-01';
$currentMonthDisplay = date('F Y', strtotime($currentMonthDate));

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'set_budget') {
            $category = $_POST['category'];
            $amount = $_POST['amount'];
            $monthYear = $currentMonthDate;
            
            if ($category && $amount > 0) {
                // Check if budget exists
                $checkStmt = $pdo->prepare("
                    SELECT id FROM elm_budgets 
                    WHERE user_id = ? AND category = ? AND month_year = ?
                ");
                $checkStmt->execute([$userId, $category, $monthYear]);
                
                if ($checkStmt->rowCount() > 0) {
                    // Update
                    $updateStmt = $pdo->prepare("
                        UPDATE elm_budgets SET amount = ? 
                        WHERE user_id = ? AND category = ? AND month_year = ?
                    ");
                    $updateStmt->execute([$amount, $userId, $category, $monthYear]);
                    $message = "Budget updated successfully!";
                } else {
                    // Insert
                    $insertStmt = $pdo->prepare("
                        INSERT INTO elm_budgets (user_id, category, amount, month_year) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $insertStmt->execute([$userId, $category, $amount, $monthYear]);
                    $message = "Budget set successfully!";
                }
                
                $_SESSION['success'] = $message;
                header("Location: budgets.php?month=$currentMonth");
                exit();
            }
        }
        elseif ($_POST['action'] === 'delete_budget') {
            $budgetId = $_POST['budget_id'];
            $deleteStmt = $pdo->prepare("DELETE FROM elm_budgets WHERE id = ? AND user_id = ?");
            $deleteStmt->execute([$budgetId, $userId]);
            $_SESSION['success'] = "Budget deleted successfully!";
            header("Location: budgets.php?month=$currentMonth");
            exit();
        }
    }
}

// Check for success message
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Get budgets for current month
$budgetsStmt = $pdo->prepare("
    SELECT b.*, 
           COALESCE((
               SELECT SUM(e.amount) 
               FROM elm_expenses e 
               WHERE e.user_id = b.user_id 
               AND e.category = b.category 
               AND DATE_FORMAT(e.expense_date, '%Y-%m-01') = b.month_year
           ), 0) as spent
    FROM elm_budgets b
    WHERE b.user_id = ? AND b.month_year = ?
    ORDER BY b.category
");
$budgetsStmt->execute([$userId, $currentMonthDate]);
$budgets = $budgetsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get total budget and spent
$totalBudget = 0;
$totalSpent = 0;
foreach ($budgets as $budget) {
    $totalBudget += $budget['amount'];
    $totalSpent += $budget['spent'];
}

$pageTitle = 'Budgets | Elm Finance';
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
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="budgets-container">
            <!-- Header -->
            <div class="budgets-header">
                <h1>
                    <i class="fas fa-chart-pie"></i> Budgets
                </h1>
                <div class="top-actions">
                    <button class="theme-toggle" id="themeToggle">
                        <span id="themeIcon">🌙</span>
                    </button>
                </div>
            </div>
            
            <!-- Success Message -->
            <?php if (isset($successMessage)): ?>
            <div class="alert-message alert-success" style="margin-bottom: 2rem;">
                <strong style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-check-circle"></i> Success
                </strong>
                <p style="margin-top: 0.5rem;"><?php echo htmlspecialchars($successMessage); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Month Navigation -->
            <div class="month-navigation">
                <button class="month-btn" onclick="changeMonth(-1)">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <div class="month-display">
                    <?php echo $currentMonthDisplay; ?>
                </div>
                <button class="month-btn" onclick="changeMonth(1)">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <!-- Budget Form -->
            <div class="budget-form-card glass-card">
                <h2>
                    <i class="fas fa-plus-circle"></i> Set New Budget
                </h2>
                <form method="POST" class="budget-form-grid">
                    <input type="hidden" name="action" value="set_budget">
                    
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="">Select category</option>
                            <option value="food">Food & Dining</option>
                            <option value="transport">Transport</option>
                            <option value="essentials">Essentials</option>
                            <option value="entertainment">Entertainment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Amount (GHS)</label>
                        <input type="number" name="amount" class="form-input" 
                               step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-check"></i> Set Budget
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Budgets Grid -->
            <?php if (!empty($budgets)): ?>
            <div class="budgets-grid">
                <?php foreach ($budgets as $budget): 
                    $percentage = $budget['amount'] > 0 ? ($budget['spent'] / $budget['amount']) * 100 : 0;
                    $remaining = $budget['amount'] - $budget['spent'];
                    $progressColor = $percentage >= 100 ? 'var(--error)' : ($percentage >= 80 ? 'var(--warning)' : 'var(--success)');
                ?>
                <div class="budget-card glass-card">
                    <div class="budget-header">
                        <div class="budget-category">
                            <div class="category-icon <?php echo $budget['category']; ?>">
                                <?php 
                                $icons = [
                                    'food' => 'fas fa-utensils',
                                    'transport' => 'fas fa-bus',
                                    'essentials' => 'fas fa-shopping-basket',
                                    'entertainment' => 'fas fa-gamepad',
                                    'other' => 'fas fa-receipt'
                                ];
                                echo '<i class="' . ($icons[$budget['category']] ?? 'fas fa-receipt') . '"></i>';
                                ?>
                            </div>
                            <div>
                                <div class="budget-category-text"><?php echo ucfirst($budget['category']); ?></div>
                                <div style="font-size: 1rem; color: var(--text-secondary);">Budget</div>
                            </div>
                        </div>
                        <div class="budget-amount">
                            <div class="budget-total">GHS <?php echo number_format($budget['amount'], 2); ?></div>
                            <div class="budget-period">Per month</div>
                        </div>
                    </div>
                    
                    <!-- Progress -->
                    <div class="budget-progress">
                        <div class="spent-remaining">
                            <span class="spent-amount">Spent: GHS <?php echo number_format($budget['spent'], 2); ?></span>
                            <span class="remaining-amount">Left: GHS <?php echo number_format($remaining, 2); ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%; background: <?php echo $progressColor; ?>;"></div>
                            <div class="progress-percentage"><?php echo round($percentage, 1); ?>%</div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="budget-actions">
                        <button class="budget-action-btn" onclick="editBudget('<?php echo $budget['category']; ?>', <?php echo $budget['amount']; ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="action" value="delete_budget">
                            <input type="hidden" name="budget_id" value="<?php echo $budget['id']; ?>">
                            <button type="submit" class="budget-action-btn delete" onclick="return confirm('Are you sure you want to delete this budget?')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Summary Card -->
            <div class="budget-summary-card glass-card">
                <h3>Monthly Summary</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Total Budget</div>
                        <div class="summary-value summary-total">GHS <?php echo number_format($totalBudget, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Spent</div>
                        <div class="summary-value summary-spent">GHS <?php echo number_format($totalSpent, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Remaining</div>
                        <div class="summary-value summary-remaining">GHS <?php echo number_format($totalBudget - $totalSpent, 2); ?></div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="budgets-empty-state">
                <div class="budgets-empty-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <h3 class="budgets-empty-title">No Budgets Set Yet</h3>
                <p class="budgets-empty-description">
                    Set your first budget using the form above to start tracking your spending limits.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="public/js/theme-manager.js"></script>
    <script>
        
        function changeMonth(direction) {
            const currentMonth = '<?php echo $currentMonth; ?>';
            const date = new Date(currentMonth + '-01');
            date.setMonth(date.getMonth() + direction);
            const newMonth = date.toISOString().slice(0, 7);
            window.location.href = `budgets.php?month=${newMonth}`;
        }
        
        function editBudget(category, amount) {
            document.querySelector('select[name="category"]').value = category;
            document.querySelector('input[name="amount"]').value = amount;
            document.querySelector('input[name="amount"]').focus();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>