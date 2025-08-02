<?php

require_once 'config.php';

// Get user orders
$stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.order_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Get cart count
$stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cart_count = $stmt->fetchColumn() ?: 0;
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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

        .footer-section p, .footer-section a {
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

    </style>
</head>
<body>
    <?php include_once("navigation.php"); ?>

    <div class="container">
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
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
                <i class="fas fa-star"></i>
                <h3><?php echo isAdmin() ? 'Admin' : 'Member'; ?></h3>
                <p>Account Type</p>
            </div>
        </div>

        <h2 class="section-title">Your Recent Orders</h2>

        <div class="orders-table">
            <?php if (count($orders) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td><?php echo $order['item_count']; ?> items</td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status <?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
        </div>
    </div>
    <?php include_once("footer.php"); ?>

</body>
</html>