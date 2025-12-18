<?php
// calculator.php - FIXED VERSION
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
$activePage = 'calculator';

// Get today's expenses for sidebar badge
$todayExpenses = getTodayExpenses($pdo, $userId);

$pageTitle = 'Calculator | Elm Finance';
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
    <?php 
    $activePage = 'calculator'; 
    include 'includes/sidebar.php'; 
    ?>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="container">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">Quick Calculators</h1>
                <div class="top-actions">
                    <button class="theme-toggle" id="themeToggle">
                        <span id="themeIcon">🌙</span>
                    </button>
                </div>
            </div>
            
            <!-- Calculators Grid -->
            <div class="calculators-grid">
                <!-- Can I Afford This? -->
                <div class="calculator-card">
                    <div class="calculator-header">
                        <div class="calculator-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div>
                            <div class="calculator-title">Can I Afford This?</div>
                            <div class="calculator-subtitle">Check if it fits your budget</div>
                        </div>
                    </div>
                    
                    <form class="calc-form" id="affordForm">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-money-bill"></i> Monthly Income (GHS)
                            </label>
                            <input type="number" id="monthlyIncome" class="form-input" 
                                   placeholder="e.g., 2000" min="0" step="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-receipt"></i> Monthly Expenses (GHS)
                            </label>
                            <input type="number" id="monthlyExpenses" class="form-input" 
                                   placeholder="e.g., 1500" min="0" step="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-shopping-cart"></i> Item Cost (GHS)
                            </label>
                            <input type="number" id="itemCost" class="form-input" 
                                   placeholder="e.g., 300" min="0" step="10" required>
                        </div>
                        
                        <button type="button" class="btn btn-primary" onclick="checkIfAffordable()">
                            <i class="fas fa-check-circle"></i> Check Affordability
                        </button>
                    </form>
                    
                    <div class="results-box" id="affordResult">
                        <div class="result-title">💰 Can You Afford It?</div>
                        <div class="quick-answer" id="affordAnswer"></div>
                        <div class="result-item">
                            <span class="result-label">Monthly Balance</span>
                            <span class="result-value" id="monthlyBalance">GHS 0</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">After Purchase</span>
                            <span class="result-value" id="afterPurchase">GHS 0</span>
                        </div>
                    </div>
                </div>
                
                <!-- Savings Timeline -->
                <div class="calculator-card">
                    <div class="calculator-header">
                        <div class="calculator-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div>
                            <div class="calculator-title">Savings Timeline</div>
                            <div class="calculator-subtitle">How long to save for something</div>
                        </div>
                    </div>
                    
                    <form class="calc-form" id="savingsForm">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-bullseye"></i> Goal Amount (GHS)
                            </label>
                            <input type="number" id="goalAmount" class="form-input" 
                                   placeholder="e.g., 5000" min="100" step="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-piggy-bank"></i> Monthly Save (GHS)
                            </label>
                            <input type="number" id="monthlySave" class="form-input" 
                                   placeholder="e.g., 200" min="10" step="10" required>
                        </div>
                        
                        <button type="button" class="btn btn-primary" onclick="calculateSavingsTime()">
                            <i class="fas fa-clock"></i> Calculate Time
                        </button>
                    </form>
                    
                    <div class="results-box" id="savingsResult">
                        <div class="result-title">⏳ Savings Timeline</div>
                        <div class="quick-answer" id="savingsAnswer"></div>
                        <div class="result-item">
                            <span class="result-label">Months Needed</span>
                            <span class="result-value" id="monthsNeeded">0</span>
                        </div>
                        <div class="result-item">
                            <span class="result-label">Years Needed</span>
                            <span class="result-value" id="yearsNeeded">0</span>
                        </div>
                    </div>
                </div>
                
                <!-- Money Leftover -->
                <div class="calculator-card">
                    <div class="calculator-header">
                        <div class="calculator-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div>
                            <div class="calculator-title">Money Leftover</div>
                            <div class="calculator-subtitle">What's left after bills?</div>
                        </div>
                    </div>
                    
                    <form class="calc-form" id="leftoverForm">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-money-bill-wave"></i> Monthly Income (GHS)
                            </label>
                            <input type="number" id="income" class="form-input" 
                                   placeholder="e.g., 2500" min="0" step="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-home"></i> Rent & Utilities (GHS)
                            </label>
                            <input type="number" id="rent" class="form-input" 
                                   placeholder="e.g., 800" min="0" step="50" required>
                        </div>
                        
                        <button type="button" class="btn btn-primary" onclick="calculateLeftover()">
                            <i class="fas fa-coins"></i> Calculate Leftover
                        </button>
                    </form>
                    
                    <div class="results-box" id="leftoverResult">
                        <div class="result-title">💸 Money Leftover</div>
                        <div class="quick-answer" id="leftoverAnswer"></div>
                        <div class="result-item">
                            <span class="result-label">Money Left</span>
                            <span class="result-value" id="moneyLeft">GHS 0</span>
                        </div>
                    </div>
                </div>
                
                <!-- Bill Splitter -->
                <div class="calculator-card">
                    <div class="calculator-header">
                        <div class="calculator-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="calculator-title">Bill Splitter</div>
                            <div class="calculator-subtitle">Split bills with friends</div>
                        </div>
                    </div>
                    
                    <form class="calc-form" id="splitForm">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-receipt"></i> Total Bill (GHS)
                            </label>
                            <input type="number" id="totalBill" class="form-input" 
                                   placeholder="e.g., 1200" min="0" step="10" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user-friends"></i> Number of People
                            </label>
                            <input type="number" id="numPeople" class="form-input" 
                                   placeholder="e.g., 4" min="1" step="1" required>
                        </div>
                        
                        <button type="button" class="btn btn-primary" onclick="splitBill()">
                            <i class="fas fa-divide"></i> Split Bill
                        </button>
                    </form>
                    
                    <div class="results-box" id="splitResult">
                        <div class="result-title">📊 Split Results</div>
                        <div class="quick-answer" id="splitAnswer"></div>
                        <div class="result-item">
                            <span class="result-label">Each Person Pays</span>
                            <span class="result-value" id="eachPays">GHS 0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Calculator JavaScript -->
    <script>
        // 1. Can I Afford This?
        function checkIfAffordable() {
            const income = parseFloat(document.getElementById('monthlyIncome').value) || 0;
            const expenses = parseFloat(document.getElementById('monthlyExpenses').value) || 0;
            const itemCost = parseFloat(document.getElementById('itemCost').value) || 0;
            
            if (!income || !expenses || !itemCost) {
                alert('Please fill in all fields');
                return;
            }
            
            const monthlyBalance = income - expenses;
            const afterPurchase = monthlyBalance - itemCost;
            
            document.getElementById('monthlyBalance').textContent = 'GHS ' + monthlyBalance.toFixed(2);
            document.getElementById('afterPurchase').textContent = 'GHS ' + afterPurchase.toFixed(2);
            
            let answer = '';
            let answerColor = '';
            
            if (afterPurchase >= 0) {
                answer = '✅ Yes, affordable!';
                answerColor = '#00ff88';
            } else {
                answer = '❌ No, cannot afford';
                answerColor = '#ff4444';
            }
            
            document.getElementById('affordAnswer').textContent = answer;
            document.getElementById('affordAnswer').style.color = answerColor;
            document.getElementById('affordResult').classList.add('show');
        }
        
        // 2. Savings Timeline
        function calculateSavingsTime() {
            const goal = parseFloat(document.getElementById('goalAmount').value) || 0;
            const monthly = parseFloat(document.getElementById('monthlySave').value) || 0;
            
            if (!goal || !monthly) {
                alert('Please fill in all fields');
                return;
            }
            
            const monthsNeeded = Math.ceil(goal / monthly);
            const yearsNeeded = (monthsNeeded / 12).toFixed(1);
            
            document.getElementById('monthsNeeded').textContent = monthsNeeded;
            document.getElementById('yearsNeeded').textContent = yearsNeeded;
            
            let answer = '';
            if (monthsNeeded <= 3) {
                answer = '🚀 Soon! ' + monthsNeeded + ' months';
            } else if (monthsNeeded <= 12) {
                answer = '📅 About ' + monthsNeeded + ' months';
            } else {
                answer = '⏳ ' + yearsNeeded + ' years';
            }
            
            document.getElementById('savingsAnswer').textContent = answer;
            document.getElementById('savingsResult').classList.add('show');
        }
        
        // 3. Money Leftover
        function calculateLeftover() {
            const income = parseFloat(document.getElementById('income').value) || 0;
            const rent = parseFloat(document.getElementById('rent').value) || 0;
            
            if (!income || !rent) {
                alert('Please fill in all fields');
                return;
            }
            
            const moneyLeft = income - rent;
            
            document.getElementById('moneyLeft').textContent = 'GHS ' + moneyLeft.toFixed(2);
            
            let answer = '';
            if (moneyLeft >= 500) {
                answer = '💰 Good amount left!';
            } else if (moneyLeft >= 0) {
                answer = '📊 Manageable';
            } else {
                answer = '❌ Overspending!';
            }
            
            document.getElementById('leftoverAnswer').textContent = answer;
            document.getElementById('leftoverResult').classList.add('show');
        }
        
        // 4. Bill Splitter
        function splitBill() {
            const total = parseFloat(document.getElementById('totalBill').value) || 0;
            const people = parseInt(document.getElementById('numPeople').value) || 1;
            
            if (!total || people < 1) {
                alert('Please fill in all fields');
                return;
            }
            
            const eachPays = total / people;
            
            document.getElementById('eachPays').textContent = 'GHS ' + eachPays.toFixed(2);
            document.getElementById('splitAnswer').textContent = 'Each pays GHS ' + eachPays.toFixed(2);
            document.getElementById('splitResult').classList.add('show');
        }
        
        // Clear results when inputs change
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.form-input').forEach(input => {
                input.addEventListener('input', () => {
                    const card = input.closest('.calculator-card');
                    const resultBox = card.querySelector('.results-box');
                    if (resultBox) {
                        resultBox.classList.remove('show');
                    }
                });
            });
        });
    </script>
    
    <!-- Load Global JavaScript -->
    <script src="public/js/theme-manager.js"></script>
</body>
</html>