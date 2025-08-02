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
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(50),
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

-- Insert sample products
INSERT INTO products (name, description, price, image, category, stock) VALUES
('Espresso', 'Rich and bold espresso shot', 2.50, 'espresso.jpg', 'Hot Coffee', 50),
('Cappuccino', 'Perfect blend of espresso and steamed milk', 3.75, 'cappuccino.jpg', 'Hot Coffee', 45),
('Latte', 'Smooth espresso with steamed milk and foam', 4.25, 'latte.jpg', 'Hot Coffee', 40),
('Americano', 'Espresso with hot water for a lighter taste', 3.00, 'americano.jpg', 'Hot Coffee', 35),
('Mocha', 'Chocolate and espresso perfection', 4.50, 'mocha.jpg', 'Hot Coffee', 30),
('Macchiato', 'Espresso with a dollop of foam', 3.50, 'macchiato.jpg', 'Hot Coffee', 25),
('French Press', 'Full-bodied coffee brewed to perfection', 3.25, 'french_press.jpg', 'Hot Coffee', 20),
('Cold Brew', 'Smooth, refreshing cold coffee', 3.75, 'cold_brew.jpg', 'Cold Coffee', 40),
('Iced Latte', 'Chilled version of our classic latte', 4.00, 'iced_latte.jpg', 'Cold Coffee', 35),
('Frappuccino', 'Blended ice coffee drink', 5.25, 'frappuccino.jpg', 'Cold Coffee', 30),
('Green Tea Latte', 'Creamy green tea with steamed milk', 4.25, 'green_tea_latte.jpg', 'Tea', 25),
('Chai Latte', 'Spiced tea with steamed milk', 4.00, 'chai_latte.jpg', 'Tea', 30),
('Hot Chocolate', 'Rich and creamy chocolate drink', 3.50, 'hot_chocolate.jpg', 'Other', 35),
('Coffee Beans - House Blend', '1lb bag of our signature blend', 12.99, 'beans_house.jpg', 'Beans', 100),
('Coffee Beans - Dark Roast', '1lb bag of dark roasted beans', 13.99, 'beans_dark.jpg', 'Beans', 80);