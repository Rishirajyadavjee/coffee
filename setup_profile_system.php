<?php
// setup_profile_system.php
// Run this file once to update your database with profile fields

require_once 'config.php';

echo "<h2>Setting up Profile System...</h2>";

try {
    // Check if columns already exist
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $columnsToAdd = [];
    
    if (!in_array('first_name', $columns)) {
        $columnsToAdd[] = "ADD COLUMN first_name VARCHAR(100) DEFAULT ''";
    }
    
    if (!in_array('last_name', $columns)) {
        $columnsToAdd[] = "ADD COLUMN last_name VARCHAR(100) DEFAULT ''";
    }
    
    if (!in_array('phone', $columns)) {
        $columnsToAdd[] = "ADD COLUMN phone VARCHAR(20) DEFAULT ''";
    }
    
    if (!in_array('address', $columns)) {
        $columnsToAdd[] = "ADD COLUMN address TEXT DEFAULT ''";
    }
    
    if (!in_array('city', $columns)) {
        $columnsToAdd[] = "ADD COLUMN city VARCHAR(100) DEFAULT ''";
    }
    
    if (!in_array('is_active', $columns)) {
        $columnsToAdd[] = "ADD COLUMN is_active TINYINT(1) DEFAULT 1";
    }
    
    // Check products table for visible column
    $stmt = $pdo->query("DESCRIBE products");
    $product_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('visible', $product_columns)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN visible TINYINT(1) DEFAULT 1");
        echo "<p style='color: green;'>✓ Added visible column to products table!</p>";
    }
    
    // Create contact_messages table
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p style='color: green;'>✓ Contact messages table created!</p>";
    
    // Create feedback table
    $pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(100) NOT NULL,
        customer_email VARCHAR(100) NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        message TEXT NOT NULL,
        service_type VARCHAR(100) DEFAULT 'Overall Experience',
        status VARCHAR(20) DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p style='color: green;'>✓ Feedback table created!</p>";
    
    // Create restaurant tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS restaurant_tables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        table_number VARCHAR(10) NOT NULL UNIQUE,
        table_name VARCHAR(50) NOT NULL,
        capacity INT NOT NULL,
        location VARCHAR(50) NOT NULL,
        is_available TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p style='color: green;'>✓ Restaurant tables table created!</p>";
    
    // Insert sample tables if not exists
    $existing_tables = $pdo->query("SELECT COUNT(*) FROM restaurant_tables")->fetchColumn();
    if ($existing_tables == 0) {
        $pdo->exec("INSERT INTO restaurant_tables (table_number, table_name, capacity, location, is_available) VALUES
            ('T001', 'Window Table 1', 2, 'Window Side', 1),
            ('T002', 'Window Table 2', 2, 'Window Side', 1),
            ('T003', 'Corner Cozy', 4, 'Corner', 1),
            ('T004', 'Central Table 1', 4, 'Center', 1),
            ('T005', 'Central Table 2', 4, 'Center', 1),
            ('T006', 'Private Booth 1', 6, 'Private Area', 1),
            ('T007', 'Private Booth 2', 6, 'Private Area', 1),
            ('T008', 'Patio Table 1', 4, 'Outdoor Patio', 1),
            ('T009', 'Patio Table 2', 4, 'Outdoor Patio', 1),
            ('T010', 'Bar Counter', 8, 'Bar Area', 1)");
        echo "<p style='color: green;'>✓ Sample restaurant tables inserted!</p>";
    }
    
    // Add new columns to bookings table if they don't exist
    $stmt = $pdo->query("DESCRIBE bookings");
    $booking_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('table_id', $booking_columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN table_id INT DEFAULT NULL");
        echo "<p style='color: green;'>✓ Added table_id column to bookings!</p>";
    }
    
    if (!in_array('user_id', $booking_columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN user_id INT DEFAULT NULL");
        echo "<p style='color: green;'>✓ Added user_id column to bookings!</p>";
    }
    
    if (!in_array('booking_time', $booking_columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN booking_time TIME DEFAULT NULL");
        echo "<p style='color: green;'>✓ Added booking_time column to bookings!</p>";
    }
    
    if (!in_array('party_size', $booking_columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN party_size INT DEFAULT 2");
        echo "<p style='color: green;'>✓ Added party_size column to bookings!</p>";
    }
    
    if (!in_array('booked_by_user_id', $booking_columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN booked_by_user_id INT DEFAULT NULL");
        echo "<p style='color: green;'>✓ Added booked_by_user_id column to bookings!</p>";
    }
    
    if (!in_array('guest_name', $booking_columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN guest_name VARCHAR(100) DEFAULT NULL");
        echo "<p style='color: green;'>✓ Added guest_name column to bookings!</p>";
    }
    
    if (!in_array('guest_email', $booking_columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN guest_email VARCHAR(100) DEFAULT NULL");
        echo "<p style='color: green;'>✓ Added guest_email column to bookings!</p>";
    }
    
    if (!in_array('guest_phone', $booking_columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN guest_phone VARCHAR(20) DEFAULT NULL");
        echo "<p style='color: green;'>✓ Added guest_phone column to bookings!</p>";
    }
    
    if (!in_array('booking_type', $booking_columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN booking_type ENUM('self', 'guest') DEFAULT 'self'");
        echo "<p style='color: green;'>✓ Added booking_type column to bookings!</p>";
    }
    
    if (!empty($columnsToAdd)) {
        $sql = "ALTER TABLE users " . implode(', ', $columnsToAdd);
        $pdo->exec($sql);
        echo "<p style='color: green;'>✓ Successfully added profile columns to users table!</p>";
    } else {
        echo "<p style='color: blue;'>✓ All profile columns already exist in users table!</p>";
    }
    
    echo "<p style='color: green;'>✓ Profile system setup complete!</p>";
    echo "<p><strong>You can now:</strong></p>";
    echo "<ul>";
    echo "<li>Register new users with profile information</li>";
    echo "<li>Edit existing user profiles</li>";
    echo "<li>Auto-fill checkout forms with user data</li>";
    echo "</ul>";
    echo "<p><a href='register.php'>Test Registration</a> | <a href='profile.php'>View Profile</a> | <a href='index.php'>Go to Homepage</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure your database connection is working and you have the necessary permissions.</p>";
}
?>