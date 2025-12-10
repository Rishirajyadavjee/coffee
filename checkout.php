<?php
            use PHPMailer\PHPMailer\PHPMailer;
            use PHPMailer\PHPMailer\Exception;
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user information for auto-filling
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.stock 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    redirect('cart.php');
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax = $subtotal * 0.08;
$shipping = $subtotal > 50 ? 0 : 5.99;
$total = $subtotal + $tax + $shipping;

$success = '';
$error = '';

// Handle payment processing
if ($_POST && isset($_POST['process_payment'])) {
    // Validate stock availability
    $stock_error = false;
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $stock_error = true;
            break;
        }
    }
    
    if ($stock_error) {
        $error = 'Some items in your cart are no longer available in the requested quantity.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Get customer email from form
            $customer_email = $_POST['email'] ?? '';
            $customer_name = ($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? '');
            
            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $total]);
            $order_id = $pdo->lastInsertId();
            
            // Add order items and update stock
            foreach ($cart_items as $item) {
                // Add to order_items
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update product stock
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $pdo->commit();
            
            // Send confirmation email to customer
            require 'phpmailer/Exception.php';
            require 'phpmailer/PHPMailer.php';
            require 'phpmailer/SMTP.php';
            
            
            
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'testyadav1002@gmail.com';
                $mail->Password   = 'etah nwhj ugpv bvuj ';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                // Recipients
                               $mail->setFrom('testyadav1002@gmail.com', 'BrewMaster Coffee Shop');
                $mail->addAddress($customer_email, $customer_name);   // Customer
                $mail->addBCC('testyadav1002@gmail.com');             // Aapko copy


                // Build order items HTML
                $items_html = '';
                foreach ($cart_items as $item) {
                    $item_total = $item['price'] * $item['quantity'];
                    $items_html .= "
                    <tr>
                        <td style='padding: 12px; border-bottom: 1px solid #eee;'>{$item['name']}</td>
                        <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                        <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: right;'>₹" . number_format($item['price'], 2) . "</td>
                        <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: right;'>₹" . number_format($item_total, 2) . "</td>
                    </tr>";
                }

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Order Confirmation - Order #' . $order_id;
                $mail->Body    = "
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #6B4423, #8B4513); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #fff; padding: 30px; border: 1px solid #ddd; }
                        .order-details { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
                        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
                        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        th { background: #6B4423; color: white; padding: 12px; text-align: left; }
                        .total-row { font-weight: bold; background: #fff3e0; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>☕ BrewMaster Coffee Shop</h1>
                            <p>Thank You for Your Order!</p>
                        </div>
                        
                        <div class='content'>
                            <h2>Hi {$customer_name},</h2>
                            <p>Thank you for your order! We're excited to prepare your coffee for you.</p>
                            
                            <div class='order-details'>
                                <h3>Order Details</h3>
                                <p><strong>Order Number:</strong> #{$order_id}</p>
                                <p><strong>Order Date:</strong> " . date('F d, Y') . "</p>
                                <p><strong>Status:</strong> Pending Confirmation</p>
                            </div>
                            
                            <h3>Order Items</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th style='text-align: center;'>Quantity</th>
                                        <th style='text-align: right;'>Price</th>
                                        <th style='text-align: right;'>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$items_html}
                                    <tr>
                                        <td colspan='3' style='padding: 12px; text-align: right;'><strong>Subtotal:</strong></td>
                                        <td style='padding: 12px; text-align: right;'>₹" . number_format($subtotal, 2) . "</td>
                                    </tr>
                                    <tr>
                                        <td colspan='3' style='padding: 12px; text-align: right;'><strong>Tax (8%):</strong></td>
                                        <td style='padding: 12px; text-align: right;'>₹" . number_format($tax, 2) . "</td>
                                    </tr>
                                    <tr>
                                        <td colspan='3' style='padding: 12px; text-align: right;'><strong>Shipping:</strong></td>
                                        <td style='padding: 12px; text-align: right;'>" . ($shipping == 0 ? 'FREE' : '₹' . number_format($shipping, 2)) . "</td>
                                    </tr>
                                    <tr class='total-row'>
                                        <td colspan='3' style='padding: 12px; text-align: right; font-size: 18px;'><strong>Total:</strong></td>
                                        <td style='padding: 12px; text-align: right; font-size: 18px; color: #6B4423;'><strong>₹" . number_format($total, 2) . "</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <p>Your order is currently being processed and will be confirmed shortly. You will receive another email once your order has been confirmed and shipped.</p>
                            
                            <p>If you have any questions about your order, please don't hesitate to contact us.</p>
                        </div>
                        
                        <div class='footer'>
                            <p><strong>BrewMaster Coffee Shop</strong></p>
                            <p>Email: testyadav1002@gmail.com | Phone: +1 (555) 123-4567</p>
                            <p>123 Coffee Street, Brew City</p>
                            <p style='margin-top: 15px; color: #666; font-size: 12px;'>This is an automated email. Please do not reply directly to this message.</p>
                        </div>
                    </div>
                </body>
                </html>";

                $mail->send();
                $success = 'Order placed successfully! Order ID: #' . $order_id . '. A confirmation email has been sent to ' . $customer_email;
                
            } catch (Exception $e) {
                // Order was successful but email failed
                $success = 'Order placed successfully! Order ID: #' . $order_id . '. However, we could not send the confirmation email. Please check your order in the dashboard.';
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Payment processing failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - BrewMaster Coffee</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            padding-top: 80px;
        }

        .navbar {
            background: linear-gradient(135deg, #6B4423, #8B4513);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            color: white;
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
            font-weight: 500;
        }

        .nav-links a:hover {
            color: #D2691E;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .checkout-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
        }

        .checkout-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: #6B4423;
            color: white;
            padding: 1.5rem;
            font-size: 1.3rem;
            font-weight: bold;
        }

        .section-content {
            padding: 2rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #6B4423;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #D2691E;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: bold;
            color: #6B4423;
        }

        .item-details {
            color: #666;
            font-size: 0.9rem;
        }

        .item-total {
            font-weight: bold;
            color: #D2691E;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .summary-row:last-child {
            border-bottom: 2px solid #6B4423;
            font-weight: bold;
            font-size: 1.2rem;
            color: #6B4423;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .payment-method {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method:hover,
        .payment-method.selected {
            border-color: #D2691E;
            background: #fff8f0;
        }

        .payment-method i {
            font-size: 2rem;
            color: #D2691E;
            margin-bottom: 0.5rem;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #D2691E, #FF8C00);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            text-align: center;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            margin-top: 2rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(210, 105, 30, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            margin-top: 1rem;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #28a745;
            text-align: center;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #dc3545;
        }

        .security-info {
            background: #e8f4fd;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #0c5460;
        }

        .security-info i {
            color: #0c5460;
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .checkout-container {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-coffee"></i> BrewMaster
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="gallery.php">Gallery</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="admin.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <?php if ($success): ?>
            <div class="success">
                <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <h3><?php echo $success; ?></h3>
                <p>Thank you for your order! Your order is pending confirmation and will be processed soon.</p>
                <a href="dashboard.php" class="btn" style="display: inline-block; width: auto; margin-top: 1rem;">View Orders</a>
                <a href="products.php" class="btn btn-secondary" style="display: inline-block; width: auto; margin-top: 1rem;">Continue Shopping</a>
            </div>
        <?php else: ?>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="checkout-container">
            <div class="checkout-section">
                <div class="section-header">
                    <i class="fas fa-credit-card"></i> Payment Information
                </div>
                <div class="section-content">
                    <form method="POST">
                        <h3 style="color: #6B4423; margin-bottom: 1rem;">Billing Address</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                             pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$"
            placeholder="Enter your Gmail address"
                            required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                             pattern="^[6-9][0-9]{9}$" 
            maxlength="10" 
            placeholder="Enter 10-digit phone number" 
            required> 
                        </div>

                        <div class="form-group">
                            <label for="address">Street Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="zip">ZIP Code</label>
                                <input type="text" id="zip" name="zip"
                                type="text"
                                maxlength="6"
                                placeholder="Enter 6-digit ZIP code"
                                required>
                                <script>
                                const zipInput = document.getElementById('zip');

                                zipInput.addEventListener('input', (e) => {
                                e.target.value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                                });
                                </script>
                             </div>
                        </div>

                        <h3 style="color: #6B4423; margin: 2rem 0 1rem 0;">Payment Method</h3>
                        
                        <div class="payment-methods">
                            <div class="payment-method selected" onclick="selectPayment(this)">
                                <i class="fas fa-credit-card"></i>
                                <div>Credit Card</div>
                            </div>
                            <div class="payment-method" onclick="selectPayment(this)">
                                <i class="fab fa-paypal"></i>
                                <div>PayPal</div>
                            </div>
                            <div class="payment-method" onclick="selectPayment(this)">
                                <i class="fab fa-apple-pay"></i>
                                <div>Apple Pay</div>
                            </div>
                        </div>

                        <div id="credit-card-form">
                            <div class="form-group">
                                <label for="card_number">Card Number</label>
                                <input type="text" id="card_number" name="card_number" placeholder="1234567890123456" 
                                 required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="expiry">Expiry Date</label>
                                    <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required>
                                </div>
                                <div class="form-group">
                                    <label for="cvv">CVV</label>
                                    <input type="text" id="cvv" name="cvv"
                                inputmode="numeric"
                                pattern="^\d{3}$"
                                maxlength="3"
                                placeholder="123"
                                    required>
                                </div>
                            </div>
                        </div>

                        <div class="security-info">
                            <i class="fas fa-shield-alt"></i>
                            Your payment information is encrypted and secure. We use industry-standard SSL encryption to protect your data.
                        </div>

                        <button type="submit" name="process_payment" class="btn">
                            <i class="fas fa-check"></i> Complete Order - $<?php echo number_format($total, 2); ?>
                        </button>
                    </form>
                </div>
            </div>

            <div class="checkout-section">
                <div class="section-header">
                    <i class="fas fa-receipt"></i> Order Summary
                </div>
                <div class="section-content">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="order-item">
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-details">
                                Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?>
                            </div>
                        </div>
                        <div class="item-total">
                            $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div style="margin-top: 2rem;">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>₹<?php echo number_format($subtotal, 2); ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Tax (8%):</span>
                            <span>₹<?php echo number_format($tax, 2); ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span><?php echo $shipping == 0 ? 'FREE' : 
              number_format($shipping, 2); ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Total:</span>
                            <span>₹<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>

                    <a href="cart.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function selectPayment(element) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(method => {
                method.classList.remove('selected');
            });
            
            // Add selected class to clicked element
            element.classList.add('selected');
            
            // Show/hide credit card form based on selection
            const creditCardForm = document.getElementById('credit-card-form');
            const isCardSelected = element.textContent.trim().includes('Credit Card');
            creditCardForm.style.display = isCardSelected ? 'block' : 'none';
        }

        // Format card number input
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        // Format expiry date input
        document.getElementById('expiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // Restrict CVV to numbers only
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
            