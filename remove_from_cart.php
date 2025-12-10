<?php
require_once 'config.php';

// Set content type for JSON response if it's a POST request
$is_ajax = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($is_ajax) {
    header('Content-Type: application/json');
}

if (!isLoggedIn()) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Please log in to manage your cart']);
        exit;
    }
    redirect('login.php');
}

// Get cart item ID from GET or POST
$cart_id = 0;

if ($is_ajax) {
    $cart_id = intval($_POST['cart_id'] ?? 0);
} else {
    $cart_id = intval($_GET['id'] ?? 0);
}

if (!$cart_id) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid cart item ID']);
        exit;
    }
    redirect('cart.php');
}

try {
    // Verify the cart item belongs to the current user
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);
    $cart_item = $stmt->fetch();

    if (!$cart_item) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Cart item not found']);
            exit;
        }
        redirect('cart.php');
    }

    // Remove the item from cart
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $_SESSION['user_id']]);

    if ($is_ajax) {
        // Get updated cart count
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_count = $stmt->fetchColumn() ?: 0;

        // Get updated cart total
        $stmt = $pdo->prepare("
            SELECT SUM(c.quantity * p.price) as total 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_total = $stmt->fetchColumn() ?: 0;

        echo json_encode([
            'success' => true, 
            'message' => 'Item removed from cart successfully',
            'cart_count' => $cart_count,
            'cart_total' => number_format($cart_total, 2)
        ]);
        exit;
    }

    // Redirect back to cart for non-AJAX requests
    redirect('cart.php');

} catch (Exception $e) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'An error occurred while removing the item']);
        exit;
    }
    redirect('cart.php');
}
?>