<?php
require_once 'config.php';

// Set content type for JSON response if it's a POST request
$is_ajax = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($is_ajax) {
    header('Content-Type: application/json');
}

if (!isLoggedIn()) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart']);
        exit;
    }
    redirect('login.php');
}

// Get product ID from GET or POST
$product_id = 0;
$quantity = 1;

if ($is_ajax) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
} else {
    $product_id = intval($_GET['id'] ?? 0);
}

if (!$product_id) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    redirect('products.php');
}

// Validate quantity
if ($quantity < 1) {
    $quantity = 1;
}

try {
    // Check if product exists and has stock
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        redirect('products.php');
    }

    if ($product['stock'] <= 0) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Product is out of stock']);
            exit;
        }
        redirect('products.php');
    }

    // Check if requested quantity is available
    if ($quantity > $product['stock']) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Requested quantity exceeds available stock']);
            exit;
        }
        redirect('products.php');
    }

    // Check if item already in cart
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
    $existing_item = $stmt->fetch();

    if ($existing_item) {
        // Update quantity if already in cart
        $new_quantity = min($existing_item['quantity'] + $quantity, $product['stock']);
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $existing_item['id']]);
    } else {
        // Add new item to cart
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
    }

    if ($is_ajax) {
        // Get updated cart count
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_count = $stmt->fetchColumn() ?: 0;

        echo json_encode([
            'success' => true, 
            'message' => 'Product added to cart successfully',
            'cart_count' => $cart_count
        ]);
        exit;
    }

    // Redirect back to products or cart for non-AJAX requests
    $redirect_to = isset($_GET['redirect']) ? $_GET['redirect'] : 'cart.php';
    redirect($redirect_to);

} catch (Exception $e) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'An error occurred while adding the product to cart']);
        exit;
    }
    redirect('products.php');
}
?>