-- Sample data to populate homepage sections
-- Run this to add some sample orders for testing most selling and hot selling sections

USE coffee_shop;

-- Insert some sample orders to create selling data
INSERT INTO orders (user_id, total_amount, status, order_date) VALUES
(1, 45.99, 'confirmed', '2024-12-01 10:30:00'),
(1, 78.50, 'confirmed', '2024-12-05 14:15:00'),
(1, 32.25, 'confirmed', '2024-12-10 09:45:00'),
(1, 156.75, 'confirmed', '2024-12-15 16:20:00'),
(1, 89.99, 'confirmed', '2024-12-18 11:30:00');

-- Get the order IDs
SET @order1 = (SELECT MAX(id) - 4 FROM orders);
SET @order2 = (SELECT MAX(id) - 3 FROM orders);
SET @order3 = (SELECT MAX(id) - 2 FROM orders);
SET @order4 = (SELECT MAX(id) - 1 FROM orders);
SET @order5 = (SELECT MAX(id) FROM orders);

-- Insert order items to create selling statistics
-- Make Ethiopian Dark Roast the most selling
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(@order1, 1, 5, 16.99),  -- Ethiopian Dark Roast - 5 sold
(@order2, 1, 3, 16.99),  -- Ethiopian Dark Roast - 3 more sold
(@order3, 1, 4, 16.99),  -- Ethiopian Dark Roast - 4 more sold
(@order4, 1, 2, 16.99),  -- Ethiopian Dark Roast - 2 more sold
(@order5, 1, 3, 16.99);  -- Ethiopian Dark Roast - 3 more sold (Total: 17)

-- Make Colombian Supreme Dark second most selling
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(@order1, 2, 2, 18.50),  -- Colombian Supreme Dark - 2 sold
(@order2, 2, 3, 18.50),  -- Colombian Supreme Dark - 3 more sold
(@order3, 2, 2, 18.50),  -- Colombian Supreme Dark - 2 more sold
(@order4, 2, 4, 18.50);  -- Colombian Supreme Dark - 4 more sold (Total: 11)

-- Make Brazilian Medium Roast hot selling (recent orders)
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(@order4, 4, 6, 14.75),  -- Brazilian Medium Roast - 6 sold recently
(@order5, 4, 4, 14.75);  -- Brazilian Medium Roast - 4 more sold recently (Total: 10)

-- Add more variety to other products
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(@order1, 3, 1, 15.25),  -- French Roast Blend
(@order2, 5, 2, 19.99),  -- Guatemala Antigua
(@order3, 6, 1, 13.99),  -- House Blend Medium
(@order4, 7, 2, 17.50),  -- Ethiopian Light Roast
(@order5, 8, 1, 16.25);  -- Costa Rican Light

-- Verify the data
SELECT 
    p.name,
    p.category,
    COALESCE(SUM(oi.quantity), 0) as total_sold,
    COALESCE(SUM(CASE WHEN o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN oi.quantity ELSE 0 END), 0) as recent_sold
FROM products p 
LEFT JOIN order_items oi ON p.id = oi.product_id 
LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'confirmed'
GROUP BY p.id, p.name, p.category
ORDER BY total_sold DESC;