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

// Handle clear cart
if ($_POST && isset($_POST['clear_cart'])) {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
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
            width: 35px;
            height: 35px;
            border: 2px solid #D2691E;
            background: white;
            color: #D2691E;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.3s;
        }

        .quantity-btn:hover:not(:disabled) {
            background: #D2691E;
            color: white;
            transform: scale(1.05);
        }

        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8f9fa;
            color: #6c757d;
            border-color: #dee2e6;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1rem;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #D2691E;
        }

        .item-total {
            font-size: 1.3rem;
            font-weight: bold;
            color: #6B4423;
        }

        .item-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .remove-btn {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }

        .remove-btn:hover {
            background: linear-gradient(45deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .remove-btn:active {
            transform: translateY(0);
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

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333) !important;
        }

        .btn-danger:hover {
            background: linear-gradient(45deg, #c82333, #bd2130) !important;
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
    
<?php include_once("navigation.php"); ?>
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
                                <div class="item-image">
                                    <?php if (!empty($item['image_path'])): ?>
                                        <img src="<?php echo 'images/' . htmlspecialchars(basename($item['image_path'])); ?>"
                                            alt="<?php echo htmlspecialchars($item['name']); ?>"
                                            style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;">
                                    <?php else: ?>
                                        <i class="fas fa-coffee"></i>
                                    <?php endif; ?>
                                </div>


                            </div>

                            <div class="item-info">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <div class="item-price">₹<?php echo number_format($item['price'], 2); ?> each</div>
                                <?php if ($item['stock'] < $item['quantity']): ?>
                                    <small style="color: #dc3545;">Only <?php echo $item['stock']; ?> in stock</small>
                                <?php endif; ?>
                            </div>

                            <div class="quantity-controls">
                                <!-- Minus button -->
                                <button type="button" class="quantity-btn"
                                    onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo max(1, $item['quantity'] - 1); ?>)"
                                    <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-minus"></i>
                                </button>

                                <!-- Quantity input -->
                                <input type="number" class="quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="<?php echo $item['stock']; ?>"
                                       onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">

                                <!-- Plus button -->
                                <button type="button" class="quantity-btn"
                                    onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                    <?php echo $item['quantity'] >= $item['stock'] ? 'disabled' : ''; ?>>
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>

                            <div class="item-total">
                                ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>

                            <div class="item-actions">
                                <button type="button" class="remove-btn" 
                                        onclick="removeFromCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <h3 class="summary-title">Order Summary</h3>

                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Tax (8%):</span>
                        <span>₹<?php echo number_format($total * 0.08, 2); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span><?php echo $total > 50 ? 'FREE' : '₹5.99'; ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Total:</span>
                        <span>₹<?php echo number_format($total + ($total * 0.08) + ($total > 50 ? 0 : 5.99), 2); ?></span>
                    </div>

                    <a href="checkout.php" class="btn">
                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                    </a>

                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>

                    <button type="button" class="btn btn-danger" onclick="clearCart()" style="background: linear-gradient(45deg, #dc3545, #c82333); margin-top: 1rem;">
                        <i class="fas fa-trash-alt"></i> Clear Cart
                    </button>
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
<?php include_once("footer.php"); ?>
    <script>
        function updateQuantity(cartId, quantity) {
            // Validate quantity
            quantity = parseInt(quantity);
            if (quantity < 1) {
                quantity = 1;
            }

            // Show loading state
            const quantityInput = document.querySelector(`input[onchange*="${cartId}"]`);
            if (quantityInput) {
                quantityInput.disabled = true;
            }

            // Create and submit form
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

        function removeFromCart(cartId, productName) {
            // Show confirmation dialog
            const confirmed = confirm(`Are you sure you want to remove "${productName}" from your cart?`);
            
            if (confirmed) {
                // Show loading state
                const removeBtn = event.target.closest('.remove-btn');
                if (removeBtn) {
                    removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
                    removeBtn.disabled = true;
                }

                // Redirect to remove URL
                window.location.href = `cart.php?remove=${cartId}`;
            }
        }

        function clearCart() {
            const confirmed = confirm('Are you sure you want to remove all items from your cart? This action cannot be undone.');
            
            if (confirmed) {
                // Show loading state
                const clearBtn = event.target;
                clearBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing Cart...';
                clearBtn.disabled = true;

                // Create form to clear all items
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="clear_cart" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Add loading states for better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading animation to quantity buttons
            const quantityBtns = document.querySelectorAll('.quantity-btn');
            quantityBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!this.disabled) {
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                        this.disabled = true;
                    }
                });
            });

            // Validate quantity inputs
            const quantityInputs = document.querySelectorAll('.quantity-input');
            quantityInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const min = parseInt(this.min) || 1;
                    const max = parseInt(this.max) || 999;
                    let value = parseInt(this.value);

                    if (isNaN(value) || value < min) {
                        this.value = min;
                    } else if (value > max) {
                        this.value = max;
                    }
                });
            });
        });

        // Add smooth animations
        function animateRemoval(element) {
            element.style.transition = 'all 0.3s ease-out';
            element.style.transform = 'translateX(-100%)';
            element.style.opacity = '0';
            
            setTimeout(() => {
                element.remove();
            }, 300);
        }
    </script>

</body>

</html>