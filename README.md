#  Elm Finance - Personal & Administrative Financial Management

Elm Finance is a premium, all-in-one financial management platform designed specifically for university students. It combines powerful personal budgeting tools with a robust administrative oversight suite, all wrapped in a state-of-the-art Glassmorphism interface.

##  Dual-Role Capabilities

###  For Students (Empowered Personal Finance)
- **Interactive Dashboard**: Real-time overview of current month spending vs. budget.
- **Smart Budgeting**: Set and track category-specific limits (Food, Transport, Essentials, etc.).
- **Transaction Tracking**: Easy entry and management of daily expenses with instant categorization.
- **Goal Setting**: Plan for future expenses with a dedicated financial goals module.
- **Analytics & Reports**: Visual breakdown of spending habits and personalized monthly summaries.

###  For Administrators (System Oversight)
- **Omniscient Dashboard**: Monitor system-wide financial health and growth trends.
- **User Management**: Complete control over user lifecycles, role assignments, and account verification.
- **Audit Reports**: Generate and export high-quality "System Wide" or "Student Detail" PDF/CSV reports.
- **Security Portal**: Centralized monitoring of active sessions and system security health.
- Admin details : username : ohene , Password: ohene1234$ ( to access admin functionalities)

## Security Framework
- **Identity Protection**: Session IP/User-Agent binding & `bcrypt` password hashing.
- **Anti-Exploit**: Comprehensive CSRF tokens, rate limiting, and SQL injection prevention via PDO.
- **Access Control**: Role-based middleware ensuring strict data isolation.

##  Tech Stack
- **Backend**: PHP 7.4+ / 8.x
- **Database**: MySQL / MariaDB (Normalized Schema)
- **Frontend**: Vanilla HTML5, CSS3 (Custom Design System), JavaScript (ES6)
- **Design Essentials**: FontAwesome 6, Google Fonts (Poppins), Glassmorphism UI patterns.

##  Project Hierarchy
```
ElmFinances/
├── config/              # Database & System Configuration
├── includes/            # Core Auth, Security, & Functional logic
├── database/            # SQL Schema & Persistence scripts
├── public/              # Global CSS & Theme assets
├── admin.php            # Administrative Master Dashboard
├── dashboard.php        # Student Financial Overview
├── expenses.php         # Transaction management
├── budgets.php          # Budgeting module
├── goals.php            # Financial goal tracking
├── analytics.php        # Visual data summaries
├── reports.php          # Student/Admin report generator
├── login.php            # Secure Entry Point
└── index.php            # Application Landing Page
```

##  Installation Guide
1. **Prerequisites**: Apache/PHP environment (XAMPP/WAMP/MAMP recommended).
2. **Setup**: Clone the repository and place it in your `htdocs` or equivalent web root.
3. **Database**: Import `database/ElmFinance_database.sql` into your local MySQL server.
4. **Link**: Update database credentials in `config/database.php`.
5. **Access**: Navigate to `http://localhost/ElmFinances/`. Default accounts can be created via the registration portal. 

