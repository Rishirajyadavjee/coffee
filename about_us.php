<?php
require_once 'config.php';

$success = '';
$error = '';

// Handle Contact Form Submission
if ($_POST && isset($_POST['contact_submit'])) {
    $name = sanitize($_POST['contact_name']);
    $email = sanitize($_POST['contact_email']);
    $subject = sanitize($_POST['contact_subject']);
    $message = sanitize($_POST['contact_message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required for contact form.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $subject, $message])) {
            $success = 'Thank you for contacting us! We will get back to you soon.';
        } else {
            $error = 'Failed to send message. Please try again.';
        }
    }
}

// Handle Feedback Form Submission
if ($_POST && isset($_POST['feedback_submit'])) {
    $customer_name = sanitize($_POST['feedback_name']);
    $customer_email = sanitize($_POST['feedback_email']);
    $rating = intval($_POST['rating']);
    $feedback_message = sanitize($_POST['feedback_message']);
    $service_type = sanitize($_POST['service_type']);
    
    if (empty($customer_name) || empty($customer_email) || empty($feedback_message) || $rating < 1 || $rating > 5) {
        $error = 'All fields are required for feedback form and rating must be between 1-5.';
    } elseif (!validateEmail($customer_email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO feedback (customer_name, customer_email, rating, message, service_type) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$customer_name, $customer_email, $rating, $feedback_message, $service_type])) {
            $success = 'Thank you for your feedback! Your opinion helps us improve our service.';
        } else {
            $error = 'Failed to submit feedback. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - BrewMaster Coffee</title>
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
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .hero-section {
            background: linear-gradient(135deg, #6B4423, #8B4513);
            color: white;
            padding: 4rem 2rem;
            border-radius: 15px;
            margin-bottom: 3rem;
            text-align: center;
        }

        .hero-section h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 4rem;
        }

        .about-text {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .about-text h2 {
            color: #6B4423;
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .about-text p {
            color: #666;
            margin-bottom: 1rem;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 3rem;
            color: #D2691E;
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            font-size: 2rem;
            color: #6B4423;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #666;
        }

        .forms-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .form-container h3 {
            color: #6B4423;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            text-align: center;
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

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #D2691E;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
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
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(210, 105, 30, 0.3);
        }

        .rating-group {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .rating-star {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.3s;
        }

        .rating-star:hover,
        .rating-star.active {
            color: #FFD700;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #28a745;
            text-align: center;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #dc3545;
            text-align: center;
        }

        .team-section {
            background: white;
            padding: 3rem 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem;
            text-align: center;
        }

        .team-section h2 {
            color: #6B4423;
            margin-bottom: 2rem;
            font-size: 2.5rem;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .team-member {
            text-align: center;
        }

        .team-member img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 4px solid #D2691E;
        }

        .team-member h4 {
            color: #6B4423;
            margin-bottom: 0.5rem;
        }

        .team-member p {
            color: #666;
        }

        @media (max-width: 768px) {
            .about-content,
            .forms-section {
                grid-template-columns: 1fr;
            }

            .hero-section h1 {
                font-size: 2rem;
            }

            .stats-section {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include_once("navigation.php"); ?>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1><i class="fas fa-coffee"></i> About BrewMaster Coffee</h1>
            <p>Crafting exceptional coffee experiences since 2020. We're passionate about bringing you the finest coffee from around the world, roasted to perfection and served with love.</p>
        </div>

        <?php if ($success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- About Content -->
        <div class="about-content">
            <div class="about-text">
                <h2>Our Story</h2>
                <p>Founded in 2020 by coffee enthusiasts, BrewMaster Coffee began as a small roastery with a big dream: to share the world's finest coffee with fellow coffee lovers.</p>
                <p>We source our beans directly from farmers, ensuring fair trade practices and the highest quality. Each batch is carefully roasted in small quantities to preserve the unique flavors and aromas that make each origin special.</p>
                <p>Today, we're proud to serve thousands of customers who share our passion for exceptional coffee. From our signature blends to rare single-origin varieties, every cup tells a story of craftsmanship and dedication.</p>
            </div>

            <div class="about-text">
                <h2>Our Mission</h2>
                <p>At BrewMaster Coffee, we believe that great coffee brings people together. Our mission is to:</p>
                <ul style="margin-left: 2rem; color: #666;">
                    <li>Source the finest coffee beans from sustainable farms</li>
                    <li>Roast each batch to perfection using traditional methods</li>
                    <li>Provide exceptional customer service and coffee education</li>
                    <li>Support coffee farming communities worldwide</li>
                    <li>Create a welcoming space for coffee lovers to connect</li>
                </ul>
                <p style="margin-top: 1rem;">Whether you're a coffee connoisseur or just beginning your coffee journey, we're here to help you discover your perfect cup.</p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-section">
            <div class="stat-card">
                <i class="fas fa-coffee"></i>
                <h3>50+</h3>
                <p>Coffee Varieties</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3>10,000+</h3>
                <p>Happy Customers</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-globe"></i>
                <h3>15+</h3>
                <p>Countries Sourced</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-award"></i>
                <h3>5+</h3>
                <p>Awards Won</p>
            </div>
        </div>

        <!-- Team Section -->
        <div class="team-section">
            <h2>Meet Our Team</h2>
            <p>Our passionate team of coffee experts is dedicated to bringing you the best coffee experience.</p>
            
            <div class="team-grid">
                <div class="team-member">
                    <img src="https://via.placeholder.com/150x150/6B4423/white?text=JD" alt="John Doe">
                    <h4>Rishi Yadav</h4>
                    <p>Head Roaster & Founder</p>
                </div>
                <div class="team-member">
                    <img src="https://via.placeholder.com/150x150/D2691E/white?text=JS" alt="Jane Smith">
                    <h4>Alfaj</h4>
                    <p>Coffee Sourcing Specialist</p>
                </div>
                <div class="team-member">
                    <img src="https://via.placeholder.com/150x150/8B4513/white?text=MB" alt="Mike Brown">
                    <h4>Fahim</h4>
                    <p>Barista Trainer</p>
                </div>
            </div>
        </div>

        <!-- Contact and Feedback Forms -->
        <div class="forms-section">
            <!-- Contact Form -->
            <div class="form-container">
                <h3><i class="fas fa-envelope"></i> Contact Us</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="contact_name">Full Name *</label>
                        <input type="text" id="contact_name" name="contact_name" required>
                    </div>

                    <div class="form-group">
                        <label for="contact_email">Email Address *</label>
                        <input type="email" id="contact_email" name="contact_email" required>
                    </div>

                    <div class="form-group">
                        <label for="contact_subject">Subject *</label>
                        <select id="contact_subject" name="contact_subject" required>
                            <option value="">Select Subject</option>
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Product Question">Product Question</option>
                            <option value="Order Support">Order Support</option>
                            <option value="Partnership">Partnership</option>
                            <option value="Complaint">Complaint</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="contact_message">Message *</label>
                        <textarea id="contact_message" name="contact_message" placeholder="How can we help you?" required></textarea>
                    </div>

                    <button type="submit" name="contact_submit" class="btn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
            <!-- Feedback Form -->
            <div class="form-container">
                <h3><i class="fas fa-star"></i> Share Your Feedback</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="feedback_name">Your Name *</label>
                        <input type="text" id="feedback_name" name="feedback_name" required>
                    </div>

                    <div class="form-group">
                        <label for="feedback_email">Email Address *</label>
                        <input type="email" id="feedback_email" name="feedback_email" required>
                    </div>

                    <div class="form-group">
                        <label for="service_type">Service Type</label>
                        <select id="service_type" name="service_type">
                            <option value="Online Order">Online Order</option>
                            <option value="In-Store Experience">In-Store Experience</option>
                            <option value="Product Quality">Product Quality</option>
                            <option value="Customer Service">Customer Service</option>
                            <option value="Delivery Service">Delivery Service</option>
                            <option value="Overall Experience">Overall Experience</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Rate Your Experience *</label>
                        <div class="rating-group">
                            <span class="rating-star" data-rating="1">★</span>
                            <span class="rating-star" data-rating="2">★</span>
                            <span class="rating-star" data-rating="3">★</span>
                            <span class="rating-star" data-rating="4">★</span>
                            <span class="rating-star" data-rating="5">★</span>
                        </div>
                        <input type="hidden" id="rating" name="rating" value="" required>
                    </div>

                    <div class="form-group">
                        <label for="feedback_message">Your Feedback *</label>
                        <textarea id="feedback_message" name="feedback_message" placeholder="Tell us about your experience..." required></textarea>
                    </div>

                    <button type="submit" name="feedback_submit" class="btn">
                        <i class="fas fa-heart"></i> Submit Feedback
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include_once("footer.php"); ?>

    <script>
        // Rating system
        const stars = document.querySelectorAll('.rating-star');
        const ratingInput = document.getElementById('rating');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingInput.value = rating;
                
                // Update star display
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });

            star.addEventListener('mouseover', function() {
                const rating = this.getAttribute('data-rating');
                
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#FFD700';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });

        // Reset stars on mouse leave
        document.querySelector('.rating-group').addEventListener('mouseleave', function() {
            const currentRating = ratingInput.value;
            stars.forEach((s, index) => {
                if (index < currentRating) {
                    s.style.color = '#FFD700';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });

        // Auto-hide success/error messages
        setTimeout(function() {
            const messages = document.querySelectorAll('.success, .error');
            messages.forEach(message => {
                message.style.opacity = '0';
                message.style.transition = 'opacity 0.5s ease';
                setTimeout(() => message.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>