<?php
include_once 'config.php';

$error = ''; 
$message = $_GET['message'] ?? '';

if ($_POST) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Check if user account is active
        $is_active = $user['is_active'] ?? 1; // Default to active if column doesn't exist yet
        
        if (!$is_active) {
            $error = 'Your account has been deactivated. Please contact support for assistance.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];

            // Check for redirect after login
            $redirect_url = $_SESSION['redirect_after_login'] ?? '';
            unset($_SESSION['redirect_after_login']);
            
            // ✅ Redirect based on is_admin flag or redirect URL
            if ($user['is_admin'] == 1) {
                redirect('admin.php');
            } elseif ($redirect_url) {
                redirect($redirect_url);
            } else {
                redirect('dashboard.php');
            }
        }
    } else {
        $error = 'Invalid username or password';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BrewMaster Coffee</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #6B4423, #8B4513);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(45deg, #D2691E, #FF8C00);
        }

        .logo {
            text-align: center;
            color: #6B4423;
            font-size: 2rem;
            margin-bottom: 2rem;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #6B4423;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #D2691E;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #D2691E, #FF8C00);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(210, 105, 30, 0.3);
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #c62828;
        }

        .links {
            text-align: center;
        }

        .links a {
            color: #D2691E;
            text-decoration: none;
            font-weight: bold;
            margin: 0 10px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            font-size: 1.5rem;
            text-decoration: none;
        }

        .back-home:hover {
            color: #D2691E;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="login-container">
        <div class="logo">
            <i class="fas fa-coffee"></i> BrewMaster
        </div>

        <?php if ($message): ?>
            <div class="info" style="background: #d1ecf1; color: #0c5460; padding: 12px; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #17a2b8;">
                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" value="rishi" id="username" name="username" 
                 pattern="^[A-Za-z]+$"
                placeholder="Enter your Username or Email"
                required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" value="123456" id="password" name="password" 
                placeholder="Enter Your password"
                required>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="links">
            <a href="register.php">Don't have an account? Register</a>
        </div>
    </div>
</body>
</html>