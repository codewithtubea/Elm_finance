<?php
// analytics.php - COMPLETELY FIXED VERSION
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
$activePage = 'analytics';

// Get analytics data
$currentMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));

// Define all possible categories
$allCategories = ['food', 'transport', 'essentials', 'entertainment', 'other'];

// Monthly spending - get last 6 months
$monthlyStmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(expense_date, '%Y-%m') as month,
        SUM(amount) as total
    FROM elm_expenses 
    WHERE user_id = ? 
    AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
    ORDER BY month
");
$monthlyStmt->execute([$userId]);
$monthlyData = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

// Category breakdown for current month - get ALL categories with proper data
$categoryData = [];
foreach ($allCategories as $category) {
    $categoryStmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(amount), 0) as total,
            COALESCE(COUNT(*), 0) as count
        FROM elm_expenses 
        WHERE user_id = ? 
        AND category = ?
        AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ");
    $categoryStmt->execute([$userId, $category, $currentMonth]);
    $catData = $categoryStmt->fetch(PDO::FETCH_ASSOC);
    
    $categoryData[] = [
        'category' => $category,
        'total' => $catData['total'] ?? 0,
        'count' => $catData['count'] ?? 0
    ];
}

// Sort categories by total (descending)
usort($categoryData, function($a, $b) {
    return $b['total'] <=> $a['total'];
});

// Get top expenses
$topStmt = $pdo->prepare("
    SELECT 
        description,
        amount,
        category,
        expense_date
    FROM elm_expenses 
    WHERE user_id = ? 
    ORDER BY amount DESC, expense_date DESC 
    LIMIT 5
");
$topStmt->execute([$userId]);
$topExpenses = $topStmt->fetchAll(PDO::FETCH_ASSOC);

// Compare with last month
$compareStmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as current_total
    FROM elm_expenses 
    WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?
");
$compareStmt->execute([$userId, $currentMonth]);
$currentTotal = $compareStmt->fetch(PDO::FETCH_ASSOC)['current_total'] ?? 0;

$compareStmt->execute([$userId, $lastMonth]);
$previousTotal = $compareStmt->fetch(PDO::FETCH_ASSOC)['current_total'] ?? 0;

$change = $previousTotal > 0 ? (($currentTotal - $previousTotal) / $previousTotal) * 100 : 0;

// Get total categories with expenses
$activeCategories = array_filter($categoryData, function($cat) {
    return ($cat['total'] ?? 0) > 0;
});

// Calculate total for percentage calculations
$totalForPercentage = array_sum(array_column($categoryData, 'total'));

// Get today's expenses for sidebar
$todayExpenses = getTodayExpenses($pdo, $userId);

$pageTitle = 'Analytics | Elm Finance';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <!-- Include Universal Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="container">
            <!-- Header -->
            <div class="top-bar">
                <h1 class="page-title">Spending Analytics</h1>
                <div class="top-actions">
                    <button class="theme-toggle" id="themeToggle">
                        <span id="themeIcon">🌙</span>
                    </button>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card fade-in-up glass-card">
                    <div style="text-align: center;">
                        <div style="font-size: 3.2rem; font-weight: 700; color: var(--accent-primary); margin-bottom: 1rem;">
                            GHS <?php echo number_format($currentTotal, 2); ?>
                        </div>
                        <div style="font-size: 1.4rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                            Spent This Month
                        </div>
                        <div style="font-size: 1.4rem; color: <?php echo $change > 0 ? 'var(--error)' : 'var(--success)'; ?>; font-weight: 500;">
                            <?php echo $change > 0 ? '+' : ''; ?><?php echo round($change, 1); ?>% vs last month
                        </div>
                    </div>
                </div>
                
                <div class="stat-card fade-in-up delay-1 glass-card">
                    <div style="text-align: center;">
                        <div style="font-size: 3.2rem; font-weight: 700; color: var(--success); margin-bottom: 1rem;">
                            <?php echo count($activeCategories); ?>
                        </div>
                        <div style="font-size: 1.4rem; color: var(--text-secondary);">
                            Categories Used
                        </div>
                        <?php if (!empty($categoryData) && $categoryData[0]['total'] > 0): ?>
                        <div style="font-size: 1.2rem; color: var(--text-secondary); margin-top: 0.5rem;">
                            Highest: <?php echo ucfirst($categoryData[0]['category']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="stat-card fade-in-up delay-2 glass-card">
                    <div style="text-align: center;">
                        <div style="font-size: 3.2rem; font-weight: 700; color: var(--warning); margin-bottom: 1rem;">
                            <?php echo array_sum(array_column($categoryData, 'count')); ?>
                        </div>
                        <div style="font-size: 1.4rem; color: var(--text-secondary);">
                            Transactions
                        </div>
                        <div style="font-size: 1.2rem; color: var(--text-secondary); margin-top: 0.5rem;">
                            This month
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-container fade-in-up glass-card">
                    <h3 class="chart-title">Monthly Spending Trend</h3>
                    <div class="chart-wrapper">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-container fade-in-up delay-1 glass-card">
                    <h3 class="chart-title">Spending by Category</h3>
                    <div class="chart-wrapper">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Category Breakdown -->
            <div class="data-section fade-in-up">
                <h3 class="section-title">Category Breakdown</h3>
                <div class="table-container glass-card">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Transactions</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categoryData)): ?>
                                <?php foreach ($categoryData as $cat): 
                                    $percentage = $totalForPercentage > 0 ? ($cat['total'] / $totalForPercentage) * 100 : 0;
                                    $category = $cat['category'];
                                ?>
                                <tr>
                                    <td>
                                        <span class="category-badge <?php echo getCategoryBadgeClass($category); ?>">
                                            <i class="fas fa-<?php echo getCategoryIcon($category); ?>"></i>
                                            <?php echo ucfirst($category); ?>
                                        </span>
                                    </td>
                                    <td style="font-weight: 600;">GHS <?php echo number_format($cat['total'], 2); ?></td>
                                    <td><?php echo $cat['count']; ?></td>
                                    <td><?php echo round($percentage, 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                                        No spending data for this month
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Top Expenses -->
            <div class="data-section fade-in-up">
                <h3 class="section-title">Top 5 Largest Expenses</h3>
                <div class="table-container glass-card">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($topExpenses)): ?>
                                <?php foreach ($topExpenses as $expense): 
                                    $category = $expense['category'] ?? 'other';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($expense['description'] ?: 'No description'); ?></td>
                                    <td>
                                        <span class="category-badge <?php echo getCategoryBadgeClass($category); ?>">
                                            <?php echo ucfirst($category); ?>
                                        </span>
                                    </td>
                                    <td style="font-weight: 600; color: var(--error);">
                                        GHS <?php echo number_format($expense['amount'], 2); ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                                        No expense data yet
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Helper to get computed CSS variable values
            const getChartColors = () => {
                const style = getComputedStyle(document.documentElement);
                return {
                    primary: style.getPropertyValue('--text-primary').trim() || '#ffffff',
                    secondary: style.getPropertyValue('--text-secondary').trim() || '#888888',
                    border: style.getPropertyValue('--border-light').trim() || 'rgba(255, 255, 255, 0.1)',
                    accent: style.getPropertyValue('--accent-primary').trim() || '#00ff88',
                    bg: style.getPropertyValue('--card-bg').trim() || 'rgba(26, 26, 26, 0.7)'
                };
            };

            let colors = getChartColors();
            let monthlyChart, categoryChart;

            const initCharts = () => {
                const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
                const monthlyLabels = <?php echo json_encode(array_map(function($item) {
                    return date('M Y', strtotime($item['month'] . '-01'));
                }, $monthlyData)); ?>;
                const monthlyValues = <?php echo json_encode(array_column($monthlyData, 'total')); ?>;
                
                monthlyChart = new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: monthlyLabels,
                        datasets: [{
                            label: 'Monthly Spending',
                            data: monthlyValues,
                            borderColor: colors.accent,
                            backgroundColor: 'rgba(0, 255, 136, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: colors.primary,
                                    font: { family: 'Poppins', size: 12 }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: colors.border },
                                ticks: {
                                    color: colors.secondary,
                                    font: { family: 'Poppins', size: 11 },
                                    callback: (v) => 'GHS ' + v
                                }
                            },
                            x: {
                                grid: { color: colors.border },
                                ticks: {
                                    color: colors.secondary,
                                    font: { family: 'Poppins', size: 11 }
                                }
                            }
                        }
                    }
                });
                
                const categoryCtx = document.getElementById('categoryChart').getContext('2d');
                const categoryLabels = <?php echo json_encode(array_column($categoryData, 'category')); ?>;
                const categoryValues = <?php echo json_encode(array_column($categoryData, 'total')); ?>;
                
                categoryChart = new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: categoryLabels.map(label => label.charAt(0).toUpperCase() + label.slice(1)),
                        datasets: [{
                            data: categoryValues,
                            backgroundColor: [
                                'rgba(0, 255, 136, 0.8)',
                                'rgba(77, 150, 255, 0.8)',
                                'rgba(255, 170, 0, 0.8)',
                                'rgba(168, 85, 247, 0.8)',
                                'rgba(255, 107, 107, 0.8)'
                            ],
                            borderWidth: 1,
                            borderColor: colors.bg
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: colors.primary,
                                    font: { family: 'Poppins', size: 11 },
                                    padding: 20
                                }
                            }
                        }
                    }
                });
            };

            // Initial call
            initCharts();

            // Handle Theme Changes
            const observer = new MutationObserver(() => {
                colors = getChartColors();
                
                // Update Line Chart
                monthlyChart.options.plugins.legend.labels.color = colors.primary;
                monthlyChart.options.scales.x.ticks.color = colors.secondary;
                monthlyChart.options.scales.x.grid.color = colors.border;
                monthlyChart.options.scales.y.ticks.color = colors.secondary;
                monthlyChart.options.scales.y.grid.color = colors.border;
                monthlyChart.update();

                // Update Doughnut Chart
                categoryChart.options.plugins.legend.labels.color = colors.primary;
                categoryChart.data.datasets[0].borderColor = colors.bg;
                categoryChart.update();
            });

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-theme']
            });
        });
    </script>
    
    <!-- Load Global JavaScript -->
    <script src="public/js/theme-manager.js"></script>
</body>
</html>