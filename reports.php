<?php
// reports.php - Student Financial Reports
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

// Any logged in user can access this, but they only see their own data.
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = $auth->getCurrentUser();
$userName = $user['username'];
$userRole = $user['role'] ?? 'student';
$userId = $user['id'];
$activePage = 'reports';

// Handle Action: Generate Personal Report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_report') {
    try {
        // Collect personal data
        $totalExpenses = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM elm_expenses WHERE user_id = $userId")->fetchColumn();
        $totalItems = $pdo->query("SELECT COUNT(*) FROM elm_expenses WHERE user_id = $userId")->fetchColumn();
        $topCategories = $pdo->query("SELECT category, SUM(amount) as total FROM elm_expenses WHERE user_id = $userId GROUP BY category ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $recentHighValue = $pdo->query("SELECT description, amount, expense_date FROM elm_expenses WHERE user_id = $userId ORDER BY expense_date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        $reportContent = [
            'summary' => [
                'total_amount' => $totalExpenses,
                'total_count' => $totalItems,
                'target_username' => $userName,
                'target_user_id' => $userId,
                'generated_at' => date('Y-m-d H:i:s')
            ],
            'categories' => $topCategories,
            'high_value_items' => $recentHighValue
        ];

        $stmt = $pdo->prepare("INSERT INTO elm_reports (report_type, report_data, generated_by) VALUES (?, ?, ?)");
        $stmt->execute(['Personal Summary', json_encode($reportContent), $userId]);
        
        $msg = "Your financial summary has been generated!";
        header("Location: reports.php?success=" . urlencode($msg));
        exit();
    } catch (Exception $e) {
        $error = "Error generating report: " . $e->getMessage();
    }
}

// Get user's reports
$stmtReports = $pdo->prepare("SELECT * FROM elm_reports WHERE generated_by = ? ORDER BY created_at DESC");
$stmtReports->execute([$userId]);
$myReports = $stmtReports->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My Financial Reports | Elm Finance';
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
            <div class="admin-header glass-card">
                <h1>📈 My Financial Reports</h1>
                <p>Generate and download your personal spending summaries and financial health reports.</p>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert success" style="margin-bottom: 2rem; padding: 1.5rem; border-radius: 12px; background: rgba(0, 255, 136, 0.1); color: var(--accent-primary); border: 1px solid var(--accent-primary);">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <div class="glass-card" style="padding: 2rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 255, 136, 0.02) 100%);">
                <div>
                    <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;">New Summary</h2>
                    <p style="opacity: 0.7;">Create a fresh analysis of your current month's spending.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="generate_report">
                    <button type="submit" class="btn" style="width: auto; padding: 1rem 2rem;">
                        <i class="fas fa-magic"></i> Generate My Report
                    </button>
                </form>
            </div>

            <div class="glass-card" style="padding: 2rem;">
                <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Report Archive</h2>
                
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Report Date</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($myReports)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 3rem; opacity: 0.5;">You haven't generated any reports yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($myReports as $report): ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?php echo date('M d, Y H:i', strtotime($report['created_at'])); ?></td>
                                        <td>
                                            <span style="color: var(--accent-primary);">Personal Summary</span>
                                        </td>
                                        <td>
                                            <a href="adminreportview.php?id=<?php echo $report['id']; ?>" class="admin-nav-btn" style="padding: 0.5rem 1rem; background: rgba(0, 255, 136, 0.1);">
                                                <i class="fas fa-eye"></i> View & Export PDF
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="public/js/theme-manager.js"></script>
</body>
</html>
