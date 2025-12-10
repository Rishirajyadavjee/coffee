<?php
// Simple admin navigation helper
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}
?>

<style>
.admin-nav {
    background: #6B4423;
    padding: 1rem 0;
    margin-bottom: 2rem;
}

.admin-nav-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    gap: 2rem;
    padding: 0 2rem;
}

.admin-nav a {
    color: white;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    transition: background 0.3s;
}

.admin-nav a:hover,
.admin-nav a.active {
    background: #D2691E;
}
</style>

<div class="admin-nav">
    <div class="admin-nav-container">
        <a href="admin.php" <?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'class="active"' : ''; ?>>
            <i class="fas fa-users"></i> Users & Sales
        </a>
        <a href="admin/dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>
            <i class="fas fa-coffee"></i> Products
        </a>
        <a href="index.php">
            <i class="fas fa-home"></i> Main Site
        </a>
    </div>
</div>