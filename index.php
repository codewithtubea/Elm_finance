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
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elm Finance | Smart Money Management for Students</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Styles */
        :root {
            /* Dark Mode - Black & Neon Green */
            --bg-primary: #0a0a0a;
            --bg-secondary: #121212;
            --bg-tertiary: #1a1a1a;
            --card-bg: rgba(26, 26, 26, 0.7);
            --text-primary: #ffffff;
            --text-secondary: #888888;
            --accent-primary: #00ff88;
            --accent-secondary: #00cc66;
            --success: #00ff88;
            --warning: #ffaa00;
            --error: #ff4444;
            --border-light: rgba(255, 255, 255, 0.08);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-shadow: 0 8px 32px rgba(0, 255, 136, 0.1);
            --neon-glow: 0 0 20px rgba(0, 255, 136, 0.3);
        }

        [data-theme="light"] {
            /* Light Mode - White & Pistachio Green */
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --card-bg: rgba(220, 253, 220, 0.9);
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --accent-primary: #00cc66;
            --accent-secondary: #00a854;
            --success: #00cc66;
            --warning: #f59e0b;
            --error: #ef4444;
            --border-light: rgba(206, 28, 28, 0.06);
            --glass-bg: rgba(232, 255, 232, 0.8);
            --glass-border: rgba(0, 0, 0, 0.08);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
            --neon-glow: 0 0 20px rgba(0, 204, 102, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        html {
            font-size: 62.5%; /* 10px = 1rem */
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-weight: 300;
            font-size: 1.1rem; /* 11px base */
            line-height: 1.6;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
            overflow-x: hidden;
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .text-thin { font-weight: 100; }
        .text-light { font-weight: 300; }
        .text-regular { font-weight: 400; }
        .text-medium { font-weight: 500; }
        .text-semibold { font-weight: 600; }
        .text-bold { font-weight: 700; }

        .text-xs { font-size: 1rem; }
        .text-sm { font-size: 1.1rem; }
        .text-base { font-size: 1.3rem; }
        .text-lg { font-size: 1.6rem; }
        .text-xl { font-size: 2rem; }
        .text-2xl { font-size: 2.5rem; }
        .text-3xl { font-size: 3rem; }
        .text-4xl { font-size: 3.5rem; }

        /* Glassmorphism */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: var(--glass-shadow);
        }

        .glass-nav {
            background: rgba(10, 10, 10, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        [data-theme="light"] .glass-nav {
            background: rgba(255, 255, 255, 0.8);
        }

        /* Buttons */
        .btn {
            padding: 1.2rem 2.4rem;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            font-size: 1.1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn:hover::after {
            left: 100%;
        }

        .btn-primary {
            background: var(--accent-primary);
            color: var(--bg-primary);
            box-shadow: 0 4px 20px rgba(0, 255, 136, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 255, 136, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: var(--accent-primary);
            border: 1.5px solid var(--accent-primary);
        }

        .btn-secondary:hover {
            background: rgba(0, 255, 136, 0.1);
            transform: translateY(-2px);
        }

        [data-theme="light"] .btn-primary {
            background: var(--accent-primary);
            color: white;
            box-shadow: 0 4px 20px rgba(0, 204, 102, 0.2);
        }

        [data-theme="light"] .btn-secondary {
            color: var(--accent-primary);
            border-color: var(--accent-primary);
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 1.5rem 0;
            z-index: 1000;
            background: var(--glass-nav);
            border-bottom: 1px solid var(--glass-border);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            padding: 1rem 0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2.4rem;
            font-weight: 700;
            color: var(--accent-primary);
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .logo-glow {
            text-shadow: var(--neon-glow);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 3rem;
        }

        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 400;
            font-size: 1.1rem;
            transition: color 0.2s ease;
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent-primary);
            transition: width 0.3s ease;
        }

        .nav-link:hover {
            color: var(--accent-primary);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .theme-toggle {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: var(--glass-bg);
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
            transform: rotate(30deg);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 8rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, var(--accent-primary) 0%, transparent 70%);
            filter: blur(120px);
            opacity: 0.1;
            top: -200px;
            right: -200px;
            z-index: 0;
        }

        .hero-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-content h1 {
            font-size: 4.8rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 2rem;
        }

        .hero-content h1 span {
            color: var(--accent-primary);
            position: relative;
        }

        .hero-content h1 span::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--accent-primary);
            border-radius: 2px;
        }

        .hero-content p {
            font-size: 1.3rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            max-width: 500px;
            line-height: 1.8;
        }

        .hero-visual {
            position: relative;
            height: 450px;
            perspective: 1000px;
        }

        .dashboard-preview {
            width: 100%;
            height: 100%;
            background: var(--card-bg);
            border-radius: 24px;
            padding: 3rem;
            transform-style: preserve-3d;
            transform: rotateY(-5deg) rotateX(5deg);
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--glass-border);
        }

        .preview-title {
            font-weight: 600;
            font-size: 1.3rem;
        }

        .preview-balance {
            font-weight: 700;
            font-size: 2.4rem;
            color: var(--accent-primary);
        }

        .preview-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .stat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: var(--accent-primary);
            transform: translateY(-2px);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        /* Features */
        .features {
            padding: 10rem 2rem;
            background: var(--bg-secondary);
        }

        .section-header {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 6rem;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--accent-primary);
            border-radius: 2px;
        }

        .section-subtitle {
            font-size: 1.3rem;
            color: var(--text-secondary);
            line-height: 1.8;
        }

        .features-grid {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
        }

        .feature-card {
            padding: 3rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 
                0 20px 40px rgba(0, 255, 136, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            background: rgba(0, 255, 136, 0.1);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            font-weight: 600;
            color: var(--accent-primary);
        }

        [data-theme="light"] .feature-icon {
            background: rgba(0, 204, 102, 0.1);
        }

        .feature-title {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .feature-description {
            color: var(--text-secondary);
            line-height: 1.8;
            font-size: 1.1rem;
        }

        /* How it Works */
        .how-it-works {
            padding: 10rem 2rem;
            position: relative;
        }

        .how-it-works::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent-primary), transparent);
        }

        .steps-container {
            max-width: 1280px;
            margin: 0 auto;
            position: relative;
        }

        .steps-container::before {
            content: '';
            position: absolute;
            top: 48px;
            left: 40px;
            right: 40px;
            height: 2px;
            background: var(--glass-border);
            z-index: 0;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            position: relative;
            z-index: 1;
        }

        .step {
            padding: 3rem;
            position: relative;
        }

        .step-number {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
            box-shadow: 0 4px 20px rgba(0, 255, 136, 0.3);
        }

        .step-title {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .step-description {
            color: var(--text-secondary);
            line-height: 1.8;
            font-size: 1.1rem;
        }

        /* CTA Section */
        .cta {
            padding: 8rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(0, 255, 136, 0.05) 0%, 
                rgba(0, 204, 102, 0.02) 100%);
            z-index: 0;
        }

        .cta-content {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .cta h2 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .cta p {
            font-size: 1.3rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            line-height: 1.8;
        }

        /* Footer */
        .footer {
            padding: 6rem 2rem 3rem;
            background: var(--bg-tertiary);
            border-top: 1px solid var(--glass-border);
        }

        .footer-content {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.5fr repeat(3, 1fr);
            gap: 4rem;
            margin-bottom: 4rem;
        }

        .footer-brand h3 {
            font-size: 2.4rem;
            font-weight: 700;
            color: var(--accent-primary);
            margin-bottom: 1.5rem;
        }

        .footer-brand p {
            color: var(--text-secondary);
            line-height: 1.8;
            font-size: 1.1rem;
            max-width: 300px;
        }

        .footer-column h4 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .footer-column a {
            display: block;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            transition: color 0.2s ease;
        }

        .footer-column a:hover {
            color: var(--accent-primary);
        }

        .footer-bottom {
            max-width: 1280px;
            margin: 0 auto;
            padding-top: 3rem;
            border-top: 1px solid var(--glass-border);
            text-align: center;
            color: var(--text-secondary);
            font-size: 1rem;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-container {
                grid-template-columns: 1fr;
                gap: 4rem;
                text-align: center;
            }

            .hero-content p {
                margin: 0 auto 3rem;
            }

            .steps {
                grid-template-columns: repeat(2, 1fr);
            }

            .steps-container::before {
                display: none;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 3rem;
            }
        }

        @media (max-width: 768px) {
            html {
                font-size: 58%;
            }

            .hero-content h1 {
                font-size: 3.5rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .steps {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
                background: none;
                border: none;
                color: var(--text-primary);
                font-size: 2rem;
                cursor: pointer;
            }

            .dashboard-preview {
                transform: none;
                height: 350px;
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 2.8rem;
            }

            .section-title {
                font-size: 2.5rem;
            }

            .cta h2 {
                font-size: 2.5rem;
            }

            .btn {
                width: 100%;
            }

            .hero-visual {
                display: none;
            }
        }

        /* Loading Animation */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--bg-primary);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.3s ease;
        }

        .elm-loader {
            position: relative;
            width: 120px;
            height: 120px;
            margin-bottom: 2rem;
        }

        .elm-logo {
            width: 100%;
            height: 100%;
            opacity: 0.9;
        }

        .elm-outline {
            fill: none;
            stroke: var(--accent-primary);
            stroke-width: 2;
            stroke-dasharray: 300;
            stroke-dashoffset: 300;
            animation: drawElm 1.5s ease-in-out infinite;
        }

        .loading-pulse {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--accent-primary) 0%, transparent 70%);
            opacity: 0.3;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes drawElm {
            0% { stroke-dashoffset: 300; opacity: 0.3; }
            50% { stroke-dashoffset: 0; opacity: 1; }
            100% { stroke-dashoffset: -300; opacity: 0.3; }
        }

        @keyframes pulse {
            0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.3; }
            50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.1; }
            100% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.3; }
        }
    </style>
</head>
<body>
    <div class="loading-screen" id="loadingScreen">
    <div class="elm-loader">
        <svg class="elm-logo" viewBox="0 0 100 100">
            <path class="elm-outline" d="M50,20 L70,40 L60,80 L40,90 L20,80 L30,40 Z"/>
        </svg>
        <div class="loading-pulse"></div>
    </div>
    <div style="margin-top: 20px; font-size: 1.3rem; color: var(--accent-primary);">
        Loading ...
    </div>
</div>

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="#" class="logo logo-glow"> 🌿 ELM</a> 
            <div class="nav-links">
                <a href="#features" class="nav-link">Features</a>
                <a href="#how-it-works" class="nav-link">How It Works</a>
                <a href="#about" class="nav-link">About</a>
                <?php if ($isLoggedIn): ?>
                    <a href="login.php" class="btn btn-primary">Log In</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Log in</a>
                    <a href="register.php" class="btn btn-primary">Get Started</a>
                <?php endif; ?>
                <button class="theme-toggle" id="themeToggle">
                    <span class="theme-icon">🌙</span>
                </button>
            </div>
            <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="animate-fadeInUp">Smart Money for <br><span>Smart Students</span></h1>
                <p class="animate-fadeInUp" style="animation-delay: 0.1s">
                    Elm Finance helps university students track expenses, set budgets, and build better financial habits. 
                    Campus-specific insights make money management intuitive.
                </p>
                <div class="hero-buttons animate-fadeInUp" style="animation-delay: 0.2s">
                    <?php if ($isLoggedIn): ?>
                        <a href="register.php" class="btn btn-primary"> Join the Elm Community 🌿→</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Start Free Today</a>
                        <a href="#features" class="btn btn-secondary">Learn More</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-visual animate-float" style="animation-delay: 0.3s">
                <div class="dashboard-preview glass-card">
                    <div class="preview-header">
                        <h3 class="preview-title">Monthly Overview</h3>
                        <div class="preview-balance">GHS 1,245</div>
                    </div>
                    <div class="preview-stats">
                        <div class="stat-card">
                            <div class="stat-value">GHS 420</div>
                            <div class="stat-label">Food & Dining</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">GHS 180</div>
                            <div class="stat-label">Transport</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">GHS 540</div>
                            <div class="stat-label">Essentials</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">GHS 105</div>
                            <div class="stat-label">Entertainment</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-header">
            <h2 class="section-title">Built for Campus Life</h2>
            <p class="section-subtitle">
                Everything you need to manage money smartly, with features designed specifically for student realities.
            </p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card glass-card animate-fadeInUp">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">Campus Cost Intelligence</h3>
                <p class="feature-description">
                    Compare your spending with campus averages. See real data from Ashesi students to set smarter budgets.
                </p>
            </div>
            
            <div class="feature-card glass-card animate-fadeInUp" style="animation-delay: 0.1s">
                <div class="feature-icon">🎯</div>
                <h3 class="feature-title">Smart Budget Tracking</h3>
                <p class="feature-description">
                    Set monthly budgets by category and get intelligent alerts before you overspend. Visual clarity on spending patterns.
                </p>
            </div>
            
            <div class="feature-card glass-card animate-fadeInUp" style="animation-delay: 0.2s">
                <div class="feature-icon">📈</div>
                <h3 class="feature-title">Real-time Insights</h3>
                <p class="feature-description">
                    Watch your financial habits evolve with beautiful visualizations. See progress and adjust as you go.
                </p>
            </div>
            
            <div class="feature-card glass-card animate-fadeInUp" style="animation-delay: 0.3s">
                <div class="feature-icon">🔒</div>
                <h3 class="feature-title">Bank-Level Security</h3>
                <p class="feature-description">
                    Your financial data is encrypted and protected. We never sell your data—your privacy is our priority.
                </p>
            </div>
            
            <div class="feature-card glass-card animate-fadeInUp" style="animation-delay: 0.4s">
                <div class="feature-icon">📱</div>
                <h3 class="feature-title">Mobile-Optimized</h3>
                <p class="feature-description">
                    Log expenses on the go. Our responsive design works perfectly between classes, in the dining hall, anywhere.
                </p>
            </div>
            
            <div class="feature-card glass-card animate-fadeInUp" style="animation-delay: 0.5s">
                <div class="feature-icon">🎓</div>
                <h3 class="feature-title">Financial Education</h3>
                <p class="feature-description">
                    Build money management skills that serve you beyond university. Learn while you track and grow.
                </p>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="section-header">
            <h2 class="section-title">Simple & Effective</h2>
            <p class="section-subtitle">
                Get started in minutes. See results immediately. Build habits that last.
            </p>
        </div>
        
        <div class="steps-container">
            <div class="steps">
                <div class="step glass-card">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Create Account</h3>
                    <p class="step-description">
                        Sign up with your university email. Takes less than 2 minutes—completely free for students.
                    </p>
                </div>
                
                <div class="step glass-card">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Set Budget</h3>
                    <p class="step-description">
                        Define monthly budgets using campus-specific data. Get smart suggestions based on peer spending.
                    </p>
                </div>
                
                <div class="step glass-card">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Track Spending</h3>
                    <p class="step-description">
                        Log expenses as you go. See real-time spending against your budget with instant categorization.
                    </p>
                </div>
                
                <div class="step glass-card">
                    <div class="step-number">4</div>
                    <h3 class="step-title">Grow Habits</h3>
                    <p class="step-description">
                        Watch your financial awareness grow. See insights that help you spend smarter and save more.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-content">
            <h2>Ready for Financial Confidence?</h2>
            <p>Join students who are already managing their money smarter with Elm.</p>
            <?php if ($isLoggedIn): ?>
                <a href="register.php" class="btn btn-primary"> Get Started 🌿</a>
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
                <p>Smart financial management for students who want to build better money habits and achieve financial independence.</p>
            </div>
            <div class="footer-column">
                <h4>Product</h4>
                <a href="#features">Features</a>
                <a href="#how-it-works">How It Works</a>
                <a href="login.php">Sign In</a>
                <a href="register.php">Sign Up</a>
            </div>
            <div class="footer-column">
                <h4>Resources</h4>
                <a href="#">Student Guide</a>
                <a href="#">Financial Tips</a>
                <a href="#">Help Center</a>
                <a href="#">Blog</a>
            </div>
            <div class="footer-column">
                <h4>Company</h4>
                <a href="#">About Us</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Elm Finance. Empowering students to build better financial futures.</p>
        </div>
    </footer>

    <script>
        // Loading screen
        window.addEventListener('load', () => {
            const loadingScreen = document.getElementById('loadingScreen');
            setTimeout(() => {
                loadingScreen.style.opacity = '0';
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                }, 300);
            }, 1000);
        });

        // Show loading on slow network
        let loadingTimeout = setTimeout(() => {
            document.getElementById('loadingScreen').style.display = 'flex';
        }, 100);

        window.addEventListener('load', () => {
            clearTimeout(loadingTimeout);
        });

        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = themeToggle.querySelector('.theme-icon');
        const html = document.documentElement;

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'light') {
            html.setAttribute('data-theme', 'light');
            themeIcon.textContent = '☀️';
        }

        // Toggle theme
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            themeIcon.textContent = newTheme === 'dark' ? '🌙' : '☀️';
            localStorage.setItem('theme', newTheme);
            
            // Add transition class
            html.classList.add('theme-transition');
            setTimeout(() => {
                html.classList.remove('theme-transition');
            }, 300);
        });

        // Navbar scroll effect
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // ============ SMOOTH SCROLLING ============
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // ============ MOBILE MENU ============
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.querySelector('.nav-links');
        
        mobileMenuBtn.addEventListener('click', () => {
            navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
        });

        // Close mobile menu on resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                navLinks.style.display = 'flex';
            } else {
                navLinks.style.display = 'none';
            }
        });

        // ============ ANIMATION ON SCROLL ============
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fadeInUp');
                }
            });
        }, observerOptions);

        // Observe all feature cards and steps
        document.querySelectorAll('.feature-card, .step').forEach(el => {
            observer.observe(el);
        });

        // ============ PARALLAX EFFECT ============
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const heroVisual = document.querySelector('.hero-visual');
            if (heroVisual) {
                heroVisual.style.transform = `translateY(${scrolled * 0.05}px)`;
            }
        });
    </script>
</body>
</html>