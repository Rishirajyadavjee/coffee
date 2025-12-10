<?php
require_once 'config.php';

$error = ''; 

if ($_POST) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];

        // ✅ Redirect based on is_admin flag
        if ($user['is_admin'] == 1) {
            redirect('admin_dashboard.php');
        } 
    } else {
        $_SESSION['login_error'] = 'Invalid username or password';
        redirect('login.php');
    }
}

?>