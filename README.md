# 🌿 Elm Finance - Student Financial Management System

A secure, modern financial management system designed specifically for university students to track expenses, manage budgets, and gain spending insights.

## 🎯 Features

### Core Functionality
- **User Authentication**: Secure login/register with CSRF protection and rate limiting
- **Expense Tracking**: Log daily expenses with categorization
- **Budget Management**: Set monthly budgets per category
- **Financial Insights**: Compare your spending against campus averages
- **Dashboard Analytics**: Visual overview of spending patterns

### Security Features
- ✅ Password hashing (bcrypt via PHP's `password_hash()`)
- ✅ CSRF token protection on all forms
- ✅ Rate limiting (login attempts, registration)
- ✅ Session hijacking prevention (IP & User-Agent validation)
- ✅ SQL injection protection (parameterized queries)
- ✅ XSS protection (output escaping)
- ✅ Session timeout (8 hours)
- ✅ Secure session configuration

### User Experience
- Modern neon-styled UI with smooth animations
- Real-time form validation
- Responsive design for mobile & desktop
- Campus spending benchmarks
- One-click budget alerts

## 📋 Project Structure

```
ElmFinances/
├── config/
│   ├── database.php          # Database connection
│   ├── constants.php         # Application constants
│   └── environment.php       # Environment configuration
├── includes/
│   ├── auth.php             # Authentication logic (login, register, session)
│   └── security.php         # Security utilities (CSRF, rate limiting, validation)
├── classes/                 # Additional class files
├── public/
│   ├── css/                 # Stylesheets
│   │   ├── style.css        # Main styles
│   │   ├── auth.css         # Auth page styles
│   │   ├── dashboard.css    # Dashboard styles
│   │   └── components.css   # Component styles
│   ├── js/                  # JavaScript files
│   │   ├── app.js           # Main app logic
│   │   ├── dashboard.js     # Dashboard functionality
│   │   ├── charts.js        # Chart rendering
│   │   ├── form-validation.js
│   │   └── auth.js
│   └── images/
├── pages/                   # Additional pages
├── api/                     # API endpoints
├── dashboard.php            # Main dashboard
├── login.php               # Login page
├── register.php            # Registration page
├── logout.php              # Logout handler
├── index.php               # Homepage
├── test-auth.php           # Auth debugging tool (dev only)
├── test-setup.php          # Setup verification
└── README.md               # This file
```

## 🚀 Quick Start

### Requirements
- PHP 7.4+ (or compatible PHP 8.x)
- MySQL / MariaDB
- Local server environment such as XAMPP (Windows), MAMP (Mac), or LAMP (Linux)
- Browser to access `http://localhost/ElmFinances/`

### Setup (XAMPP Windows)

1. **Start XAMPP** and enable Apache and MySQL services

2. **Create database** using phpMyAdmin:
   ```sql
   CREATE DATABASE elm_finance;
   ```

3. **Update credentials** in `config/database.php`:
   ```php
   $host = 'localhost';
   $db = 'elm_finance';
   $user = 'root';
   $pass = '';  // Change if you set MySQL password
   ```

4. **Verify setup** by visiting:
   - `http://localhost/ElmFinances/test-setup.php` (verify configuration)
   - `http://localhost/ElmFinances/` (access application)

5. **Register and login** with your account

## 📱 Usage Guide

### Creating an Account
1. Click "Create your account" on the login page
2. Fill in required fields (username, email, password)
3. Password must contain uppercase, lowercase, and numbers
4. Submit and log in with your credentials

### Logging In
- Enter username or email
- Enter password
- Check "Keep me logged in for 7 days" (optional)
- Click Login → Redirected to dashboard

### Tracking Expenses
1. Navigate to **Expenses** page
2. Click "Add Expense"
3. Select category (Food, Transport, Essentials, Entertainment, Other)
4. Enter amount and date
5. Save

### Setting Budget
1. Go to **Budget** page
2. Set monthly budget limits by category
3. System alerts when you reach 90% of budget
4. Adjust as needed

### Viewing Insights
1. Navigate to **Insights** page
2. Compare your spending vs. campus averages
3. View personalized recommendations
4. Track progress toward goals

### Debug Authentication (Dev Only)
- Visit `http://localhost/ElmFinances/test-auth.php`
- Shows current session status, user data, and auth state
- Only accessible from localhost
- **Remove in production**

## 🔐 Authentication System

### Login Flow
```
User submits credentials
    ↓
Rate limiting check (max 5 attempts in 15 mins)
    ↓
Database validation (username/email + password verify)
    ↓
Email verification check
    ↓
Account status check
    ↓
Session creation (encrypted, IP-bound)
    ↓
Redirect to dashboard
```

### Session Management
- Sessions include user ID, username, email, IP, user agent
- Sessions regenerated on login for security
- 8-hour timeout automatically logs user out
- IP/User-Agent mismatch triggers automatic logout
- Remember-me extends cookie to 7 days

### CSRF Protection
- All forms include hidden CSRF token
- Token verified on POST requests
- New token generated per session

## 🛡️ Security Best Practices

### Implemented ✅
- Parameterized SQL queries (prevents SQL injection)
- Password hashing (bcrypt)
- HTTPS-ready (set in `security.php`)
- Session fixation prevention
- Output escaping (prevents XSS)
- Rate limiting on login/register
- CSRF token validation
- Security headers

### Production Recommendations
- [ ] Use HTTPS/SSL certificate
- [ ] Set `SESSION_SECURE_ONLY = true` in `config/environment.php`
- [ ] Remove `test-auth.php` and `test-setup.php`
- [ ] Set strong `SESSION_NAME` value
- [ ] Enable database connection encryption
- [ ] Implement 2FA for sensitive operations
- [ ] Regular security audits
- [ ] Monitor logs for suspicious activity

## 🐛 Troubleshooting

### Login Not Working
1. Verify credentials are correct
2. Check if account is verified (email)
3. Verify account is active (not deactivated)
4. Visit `test-auth.php` to debug session
5. Check browser console for JavaScript errors

### Rate Limited
- Too many login attempts? Wait 15 minutes
- Too many registrations? Try again later
- Check server logs for rate limit details

### Session Expires Immediately
- Check if IP matches (especially on mobile/VPN)
- Verify user agent isn't being blocked
- Check 8-hour session timeout setting
- Clear browser cookies and try again

### 500 Internal Server Error
- Check PHP error logs
- Verify database connection in `config/database.php`
- Ensure all required tables exist
- Check file permissions (writeable for uploads)
- Visit `test-setup.php` to verify configuration

### "No Authentication" Messages on Dashboard
- This has been fixed in the latest version
- Auth status now displays at top of dashboard
- Shows username, email, last login time
- Visit `test-auth.php` to verify session

## 📊 Database Schema

### elm_users Table
```sql
CREATE TABLE elm_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    university VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### elm_expenses Table
```sql
CREATE TABLE elm_expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES elm_users(id)
);
```

### elm_budgets Table
```sql
CREATE TABLE elm_budgets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    month_year DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES elm_users(id)
);
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit changes (`git commit -m 'Add your feature'`)
4. Push to branch (`git push origin feature/your-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License. See LICENSE file for details.

## 👨‍💻 Developer

**Code with Tubea**  
GitHub: [@codewithtubea](https://github.com/codewithtubea)

## ⚡ Useful Commands

```bash
# Clone the repository
git clone https://github.com/codewithtubea/Elm_finance.git
cd ElmFinances

# Start PHP development server (if not using XAMPP)
php -S localhost:8000

# Access application
# http://localhost/ElmFinances/
# or
# http://localhost:8000

# View authentication debug
# http://localhost/ElmFinances/test-auth.php

# View setup status
# http://localhost/ElmFinances/test-setup.php
```

## 📞 Support & Issues

For issues or questions:
1. Check the **Troubleshooting** section above
2. Review error logs (`error_log` or server logs)
3. Visit debug pages (`test-auth.php`, `test-setup.php`)
4. Open an issue on [GitHub](https://github.com/codewithtubea/Elm_finance/issues)

## 🔄 Recent Updates (Dec 4, 2025)

- ✅ Fixed login redirect (immediate `header()` instead of refresh)
- ✅ Fixed session creation (removed problematic `session_write_close()`)
- ✅ Added authentication status bar to dashboard
- ✅ Enhanced login error messages with visual feedback
- ✅ Added `test-auth.php` for session debugging
- ✅ Improved session validation and logging
- ✅ Updated README with comprehensive documentation

---

**Version**: 1.0.1  
**Last Updated**: December 4, 2025  
**Status**: Production Ready
