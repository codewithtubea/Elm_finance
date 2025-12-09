<?php
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/auth.php';

// Configure session
Security::configureSession();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    Security::regenerateSession();
}

$auth = new Auth($pdo);
$isLoggedIn = $auth->isLoggedIn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elm Finance - Smart Money Management for Students</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Professional Color System */
            --primary-color: #10b981;
            --primary-light: #d1fae5;
            --primary-dark: #047857;
            --accent-color: #06b6d4;
            --accent-light: #cffafe;
            --neutral-900: #111827;
            --neutral-800: #1f2937;
            --neutral-700: #374151;
            --neutral-600: #4b5563;
            --neutral-500: #6b7280;
            --neutral-400: #9ca3af;
            --neutral-300: #d1d5db;
            --neutral-200: #e5e7eb;
            --neutral-100: #f3f4f6;
            --neutral-50: #f9fafb;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            
            /* Dark Mode (Default) */
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
            --border-color: #475569;
            --card-bg: #1e293b;
        }
        
        /* Light Mode */
        html.light-mode {
            --bg-primary: #f9fafb;
            --bg-secondary: #ffffff;
            --bg-tertiary: #f3f4f6;
            --text-primary: #111827;
            --text-secondary: #374151;
            --text-tertiary: #6b7280;
            --border-color: #e5e7eb;
            --card-bg: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* ==================== NAVIGATION ==================== */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background-color: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            z-index: 1000;
            padding: 1rem 0;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }
        
        .nav-links a:hover {
            color: var(--primary-color);
        }
        
        /* Theme Toggle */
        .theme-toggle {
            background: none;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            margin-left: 1rem;
            font-weight: 600;
        }
        
        .theme-toggle:hover {
            background-color: var(--bg-tertiary);
            border-color: var(--primary-color);
        }
        
        /* Buttons */
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .btn-secondary {
            background-color: transparent;
            color: var(--primary-color);
            border: 1.5px solid var(--primary-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--primary-light);
            color: var(--primary-dark);
        }
        
        /* ==================== HERO SECTION ==================== */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 60px;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        }
        
        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            width: 100%;
        }
        
        .hero-content h1 {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }
        
        .hero-content h1 .highlight {
            color: var(--primary-color);
        }
        
        .hero-content p {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.8;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .hero-visual {
            position: relative;
            height: 400px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(16, 185, 129, 0.15);
        }
        
        .dashboard-preview {
            width: 100%;
            height: 100%;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
        }
        
        .preview-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .preview-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 1rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        /* ==================== SECTION STYLES ==================== */
        .features {
            padding: 5rem 2rem;
            background: var(--bg-primary);
        }
        
        .how-it-works {
            padding: 5rem 2rem;
            background: var(--bg-secondary);
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .section-subtitle {
            font-size: 1.125rem;
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* ==================== FEATURES GRID ==================== */
        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.1);
            transform: translateY(-4px);
        }
        
        .feature-icon {
            width: 48px;
            height: 48px;
            background-color: var(--primary-light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary-dark);
            font-weight: 700;
        }
        
        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }
        
        .feature-description {
            color: var(--text-secondary);
            line-height: 1.7;
            font-size: 0.95rem;
        }
        
        /* ==================== STEPS ==================== */
        .steps {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .step {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .step:hover {
            border-color: var(--primary-color);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.1);
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .step h3 {
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }
        
        .step p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.7;
        }
        
        /* ==================== CTA SECTION ==================== */
        .cta {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            padding: 4rem 2rem;
            text-align: center;
            color: white;
        }
        
        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .cta p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        .cta .btn {
            background: white;
            color: var(--primary-dark);
            font-weight: 600;
        }
        
        .cta .btn:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        /* ==================== FOOTER ==================== */
        .footer {
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            padding: 4rem 2rem 2rem;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.5fr 2fr;
            gap: 4rem;
            margin-bottom: 2rem;
        }
        
        .footer-brand h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .footer-brand p {
            color: var(--text-secondary);
            line-height: 1.7;
        }
        
        .footer-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .footer-column h4 {
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .footer-column a {
            display: block;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }
        
        .footer-column a:hover {
            color: var(--primary-color);
        }
        
        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
            color: var(--text-tertiary);
            font-size: 0.9rem;
        }
        
        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 768px) {
            .hero-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-visual {
                height: 300px;
            }
            
            .nav-links {
                display: none;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .footer-links {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 1.75rem;
            }
            
            .hero-visual {
                display: none;
            }
            
            .footer-links {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="#" class="logo">Elm Finance</a>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#how-it-works">How It Works</a>
                <a href="#about">About</a>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Login</a>
                    <a href="register.php" class="btn btn-primary">Get Started</a>
                <?php endif; ?>
                <button class="theme-toggle" id="themeToggle" title="Toggle Dark/Light Mode">Light</button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Take Control of Your <span class="highlight">Student Budget</span></h1>
                <p>Elm Finance makes it simple to track expenses, set realistic budgets, and build smarter money habits. Designed specifically for college students who want to spend intentionally.</p>
                <div class="hero-buttons">
                    <?php if ($isLoggedIn): ?>
                        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Create Account</a>
                        <a href="#features" class="btn btn-secondary">Learn More</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-visual">
                <div class="dashboard-preview">
                    <div class="preview-header">
                        <span>Monthly Overview</span>
                        <span>1,240 GHS</span>
                    </div>
                    <div class="preview-stats">
                        <div class="stat-card">
                            <div class="stat-value">420</div>
                            <div class="stat-label">Food & Dining</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">180</div>
                            <div class="stat-label">Transport</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">540</div>
                            <div class="stat-label">Essentials</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">100</div>
                            <div class="stat-label">Entertainment</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <h2 class="section-title">Designed for Student Life</h2>
        <p class="section-subtitle">Everything you need to manage money smartly, built with your lifestyle in mind.</p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">Campus Cost Intelligence</h3>
                <p class="feature-description">See real spending data from Ashesi students. Know what peers spend on food, transport, and essentials so you can spend intentionally.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">Target</div>
                <h3 class="feature-title">Smart Budget Tracking</h3>
                <p class="feature-description">Set monthly budgets by category and get alerts when approaching limits. Visual clarity on exactly where your money goes each month.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">Growth</div>
                <h3 class="feature-title">Spending Insights</h3>
                <p class="feature-description">Compare your spending with campus averages. See where you're spending more or less and get personalized recommendations to save more.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">Lock</div>
                <h3 class="feature-title">Bank-Level Security</h3>
                <p class="feature-description">Your financial data is encrypted and protected. Your information stays private. We never sell your data.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">Phone</div>
                <h3 class="feature-title">Mobile-Optimized</h3>
                <p class="feature-description">Track expenses on the go with our responsive design. Log spending between classes, in the dining hall, anywhere on campus.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">Learn</div>
                <h3 class="feature-title">Financial Education</h3>
                <p class="feature-description">Learn money management skills that serve you beyond university. Build habits that set you up for long-term financial success.</p>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <h2 class="section-title">Get Started in 4 Steps</h2>
        <p class="section-subtitle">Simple setup. Immediate insights. Better habits.</p>
        
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Create Your Account</h3>
                <p>Sign up with your university email in under 2 minutes. No credit card required, completely free for students.</p>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <h3>Set Your Budget</h3>
                <p>Define monthly budgets using campus-specific cost data. Let Elm suggest realistic amounts based on peer spending.</p>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <h3>Log Your Spending</h3>
                <p>Add expenses as you go. Elm tracks them by category and shows real-time spending against your budget.</p>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <h3>Improve Your Habits</h3>
                <p>See insights that help you spend smarter. Watch your savings grow while building lifelong financial skills.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-content">
            <h2>Ready to Take Control?</h2>
            <p>Join hundreds of students managing their finances smarter with Elm.</p>
            <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary">Create Your Account</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="about">
        <div class="footer-content">
            <div class="footer-brand">
                <h3>Elm Finance</h3>
                <p>Smart financial management for students who care about their money. We help you spend intentionally and build habits that matter.</p>
            </div>
            <div class="footer-links">
                <div class="footer-column">
                    <h4>Product</h4>
                    <a href="#features">Features</a>
                    <a href="#how-it-works">How It Works</a>
                    <a href="login.php">Sign In</a>
                </div>
                <div class="footer-column">
                    <h4>Resources</h4>
                    <a href="#">Financial Tips</a>
                    <a href="#">Campus Guide</a>
                    <a href="#">Help & Support</a>
                </div>
                <div class="footer-column">
                    <h4>Company</h4>
                    <a href="#">About</a>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Elm Finance. Helping students build better financial futures.</p>
        </div>
    </footer>

    <script>
        // ============ THEME TOGGLE ============
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        
        // Load saved theme from localStorage
        function loadTheme() {
            const savedTheme = localStorage.getItem('theme') || 'dark-mode';
            if (savedTheme === 'light-mode') {
                html.classList.add('light-mode');
                themeToggle.textContent = 'Dark';
            } else {
                html.classList.remove('light-mode');
                themeToggle.textContent = 'Light';
            }
        }
        
        // Toggle theme on button click
        themeToggle.addEventListener('click', function() {
            html.classList.toggle('light-mode');
            const isLightMode = html.classList.contains('light-mode');
            
            // Update button text
            themeToggle.textContent = isLightMode ? 'Dark' : 'Light';
            
            // Save preference to localStorage
            localStorage.setItem('theme', isLightMode ? 'light-mode' : 'dark-mode');
        });
        
        // Load theme on page load
        loadTheme();
        
        // ============ SMOOTH SCROLLING ============
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // ============ SCROLL ANIMATIONS ============
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .step').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>