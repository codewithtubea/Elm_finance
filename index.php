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
            --neon-green: #00ff88;
            --neon-cyan: #00ffff;
            --neon-purple: #b967ff;
            --dark-bg: #0a0a0a;
            --darker-bg: #050505;
            --card-bg: rgba(15, 15, 15, 0.8);
            --card-border: rgba(0, 255, 136, 0.2);
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --gradient-1: linear-gradient(135deg, var(--neon-green), var(--neon-cyan));
            --gradient-2: linear-gradient(135deg, var(--neon-purple), var(--neon-cyan));
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
            font-family: 'Inter', sans-serif; 
            background: var(--darker-bg);
            color: var(--text-primary);
            overflow-x: hidden;
        }
        
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(10, 10, 10, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--card-border);
            z-index: 1000;
            padding: 1rem 0;
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
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-links a:hover {
            color: var(--neon-green);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        
        .btn-primary {
            background: var(--gradient-1);
            color: var(--dark-bg);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--text-primary);
            border: 2px solid var(--neon-green);
        }
        
        .btn-secondary:hover {
            background: var(--neon-green);
            color: var(--dark-bg);
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: 
                radial-gradient(circle at 20% 80%, rgba(0, 255, 136, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 255, 255, 0.1) 0%, transparent 50%),
                var(--dark-bg);
            padding: 0 2rem;
        }
        
        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff, var(--neon-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-content p {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .hero-visual {
            position: relative;
        }
        
        .dashboard-preview {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 
                0 0 50px rgba(0, 255, 136, 0.1),
                0 0 0 1px rgba(0, 255, 136, 0.1);
            transform: perspective(1000px) rotateY(-5deg) rotateX(5deg);
            transition: transform 0.3s ease;
        }
        
        .dashboard-preview:hover {
            transform: perspective(1000px) rotateY(0deg) rotateX(0deg);
        }
        
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .preview-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        /* Features Section */
        .features {
            padding: 6rem 2rem;
            background: var(--darker-bg);
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .section-subtitle {
            text-align: center;
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 4rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 255, 136, 0.1);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .feature-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        /* How It Works */
        .how-it-works {
            padding: 6rem 2rem;
            background: var(--dark-bg);
        }
        
        .steps {
            max-width: 800px;
            margin: 0 auto;
            display: grid;
            gap: 3rem;
        }
        
        .step {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2rem;
            align-items: start;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background: var(--gradient-1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-bg);
        }
        
        .step-content h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .step-content p {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        /* CTA Section */
        .cta {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, var(--dark-bg), var(--darker-bg));
            text-align: center;
        }
        
        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .cta p {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        /* Footer */
        .footer {
            background: var(--darker-bg);
            padding: 4rem 2rem 2rem;
            border-top: 1px solid var(--card-border);
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 4rem;
        }
        
        .footer-brand h3 {
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .footer-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .footer-column h4 {
            color: var(--text-primary);
            margin-bottom: 1rem;
            font-size: 1.125rem;
        }
        
        .footer-column a {
            display: block;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }
        
        .footer-column a:hover {
            color: var(--neon-green);
        }
        
        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .footer-links {
                grid-template-columns: 1fr;
            }
        }
        
        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .float {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(0, 255, 136, 0.2); }
            50% { box-shadow: 0 0 30px rgba(0, 255, 136, 0.4); }
        }
        
        .glow {
            animation: glow 2s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">🌿 Elm Finance</div>
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
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Financial Peace for Student Life</h1>
                <p>Elm helps Ashesi students manage budgets, track expenses, and build better financial habits with campus-specific insights and intelligent spending recommendations.</p>
                <div class="hero-buttons">
                    <?php if ($isLoggedIn): ?>
                        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Start Free Today</a>
                        <a href="#features" class="btn btn-secondary">Learn More</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-visual">
                <div class="dashboard-preview glow">
                    <div class="preview-header">
                        <span style="font-weight: 600;">Monthly Overview</span>
                        <span style="color: var(--neon-green); font-weight: 700;">₵1,240</span>
                    </div>
                    <div class="preview-stats">
                        <div class="stat-card">
                            <div class="stat-value">₵420</div>
                            <div class="stat-label">Food & Dining</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">₵180</div>
                            <div class="stat-label">Transport</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">₵540</div>
                            <div class="stat-label">Essentials</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">₵100</div>
                            <div class="stat-label">Entertainment</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <h2 class="section-title">Designed for Ashesi Students</h2>
        <p class="section-subtitle">Everything you need to take control of your finances, built specifically for campus life.</p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🍽️</div>
                <h3 class="feature-title">Campus Cost Intelligence</h3>
                <p class="feature-description">Get real pricing data from Ashesi campus - dining hall meals, transport costs, textbook prices, and more. Know what's reasonable before you spend.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">Smart Budget Tracking</h3>
                <p class="feature-description">Set realistic budgets and get alerts when you're approaching limits. Visual charts show exactly where your money goes each month.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3 class="feature-title">Spending Insights</h3>
                <p class="feature-description">Compare your spending with campus averages. See how you're doing versus other Ashesi students and get personalized saving tips.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3 class="feature-title">Secure & Private</h3>
                <p class="feature-description">Bank-level security protecting your financial data. Your information stays private while you get the insights you need.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3 class="feature-title">Mobile-Friendly</h3>
                <p class="feature-description">Track expenses on the go with our responsive design. Perfect for quick updates between classes or in the dining hall.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">💡</div>
                <h3 class="feature-title">Financial Education</h3>
                <p class="feature-description">Learn money management skills that will serve you beyond university. Build habits that last a lifetime.</p>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <h2 class="section-title">Simple & Effective</h2>
        <p class="section-subtitle">Get started in minutes and see results immediately.</p>
        
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Create Your Account</h3>
                    <p>Sign up with your Ashesi email in under 2 minutes. No credit card required, completely free for students.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Set Your Budget</h3>
                    <p>Define monthly budgets for food, transport, essentials, and entertainment based on Ashesi-specific cost data.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Track Your Spending</h3>
                    <p>Quickly log expenses as you go. Get instant feedback on how you're doing versus your budget and campus averages.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3>Grow Your Financial IQ</h3>
                    <p>Watch your savings grow while building money management skills that will benefit you for years to come.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-content">
            <h2>Ready to Take Control?</h2>
            <p>Join hundreds of Ashesi students already managing their finances smarter with Elm.</p>
            <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary">Create Your Free Account</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="about">
        <div class="footer-content">
            <div class="footer-brand">
                <h3>🌿 Elm Finance</h3>
                <p>Smart financial management for the next generation of African leaders.</p>
            </div>
            <div class="footer-links">
                <div class="footer-column">
                    <h4>Product</h4>
                    <a href="#features">Features</a>
                    <a href="#how-it-works">How It Works</a>
                    <a href="login.php">Login</a>
                </div>
                <div class="footer-column">
                    <h4>Resources</h4>
                    <a href="#">Financial Tips</a>
                    <a href="#">Campus Guide</a>
                    <a href="#">Support</a>
                </div>
                <div class="footer-column">
                    <h4>Company</h4>
                    <a href="#">About</a>
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Elm Finance. Made with 💚 for Ashesi University students.</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Parallax effect for hero section
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            hero.style.backgroundPositionY = scrolled * 0.5 + 'px';
        });

        // Animation on scroll
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

        // Observe feature cards and steps
        document.querySelectorAll('.feature-card, .step').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>