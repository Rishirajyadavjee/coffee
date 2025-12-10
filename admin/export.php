<?php
require_once 'config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    die('Access denied');
}

$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';

if ($type === 'users') {
    exportUsers($format);
} elseif ($type === 'sales') {
    exportSales($format);
} else {
    die('Invalid export type');
}

function exportUsers($format) {
    global $pdo;
    
    $filter = $_GET['filter'] ?? 'all';
    
    // Build query
    $where = "WHERE 1=1";
    $params = [];
    
    if ($filter == 'active') {
        $where .= " AND is_active = 1";
    } elseif ($filter == 'inactive') {
        $where .= " AND is_active = 0";
    } elseif ($filter == 'admin') {
        $where .= " AND is_admin = 1";
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            username,
            CONCAT(first_name, ' ', last_name) as full_name,
            email,
            phone,
            address,
            city,
            CASE WHEN is_active = 1 THEN 'Active' ELSE 'Inactive' END as status,
            CASE WHEN is_admin = 1 THEN 'Yes' ELSE 'No' END as is_admin,
            created_at
        FROM users 
        $where 
        ORDER BY created_at DESC
    ");
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    $filename = 'users_export_' . date('Y-m-d_H-i-s');
    
    if ($format === 'csv') {
        exportCSV($users, $filename, [
            'ID', 'Username', 'Full Name', 'Email', 'Phone', 'Address', 'City', 'Status', 'Admin', 'Joined Date'
        ]);
    } else {
        exportExcel($users, $filename, [
            'ID', 'Username', 'Full Name', 'Email', 'Phone', 'Address', 'City', 'Status', 'Admin', 'Joined Date'
        ]);
    }
}

function exportSales($format) {
    global $pdo;
    
    $date_filter = $_GET['date_filter'] ?? 'all';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    
    // Build query
    $where = "WHERE 1=1";
    $params = [];
    
    if ($date_filter == 'today') {
        $where .= " AND DATE(o.order_date) = CURDATE()";
    } elseif ($date_filter == 'week') {
        $where .= " AND o.order_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
    } elseif ($date_filter == 'month') {
        $where .= " AND o.order_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    } elseif ($date_filter == 'year') {
        $where .= " AND o.order_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    } elseif ($date_filter == 'custom' && $start_date && $end_date) {
        $where .= " AND DATE(o.order_date) BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            o.id as order_id,
            o.order_date,
            CONCAT(u.first_name, ' ', u.last_name) as customer_name,
            u.email as customer_email,
            u.phone as customer_phone,
            COUNT(oi.id) as item_count,
            o.total_amount,
            o.status,
            GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, 'x)') SEPARATOR ', ') as items
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        LEFT JOIN products p ON oi.product_id = p.id
        $where 
        GROUP BY o.id 
        ORDER BY o.order_date DESC
    ");
    $stmt->execute($params);
    $sales = $stmt->fetchAll();
    
    $filename = 'sales_export_' . date('Y-m-d_H-i-s');
    
    if ($format === 'csv') {
        exportCSV($sales, $filename, [
            'Order ID', 'Order Date', 'Customer Name', 'Customer Email', 'Customer Phone', 
            'Item Count', 'Total Amount', 'Status', 'Items'
        ]);
    } else {
        exportExcel($sales, $filename, [
            'Order ID', 'Order Date', 'Customer Name', 'Customer Email', 'Customer Phone', 
            'Item Count', 'Total Amount', 'Status', 'Items'
        ]);
    }
}

function exportCSV($data, $filename, $headers) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, array_values($row));
    }
    
    fclose($output);
    exit;
}

function exportExcel($data, $filename, $headers) {
    // Simple Excel export using HTML table format
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo '<table border="1">';
    
    // Headers
    echo '<tr>';
    foreach ($headers as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';
    
    // Data
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    exit;
}
?>