<?php
// contact.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));
    
    // Basic validation
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Use PHPMailer to send email
        require 'phpmailer/Exception.php';
        require 'phpmailer/PHPMailer.php';
        require 'phpmailer/SMTP.php';
        
        $mail = new PHPMailer(true);
        
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'testyadav1002@gmail.com';
            $mail->Password   = 'etah nwhj ugpv bvuj ';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            //Recipients
            $mail->setFrom('testyadav1002@gmail.com', 'Contact Form');
            $mail->addAddress('testyadav1002@gmail.com', 'Coffee Shop Website');
            $mail->addReplyTo($email, $name);

            //Content
            $mail->isHTML(true);
            $mail->Subject = 'Contact Form Submission from ' . $name;
            $mail->Body    = "Sender Name: $name<br>Sender Email: $email<br><br>Message:<br>$message";

            $mail->send();
            $success = true;
        } catch (Exception $e) {
            $error = "Failed to send message. Error: {$mail->ErrorInfo}";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Coffee Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #6B4423 0%, #3E2723 50%, #1B0F0A 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.05" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            pointer-events: none;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.98);
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            max-width: 600px;
            width: 100%;
            position: relative;
            animation: slideUp 0.6s ease-out;
            backdrop-filter: blur(10px);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .coffee-icon {
            font-size: 50px;
            color: #6B4423;
            margin-bottom: 15px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        h1 {
            color: #3E2723;
            margin-bottom: 10px;
            font-size: 32px;
            font-weight: 700;
        }
        
        .subtitle {
            color: #6B4423;
            font-size: 14px;
            font-weight: 400;
        }
        
        .contact-info {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);
            border-radius: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #3E2723;
            font-size: 14px;
        }
        
        .info-item i {
            color: #6B4423;
            font-size: 18px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #3E2723;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        label i {
            color: #6B4423;
        }
        
        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #E0E0E0;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #FAFAFA;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus {
            outline: none;
            border-color: #6B4423;
            background: white;
            box-shadow: 0 5px 15px rgba(107, 68, 35, 0.1);
            transform: translateY(-2px);
        }
        
        textarea {
            resize: vertical;
            min-height: 140px;
        }
        
        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #6B4423 0%, #8D6E63 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(107, 68, 35, 0.3);
        }
        
        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(107, 68, 35, 0.4);
            background: linear-gradient(135deg, #8D6E63 0%, #6B4423 100%);
        }
        
        button:active {
            transform: translateY(-1px);
        }
        
        .success {
            background: linear-gradient(135deg, #C8E6C9 0%, #A5D6A7 100%);
            color: #1B5E20;
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 5px solid #4CAF50;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.5s ease-out;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.2);
        }
        
        .success i {
            font-size: 24px;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error {
            background: linear-gradient(135deg, #FFCDD2 0%, #EF9A9A 100%);
            color: #B71C1C;
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 5px solid #F44336;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.5s ease-out;
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.2);
        }
        
        .error i {
            font-size: 24px;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .back-link a {
            color: #6B4423;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            color: #3E2723;
            gap: 12px;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 35px 25px;
            }
            
            h1 {
                font-size: 26px;
            }
            
            .contact-info {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="coffee-icon">
                <i class="fas fa-mug-hot"></i>
            </div>
            <h1>Get In Touch</h1>
            <p class="subtitle">We'd love to hear from you! Drop us a message.</p>
        </div>
        
        <div class="contact-info">
            <div class="info-item">
                <i class="fas fa-phone"></i>
                <span>8780307902</span>
            </div>
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <span>Testyadav1000@gmail.com</span>
            </div>
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <span>123 Coffee Street</span>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Success!</strong> Thank you for your message! We'll get back to you soon.
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Oops!</strong> <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">
                    <i class="fas fa-user"></i>
                    Your Name
                </label>
                <input type="text" id="name" name="name" placeholder="Enter your full name" value="<?php echo $_POST['name'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i>
                    Email Address
                </label>
                <input type="email" id="email" name="email" placeholder="your.email@example.com" value="<?php echo $_POST['email'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="message">
                    <i class="fas fa-comment-dots"></i>
                    Your Message
                </label>
                <textarea id="message" name="message" placeholder="Tell us what's on your mind..." required><?php echo $_POST['message'] ?? ''; ?></textarea>
            </div>
            
            <button type="submit" name="send">
                <i class="fas fa-paper-plane"></i>
                Send Message
            </button>
        </form>
        
        <div class="back-link">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>
