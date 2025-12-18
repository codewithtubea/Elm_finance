<?php
// goals.php - Savings Goals Management
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Initialize database and auth
try {
    $db = new Database();
    $pdo = $db->getPDO();
    $auth = new Auth($pdo);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get current user data
$user = $auth->getCurrentUser();
if (!$user) {
    $auth->logout();
    header('Location: login.php');
    exit();
}

$userId = $user['id'];
$userName = $user['username'];

// Initialize variables with default values
$message = '';
$success = false;
$totalGoals = 0;
$activeGoals = 0;
$completedGoals = 0;
$totalTarget = 0;
$totalSaved = 0;
$overallProgress = 0;
$goals = [];

// Check if elm_goals table exists
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'elm_goals'");
    $tableExists = ($checkTable->rowCount() > 0);
    
    if (!$tableExists) {
        // Create the table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS elm_goals (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                goal_name VARCHAR(100) NOT NULL,
                target_amount DECIMAL(10,2) NOT NULL,
                current_amount DECIMAL(10,2) DEFAULT 0.00,
                deadline DATE NOT NULL,
                category VARCHAR(50),
                description TEXT,
                status ENUM('active', 'completed', 'failed') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES elm_users(id) ON DELETE CASCADE
            )
        ");
    }
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_goal') {
            // Add new goal
            $goalName = $_POST['goal_name'] ?? '';
            $targetAmount = $_POST['target_amount'] ?? 0;
            $deadline = $_POST['deadline'] ?? '';
            $category = $_POST['category'] ?? 'other';
            $description = $_POST['description'] ?? '';
            
            if ($goalName && $targetAmount > 0 && $deadline) {
                $insertStmt = $pdo->prepare("
                    INSERT INTO elm_goals 
                    (user_id, goal_name, target_amount, deadline, category, description) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insertStmt->execute([$userId, $goalName, $targetAmount, $deadline, $category, $description]);
                $message = "Goal created successfully!";
                $success = true;
                
                // Redirect to avoid form resubmission
                header("Location: goals.php?success=" . urlencode($message));
                exit();
            } else {
                $message = "Please fill all required fields correctly.";
                $success = false;
            }
        }
        elseif ($action === 'update_progress') {
            // Update goal progress
            $goalId = $_POST['goal_id'] ?? 0;
            $amountToAdd = $_POST['amount_to_add'] ?? 0;
            
            if ($goalId && $amountToAdd > 0) {
                // Get current amount
                $checkStmt = $pdo->prepare("
                    SELECT current_amount, target_amount 
                    FROM elm_goals 
                    WHERE id = ? AND user_id = ?
                ");
                $checkStmt->execute([$goalId, $userId]);
                $goal = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($goal) {
                    $newAmount = $goal['current_amount'] + $amountToAdd;
                    $status = $newAmount >= $goal['target_amount'] ? 'completed' : 'active';
                    
                    $updateStmt = $pdo->prepare("
                        UPDATE elm_goals 
                        SET current_amount = ?, status = ?
                        WHERE id = ? AND user_id = ?
                    ");
                    $updateStmt->execute([$newAmount, $status, $goalId, $userId]);
                    $message = "Progress updated successfully!";
                    $success = true;
                    
                    header("Location: goals.php?success=" . urlencode($message));
                    exit();
                }
            }
        }
        elseif ($action === 'delete_goal') {
            // Delete goal
            $goalId = $_POST['goal_id'] ?? 0;
            if ($goalId) {
                $deleteStmt = $pdo->prepare("
                    DELETE FROM elm_goals 
                    WHERE id = ? AND user_id = ?
                ");
                $deleteStmt->execute([$goalId, $userId]);
                $message = "Goal deleted successfully!";
                $success = true;
                
                header("Location: goals.php?success=" . urlencode($message));
                exit();
            }
        }
        elseif ($action === 'edit_goal') {
            // Edit goal
            $goalId = $_POST['goal_id'] ?? 0;
            $goalName = $_POST['goal_name'] ?? '';
            $targetAmount = $_POST['target_amount'] ?? 0;
            $deadline = $_POST['deadline'] ?? '';
            $category = $_POST['category'] ?? 'other';
            $description = $_POST['description'] ?? '';
            
            if ($goalId && $goalName && $targetAmount > 0 && $deadline) {
                // Check if goal needs status update
                $checkStmt = $pdo->prepare("
                    SELECT current_amount 
                    FROM elm_goals 
                    WHERE id = ? AND user_id = ?
                ");
                $checkStmt->execute([$goalId, $userId]);
                $goal = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                $status = ($goal['current_amount'] >= $targetAmount) ? 'completed' : 'active';
                
                $updateStmt = $pdo->prepare("
                    UPDATE elm_goals 
                    SET goal_name = ?, target_amount = ?, deadline = ?, 
                        category = ?, description = ?, status = ?
                    WHERE id = ? AND user_id = ?
                ");
                $updateStmt->execute([$goalName, $targetAmount, $deadline, $category, $description, $status, $goalId, $userId]);
                $message = "Goal updated successfully!";
                $success = true;
                
                header("Location: goals.php?success=" . urlencode($message));
                exit();
            }
        }
    }
    
    // Check for success message in URL
    if (isset($_GET['success'])) {
        $message = $_GET['success'];
        $success = true;
    }
    
    // Get user's goals
    $goalsStmt = $pdo->prepare("
        SELECT 
            *,
            DATEDIFF(deadline, CURDATE()) as days_remaining,
            (current_amount / target_amount) * 100 as progress_percentage
        FROM elm_goals 
        WHERE user_id = ? 
        ORDER BY 
            CASE status 
                WHEN 'active' THEN 1
                WHEN 'completed' THEN 2
                WHEN 'failed' THEN 3
            END,
            deadline ASC
    ");
    $goalsStmt->execute([$userId]);
    $goals = $goalsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate goal statistics
    $totalGoals = count($goals);
    $activeGoals = 0;
    $completedGoals = 0;
    $totalTarget = 0;
    $totalSaved = 0;
    
    foreach ($goals as $goal) {
        $totalTarget += $goal['target_amount'];
        $totalSaved += $goal['current_amount'];
        if ($goal['status'] === 'active') {
            $activeGoals++;
        } elseif ($goal['status'] === 'completed') {
            $completedGoals++;
        }
    }
    
    $overallProgress = $totalTarget > 0 ? ($totalSaved / $totalTarget) * 100 : 0;
    
} catch (Exception $e) {
    error_log("Goals page error: " . $e->getMessage());
    $message = "An error occurred while loading goals. Please try again.";
    $success = false;
}

// Page title
$pageTitle = 'Goals | Elm Finance';
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
    <style>
        /* Quick inline styles for buttons */
        .btn {
            padding: 1rem 2rem;
            border-radius: 12px;
            border: none;
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--accent-primary);
            color: #0a0a0a;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 255, 136, 0.3);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--accent-primary);
            border: 1.5px solid var(--accent-primary);
        }
        
        .btn-secondary:hover {
            background: rgba(0, 255, 136, 0.1);
        }
        
        .btn-danger {
            background: rgba(255, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(255, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            background: var(--error);
            color: white;
        }
        
        .alert {
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border: 1px solid;
            backdrop-filter: blur(10px);
        }
        
        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            color: var(--success);
            border-color: rgba(0, 255, 136, 0.3);
        }
        
        .alert-error {
            background: rgba(255, 68, 68, 0.1);
            color: var(--error);
            border-color: rgba(255, 68, 68, 0.3);
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        
        .modal-content {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        
        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="goals-container">
            <!-- Header -->
            <div class="goals-header">
                <h1>
                    <i class="fas fa-bullseye"></i> Savings Goals
                </h1>
                <div class="top-actions">
                    <button class="theme-toggle" id="themeToggle">
                        <span id="themeIcon">🌙</span>
                    </button>
                </div>
            </div>
            
            <!-- Success/Error Message -->
            <?php if (!empty($message)): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
                <strong style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-<?php echo $success ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $success ? 'Success' : 'Error'; ?>
                </strong>
                <p style="margin-top: 0.5rem;"><?php echo htmlspecialchars($message); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Stats Overview -->
            <div class="goals-stats-grid">
                <!-- Overall Progress -->
                <div class="goal-stat-card glass-card">
                    <div class="circular-progress">
                        <svg width="100" height="100" viewBox="0 0 100 100">
                            <circle class="circular-bg" cx="50" cy="50" r="45"></circle>
                            <circle class="circular-fill" cx="50" cy="50" r="45" 
                                    stroke-dasharray="<?php echo $overallProgress * 2.827; ?> 282.7"></circle>
                        </svg>
                        <div class="circular-text"><?php echo round($overallProgress); ?>%</div>
                    </div>
                    <div class="goal-stat-label">Overall Progress</div>
                </div>
                
                <!-- Total Goals -->
                <div class="goal-stat-card glass-card">
                    <div class="goal-stat-value" style="color: var(--accent-primary);">
                        <?php echo $totalGoals; ?>
                    </div>
                    <div class="goal-stat-label">Total Goals</div>
                </div>
                
                <!-- Active Goals -->
                <div class="goal-stat-card glass-card">
                    <div class="goal-stat-value" style="color: var(--success);">
                        <?php echo $activeGoals; ?>
                    </div>
                    <div class="goal-stat-label">Active Goals</div>
                </div>
                
                <!-- Total Saved -->
                <div class="goal-stat-card glass-card">
                    <div class="goal-stat-value" style="color: var(--info);">
                        GHS <?php echo number_format($totalSaved, 2); ?>
                    </div>
                    <div class="goal-stat-label">Total Saved</div>
                </div>
            </div>
            
            <!-- Create Goal Form -->
            <div class="create-goal-form glass-card">
                <h2>
                    <i class="fas fa-plus-circle"></i> Create New Goal
                </h2>
                <form method="POST" class="form-grid">
                    <input type="hidden" name="action" value="add_goal">
                    
                    <div class="form-group">
                        <label class="form-label">Goal Name</label>
                        <input type="text" name="goal_name" class="form-input" 
                               placeholder="e.g., New Laptop, Vacation Fund" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Target Amount (GHS)</label>
                        <input type="number" name="target_amount" class="form-input" 
                               min="0.01" step="0.01" placeholder="0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Deadline</label>
                        <input type="date" name="deadline" class="form-input" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="electronics">Electronics</option>
                            <option value="education">Education</option>
                            <option value="travel">Travel</option>
                            <option value="savings">General Savings</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" class="form-textarea" 
                                  placeholder="Describe your goal..." rows="2"></textarea>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1; text-align: center;">
                        <button type="submit" class="btn btn-primary" style="padding: 1rem 2.5rem;">
                            <i class="fas fa-check"></i> Create Goal
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Goals Grid -->
            <div class="goals-grid">
                <?php if (!empty($goals)): ?>
                    <?php foreach ($goals as $goal): 
                        $progress = $goal['progress_percentage'] ?? 0;
                        $daysRemaining = $goal['days_remaining'] ?? 0;
                        $statusClass = 'status-' . $goal['status'];
                        $cardClass = $goal['status'] === 'completed' ? 'completed' : ($goal['status'] === 'failed' ? 'failed' : '');
                    ?>
                    <div class="goal-card glass-card <?php echo $cardClass; ?>">
                        <div class="card-content">
                            <div class="goal-header">
                                <div>
                                    <div class="goal-title"><?php echo htmlspecialchars($goal['goal_name']); ?></div>
                                    <span class="goal-category"><?php echo ucfirst($goal['category']); ?></span>
                                </div>
                                <span class="goal-status <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($goal['status']); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($goal['description'])): ?>
                            <p style="color: var(--text-secondary); margin: 1rem 0;">
                                <?php echo htmlspecialchars($goal['description']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="goal-progress">
                                <div class="progress-info">
                                    <span>GHS <?php echo number_format($goal['current_amount'], 2); ?></span>
                                    <span>GHS <?php echo number_format($goal['target_amount'], 2); ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min($progress, 100); ?>%;"></div>
                                    <div class="progress-percentage"><?php echo round($progress, 1); ?>%</div>
                                </div>
                            </div>
                            
                            <div class="goal-details-grid">
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Deadline: <?php echo date('M d, Y', strtotime($goal['deadline'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <span>
                                        <?php if ($daysRemaining > 0): ?>
                                            <?php echo $daysRemaining; ?> days left
                                        <?php elseif ($daysRemaining == 0): ?>
                                            Today!
                                        <?php else: ?>
                                            <?php echo abs($daysRemaining); ?> days ago
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-wallet"></i>
                                    <span>Left: GHS <?php echo number_format($goal['target_amount'] - $goal['current_amount'], 2); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar-plus"></i>
                                    <span>Created: <?php echo date('M d, Y', strtotime($goal['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="goal-actions">
                                <?php if ($goal['status'] === 'active'): ?>
                                <button class="action-btn" onclick="addProgress(<?php echo $goal['id']; ?>)">
                                    <i class="fas fa-plus"></i> Add Money
                                </button>
                                <?php endif; ?>
                                <button class="action-btn" onclick="editGoal(
                                    '<?php echo $goal['id']; ?>',
                                    '<?php echo htmlspecialchars($goal['goal_name'], ENT_QUOTES); ?>',
                                    '<?php echo $goal['target_amount']; ?>',
                                    '<?php echo $goal['deadline']; ?>',
                                    '<?php echo $goal['category']; ?>',
                                    `<?php echo htmlspecialchars($goal['description'] ?? '', ENT_QUOTES); ?>`
                                )">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn delete" onclick="deleteGoal(<?php echo $goal['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <div class="empty-state-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="empty-state-title">No Goals Yet</div>
                        <div class="empty-state-description">
                            Create your first savings goal above to start tracking your progress!
                        </div>
                        <button class="btn btn-primary" onclick="document.querySelector('form').scrollIntoView({behavior: 'smooth'})">
                            <i class="fas fa-plus"></i> Create Your First Goal
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modals -->
    <!-- Add Progress Modal -->
    <div class="modal-overlay" id="progressModal">
        <div class="modal-content">
            <div class="card-content">
                <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                    <i class="fas fa-plus"></i> Add Progress
                </h2>
                <form method="POST" id="progressForm">
                    <input type="hidden" name="action" value="update_progress">
                    <input type="hidden" name="goal_id" id="progressGoalId">
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label class="form-label">Amount to Add (GHS)</label>
                        <input type="number" name="amount_to_add" id="progressAmount" 
                               class="form-input" min="0.01" step="0.01" 
                               placeholder="0.00" required>
                        <small style="color: var(--text-secondary); font-size: 1rem; display: block; margin-top: 0.5rem;">
                            This will be added to your current savings for this goal.
                        </small>
                    </div>
                    
                    <div style="display: flex; gap: 0.8rem;">
                        <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i> Add to Goal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Goal Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <div class="card-content">
                <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem;">
                    <i class="fas fa-edit"></i> Edit Goal
                </h2>
                <form method="POST" id="editGoalForm">
                    <input type="hidden" name="action" value="edit_goal">
                    <input type="hidden" name="goal_id" id="editGoalId">
                    
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Goal Name</label>
                        <input type="text" name="goal_name" id="editGoalName" 
                               class="form-input" required>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Target Amount (GHS)</label>
                        <input type="number" name="target_amount" id="editTargetAmount" 
                               class="form-input" min="0.01" step="0.01" required>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Deadline</label>
                        <input type="date" name="deadline" id="editDeadline" 
                               class="form-input" required>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Category</label>
                        <select name="category" id="editCategory" class="form-select">
                            <option value="electronics">Electronics</option>
                            <option value="education">Education</option>
                            <option value="travel">Travel</option>
                            <option value="savings">General Savings</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" id="editDescription" 
                                  class="form-textarea" rows="3"></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 0.8rem;">
                        <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <div class="card-content">
                <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem; color: var(--error);">
                    <i class="fas fa-exclamation-triangle"></i> Delete Goal
                </h2>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 1.1rem;">
                    Are you sure you want to delete this goal? This action cannot be undone.
                </p>
                <form method="POST" id="deleteGoalForm">
                    <input type="hidden" name="action" value="delete_goal">
                    <input type="hidden" name="goal_id" id="deleteGoalId">
                    
                    <div style="display: flex; gap: 0.8rem;">
                        <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-danger" style="flex: 1;">
                            <i class="fas fa-trash"></i> Delete Goal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script>
        // Theme toggle
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('elm-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            const themeIcon = document.getElementById('themeIcon');
            if (themeIcon) {
                themeIcon.textContent = savedTheme === 'dark' ? '🌙' : '☀️';
            }
            
            // Theme toggle event
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const html = document.documentElement;
                    const currentTheme = html.getAttribute('data-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    const themeIcon = document.getElementById('themeIcon');
                    
                    html.setAttribute('data-theme', newTheme);
                    if (themeIcon) {
                        themeIcon.textContent = newTheme === 'dark' ? '🌙' : '☀️';
                    }
                    localStorage.setItem('elm-theme', newTheme);
                });
            }
            
            // Set min date for deadline inputs
            const today = new Date().toISOString().split('T')[0];
            const deadlineInputs = document.querySelectorAll('input[name="deadline"], #editDeadline');
            deadlineInputs.forEach(input => {
                if (input) input.min = today;
            });
        });
        
        // Modal functions
        function addProgress(goalId) {
            document.getElementById('progressGoalId').value = goalId;
            document.getElementById('progressAmount').value = '';
            document.getElementById('progressAmount').focus();
            openModal('progressModal');
        }
        
        function editGoal(id, name, target, deadline, category, description) {
            document.getElementById('editGoalId').value = id;
            document.getElementById('editGoalName').value = name;
            document.getElementById('editTargetAmount').value = target;
            document.getElementById('editDeadline').value = deadline;
            document.getElementById('editCategory').value = category;
            document.getElementById('editDescription').value = description;
            openModal('editModal');
        }
        
        function deleteGoal(goalId) {
            document.getElementById('deleteGoalId').value = goalId;
            openModal('deleteModal');
        }
        
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.classList.remove('active');
            });
            document.body.style.overflow = 'auto';
        }
        
        // Close modal on overlay click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>