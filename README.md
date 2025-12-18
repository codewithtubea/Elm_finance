# 🌿 Elm Finance - Admin Management System

A premium, secure financial oversight platform built for administrative control. Elm Finance features a state-of-the-art Glassmorphism UI, real-time analytics, and comprehensive user management.

## 🎯 Core Features

### 👑 Administrative Suite
- **Omniscient Dashboard**: View system-wide metrics, total user expenditures, and growth trends.
- **User Lifecycle Management**: Monitor registrations, update user roles, and manage accounts with self-deletion protection.
- **Unified Dashboard**: Personalized overview for both students and administrators.
- **Dual-Level Reporting**: Role-aware reporting system (System-Wide for Admins, Personal for Students).
- **Premium PDF Export**: High-quality "View then Print" reports with custom branding.
- **Smart Analytics**: Real-time spending trends, daily averages, and budget tracking.
- **Security Portal**: Centralized monitoring of session security and configuration status.

### 🛡️ Security First
- **bcrypt Hashing**: Industry-standard password encryption.
- **CSRF Defense**: Strict token validation on all administrative actions.
- **Middleware Authorization**: Robust `requireAdmin()` gatekeeping.
- **Session Protection**: IP & User-Agent binding with automatic hijacking detection.
- **Rate Limiting**: Brute-force protection on all entry points.

### 🎨 Design & UX
- **Glassmorphism UI**: High-end aesthetics with backdrop-filters and neon glow effects.
- **Unified Theme Control**: Centralized dark/light mode persistence via `localStorage`.
- **Responsive Navigation**: Optimized for both high-resolution monitors and mobile management.

## 🚀 Tech Stack

- **Backend**: PHP 7.4+ / 8.x
- **Database**: MySQL / MariaDB (PDO for secure transactions)
- **Frontend**: Vanilla HTML5, CSS3 (Advanced Variables & Gradients), Modern JavaScript (ES6+)
- **Icons**: FontAwesome 6.4.0
- **Typography**: Google Fonts (Poppins)

## 📋 Project Structure

```
ElmFinances/
├── admin.php            # Master Dashboard
├── adminusers.php       # User Management portal
├── adminreports.php      # Reporting & Export system
├── adminsecurity.php     # Security Monitoring portal
├── login.php            # Secure Entry Point
├── profile.php          # Admin Profile management
├── includes/
│   ├── auth.php         # Core Identity Service
│   └── sidebar.php      # Unified Admin Navigation
└── public/
    ├── css/style.css    # Premium Design System
    └── js/theme-manager.js # Theme & Persistance logic
```

## ⚙️ Setup Instructions

1. **Environment**: Ensure XAMPP/WAMP (Apache & MySQL) is running.
2. **Database**: Create `elm_finance` and import the schema.
3. **Configuration**: Set credentials in `config/database.php`.
4. **Promotion**: To access admin features, ensure your user record in `elm_users` has `role = 'admin'`.
5. **Access**: Navigate to `http://localhost/ElmFinances/`.

---

**Version**: 2.0.0 (Dec 18, 2025)  
**Status**: Stable / Optimized
