<?php
// Database configuration
//  $host = 'sql201.infinityfree.com';
//  $dbname = 'if0_40149263_coffee_shop';
//  $username = 'if0_40149263'; // Change as needed
//  $password = 'mmdXl1csk57';     // Change as needed



$host = 'localhost';
$dbname = 'coffee_shop';
$username = 'root'; // Change as needed       
$password = '';     // Change as needed  

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get product by ID
if (!isset($_GET['id'])) {
    die("Product ID not provided!");
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$_GET['id']]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found!");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Product - <?php echo htmlspecialchars($product['name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #6F4E37;
            margin-bottom: 15px;
        }
        .product-details {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }
        .product-details img {
            max-width: 350px;
            height: auto;
            border-radius: 12px;
            border: 2px solid #ddd;
        }
        .info {
            flex: 1;
        }
        .info p {
            margin: 10px 0;
            font-size: 1.1em;
            color: #333;
        }
        .price {
            font-size: 1.4em;
            font-weight: bold;
            color: #D2691E;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 20px;
            background: #6F4E37;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #D2691E;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        <div class="product-details">
            <img src="../images/<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <div class="info">
                <p class="price">₹<?php echo number_format($product['price'], 2); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                <p><strong>Stock:</strong> <?php echo htmlspecialchars($product['stock']); ?> units</p>
                <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <a href="admin_dashboard.php" class="btn">⬅ Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
