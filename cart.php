<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Handle quantity updates
if ($_POST && isset($_POST['update_quantity'])) {
    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity > 0) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
    }
    
    redirect('cart.php');
}

// Handle item removal
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    redirect('cart.php');
}

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.image_path, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");

$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - BrewMaster Coffee</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
 <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f8f9fa;
        padding-top: 80px;
    }

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

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .page-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .page-title {
        font-size: 3rem;
        color: #6B4423;
        margin-bottom: 1rem;
    }

    .cart-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 3rem;
    }

    .cart-items {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .cart-header {
        background: #6B4423;
        color: white;
        padding: 1.5rem;
        font-size: 1.3rem;
        font-weight: bold;
    }

    .cart-item {
        display: grid;
        grid-template-columns: 100px 1fr auto auto auto;
        gap: 1rem;
        padding: 1.5rem;
        border-bottom: 1px solid #eee;
        align-items: center;
    }

    .cart-item:last-child {
        border-bottom: none;
    }

    .item-image {
        width: 80px;
        height: 80px;
        background: linear-gradient(45deg, #D2691E, #CD853F);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .item-info h3 {
        color: #6B4423;
        margin-bottom: 0.5rem;
    }

    .item-price {
        color: #D2691E;
        font-weight: bold;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .quantity-btn {
        width: 30px;
        height: 30px;
        border: 2px solid #D2691E;
        background: white;
        color: #D2691E;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .quantity-btn:hover {
        background: #D2691E;
        color: white;
    }

    .quantity-input {
        width: 50px;
        text-align: center;
        padding: 5px;
        border: 2px solid #ddd;
        border-radius: 5px;
    }

    .item-total {
        font-size: 1.2rem;
        font-weight: bold;
        color: #6B4423;
    }

    .remove-btn {
        color: #dc3545;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0.5rem;
    }

    .remove-btn:hover {
        color: #c82333;
    }

    .cart-summary {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        height: fit-content;
    }

    .summary-title {
        font-size: 1.5rem;
        color: #6B4423;
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }

    .summary-row:last-child {
        border-bottom: 2px solid #6B4423;
        font-weight: bold;
        font-size: 1.2rem;
        color: #6B4423;
    }

    .btn {
        display: block;
        width: 100%;
        padding: 15px;
        background: linear-gradient(45deg, #D2691E, #FF8C00);
        color: white;
        text-decoration: none;
        border-radius: 10px;
        font-weight: bold;
        text-align: center;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        font-size: 1.1rem;
        margin-top: 2rem;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(210, 105, 30, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        margin-top: 1rem;
    }

    .empty-cart {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .empty-cart i {
        font-size: 5rem;
        color: #D2691E;
        margin-bottom: 2rem;
    }

    .empty-cart h3 {
        color: #6B4423;
        margin-bottom: 1rem;
        font-size: 2rem;
    }

    .empty-cart p {
        color: #666;
        margin-bottom: 2rem;
        font-size: 1.1rem;
    }

    @media (max-width: 768px) {
        .nav-links {
            display: none;
        }

        .cart-container {
            grid-template-columns: 1fr;
        }

        .cart-item {
            grid-template-columns: 80px 1fr;
            gap: 1rem;
        }

        .item-controls {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
    }
</style>
</head>

<body>
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
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                <?php if (isAdmin()): ?>
                <li><a href="admin.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Shopping Cart</h1>
        </div>

        <?php if (count($cart_items) > 0): ?>
        <div class="cart-container">
            <div class="cart-items">
                <div class="cart-header">
                    <i class="fas fa-shopping-cart"></i> Your Items (<?php echo count($cart_items); ?>)
                </div>

                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="item-image">
                        <i class="fas fa-coffee"></i>
                    </div>

                    <div class="item-info">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <div class="item-price">$<?php echo number_format($item['price'], 2); ?> each</div>
                        <?php if ($item['stock'] < $item['quantity']): ?>
                        <small style="color: #dc3545;">Only <?php echo $item['stock']; ?> in stock</small>
                        <?php endif; ?>
                    </div>

                    <div class="quantity-controls">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>">
                            <button type="submit" name="update_quantity" class="quantity-btn">+</button>
                        </form>
                    </div>

                    <div class="item-total">
                        $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </div>

                    <a href="cart.php?remove=<?php echo $item['id']; ?>" class="remove-btn"
                        onclick="return confirm('Remove this item from cart?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h3 class="summary-title">Order Summary</h3>

                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>

                <div class="summary-row">
                    <span>Tax (8%):</span>
                    <span>$<?php echo number_format($total * 0.08, 2); ?></span>
                </div>

                <div class="summary-row">
                    <span>Shipping:</span>
                    <span><?php echo $total > 50 ? 'FREE' : '$5.99'; ?></span>
                </div>

                <div class="summary-row">
                    <span>Total:</span>
                    <span>$<?php echo number_format($total + ($total * 0.08) + ($total > 50 ? 0 : 5.99), 2); ?></span>
                </div>

                <a href="checkout.php" class="btn">
                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                </a>

                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any items to your cart yet. Start exploring our amazing coffee collection!
            </p>
            <a href="products.php" class="btn">Browse Products</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function updateQuantity(cartId, quantity) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
                <input type="hidden" name="cart_id" value="${cartId}">
                <input type="hidden" name="quantity" value="${quantity}">
                <input type="hidden" name="update_quantity" value="1">
            `;
        document.body.appendChild(form);
        form.submit();
    }
    </script>
</body>

</html>

