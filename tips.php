<?php
// tips.php - CLEAN FIXED VERSION
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
$activePage = 'tips';

// Get today's expenses for sidebar
$todayExpenses = getTodayExpenses($pdo, $userId);

$pageTitle = 'Student Tips | Elm Finance';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <!-- Include Universal Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="container">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Student Finance Tips</h1>
                <div class="top-actions">
                    <button class="theme-toggle" id="themeToggle">
                        <span id="themeIcon">🌙</span>
                    </button>
                </div>
            </div>
            
            <!-- Stats Banner -->
            <div class="stats-banner">
                <h2 style="font-size: 2.8rem; margin-bottom: 1rem;">Smart Saving = More Fun!</h2>
                <p style="font-size: 1.6rem; margin-bottom: 2rem;">Follow these tips to save money without sacrificing your student life</p>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value">60%</div>
                        <div class="stat-label">Save on Food</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">70%</div>
                        <div class="stat-label">Save on Transport</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">50%</div>
                        <div class="stat-label">Save on Textbooks</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">GHS 500+</div>
                        <div class="stat-label">Monthly Savings</div>
                    </div>
                </div>
            </div>
            
            <!-- Tips Grid -->
            <div class="tips-grid">
                <!-- Food Tips -->
                <div class="tip-card fade-in-up">
                    <div class="tip-icon food">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="tip-title">Food & Dining</div>
                    <div class="tip-category">Biggest Savings Area</div>
                    <div class="tip-content">
                        Cook meals in bulk (meal prep Sunday). Use campus meal plans. 
                        Share grocery costs with roommates. Drink water instead of soda.
                        Use student discounts at local restaurants.
                    </div>
                    <div class="tip-savings">Save: GHS 300-500/month</div>
                </div>
                
                <!-- Transport Tips -->
                <div class="tip-card fade-in-up delay-1">
                    <div class="tip-icon transport">
                        <i class="fas fa-bus"></i>
                    </div>
                    <div class="tip-title">Transportation</div>
                    <div class="tip-category">Smart Moving</div>
                    <div class="tip-content">
                        Use student transport cards (70% cheaper). Walk or bike to campus. 
                        Carpool with classmates (split fuel). Plan trips together.
                        Avoid Uber/Taxi for daily commute.
                    </div>
                    <div class="tip-savings">Save: GHS 150-250/month</div>
                </div>
                
                <!-- Textbook Tips -->
                <div class="tip-card fade-in-up delay-2">
                    <div class="tip-icon books">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="tip-title">Textbooks</div>
                    <div class="tip-category">Academic Savings</div>
                    <div class="tip-content">
                        Rent instead of buy. Use library copies. Buy used books from seniors.
                        Share textbooks with classmates. Use PDF versions when available.
                        Sell books after semester ends.
                    </div>
                    <div class="tip-savings">Save: GHS 400-800/semester</div>
                </div>
            </div>
            
            <!-- More Tips Grid -->
            <div class="tips-grid">
                <!-- Housing Tips -->
                <div class="tip-card fade-in-up">
                    <div class="tip-icon housing">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="tip-title">Housing</div>
                    <div class="tip-category">Live Smart</div>
                    <div class="tip-content">
                        Get roommates (split rent & utilities). Choose campus housing.
                        Negotiate rent with landlords. Use energy-efficient bulbs.
                        Turn off appliances when not in use. Split WiFi costs.
                    </div>
                    <div class="tip-savings">Save: GHS 200-400/month</div>
                </div>
                
                <!-- Income Tips -->
                <div class="tip-card fade-in-up delay-1">
                    <div class="tip-icon income">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="tip-title">Extra Income</div>
                    <div class="tip-category">Earn More</div>
                    <div class="tip-content">
                        Tutor other students. Freelance online (writing, design, coding).
                        Campus jobs (library, cafeteria). Sell unused items.
                        Participate in paid research studies. Internships with stipends.
                    </div>
                    <div class="tip-savings">Earn: GHS 500-1500/month</div>
                </div>
                
                <!-- Savings Tips -->
                <div class="tip-card fade-in-up delay-2">
                    <div class="tip-icon savings">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <div class="tip-title">Smart Saving</div>
                    <div class="tip-category">Build Wealth</div>
                    <div class="tip-content">
                        Save 20% of any income automatically. Use separate savings account.
                        Set specific goals (phone, trip, laptop). Use round-up apps.
                        Avoid impulse purchases (wait 24 hours). Track every expense.
                    </div>
                    <div class="tip-savings">Goal: GHS 1000+/month saved</div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="calculator.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="action-title">Use Calculator</div>
                    <div class="action-desc">
                        Check if you can afford something or calculate savings timeline
                    </div>
                </a>
                
                <a href="budgets.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="action-title">Set Budgets</div>
                    <div class="action-desc">
                        Create monthly budgets to track your spending limits
                    </div>
                </a>
                
                <a href="goals.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div class="action-title">Set Goals</div>
                    <div class="action-desc">
                        Create savings goals for things you really want
                    </div>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Load Global JavaScript -->
    <script src="public/js/theme-manager.js"></script>
</body>
</html>