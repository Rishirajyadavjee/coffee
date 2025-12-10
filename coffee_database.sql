-- Coffee Website Database Schema
-- Run this SQL to create the database structure

CREATE DATABASE coffee_shop;
USE coffee_shop;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100) NOT NULL,
    image_path TEXT,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT,
    price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Bookings table for contact us
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    message TEXT,
    booking_date DATE,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, is_admin) VALUES 
('admin', 'admin@coffee.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE);

-- Sample data for coffee shop products
-- Run these INSERT statements in your MySQL database after creating the products table

-- Dark Roast Coffee Products
INSERT INTO products (name, description, price, category, image_path, stock, created_at) VALUES
('Ethiopian Dark Roast', 'Rich and bold Ethiopian coffee with notes of chocolate and berries. Perfect for espresso or french press brewing.', 16.99, 'Dark Roast', 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=400&h=300&fit=crop', 45, NOW()),

('Colombian Supreme Dark', 'Premium Colombian beans roasted to perfection. Full-bodied with a smooth finish and hints of caramel.', 18.50, 'Dark Roast', 'https://images.unsplash.com/photo-1559496417-e7f25cb247cd?w=400&h=300&fit=crop', 32, NOW()),

('French Roast Blend', 'Classic French roast with intense smoky flavor. Bold and robust, perfect for those who love strong coffee.', 15.25, 'Dark Roast', 'https://images.unsplash.com/photo-1610632380989-680fe40816c6?w=400&h=300&fit=crop', 28, NOW()),

-- Medium Roast Coffee Products
('Brazilian Medium Roast', 'Smooth Brazilian beans with balanced acidity. Notes of nuts and milk chocolate make this perfect for any time of day.', 14.75, 'Medium Roast', 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400&h=300&fit=crop', 52, NOW()),

('Guatemala Antigua', 'Single-origin Guatemalan coffee with complex flavor profile. Medium roast brings out citrus and spice notes.', 19.99, 'Medium Roast', 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400&h=300&fit=crop', 38, NOW()),

('House Blend Medium', 'Our signature house blend combining beans from three continents. Perfectly balanced for everyday enjoyment.', 13.99, 'Medium Roast', 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=400&h=300&fit=crop', 65, NOW()),

-- Light Roast Coffee Products
('Ethiopian Light Roast', 'Bright and floral Ethiopian beans with wine-like acidity. Perfect for pour-over brewing methods.', 17.50, 'Light Roast', 'https://images.unsplash.com/photo-1442512595331-e89e73853f31?w=400&h=300&fit=crop', 29, NOW()),

('Costa Rican Light', 'High-altitude Costa Rican beans with bright citrus notes. Light roast preserves the beans natural flavors.', 16.25, 'Light Roast', 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=400&h=300&fit=crop', 41, NOW()),

('Breakfast Blend Light', 'Gentle morning blend with mild flavor and smooth finish. Perfect way to start your day.', 12.99, 'Light Roast', 'https://images.unsplash.com/photo-1497515114629-f71d768fd07c?w=400&h=300&fit=crop', 48, NOW()),

-- Espresso Products
('Italian Espresso Blend', 'Traditional Italian espresso blend with rich crema. Perfect balance of intensity and smoothness.', 21.99, 'Espresso', 'https://images.unsplash.com/photo-1510707577719-ae7c14805e5a?w=400&h=300&fit=crop', 35, NOW()),

('Single Origin Espresso', 'Premium single-origin beans crafted specifically for espresso. Complex flavor with lasting finish.', 24.50, 'Espresso', 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=400&h=300&fit=crop', 22, NOW()),

('Decaf Espresso', 'All the flavor of our signature espresso without the caffeine. Swiss water processed for purity.', 19.75, 'Espresso', 'https://images.unsplash.com/photo-1551218808-94e220e084d2?w=400&h=300&fit=crop', 18, NOW()),

-- Decaf Products
('Colombian Decaf', 'Premium Colombian beans decaffeinated using the Swiss water process. Full flavor without the caffeine.', 17.99, 'Decaf', 'https://images.unsplash.com/photo-1606312619070-d48b4c652a52?w=400&h=300&fit=crop', 26, NOW()),

('French Roast Decaf', 'Bold French roast flavor in a decaffeinated version. Perfect for evening coffee lovers.', 16.50, 'Decaf', 'https://images.unsplash.com/photo-1545665277-5937750a7173?w=400&h=300&fit=crop', 31, NOW()),

-- Cold Brew Products
('Cold Brew Concentrate', 'Smooth cold brew concentrate perfect for iced coffee. Just add water or milk and enjoy.', 22.99, 'Cold Brew', 'https://images.unsplash.com/photo-1461988320302-91bde64fc8e4?w=400&h=300&fit=crop', 15, NOW()),

('Vanilla Cold Brew', 'Cold brew infused with natural vanilla flavoring. Smooth and refreshing with a sweet finish.', 24.99, 'Cold Brew', 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?w=400&h=300&fit=crop', 12, NOW()),

('Nitro Cold Brew', 'Nitrogen-infused cold brew for that perfect creamy texture. Served on tap for the ultimate experience.', 26.50, 'Cold Brew', 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop', 8, NOW()),

-- Coffee Accessories
('French Press - 34oz', 'Premium stainless steel French press perfect for brewing rich, full-bodied coffee at home.', 39.99, 'Accessories', 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=400&h=300&fit=crop', 25, NOW()),

('Pour Over Coffee Dripper', 'Ceramic pour-over dripper for the perfect cup. Includes 100 paper filters to get you started.', 29.99, 'Accessories', 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=400&h=300&fit=crop', 18, NOW()),

('Coffee Grinder - Burr', 'Professional burr grinder for consistent grind size. Essential for the perfect coffee extraction.', 79.99, 'Accessories', 'https://images.unsplash.com/photo-1595981267035-7b04ca84a82d?w=400&h=300&fit=crop', 12, NOW()),

('Thermal Coffee Carafe', 'Double-walled stainless steel carafe keeps coffee hot for hours. Perfect for offices and homes.', 45.99, 'Accessories', 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=400&h=300&fit=crop', 22, NOW()),

('Coffee Storage Canister', 'Airtight coffee storage canister with CO2 valve. Keeps coffee fresh for weeks.', 24.99, 'Accessories', 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=400&h=300&fit=crop', 35, NOW()),

('Espresso Cups Set', 'Set of 6 traditional Italian espresso cups with saucers. Perfect for serving authentic espresso.', 34.99, 'Accessories', 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=400&h=300&fit=crop', 15, NOW()),

('Coffee Scale Digital', 'Precision digital scale for perfect coffee-to-water ratios. Essential for serious coffee enthusiasts.', 49.99, 'Accessories', 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=400&h=300&fit=crop', 20, NOW());

-- Additional seasonal and specialty items
INSERT INTO products (name, description, price, category, image_url, stock, created_at) VALUES
('Holiday Blend', 'Limited edition holiday blend with cinnamon and nutmeg notes. Available only during the holiday season.', 19.99, 'Medium Roast', 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=300&fit=crop', 40, NOW()),

('Organic Fair Trade Blend', 'Certified organic and fair trade coffee blend. Supporting sustainable farming practices worldwide.', 21.50, 'Medium Roast', 'https://images.unsplash.com/photo-1497515114629-f71d768fd07c?w=400&h=300&fit=crop', 33, NOW()),

('Jamaican Blue Mountain', 'Rare and expensive Jamaican Blue Mountain coffee. Known for its mild flavor and lack of bitterness.', 89.99, 'Light Roast', 'https://images.unsplash.com/photo-1442512595331-e89e73853f31?w=400&h=300&fit=crop', 5, NOW()),

('Kona Hawaiian Coffee', 'Authentic Hawaiian Kona coffee with smooth, rich flavor. Grown on the volcanic slopes of Hawaii.', 65.99, 'Medium Roast', 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400&h=300&fit=crop', 8, NOW());

-- Check the inserted data
-- SELECT * FROM products ORDER BY category, name;