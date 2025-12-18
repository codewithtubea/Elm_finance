<?php
// expenses.php - FIXED VERSION
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
$activePage = 'expenses';

// Initialize message variables
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Security error. Please try again.';
        $messageType = 'error';
    } else {
        // Validate input
        $amount = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
        $category = htmlspecialchars($_POST['category'] ?? 'other');
        $description = htmlspecialchars($_POST['description'] ?? '');
        $expenseDate = $_POST['expense_date'] ?? date('Y-m-d');
        
        // Basic validation
        if ($amount <= 0) {
            $message = 'Please enter a valid amount.';
            $messageType = 'error';
        } elseif (!in_array($category, ['food', 'transport', 'essentials', 'entertainment', 'other'])) {
            $message = 'Please select a valid category.';
            $messageType = 'error';
        } else {
            try {
                // Insert expense
                $stmt = $pdo->prepare("
                    INSERT INTO elm_expenses 
                    (user_id, amount, category, description, expense_date) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([$userId, $amount, $category, $description, $expenseDate]);
                
                $message = 'Expense added successfully!';
                $messageType = 'success';
                
                // Clear form (except date)
                $_POST = ['expense_date' => $expenseDate];
                
            } catch (PDOException $e) {
                error_log("Expense insertion error: " . $e->getMessage());
                $message = 'Error saving expense. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get today's expenses for sidebar
$todayExpenses = getTodayExpenses($pdo, $userId);

$pageTitle = 'Add Expenses | Elm Finance';
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
    <style>
        /* Additional styles for better category selection */
        .category-option {
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .category-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .category-option.selected {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 255, 136, 0.3);
        }
        
        /* Make category icons larger */
        .category-icon-large {
            font-size: 2.5rem;
            margin-bottom: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .category-option.selected .category-icon-large {
            transform: scale(1.2);
        }
        
        /* Category-specific colors */
        .category-option[data-value="food"] {
            border-color: rgba(0, 255, 136, 0.3);
        }
        
        .category-option[data-value="food"]:hover {
            border-color: var(--success);
        }
        
        .category-option[data-value="food"].selected {
            background: var(--success);
        }
        
        .category-option[data-value="transport"] {
            border-color: rgba(77, 150, 255, 0.3);
        }
        
        .category-option[data-value="transport"]:hover {
            border-color: var(--info);
        }
        
        .category-option[data-value="transport"].selected {
            background: var(--info);
        }
        
        .category-option[data-value="essentials"] {
            border-color: rgba(255, 170, 0, 0.3);
        }
        
        .category-option[data-value="essentials"]:hover {
            border-color: var(--warning);
        }
        
        .category-option[data-value="essentials"].selected {
            background: var(--warning);
        }
        
        .category-option[data-value="entertainment"] {
            border-color: rgba(168, 85, 247, 0.3);
        }
        
        .category-option[data-value="entertainment"]:hover {
            border-color: #a855f7;
        }
        
        .category-option[data-value="entertainment"].selected {
            background: #a855f7;
        }
        
        .category-option[data-value="other"] {
            border-color: rgba(255, 107, 107, 0.3);
        }
        
        .category-option[data-value="other"]:hover {
            border-color: var(--error);
        }
        
        .category-option[data-value="other"].selected {
            background: var(--error);
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php 
    $activePage = 'expenses'; 
    include 'includes/sidebar.php'; 
    ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="expenses-container">
            <!-- Header -->
            <div class="expenses-header">
                <h1>
                    <i class="fas fa-plus-circle"></i> Add Expenses
                </h1>
                <div class="top-actions">
                    <button class="theme-toggle" id="themeToggle">
                        <span id="themeIcon">🌙</span>
                    </button>
                </div>
            </div>
            
            <!-- Message Alert -->
            <?php if ($message): ?>
            <div class="alert-message <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <strong style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $messageType === 'success' ? 'Success' : 'Error'; ?>
                </strong>
                <p style="margin-top: 0.5rem;"><?php echo htmlspecialchars($message); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Expense Form -->
            <div class="expense-form-card glass-card">
                <h2>Add New Expense</h2>
                <form method="POST" action="" id="expenseForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Amount -->
                    <div class="form-group">
                        <label class="form-label">Amount (GHS)</label>
                        <input type="number" 
                               name="amount" 
                               class="form-input" 
                               step="0.01" 
                               min="0" 
                               placeholder="0.00" 
                               required
                               value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
                               id="amountInput">
                    </div>
                    
                    <!-- Category Selection -->
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <div class="category-selection" id="categoryContainer">
                            <?php 
                            $categoryOptions = [
                                'food' => ['icon' => '🍔', 'label' => 'Food'],
                                'transport' => ['icon' => '🚗', 'label' => 'Transport'],
                                'essentials' => ['icon' => '🛒', 'label' => 'Essentials'],
                                'entertainment' => ['icon' => '🎬', 'label' => 'Entertainment'],
                                'other' => ['icon' => '📝', 'label' => 'Other']
                            ];
                            
                            $selectedCategory = $_POST['category'] ?? 'food';
                            
                            foreach ($categoryOptions as $value => $data):
                                $isSelected = $selectedCategory === $value;
                            ?>
                            <button type="button" 
                                    class="category-option <?php echo $isSelected ? 'selected' : ''; ?>"
                                    onclick="selectCategory('<?php echo $value; ?>')"
                                    data-value="<?php echo $value; ?>"
                                    id="category-<?php echo $value; ?>">
                                <div class="category-icon-large"><?php echo $data['icon']; ?></div>
                                <div class="category-label"><?php echo $data['label']; ?></div>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="categoryInput" name="category" value="<?php echo $selectedCategory; ?>" required>
                    </div>
                    
                    <!-- Description -->
                    <div class="form-group">
                        <label class="form-label">Description (Optional)</label>
                        <input type="text" 
                               name="description" 
                               class="form-input" 
                               placeholder="What was this for? e.g., 'Lunch at campus cafe'"
                               value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>"
                               id="descriptionInput">
                    </div>
                    
                    <!-- Date -->
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" 
                               name="expense_date" 
                               class="form-input" 
                               required
                               value="<?php echo htmlspecialchars($_POST['expense_date'] ?? date('Y-m-d')); ?>"
                               id="dateInput">
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                        <i class="fas fa-check"></i> Add Expense
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="public/js/theme-manager.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            // Initialize category selection
            const initialCategory = document.getElementById('categoryInput').value;
            selectCategory(initialCategory);
            
            // Auto-set today's date if empty
            const dateInput = document.getElementById('dateInput');
            if (!dateInput.value) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.value = today;
            }
            
            // Focus amount field
            document.getElementById('amountInput').focus();
        });
        
        // Category selection function
        function selectCategory(category) {
            console.log('Selecting category:', category); // Debug log
            
            // Update hidden input
            document.getElementById('categoryInput').value = category;
            
            // Update all category buttons
            const allButtons = document.querySelectorAll('.category-option');
            allButtons.forEach(button => {
                const buttonCategory = button.getAttribute('data-value');
                if (buttonCategory === category) {
                    // Add selected class
                    button.classList.add('selected');
                    
                    // Update colors based on category
                    switch(category) {
                        case 'food':
                            button.style.background = 'var(--success)';
                            button.style.color = '#0a0a0a';
                            break;
                        case 'transport':
                            button.style.background = 'var(--info)';
                            button.style.color = '#0a0a0a';
                            break;
                        case 'essentials':
                            button.style.background = 'var(--warning)';
                            button.style.color = '#0a0a0a';
                            break;
                        case 'entertainment':
                            button.style.background = '#a855f7';
                            button.style.color = '#ffffff';
                            break;
                        case 'other':
                            button.style.background = 'var(--error)';
                            button.style.color = '#ffffff';
                            break;
                    }
                } else {
                    // Remove selected class and reset styles
                    button.classList.remove('selected');
                    button.style.background = '';
                    button.style.color = '';
                }
            });
        }
        
        // Form validation
        document.getElementById('expenseForm').addEventListener('submit', function(e) {
            const amount = document.getElementById('amountInput').value;
            const category = document.getElementById('categoryInput').value;
            
            if (!amount || parseFloat(amount) <= 0) {
                e.preventDefault();
                alert('Please enter a valid amount greater than 0.');
                document.getElementById('amountInput').focus();
                return false;
            }
            
            if (!category) {
                e.preventDefault();
                alert('Please select a category.');
                return false;
            }
            
            return true;
        });
        
        // Quick amount buttons (optional feature)
        const quickAmounts = [10, 20, 50, 100, 200];
        const amountContainer = document.createElement('div');
        amountContainer.style.display = 'flex';
        amountContainer.style.gap = '0.5rem';
        amountContainer.style.marginTop = '0.5rem';
        amountContainer.style.flexWrap = 'wrap';
        
        quickAmounts.forEach(amount => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = `GHS ${amount}`;
            btn.style.padding = '0.5rem 1rem';
            btn.style.borderRadius = '8px';
            btn.style.background = 'var(--glass-bg)';
            btn.style.border = '1px solid var(--glass-border)';
            btn.style.color = 'var(--text-primary)';
            btn.style.cursor = 'pointer';
            btn.style.fontSize = '1rem';
            btn.style.transition = 'all 0.3s ease';
            
            btn.addEventListener('click', () => {
                document.getElementById('amountInput').value = amount;
                document.getElementById('amountInput').focus();
                
                // Add visual feedback
                btn.style.background = 'var(--accent-primary)';
                btn.style.color = '#0a0a0a';
                
                // Reset other buttons
                setTimeout(() => {
                    btn.style.background = 'var(--glass-bg)';
                    btn.style.color = 'var(--text-primary)';
                }, 300);
            });
            
            btn.addEventListener('mouseenter', () => {
                btn.style.transform = 'translateY(-2px)';
                btn.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
            });
            
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = '';
                btn.style.boxShadow = '';
            });
            
            amountContainer.appendChild(btn);
        });
        
        // Add quick amount buttons after amount input
        const amountInput = document.getElementById('amountInput');
        amountInput.parentNode.appendChild(amountContainer);
    </script>
</body>
</html>