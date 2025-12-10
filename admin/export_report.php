<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    die('Access denied');
}

$type = $_GET['type'] ?? 'monthly';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$format = $_GET['format'] ?? 'excel';
$report_start_date = $_GET['report_start_date'] ?? '';
$report_end_date = $_GET['report_end_date'] ?? '';

// Build query based on type
$where = "WHERE o.status = 'completed'";
$params = [];
$filename_suffix = '';

if ($type == 'monthly') {
    $where .= " AND YEAR(o.order_date) = ? AND MONTH(o.order_date) = ?";
    $params = [$year, $month];
    $months = [
        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
        '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
        '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
    ];
    $filename_suffix = $months[$month] . '_' . $year;
} elseif ($type == 'yearly') {
    $where .= " AND YEAR(o.order_date) = ?";
    $params = [$year];
    $filename_suffix = 'Year_' . $year;
} elseif ($type == 'date_range' && $report_start_date && $report_end_date) {
    $where .= " AND DATE(o.order_date) BETWEEN ? AND ?";
    $params = [$report_start_date, $report_end_date];
    $filename_suffix = date('M_d_Y', strtotime($report_start_date)) . '_to_' . date('M_d_Y', strtotime($report_end_date));
}

// Get detailed sales data
$stmt = $pdo->prepare("
    SELECT 
        o.id as order_id,
        DATE_FORMAT(o.order_date, '%Y-%m-%d %H:%i:%s') as order_date,
        o.total_amount,
        o.status,
        CONCAT(u.first_name, ' ', u.last_name) as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        u.address as customer_address,
        u.city as customer_city,
        GROUP_CONCAT(CONCAT(p.name, ' (Qty: ', oi.quantity, ', Price: $', FORMAT(oi.price, 2), ')') SEPARATOR '; ') as order_items,
        COUNT(oi.id) as item_count,
        SUM(oi.quantity) as total_quantity
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    LEFT JOIN products p ON oi.product_id = p.id
    $where 
    GROUP BY o.id 
    ORDER BY o.order_date DESC
");
$stmt->execute($params);
$sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary
$total_revenue = array_sum(array_column($sales_data, 'total_amount'));
$total_orders = count($sales_data);
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// Handle different export formats
if ($format == 'csv') {
    exportCSV($sales_data, $filename_suffix, $total_orders, $total_revenue, $avg_order_value, $type, $year);
} else {
    exportExcel($sales_data, $filename_suffix, $total_orders, $total_revenue, $avg_order_value, $type, $year);
}

function exportCSV($sales_data, $filename_suffix, $total_orders, $total_revenue, $avg_order_value, $type, $year) {
    global $pdo;
    
    $filename = 'Sales_Report_' . $filename_suffix . '_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Report header
    fputcsv($output, ['BrewMaster Coffee - Sales Report']);
    fputcsv($output, ['Report Period: ' . $filename_suffix]);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Summary
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Orders', 'Total Revenue', 'Average Order Value']);
    fputcsv($output, [$total_orders, '$' . number_format($total_revenue, 2), '$' . number_format($avg_order_value, 2)]);
    fputcsv($output, []);
    
    // Headers for detailed data
    fputcsv($output, ['DETAILED SALES DATA']);
    fputcsv($output, [
        'Order ID', 'Date', 'Customer Name', 'Customer Email', 'Customer Phone', 
        'Customer Address', 'Customer City', 'Items Ordered', 'Total Quantity', 
        'Order Amount', 'Status'
    ]);
    
    // Data rows
    foreach ($sales_data as $order) {
        fputcsv($output, [
            '#' . $order['order_id'],
            $order['order_date'],
            $order['customer_name'],
            $order['customer_email'],
            $order['customer_phone'] ?: 'N/A',
            $order['customer_address'] ?: 'N/A',
            $order['customer_city'] ?: 'N/A',
            $order['order_items'],
            $order['total_quantity'],
            '$' . number_format($order['total_amount'], 2),
            ucfirst($order['status'])
        ]);
    }
    
    // Monthly breakdown for yearly reports
    if ($type == 'yearly') {
        fputcsv($output, []);
        fputcsv($output, ['MONTHLY BREAKDOWN FOR ' . $year]);
        fputcsv($output, ['Month', 'Orders', 'Revenue', 'Average Order Value']);
        
        $monthly_stmt = $pdo->prepare("
            SELECT 
                MONTHNAME(order_date) as month_name,
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value
            FROM orders 
            WHERE status = 'completed' AND YEAR(order_date) = ?
            GROUP BY MONTH(order_date), MONTHNAME(order_date)
            ORDER BY MONTH(order_date)
        ");
        $monthly_stmt->execute([$year]);
        $monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($monthly_data as $month) {
            fputcsv($output, [
                $month['month_name'],
                $month['total_orders'],
                '$' . number_format($month['total_revenue'], 2),
                '$' . number_format($month['avg_order_value'], 2)
            ]);
        }
    }
    
    fclose($output);
    exit;
}

function exportExcel($sales_data, $filename_suffix, $total_orders, $total_revenue, $avg_order_value, $type, $year) {
    global $pdo;
    
    $filename = 'Sales_Report_' . $filename_suffix . '_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Start Excel content
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body>';

    // Report Header
    echo '<h1>BrewMaster Coffee - Sales Report</h1>';
    echo '<h2>Report Period: ' . $filename_suffix . '</h2>';
    echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<br>';

    // Summary Table
    echo '<h3>Summary</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #D2691E; color: white; font-weight: bold;">';
    echo '<th>Total Orders</th><th>Total Revenue</th><th>Average Order Value</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>' . $total_orders . '</td>';
    echo '<td>$' . number_format($total_revenue, 2) . '</td>';
    echo '<td>$' . number_format($avg_order_value, 2) . '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<br><br>';

    // Detailed Sales Table
    echo '<h3>Detailed Sales Data</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #6B4423; color: white; font-weight: bold;">';
    echo '<th>Order ID</th>';
    echo '<th>Date</th>';
    echo '<th>Customer Name</th>';
    echo '<th>Customer Email</th>';
    echo '<th>Customer Phone</th>';
    echo '<th>Customer Address</th>';
    echo '<th>Customer City</th>';
    echo '<th>Items Ordered</th>';
    echo '<th>Total Quantity</th>';
    echo '<th>Order Amount</th>';
    echo '<th>Status</th>';
    echo '</tr>';

    foreach ($sales_data as $order) {
        echo '<tr>';
        echo '<td>#' . htmlspecialchars($order['order_id']) . '</td>';
        echo '<td>' . htmlspecialchars($order['order_date']) . '</td>';
        echo '<td>' . htmlspecialchars($order['customer_name']) . '</td>';
        echo '<td>' . htmlspecialchars($order['customer_email']) . '</td>';
        echo '<td>' . htmlspecialchars($order['customer_phone'] ?: 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($order['customer_address'] ?: 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($order['customer_city'] ?: 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($order['order_items']) . '</td>';
        echo '<td>' . htmlspecialchars($order['total_quantity']) . '</td>';
        echo '<td>$' . number_format($order['total_amount'], 2) . '</td>';
        echo '<td>' . ucfirst(htmlspecialchars($order['status'])) . '</td>';
        echo '</tr>';
    }

    echo '</table>';

    // If yearly report, add monthly breakdown
    if ($type == 'yearly') {
        echo '<br><br>';
        echo '<h3>Monthly Breakdown for ' . $year . '</h3>';
        
        // Get monthly summary
        $monthly_stmt = $pdo->prepare("
            SELECT 
                MONTH(order_date) as month,
                MONTHNAME(order_date) as month_name,
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value
            FROM orders 
            WHERE status = 'completed' AND YEAR(order_date) = ?
            GROUP BY MONTH(order_date), MONTHNAME(order_date)
            ORDER BY MONTH(order_date)
        ");
        $monthly_stmt->execute([$year]);
        $monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr style="background-color: #D2691E; color: white; font-weight: bold;">';
        echo '<th>Month</th><th>Orders</th><th>Revenue</th><th>Average Order Value</th>';
        echo '</tr>';
        
        foreach ($monthly_data as $month) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($month['month_name']) . '</td>';
            echo '<td>' . htmlspecialchars($month['total_orders']) . '</td>';
            echo '<td>$' . number_format($month['total_revenue'], 2) . '</td>';
            echo '<td>$' . number_format($month['avg_order_value'], 2) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }

    // Footer
    echo '<br><br>';
    echo '<p><em>Report generated by BrewMaster Coffee Admin System</em></p>';
    echo '</body></html>';
    exit;
}
?>