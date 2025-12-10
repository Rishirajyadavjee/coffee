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
   

    <div class="login-container">
        <div class="logo">
            <i class="fas fa-coffee"></i> BrewMaster
        </div>
        
        <?php 
        session_start();
        $error = $_SESSION['login_error'] ?? ''; 
        unset($_SESSION['login_error']);
        if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" name="login" id="login" action="logincheck.php" >
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" value="admin" name="username" 
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" value="123456" name="password" 
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

       
    </div>
</body>
</html>