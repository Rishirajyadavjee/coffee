<?php
require_once 'config.php';

if ($_POST) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
    $message = isset($_POST['message']) ? sanitize($_POST['message']) : '';
    $booking_date = $_POST['booking_date'];
    
    $stmt = $pdo->prepare("INSERT INTO bookings (name, email, phone, message, booking_date) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$name, $email, $phone, $message, $booking_date])) {
        $success = true;
    } else {
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Booking - BrewMaster Coffee</title>
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

        .booking-container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
        }

        .booking-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(45deg, #D2691E, #FF8C00);
        }

        .success-message {
            text-align: center;
            color: #28a745;
        }

        .success-message i {
            font-size: 4rem;
            margin-bottom: 2rem;
        }

        .success-message h2 {
            color: #6B4423;
            margin-bottom: 1rem;
        }

        .success-message p {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(45deg, #D2691E, #FF8C00);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(210, 105, 30, 0.4);
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <?php if (isset($success)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <h2>Booking Confirmed!</h2>
                <p>Thank you for your reservation. We've received your booking request and will contact you shortly to confirm the details.</p>
                <a href="index.php" class="btn">Return to Home</a>
            </div>
        <?php elseif (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                Sorry, there was an error processing your booking. Please try again.
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 3000);
            </script>
        <?php else: ?>
            <script>
                window.location.href = 'index.php';
            </script>
        <?php endif; ?>
    </div>
</body>
</html>