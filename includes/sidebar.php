<?php
// Universal sidebar

// Required variables (set in each page before including):
// $activePage, $userName, $userRole

// Default values if not set
$activePage = $activePage ?? '';
$userName = $userName ?? 'User';
$userRole = $userRole ?? 'student';

// Get today's expenses for badge (only if needed for sidebar)
$todayExpenses = $todayExpenses ?? 0;
$totalGoals = $totalGoals ?? 0;

// Check admin
$isAdmin = false;
if (isset($_SESSION['user_role'])) {
    $isAdmin = ($_SESSION['user_role'] === 'admin');
} elseif (isset($auth) && method_exists($auth, 'isAdmin')) {
    $isAdmin = $auth->isAdmin();
}
?>
<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Menu -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo">
            🌿
        </div>
        <h2>Elm Finance</h2>
    </div>
    
    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <?php echo strtoupper(substr($userName, 0, 2)); ?>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-username"><?php echo htmlspecialchars($userName); ?></div>
            <div class="sidebar-user-role">
                <?php echo ucfirst($userRole); ?>
                <?php if ($isAdmin): ?>
                <span style="color: var(--accent-primary);">(Admin)</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <div class="menu-section">
            <div class="section-title">Navigation</div>
            <?php if ($isAdmin): ?>
                <a href="admin.php" class="menu-item <?php echo $activePage === 'admin' ? 'active' : ''; ?>">
                    <i class="fas fa-crown"></i>
                    <span>Dashboard</span>
                </a>
                <a href="adminusers.php" class="menu-item <?php echo $activePage === 'adminusers' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </a>
                <a href="adminreports.php" class="menu-item <?php echo $activePage === 'adminreports' ? 'active' : ''; ?>">
                    <i class="fas fa-file-contract"></i>
                    <span>Financial Reports</span>
                </a>
                <a href="adminsecurity.php" class="menu-item <?php echo $activePage === 'adminsecurity' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security Dashboard</span>
                </a>
            <?php else: ?>
                <a href="dashboard.php" class="menu-item <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>My Dashboard</span>
                </a>
                <a href="expenses.php" class="menu-item <?php echo $activePage === 'expenses' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transactions</span>
                </a>
                <a href="budgets.php" class="menu-item <?php echo $activePage === 'budgets' ? 'active' : ''; ?>">
                    <i class="fas fa-wallet"></i>
                    <span>Budgets</span>
                </a>
                <a href="goals.php" class="menu-item <?php echo $activePage === 'goals' ? 'active' : ''; ?>">
                    <i class="fas fa-bullseye"></i>
                    <span>Goals</span>
                </a>
                <a href="analytics.php" class="menu-item <?php echo $activePage === 'analytics' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="reports.php" class="menu-item <?php echo $activePage === 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>My Reports</span>
                </a>
                <a href="calculator.php" class="menu-item <?php echo $activePage === 'calculator' ? 'active' : ''; ?>">
                    <i class="fas fa-calculator"></i>
                    <span>Calculator</span>
                </a>
                <a href="tips.php" class="menu-item <?php echo $activePage === 'tips' ? 'active' : ''; ?>">
                    <i class="fas fa-lightbulb"></i>
                    <span>Financial Tips</span>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="menu-section">
            <div class="section-title">Account</div>
            <a href="profile.php" class="menu-item <?php echo $activePage === 'profile' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </div>
    </div>
    
    <div class="sidebar-footer">
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</nav>

<!-- Include JavaScript for sidebar functionality -->
<script>
// Toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        sidebarOverlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
    }
    
    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);
    
    // Auto-open sidebar on desktop
    if (window.innerWidth >= 1024) {
        sidebar.classList.add('active');
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 1024 && 
            !sidebar.contains(e.target) && 
            !mobileMenuBtn.contains(e.target) &&
            sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            sidebar.classList.add('active');
            if (sidebarOverlay) {
                sidebarOverlay.style.display = 'none';
            }
        } else {
            sidebar.classList.remove('active');
        }
    });
});
</script>