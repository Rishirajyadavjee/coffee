<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$success = '';
$error = '';
$activeTab = $_GET['tab'] ?? 'products';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            // Product Management
            case 'add_product':
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
                    $success = "Product added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding product: " . $e->getMessage();
                }
                break;

            case 'edit_product':
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
                    $success = "Product updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating product: " . $e->getMessage();
                }
                break;

            case 'delete_product':
                try {
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $success = "Product deleted successfully!";
                } catch (PDOException $e) {
                    $error = "Error deleting product: " . $e->getMessage();
                }
                break;

            case 'toggle_product':
                try {
                    $stmt = $pdo->prepare("UPDATE products SET visible = CASE WHEN visible = 1 THEN 0 ELSE 1 END WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $success = "Product visibility updated!";
                } catch (PDOException $e) {
                    $error = "Error updating visibility: " . $e->getMessage();
                }
                break;

            // User Management
            case 'toggle_user':
                $user_id = intval($_POST['user_id']);
                $stmt = $pdo->prepare("UPDATE users SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?");
                if ($stmt->execute([$user_id])) {
                    $success = "User status updated successfully!";
                } else {
                    $error = "Failed to update user status.";
                }
                break;
                
            case 'delete_user':
                $user_id = intval($_POST['user_id']);
                if ($user_id != $_SESSION['user_id']) {
                    try {
                        $pdo->beginTransaction();
                        
                        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        
                        $stmt = $pdo->prepare("DELETE oi FROM order_items oi INNER JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ?");
                        $stmt->execute([$user_id]);
                        
                        $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        
                        $pdo->commit();
                        $success = "User deleted successfully!";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = "Failed to delete user: " . $e->getMessage();
                    }
                } else {
                    $error = "You cannot delete your own account!";
                }
                break;

            // Booking Management
            case 'update_booking_status':
                $booking_id = intval($_POST['booking_id']);
                $status = sanitize($_POST['status']);
                $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $booking_id])) {
                    $success = "Booking status updated successfully!";
                } else {
                    $error = "Failed to update booking status.";
                }
                break;
                
            case 'delete_booking':
                $booking_id = intval($_POST['booking_id']);
                $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
                if ($stmt->execute([$booking_id])) {
                    $success = "Booking deleted successfully!";
                } else {
                    $error = "Failed to delete booking.";
                }
                break;

            // Contact Messages Management
            case 'update_message_status':
                $message_id = intval($_POST['message_id']);
                $status = sanitize($_POST['status']);
                $stmt = $pdo->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $message_id])) {
                    $success = "Message status updated successfully!";
                } else {
                    $error = "Failed to update message status.";
                }
                break;
                
            case 'delete_message':
                $message_id = intval($_POST['message_id']);
                $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
                if ($stmt->execute([$message_id])) {
                    $success = "Message deleted successfully!";
                } else {
                    $error = "Failed to delete message.";
                }
                break;

            // Order Management
            case 'update_order_status':
                $order_id = intval($_POST['order_id']);
                $new_status = sanitize($_POST['new_status']);
                
                // Validate status
                $valid_statuses = ['pending', 'confirmed', 'cancelled'];
                if (in_array($new_status, $valid_statuses)) {
                    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    if ($stmt->execute([$new_status, $order_id])) {
                        $success = "Order status updated to " . ucfirst($new_status) . " successfully!";
                    } else {
                        $error = "Failed to update order status.";
                    }
                } else {
                    $error = "Invalid order status.";
                }
                break;

            // Feedback Management
            case 'update_feedback_status':
                $feedback_id = intval($_POST['feedback_id']);
                $status = sanitize($_POST['status']);
                $stmt = $pdo->prepare("UPDATE feedback SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $feedback_id])) {
                    $success = "Feedback status updated successfully!";
                } else {
                    $error = "Failed to update feedback status.";
                }
                break;
                
            case 'delete_feedback':
                $feedback_id = intval($_POST['feedback_id']);
                $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
                if ($stmt->execute([$feedback_id])) {
                    $success = "Feedback deleted successfully!";
                } else {
                    $error = "Failed to delete feedback.";
                }
                break;
        }
    }
}

// Get products
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get product for editing
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get sales data
$sales = $pdo->query("
    SELECT 
        o.id,
        o.order_date,
        o.total_amount,
        o.status,
        CONCAT(u.first_name, ' ', u.last_name) as customer_name,
        u.email as customer_email,
        COUNT(oi.id) as item_count
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    GROUP BY o.id 
    ORDER BY o.order_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get filter parameters for reports
$report_filter = $_GET['report_filter'] ?? 'monthly';
$report_year = $_GET['report_year'] ?? date('Y');
$report_month = $_GET['report_month'] ?? date('m');

// Build sales report query based on filters
$report_where = "WHERE o.status = 'completed'";
$report_params = [];
$report_start_date = $_GET['report_start_date'] ?? '';
$report_end_date = $_GET['report_end_date'] ?? '';

if ($report_filter == 'monthly') {
    $report_where .= " AND YEAR(o.order_date) = ? AND MONTH(o.order_date) = ?";
    $report_params = [$report_year, $report_month];
} elseif ($report_filter == 'yearly') {
    $report_where .= " AND YEAR(o.order_date) = ?";
    $report_params = [$report_year];
} elseif ($report_filter == 'date_range' && $report_start_date && $report_end_date) {
    $report_where .= " AND DATE(o.order_date) BETWEEN ? AND ?";
    $report_params = [$report_start_date, $report_end_date];
}

// Get detailed sales report
$stmt = $pdo->prepare("
    SELECT 
        o.id as order_id,
        o.order_date,
        o.total_amount,
        o.status,
        CONCAT(u.first_name, ' ', u.last_name) as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        u.address as customer_address,
        u.city as customer_city,
        GROUP_CONCAT(CONCAT(p.name, ' (Qty: ', oi.quantity, ', Price: $', oi.price, ')') SEPARATOR '; ') as order_items,
        COUNT(oi.id) as item_count,
        SUM(oi.quantity) as total_quantity
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    LEFT JOIN products p ON oi.product_id = p.id
    $report_where 
    GROUP BY o.id 
    ORDER BY o.order_date DESC
");
$stmt->execute($report_params);
$sales_report = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly sales summary
$monthly_summary = $pdo->prepare("
    SELECT 
        YEAR(order_date) as year,
        MONTH(order_date) as month,
        ANY_VALUE(MONTHNAME(order_date)) as month_name,
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE status = 'completed' AND YEAR(order_date) = ?
    GROUP BY YEAR(order_date), MONTH(order_date)
    ORDER BY YEAR(order_date) DESC, MONTH(order_date) DESC
");

//$monthly_summary->execute([$report_year]);
$monthly_data = $monthly_summary->fetchAll(PDO::FETCH_ASSOC);

// Get yearly sales summary
$yearly_summary = $pdo->query("
    SELECT 
        YEAR(order_date) as year,
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE status = 'completed'
    GROUP BY YEAR(order_date)
    ORDER BY YEAR(order_date) DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Calculate report totals
$report_total_revenue = array_sum(array_column($sales_report, 'total_amount'));
$report_total_orders = count($sales_report);
$report_avg_order = $report_total_orders > 0 ? $report_total_revenue / $report_total_orders : 0;

// Get bookings data with filtering
$booking_where = "WHERE 1=1";
$booking_params = [];

if (isset($_GET['booking_status_filter']) && $_GET['booking_status_filter'] != 'all') {
    $booking_where .= " AND status = ?";
    $booking_params[] = $_GET['booking_status_filter'];
}

if (isset($_GET['booking_start_date']) && $_GET['booking_start_date']) {
    $booking_where .= " AND DATE(created_at) >= ?";
    $booking_params[] = $_GET['booking_start_date'];
}

if (isset($_GET['booking_end_date']) && $_GET['booking_end_date']) {
    $booking_where .= " AND DATE(created_at) <= ?";
    $booking_params[] = $_GET['booking_end_date'];
}

$stmt = $pdo->prepare("SELECT * FROM bookings $booking_where ORDER BY created_at DESC");
$stmt->execute($booking_params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get booking statistics
$booking_stats = [
    'total_bookings' => count($bookings),
    'pending_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'confirmed_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn(),
    'cancelled_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn()
];

// Get contact messages data with filtering
$message_where = "WHERE 1=1";
$message_params = [];

if (isset($_GET['message_status_filter']) && $_GET['message_status_filter'] != 'all') {
    $message_where .= " AND status = ?";
    $message_params[] = $_GET['message_status_filter'];
}

if (isset($_GET['message_start_date']) && $_GET['message_start_date']) {
    $message_where .= " AND DATE(created_at) >= ?";
    $message_params[] = $_GET['message_start_date'];
}

if (isset($_GET['message_end_date']) && $_GET['message_end_date']) {
    $message_where .= " AND DATE(created_at) <= ?";
    $message_params[] = $_GET['message_end_date'];
}

$stmt = $pdo->prepare("SELECT * FROM contact_messages $message_where ORDER BY created_at DESC");
$stmt->execute($message_params);
$contact_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get feedback data with filtering
$feedback_where = "WHERE 1=1";
$feedback_params = [];

if (isset($_GET['feedback_status_filter']) && $_GET['feedback_status_filter'] != 'all') {
    $feedback_where .= " AND status = ?";
    $feedback_params[] = $_GET['feedback_status_filter'];
}

if (isset($_GET['feedback_rating_filter']) && $_GET['feedback_rating_filter'] != 'all') {
    $feedback_where .= " AND rating = ?";
    $feedback_params[] = $_GET['feedback_rating_filter'];
}

if (isset($_GET['feedback_start_date']) && $_GET['feedback_start_date']) {
    $feedback_where .= " AND DATE(created_at) >= ?";
    $feedback_params[] = $_GET['feedback_start_date'];
}

if (isset($_GET['feedback_end_date']) && $_GET['feedback_end_date']) {
    $feedback_where .= " AND DATE(created_at) <= ?";
    $feedback_params[] = $_GET['feedback_end_date'];
}

$stmt = $pdo->prepare("SELECT * FROM feedback $feedback_where ORDER BY created_at DESC");
$stmt->execute($feedback_params);
$feedback_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get message and feedback statistics
$message_stats = [
    'total_messages' => count($contact_messages),
    'unread_messages' => $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn(),
    'read_messages' => $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'read'")->fetchColumn(),
    'total_feedback' => count($feedback_data),
    'new_feedback' => $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'new'")->fetchColumn(),
    'avg_rating' => $pdo->query("SELECT AVG(rating) FROM feedback")->fetchColumn() ?: 0
];

// Get statistics
$stats = [
    'total_products' => count($products),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn(),
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed'")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BrewMaster Coffee</title>
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
        }

        .admin-header {
            background: linear-gradient(135deg, #6B4423, #8B4513);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .tabs {
            display: flex;
            background: white;
            border-radius: 10px 10px 0 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 0;
        }

        .tab {
            flex: 1;
            padding: 1rem 2rem;
            text-align: center;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            text-decoration: none;
            color: #6B4423;
            font-weight: bold;
        }

        .tab:hover,
        .tab.active {
            background: #f8f9fa;
            border-bottom-color: #D2691E;
            color: #D2691E;
        }

        .tab-content {
            background: white;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            min-height: 600px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #D2691E, #FF8C00);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .form-section {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .form-section h3 {
            color: #6B4423;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
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
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
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
            padding: 10px 20px;
            background: linear-gradient(45deg, #D2691E, #FF8C00);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            margin: 0.25rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(210, 105, 30, 0.3);
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #e74c3c);
        }

        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
        }

        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
        }

        .btn-small {
            padding: 5px 10px;
            font-size: 0.875rem;
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
            color: #6B4423;
            font-weight: bold;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .product-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            transition: all 0.3s;
        }

        .product-card:hover {
            border-color: #D2691E;
            transform: translateY(-2px);
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .product-info h4 {
            color: #6B4423;
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #D2691E;
        }

        .product-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .status {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: bold;
        }

        .status.active {
            background: #d4edda;
            color: #155724;
        }

        .status.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status.visible {
            background: #d4edda;
            color: #155724;
        }

        .status.hidden {
            background: #f8d7da;
            color: #721c24;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status.confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .status.unread {
            background: #fff3cd;
            color: #856404;
        }

        .status.read {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status.replied {
            background: #d4edda;
            color: #155724;
        }

        .status.new {
            background: #fff3cd;
            color: #856404;
        }

        .status.reviewed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status.addressed {
            background: #d4edda;
            color: #155724;
        }

        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
        }

        .btn-warning:hover {
            background: linear-gradient(45deg, #e0a800, #e8590c);
        }

        .table tbody tr[data-status="inactive"] {
            opacity: 0.7;
            background-color: #f8f9fa;
        }

        .table tbody tr[data-status="inactive"]:hover {
            background-color: #e9ecef;
        }

        .user-status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .booking-details {
            max-width: 250px;
        }

        .booking-message {
            max-width: 200px;
            word-wrap: break-word;
        }

        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .search-box {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .report-summary {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #D2691E;
        }

        .report-summary h4 {
            color: #6B4423;
            margin-bottom: 1rem;
        }

        .table-responsive {
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .export-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .export-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .export-buttons .btn {
            padding: 8px 16px;
            font-size: 0.9rem;
            margin: 0;
        }

        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-form .form-group {
            margin-bottom: 0;
            min-width: 120px;
        }

        /* Admin Order Management Styles */
        .orders-management {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .admin-order-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border-left: 4px solid #D2691E;
        }

        .admin-order-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #eee;
        }

        .order-basic-info h3 {
            color: #6B4423;
            margin-bottom: 0.75rem;
            font-size: 1.3rem;
        }

        .customer-info,
        .order-date,
        .customer-contact {
            margin: 0.5rem 0;
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .customer-info i,
        .order-date i,
        .customer-contact i {
            color: #D2691E;
            width: 16px;
        }

        .order-status-management {
            text-align: right;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: flex-end;
        }

        .status-select {
            padding: 0.5rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            background: white;
            color: #6B4423;
            font-weight: bold;
            cursor: pointer;
        }

        .status-select:focus {
            outline: none;
            border-color: #D2691E;
        }

        .order-total-admin {
            font-size: 1.3rem;
            color: #D2691E;
            font-weight: bold;
        }

        .admin-order-items {
            padding: 2rem;
        }

        .admin-order-items h4 {
            color: #6B4423;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .admin-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .admin-order-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            align-items: center;
        }

        .admin-item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .admin-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .admin-item-details {
            flex: 1;
        }

        .admin-item-details h5 {
            color: #6B4423;
            margin: 0 0 0.25rem 0;
            font-size: 0.95rem;
        }

        .admin-item-pricing {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            font-size: 0.85rem;
        }

        .admin-item-pricing .quantity {
            background: #D2691E;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-weight: bold;
        }

        .admin-item-pricing .price {
            color: #666;
        }

        .admin-item-pricing .subtotal {
            color: #D2691E;
            font-weight: bold;
            margin-left: auto;
        }

        .no-orders {
            text-align: center;
            padding: 3rem;
            color: #666;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .no-orders i {
            font-size: 4rem;
            color: #D2691E;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .product-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .product-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .admin-order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .order-status-management {
                align-items: flex-start;
                width: 100%;
            }

            .admin-items-grid {
                grid-template-columns: 1fr;
            }

            .admin-order-item {
                flex-direction: column;
                text-align: center;
            }

            .admin-item-pricing {
                justify-content: center;
            }

            .admin-item-pricing .subtotal {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-coffee"></i> BrewMaster Admin
            </div>
            <div class="admin-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="../index.php" class="logout-btn">
                    <i class="fas fa-home"></i> Main Site
                </a>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_products']; ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $booking_stats['total_bookings']; ?></div>
                <div class="stat-label">Table Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₹<?php echo number_format($stats['total_revenue'], 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="tabs">
            <a href="?tab=products" class="tab <?php echo $activeTab == 'products' ? 'active' : ''; ?>">Products</a>
            <a href="?tab=orders" class="tab <?php echo $activeTab == 'orders' ? 'active' : ''; ?>">Orders</a>
            <a href="?tab=users" class="tab <?php echo $activeTab == 'users' ? 'active' : ''; ?>">Users</a>
            <a href="?tab=sales" class="tab <?php echo $activeTab == 'sales' ? 'active' : ''; ?>">Sales Reports & Analytics</a>
            <a href="?tab=bookings" class="tab <?php echo $activeTab == 'bookings' ? 'active' : ''; ?>">Bookings</a>
            <a href="?tab=messages" class="tab <?php echo $activeTab == 'messages' ? 'active' : ''; ?>">Messages</a>
        </div>

        <div class="tab-content">
            <?php if ($activeTab == 'orders'): ?>
                <!-- Orders Management Section -->
                <h2>Order Management</h2>
                
                <!-- Order Statistics -->
                <div class="stats-grid" style="margin-bottom: 2rem;">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(); ?></div>
                        <div class="stat-label">Pending Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'confirmed'")->fetchColumn(); ?></div>
                        <div class="stat-label">Confirmed Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn(); ?></div>
                        <div class="stat-label">Cancelled Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">₹<?php echo number_format($pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'confirmed' AND DATE(order_date) = CURDATE()")->fetchColumn(), 2); ?></div>
                        <div class="stat-label">Today's Revenue</div>
                    </div>
                </div>

                <!-- Order Filters -->
                <div class="export-section">
                    <form method="GET" class="filter-form">
                        <input type="hidden" name="tab" value="orders">
                        <div class="form-group">
                            <label>Status Filter:</label>
                            <select name="order_status_filter">
                                <option value="all" <?php echo ($_GET['order_status_filter'] ?? 'all') == 'all' ? 'selected' : ''; ?>>All Orders</option>
                                <option value="pending" <?php echo ($_GET['order_status_filter'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo ($_GET['order_status_filter'] ?? '') == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="cancelled" <?php echo ($_GET['order_status_filter'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>From Date:</label>
                            <input type="date" name="order_start_date" value="<?php echo $_GET['order_start_date'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>To Date:</label>
                            <input type="date" name="order_end_date" value="<?php echo $_GET['order_end_date'] ?? ''; ?>">
                        </div>
                        <button type="submit" class="btn">Filter Orders</button>
                    </form>
                </div>

                <?php
                // Get filtered orders
                $order_where = "WHERE 1=1";
                $order_params = [];

                if (isset($_GET['order_status_filter']) && $_GET['order_status_filter'] != 'all') {
                    $order_where .= " AND o.status = ?";
                    $order_params[] = $_GET['order_status_filter'];
                }

                if (isset($_GET['order_start_date']) && $_GET['order_start_date']) {
                    $order_where .= " AND DATE(o.order_date) >= ?";
                    $order_params[] = $_GET['order_start_date'];
                }

                if (isset($_GET['order_end_date']) && $_GET['order_end_date']) {
                    $order_where .= " AND DATE(o.order_date) <= ?";
                    $order_params[] = $_GET['order_end_date'];
                }

                $stmt = $pdo->prepare("
                    SELECT 
                        o.*,
                        CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                        u.email as customer_email,
                        u.phone as customer_phone,
                        COUNT(oi.id) as item_count,
                        SUM(oi.quantity) as total_quantity
                    FROM orders o 
                    LEFT JOIN users u ON o.user_id = u.id 
                    LEFT JOIN order_items oi ON o.id = oi.order_id 
                    $order_where 
                    GROUP BY o.id 
                    ORDER BY o.order_date DESC
                ");
                $stmt->execute($order_params);
                $filtered_orders = $stmt->fetchAll();
                ?>

                <!-- Orders List -->
                <div class="orders-management">
                    <?php if (count($filtered_orders) > 0): ?>
                        <?php foreach ($filtered_orders as $order): ?>
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
                            
                            <div class="admin-order-card">
                                <div class="admin-order-header">
                                    <div class="order-basic-info">
                                        <h3>Order #<?php echo $order['id']; ?></h3>
                                        <p class="customer-info">
                                            <i class="fas fa-user"></i>
                                            <strong><?php echo htmlspecialchars($order['customer_name'] ?: 'Guest User'); ?></strong>
                                        </p>
                                        <p class="order-date">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('F d, Y \a\t g:i A', strtotime($order['order_date'])); ?>
                                        </p>
                                        <?php if ($order['customer_email']): ?>
                                            <p class="customer-contact">
                                                <i class="fas fa-envelope"></i>
                                                <?php echo htmlspecialchars($order['customer_email']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($order['customer_phone']): ?>
                                            <p class="customer-contact">
                                                <i class="fas fa-phone"></i>
                                                <?php echo htmlspecialchars($order['customer_phone']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="order-status-management">
                                        <div class="current-status">
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <i class="fas <?php 
                                                    echo $order['status'] == 'pending' ? 'fa-clock' : 
                                                        ($order['status'] == 'confirmed' ? 'fa-check-circle' : 
                                                        ($order['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-info-circle')); 
                                                ?>"></i>
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="status-actions">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_order_status">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="new_status" onchange="this.form.submit()" class="status-select">
                                                    <option value="">Change Status...</option>
                                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'disabled' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'disabled' : ''; ?>>Confirmed</option>
                                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'disabled' : ''; ?>>Cancelled</option>
                                                </select>
                                            </form>
                                        </div>
                                        
                                        <div class="order-total-admin">
                                            <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="admin-order-items">
                                    <h4>Order Items (<?php echo $order['item_count']; ?> items):</h4>
                                    <div class="admin-items-grid">
                                        <?php foreach ($order_items as $item): ?>
                                            <div class="admin-order-item">
                                                <div class="admin-item-image">
                                                    <img src="../images/<?php echo htmlspecialchars(basename($item['image_path'])); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                         onerror="this.src='https://via.placeholder.com/60x60/D2691E/white?text=Coffee'">
                                                </div>
                                                <div class="admin-item-details">
                                                    <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                                    <p class="item-category"><?php echo htmlspecialchars($item['category']); ?></p>
                                                    <div class="admin-item-pricing">
                                                        <span class="quantity">Qty: <?php echo $item['quantity']; ?></span>
                                                        <span class="price">₹<?php echo number_format($item['price'], 2); ?></span>
                                                        <span class="subtotal">₹<?php echo number_format($item['quantity'] * $item['price'], 2); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-orders">
                            <i class="fas fa-shopping-bag"></i>
                            <h3>No Orders Found</h3>
                            <p>No orders match your current filter criteria.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($activeTab == 'products'): ?>
                <!-- Products Management -->
                <div class="form-section">
                    <h3><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $editProduct ? 'edit_product' : 'add_product'; ?>">
                        <?php if ($editProduct): ?>
                            <input type="hidden" name="id" value="<?php echo $editProduct['id']; ?>">
                        <?php endif; ?>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Product Name *</label>
                                <input type="text" id="name" name="name" value="<?php echo $editProduct ? htmlspecialchars($editProduct['name']) : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="price">Price ($) *</label>
                                <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $editProduct ? $editProduct['price'] : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="category">Category *</label>
                                <select id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Dark Roast" <?php echo ($editProduct && $editProduct['category'] == 'Dark Roast') ? 'selected' : ''; ?>>Dark Roast</option>
                                    <option value="Medium Roast" <?php echo ($editProduct && $editProduct['category'] == 'Medium Roast') ? 'selected' : ''; ?>>Medium Roast</option>
                                    <option value="Light Roast" <?php echo ($editProduct && $editProduct['category'] == 'Light Roast') ? 'selected' : ''; ?>>Light Roast</option>
                                    <option value="Espresso" <?php echo ($editProduct && $editProduct['category'] == 'Espresso') ? 'selected' : ''; ?>>Espresso</option>
                                    <option value="Decaf" <?php echo ($editProduct && $editProduct['category'] == 'Decaf') ? 'selected' : ''; ?>>Decaf</option>
                                    <option value="Cold Brew" <?php echo ($editProduct && $editProduct['category'] == 'Cold Brew') ? 'selected' : ''; ?>>Cold Brew</option>
                                    <option value="Accessories" <?php echo ($editProduct && $editProduct['category'] == 'Accessories') ? 'selected' : ''; ?>>Accessories</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="stock">Stock Quantity *</label>
                                <input type="number" id="stock" name="stock" min="0" value="<?php echo $editProduct ? $editProduct['stock'] : '0'; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="image_path">Image Filename *</label>
                                <input type="text" id="image_path" name="image_path" value="<?php echo $editProduct ? htmlspecialchars($editProduct['image_path']) : ''; ?>" required>
                                <small>Example: coffee-name.jpg (place in images/ folder)</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"><?php echo $editProduct ? htmlspecialchars($editProduct['description']) : ''; ?></textarea>
                        </div>

                        <button type="submit" class="btn">
                            <?php echo $editProduct ? 'Update Product' : 'Add Product'; ?>
                        </button>

                        <?php if ($editProduct): ?>
                            <a href="?tab=products" class="btn" style="background: #6c757d;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Products List -->
                <h3>All Products (<?php echo count($products); ?>)</h3>
                <input type="text" class="search-box" id="productSearch" placeholder="Search products..." onkeyup="searchProducts()">
                
                <div id="productsList">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" data-name="<?php echo strtolower($product['name']); ?>" data-category="<?php echo strtolower($product['category']); ?>">
                            <div class="product-header">
                                <div class="product-info">
                                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                                    <span class="status <?php echo ($product['visible'] ?? 1) ? 'visible' : 'hidden'; ?>">
                                        <?php echo ($product['visible'] ?? 1) ? 'Visible' : 'Hidden'; ?>
                                    </span>
                                </div>
                                <div class="product-actions">
                                    <a href="?tab=products&edit=<?php echo $product['id']; ?>" class="btn btn-small">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_product">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-small <?php echo ($product['visible'] ?? 1) ? 'btn-warning' : 'btn-success'; ?>">
                                            <?php echo ($product['visible'] ?? 1) ? 'Hide' : 'Show'; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                        <!-- View Button -->
                                <a href="view_product.php?id=<?php echo $product['id']; ?>" class="btn"
                                    style="background:#007bff; color:white;">
                                    View
                                </a>
                                    </form>
                                </div>
                            </div>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                            <p><strong>Stock:</strong> <?php echo $product['stock']; ?> units</p>
                            <p><strong>Description:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($activeTab == 'users'): ?>
                <!-- Users Management -->
                <h3>User Management (<?php echo count($users); ?> users)</h3>
                
                <!-- User Statistics -->
                <div class="stats-grid" style="margin-bottom: 2rem; grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($users, function($u) { return ($u['is_active'] ?? 1) && !$u['is_admin']; })); ?></div>
                        <div class="stat-label">Active Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($users, function($u) { return !($u['is_active'] ?? 1) && !$u['is_admin']; })); ?></div>
                        <div class="stat-label">Inactive Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($users, function($u) { return $u['is_admin']; })); ?></div>
                        <div class="stat-label">Administrators</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($users, function($u) { return !$u['is_admin']; })); ?></div>
                        <div class="stat-label">Regular Users</div>
                    </div>
                </div>

                <!-- User Filters -->
                <div style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center; flex-wrap: wrap;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="userStatusFilter">Filter by Status:</label>
                        <select id="userStatusFilter" onchange="filterUsersByStatus()">
                            <option value="all">All Users</option>
                            <option value="active">Active Only</option>
                            <option value="inactive">Inactive Only</option>
                            <option value="admin">Admins Only</option>
                        </select>
                    </div>
                    <input type="text" class="search-box" id="userSearch" placeholder="Search users..." onkeyup="searchUsers()" style="flex: 1;">
                </div>
                
                <div class="table-responsive">
                    <table class="table" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr data-name="<?php echo strtolower($user['first_name'] . ' ' . $user['last_name']); ?>" 
                                data-email="<?php echo strtolower($user['email']); ?>"
                                data-status="<?php echo ($user['is_active'] ?? 1) ? 'active' : 'inactive'; ?>"
                                data-role="<?php echo $user['is_admin'] ? 'admin' : 'user'; ?>">
                                <td>#<?php echo $user['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="status" style="background: #d1ecf1; color: #0c5460;">Admin</span>
                                    <?php endif; ?>
                                    <?php if (!($user['is_active'] ?? 1)): ?>
                                        <span class="status inactive" style="margin-left: 0.5rem;">Deactivated</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="status <?php echo ($user['is_active'] ?? 1) ? 'active' : 'inactive'; ?>">
                                        <i class="fas <?php echo ($user['is_active'] ?? 1) ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo ($user['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                            <form method="POST" style="display: inline;" onsubmit="return confirmUserToggle('<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>', <?php echo ($user['is_active'] ?? 1) ? 'true' : 'false'; ?>);">
                                                <input type="hidden" name="action" value="toggle_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-small <?php echo ($user['is_active'] ?? 1) ? 'btn-warning' : 'btn-success'; ?>" title="<?php echo ($user['is_active'] ?? 1) ? 'Deactivate user account' : 'Activate user account'; ?>">
                                                    <i class="fas <?php echo ($user['is_active'] ?? 1) ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                                    <?php echo ($user['is_active'] ?? 1) ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;" onsubmit="return confirmUserDelete('<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-small btn-danger" title="Permanently delete user">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="status" style="background: #d1ecf1; color: #0c5460;">
                                            <i class="fas fa-user-shield"></i> You
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($activeTab == 'sales'): ?>
                <!-- Sales Reports & Analytics -->
                <h2>Sales Reports & Analytics</h2>
                
                <!-- Report Filters -->
                <div class="export-section">
                    <form method="GET" class="filter-form">
                        <input type="hidden" name="tab" value="sales">
                        <div class="form-group">
                            <label>Report Type:</label>
                            <select name="report_filter" onchange="toggleReportDateFilters(this)">
                                <option value="monthly" <?php echo $report_filter == 'monthly' ? 'selected' : ''; ?>>Monthly Report</option>
                                <option value="yearly" <?php echo $report_filter == 'yearly' ? 'selected' : ''; ?>>Yearly Report</option>
                                <option value="date_range" <?php echo $report_filter == 'date_range' ? 'selected' : ''; ?>>Custom Date Range</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="reportYearFilter" style="<?php echo $report_filter == 'date_range' ? 'display: none;' : ''; ?>">
                            <label>Year:</label>
                            <select name="report_year">
                                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $report_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="reportMonthFilter" style="<?php echo ($report_filter == 'yearly' || $report_filter == 'date_range') ? 'display: none;' : ''; ?>">
                            <label>Month:</label>
                            <select name="report_month">
                                <?php 
                                $months = [
                                    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                                    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                                    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                                ];
                                foreach ($months as $num => $name): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $report_month == $num ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="reportStartDate" style="<?php echo $report_filter != 'date_range' ? 'display: none;' : ''; ?>">
                            <label>From Date:</label>
                            <input type="date" name="report_start_date" value="<?php echo $report_start_date; ?>">
                        </div>
                        
                        <div class="form-group" id="reportEndDate" style="<?php echo $report_filter != 'date_range' ? 'display: none;' : ''; ?>">
                            <label>To Date:</label>
                            <input type="date" name="report_end_date" value="<?php echo $report_end_date; ?>">
                        </div>
                        
                        <button type="submit" class="btn">Generate Report</button>
                    </form>
                    
                    <div class="export-buttons">
                        <a href="export_report.php?type=sales&filter=<?php echo $report_filter; ?>&year=<?php echo $report_year; ?>&month=<?php echo $report_month; ?>&start=<?php echo $report_start_date; ?>&end=<?php echo $report_end_date; ?>" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                        <a href="export_report.php?type=sales&format=csv&filter=<?php echo $report_filter; ?>&year=<?php echo $report_year; ?>&month=<?php echo $report_month; ?>&start=<?php echo $report_start_date; ?>&end=<?php echo $report_end_date; ?>" class="btn btn-warning">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                    </div>
                </div>

                <!-- Report Summary -->
                <div class="report-summary">
                    <h4>Report Summary</h4>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $report_total_orders; ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">₹<?php echo number_format($report_total_revenue, 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">₹<?php echo number_format($report_avg_order, 2); ?></div>
                            <div class="stat-label">Average Order Value</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo array_sum(array_column($sales_report, 'total_quantity')); ?></div>
                            <div class="stat-label">Items Sold</div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Summary (for yearly reports) -->
                <?php if ($report_filter == 'yearly' && !empty($monthly_data)): ?>
                <div class="form-section">
                    <h4>Monthly Breakdown for <?php echo $report_year; ?></h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Avg Order Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_data as $month): ?>
                                <tr>
                                    <td><?php echo $month['month_name']; ?></td>
                                    <td><?php echo $month['total_orders']; ?></td>
                                    <td>₹<?php echo number_format($month['total_revenue'], 2); ?></td>
                                    <td>₹<?php echo number_format($month['avg_order_value'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Yearly Summary -->
                <?php if (!empty($yearly_summary)): ?>
                <div class="form-section">
                    <h4>Yearly Performance</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Total Orders</th>
                                    <th>Total Revenue</th>
                                    <th>Average Order Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($yearly_summary as $year): ?>
                                <tr>
                                    <td><?php echo $year['year']; ?></td>
                                    <td><?php echo $year['total_orders']; ?></td>
                                    <td>₹<?php echo number_format($year['total_revenue'], 2); ?></td>
                                    <td>₹<?php echo number_format($year['avg_order_value'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Detailed Sales Report -->
                <div class="form-section">
                    <h4>Detailed Sales Report</h4>
                    <input type="text" class="search-box" id="salesSearch" placeholder="Search orders..." onkeyup="searchSales()">
                    
                    <div class="table-responsive">
                        <table class="table" id="salesTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales_report as $sale): ?>
                                <tr data-customer="<?php echo strtolower($sale['customer_name']); ?>" data-email="<?php echo strtolower($sale['customer_email']); ?>">
                                    <td>#<?php echo $sale['order_id']; ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($sale['order_date'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($sale['customer_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($sale['customer_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($sale['customer_phone']): ?>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($sale['customer_phone']); ?><br>
                                        <?php endif; ?>
                                        <?php if ($sale['customer_city']): ?>
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($sale['customer_city']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $sale['item_count']; ?> items</strong><br>
                                        <small>Qty: <?php echo $sale['total_quantity']; ?></small>
                                    </td>
                                    <td><strong>₹<?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="status <?php echo $sale['status']; ?>">
                                            <?php echo ucfirst($sale['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($activeTab == 'reports'): ?>
                <!-- Sales Reports -->
                <h3>Sales Reports & Analytics</h3>
                
                <!-- Report Filters -->
                <div class="form-section">
                    <h4>Generate Report</h4>
                    <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                        <input type="hidden" name="tab" value="reports">
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="report_filter">Report Type:</label>
                            <select id="report_filter" name="report_filter" onchange="toggleReportDateFilters()">
                                <option value="monthly" <?php echo $report_filter == 'monthly' ? 'selected' : ''; ?>>Monthly Report</option>
                                <option value="yearly" <?php echo $report_filter == 'yearly' ? 'selected' : ''; ?>>Yearly Report</option>
                                <option value="date_range" <?php echo $report_filter == 'date_range' ? 'selected' : ''; ?>>Custom Date Range</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="reportYearFilter" style="margin-bottom: 0; <?php echo $report_filter == 'date_range' ? 'display: none;' : ''; ?>">
                            <label for="report_year">Year:</label>
                            <select id="report_year" name="report_year">
                                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $report_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="reportMonthFilter" style="margin-bottom: 0; <?php echo ($report_filter == 'yearly' || $report_filter == 'date_range') ? 'display: none;' : ''; ?>">
                            <label for="report_month">Month:</label>
                            <select id="report_month" name="report_month">
                                <?php 
                                $months = [
                                    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                                    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                                    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                                ];
                                foreach ($months as $num => $name): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $report_month == $num ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" id="reportStartDate" style="margin-bottom: 0; <?php echo $report_filter != 'date_range' ? 'display: none;' : ''; ?>">
                            <label for="report_start_date">From:</label>
                            <input type="date" id="report_start_date" name="report_start_date" value="<?php echo $_GET['report_start_date'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group" id="reportEndDate" style="margin-bottom: 0; <?php echo $report_filter != 'date_range' ? 'display: none;' : ''; ?>">
                            <label for="report_end_date">To:</label>
                            <input type="date" id="report_end_date" name="report_end_date" value="<?php echo $_GET['report_end_date'] ?? ''; ?>">
                        </div>
                        
                        <button type="submit" class="btn">Generate Report</button>
                        
                        <a href="export_report.php?type=<?php echo $report_filter; ?>&year=<?php echo $report_year; ?>&month=<?php echo $report_month; ?>&report_start_date=<?php echo $_GET['report_start_date'] ?? ''; ?>&report_end_date=<?php echo $_GET['report_end_date'] ?? ''; ?>&format=excel" 
                           class="btn btn-success" target="_blank">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                        
                        <a href="export_report.php?type=<?php echo $report_filter; ?>&year=<?php echo $report_year; ?>&month=<?php echo $report_month; ?>&report_start_date=<?php echo $_GET['report_start_date'] ?? ''; ?>&report_end_date=<?php echo $_GET['report_end_date'] ?? ''; ?>&format=csv" 
                           class="btn" target="_blank">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                    </form>
                </div>

                <!-- Report Summary -->
                <div class="stats-grid" style="margin-bottom: 2rem;">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $report_total_orders; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">₹<?php echo number_format($report_total_revenue, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">₹<?php echo number_format($report_avg_order, 2); ?></div>
                        <div class="stat-label">Average Order</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php 
                            if ($report_filter == 'monthly') {
                                echo $months[$report_month] . ' ' . $report_year;
                            } else {
                                echo $report_year;
                            }
                            ?>
                        </div>
                        <div class="stat-label">Report Period</div>
                    </div>
                </div>

                <?php if ($report_filter == 'yearly'): ?>
                    <!-- Monthly Breakdown for Yearly Report -->
                    <h4>Monthly Breakdown for <?php echo $report_year; ?></h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Avg Order Value</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_data as $month): ?>
                                <tr>
                                    <td><?php echo $month['month_name'] . ' ' . $month['year']; ?></td>
                                    <td><?php echo $month['total_orders']; ?></td>
                                    <td>₹<?php echo number_format($month['total_revenue'], 2); ?></td>
                                    <td>₹<?php echo number_format($month['avg_order_value'], 2); ?></td>
                                    <td>
                                        <a href="?tab=reports&report_filter=monthly&report_year=<?php echo $month['year']; ?>&report_month=<?php echo sprintf('%02d', $month['month']); ?>" 
                                           class="btn btn-small">View Details</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Detailed Sales Report -->
                <h4>
                    Detailed Sales Report 
                    <?php if ($report_filter == 'monthly'): ?>
                        - <?php echo $months[$report_month] . ' ' . $report_year; ?>
                    <?php else: ?>
                        - <?php echo $report_year; ?>
                    <?php endif; ?>
                </h4>
                
                <input type="text" class="search-box" id="reportSearch" placeholder="Search orders..." onkeyup="searchReport()">
                
                <div class="table-responsive">
                    <table class="table" id="reportTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Items</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales_report as $order): ?>
                            <tr data-customer="<?php echo strtolower($order['customer_name']); ?>" data-email="<?php echo strtolower($order['customer_email']); ?>">
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                </td>
                                <td>
                                    <?php if ($order['customer_phone']): ?>
                                        <strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($order['customer_address']): ?>
                                        <strong>Address:</strong> <?php echo htmlspecialchars($order['customer_address']); ?>
                                        <?php if ($order['customer_city']): ?>
                                            , <?php echo htmlspecialchars($order['customer_city']); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($order['order_items']); ?></small>
                                </td>
                                <td><?php echo $order['total_quantity']; ?> items</td>
                                <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($sales_report)): ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>No Sales Data Found</h3>
                        <p>No completed orders found for the selected period.</p>
                    </div>
                <?php endif; ?>

            <?php elseif ($activeTab == 'bookings'): ?>
                <!-- Bookings Management -->
                <h3>Table Bookings Management</h3>
                
                <!-- Booking Statistics -->
                <div class="stats-grid" style="margin-bottom: 2rem;">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $booking_stats['total_bookings']; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $booking_stats['pending_bookings']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $booking_stats['confirmed_bookings']; ?></div>
                        <div class="stat-label">Confirmed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $booking_stats['cancelled_bookings']; ?></div>
                        <div class="stat-label">Cancelled</div>
                    </div>
                </div>

                <!-- Booking Filters -->
                <div class="form-section">
                    <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap; justify-content: space-between;">
                        <input type="hidden" name="tab" value="bookings">
                        <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="booking_status_filter">Status:</label>
                                <select id="booking_status_filter" name="booking_status_filter">
                                    <option value="all" <?php echo ($_GET['booking_status_filter'] ?? 'all') == 'all' ? 'selected' : ''; ?>>All Bookings</option>
                                    <option value="pending" <?php echo ($_GET['booking_status_filter'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo ($_GET['booking_status_filter'] ?? '') == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="cancelled" <?php echo ($_GET['booking_status_filter'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="booking_start_date">From Date:</label>
                                <input type="date" id="booking_start_date" name="booking_start_date" value="<?php echo $_GET['booking_start_date'] ?? ''; ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="booking_end_date">To Date:</label>
                                <input type="date" id="booking_end_date" name="booking_end_date" value="<?php echo $_GET['booking_end_date'] ?? ''; ?>">
                            </div>
                            <button type="submit" class="btn">Filter</button>
                            <input type="text" class="search-box" id="bookingSearch" placeholder="Search bookings..." onkeyup="searchBookings()" style="width: 250px; margin-bottom: 0;">
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="export_bookings.php?format=excel&booking_status_filter=<?php echo $_GET['booking_status_filter'] ?? 'all'; ?>&booking_start_date=<?php echo $_GET['booking_start_date'] ?? ''; ?>&booking_end_date=<?php echo $_GET['booking_end_date'] ?? ''; ?>" class="btn btn-success" target="_blank">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </a>
                            <a href="export_bookings.php?format=csv&booking_status_filter=<?php echo $_GET['booking_status_filter'] ?? 'all'; ?>&booking_start_date=<?php echo $_GET['booking_start_date'] ?? ''; ?>&booking_end_date=<?php echo $_GET['booking_end_date'] ?? ''; ?>" class="btn" target="_blank">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Bookings List -->
                <div class="table-responsive">
                    <table class="table" id="bookingsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer Details</th>
                                <th>Booking Date</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr data-status="<?php echo $booking['status']; ?>" 
                                data-name="<?php echo strtolower($booking['name']); ?>" 
                                data-email="<?php echo strtolower($booking['email']); ?>">
                                <td>#<?php echo $booking['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['name']); ?></strong>
                                    <?php if ($booking['booking_type'] == 'guest'): ?>
                                        <span class="status" style="background: #fff3cd; color: #856404; font-size: 0.7rem; margin-left: 0.5rem;">GUEST</span>
                                    <?php endif; ?>
                                    <br>
                                    <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($booking['email']); ?></small><br>
                                    <?php if ($booking['phone']): ?>
                                        <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['phone']); ?></small><br>
                                    <?php endif; ?>
                                    <?php if ($booking['party_size']): ?>
                                        <small><i class="fas fa-users"></i> <?php echo $booking['party_size']; ?> people</small><br>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['booking_type'] == 'guest' && $booking['booked_by_user_id']): ?>
                                        <?php
                                        $booker_stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
                                        $booker_stmt->execute([$booking['booked_by_user_id']]);
                                        $booker = $booker_stmt->fetch();
                                        if ($booker):
                                        ?>
                                            <small style="color: #6c757d; font-style: italic;">
                                                <i class="fas fa-user-friends"></i> Booked by: <?php echo htmlspecialchars($booker['first_name'] . ' ' . $booker['last_name']); ?>
                                                <br><span style="margin-left: 1rem;"><?php echo htmlspecialchars($booker['email']); ?></span>
                                            </small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($booking['booking_date']): ?>
                                        <strong><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></strong><br>
                                        <?php if ($booking['booking_time']): ?>
                                            <small><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($booking['booking_time'])); ?></small><br>
                                        <?php endif; ?>
                                        <small><?php echo date('l', strtotime($booking['booking_date'])); ?></small>
                                    <?php else: ?>
                                        <em>Not specified</em>
                                    <?php endif; ?>
                                    <?php if ($booking['table_id']): ?>
                                        <?php
                                        $table_stmt = $pdo->prepare("SELECT table_name, table_number FROM restaurant_tables WHERE id = ?");
                                        $table_stmt->execute([$booking['table_id']]);
                                        $table_info = $table_stmt->fetch();
                                        if ($table_info):
                                        ?>
                                            <br><small><i class="fas fa-chair"></i> <?php echo htmlspecialchars($table_info['table_name']); ?> (<?php echo $table_info['table_number']; ?>)</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($booking['message']): ?>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars(substr($booking['message'], 0, 100)); ?>
                                            <?php if (strlen($booking['message']) > 100): ?>...<?php endif; ?>
                                        </div>
                                        <?php if (strlen($booking['message']) > 100): ?>
                                            <small><a href="#" onclick="showFullMessage('<?php echo addslashes($booking['message']); ?>')">Read more</a></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <em>No message</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status <?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                        <!-- Status Update Form -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_booking_status">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="btn btn-small" style="padding: 3px 8px; font-size: 0.8rem;">
                                                <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                        
                                        <!-- Delete Button -->
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                                            <input type="hidden" name="action" value="delete_booking">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" class="btn btn-small btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($bookings)): ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <i class="fas fa-calendar-alt" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>No Bookings Found</h3>
                        <p>No table booking requests have been submitted yet.</p>
                    </div>
                <?php endif; ?>

            <?php elseif ($activeTab == 'messages'): ?>
                <!-- Messages & Feedback Management -->
                <h3>Messages & Feedback Management</h3>
                
                <!-- Message Statistics -->
                <div class="stats-grid" style="margin-bottom: 2rem;">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $message_stats['total_messages']; ?></div>
                        <div class="stat-label">Total Messages</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $message_stats['unread_messages']; ?></div>
                        <div class="stat-label">Unread Messages</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $message_stats['total_feedback']; ?></div>
                        <div class="stat-label">Total Feedback</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($message_stats['avg_rating'], 1); ?>/5</div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                </div>

                <!-- Contact Messages Section -->
                <div style="margin-bottom: 3rem;">
                    <h4 style="color: #6B4423; margin-bottom: 1rem; font-size: 1.5rem;">
                        <i class="fas fa-envelope"></i> Contact Messages (<?php echo count($contact_messages); ?>)
                    </h4>
                    
                    <form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: end; flex-wrap: wrap;">
                        <input type="hidden" name="tab" value="messages">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="message_status_filter">Status:</label>
                            <select id="message_status_filter" name="message_status_filter">
                                <option value="all" <?php echo ($_GET['message_status_filter'] ?? 'all') == 'all' ? 'selected' : ''; ?>>All Messages</option>
                                <option value="unread" <?php echo ($_GET['message_status_filter'] ?? '') == 'unread' ? 'selected' : ''; ?>>Unread</option>
                                <option value="read" <?php echo ($_GET['message_status_filter'] ?? '') == 'read' ? 'selected' : ''; ?>>Read</option>
                                <option value="replied" <?php echo ($_GET['message_status_filter'] ?? '') == 'replied' ? 'selected' : ''; ?>>Replied</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="message_start_date">From Date:</label>
                            <input type="date" id="message_start_date" name="message_start_date" value="<?php echo $_GET['message_start_date'] ?? ''; ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="message_end_date">To Date:</label>
                            <input type="date" id="message_end_date" name="message_end_date" value="<?php echo $_GET['message_end_date'] ?? ''; ?>">
                        </div>
                        <button type="submit" class="btn">Filter</button>
                        <input type="text" class="search-box" id="messageSearch" placeholder="Search messages..." onkeyup="searchMessages()" style="width: 200px; margin-bottom: 0;">
                        <a href="export_messages.php?type=contact&format=excel&message_status_filter=<?php echo $_GET['message_status_filter'] ?? 'all'; ?>&message_start_date=<?php echo $_GET['message_start_date'] ?? ''; ?>&message_end_date=<?php echo $_GET['message_end_date'] ?? ''; ?>" class="btn btn-success" target="_blank" style="width: auto; padding: 8px 16px;">
                            <i class="fas fa-file-excel"></i> Export
                        </a>
                    </form>

                    <div class="table-responsive">
                        <table class="table" id="messagesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Contact Details</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Received</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contact_messages as $message): ?>
                                <tr data-name="<?php echo strtolower($message['name']); ?>" 
                                    data-email="<?php echo strtolower($message['email']); ?>"
                                    data-subject="<?php echo strtolower($message['subject']); ?>">
                                    <td>#<?php echo $message['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($message['name']); ?></strong><br>
                                        <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($message['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="status" style="background: #e9ecef; color: #495057;">
                                            <?php echo htmlspecialchars($message['subject']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars(substr($message['message'], 0, 100)); ?>
                                            <?php if (strlen($message['message']) > 100): ?>...<?php endif; ?>
                                        </div>
                                        <?php if (strlen($message['message']) > 100): ?>
                                            <small><a href="#" onclick="showFullMessage('<?php echo addslashes($message['message']); ?>')">Read more</a></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status <?php echo $message['status']; ?>">
                                            <?php echo ucfirst($message['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_message_status">
                                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" class="btn btn-small" style="padding: 3px 8px; font-size: 0.8rem;">
                                                    <option value="unread" <?php echo $message['status'] == 'unread' ? 'selected' : ''; ?>>Unread</option>
                                                    <option value="read" <?php echo $message['status'] == 'read' ? 'selected' : ''; ?>>Read</option>
                                                    <option value="replied" <?php echo $message['status'] == 'replied' ? 'selected' : ''; ?>>Replied</option>
                                                </select>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                                <input type="hidden" name="action" value="delete_message">
                                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                                <button type="submit" class="btn btn-small btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Feedback Section -->
                <div>
                    <h4 style="color: #6B4423; margin-bottom: 1rem; font-size: 1.5rem;">
                        <i class="fas fa-star"></i> Customer Feedback (<?php echo count($feedback_data); ?>)
                    </h4>
                    
                    <form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: end; flex-wrap: wrap;">
                        <input type="hidden" name="tab" value="messages">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="feedback_status_filter">Status:</label>
                            <select id="feedback_status_filter" name="feedback_status_filter">
                                <option value="all" <?php echo ($_GET['feedback_status_filter'] ?? 'all') == 'all' ? 'selected' : ''; ?>>All Feedback</option>
                                <option value="new" <?php echo ($_GET['feedback_status_filter'] ?? '') == 'new' ? 'selected' : ''; ?>>New</option>
                                <option value="reviewed" <?php echo ($_GET['feedback_status_filter'] ?? '') == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="addressed" <?php echo ($_GET['feedback_status_filter'] ?? '') == 'addressed' ? 'selected' : ''; ?>>Addressed</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="feedback_rating_filter">Rating:</label>
                            <select id="feedback_rating_filter" name="feedback_rating_filter">
                                <option value="all" <?php echo ($_GET['feedback_rating_filter'] ?? 'all') == 'all' ? 'selected' : ''; ?>>All Ratings</option>
                                <option value="5" <?php echo ($_GET['feedback_rating_filter'] ?? '') == '5' ? 'selected' : ''; ?>>5 Stars</option>
                                <option value="4" <?php echo ($_GET['feedback_rating_filter'] ?? '') == '4' ? 'selected' : ''; ?>>4 Stars</option>
                                <option value="3" <?php echo ($_GET['feedback_rating_filter'] ?? '') == '3' ? 'selected' : ''; ?>>3 Stars</option>
                                <option value="2" <?php echo ($_GET['feedback_rating_filter'] ?? '') == '2' ? 'selected' : ''; ?>>2 Stars</option>
                                <option value="1" <?php echo ($_GET['feedback_rating_filter'] ?? '') == '1' ? 'selected' : ''; ?>>1 Star</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="feedback_start_date">From Date:</label>
                            <input type="date" id="feedback_start_date" name="feedback_start_date" value="<?php echo $_GET['feedback_start_date'] ?? ''; ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="feedback_end_date">To Date:</label>
                            <input type="date" id="feedback_end_date" name="feedback_end_date" value="<?php echo $_GET['feedback_end_date'] ?? ''; ?>">
                        </div>
                        <button type="submit" class="btn">Filter</button>
                        <input type="text" class="search-box" id="feedbackSearch" placeholder="Search feedback..." onkeyup="searchFeedback()" style="width: 200px; margin-bottom: 0;">
                        <a href="export_messages.php?type=feedback&format=excel&feedback_status_filter=<?php echo $_GET['feedback_status_filter'] ?? 'all'; ?>&feedback_rating_filter=<?php echo $_GET['feedback_rating_filter'] ?? 'all'; ?>&feedback_start_date=<?php echo $_GET['feedback_start_date'] ?? ''; ?>&feedback_end_date=<?php echo $_GET['feedback_end_date'] ?? ''; ?>" class="btn btn-success" target="_blank" style="width: auto; padding: 8px 16px;">
                            <i class="fas fa-file-excel"></i> Export
                        </a>
                    </form>

                    <div class="table-responsive">
                        <table class="table" id="feedbackTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Rating</th>
                                    <th>Service Type</th>
                                    <th>Feedback</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($feedback_data as $feedback): ?>
                                <tr data-name="<?php echo strtolower($feedback['customer_name']); ?>" 
                                    data-email="<?php echo strtolower($feedback['customer_email']); ?>">
                                    <td>#<?php echo $feedback['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($feedback['customer_name']); ?></strong><br>
                                        <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($feedback['customer_email']); ?></small>
                                    </td>
                                    <td>
                                        <div style="color: #FFD700; font-size: 1.2rem;">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $feedback['rating']): ?>
                                                    ★
                                                <?php else: ?>
                                                    ☆
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <small><?php echo $feedback['rating']; ?>/5</small>
                                    </td>
                                    <td>
                                        <span class="status" style="background: #e9ecef; color: #495057;">
                                            <?php echo htmlspecialchars($feedback['service_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars(substr($feedback['message'], 0, 100)); ?>
                                            <?php if (strlen($feedback['message']) > 100): ?>...<?php endif; ?>
                                        </div>
                                        <?php if (strlen($feedback['message']) > 100): ?>
                                            <small><a href="#" onclick="showFullMessage('<?php echo addslashes($feedback['message']); ?>')">Read more</a></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status <?php echo $feedback['status']; ?>">
                                            <?php echo ucfirst($feedback['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($feedback['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_feedback_status">
                                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" class="btn btn-small" style="padding: 3px 8px; font-size: 0.8rem;">
                                                    <option value="new" <?php echo $feedback['status'] == 'new' ? 'selected' : ''; ?>>New</option>
                                                    <option value="reviewed" <?php echo $feedback['status'] == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                                    <option value="addressed" <?php echo $feedback['status'] == 'addressed' ? 'selected' : ''; ?>>Addressed</option>
                                                </select>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                                                <input type="hidden" name="action" value="delete_feedback">
                                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                                <button type="submit" class="btn btn-small btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (empty($contact_messages) && empty($feedback_data)): ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>No Messages or Feedback</h3>
                        <p>No contact messages or feedback have been submitted yet.</p>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <script>
        function searchProducts() {
            const searchTerm = document.getElementById('productSearch').value.toLowerCase();
            const products = document.querySelectorAll('.product-card');
            
            products.forEach(product => {
                const name = product.getAttribute('data-name');
                const category = product.getAttribute('data-category');
                
                if (name.includes(searchTerm) || category.includes(searchTerm)) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }

        function searchUsers() {
            const searchTerm = document.getElementById('userSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const email = row.getAttribute('data-email');
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterUsersByStatus() {
            const statusFilter = document.getElementById('userStatusFilter').value;
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                const role = row.getAttribute('data-role');
                
                let shouldShow = false;
                
                switch (statusFilter) {
                    case 'all':
                        shouldShow = true;
                        break;
                    case 'active':
                        shouldShow = status === 'active';
                        break;
                    case 'inactive':
                        shouldShow = status === 'inactive';
                        break;
                    case 'admin':
                        shouldShow = role === 'admin';
                        break;
                }
                
                row.style.display = shouldShow ? 'table-row' : 'none';
            });
        }

        function confirmUserToggle(userName, isActive) {
            const action = isActive ? 'deactivate' : 'activate';
            const message = isActive 
                ? `Are you sure you want to deactivate ${userName}? They will not be able to log in until reactivated.`
                : `Are you sure you want to activate ${userName}? They will be able to log in and use the system.`;
            
            return confirm(message);
        }

        function confirmUserDelete(userName) {
            return confirm(`Are you sure you want to permanently delete ${userName}? This action cannot be undone and will remove all their data including orders, bookings, and profile information.`);
        }

        function searchSales() {
            const searchTerm = document.getElementById('salesSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#salesTable tbody tr');
            
            rows.forEach(row => {
                const customer = row.getAttribute('data-customer');
                const email = row.getAttribute('data-email');
                
                if (customer.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function searchReport() {
            const searchTerm = document.getElementById('reportSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#reportTable tbody tr');
            
            rows.forEach(row => {
                const customer = row.getAttribute('data-customer');
                const email = row.getAttribute('data-email');
                
                if (customer.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function toggleReportDateFilters() {
            const filterType = document.getElementById('report_filter').value;
            const yearFilter = document.getElementById('reportYearFilter');
            const monthFilter = document.getElementById('reportMonthFilter');
            const startDateFilter = document.getElementById('reportStartDate');
            const endDateFilter = document.getElementById('reportEndDate');
            
            if (filterType === 'yearly') {
                yearFilter.style.display = 'block';
                monthFilter.style.display = 'none';
                startDateFilter.style.display = 'none';
                endDateFilter.style.display = 'none';
            } else if (filterType === 'monthly') {
                yearFilter.style.display = 'block';
                monthFilter.style.display = 'block';
                startDateFilter.style.display = 'none';
                endDateFilter.style.display = 'none';
            } else if (filterType === 'date_range') {
                yearFilter.style.display = 'none';
                monthFilter.style.display = 'none';
                startDateFilter.style.display = 'block';
                endDateFilter.style.display = 'block';
            }
        }

        function searchBookings() {
            const searchTerm = document.getElementById('bookingSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#bookingsTable tbody tr');
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const email = row.getAttribute('data-email');
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterBookings() {
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#bookingsTable tbody tr');
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                
                if (statusFilter === 'all' || status === statusFilter) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function showFullMessage(message) {
            alert(message);
        }

        function searchMessages() {
            const searchTerm = document.getElementById('messageSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#messagesTable tbody tr');
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const email = row.getAttribute('data-email');
                const subject = row.getAttribute('data-subject');
                
                if (name.includes(searchTerm) || email.includes(searchTerm) || subject.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function searchFeedback() {
            const searchTerm = document.getElementById('feedbackSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#feedbackTable tbody tr');
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const email = row.getAttribute('data-email');
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Auto-hide success messages
        setTimeout(function() {
            const successMsg = document.querySelector('.message.success');
            if (successMsg) {
                successMsg.style.opacity = '0';
                successMsg.style.transition = 'opacity 0.5s ease';
                setTimeout(() => successMsg.remove(), 500);
            }
        }, 3000);

        // Sales report functions
        function searchSales() {
            const searchTerm = document.getElementById('salesSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#salesTable tbody tr');
            
            rows.forEach(row => {
                const customer = row.getAttribute('data-customer');
                const email = row.getAttribute('data-email');
                const orderText = row.textContent.toLowerCase();
                
                if (customer.includes(searchTerm) || email.includes(searchTerm) || orderText.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function toggleReportDateFilters(selectElement) {
            const reportType = selectElement.value;
            const yearFilter = document.getElementById('reportYearFilter');
            const monthFilter = document.getElementById('reportMonthFilter');
            const startDateFilter = document.getElementById('reportStartDate');
            const endDateFilter = document.getElementById('reportEndDate');
            
            // Hide all filters first
            yearFilter.style.display = 'none';
            monthFilter.style.display = 'none';
            startDateFilter.style.display = 'none';
            endDateFilter.style.display = 'none';
            
            // Show relevant filters based on selection
            switch (reportType) {
                case 'monthly':
                    yearFilter.style.display = 'block';
                    monthFilter.style.display = 'block';
                    break;
                case 'yearly':
                    yearFilter.style.display = 'block';
                    break;
                case 'date_range':
                    startDateFilter.style.display = 'block';
                    endDateFilter.style.display = 'block';
                    break;
            }
        }
    </script>
</body>
</html>