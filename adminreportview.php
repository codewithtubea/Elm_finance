<?php
// adminreportview.php - Detailed Report View & PDF Export
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

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
$isAdmin = ($user['role'] === 'admin');

$reportId = $_GET['id'] ?? null;
if (!$reportId) {
    header('Location: adminreports.php');
    exit();
}

$stmt = $pdo->prepare("SELECT r.*, u.username as creator FROM elm_reports r JOIN elm_users u ON r.generated_by = u.id WHERE r.id = ?");
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    die("Report not found.");
}

// Security Check: Students can only view reports generated for them or by them
if (!$isAdmin && $report['generated_by'] != $user['id']) {
    die("Access denied. You can only view your own reports.");
}

$reportData = json_decode($report['report_data'], true);
$targetName = $reportData['summary']['target_username'] ?? 'All Users';
$pageTitle = "Financial Report - " . $targetName;
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        :root {
            --print-primary: #008f4c;
            --print-secondary: #005a30;
        }

        body {
            background: #f8fafc;
            color: #1e293b;
            font-family: 'Poppins', sans-serif;
        }

        .report-actions {
            max-width: 900px;
            margin: 2rem auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .report-page {
            max-width: 900px;
            margin: 0 auto 3rem auto;
            background: white;
            padding: 4rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 2rem;
            margin-bottom: 3rem;
        }

        .brand-section h1 {
            color: var(--print-primary);
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0;
        }

        .brand-section p {
            margin: 0.2rem 0 0 0;
            opacity: 0.6;
            font-size: 0.9rem;
        }

        .report-info {
            text-align: right;
        }

        .report-info h2 {
            font-size: 1.2rem;
            margin: 0 0 0.5rem 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .summary-item {
            background: #f1f5f9;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }

        .summary-item .label {
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .summary-item .value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--print-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3rem;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #64748b;
            font-weight: 600;
            font-size: 0.9rem;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.95rem;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.8rem;
            background: #e2e8f0;
        }

        .footer-note {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        @media print {
            body { background: white; padding: 0; }
            .report-actions, .main-content-sidebar { display: none !important; }
            .report-page { box-shadow: none; padding: 0; margin: 0; max-width: 100%; }
            .btn-print { display: none; }
        }

        .btn-print {
            padding: 0.8rem 2rem;
            background: var(--print-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-print:hover {
            background: var(--print-secondary);
            transform: translateY(-2px);
        }

        .btn-back {
            color: #64748b;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="report-actions">
        <a href="<?php echo $isAdmin ? 'adminreports.php' : 'reports.php'; ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Archive
        </a>
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-file-pdf"></i> Save as PDF
        </button>
    </div>

    <div class="report-page">
        <div class="report-header">
            <div class="brand-section">
                <h1>ELM FINANCE</h1>
                <p>Premium Administrative Oversight System</p>
            </div>
            <div class="report-info">
                <h2><?php echo htmlspecialchars($report['report_type']); ?></h2>
                <div>Report ID: #<?php echo $reportId; ?></div>
                <div>Date: <?php echo date('M d, Y', strtotime($report['created_at'])); ?></div>
            </div>
        </div>

        <div class="section-title">
            <i class="fas fa-info-circle"></i> Basic Information
        </div>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Target Audience</div>
                <div class="value"><?php echo htmlspecialchars($targetName); ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Total Expenditure</div>
                <div class="value">GHS <?php echo number_format($reportData['summary']['total_amount'], 2); ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Transaction Count</div>
                <div class="value"><?php echo $reportData['summary']['total_count']; ?></div>
            </div>
        </div>

        <div class="section-title">
            <i class="fas fa-chart-pie"></i> Spending by Category
        </div>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Total Amount (GHS)</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['categories'] as $cat): 
                    $pct = $reportData['summary']['total_amount'] > 0 ? ($cat['total'] / $reportData['summary']['total_amount']) * 100 : 0;
                ?>
                <tr>
                    <td><strong><?php echo ucfirst($cat['category']); ?></strong></td>
                    <td><?php echo number_format($cat['total'], 2); ?></td>
                    <td><?php echo round($pct, 1); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="section-title">
            <i class="fas fa-list-ul"></i> Detailed Transactions
        </div>
        <p style="margin-bottom: 1rem; opacity: 0.7; font-size: 0.9rem;">Displaying the top 10 most relevant transactions for this period.</p>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount (GHS)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData['high_value_items'] as $item): ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($item['expense_date'])); ?></td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td style="font-weight: 600; color: #b91c1c;">-<?php echo number_format($item['amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer-note">
            <p>Generated by <?php echo htmlspecialchars($report['creator']); ?> on <?php echo $reportData['summary']['generated_at']; ?>.</p>
            <p>Confidentially generated by Elm Finance Administration. &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>

    <script>
        // Set dynamic filename for browsers that support it on print
        window.onbeforeprint = function() {
            document.title = "Financial_Report_<?php echo str_replace(' ', '_', $targetName); ?>_<?php echo date('Y-m-d'); ?>";
        };
    </script>
</body>
</html>
