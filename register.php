<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_POST) {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (empty($first_name) || empty($last_name)) {
        $error = 'First name and last name are required';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Username or email already exists';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, first_name, last_name, phone, address, city, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $first_name, $last_name, $phone, $address, $city, $hashed_password])) {
                $success = 'Registration successful! login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BrewMaster Coffee</title>
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

        .register-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .register-container::before {
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

        .success {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #2e7d32;
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

    <div class="register-container">
        <div class="logo">
            <i class="fas fa-coffee"></i> BrewMaster
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name"
                 pattern="^[A-Za-z]+$"
            placeholder="Enter your first name"
                required>
            </div>
        
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" 
                 pattern="^[A-Za-z]+$"
                placeholder="Enter your last name"
                required>
            </div>

            <!-- <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div> -->

            <div class="form-group">
                <label for="username">Username:</label>
  <input type="text" id="username" name="username"
         pattern="^[A-Za-z._-]+$"
         maxlength="20"
         placeholder="Choose a username"
         required>
             </div>

            <!-- <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div> -->

            <div class="form-group">
            <label for="email">Email (Gmail Only):</label>
            <input type="email" id="email" name="email"
            pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$"
            placeholder="Enter your Gmail address"
            required>
            </form>


            <!-- <div class="form-group">
                <label for="phone">Phone (Optional)</label>
                <input type="tel" id="phone" name="phone">
            </div> -->

           <div class="form-group">
            <label for="phone">Phone Number:</label>
            <input type="tel" id="phone" name="phone" 
            pattern="^[6-9][0-9]{9}$" 
            maxlength="10" 
            placeholder="Enter 10-digit phone number" 
            required>  
        </form>



            <div class="form-group">
                <label for="address">Address (Optional)</label>
                <input type="text" id="address" name="address"
                placeholder="Enter the Address">
            </div>

            <div class="form-group">
                <label for="city">City (Optional)</label>
                <input type="text" id="city" name="city"
                placeholder="Enter the City">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                placeholder="Enter Your Password"
                required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                placeholder="Enter Your Confirm Password"
                required>
            </div>

            <button type="submit" class="btn">Register</button>
        </form>

        <div class="links">
            <a href="login.php">Already have an account? Login</a>
        </div>
    </div>
</body>
</html>