<?php
// adminreports.php - Financial Reports Management
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
$activePage = 'adminreports';

// Get users for selection (students only)
$allUsers = $pdo->query("SELECT id, username FROM elm_users WHERE role = 'student' ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Handle Action: Generate Report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_report') {
    try {
        $targetUserId = $_POST['target_user_id'] ?? 'all';
        $reportType = ($targetUserId === 'all') ? 'System Summary' : 'Student Detail';
        
        $whereClause = ($targetUserId === 'all') ? "WHERE 1=1" : "WHERE user_id = " . intval($targetUserId);
        
        // Collect data
        $totalExpenses = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM elm_expenses $whereClause")->fetchColumn();
        $totalItems = $pdo->query("SELECT COUNT(*) FROM elm_expenses $whereClause")->fetchColumn();
        $topCategories = $pdo->query("SELECT category, SUM(amount) as total FROM elm_expenses $whereClause GROUP BY category ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $recentHighValue = $pdo->query("SELECT description, amount, expense_date FROM elm_expenses $whereClause " . ($targetUserId === 'all' ? "AND amount > 500" : "") . " ORDER BY expense_date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        $targetUserName = 'All Users';
        if ($targetUserId !== 'all') {
            $stmtUser = $pdo->prepare("SELECT username FROM elm_users WHERE id = ?");
            $stmtUser->execute([$targetUserId]);
            $targetUserName = $stmtUser->fetchColumn();
        }

        $reportContent = [
            'summary' => [
                'total_amount' => $totalExpenses,
                'total_count' => $totalItems,
                'target_username' => $targetUserName,
                'target_user_id' => $targetUserId,
                'generated_at' => date('Y-m-d H:i:s')
            ],
            'categories' => $topCategories,
            'high_value_items' => $recentHighValue
        ];

        $stmt = $pdo->prepare("INSERT INTO elm_reports (report_type, report_data, generated_by) VALUES (?, ?, ?)");
        $stmt->execute([$reportType, json_encode($reportContent), $user['id']]);
        
        $msg = "Report for $targetUserName generated successfully!";
        header("Location: adminreports.php?success=" . urlencode($msg));
        exit();
    } catch (Exception $e) {
        $error = "Error generating report: " . $e->getMessage();
    }
}

// Handle Action: Export to CSV
if (isset($_GET['export']) && $_GET['export'] > 0) {
    $reportId = $_GET['export'];
    $stmt = $pdo->prepare("SELECT report_data, report_type FROM elm_reports WHERE id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if ($report) {
        $data = json_decode($report['report_data'], true);
        
        $filename = "report_" . $reportId . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $report['report_type'] . '_' . $reportId . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header info
        fputcsv($output, ['Report ID', $reportId]);
        fputcsv($output, ['Report Type', $report['report_type']]);
        fputcsv($output, ['Generated At', $data['summary']['generated_at']]);
        fputcsv($output, []);
        
        // Summary
        fputcsv($output, ['Financial Summary']);
        fputcsv($output, ['Total Amount', 'Total Items']);
        fputcsv($output, [$data['summary']['total_amount'], $data['summary']['total_count']]);
        fputcsv($output, []);
        
        // Categories
        fputcsv($output, ['Top Categories']);
        fputcsv($output, ['Category', 'Total Amount']);
        foreach ($data['categories'] as $cat) {
            fputcsv($output, [$cat['category'], $cat['total']]);
        }
        fputcsv($output, []);
        
        // High Value
        if (!empty($data['high_value_items'])) {
            fputcsv($output, ['Recent High Value Items (>500)']);
            fputcsv($output, ['Description', 'Amount', 'Date']);
            foreach ($data['high_value_items'] as $item) {
                fputcsv($output, [$item['description'], $item['amount'], $item['expense_date']]);
            }
        }
        
        fclose($output);
        exit();
    }
}

// Get all reports
$reports = $pdo->query("SELECT r.*, u.username FROM elm_reports r JOIN elm_users u ON r.generated_by = u.id ORDER BY r.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Financial Reports | Elm Finance';
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
                <h1>📊 Financial Reports</h1>
                <p>Generate, view, and export detailed financial summaries for the entire system.</p>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert success" style="margin-bottom: 2rem; padding: 1.5rem; border-radius: 12px; background: rgba(0, 255, 136, 0.1); color: var(--accent-primary); border: 1px solid var(--accent-primary);">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

                <div class="glass-card" style="padding: 2rem; margin-bottom: 2rem; background: rgba(0, 255, 136, 0.05);">
                    <form method="POST" style="display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: flex-end;">
                        <input type="hidden" name="action" value="generate_report">
                        <div style="flex: 1; min-width: 250px;">
                            <label style="display: block; margin-bottom: 0.5rem; opacity: 0.8;">Target Audience</label>
                            <select name="target_user_id" class="form-select">
                                <option value="all">System Wide Summary</option>
                                <optgroup label="Specific Students">
                                    <?php foreach ($allUsers as $u): ?>
                                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        <button type="submit" class="btn" style="width: auto; padding: 1rem 2rem;">
                            <i class="fas fa-magic"></i> Generate Report
                        </button>
                    </form>
                </div>

                <div class="glass-card" style="padding: 2rem; margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h2 style="font-size: 1.8rem; font-weight: 600;">Report Archive</h2>
                    </div>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Report Type</th>
                                <th>Generated By</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 3rem;">No reports generated yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td>#<?php echo $report['id']; ?></td>
                                        <td>
                                            <span style="font-weight: 500; color: var(--accent-primary);">
                                                <?php echo htmlspecialchars($report['report_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($report['username']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($report['created_at'])); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="adminreportview.php?id=<?php echo $report['id']; ?>" class="admin-nav-btn" style="padding: 0.5rem 1rem; font-size: 0.9rem; background: rgba(0, 255, 136, 0.2);">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="?export=<?php echo $report['id']; ?>" class="admin-nav-btn" style="padding: 0.5rem 1rem; font-size: 0.9rem; background: rgba(0, 255, 136, 0.1);">
                                                    <i class="fas fa-file-csv"></i> CSV
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="admin-stats-grid">
                <div class="stat-card glass-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: rgba(0, 255, 136, 0.2); color: var(--accent-primary);">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo count($reports); ?></div>
                    <div class="stat-label">Total Reports</div>
                </div>
                
                <div class="stat-card glass-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: rgba(0, 255, 136, 0.2); color: var(--accent-primary);">
                            <i class="fas fa-history"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo count($reports) > 0 ? date('M d', strtotime($reports[0]['created_at'])) : 'None'; ?></div>
                    <div class="stat-label">Last Generated</div>
                </div>
            </div>
        </div>
    </div>

    <script src="public/js/theme-manager.js"></script>
</body>
</html>
