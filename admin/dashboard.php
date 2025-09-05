<?php
// Database configuration
$host = 'localhost';
$dbname = 'coffee_shop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create table if not exists
$createTable = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(100),
    image_path VARCHAR(255),
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$pdo->exec($createTable);



// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image_path, stock) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['price'],
                        $_POST['category'],
                        $_POST['image_path'],
                        $_POST['stock']
                    ]);
                    $message = "Product added successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error adding product: " . $e->getMessage();
                    $messageType = "error";
                }
                break;

            case 'edit':
                try {
                    $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, category=?, image_path=?, stock=? WHERE id=?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $_POST['price'],
                        $_POST['category'],
                        $_POST['image_path'],
                        $_POST['stock'],
                        $_POST['id']
                    ]);
                    $message = "Product updated successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error updating product: " . $e->getMessage();
                    $messageType = "error";
                }
                break;

            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $message = "Product deleted successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error deleting product: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
            //is me changes kiye hai     
            case 'toggle':
                try {
                    $stmt = $pdo->prepare("UPDATE products 
                               SET visible = CASE WHEN visible = 1 THEN 0 ELSE 1 END 
                               WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $message = "Product visibility updated!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error updating visibility: " . $e->getMessage();
                    $messageType = "error";
                }
                break;

        }
    }
}

// Get all products
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get product for editing
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coffee Shop - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(45deg, #6F4E37, #8B4513);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            padding: 30px;
        }

        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border: 2px solid #e9ecef;
        }

        .form-section h2 {
            color: #6F4E37;
            margin-bottom: 20px;
            font-size: 1.5em;
            border-bottom: 2px solid #D2691E;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #495057;
            font-weight: 500;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #D2691E;
            box-shadow: 0 0 0 3px rgba(210, 105, 30, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(45deg, #D2691E, #FF8C00);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #B8860B, #D2691E);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(210, 105, 30, 0.3);
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #e74c3c);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(45deg, #c82333, #dc3545);
            transform: translateY(-2px);
        }

        .btn-edit {
            background: linear-gradient(45deg, #17a2b8, #20c997);
            color: white;
            padding: 8px 15px;
            font-size: 14px;
        }

        .btn-edit:hover {
            background: linear-gradient(45deg, #138496, #17a2b8);
        }

        .products-section {
            background: white;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }

        .products-section h2 {
            color: #6F4E37;
            margin-bottom: 20px;
            font-size: 1.5em;
            border-bottom: 2px solid #D2691E;
            padding: 20px 25px 15px;
            margin: 0;
        }

        .products-grid {
            display: grid;
            gap: 20px;
            padding: 25px;
            max-height: 600px;
            overflow-y: auto;
        }

        .product-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .product-card:hover {
            border-color: #D2691E;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(210, 105, 30, 0.2);
        }

        .product-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #6F4E37;
            margin-bottom: 8px;
        }

        .product-price {
            font-size: 1.3em;
            font-weight: bold;
            color: #D2691E;
            margin-bottom: 5px;
        }

        .product-category {
            background: #D2691E;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            display: inline-block;
            margin-bottom: 10px;
        }

        .product-description {
            color: #6c757d;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .product-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        .product-meta span {
            padding: 8px;
            background: white;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e9ecef;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #D2691E;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9em;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 20px;
            }

            .header h1 {
                font-size: 2em;
            }

            .products-grid {
                max-height: none;
            }
        }

        .image-path-help {
            font-size: 0.85em;
            color: #6c757d;
            margin-top: 5px;
            font-style: italic;
        }

        .required {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>☕ Coffee Shop Admin</h1>
            <p>Manage your coffee products with ease</p>
            <button onclick="window.location.href='logout.php'" style="
        background-color: #d9534f;
        color: white;
        padding: 12px 24px;
        font-size: 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Segoe UI', sans-serif;
    " onmouseover="this.style.transform='scale(1.05)'; this.style.backgroundColor='#c9302c';"
                onmouseout="this.style.transform='scale(1)'; this.style.backgroundColor='#d9534f';">
                🔓 Logout
            </button>

        </div>

        <?php if ($message): ?>
            <div style="padding: 20px 30px 0;">
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div style="padding: 20px 30px 0;">
            <?php
            $totalProducts = count($products);
            $totalValue = array_sum(array_column($products, 'price'));
            $avgPrice = $totalProducts > 0 ? $totalValue / $totalProducts : 0;
            $categories = array_unique(array_column($products, 'category'));
            ?>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalProducts; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($avgPrice, 2); ?></div>
                    <div class="stat-label">Average Price</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($categories); ?></div>
                    <div class="stat-label">Categories</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo array_sum(array_column($products, 'stock')); ?></div>
                    <div class="stat-label">Total Stock</div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <!-- Add/Edit Product Form -->
            <div class="form-section">
                <h2><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="<?php echo $editProduct ? 'edit' : 'add'; ?>">
                    <?php if ($editProduct): ?>
                        <input type="hidden" name="id" value="<?php echo $editProduct['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="name">Product Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name"
                            value="<?php echo $editProduct ? htmlspecialchars($editProduct['name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description"
                            name="description"><?php echo $editProduct ? htmlspecialchars($editProduct['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price">Price ($) <span class="required">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" min="0"
                            value="<?php echo $editProduct ? $editProduct['price'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category <span class="required">*</span></label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Single Origin" <?php echo ($editProduct && $editProduct['category'] == 'Single Origin') ? 'selected' : ''; ?>>Single Origin</option>
                            <option value="Blend" <?php echo ($editProduct && $editProduct['category'] == 'Blend') ? 'selected' : ''; ?>>Blend</option>
                            <option value="Flavored" <?php echo ($editProduct && $editProduct['category'] == 'Flavored') ? 'selected' : ''; ?>>Flavored</option>
                            <option value="Espresso" <?php echo ($editProduct && $editProduct['category'] == 'Espresso') ? 'selected' : ''; ?>>Espresso</option>
                            <option value="Decaf" <?php echo ($editProduct && $editProduct['category'] == 'Decaf') ? 'selected' : ''; ?>>Decaf</option>
                            <option value="Premium" <?php echo ($editProduct && $editProduct['category'] == 'Premium') ? 'selected' : ''; ?>>Premium</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="image_path">Image Path <span class="required">*</span></label>
                        <input type="text" id="image_path" name="image_path"
                            value="<?php echo $editProduct ? htmlspecialchars($editProduct['image_path']) : ''; ?>"
                            required>
                        <div class="image-path-help">
                            Example: images/coffee-name.jpg<br>
                            Make sure the image exists in your project folder
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="stock">Stock Quantity <span class="required">*</span></label>
                        <input type="number" id="stock" name="stock" min="0"
                            value="<?php echo $editProduct ? $editProduct['stock'] : '0'; ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?php echo $editProduct ? 'Update Product' : 'Add Product'; ?>
                    </button>

                    <?php if ($editProduct): ?>
                        <a href="?" class="btn" style="background: #6c757d; color: white; margin-left: 10px;">Cancel
                            Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Products List -->
            <div class="products-section">
                <h2>Coffee Products (<?php echo count($products); ?>)</h2>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <head>
    <style>
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }

        .product-info {
            flex: 1;
            text-align: left;
        }

        .product-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }

        .product-price {
            font-size: 1em;
            font-weight: bold;
            color: #D2691E;
            margin: 5px 0;
        }

        .product-category {
            font-size: 0.9em;
            color: #555;
        }

        .product-header img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-left: 15px;
        }
    </style>
</head>

<body>
    <div class="product-header">
        <div class="product-info">
            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
            <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
        </div>

        <img src="../images/<?php echo htmlspecialchars($product['image_path']); ?>"
             alt="<?php echo htmlspecialchars($product['name']); ?>">
    </div>
</body>


                            <div class="product-description">
                                <?php echo htmlspecialchars($product['description']); ?>
                            </div>

                            <div class="product-meta">
                                <span><strong>Stock:</strong> <?php echo $product['stock']; ?> units</span>
                                <span><strong>Image:</strong>
                                    <?php echo htmlspecialchars($product['image_path']); ?></span>
                            </div>

                            <!--is me changes kiye hai or yee code use kiya hai sql mai  ALTER TABLE products ADD COLUMN visible TINYINT(1) NOT NULL DEFAULT 1 AFTER stock; -->
                            <div class="product-actions">
                                <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-edit">Edit</a>

                                <form method="POST" style="display: inline;"
                                    onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>

                                <!-- Show / Hide Button -->
                                <form method="POST" style="display:inline; margin-left:6px;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn"
                                        style="background: <?php echo ($product['visible']) ? '#ffc107' : '#28a745'; ?>; color: white;">
                                        <?php echo ($product['visible']) ? 'Hide' : 'Show'; ?>
                                    </button>
                                </form>
                                <!-- View Button -->
                                <a href="view_product.php?id=<?php echo $product['id']; ?>" class="btn"
                                    style="background:#007bff; color:white;">
                                    View
                                </a>

                            </div>

                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($products)): ?>
                        <div style="text-align: center; color: #6c757d; padding: 40px;">
                            <h3>No products found</h3>
                            <p>Start by adding your first coffee product!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide success messages
        setTimeout(function () {
            const successMsg = document.querySelector('.message.success');
            if (successMsg) {
                successMsg.style.opacity = '0';
                successMsg.style.transition = 'opacity 0.5s ease';
                setTimeout(() => successMsg.remove(), 500);
            }
        }, 3000);

        // Form validation
        document.querySelector('form').addEventListener('submit', function (e) {
            const name = document.getElementById('name').value.trim();
            const price = document.getElementById('price').value;
            const category = document.getElementById('category').value;
            const imagePath = document.getElementById('image_path').value.trim();

            if (!name || !price || !category || !imagePath) {
                alert('Please fill in all required fields!');
                e.preventDefault();
                return false;
            }

            if (parseFloat(price) <= 0) {
                alert('Price must be greater than 0!');
                e.preventDefault();
                return false;
            }
        });

        // Auto-generate image path suggestion
        document.getElementById('name').addEventListener('input', function () {
            const imagePath = document.getElementById('image_path');
            if (!imagePath.value || imagePath.dataset.auto === 'true') {
                const name = this.value.toLowerCase()
                    .replace(/[^a-z0-9\s]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/^-+|-+$/g, '');
                if (name) {
                    imagePath.value = `images/${name}.jpg`;
                    imagePath.dataset.auto = 'true';
                }
            }
        });

        document.getElementById('image_path').addEventListener('input', function () {
            this.dataset.auto = 'false';
        });
    </script>
</body>

</html>