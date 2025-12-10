<?php
require_once 'config.php';

// Get all products
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM products WHERE visible = 1";

$params = [];

if ($category_filter) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
}

if ($search) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
    
$sql .= " ORDER BY name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories
$stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get cart items for current user (if logged in)
$cart_items = [];
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT product_id, quantity, id as cart_id FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_data = $stmt->fetchAll();
    
    // Create associative array for easy lookup
    foreach ($cart_data as $item) {
        $cart_items[$item['product_id']] = [
            'quantity' => $item['quantity'],
            'cart_id' => $item['cart_id']
        ];
    }
}

// Get user orders
$stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.order_date DESC");
$user_id = $_SESSION['user_id'] ?? null; // $stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Get cart count
$stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$user_id = $_SESSION['user_id'] ?? null;  // $stmt->execute([$_SESSION['user_id']]);
$cart_count = $stmt->fetchColumn() ?: 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - BrewMaster Coffee</title>
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

    .page-subtitle {
        font-size: 1.2rem;
        color: #666;
    }

    .filters {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 3rem;
    }

    .filter-row {
        display: flex;
        gap: 2rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .search-box {
        flex: 1;
        min-width: 250px;
    }

    .search-box input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
    }

    .search-box input:focus {
        outline: none;
        border-color: #D2691E;
    }

    .category-filters {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .category-btn {
        padding: 8px 16px;
        background: #f8f9fa;
        border: 2px solid #ddd;
        border-radius: 20px;
        color: #666;
        text-decoration: none;
        transition: all 0.3s;
        font-weight: 500;
    }

    .category-btn:hover,
    .category-btn.active {
        background: #D2691E;
        border-color: #D2691E;
        color: white;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
    }

    .product-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: transform 0.3s;
    }

    .product-card:hover {
        transform: translateY(-10px);
    }

    .product-image {
        height: 250px;
        background: linear-gradient(45deg, #D2691E, #CD853F);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 4rem;
        position: relative;
    }

    .product-category {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255, 255, 255, 0.9);
        color: #6B4423;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .product-info {
        padding: 2rem;
    }

    .product-name {
        font-size: 1.5rem;
        color: #6B4423;
        margin-bottom: 0.5rem;
        font-weight: bold;
    }

    .product-description {
        color: #666;
        margin-bottom: 1rem;
        line-height: 1.6;
    }

    .product-price {
        font-size: 2rem;
        font-weight: bold;
        color: #D2691E;
        margin-bottom: 1rem;
    }

    .product-stock {
        font-size: 0.9rem;
        color: #28a745;
        margin-bottom: 1rem;
    }

    .product-stock.low {
        color: #ffc107;
    }

    .product-stock.out {
        color: #dc3545;
    }

    .btn {
        display: inline-block;
        padding: 12px 24px;
        background: linear-gradient(45deg, #D2691E, #FF8C00);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: bold;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        width: 100%;
        text-align: center;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(210, 105, 30, 0.3);
    }

    .btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .no-products {
        text-align: center;
        padding: 4rem 2rem;
        color: #666;
    }

    .no-products i {
        font-size: 5rem;
        color: #D2691E;
        margin-bottom: 2rem;
    }

    @media (max-width: 768px) {
        .nav-links {
            display: none;
        }

        .filter-row {
            flex-direction: column;
            align-items: stretch;
        }

        .category-filters {
            justify-content: center;
        }

        .products-grid {
            grid-template-columns: 1fr;
        }
    }

    .footer {
        background: #333;
        color: white;
        padding: 3rem 0 1rem;
    }

    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .footer-section h3 {
        margin-bottom: 1rem;
        color: #D2691E;
    }

    .footer-section p,
    .footer-section a {
        color: #ccc;
        text-decoration: none;
        margin-bottom: 0.5rem;
        display: block;
    }

    .footer-section a:hover {
        color: #D2691E;
    }

    .footer-bottom {
        text-align: center;
        padding-top: 2rem;
        border-top: 1px solid #555;
        color: #ccc;
    }

    .booking-form {
        background: #f8f9fa;
        padding: 2rem;
        border-radius: 10px;
        margin-top: 2rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #6B4423;
        font-weight: bold;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 2px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #D2691E;
    }

    @media (max-width: 768px) {
        .nav-links {
            display: none;
        }

        .hero-content h1 {
            font-size: 2.5rem;
        }

        .featured-products {
            grid-template-columns: 1fr;
        }
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 0;
        border-radius: 15px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #6B4423, #8B4513);
        color: white;
        padding: 1.5rem 2rem;
        border-radius: 15px 15px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 1.5rem;
    }

    .close {
        color: white;
        font-size: 2rem;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.3s;
        line-height: 1;
    }

    .close:hover {
        color: #D2691E;
    }

    .modal-body {
        padding: 2rem;
    }

    .product-preview {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        align-items: center;
    }

    .product-image-small {
        width: 80px;
        height: 80px;
        border-radius: 10px;
        overflow: hidden;
        flex-shrink: 0;
    }

    .product-image-small img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-details h3 {
        color: #6B4423;
        margin: 0 0 0.5rem 0;
        font-size: 1.2rem;
    }

    .modal-price {
        color: #D2691E;
        font-size: 1.3rem;
        font-weight: bold;
        margin: 0 0 0.5rem 0;
    }

    .modal-stock {
        color: #666;
        margin: 0;
        font-size: 0.9rem;
    }

    .quantity-controls {
        margin-bottom: 1.5rem;
    }

    .quantity-controls label {
        display: block;
        margin-bottom: 0.5rem;
        color: #6B4423;
        font-weight: bold;
    }

    .quantity-input {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        justify-content: center;
    }

    .qty-btn {
        background: #D2691E;
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        font-size: 1.2rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .qty-btn:hover {
        background: #B8541A;
        transform: scale(1.1);
    }

    .qty-btn:active {
        transform: scale(0.95);
    }

    #quantity {
        width: 80px;
        height: 40px;
        text-align: center;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: bold;
    }

    #quantity:focus {
        outline: none;
        border-color: #D2691E;
    }

    .total-price {
        text-align: center;
        font-size: 1.3rem;
        color: #6B4423;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 1rem;
    }

    .cart-status-message {
        background: #e7f3ff;
        color: #0066cc;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        border-left: 4px solid #0066cc;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
    }

    .cart-status-message i {
        color: #0066cc;
    }

    .modal-footer {
        padding: 1.5rem 2rem;
        border-top: 1px solid #eee;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .modal-footer .btn {
        min-width: 120px;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s;
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .btn-primary {
        background: linear-gradient(45deg, #D2691E, #FF8C00);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(210, 105, 30, 0.3);
    }

    /* Cart Controls Styles */
    .cart-controls {
        width: 100%;
    }

    .in-cart-info {
        background: #d4edda;
        color: #155724;
        padding: 0.75rem;
        border-radius: 8px;
        text-align: center;
        font-weight: bold;
        margin-bottom: 0.75rem;
        border: 2px solid #c3e6cb;
    }

    .in-cart-info i {
        color: #28a745;
        margin-right: 0.5rem;
    }

    .quick-quantity-controls {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .quick-qty-btn {
        width: 25px;
        height: 25px;
        border: 1px solid #28a745;
        background: white;
        color: #28a745;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        transition: all 0.2s;
    }

    .quick-qty-btn:hover {
        background: #28a745;
        color: white;
        transform: scale(1.1);
    }

    .current-qty {
        font-weight: bold;
        color: #155724;
        min-width: 20px;
        text-align: center;
    }

    .cart-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .cart-buttons .btn {
        flex: 1;
        padding: 10px 16px;
        font-size: 0.9rem;
    }

    .btn-secondary {
        background: linear-gradient(45deg, #6c757d, #5a6268);
        color: white;
    }

    .btn-secondary:hover {
        background: linear-gradient(45deg, #5a6268, #495057);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
    }

    .btn-danger {
        background: linear-gradient(45deg, #dc3545, #c82333);
        color: white;
        position: relative;
        overflow: hidden;
    }

    .btn-danger:hover {
        background: linear-gradient(45deg, #c82333, #bd2130);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
    }

    .btn-danger:active {
        transform: translateY(0);
    }

    .remove-from-cart-btn {
        transition: all 0.3s ease;
    }

    .remove-from-cart-btn:hover {
        animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-2px); }
        75% { transform: translateX(2px); }
    }

    /* Special styling for products in cart */
    .product-card:has(.cart-controls) {
        border: 2px solid #28a745;
        box-shadow: 0 10px 30px rgba(40, 167, 69, 0.2);
    }

    .product-card:has(.cart-controls):hover {
        box-shadow: 0 15px 35px rgba(40, 167, 69, 0.3);
    }

    /* Pulse animation for in-cart indicator */
    .in-cart-info {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
    }

    /* Mobile responsiveness for modal */
    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            margin: 10% auto;
        }

        .product-preview {
            flex-direction: column;
            text-align: center;
        }

        .modal-footer {
            flex-direction: column;
        }

        .modal-footer .btn {
            width: 100%;
        }

        .cart-buttons {
            flex-direction: column;
        }

        .cart-buttons .btn {
            width: 100%;
        }
    }

    /* Notification Styles */
    .notification {
        position: fixed;
        top: 100px;
        right: 20px;
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        z-index: 3000;
        transform: translateX(400px);
        opacity: 0;
        transition: all 0.3s ease-out;
        max-width: 350px;
        border-left: 4px solid;
    }

    .notification.show {
        transform: translateX(0);
        opacity: 1;
    }

    .notification-success {
        border-left-color: #28a745;
        color: #155724;
    }

    .notification-success i {
        color: #28a745;
    }

    .notification-error {
        border-left-color: #dc3545;
        color: #721c24;
    }

    .notification-error i {
        color: #dc3545;
    }

    .notification-close {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: #666;
        margin-left: auto;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .notification-close:hover {
        color: #333;
    }

    @media (max-width: 768px) {
        .notification {
            right: 10px;
            left: 10px;
            max-width: none;
            transform: translateY(-100px);
        }

        .notification.show {
            transform: translateY(0);
        }
    }
    </style>
</head>

<body>
    <?php include_once("navigation.php"); ?>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Our Premium Coffee Collection</h1>
            <p class="page-subtitle">Discover the perfect blend for every taste and occasion</p>
        </div>

        <div class="filters">
            <form method="GET" class="filter-row">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search for coffee..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn" style="width: auto; padding: 12px 20px;">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>

            <div class="category-filters" style="margin-top: 1rem;">
                <a href="products.php" class="category-btn <?php echo !$category_filter ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $category): ?>
                <a href="products.php?category=<?php echo urlencode($category); ?>"
                    class="category-btn <?php echo $category_filter === $category ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($category); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (count($products) > 0): ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                    <img src="images/<?php echo htmlspecialchars(basename($product['image_path'])); ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                        style="width: 100%; height: 100%; object-fit: cover;">
                </div>


                <div class="product-info">
                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                    <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>

                    <?php 
                        $stock_class = '';
                     $stock_text = '';

                         // Use isset to avoid undefined index error
                     $stock = isset($product['stock']) ? (int)$product['stock'] : 0;

                       if ($stock > 10) {
                         $stock_class = '';
                          $stock_text = 'In Stock (' . $stock . ' available)';
                        } elseif ($stock > 0) {
                     $stock_class = 'low';
                       $stock_text = 'Low Stock (' . $stock . ' left)';
                         } else {
                          $stock_class = 'out';
                         $stock_text = 'Out of Stock';
                          }
                    ?>


                    <div class="product-stock <?php echo $stock_class; ?>">
                        <i class="fas fa-box"></i> <?php echo $stock_text; ?>
                    </div>

                    <?php if (isLoggedIn()): ?>
                        <?php if ($product['stock'] > 0): ?>
                            <?php if (isset($cart_items[$product['id']])): ?>
                                <!-- Item is in cart - show cart controls -->
                                <div class="cart-controls">
                                    <div class="in-cart-info">
                                        <i class="fas fa-check-circle"></i> 
                                        <strong>In Cart (<?php echo $cart_items[$product['id']]['quantity']; ?>)</strong>
                                        <div class="quick-quantity-controls">
                                            <button class="quick-qty-btn" onclick="updateCartQuantity(<?php echo $cart_items[$product['id']]['cart_id']; ?>, <?php echo max(1, $cart_items[$product['id']]['quantity'] - 1); ?>)" title="Decrease quantity">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span class="current-qty"><?php echo $cart_items[$product['id']]['quantity']; ?></span>
                                            <button class="quick-qty-btn" onclick="updateCartQuantity(<?php echo $cart_items[$product['id']]['cart_id']; ?>, <?php echo $cart_items[$product['id']]['quantity'] + 1; ?>)" title="Increase quantity">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="cart-buttons">
                                        <button class="btn btn-secondary view-cart-btn" onclick="window.location.href='cart.php'">
                                            <i class="fas fa-shopping-cart"></i> View Cart
                                        </button>
                                        <button class="btn btn-danger remove-from-cart-btn" 
                                                data-cart-id="<?php echo $cart_items[$product['id']]['cart_id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                title="Remove <?php echo htmlspecialchars($product['name']); ?> from cart">
                                            <i class="fas fa-trash"></i> Remove from Cart
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Item not in cart - show add to cart button -->
                                <button class="btn add-to-cart-btn" 
                                        data-product-id="<?php echo $product['id']; ?>"
                                        data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                        data-product-price="<?php echo $product['price']; ?>"
                                        data-product-stock="<?php echo $product['stock']; ?>"
                                        data-product-image="<?php echo htmlspecialchars($product['image_path']); ?>">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn" disabled>Out of Stock</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="btn">Login to Purchase</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-products">
            <i class="fas fa-search"></i>
            <h3>No Products Found</h3>
            <p>Try adjusting your search or filter criteria</p>
            <a href="products.php" class="btn" style="margin-top: 1rem; width: auto; display: inline-block;">View All
                Products</a>
        </div>
        <?php endif; ?>
    </div>
    <?php include_once("footer.php"); ?>

    <!-- Add to Cart Modal -->
    <div id="cartModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add to Cart</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="product-preview">
                    <div class="product-image-small">
                        <img id="modalProductImage" src="" alt="">
                    </div>
                    <div class="product-details">
                        <h3 id="modalProductName"></h3>
                        <p class="modal-price">₹<span id="modalProductPrice"></span></p>
                        <p class="modal-stock">Stock: <span id="modalProductStock"></span> available</p>
                    </div>
                </div>
                
                <div class="quantity-controls">
                    <label for="quantity">Quantity:</label>
                    <div class="quantity-input">
                        <button type="button" class="qty-btn minus" id="decreaseQty">-</button>
                        <input type="number" id="quantity" value="1" min="1" max="1">
                        <button type="button" class="qty-btn plus" id="increaseQty">+</button>
                    </div>
                </div>
                
                <div class="total-price">
                    <strong>Total: ₹<span id="totalPrice">0.00</span></strong>
                </div>
                
                <div id="cartStatusMessage" class="cart-status-message" style="display: none;">
                    <i class="fas fa-info-circle"></i>
                    <span>This item is already in your cart. You can update the quantity or remove it.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                <button type="button" class="btn btn-danger" id="removeFromCartBtn" style="display: none;">
                    <i class="fas fa-trash"></i> Remove from Cart
                </button>
                <button type="button" class="btn btn-primary" id="addToCartBtn">Add to Cart</button>
            </div>
        </div>
    </div>

    <script>
        // Cart data from PHP
        const cartItems = <?php echo json_encode($cart_items); ?>;
        
        // Modal functionality
        const modal = document.getElementById('cartModal');
        const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
        const removeFromCartBtns = document.querySelectorAll('.remove-from-cart-btn');
        const closeBtn = document.querySelector('.close');
        const cancelBtn = document.getElementById('cancelBtn');
        const addToCartBtn = document.getElementById('addToCartBtn');
        const removeFromCartModalBtn = document.getElementById('removeFromCartBtn');
        const quantityInput = document.getElementById('quantity');
        const increaseBtn = document.getElementById('increaseQty');
        const decreaseBtn = document.getElementById('decreaseQty');

        let currentProduct = {};
        let currentCartItem = null;

        // Open modal when add to cart button is clicked
        addToCartBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                currentProduct = {
                    id: this.dataset.productId,
                    name: this.dataset.productName,
                    price: parseFloat(this.dataset.productPrice),
                    stock: parseInt(this.dataset.productStock),
                    image: this.dataset.productImage
                };
                
                // Check if item is already in cart
                currentCartItem = cartItems[currentProduct.id] || null;
                
                // Populate modal with product data
                document.getElementById('modalProductName').textContent = currentProduct.name;
                document.getElementById('modalProductPrice').textContent = currentProduct.price.toFixed(2);
                document.getElementById('modalProductStock').textContent = currentProduct.stock;
                document.getElementById('modalProductImage').src = 'images/' + currentProduct.image.split('/').pop();
                document.getElementById('modalProductImage').alt = currentProduct.name;
                
                // Set quantity based on cart status
                const cartStatusMessage = document.getElementById('cartStatusMessage');
                
                if (currentCartItem) {
                    quantityInput.value = currentCartItem.quantity;
                    quantityInput.max = currentProduct.stock;
                    
                    // Show both buttons for items in cart
                    addToCartBtn.style.display = 'inline-block';
                    removeFromCartModalBtn.style.display = 'inline-block';
                    cartStatusMessage.style.display = 'flex';
                    
                    // Update button text and modal title
                    addToCartBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Update Cart';
                    document.querySelector('.modal-header h2').textContent = 'Update Cart Item';
                } else {
                    quantityInput.value = 1;
                    quantityInput.max = currentProduct.stock;
                    
                    // Show add button, hide remove button and message
                    addToCartBtn.style.display = 'inline-block';
                    removeFromCartModalBtn.style.display = 'none';
                    cartStatusMessage.style.display = 'none';
                    
                    // Update button text and modal title
                    addToCartBtn.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';
                    document.querySelector('.modal-header h2').textContent = 'Add to Cart';
                }
                
                updateTotalPrice();
                modal.style.display = 'block';
            });
        });

        // Close modal functions
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        function closeModal() {
            modal.style.display = 'none';
        }

        // Quantity controls
        increaseBtn.addEventListener('click', function() {
            const currentQty = parseInt(quantityInput.value);
            if (currentQty < currentProduct.stock) {
                quantityInput.value = currentQty + 1;
                updateTotalPrice();
            }
        });

        decreaseBtn.addEventListener('click', function() {
            const currentQty = parseInt(quantityInput.value);
            if (currentQty > 1) {
                quantityInput.value = currentQty - 1;
                updateTotalPrice();
            }
        });

        quantityInput.addEventListener('input', function() {
            let qty = parseInt(this.value);
            if (isNaN(qty) || qty < 1) {
                qty = 1;
            } else if (qty > currentProduct.stock) {
                qty = currentProduct.stock;
            }
            this.value = qty;
            updateTotalPrice();
        });

        function updateTotalPrice() {
            const quantity = parseInt(quantityInput.value);
            const total = (currentProduct.price * quantity).toFixed(2);
            document.getElementById('totalPrice').textContent = total;
        }

        // Add to cart functionality
        addToCartBtn.addEventListener('click', function() {
            const quantity = parseInt(quantityInput.value);
            
            if (currentCartItem) {
                // Update existing cart item
                const formData = new FormData();
                formData.append('cart_id', currentCartItem.cart_id);
                formData.append('quantity', quantity);
                formData.append('update_quantity', '1');
                
                // Send request to cart.php for update
                fetch('cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        showNotification('Cart updated successfully!', 'success');
                        closeModal();
                        
                        // Reload page to show updated quantity
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        throw new Error('Failed to update cart');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to update cart', 'error');
                });
            } else {
                // Add new item to cart
                const formData = new FormData();
                formData.append('product_id', currentProduct.id);
                formData.append('quantity', quantity);
                
                // Send AJAX request
                fetch('add_to_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showNotification('Product added to cart successfully!', 'success');
                        closeModal();
                        
                        // Update cart count if exists
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount && data.cart_count) {
                            cartCount.textContent = data.cart_count;
                        }
                        
                        // Reload page to show updated cart status
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification('Error: ' + (data.message || 'Failed to add product to cart'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while adding the product to cart', 'error');
                });
            }
        });

        // Remove from cart modal functionality
        removeFromCartModalBtn.addEventListener('click', function() {
            if (!currentCartItem) return;
            
            const confirmed = confirm(`Remove "${currentProduct.name}" from your cart?`);
            if (!confirmed) return;
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
            this.disabled = true;
            
            // Send AJAX request
            fetch('remove_from_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_id=${currentCartItem.cart_id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Product removed from cart successfully!', 'success');
                    closeModal();
                    
                    // Update cart count if exists
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount && data.cart_count) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Reload page to update cart status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification('Error: ' + (data.message || 'Failed to remove product from cart'), 'error');
                    
                    // Reset button state
                    this.innerHTML = '<i class="fas fa-trash"></i> Remove from Cart';
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while removing the product from cart', 'error');
                
                // Reset button state
                this.innerHTML = '<i class="fas fa-trash"></i> Remove from Cart';
                this.disabled = false;
            });
        });

        // Remove from cart functionality
        removeFromCartBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const cartId = this.dataset.cartId;
                const productName = this.dataset.productName;
                
                if (confirm(`Remove "${productName}" from your cart?`)) {
                    // Show loading state
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
                    this.disabled = true;
                    
                    // Send AJAX request
                    fetch('remove_from_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `cart_id=${cartId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Product removed from cart successfully!', 'success');
                            
                            // Reload page to update cart status
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showNotification('Error: ' + (data.message || 'Failed to remove product from cart'), 'error');
                            
                            // Reset button state
                            this.innerHTML = '<i class="fas fa-trash"></i> Remove';
                            this.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred while removing the product from cart', 'error');
                        
                        // Reset button state
                        this.innerHTML = '<i class="fas fa-trash"></i> Remove';
                        this.disabled = false;
                    });
                }
            });
        });

        // Quick quantity update function
        function updateCartQuantity(cartId, newQuantity) {
            if (newQuantity < 1) return;
            
            // Show loading state
            const qtyControls = document.querySelector(`[onclick*="${cartId}"]`).closest('.quick-quantity-controls');
            if (qtyControls) {
                qtyControls.style.opacity = '0.5';
                qtyControls.style.pointerEvents = 'none';
            }

            // Create form data
            const formData = new FormData();
            formData.append('cart_id', cartId);
            formData.append('quantity', newQuantity);
            formData.append('update_quantity', '1');

            // Send request to cart.php
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    showNotification('Quantity updated successfully!', 'success');
                    // Reload page to show updated quantity
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    throw new Error('Failed to update quantity');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to update quantity', 'error');
                
                // Reset loading state
                if (qtyControls) {
                    qtyControls.style.opacity = '1';
                    qtyControls.style.pointerEvents = 'auto';
                }
            });
        }

        // Notification function
        function showNotification(message, type = 'success') {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(n => n.remove());

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            `;

            // Add to page
            document.body.appendChild(notification);

            // Show notification
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            // Auto hide after 3 seconds
            setTimeout(() => {
                hideNotification(notification);
            }, 3000);

            // Close button functionality
            notification.querySelector('.notification-close').addEventListener('click', () => {
                hideNotification(notification);
            });
        }

        function hideNotification(notification) {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    </script>

</body>

</html>