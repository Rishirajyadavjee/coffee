<style>
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
</style>
<footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About BrewMaster</h3>
                    <p>We are passionate about delivering the finest coffee experience. From bean to cup, we ensure quality in every step.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="products.php">Our Products</a>
                    <a href="gallery.php">Gallery</a>
                    <a href="about_us.php">About Us</a>
                    <a href="Contact_us.php">Contact Us</a>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Coffee Street, Bean City</p>
                    <p><i class="fas fa-phone"></i>8780307902</p>
                    <p><i class="fas fa-envelope"></i> rishi@gmail.com</p>
                </div>
                <div class="footer-section">
                    <h3>Book a Table</h3>
                    <p style="margin-bottom: 1rem; color: #ccc;">Reserve your perfect spot at BrewMaster Coffee</p>
                    <div style="text-align: center;">
                        <a href="book_table.php" class="btn" style="display: inline-block; margin-bottom: 1rem;">
                            <i class="fas fa-utensils"></i> Reserve Table
                        </a>
                    </div>
                    <div style="font-size: 0.9rem; color: #ccc;">
                        <p><i class="fas fa-clock"></i> Open: 8:00 AM - 8:00 PM</p>
                        <p><i class="fas fa-phone"></i> Call: (555) 123-4567</p>
                        <p><i class="fas fa-info-circle"></i> Login required for booking</p>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 BrewMaster Coffee. All rights reserved.</p>
            </div>
        </div>
    </footer>