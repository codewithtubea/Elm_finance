<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elm - Student Finance Manager</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2>🌿 Elm</h2>
            </div>
            <ul class="nav-menu">
                <li><a href="#dashboard" class="nav-link">Dashboard</a></li>
                <li><a href="#budget" class="nav-link">Budget</a></li>
                <li><a href="#expenses" class="nav-link">Expenses</a></li>
                <li><a href="#insights" class="nav-link">Insights</a></li>
            </ul>
            <div class="nav-actions">
                <button class="btn-secondary">Sign Up</button>
                <button class="btn-primary">Login</button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Financial Peace for Student Life</h1>
                <p class="hero-subtitle">Set budgets, track expenses, and build better financial habits with Elm's intuitive platform designed specifically for students.</p>
                <div class="hero-actions">
                    <button class="btn-primary large">Get Started Free</button>
                    <button class="btn-secondary large">Watch Demo</button>
                </div>
            </div>
            <div class="hero-visual">
                <div class="dashboard-preview">
                    <!-- This will be styled as a mock dashboard -->
                    <div class="preview-card">
                        <div class="preview-header">
                            <span>Monthly Budget</span>
                            <span class="amount">$1,200</span>
                        </div>
                        <div class="preview-chart">
                            <div class="chart-bar food" style="height: 70%"></div>
                            <div class="chart-bar transport" style="height: 40%"></div>
                            <div class="chart-bar essentials" style="height: 30%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Designed for Student Life</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🍽️</div>
                    <h3>Campus Cost Data</h3>
                    <p>Realistic pricing for meals, transportation, and essentials based on actual campus data.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Visual Insights</h3>
                    <p>See your spending patterns with clean, easy-to-understand charts and graphs.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💡</div>
                    <h3>Smart Budgeting</h3>
                    <p>Set realistic budgets and get alerts when you're approaching your limits.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3>Intentional Spending</h3>
                    <p>Make informed decisions before you spend with our predictive cost analysis.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">Simple Financial Management</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Set Your Budget</h3>
                    <p>Define monthly budgets for food, transportation, and essentials based on your needs.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Track Expenses</h3>
                    <p>Quickly log daily spending with our simple, student-friendly interface.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Gain Insights</h3>
                    <p>Understand your spending patterns and make smarter financial decisions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Start Your Financial Journey Today</h2>
            <p>Join thousands of students already managing their finances with Elm.</p>
            <button class="btn-primary large">Create Your Free Account</button>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <h3>🌿 Elm</h3>
                    <p>Making student finances simple and intentional.</p>
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Product</h4>
                        <a href="#">Features</a>
                        <a href="#">Pricing</a>
                        <a href="#">Testimonials</a>
                    </div>
                    <div class="footer-column">
                        <h4>Resources</h4>
                        <a href="#">Blog</a>
                        <a href="#">Guides</a>
                        <a href="#">Support</a>
                    </div>
                    <div class="footer-column">
                        <h4>Company</h4>
                        <a href="#">About</a>
                        <a href="#">Contact</a>
                        <a href="#">Privacy</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Elm Finance. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/app.js"></script>
</body>
</html>