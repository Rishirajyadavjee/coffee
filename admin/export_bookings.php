<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    die('Access denied');
}

$format = $_GET['format'] ?? 'excel';

// Get filtered bookings data
$where = "WHERE 1=1";
$params = [];

$booking_status_filter = $_GET['booking_status_filter'] ?? 'all';
$booking_start_date = $_GET['booking_start_date'] ?? '';
$booking_end_date = $_GET['booking_end_date'] ?? '';

if ($booking_status_filter != 'all') {
    $where .= " AND status = ?";
    $params[] = $booking_status_filter;
}

if ($booking_start_date) {
    $where .= " AND DATE(created_at) >= ?";
    $params[] = $booking_start_date;
}

if ($booking_end_date) {
    $where .= " AND DATE(created_at) <= ?";
    $params[] = $booking_end_date;
}

$stmt = $pdo->prepare("
    SELECT 
        b.id,
        b.name,
        b.email,
        b.phone,
        b.message,
        b.booking_date,
        b.booking_time,
        b.party_size,
        b.booking_type,
        b.status,
        b.created_at,
        rt.table_name,
        rt.table_number,
        rt.location,
        CONCAT(booker.first_name, ' ', booker.last_name) as booked_by_name,
        booker.email as booked_by_email
    FROM bookings b
    LEFT JOIN restaurant_tables rt ON b.table_id = rt.id
    LEFT JOIN users booker ON b.booked_by_user_id = booker.id
    $where
    ORDER BY b.created_at DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_bookings = count($bookings);
$pending_count = count(array_filter($bookings, function($b) { return $b['status'] == 'pending'; }));
$confirmed_count = count(array_filter($bookings, function($b) { return $b['status'] == 'confirmed'; }));
$cancelled_count = count(array_filter($bookings, function($b) { return $b['status'] == 'cancelled'; }));

if ($format == 'csv') {
    exportCSV($bookings, $total_bookings, $pending_count, $confirmed_count, $cancelled_count);
} else {
    exportExcel($bookings, $total_bookings, $pending_count, $confirmed_count, $cancelled_count);
}

function exportCSV($bookings, $total_bookings, $pending_count, $confirmed_count, $cancelled_count) {
    $filename = 'Table_Bookings_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Report header
    fputcsv($output, ['BrewMaster Coffee - Table Bookings Report']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Summary
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Bookings', 'Pending', 'Confirmed', 'Cancelled']);
    fputcsv($output, [$total_bookings, $pending_count, $confirmed_count, $cancelled_count]);
    fputcsv($output, []);
    
    // Headers for detailed data
    fputcsv($output, ['DETAILED BOOKINGS DATA']);
    
    if (empty($bookings)) {
        fputcsv($output, ['No booking data found for the selected criteria.']);
    } else {
        fputcsv($output, [
            'Booking ID', 'Customer Name', 'Email', 'Phone', 'Booking Date', 'Booking Time',
            'Party Size', 'Table', 'Location', 'Booking Type', 'Booked By', 'Booked By Email',
            'Message', 'Status', 'Submitted Date'
        ]);
        
        // Data rows
        foreach ($bookings as $booking) {
            fputcsv($output, [
                '#' . $booking['id'],
                $booking['name'],
                $booking['email'],
                $booking['phone'] ?: 'N/A',
                $booking['booking_date'] ? date('Y-m-d', strtotime($booking['booking_date'])) : 'Not specified',
                $booking['booking_time'] ? date('g:i A', strtotime($booking['booking_time'])) : 'N/A',
                $booking['party_size'] ?: 'N/A',
                $booking['table_name'] ? $booking['table_name'] . ' (' . $booking['table_number'] . ')' : 'N/A',
                $booking['location'] ?: 'N/A',
                ucfirst($booking['booking_type'] ?: 'self'),
                $booking['booked_by_name'] ?: 'N/A',
                $booking['booked_by_email'] ?: 'N/A',
                $booking['message'] ?: 'No message',
                ucfirst($booking['status']),
                date('Y-m-d H:i:s', strtotime($booking['created_at']))
            ]);
        }
    }
    
    fclose($output);
    exit;
}

function exportExcel($bookings, $total_bookings, $pending_count, $confirmed_count, $cancelled_count) {
    $filename = 'Table_Bookings_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Start Excel content
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body>';

    // Report Header
    echo '<h1>BrewMaster Coffee - Table Bookings Report</h1>';
    echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<br>';

    // Summary Table
    echo '<h3>Summary</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #D2691E; color: white; font-weight: bold;">';
    echo '<th>Total Bookings</th><th>Pending</th><th>Confirmed</th><th>Cancelled</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>' . $total_bookings . '</td>';
    echo '<td>' . $pending_count . '</td>';
    echo '<td>' . $confirmed_count . '</td>';
    echo '<td>' . $cancelled_count . '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<br><br>';

    // Detailed Bookings Table
    echo '<h3>Detailed Bookings Data</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #6B4423; color: white; font-weight: bold;">';
    echo '<th>Booking ID</th>';
    echo '<th>Customer Name</th>';
    echo '<th>Email</th>';
    echo '<th>Phone</th>';
    echo '<th>Booking Date</th>';
    echo '<th>Booking Time</th>';
    echo '<th>Party Size</th>';
    echo '<th>Table</th>';
    echo '<th>Location</th>';
    echo '<th>Booking Type</th>';
    echo '<th>Booked By</th>';
    echo '<th>Booked By Email</th>';
    echo '<th>Message</th>';
    echo '<th>Status</th>';
    echo '<th>Submitted Date</th>';
    echo '</tr>';

    foreach ($bookings as $booking) {
        echo '<tr>';
        echo '<td>#' . htmlspecialchars($booking['id']) . '</td>';
        echo '<td>' . htmlspecialchars($booking['name']) . '</td>';
        echo '<td>' . htmlspecialchars($booking['email']) . '</td>';
        echo '<td>' . htmlspecialchars($booking['phone'] ?: 'N/A') . '</td>';
        echo '<td>' . ($booking['booking_date'] ? date('Y-m-d', strtotime($booking['booking_date'])) : 'Not specified') . '</td>';
        echo '<td>' . ($booking['booking_time'] ? date('g:i A', strtotime($booking['booking_time'])) : 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($booking['party_size'] ?: 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($booking['table_name'] ? $booking['table_name'] . ' (' . $booking['table_number'] . ')' : 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($booking['location'] ?: 'N/A') . '</td>';
        echo '<td>' . ucfirst(htmlspecialchars($booking['booking_type'] ?: 'self')) . '</td>';
        echo '<td>' . htmlspecialchars($booking['booked_by_name'] ?: 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($booking['booked_by_email'] ?: 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($booking['message'] ?: 'No message') . '</td>';
        echo '<td>' . ucfirst(htmlspecialchars($booking['status'])) . '</td>';
        echo '<td>' . date('Y-m-d H:i:s', strtotime($booking['created_at'])) . '</td>';
        echo '</tr>';
    }

    echo '</table>';

    // Status breakdown
    echo '<br><br>';
    echo '<h3>Status Breakdown</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #D2691E; color: white; font-weight: bold;">';
    echo '<th>Status</th><th>Count</th><th>Percentage</th>';
    echo '</tr>';
    
    $statuses = [
        'Pending' => $pending_count,
        'Confirmed' => $confirmed_count,
        'Cancelled' => $cancelled_count
    ];
    
    foreach ($statuses as $status => $count) {
        $percentage = $total_bookings > 0 ? round(($count / $total_bookings) * 100, 1) : 0;
        echo '<tr>';
        echo '<td>' . $status . '</td>';
        echo '<td>' . $count . '</td>';
        echo '<td>' . $percentage . '%</td>';
        echo '</tr>';
    }
    
    echo '</table>';

    // Footer
    echo '<br><br>';
    echo '<p><em>Report generated by BrewMaster Coffee Admin System</em></p>';
    echo '</body></html>';
    exit;
}
?>