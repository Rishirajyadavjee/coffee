<style>
    .navbar {
        background: linear-gradient(135deg, #6B4423, #8B4513);
        padding: 1rem 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
    }

    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 2rem;
    }

    .logo {
        color: white;
        font-size: 1.8rem;
        font-weight: bold;
        text-decoration: none;
    }

    .nav-links {
        display: flex;
        list-style: none;
        gap: 2rem;
    }

    .nav-links a {
        color: white;
        text-decoration: none;
        transition: color 0.3s;
        font-weight: 500;
    }

    .nav-links a:hover {
        color: #D2691E;
    }
</style>

<?php
// Get cart count if user is logged in
$cart_count = 0;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetchColumn() ?: 0;
}
?>

<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">
            <i class="fas fa-coffee"></i> BrewMaster 
        </a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="gallery.php">Gallery</a></li>
            <li><a href="about_us.php">About us</a></li>

            <?php if (isLoggedIn()): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart<?php if ($cart_count > 0) echo " ($cart_count)"; ?></a></li>
                <li><a href="profile.php">Profile</a></li>

                <?php if (isAdmin()): ?>
                    <li><a href="admin/admin_dashboard.php">Admin</a></li>
                <?php endif; ?>

                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

