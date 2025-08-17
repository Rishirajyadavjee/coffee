<?php
session_start();

//$host = 'sql301.infinityfree.com';
//$dbname = 'if0_39724676_coffee';
//$username = 'if0_39724676'; // Change as needed
//$password = 'GpTGBtzzGI';     // Change as needed

$host = 'localhost';                        
$dbname = 'coffee_shop';                      
$username = 'root'; // Change as needed       
$password = '';     // Change as needed     

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>