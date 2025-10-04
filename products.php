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

// Get user orders
$stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.order_date DESC");
$user_id = $_SESSION['user_id'] ?? null; // $stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Get cart count
$stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$user_id = $_SESSION['user_id'] ?? null;  // $stmt->execute([$_SESSION['user_id']]);
$cart_count = $stmt->fetchColumn() ?: 0;

// ALTER TABLE products 
// ADD COLUMN visible TINYINT(1) NOT NULL DEFAULT '1';


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
                    <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>

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
                    <a href="add_to_cart.php?id=<?php echo $product['id']; ?>" class="btn">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </a>
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

</body>

</html>