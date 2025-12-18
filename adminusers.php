<?php
// adminusers.php - User Management
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
$activePage = 'adminusers';

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetUserId = $_POST['user_id'] ?? 0;
    
    if ($action === 'update_role' && $targetUserId) {
        $newRole = $_POST['role'] ?? 'student';
        
        try {
            $stmt = $pdo->prepare("UPDATE elm_users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $targetUserId]);
            $message = "User role updated successfully!";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "Failed to update user role: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'delete_user' && $targetUserId) {
        try {
            // Don't allow self-deletion
            if ($targetUserId == $_SESSION['user_id']) {
                $message = "You cannot delete your own account!";
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare("DELETE FROM elm_users WHERE id = ?");
                $stmt->execute([$targetUserId]);
                $message = "User deleted successfully!";
                $messageType = 'success';
            }
        } catch (Exception $e) {
            $message = "Failed to delete user: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$universityFilter = $_GET['university'] ?? '';

// Build query
$whereClauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($roleFilter)) {
    $whereClauses[] = "role = ?";
    $params[] = $roleFilter;
}

if (!empty($universityFilter)) {
    $whereClauses[] = "university = ?";
    $params[] = $universityFilter;
}

$whereSQL = implode(' AND ', $whereClauses);

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM elm_users WHERE $whereSQL");
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();

// Pagination
$perPage = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$totalPages = ceil($totalUsers / $perPage);
$offset = ($page - 1) * $perPage;

// Get users
$usersStmt = $pdo->prepare("
    SELECT id, username, email, first_name, last_name, university, role, created_at, last_login 
    FROM elm_users 
    WHERE $whereSQL 
    ORDER BY created_at DESC 
    LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
$usersStmt->execute($params);
$usersList = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'User Management | Elm Finance Admin';
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
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
        <div class="admin-container">
            <!-- Header -->
            <div class="admin-header glass-card">
                <h1>👥 User Management</h1>
                <p>Manage users, update roles, and monitor system access</p>
            </div>

            <?php if ($message): ?>
                <div class="admin-alert <?php echo $messageType; ?>">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <strong><?php echo $message; ?></strong>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="user-filters glass-card">
                <form method="GET" action="" class="filter-row">
                    <div class="form-group" style="flex: 2;">
                        <label>Search Users</label>
                        <div class="input-wrapper">
                            <div class="input-icon">🔍</div>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Username, email, or name...">
                        </div>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Role</label>
                        <select name="role" class="input-select" style="width:100%; padding:1rem; border-radius:12px; background:var(--input-bg); border:1px solid var(--input-border); color:var(--text-primary);">
                            <option value="">All Roles</option>
                            <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Student</option>
                            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <button type="submit" class="btn btn-primary" style="padding:1.1rem">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="admin-table-container glass-card">
                <div class="admin-table-header">
                    <h2>
                        <i class="fas fa-users"></i> Registered Users (<?php echo $totalUsers; ?>)
                    </h2>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>University</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersList as $userRow): ?>
                        <tr>
                            <td>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($userRow['username']); ?></span>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);"><?php echo htmlspecialchars($userRow['email']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($userRow['university']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $userRow['role']; ?>">
                                    <?php echo ucfirst($userRow['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($userRow['created_at'])); ?></td>
                            <td><?php echo $userRow['last_login'] ? date('M d, H:i', strtotime($userRow['last_login'])) : 'Never'; ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $userRow['id']; ?>">
                                        <input type="hidden" name="action" value="update_role">
                                        <select name="role" onchange="this.form.submit()" class="action-btn" style="padding: 0.4rem;">
                                            <option value="student" <?php echo $userRow['role'] === 'student' ? 'selected' : ''; ?>>To Student</option>
                                            <option value="admin" <?php echo $userRow['role'] === 'admin' ? 'selected' : ''; ?>>To Admin</option>
                                        </select>
                                    </form>
                                    <?php if ($userRow['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                        <input type="hidden" name="user_id" value="<?php echo $userRow['id']; ?>">
                                        <input type="hidden" name="action" value="delete_user">
                                        <button type="submit" class="action-btn danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($usersList)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;">No users found matching your criteria.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>" 
                           class="page-btn <?php echo $page === $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="public/js/theme-manager.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('elm-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        });
    </script>
</body>
</html>