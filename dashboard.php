<?php

include_once 'config.php';

// Ensure user is logged in and active
requireLogin();

// Get user orders
$stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.order_date DESC");
//$user_id = $_SESSION['user_id'] ?? null; 
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Get cart count
$stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
//$user_id = $_SESSION['user_id'] ?? null;
 $stmt->execute([$_SESSION['user_id']]);
$cart_count = $stmt->fetchColumn() ?: 0;

// Handle booking deletion
if (isset($_GET['delete_booking'])) {
    $booking_id = intval($_GET['delete_booking']);
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$booking_id, $_SESSION['user_id']])) {
        $_SESSION['success_message'] = "Booking deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete booking.";
    }
    header('Location: dashboard.php');
    exit;
}

// Get user bookings
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

// Count bookings by status
$booking_stats = [
    'pending' => 0,
    'confirmed' => 0,
    'cancelled' => 0
];
foreach ($bookings as $booking) {
    if (isset($booking_stats[$booking['status']])) {
        $booking_stats[$booking['status']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BrewMaster Coffee</title>
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

    .welcome-section {
        background: linear-gradient(135deg, #D2691E, #FF8C00);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        text-align: center;
    }

    .welcome-section h1 {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        text-align: center;
        transition: transform 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-card i {
        font-size: 3rem;
        color: #D2691E;
        margin-bottom: 1rem;
    }

    .stat-card h3 {
        font-size: 2rem;
        color: #6B4423;
        margin-bottom: 0.5rem;
    }

    .stat-card p {
        color: #666;
    }

    .section-title {
        font-size: 2rem;
        color: #6B4423;
        margin-bottom: 2rem;
        text-align: center;
    }

    .orders-table {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .table th {
        background: #6B4423;
        color: white;
        font-weight: bold;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
    }

    .status {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: bold;
    }

    .status.pending {
        background: #fff3cd;
        color: #856404;
    }

    .status.completed {
        background: #d4edda;
        color: #155724;
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
        margin: 0.5rem;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(210, 105, 30, 0.3);
    }

    .no-orders {
        text-align: center;
        padding: 3rem;
        color: #666;
    }

    .no-orders i {
        font-size: 4rem;
        color: #D2691E;
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .nav-links {
            display: none;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .table-responsive {
            overflow-x: auto;
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

    /* Order Cards Styles */
    .orders-section {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .order-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: transform 0.3s;
    }

    .order-card:hover {
        transform: translateY(-5px);
    }

    .order-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        padding: 1.5rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eee;
    }

    .order-info h3 {
        color: #6B4423;
        margin-bottom: 0.5rem;
        font-size: 1.3rem;
    }

    .order-date {
        color: #666;
        margin: 0;
        font-size: 0.9rem;
    }

    .order-date i {
        margin-right: 0.5rem;
        color: #D2691E;
    }

    .order-status-section {
        text-align: right;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-confirmed {
        background: #d4edda;
        color: #155724;
    }

    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }

    .order-total {
        font-size: 1.2rem;
        color: #D2691E;
    }

    .order-items {
        padding: 2rem;
    }

    .order-items h4 {
        color: #6B4423;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .items-grid {
        display: grid;
        gap: 1rem;
    }

    .order-item {
        display: flex;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 10px;
        align-items: center;
    }

    .item-image {
        width: 80px;
        height: 80px;
        border-radius: 10px;
        overflow: hidden;
        flex-shrink: 0;
    }

    .item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .item-details {
        flex: 1;
    }

    .item-details h5 {
        color: #6B4423;
        margin: 0 0 0.25rem 0;
        font-size: 1rem;
    }

    .item-category {
        color: #666;
        font-size: 0.85rem;
        margin: 0 0 0.5rem 0;
    }

    .item-pricing {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .quantity {
        background: #D2691E;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .price {
        color: #666;
        font-size: 0.9rem;
    }

    .subtotal {
        color: #D2691E;
        font-weight: bold;
        margin-left: auto;
    }

    .order-summary {
        padding: 1rem 2rem;
        background: #f8f9fa;
        border-top: 1px solid #eee;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
    }

    .summary-row.total {
        border-top: 2px solid #D2691E;
        margin-top: 0.5rem;
        padding-top: 1rem;
        font-size: 1.1rem;
        color: #6B4423;
    }

    .order-actions {
        padding: 1.5rem 2rem;
        background: white;
    }

    .status-message {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        border-radius: 10px;
        margin: 0;
        font-weight: 500;
    }

    .status-message {
        background: #e7f3ff;
        color: #0066cc;
        border-left: 4px solid #0066cc;
    }

    .status-message.success {
        background: #d4edda;
        color: #155724;
        border-left-color: #28a745;
    }

    .status-message.error {
        background: #f8d7da;
        color: #721c24;
        border-left-color: #dc3545;
    }

    /* Alert Styles */
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 500;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }

    .alert-success i {
        color: #28a745;
        font-size: 1.2rem;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }

    .alert-error i {
        color: #dc3545;
        font-size: 1.2rem;
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

        .order-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .order-status-section {
            text-align: left;
            width: 100%;
        }

        .order-item {
            flex-direction: column;
            text-align: center;
        }

        .item-pricing {
            justify-content: center;
        }

        .subtotal {
            margin-left: 0;
        }
    }
    </style>
</head>

<body>
    <?php include_once("navigation.php"); ?>

    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="welcome-section">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p>Manage your coffee orders and explore our premium collection</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-shopping-bag"></i>
                <h3><?php echo count($orders); ?></h3>
                <p>Total Orders</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-shopping-cart"></i>
                <h3><?php echo $cart_count; ?></h3>
                <p>Items in Cart</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <h3><?php echo count($bookings); ?></h3>
                <p>Table Bookings</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-star"></i>
                <h3><?php echo isAdmin() ? 'Admin' : 'Member'; ?></h3>
                <p>Account Type</p>
            </div>
        </div>

        <!-- Bookings Section -->
        <h2 class="section-title">Your Table Bookings</h2>

        <div class="orders-section">
            <?php if (count($bookings) > 0): ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>Booking #<?php echo $booking['id']; ?></h3>
                                <p class="order-date">
                                    <i class="fas fa-calendar"></i>
                                    Booking Date: <?php echo date('F d, Y', strtotime($booking['booking_date'])); ?>
                                </p>
                                <p class="order-date">
                                    <i class="fas fa-clock"></i>
                                    Time: <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                </p>
                            </div>
                            <div class="order-status-section">
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <i class="fas <?php 
                                        echo $booking['status'] == 'pending' ? 'fa-clock' : 
                                            ($booking['status'] == 'confirmed' ? 'fa-check-circle' : 'fa-times-circle'); 
                                    ?>"></i>
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-items">
                            <h4>Booking Details:</h4>
                            <div class="items-grid">
                                <div class="order-item">
                                    <div class="item-details" style="width: 100%;">
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                            <div>
                                                <h5><i class="fas fa-user"></i> Guest Name</h5>
                                                <p style="color: #666; margin-top: 0.5rem;"><?php echo htmlspecialchars($booking['name']); ?></p>
                                            </div>
                                            <div>
                                                <h5><i class="fas fa-envelope"></i> Email</h5>
                                                <p style="color: #666; margin-top: 0.5rem;"><?php echo htmlspecialchars($booking['email']); ?></p>
                                            </div>
                                            <div>
                                                <h5><i class="fas fa-phone"></i> Phone</h5>
                                                <p style="color: #666; margin-top: 0.5rem;"><?php echo htmlspecialchars($booking['phone']); ?></p>
                                            </div>
                                            <div>
                                                <h5><i class="fas fa-users"></i> Number of Guests</h5>
                                                <p style="color: #666; margin-top: 0.5rem;"><?php echo isset($booking['party_size']) ? $booking['party_size'] : (isset($booking['guests']) ? $booking['guests'] : 'N/A'); ?> people</p>
                                            </div>
                                        </div>
                                        <?php if (!empty($booking['message'])): ?>
                                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                                                <h5><i class="fas fa-comment"></i> Special Requests</h5>
                                                <p style="color: #666; margin-top: 0.5rem;"><?php echo htmlspecialchars($booking['message']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-summary">
                            <div class="summary-row">
                                <span>Submitted On:</span>
                                <span><?php echo date('F d, Y \a\t g:i A', strtotime($booking['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <?php if ($booking['status'] == 'pending'): ?>
                                <p class="status-message">
                                    <i class="fas fa-info-circle"></i>
                                    Your booking request is being reviewed. We will contact you shortly to confirm.
                                </p>
                            <?php elseif ($booking['status'] == 'confirmed'): ?>
                                <p class="status-message success">
                                    <i class="fas fa-check-circle"></i>
                                    Your table booking has been confirmed! We look forward to serving you.
                                </p>
                            <?php elseif ($booking['status'] == 'cancelled'): ?>
                                <p class="status-message error">
                                    <i class="fas fa-times-circle"></i>
                                    This booking has been cancelled. Please contact us if you have any questions.
                                </p>
                            <?php endif; ?>
                            
                            <div style="margin-top: 1rem; text-align: center;">
                                <button onclick="confirmDeleteBooking(<?php echo $booking['id']; ?>)" class="btn" style="background: linear-gradient(45deg, #dc3545, #c82333);">
                                    <i class="fas fa-trash"></i> Delete Booking
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Bookings Yet</h3>
                    <p>Book a table to enjoy our premium coffee experience!</p>
                    <a href="booking.php" class="btn">Book a Table</a>
                </div>
            <?php endif; ?>
        </div>

        <h2 class="section-title" style="margin-top: 4rem;">Your Recent Orders</h2>

        <div class="orders-section">
            <?php if (count($orders) > 0): ?>
                <?php foreach ($orders as $order): ?>
                    <?php
                    // Get order items with product details
                    $stmt = $pdo->prepare("
                        SELECT oi.*, p.name, p.image_path, p.category 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ?
                    ");
                    $stmt->execute([$order['id']]);
                    $order_items = $stmt->fetchAll();
                    ?>
                    
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>Order #<?php echo $order['id']; ?></h3>
                                <p class="order-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('F d, Y \a\t g:i A', strtotime($order['order_date'])); ?>
                                </p>
                            </div>
                            <div class="order-status-section">
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <i class="fas <?php 
                                        echo $order['status'] == 'pending' ? 'fa-clock' : 
                                            ($order['status'] == 'confirmed' ? 'fa-check-circle' : 
                                            ($order['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-info-circle')); 
                                    ?>"></i>
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <div class="order-total">
                                    <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-items">
                            <h4>Items Ordered:</h4>
                            <div class="items-grid">
                                <?php foreach ($order_items as $item): ?>
                                    <div class="order-item">
                                        <div class="item-image">
                                            <img src="images/<?php echo htmlspecialchars(basename($item['image_path'])); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 onerror="this.src='https://via.placeholder.com/80x80/D2691E/white?text=Coffee'">
                                        </div>
                                        <div class="item-details">
                                            <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <p class="item-category"><?php echo htmlspecialchars($item['category']); ?></p>
                                            <div class="item-pricing">
                                                <span class="quantity">Qty: <?php echo $item['quantity']; ?></span>
                                                <span class="price">₹<?php echo number_format($item['price'], 2); ?> each</span>
                                                <span class="subtotal">
                                                    <strong>₹<?php echo number_format($item['quantity'] * $item['price'], 2); ?></strong>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="order-summary">
                            <div class="summary-row">
                                <span>Total Items:</span>
                                <span><?php echo array_sum(array_column($order_items, 'quantity')); ?></span>
                            </div>
                            <div class="summary-row total">
                                <span>Order Total:</span>
                                <span><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></span>
                            </div>
                        </div>
                        
                        <?php if ($order['status'] == 'pending'): ?>
                            <div class="order-actions">
                                <p class="status-message">
                                    <i class="fas fa-info-circle"></i>
                                    Your order is being processed. You will receive a confirmation soon.
                                </p>
                            </div>
                        <?php elseif ($order['status'] == 'confirmed'): ?>
                            <div class="order-actions">
                                <p class="status-message success">
                                    <i class="fas fa-check-circle"></i>
                                    Your order has been confirmed and is being prepared!
                                </p>
                            </div>
                        <?php elseif ($order['status'] == 'cancelled'): ?>
                            <div class="order-actions">
                                <p class="status-message error">
                                    <i class="fas fa-times-circle"></i>
                                    This order has been cancelled. Please contact us if you have any questions.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>No Orders Yet</h3>
                    <p>Start exploring our amazing coffee collection!</p>
                    <a href="products.php" class="btn">Browse Products</a>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 3rem;">
            <a href="products.php" class="btn">Continue Shopping</a>
            <a href="cart.php" class="btn">View Cart</a>
            <a href="booking.php" class="btn">Book a Table</a>
        </div>
    </div>
    <?php include_once("footer.php"); ?>

    <script>
        function confirmDeleteBooking(bookingId) {
            if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
                window.location.href = 'dashboard.php?delete_booking=' + bookingId;
            }
        }
    </script>

</body>

</html>