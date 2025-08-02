<?php
 
include_once 'config.php';

// Handle product deletion
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$product_id])) {
        $success = "Product deleted successfully!";
    } else {
        $error = "Failed to delete product.";
    }
}

// Handle new product addition
if ($_POST && isset($_POST['add_product'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $category = sanitize($_POST['category']);
    $stock = intval($_POST['stock']);
    $image = sanitize($_POST['image']);
    
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $description, $price, $category, $stock, $image])) {
        $success = "Product added successfully!";
    } else {
        $error = "Failed to add product.";
    }
}

// Get all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
$total_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$total_orders = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(total_amount) FROM orders");
$total_revenue = $stmt->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - BrewMaster Coffee</title>
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

        .admin-header {
            background: linear-gradient(135deg, #D2691E, #FF8C00);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 3rem;
            text-align: center;
        }

        .admin-header h1 {
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
            margin-bottom: 1rem;
        }

        .stat-card.products i { color: #D2691E; }
        .stat-card.users i { color: #28a745; }
        .stat-card.orders i { color: #007bff; }
        .stat-card.revenue i { color: #6f42c1; }

        .stat-card h3 {
            font-size: 2rem;
            color: #6B4423;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #666;
        }

        .admin-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
            overflow: hidden;
        }

        .section-header {
            background: #6B4423;
            color: white;
            padding: 1.5rem;
            font-size: 1.3rem;
            font-weight: bold;
        }

        .section-content {
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #D2691E;
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
            margin: 0.5rem 0.5rem 0.5rem 0;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(210, 105, 30, 0.3);
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }

        .btn-danger:hover {
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .products-table {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #6B4423;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .product-image-small {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #D2691E, #CD853F);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #dc3545;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .table {
                font-size: 0.8rem;
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
                <li><a href="dashboard.php">Admin</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="admin-header">
            <h1><i class="fas fa-tools"></i> Admin Panel</h1>
            <p>Manage your coffee shop products and monitor performance</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card products">
                <i class="fas fa-coffee"></i>
                <h3><?php echo $total_products; ?></h3>
                <p>Total Products</p>
            </div>
            <div class="stat-card users">
                <i class="fas fa-users"></i>
                <h3><?php echo $total_users; ?></h3>
                <p>Registered Users</p>
            </div>
            <div class="stat-card orders">
                <i class="fas fa-shopping-bag"></i>
                <h3><?php echo $total_orders; ?></h3>
                <p>Total Orders</p>
            </div>
            <div class="stat-card revenue">
                <i class="fas fa-dollar-sign"></i>
                <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>

        <!-- Add New Product Section -->
        <div class="admin-section">
            <div class="section-header">
                <i class="fas fa-plus"></i> Add New Product
            </div>
            <div class="section-content">
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Product Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="price">Price ($)</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Hot Coffee">Hot Coffee</option>
                                <option value="Cold Coffee">Cold Coffee</option>
                                <option value="Tea">Tea</option>
                                <option value="Beans">Beans</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="stock">Stock Quantity</label>
                            <input type="number" id="stock" name="stock" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="image">Image Filename</label>
                            <input type="text" id="image" name="image" placeholder="e.g., latte.jpg">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" required></textarea>
                    </div>
                    <button type="submit" name="add_product" class="btn">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </form>
            </div>
        </div>

        <!-- Products Management Section -->
        <div class="admin-section">
            <div class="section-header">
                <i class="fas fa-list"></i> Manage Products
            </div>
            <div class="section-content">
                <div class="products-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-image-small">
                                        <i class="fas fa-coffee"></i>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . '...'; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <?php if ($product['stock'] > 10): ?>
                                        <span style="color: #28a745;"><?php echo $product['stock']; ?></span>
                                    <?php elseif ($product['stock'] > 0): ?>
                                        <span style="color: #ffc107;"><?php echo $product['stock']; ?></span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <a href="admin.php?delete=<?php echo $product['id']; ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this product?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>