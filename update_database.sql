-- Update users table to include profile information and admin features
-- Run this SQL to add new columns to existing users table

USE coffee_shop;

ALTER TABLE users
ADD COLUMN first_name VARCHAR(100),
ADD COLUMN last_name VARCHAR(100),
ADD COLUMN phone VARCHAR(20),
ADD COLUMN address TEXT,
ADD COLUMN city VARCHAR(100),
ADD COLUMN is_active TINYINT(1) DEFAULT 1;


-- Add visible column to products table for show/hide functionality
ALTER TABLE products 
ADD COLUMN visible TINYINT(1) DEFAULT 1;

-- Create contact_messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create feedback table
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    message TEXT NOT NULL,
    service_type VARCHAR(100) DEFAULT 'Overall Experience',
    status VARCHAR(20) DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL,
  `table_number` varchar(10) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL,
  `location` varchar(50) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `restaurant_tables`
--

INSERT INTO `restaurant_tables` (`id`, `table_number`, `table_name`, `capacity`, `location`, `is_available`, `created_at`) VALUES
(1, 'T001', 'Window Table 1', 2, 'Window Side', 1, '2025-10-15 02:22:26'),
(2, 'T002', 'Window Table 2', 2, 'Window Side', 1, '2025-10-15 02:22:26'),
(3, 'T003', 'Corner Cozy', 4, 'Corner', 1, '2025-10-15 02:22:26'),
(4, 'T004', 'Central Table 1', 4, 'Center', 1, '2025-10-15 02:22:26'),
(5, 'T005', 'Central Table 2', 4, 'Center', 1, '2025-10-15 02:22:26'),
(6, 'T006', 'Private Booth 1', 6, 'Private Area', 1, '2025-10-15 02:22:26'),
(7, 'T007', 'Private Booth 2', 6, 'Private Area', 1, '2025-10-15 02:22:26'),
(8, 'T008', 'Patio Table 1', 4, 'Outdoor Patio', 1, '2025-10-15 02:22:26'),
(9, 'T009', 'Patio Table 2', 4, 'Outdoor Patio', 1, '2025-10-15 02:22:26'),
(10, 'T010', 'Bar Counter', 8, 'Bar Area', 1, '2025-10-15 02:22:26');

ALTER TABLE `restaurant_tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `table_number` (`table_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

-- Update existing users with default values if needed
UPDATE users SET 
    first_name = COALESCE(first_name, ''),
    last_name = COALESCE(last_name, ''),
    phone = COALESCE(phone, ''),
    address = COALESCE(address, ''),
    city = COALESCE(city, ''),
    is_active = COALESCE(is_active, 1)
WHERE first_name IS NULL OR last_name IS NULL OR is_active IS NULL;

-- Show updated table structure
DESCRIBE users;