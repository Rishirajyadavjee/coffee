<?php
require_once 'config.php';

// This is a simple test page to verify user activation/deactivation functionality
// Only admins should be able to access this page

if (!isLoggedIn() || !isAdmin()) {
    die('Access denied. Admin only.');
}

echo "<h2>User Status Test Page</h2>";

// Get all users with their status
$stmt = $pdo->query("SELECT id, username, first_name, last_name, email, is_active, is_admin FROM users ORDER BY id");
$users = $stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Active</th><th>Admin</th><th>Actions</th>";
echo "</tr>";

foreach ($users as $user) {
    $activeStatus = $user['is_active'] ?? 1;
    $statusColor = $activeStatus ? 'green' : 'red';
    $statusText = $activeStatus ? 'Active' : 'Inactive';
    
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['username']}</td>";
    echo "<td>{$user['first_name']} {$user['last_name']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td style='color: {$statusColor}; font-weight: bold;'>{$statusText}</td>";
    echo "<td>" . ($user['is_admin'] ? 'Yes' : 'No') . "</td>";
    echo "<td>";
    
    if ($user['id'] != $_SESSION['user_id']) {
        $toggleAction = $activeStatus ? 'Deactivate' : 'Activate';
        $toggleColor = $activeStatus ? 'orange' : 'green';
        echo "<a href='?toggle_user={$user['id']}' style='color: {$toggleColor}; margin-right: 10px;'>{$toggleAction}</a>";
    } else {
        echo "<em>Current User</em>";
    }
    
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Handle toggle action
if (isset($_GET['toggle_user'])) {
    $user_id = intval($_GET['toggle_user']);
    
    if ($user_id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            echo "<p style='color: green; font-weight: bold;'>User status updated successfully!</p>";
            echo "<script>setTimeout(function(){ window.location.href = 'test_user_status.php'; }, 1000);</script>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>Failed to update user status.</p>";
        }
    }
}

echo "<br><br>";
echo "<p><strong>Instructions:</strong></p>";
echo "<ul>";
echo "<li>Click 'Deactivate' to disable a user account</li>";
echo "<li>Click 'Activate' to enable a user account</li>";
echo "<li>Deactivated users cannot log in</li>";
echo "<li>You cannot deactivate your own account</li>";
echo "</ul>";

echo "<p><a href='admin/admin_dashboard.php'>← Back to Admin Dashboard</a></p>";
?>