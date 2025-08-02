<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('products.php');
}

$product_id = intval($_GET['id']);

// Check if product exists and has stock
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product || $product['stock'] <= 0) {
    redirect('products.php');
}

// Check if item already in cart
$stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->execute([$_SESSION['user_id'], $product_id]);
$existing_item = $stmt->fetch();

if ($existing_item) {
    // Update quantity if already in cart
    $new_quantity = min($existing_item['quantity'] + 1, $product['stock']);
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt->execute([$new_quantity, $existing_item['id']]);
} else {
    // Add new item to cart
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
}

// Redirect back to products or cart
$redirect_to = isset($_GET['redirect']) ? $_GET['redirect'] : 'cart.php';
redirect($redirect_to);
?>