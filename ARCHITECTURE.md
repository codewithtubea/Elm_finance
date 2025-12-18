## 📚 COMPLETE ARCHITECTURE & DESIGN PATTERNS
### Understanding How Elm Finance Works

---

## TABLE OF CONTENTS

1. Application Flow
2. Authentication Architecture
3. Security Layers
4. Session Management
5. UI/Theme System
6. Database Schema
7. File Organization
8. Design Patterns Used

---

## 1. APPLICATION FLOW

### User Registration

```
┌─────────────────────────────────────────────────┐
│  User visits register.php                       │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│  Form Validation                                │
│  - Username not empty                           │
│  - Email valid format                           │
│  - Password 8+ chars, upper/lower/numbers       │
│  - Passwords match                              │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│  Rate Limiting Check                            │
│  - Max 3 registrations per IP per hour         │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│  Check for Duplicates                           │
│  - Username in database?                        │
│  - Email in database?                           │
└────────────────┬────────────────────────────────┘
                 │
                 ├─ YES ─▶ Show "Already exists"
                 │
                 ├─ NO ──▶
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│  Hash Password (bcrypt)                         │
│  - Never store plain passwords!                 │
│  - bcrypt adds salt automatically               │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│  Create User Record                             │
│  - Insert into elm_users table                  │
│  - Generate verification token                  │
│  - Set is_verified = 1 (auto for dev)          │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│  Account Created!                               │
│  - Redirect to login                            │
└─────────────────────────────────────────────────┘
```

### User Login

```
┌──────────────────────────────────────────────────┐
│  User visits login.php                          │
│  - Check if already logged in ─▶ Redirect to    │
│    dashboard                                     │
└────────────────┬─────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────┐
│  User submits login form (POST)                 │
│  - Username/email                               │
│  - Password                                     │
│  - CSRF token                                   │
└────────────────┬─────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────┐
│  CSRF Validation                                │
│  - Token in POST === Token in SESSION?          │
└────────────────┬─────────────────────────────────┘
                 │
                 ├─ NO ──▶ Show "Security validation failed"
                 │
                 ├─ YES ─▶
                 │
                 ▼
┌──────────────────────────────────────────────────┐
│  Rate Limiting Check                            │
│  - Max 5 login attempts per IP per 15 mins     │
└────────────────┬─────────────────────────────────┘
                 │
                 ├─ EXCEEDED ──▶ Show "Try again in X minutes"
                 │
                 ├─ OK ─▶
                 │
                 ▼
┌──────────────────────────────────────────────────┐
│  Database Query                                 │
│  - Find user by username OR email               │
│  - Parameterized query (SQL injection safe)     │
└────────────────┬─────────────────────────────────┘
                 │
                 ├─ NOT FOUND ──▶ "Invalid username or password"
                 │
                 ├─ FOUND ─▶
                 │
                 ▼
┌──────────────────────────────────────────────────┐
│  Verify Password                                │
│  - password_verify(user_input, hashed_in_db)   │
└────────────────┬─────────────────────────────────┘
                 │
                 ├─ WRONG ──▶ "Invalid username or password"
                 │
                 ├─ CORRECT ─▶
                 │
                 ▼
┌──────────────────────────────────────────────────┐
│  Check Account Status                           │
│  - is_verified = true?                          │
│  - is_active = true?                            │
└────────────────┬─────────────────────────────────┘
                 │
                 ├─ FAILED ──▶ Show specific error
                 │
                 ├─ OK ─▶
                 │
                 ▼
┌──────────────────────────────────────────────────┐
│  Create User Session                            │
│  1. Regenerate session ID (prevent fixation)    │
│  2. Store in $_SESSION:                         │
│     - user_id                                   │
│     - username                                  │
│     - email                                     │
│     - login_time                                │
│     - ip_address                                │
│     - user_agent                                │
│  3. Update last_login timestamp                 │
└────────────────┬─────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────┐
│  Handle "Remember Me"                           │
│  - If checked: extend cookie to 7 days         │
│  - If unchecked: cookie expires when browser   │
│    closes                                       │
└────────────────┬─────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────┐
│  Redirect to Dashboard (Location header)        │
│  - Immediate, no delay                          │
│  - Session data persists across redirect        │
└──────────────────────────────────────────────────┘
```

---

## 2. AUTHENTICATION ARCHITECTURE

### Session vs Token Authentication

```
SESSION-BASED (What Elm Finance uses)
├─ Server stores session data
├─ Client only has session ID (in cookie)
├─ Session ID is pseudo-random, can't forge
├─ Server validates IP/User-Agent on each request
├─ Scales to ~1000 concurrent users per server
├─ Good for traditional web apps
└─ Example: PHP $_SESSION

TOKEN-BASED (JWT, OAuth)
├─ Server signs token with secret
├─ Client stores token (localStorage/cookie)
├─ Client sends token with each request
├─ Server verifies signature (doesn't need storage)
├─ Scales to millions of users
├─ Good for APIs and SPAs
└─ Example: JSON Web Tokens (JWT)
```

### How Session Security Works

```
REQUEST 1 (Login):
┌────────────────────────────────────────┐
│ Browser sends credentials              │
│ Server creates session                 │
│ Server sets cookie: Set-Cookie: PHPSESSID=abc123
│ Server stores in /tmp/sess_abc123:     │
│ {                                      │
│   "user_id": 1,                        │
│   "ip_address": "192.168.1.100",       │
│   "user_agent": "Chrome 120..."        │
│ }                                      │
└────────────────────────────────────────┘

REQUEST 2 (Access Dashboard):
┌────────────────────────────────────────┐
│ Browser sends: Cookie: PHPSESSID=abc123
│                                        │
│ Server checks:                         │
│ 1. Session file exists? YES            │
│ 2. IP matches? YES ✓                   │
│ 3. User-Agent matches? YES ✓           │
│ 4. Timeout exceeded? NO ✓              │
│                                        │
│ Server loads $_SESSION with data       │
│ Request allowed ✓                      │
└────────────────────────────────────────┘

ATTACK SCENARIO:
┌────────────────────────────────────────┐
│ Attacker steals PHPSESSID=abc123       │
│ Attacker tries from different IP       │
│                                        │
│ Server checks:                         │
│ 1. Session file exists? YES            │
│ 2. IP matches? NO ✗                    │
│                                        │
│ Server: "Hijacking detected!"          │
│ Session destroyed                      │
│ Attacker gets logged out               │
└────────────────────────────────────────┘
```

---

## 3. SECURITY LAYERS

### Defense-in-Depth Approach

```
Layer 1: Input Validation
├─ Username not empty
├─ Email format valid
├─ Password strength required
├─ Prevent malicious input
└─ File: includes/security.php

Layer 2: SQL Injection Prevention
├─ Parameterized queries (prepared statements)
├─ Never concatenate user input in SQL
├─ Bind variables separately
└─ Example: 
   $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
   $stmt->execute([$email]);

Layer 3: CSRF Protection
├─ Generate random token on each page
├─ Store in $_SESSION
├─ Verify on form submission
├─ Token regenerated after login
└─ File: includes/security.php

Layer 4: Rate Limiting
├─ Max 5 login attempts per IP per 15 mins
├─ Max 3 registrations per IP per hour
├─ Prevents brute force attacks
└─ File: includes/security.php

Layer 5: Password Hashing
├─ Use bcrypt (password_hash with PASSWORD_DEFAULT)
├─ Never store plain passwords
├─ Salt added automatically
├─ Verify with password_verify()
└─ Cost: 10 (2^10 = 1024 iterations)

Layer 6: Session Security
├─ Regenerate ID on login
├─ Bind to IP address
├─ Bind to User-Agent
├─ 8-hour timeout
├─ Prevents fixation and hijacking
└─ File: includes/auth.php

Layer 7: HTTP Security Headers
├─ Set in Security::configureSession()
├─ Content-Security-Policy
├─ X-Frame-Options
├─ X-Content-Type-Options
└─ Prevents XSS and clickjacking
```

---

## 4. SESSION MANAGEMENT LIFECYCLE

### Session File Structure

```
/tmp/sess_abc123def456ghi789jkl012mno
│
├─ user_id|i:1;
├─ username|s:4:"john";
├─ email|s:16:"john@example.com";
├─ logged_in|b:1;
├─ login_time|i:1701619200;
├─ session_id|s:26:"abc123def456ghi789jkl012m";
├─ ip_address|s:15:"192.168.1.100";
└─ user_agent|s:50:"Mozilla/5.0...";

Serialized format:
- i: = integer
- s:length: = string
- b: = boolean
- a: = array
- O: = object
```

### Session Lifecycle Timeline

```
T0: User visits login.php
    ├─ session_start() called
    ├─ Session created: sess_random_id
    ├─ $_SESSION['csrf_token'] generated
    └─ Delivered to browser: Set-Cookie: PHPSESSID=sess_random_id

T1: User submits login form
    ├─ Browser sends: Cookie: PHPSESSID=sess_random_id
    ├─ Server calls session_start()
    ├─ $_SESSION populated from sess_random_id file
    ├─ Validation passes
    ├─ New session ID generated (regenerate)
    ├─ Old session file deleted
    ├─ New file created: sess_new_id_12345
    ├─ $_SESSION populated with user data
    ├─ New ID sent to browser
    └─ Redirect to dashboard

T2: User visits dashboard.php
    ├─ Browser sends: Cookie: PHPSESSID=sess_new_id_12345
    ├─ Server reads session file
    ├─ Validates IP and User-Agent
    ├─ Checks 8-hour timeout
    ├─ Session valid ✓
    ├─ User data available
    └─ Dashboard renders

T3: User clicks logout
    ├─ logout.php processes
    ├─ $_SESSION = [] (empty)
    ├─ session_destroy() called
    ├─ Session file deleted
    ├─ Cookie cleared from browser
    └─ Redirect to home page

T4: User tries to access dashboard
    ├─ Browser sends: Cookie: PHPSESSID=sess_new_id_12345
    ├─ Server tries to read session file
    ├─ File doesn't exist (was deleted)
    ├─ !isLoggedIn() returns false
    ├─ Redirect to login.php
    └─ Login required
```

---

## 5. UI/THEME SYSTEM

### CSS Variables Cascade

```
:root (HTML element)
│
└─ Sets --primary-color: #00ff88
   │
   ├─ body inherits --primary-color
   │  │
   │  ├─ .navbar uses var(--primary-color)
   │  │
   │  └─ .btn uses var(--primary-color)
   │
   └─ html.light-mode overrides --primary-color: #333
      │
      ├─ All children now get #333
      └─ No changes to CSS needed!
```

### Theme Toggle Data Flow

```
HTML File Loads
│
▼
JavaScript runs loadTheme()
│
├─ Check localStorage.getItem('theme')
│
├─ If 'light-mode' → add class to html
│
▼
html.light-mode selector matches
│
▼
CSS variables override
│
▼
All elements using var() get light colors
│
▼
Page renders in light mode

User clicks theme button
│
▼
Toggle class on html
│
▼
CSS variables cascade changes
│
▼
Page transitions to dark/light mode
│
▼
Save preference to localStorage
│
▼
On next visit, theme loads automatically
```

---

## 6. DATABASE SCHEMA

### Relationships

```
elm_users (Main table)
├─ id (PK)
├─ username (UNIQUE)
├─ email (UNIQUE)
├─ password_hash
├─ is_verified (boolean)
├─ is_active (boolean)
└─ last_login (timestamp)
   │
   ├─ 1 user has MANY expenses
   │
   └─ 1 user has MANY budgets

elm_expenses (Transaction log)
├─ id (PK)
├─ user_id (FK → elm_users.id)
├─ category (food, transport, etc.)
├─ amount (decimal)
├─ expense_date
└─ created_at
   │
   └─ Used for:
       ├─ Spending analysis
       ├─ Monthly reports
       └─ System-wide financial summaries (Admin)

elm_budgets (Spending limits)
├─ id (PK)
├─ user_id (FK → elm_users.id)
├─ category (food, transport, etc.)
├─ amount (monthly limit)
├─ month_year
└─ created_at
   │
   └─ Used for:
       ├─ Budget tracking
       ├─ Alerts (90% reached)
       └─ Spending predictions

### Dual-Level Reporting System
The system implements a role-aware reporting engine using a "View then Export" workflow:

- **Admin Level**:
    - **System-Wide Summary**: Aggregates data from all users into a single executive PDF.
    - **Student Detail**: Filters and generates a deep-dive report for a specific selected student.
    - **User Selection**: Admins see a filtered list of only student-role users for specific reports.

- **Student Level**:
    - **Self-Service Portal**: Students have a simplified reporting archive (`reports.php`) to generate their own summaries.
    - **Personal Privacy**: Students can *only* see and generate reports for their own data.

- **Shared Export Engine** (`adminreportview.php`):
    - A premium, print-optimized view with high-quality CSS gradients and typography.
    - Role-aware security to determine access levels.
    - "Save as PDF" functionality utilizing native browser print-to-PDF for maximum visual fidelity.
elm_reports (Admin Generated Reports)
├─ id (PK)
├─ report_type (e.g., 'Financial Summary')
├─ report_data (JSON blob containing summary, categories, high_value_items)
├─ generated_by (FK → elm_users.id)
└─ created_at

### Normalized vs Denormalized

```
NORMALIZED (Elm Finance uses this)
├─ elm_users: user information
├─ elm_expenses: each transaction separate
├─ elm_budgets: budget limits
├─ Advantages:
│  ├─ No data duplication
│  ├─ Consistent data
│  └─ Easy to update
└─ Disadvantage: More queries needed

DENORMALIZED (Not recommended)
├─ users_with_totals table:
│  ├─ user_id
│  ├─ username
│  ├─ total_spent
│  ├─ total_budget
│  ├─ last_expense_date
│  └─ (duplicates calculation results)
└─ Disadvantage: Must update multiple places
```

---

## 7. FILE ORGANIZATION

### Directory Structure with Purposes

```
ElmFinances/
│
├─ index.php ─────────────────── Landing page (public)
├─ login.php ─────────────────── Login form & processing
├─ register.php ───────────────── Registration form
├─ logout.php ─────────────────── Session termination
├─ admin.php ──────────────────── Admin Dashboard (Protected)
├─ adminusers.php ─────────────── User Management (Admin only)
├─ adminreports.php ───────────── Financial Reporting (Admin only)
├─ adminsecurity.php ──────────── Security Overview (Admin only)
│
├─ config/
│  ├─ database.php ───────────── PDO connection setup
│  ├─ constants.php ─────────── App-wide constants
│  └─ environment.php ───────── Environment config
│
├─ includes/
│  ├─ auth.php ─────────────── Login/register/session logic
│  └─ security.php ────────── CSRF, validation, rate limiting
│
├─ classes/ ──────────────── Additional business logic
│
├─ public/
│  ├─ css/ ────────────────── Stylesheets
│  │  ├─ style.css ─────────── Main styles
│  │  ├─ auth.css ────────── Login/register styles
│  │  ├─ dashboard.css ───── Dashboard styles
│  │  └─ components.css ─── Reusable components
│  ├─ js/ ────────────────── JavaScript
│  │  ├─ app.js ────────── Main app logic
│  │  ├─ dashboard.js ──── Dashboard features
│  │  ├─ form-validation.js  Form checking
│  │  └─ charts.js ──────── Chart rendering
│  └─ images/ ─────────────── Image assets
│
├─ pages/ ──────────────── Additional pages
│
├─ api/ ────────────────── API endpoints (if needed)
│
├─ utils/ ──────────────── Utility functions
│
├─ .htaccess ───────────── Apache rewrite rules
├─ .env ────────────────── Environment variables (git ignored)
├─ .gitignore ──────────── What to exclude from git
│
└─ Documentation/
   ├─ README.md ───────── Quick start guide
   ├─ DEVELOPMENT_GUIDE.md  Detailed explanations
   ├─ CHANGES_SUMMARY.md  What was changed and where
   ├─ EXPERIMENTS.md ──── Hands-on code examples
   └─ ARCHITECTURE.md ─── This file
```

---

## 8. DESIGN PATTERNS USED

### 1. MVC-lite (Model-View-Controller)

```
Model: includes/auth.php
├─ Database operations
├─ Business logic
├─ Data validation
└─ Doesn't know about HTML

View: login.php (HTML part)
├─ Displays form
├─ Shows errors/success
├─ No database access
└─ User interaction only

Controller: login.php (PHP part)
├─ Handles form submission
├─ Calls Model (Auth class)
├─ Passes data to View
└─ Orchestrates flow
```

### 2. Singleton Pattern (Database)

```
// Single database connection used everywhere
$pdo = new PDO($dsn, $user, $pass);

// Instead of:
$pdo1 = new PDO($dsn, $user, $pass);
$pdo2 = new PDO($dsn, $user, $pass);
$pdo3 = new PDO($dsn, $user, $pass);
// Would create 3 connections (wasteful)

// One connection passed to all classes:
$auth = new Auth($pdo);
// $auth can use $pdo internally
```

### 3. Dependency Injection

```
// Constructor receives dependencies
public function __construct($database) {
    $this->db = $database;
}

// Instead of:
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new PDO(...);  // Tightly coupled
    }
}

// Advantages:
├─ Easy to test (inject mock database)
├─ Easy to change (swap implementation)
└─ Explicit dependencies (clear what's needed)
```

### 4. Factory Pattern (Prepared Statements)

```
// PDO acts like a factory for prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
// Returns PDOStatement object

// Each statement is independent
$stmt1 = $pdo->prepare("INSERT INTO users ...");
$stmt2 = $pdo->prepare("UPDATE users ...");
$stmt3 = $pdo->prepare("DELETE FROM users ...");
```

### 5. Validator Pattern (Static Methods)

```
// Static methods = utility functions in a class
Validator::sanitizeInput($input);
Security::generateToken();
Security::checkRateLimit($key, $limit, $time);

// Instead of random functions floating around:
function sanitize_input() { ... }
function generate_token() { ... }

// Advantages:
├─ Namespacing (prevents conflicts)
├─ Organization (related functions together)
└─ Clear origin (know where it comes from)
```

### 6. Template Pattern

```
// HTML forms follow same pattern
├─ Page setup (session, auth check)
├─ Get data from database
├─ Process form if POST
├─ Display errors/success
├─ Render HTML with data
└─ Close connections
```

---

## SECURITY CHECKLIST FOR DEPLOYMENT

- [ ] Remove test-auth.php
- [ ] Remove DEVELOPMENT_GUIDE.md
- [ ] Set `display_errors = false` in php.ini
- [ ] Enable HTTPS/SSL certificate
- [ ] Set `SESSION_SECURE_ONLY = true`
- [ ] Set strong unique `SESSION_NAME`
- [ ] Database user: minimal permissions
- [ ] Enable database connection encryption
- [ ] Set up firewall rules
- [ ] Regular security audits
- [ ] Monitor logs for suspicious activity
- [ ] Implement 2FA for admin
- [ ] Set up backup strategy
- [ ] Enable CORS headers if needed
- [ ] Rate limiting on API endpoints

---

## PERFORMANCE OPTIMIZATIONS

Already implemented:
- Prepared statements (prevent N+1 queries)
- Session caching (no database hit per request)
- CSS variables (no repeated calculations)

Future improvements:
- Database query caching
- ORM (Doctrine, Eloquent)
- Compiled templates
- Redis sessions
- CDN for static assets
- API rate limiting
- Query optimization indexes

---

## TESTING STRATEGY

### Unit Tests (Single function)
```
Test Auth::validatePassword()
Test Validator::sanitizeInput()
Test Security::generateToken()
```

### Integration Tests (Multiple functions)
```
Test complete login flow
Test budget creation and expense tracking
Test session persistence
```

### Security Tests
```
Test SQL injection attempts
Test CSRF token validation
Test rate limiting
Test session hijacking
```

---

## NEXT STEPS 

1. ✅ Understand current architecture
2. Read all documentation files
3. Run the experiments
4. Modify code and see what breaks
5. Write your own tests
6. Deploy to production
7. Monitor and optimize
8. Scale to handle growth

---

**Elm Finance**
