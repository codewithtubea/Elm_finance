<?php
// includes/functions.php
// Core Functions



function adjustContentForSidebar() {
    // This function can be used to dynamically adjust margins
    // For now, we rely on CSS media queries
    return true;
}

/** Get category icon */
function getCategoryDisplayIcon($category) {
    $icons = [
        'food' => '🍔',
        'transport' => '🚗',
        'essentials' => '🛒',
        'entertainment' => '🎬',
        'other' => '📝'
    ];
    return $icons[$category] ?? '📝';
}

/** Format date */
function formatDateNice($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format date short
 */
function formatDateShort($date) {
    return date('M d', strtotime($date));
}

/** Compare budget vs expenses */
function getBudgetExpenseComparison($pdo, $userId, $monthYear = null) {
    if (!$monthYear) {
        $monthYear = date('Y-m-01');
    }
    
    // Get budgets for this month
    $budgetQuery = "SELECT category, amount FROM elm_budgets 
                    WHERE user_id = ? AND month_year = ?";
    $budgetStmt = $pdo->prepare($budgetQuery);
    $budgetStmt->execute([$userId, $monthYear]);
    $budgets = $budgetStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get expenses for this month
    $expenseQuery = "SELECT category, SUM(amount) as spent 
                     FROM elm_expenses 
                     WHERE user_id = ? 
                     AND DATE_FORMAT(expense_date, '%Y-%m-01') = ?
                     GROUP BY category";
    $expenseStmt = $pdo->prepare($expenseQuery);
    $expenseStmt->execute([$userId, $monthYear]);
    $expenses = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine data
    $result = [];
    $totalBudget = 0;
    $totalSpent = 0;
    
    foreach ($budgets as $budget) {
        $category = $budget['category'];
        $spent = 0;
        
        foreach ($expenses as $expense) {
            if ($expense['category'] == $category) {
                $spent = $expense['spent'];
                break;
            }
        }
        
        $totalBudget += $budget['amount'];
        $totalSpent += $spent;
        
        $result[$category] = [
            'budget' => $budget['amount'],
            'spent' => $spent,
            'remaining' => $budget['amount'] - $spent,
            'percentage' => $budget['amount'] > 0 ? ($spent / $budget['amount']) * 100 : 0
        ];
    }
    
    return [
        'categories' => $result,
        'total_budget' => $totalBudget,
        'total_spent' => $totalSpent,
        'total_remaining' => $totalBudget - $totalSpent,
        'total_percentage' => $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0
    ];
}

/**
 * Get category icon
 */
function getCategoryIcon($category) {
    $icons = [
        'food' => 'utensils',
        'transport' => 'bus',
        'essentials' => 'shopping-basket',
        'entertainment' => 'gamepad',
        'other' => 'receipt'
    ];
    return $icons[$category] ?? 'receipt';
}

/**
 * Get category badge class
 */
function getCategoryBadgeClass($category) {
    $classes = [
        'food' => 'badge-food',
        'transport' => 'badge-transport',
        'essentials' => 'badge-essentials',
        'entertainment' => 'badge-entertainment',
        'other' => 'badge-other'
    ];
    return $classes[$category] ?? 'badge-other';
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return 'GHS ' . number_format($amount, 2);
}

/**
 * Get today's expenses count
 */
function getTodayExpenses($pdo, $userId) {
    $today = date('Y-m-d');
    $query = "SELECT COUNT(*) as count FROM elm_expenses 
              WHERE user_id = ? AND DATE(expense_date) = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId, $today]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] ?? 0;
}