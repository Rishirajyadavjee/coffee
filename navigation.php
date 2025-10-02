<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">
            <i class="fas fa-coffee"></i> BrewMaster
        </a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="gallery.php">Gallery</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
             <li><a href="profile.php">profile</a></li>

            <?php if (isLoggedIn()): ?>
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart (<?php echo $cart_count; ?>)</a></li>
         

                <?php if (isAdmin()): ?>
                    <li><a href="admin.php">Admin</a></li>
                <?php endif; ?>

                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
