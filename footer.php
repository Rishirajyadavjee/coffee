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
                    <a href="about.php">About Us</a>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Coffee Street, Bean City</p>
                    <p><i class="fas fa-phone"></i> (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> info@brewmaster.com</p>
                </div>
                <div class="footer-section">
                    <h3>Book a Table</h3>
                    <form action="book_table.php" method="POST" class="booking-form">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <input type="date" name="booking_date" required>
                        </div>
                        <div class="form-group">
                            <textarea name="message" placeholder="Special requests..." rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn">Book Now</button>
                    </form>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 BrewMaster Coffee. All rights reserved.</p>
            </div>
        </div>
    </footer>