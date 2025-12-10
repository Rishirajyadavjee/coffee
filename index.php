<?php
include_once 'config.php';

// Get featured products
$stmt = $pdo->query("SELECT * FROM products WHERE visible = 1 ORDER BY created_at DESC LIMIT 6");
$featured_products = $stmt->fetchAll();

// Get most selling coffee (based on order quantity)
$stmt = $pdo->query("
    SELECT p.*, COALESCE(SUM(oi.quantity), 0) as total_sold 
    FROM products p 
    LEFT JOIN order_items oi ON p.id = oi.product_id 
    LEFT JOIN orders o ON oi.order_id = o.id 
    WHERE p.visible = 1 AND o.status = 'confirmed'
    GROUP BY p.id 
    ORDER BY total_sold DESC 
    LIMIT 6
");
$most_selling = $stmt->fetchAll();

// Get hot selling coffee (recent popular items)
$stmt = $pdo->query("
    SELECT p.*, COALESCE(SUM(oi.quantity), 0) as recent_sold 
    FROM products p 
    LEFT JOIN order_items oi ON p.id = oi.product_id 
    LEFT JOIN orders o ON oi.order_id = o.id 
    WHERE p.visible = 1 AND o.status = 'confirmed' 
    AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY p.id 
    ORDER BY recent_sold DESC 
    LIMIT 6
");
$hot_selling = $stmt->fetchAll();

// Get slider images/products for hero carousel
$stmt = $pdo->query("SELECT * FROM products WHERE visible = 1 ORDER BY RAND() LIMIT 5");
$slider_products = $stmt->fetchAll();

// Get user orders
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.order_date DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();

    // Get cart count
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_count = $stmt->fetchColumn() ?: 0;
} else {
    $orders = [];
    $cart_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BrewMaster Coffee - Premium Coffee Experience</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .navbar {
            background: linear-gradient(135deg, #6B4423, #8B4513);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

    /* 
            background ke photos ke liye hai change karna ho toh kar lena
    .hero { background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%238B4513" width="1200" height="600"/><circle fill="%23D2691E" cx="200" cy="150" r="50" opacity="0.3"/><circle fill="%23CD853F" cx="800" cy="400" r="80" opacity="0.2"/><circle fill="%23F4A460" cx="1000" cy="200" r="60" opacity="0.25"/></svg>');
    height: 100vh; 
    display: flex; 
    align-items: center;
    justify-content: center; 
    text-align: center; 
    color: white; 
    background-size: cover;
    background-position: center; } */

            .hero {
        background: 
        linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
        url('backgroundphotos/image.jpg');
    background-size: cover;
    background-position: center;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: white;
    }


        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }

        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            max-width: 600px;
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
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(210, 105, 30, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(210, 105, 30, 0.4);
        }

        .section {
            padding: 5rem 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #6B4423;
        }

        .featured-products {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-10px);
        }

        .product-image {
            height: 200px;
            background: linear-gradient(45deg, #D2691E, #CD853F);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-info h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #6B4423;
        }

        .product-info p {
            color: #666;
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #D2691E;
            margin-bottom: 1rem;
        }

        .footer {
            background: #333;
            color: white;
            padding: 3rem 0 1rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            color: #D2691E;
        }

        .footer-section p,
        .footer-section a {
            color: #ccc;
            text-decoration: none;
            margin-bottom: 0.5rem;
            display: block;
        }

        .footer-section a:hover {
            color: #D2691E;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #555;
            color: #ccc;
        }

        .booking-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
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
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #D2691E;
        }

        /* Hero Slider Styles */
        .hero-slider {
            position: relative;
            height: 100vh;
            overflow: hidden;
        }

        .slider-container {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }

        .slide.active {
            opacity: 1;
        }

        .slide-content {
            text-align: center;
            color: white;
            max-width: 600px;
            padding: 2rem;
        }

        .slide-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }

        .slide-content p {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
        }

        .slide-price {
            font-size: 2.5rem;
            font-weight: bold;
            color: #FFD700;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 2rem;
            padding: 1rem;
            cursor: pointer;
            border-radius: 50%;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .slider-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-50%) scale(1.1);
        }

        .prev-btn {
            left: 2rem;
        }

        .next-btn {
            right: 2rem;
        }

        .slider-dots {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 1rem;
        }

        .dot {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s;
        }

        .dot.active,
        .dot:hover {
            background: white;
            transform: scale(1.2);
        }

        /* Section Styles */
        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 3rem;
            margin-top: -2rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .product-card {
            position: relative;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #8B4513;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 10;
            box-shadow: 0 2px 10px rgba(255, 215, 0, 0.3);
        }

        .hot-badge {
            background: linear-gradient(45deg, #FF6B35, #FF4500) !important;
            color: white !important;
        }

        .product-image {
            height: 250px;
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-info {
            padding: 2rem;
        }

        .product-info h3 {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
            color: #6B4423;
        }

        .product-category {
            color: #D2691E;
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .product-description {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .product-stats {
            margin-bottom: 1rem;
        }

        .sold-count {
            background: #f8f9fa;
            color: #6B4423;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hot-count {
            background: linear-gradient(45deg, #FF6B35, #FF4500);
            color: white;
        }

        .product-price {
            font-size: 1.8rem;
            font-weight: bold;
            color: #D2691E;
            margin-bottom: 1.5rem;
        }

        .product-btn {
            width: 100%;
            text-align: center;
            padding: 12px 24px;
            font-size: 1rem;
        }

        /* Special section styling */
        .hot-section {
            background: linear-gradient(135deg, #fff8f0, #ffeee6);
        }

        .bestseller {
            border: 2px solid #FFD700;
        }

        .hot-item {
            border: 2px solid #FF6B35;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .slide-content h1 {
                font-size: 2.5rem;
            }

            .slide-content p {
                font-size: 1.1rem;
            }

            .slide-price {
                font-size: 2rem;
            }

            .slider-btn {
                font-size: 1.5rem;
                padding: 0.8rem;
            }

            .prev-btn {
                left: 1rem;
            }

            .next-btn {
                right: 1rem;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
   
<?php include_once("navigation.php"); ?>

    <!-- Hero Slider -->
    <section class="hero-slider">
        <div class="slider-container">
            <?php foreach ($slider_products as $index => $product): ?>
            <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/<?php echo htmlspecialchars(basename($product['image_path'])); ?>');">
                <div class="slide-content">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <div class="slide-price">₹<?php echo number_format($product['price'], 2); ?></div>
                    <a href="products.php" class="btn">Shop Now</a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Navigation arrows -->
            <button class="slider-btn prev-btn" onclick="changeSlide(-1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="slider-btn next-btn" onclick="changeSlide(1)">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <!-- Dots indicator -->
            <div class="slider-dots">
                <?php foreach ($slider_products as $index => $product): ?>
                <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="currentSlide(<?php echo $index + 1; ?>)"></span>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Most Selling Coffee Section -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-trophy"></i> Most Selling Coffee
            </h2>
            <p class="section-subtitle">Our customers' all-time favorites</p>
            
            <div class="products-grid">
                <?php foreach ($most_selling as $product): ?>
                <div class="product-card bestseller">
                    <div class="product-badge">
                        <i class="fas fa-crown"></i> Bestseller
                    </div>
                    <div class="product-image">
                        <img src="images/<?php echo htmlspecialchars(basename($product['image_path'])); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/300x200/D2691E/white?text=Coffee'">
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                        <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                        <div class="product-stats">
                            <span class="sold-count">
                                <i class="fas fa-shopping-bag"></i> 
                                <?php echo $product['total_sold']; ?> sold
                            </span>
                        </div>
                        <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                        <a href="products.php" class="btn product-btn">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Hot Selling Coffee Section -->
    <section class="section hot-section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-fire"></i> Hot Selling Coffee
            </h2>
            <p class="section-subtitle">Trending favorites this month</p>
            
            <div class="products-grid">
                <?php foreach ($hot_selling as $product): ?>
                <div class="product-card hot-item">
                    <div class="product-badge hot-badge">
                        <i class="fas fa-fire"></i> Hot
                    </div>
                    <div class="product-image">
                        <img src="images/<?php echo htmlspecialchars(basename($product['image_path'])); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/300x200/FF6B35/white?text=Coffee'">
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                        <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                        <div class="product-stats">
                            <span class="sold-count hot-count">
                                <i class="fas fa-fire"></i> 
                                <?php echo $product['recent_sold']; ?> sold this month
                            </span>
                        </div>
                        <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                        <a href="products.php" class="btn product-btn">Order Now</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-star"></i> Featured Products
            </h2>
            <p class="section-subtitle">Discover our latest coffee selections</p>
            
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="images/<?php echo htmlspecialchars(basename($product['image_path'])); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/300x200/8B4513/white?text=Coffee'">
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                        <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                        <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                        <a href="products.php" class="btn product-btn">Explore</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>



    <?php include_once("footer.php"); ?>

    <script>
        let currentSlideIndex = 0;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        const totalSlides = slides.length;

        function showSlide(index) {
            // Hide all slides
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Show current slide
            slides[index].classList.add('active');
            dots[index].classList.add('active');
        }

        function changeSlide(direction) {
            currentSlideIndex += direction;
            
            if (currentSlideIndex >= totalSlides) {
                currentSlideIndex = 0;
            } else if (currentSlideIndex < 0) {
                currentSlideIndex = totalSlides - 1;
            }
            
            showSlide(currentSlideIndex);
        }

        function currentSlide(index) {
            currentSlideIndex = index - 1;
            showSlide(currentSlideIndex);
        }

        // Auto-play slider
        function autoSlide() {
            changeSlide(1);
        }

        // Start auto-play
        let slideInterval = setInterval(autoSlide, 5000);

        // Pause auto-play on hover
        const sliderContainer = document.querySelector('.slider-container');
        sliderContainer.addEventListener('mouseenter', () => {
            clearInterval(slideInterval);
        });

        sliderContainer.addEventListener('mouseleave', () => {
            slideInterval = setInterval(autoSlide, 5000);
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                changeSlide(-1);
            } else if (e.key === 'ArrowRight') {
                changeSlide(1);
            }
        });

        // Touch/swipe support for mobile
        let startX = 0;
        let endX = 0;

        sliderContainer.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });

        sliderContainer.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            handleSwipe();
        });

        function handleSwipe() {
            const swipeThreshold = 50;
            const diff = startX - endX;
            
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    changeSlide(1); // Swipe left - next slide
                } else {
                    changeSlide(-1); // Swipe right - previous slide
                }
            }
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading animation for images
        document.querySelectorAll('.product-image img').forEach(img => {
            img.addEventListener('load', function() {
                this.style.opacity = '1';
            });
            
            img.addEventListener('error', function() {
                this.style.opacity = '1';
            });
        });
    </script>

</body>

</html>