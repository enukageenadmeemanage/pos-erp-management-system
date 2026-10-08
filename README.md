# Enterprise Point of Sale (POS) & ERP Management System

A web-based Point of Sale, Inventory Control, and Human Resource Management System built using PHP, JavaScript, and MySQL to streamline retail operations, staff attendance, payroll processing, and multi-channel reporting.

---

## 📌 Features & Core Modules

### 1. Point of Sale (POS) & Billing
- **Interactive POS Interface (`pos.php`):** Real-time product selection, cart updates, barcode search, and dynamic total/discount calculations.
- **Instant Receipt Generation (`print/receipt.php`):** Clean thermal/standard print-ready receipts with automated tax and order breakdown.

### 2. Inventory & Supply Chain Management
- **Product & Category Cataloging (`products.php`, `categories.php`):** Stock monitoring, pricing rules, and item categorization.
- **Supplier & Purchase Order Handling (`suppliers.php`, `purchases.php`):** Vendor directory tracking and inventory procurement workflows.

### 3. Human Resources, Attendance & Payroll
- **Staff Records (`staff.php`, `users.php`):** Employee profiles, contact info, and user access levels.
- **Daily Attendance Tracking (`attendance.php`):** Clock-in and presence monitoring.
- **Automated Payroll Processing (`payroll.php`, `print/payslip.php`):** Salary computation, deduction calculations, and printable payslips.

### 4. Sales Insights & Centralized Reporting
- **Business Dashboard (`dashboard.php`):** High-level operational metrics and revenue KPIs.
- **Sales & Performance Analytics (`sales.php`, `reports.php`):** Timeframe-based revenue and inventory auditing.

### 5. Application Security & Access Control
- **Role-Based Authentication (`auth.php`, `login.php`):** Secure login, logout, and session lifecycle controls.
- **CSRF Protection (`csrf.php`):** Token validation for sensitive transactional and update forms.
- **Server-Side Hardening (`.htaccess`):** Route configuration and secure file handling.

---

## 🛠️ Tech Stack
- **Backend:** PHP (Modular MVC-style functional architecture)
- **Frontend:** HTML5, CSS3, JavaScript (DOM manipulation & AJAX for POS operations)
- **Database:** MySQL
- **Print Utilities:** Custom CSS-driven printable layouts for receipts and payslips

---

## 📁 Repository Structure
```text
pos-erp-management-system/
├── assets/
│   ├── css/          # Application stylesheets
│   ├── js/           # App and POS frontend logic
│   └── images/       # Static visual assets
├── config/           # App settings and database connection
├── includes/         # Headers, sidebars, auth, CSRF, and shared utilities
├── pages/            # Core business modules (POS, Inventory, Staff, Payroll)
├── print/            # Printable receipts and employee payslips
├── .htaccess         # Apache web server routing configuration
├── index.php         # Application entry point
├── login.php         # User authentication
└── reset.php         # Password reset workflow
```

---

## 🚀 Local Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone [https://github.com/](https://github.com/)<your-username>/pos-erp-management-system.git
   ```
2. **Move to your local web server:**
   - Place inside `htdocs` (XAMPP) or `www` (WampServer / LAMP stack).
3. **Database Configuration:**
   - Open `config/database.php` and configure your MySQL host, database name, user, and password.
4. **Launch Application:**
   - Open your browser and navigate to `http://localhost/pos-erp-management-system/login.php`.
