<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    die('Access denied');
}

$type = $_GET['type'] ?? 'contact';
$format = $_GET['format'] ?? 'excel';

if ($type == 'contact') {
    exportContactMessages($format);
} elseif ($type == 'feedback') {
    exportFeedback($format);
} else {
    die('Invalid export type');
}

function exportContactMessages($format) {
    global $pdo;
    
    // Get filtered contact messages
    $where = "WHERE 1=1";
    $params = [];
    
    $message_status_filter = $_GET['message_status_filter'] ?? 'all';
    $message_start_date = $_GET['message_start_date'] ?? '';
    $message_end_date = $_GET['message_end_date'] ?? '';
    
    if ($message_status_filter != 'all') {
        $where .= " AND status = ?";
        $params[] = $message_status_filter;
    }
    
    if ($message_start_date) {
        $where .= " AND DATE(created_at) >= ?";
        $params[] = $message_start_date;
    }
    
    if ($message_end_date) {
        $where .= " AND DATE(created_at) <= ?";
        $params[] = $message_end_date;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            email,
            subject,
            message,
            status,
            created_at
        FROM contact_messages 
        $where
        ORDER BY created_at DESC
    ");
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $total_messages = count($messages);
    $unread_count = count(array_filter($messages, function($m) { return $m['status'] == 'unread'; }));
    $read_count = count(array_filter($messages, function($m) { return $m['status'] == 'read'; }));
    $replied_count = count(array_filter($messages, function($m) { return $m['status'] == 'replied'; }));
    
    if ($format == 'csv') {
        exportContactCSV($messages, $total_messages, $unread_count, $read_count, $replied_count);
    } else {
        exportContactExcel($messages, $total_messages, $unread_count, $read_count, $replied_count);
    }
}

function exportFeedback($format) {
    global $pdo;
    
    // Get filtered feedback
    $where = "WHERE 1=1";
    $params = [];
    
    $feedback_status_filter = $_GET['feedback_status_filter'] ?? 'all';
    $feedback_rating_filter = $_GET['feedback_rating_filter'] ?? 'all';
    $feedback_start_date = $_GET['feedback_start_date'] ?? '';
    $feedback_end_date = $_GET['feedback_end_date'] ?? '';
    
    if ($feedback_status_filter != 'all') {
        $where .= " AND status = ?";
        $params[] = $feedback_status_filter;
    }
    
    if ($feedback_rating_filter != 'all') {
        $where .= " AND rating = ?";
        $params[] = $feedback_rating_filter;
    }
    
    if ($feedback_start_date) {
        $where .= " AND DATE(created_at) >= ?";
        $params[] = $feedback_start_date;
    }
    
    if ($feedback_end_date) {
        $where .= " AND DATE(created_at) <= ?";
        $params[] = $feedback_end_date;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            customer_name,
            customer_email,
            rating,
            message,
            service_type,
            status,
            created_at
        FROM feedback 
        $where
        ORDER BY created_at DESC
    ");
    $stmt->execute($params);
    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $total_feedback = count($feedback);
    $avg_rating = $pdo->query("SELECT AVG(rating) FROM feedback")->fetchColumn() ?: 0;
    $rating_distribution = [];
    for ($i = 1; $i <= 5; $i++) {
        $rating_distribution[$i] = count(array_filter($feedback, function($f) use ($i) { return $f['rating'] == $i; }));
    }
    
    if ($format == 'csv') {
        exportFeedbackCSV($feedback, $total_feedback, $avg_rating, $rating_distribution);
    } else {
        exportFeedbackExcel($feedback, $total_feedback, $avg_rating, $rating_distribution);
    }
}

function exportContactCSV($messages, $total_messages, $unread_count, $read_count, $replied_count) {
    $filename = 'Contact_Messages_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Report header
    fputcsv($output, ['BrewMaster Coffee - Contact Messages Report']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Summary
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Messages', 'Unread', 'Read', 'Replied']);
    fputcsv($output, [$total_messages, $unread_count, $read_count, $replied_count]);
    fputcsv($output, []);
    
    // Headers for detailed data
    fputcsv($output, ['DETAILED MESSAGES DATA']);
    
    if (empty($messages)) {
        fputcsv($output, ['No contact messages found for the selected criteria.']);
    } else {
        fputcsv($output, [
            'Message ID', 'Name', 'Email', 'Subject', 'Message', 'Status', 'Received Date'
        ]);
        
        // Data rows
        foreach ($messages as $message) {
            fputcsv($output, [
                '#' . $message['id'],
                $message['name'],
                $message['email'],
                $message['subject'],
                $message['message'],
                ucfirst($message['status']),
                date('Y-m-d H:i:s', strtotime($message['created_at']))
            ]);
        }
    }
    
    fclose($output);
    exit;
}

function exportContactExcel($messages, $total_messages, $unread_count, $read_count, $replied_count) {
    $filename = 'Contact_Messages_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Start Excel content
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body>';

    // Report Header
    echo '<h1>BrewMaster Coffee - Contact Messages Report</h1>';
    echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<br>';

    // Summary Table
    echo '<h3>Summary</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #D2691E; color: white; font-weight: bold;">';
    echo '<th>Total Messages</th><th>Unread</th><th>Read</th><th>Replied</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>' . $total_messages . '</td>';
    echo '<td>' . $unread_count . '</td>';
    echo '<td>' . $read_count . '</td>';
    echo '<td>' . $replied_count . '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<br><br>';

    // Detailed Messages Table
    echo '<h3>Detailed Messages Data</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #6B4423; color: white; font-weight: bold;">';
    echo '<th>Message ID</th>';
    echo '<th>Name</th>';
    echo '<th>Email</th>';
    echo '<th>Subject</th>';
    echo '<th>Message</th>';
    echo '<th>Status</th>';
    echo '<th>Received Date</th>';
    echo '</tr>';

    foreach ($messages as $message) {
        echo '<tr>';
        echo '<td>#' . htmlspecialchars($message['id']) . '</td>';
        echo '<td>' . htmlspecialchars($message['name']) . '</td>';
        echo '<td>' . htmlspecialchars($message['email']) . '</td>';
        echo '<td>' . htmlspecialchars($message['subject']) . '</td>';
        echo '<td>' . htmlspecialchars($message['message']) . '</td>';
        echo '<td>' . ucfirst(htmlspecialchars($message['status'])) . '</td>';
        echo '<td>' . date('Y-m-d H:i:s', strtotime($message['created_at'])) . '</td>';
        echo '</tr>';
    }

    echo '</table>';

    // Footer
    echo '<br><br>';
    echo '<p><em>Report generated by BrewMaster Coffee Admin System</em></p>';
    echo '</body></html>';
    exit;
}

function exportFeedbackCSV($feedback, $total_feedback, $avg_rating, $rating_distribution) {
    $filename = 'Customer_Feedback_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Report header
    fputcsv($output, ['BrewMaster Coffee - Customer Feedback Report']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Summary
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Feedback', 'Average Rating']);
    fputcsv($output, [$total_feedback, number_format($avg_rating, 2)]);
    fputcsv($output, []);
    
    // Rating distribution
    fputcsv($output, ['RATING DISTRIBUTION']);
    fputcsv($output, ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star']);
    fputcsv($output, [
        $rating_distribution[5], $rating_distribution[4], $rating_distribution[3], 
        $rating_distribution[2], $rating_distribution[1]
    ]);
    fputcsv($output, []);
    
    // Headers for detailed data
    fputcsv($output, ['DETAILED FEEDBACK DATA']);
    fputcsv($output, [
        'Feedback ID', 'Customer Name', 'Email', 'Rating', 'Service Type', 'Feedback Message', 'Status', 'Submitted Date'
    ]);
    
    // Data rows
    foreach ($feedback as $fb) {
        fputcsv($output, [
            '#' . $fb['id'],
            $fb['customer_name'],
            $fb['customer_email'],
            $fb['rating'] . '/5',
            $fb['service_type'],
            $fb['message'],
            ucfirst($fb['status']),
            date('Y-m-d H:i:s', strtotime($fb['created_at']))
        ]);
    }
    
    fclose($output);
    exit;
}

function exportFeedbackExcel($feedback, $total_feedback, $avg_rating, $rating_distribution) {
    $filename = 'Customer_Feedback_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Start Excel content
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body>';

    // Report Header
    echo '<h1>BrewMaster Coffee - Customer Feedback Report</h1>';
    echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<br>';

    // Summary Table
    echo '<h3>Summary</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #D2691E; color: white; font-weight: bold;">';
    echo '<th>Total Feedback</th><th>Average Rating</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>' . $total_feedback . '</td>';
    echo '<td>' . number_format($avg_rating, 2) . '/5</td>';
    echo '</tr>';
    echo '</table>';
    echo '<br>';

    // Rating Distribution
    echo '<h3>Rating Distribution</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #D2691E; color: white; font-weight: bold;">';
    echo '<th>5 Stars</th><th>4 Stars</th><th>3 Stars</th><th>2 Stars</th><th>1 Star</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>' . $rating_distribution[5] . '</td>';
    echo '<td>' . $rating_distribution[4] . '</td>';
    echo '<td>' . $rating_distribution[3] . '</td>';
    echo '<td>' . $rating_distribution[2] . '</td>';
    echo '<td>' . $rating_distribution[1] . '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<br><br>';

    // Detailed Feedback Table
    echo '<h3>Detailed Feedback Data</h3>';
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr style="background-color: #6B4423; color: white; font-weight: bold;">';
    echo '<th>Feedback ID</th>';
    echo '<th>Customer Name</th>';
    echo '<th>Email</th>';
    echo '<th>Rating</th>';
    echo '<th>Service Type</th>';
    echo '<th>Feedback Message</th>';
    echo '<th>Status</th>';
    echo '<th>Submitted Date</th>';
    echo '</tr>';

    foreach ($feedback as $fb) {
        echo '<tr>';
        echo '<td>#' . htmlspecialchars($fb['id']) . '</td>';
        echo '<td>' . htmlspecialchars($fb['customer_name']) . '</td>';
        echo '<td>' . htmlspecialchars($fb['customer_email']) . '</td>';
        echo '<td>' . $fb['rating'] . '/5</td>';
        echo '<td>' . htmlspecialchars($fb['service_type']) . '</td>';
        echo '<td>' . htmlspecialchars($fb['message']) . '</td>';
        echo '<td>' . ucfirst(htmlspecialchars($fb['status'])) . '</td>';
        echo '<td>' . date('Y-m-d H:i:s', strtotime($fb['created_at'])) . '</td>';
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