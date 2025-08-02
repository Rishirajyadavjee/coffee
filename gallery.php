<?php require_once 'config.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - BrewMaster Coffee</title>
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

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 3rem;
            color: #6B4423;
            margin-bottom: 1rem;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: #666;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .gallery-item {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
            cursor: pointer;
        }

        .gallery-item:hover {
            transform: translateY(-10px);
        }

        .gallery-image {
            height: 250px;
            background: linear-gradient(45deg, #D2691E, #CD853F);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            position: relative;
        }

        .gallery-coffee-beans {
            background: linear-gradient(45deg, #8B4513, #A0522D);
        }

        .gallery-espresso {
            background: linear-gradient(45deg, #654321, #8B4513);
        }

        .gallery-latte {
            background: linear-gradient(45deg, #D2691E, #F4A460);
        }

        .gallery-cafe {
            background: linear-gradient(45deg, #6B4423, #8B4513);
        }

        .gallery-barista {
            background: linear-gradient(45deg, #CD853F, #DEB887);
        }

        .gallery-roasting {
            background: linear-gradient(45deg, #A0522D, #CD853F);
        }

        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.7));
            display: flex;
            align-items: flex-end;
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-title {
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .gallery-description {
            padding: 1.5rem;
        }

        .gallery-description h3 {
            color: #6B4423;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }

        .gallery-description p {
            color: #666;
            line-height: 1.6;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }

        .modal-content {
            position: relative;
            margin: 5% auto;
            width: 90%;
            max-width: 800px;
            background: white;
            border-radius: 15px;
            overflow: hidden;
        }

        .modal-image {
            width: 100%;
            height: 400px;
            background: linear-gradient(45deg, #D2691E, #CD853F);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 6rem;
        }

        .modal-info {
            padding: 2rem;
        }

        .modal-info h2 {
            color: #6B4423;
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .modal-info p {
            color: #666;
            line-height: 1.8;
            font-size: 1.1rem;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 25px;
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            z-index: 2001;
        }

        .close:hover {
            color: #D2691E;
        }

        .coffee-story {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 3rem;
        }

        .coffee-story h2 {
            color: #6B4423;
            font-size: 2.5rem;
            margin-bottom: 2rem;
        }

        .coffee-story p {
            color: #666;
            font-size: 1.2rem;
            line-height: 1.8;
            max-width: 800px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .modal-image {
                height: 250px;
                font-size: 4rem;
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
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="admin.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Coffee Gallery</h1>
            <p class="page-subtitle">Experience the artistry and passion behind every cup</p>
        </div>

        <div class="gallery-grid">
            <div class="gallery-item" onclick="openModal('modal1')">
                <div class="gallery-image gallery-coffee-beans">
                    <i class="fas fa-seedling"></i>
                    <div class="gallery-overlay">
                        <div class="gallery-title">Premium Coffee Beans</div>
                    </div>
                </div>
                <div class="gallery-description">
                    <h3>Origin Story</h3>
                    <p>Our journey begins with carefully selected premium coffee beans from the finest farms around the world.</p>
                </div>
            </div>

            <div class="gallery-item" onclick="openModal('modal2')">
                <div class="gallery-image gallery-roasting">
                    <i class="fas fa-fire"></i>
                    <div class="gallery-overlay">
                        <div class="gallery-title">Artisan Roasting</div>
                    </div>
                </div>
                <div class="gallery-description">
                    <h3>Perfect Roast</h3>
                    <p>Master roasters craft each batch to perfection, bringing out the unique flavors of every bean.</p>
                </div>
            </div>

            <div class="gallery-item" onclick="openModal('modal3')">
                <div class="gallery-image gallery-barista">
                    <i class="fas fa-user-tie"></i>
                    <div class="gallery-overlay">
                        <div class="gallery-title">Skilled Baristas</div>
                    </div>
                </div>
                <div class="gallery-description">
                    <h3>Expert Craftsmanship</h3>
                    <p>Our skilled baristas transform premium beans into extraordinary coffee experiences.</p>
                </div>
            </div>

            <div class="gallery-item" onclick="openModal('modal4')">
                <div class="gallery-image gallery-espresso">
                    <i class="fas fa-coffee"></i>
                    <div class="gallery-overlay">
                        <div class="gallery-title">Perfect Espresso</div>
                    </div>
                </div>
                <div class="gallery-description">
                    <h3>Signature Shots</h3>
                    <p>Rich, bold espresso shots that form the foundation of all our specialty coffee drinks.</p>
                </div>
            </div>

            <div class="gallery-item" onclick="openModal('modal5')">
                <div class="gallery-image gallery-latte">
                    <i class="fas fa-heart"></i>
                    <div class="gallery-overlay">
                        <div class="gallery-title">Latte Art</div>
                    </div>
                </div>
                <div class="gallery-description">
                    <h3>Artistic Touch</h3>
                    <p>Beautiful latte art that makes every cup a visual masterpiece as well as a taste sensation.</p>
                </div>
            </div>

            <div class="gallery-item" onclick="openModal('modal6')">
                <div class="gallery-image gallery-cafe">
                    <i class="fas fa-store"></i>
                    <div class="gallery-overlay">
                        <div class="gallery-title">Cozy Atmosphere</div>
                    </div>
                </div>
                <div class="gallery-description">
                    <h3>Perfect Ambiance</h3>
                    <p>A warm, inviting space where coffee lovers gather to enjoy exceptional brews and great company.</p>
                </div>
            </div>
        </div>

        <div class="coffee-story">
            <h2>Our Coffee Story</h2>
            <p>At BrewMaster, we believe that great coffee is more than just a beverage – it's an experience, a moment of connection, and a daily ritual that brings joy to life. From the highland farms where our beans are grown to the expert hands of our baristas, every step of our process is guided by passion, precision, and an unwavering commitment to quality. We invite you to be part of our story, one perfect cup at a time.</p>
        </div>
    </div>

    <!-- Modals -->
    <div id="modal1" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modal1')">&times;</span>
            <div class="modal-image gallery-coffee-beans">
                <i class="fas fa-seedling"></i>
            </div>
            <div class="modal-info">
                <h2>Premium Coffee Beans</h2>
                <p>Our coffee journey begins at carefully selected farms in the world's premier coffee-growing regions. We work directly with farmers who share our commitment to quality and sustainability. Each bean is hand-picked at peak ripeness, ensuring that only the finest make it into your cup. From the volcanic soils of Guatemala to the misty mountains of Ethiopia, we source beans that tell a story of tradition, craftsmanship, and exceptional flavor.</p>
            </div>
        </div>
    </div>

    <div id="modal2" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modal2')">&times;</span>
            <div class="modal-image gallery-roasting">
                <i class="fas fa-fire"></i>
            </div>
            <div class="modal-info">
                <h2>Artisan Roasting Process</h2>
                <p>Roasting is where science meets art. Our master roasters have decades of experience in bringing out the unique characteristics of each bean origin. Using state-of-the-art roasting equipment combined with time-honored techniques, we carefully monitor temperature, time, and airflow to achieve the perfect roast profile. Whether it's a light roast that preserves the bean's origin flavors or a dark roast with rich, bold notes, every batch is crafted to perfection.</p>
            </div>
        </div>
    </div>

    <div id="modal3" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modal3')">&times;</span>
            <div class="modal-image gallery-barista">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="modal-info">
                <h2>Skilled Barista Craftmanship</h2>
                <p>Our baristas are true coffee artisans, trained in the art and science of coffee brewing. Each team member undergoes extensive training in espresso extraction, milk steaming, and latte art. They understand that every cup is an opportunity to create something special, combining technical precision with creative flair. Their expertise ensures that whether you order a simple americano or a complex signature drink, it will be prepared to perfection.</p>
            </div>
        </div>
    </div>

    <div id="modal4" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modal4')">&times;</span>
            <div class="modal-image gallery-espresso">
                <i class="fas fa-coffee"></i>
            </div>
            <div class="modal-info">
                <h2>Perfect Espresso Shots</h2>
                <p>The espresso shot is the heart of our coffee program. Using precisely calibrated espresso machines and our signature blend, we extract shots that achieve the perfect balance of sweetness, acidity, and body. Each shot is pulled with meticulous attention to grind size, dose, and extraction time, resulting in a rich, golden crema and a flavor profile that showcases the best of our beans.</p>
            </div>
        </div>
    </div>

    <div id="modal5" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modal5')">&times;</span>
            <div class="modal-image gallery-latte">
                <i class="fas fa-heart"></i>
            </div>
            <div class="modal-info">
                <h2>Beautiful Latte Art</h2>
                <p>Latte art is the perfect fusion of technical skill and creative expression. Our baristas create stunning designs in every cup, from classic rosettes and hearts to intricate patterns that make each drink unique. But latte art isn't just about beauty – the process of creating microfoam and pouring steamed milk also ensures the perfect texture and temperature for an exceptional drinking experience.</p>
            </div>
        </div>
    </div>

    <div id="modal6" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modal6')">&times;</span>
            <div class="modal-image gallery-cafe">
                <i class="fas fa-store"></i>
            </div>
            <div class="modal-info">
                <h2>Cozy Cafe Atmosphere</h2>
                <p>Our cafe is designed to be a third place – somewhere between home and work where you can relax, connect, and enjoy exceptional coffee. With comfortable seating, warm lighting, and the gentle hum of conversation, we've created a space that welcomes everyone. Whether you're meeting friends, working on a project, or simply taking a moment to savor your coffee, our atmosphere enhances every experience.</p>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        }
    </script>
</body>
</html>