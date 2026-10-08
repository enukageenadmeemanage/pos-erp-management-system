<?php
$current_page = basename($_SERVER['SCRIPT_NAME']);
$role = $_SESSION['user_role'] ?? '';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-shopping-basket text-primary"></i>
            <span>FreshMart</span>
        </div>
    </div>
    <ul class="sidebar-menu">
        <li class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        </li>
        
        <li class="<?= $current_page == 'pos.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/pos.php" class="text-primary"><i class="fas fa-cash-register"></i> <span>POS / Billing</span></a>
        </li>

        <?php if (in_array($role, ['admin', 'manager'])): ?>
        <li class="menu-header">Inventory</li>
        <li class="<?= $current_page == 'products.php' || $current_page == 'product_form.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/products.php"><i class="fas fa-box"></i> <span>Products</span></a>
        </li>
        <li class="<?= $current_page == 'categories.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/categories.php"><i class="fas fa-tags"></i> <span>Categories</span></a>
        </li>
        <li class="<?= $current_page == 'suppliers.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/suppliers.php"><i class="fas fa-truck"></i> <span>Suppliers</span></a>
        </li>
        <li class="<?= $current_page == 'purchases.php' || $current_page == 'purchase_form.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/purchases.php"><i class="fas fa-shopping-cart"></i> <span>Purchases (Stock)</span></a>
        </li>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'manager', 'accountant'])): ?>
        <li class="menu-header">Sales</li>
        <li class="<?= $current_page == 'sales.php' || $current_page == 'sale_view.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/sales.php"><i class="fas fa-receipt"></i> <span>Sales History</span></a>
        </li>
        <li class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
        </li>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'accountant', 'manager'])): ?>
        <li class="menu-header">HR & Payroll</li>
        <li class="<?= $current_page == 'staff.php' || $current_page == 'staff_form.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/staff.php"><i class="fas fa-id-badge"></i> <span>Staff</span></a>
        </li>
        <li class="<?= $current_page == 'attendance.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/attendance.php"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a>
        </li>
        <?php if (in_array($role, ['admin', 'accountant'])): ?>
        <li class="<?= $current_page == 'payroll.php' || $current_page == 'payroll_form.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/payroll.php"><i class="fas fa-money-check-alt"></i> <span>Payroll</span></a>
        </li>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
        <li class="menu-header">Administration</li>
        <li class="<?= $current_page == 'users.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/users.php"><i class="fas fa-users-cog"></i> <span>Users</span></a>
        </li>
        <li class="<?= $current_page == 'settings.php' ? 'active' : '' ?>">
            <a href="<?= BASE_URL ?>/pages/settings.php"><i class="fas fa-cogs"></i> <span>Settings</span></a>
        </li>
        <?php endif; ?>
    </ul>
</aside>
