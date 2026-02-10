-- ============================================
-- SME System Database - SQL Schema
-- Database: sme_system
-- Created: February 10, 2026
-- ============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS `sme_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sme_system`;

-- ============================================
-- Table 1: USERS (Customers & Admins)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL UNIQUE,
    `role` ENUM('admin','customer') DEFAULT 'customer',
    `phone` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `remember_token` VARCHAR(255) DEFAULT NULL,
    INDEX `idx_email` (`email`),
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Table 2: CATEGORIES
-- ============================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active','inactive') DEFAULT 'active',
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Table 3: PRODUCTS
-- ============================================
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `quantity` INT NOT NULL DEFAULT 0,
    `description` TEXT DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active','inactive') DEFAULT 'active',
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_category` (`category_id`),
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Table 4: ORDERS
-- ============================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `order_date` DATE NOT NULL,
    `status` ENUM('pending','processing','completed','cancelled') DEFAULT 'pending',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_product` (`product_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- Table 5: PAYMENTS
-- ============================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `customer_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('cash','credit_card','debit_card','bank_transfer','online_gateway') DEFAULT 'cash',
    `payment_status` ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    `transaction_id` VARCHAR(100) UNIQUE DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `payment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_order` (`order_id`),
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_payment_status` (`payment_status`),
    INDEX `idx_payment_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- INSERT DEFAULT ADMIN USER
-- ============================================
INSERT IGNORE INTO `users` (`username`, `password`, `full_name`, `email`, `role`, `status`) 
VALUES ('admin', '$2y$10$HASHED_PASSWORD_HERE', 'Administrator', 'admin@example.com', 'admin', 'active');

-- ============================================
-- SAMPLE DATA QUERIES
-- ============================================

-- View all relationships
SELECT 
    TABLE_NAME, 
    COLUMN_NAME, 
    CONSTRAINT_NAME, 
    REFERENCED_TABLE_NAME, 
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'sme_system' 
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Get all tables in database
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'sme_system';

-- ============================================
-- USEFUL QUERIES FOR OPERATIONS
-- ============================================

-- Get customer orders with product details
SELECT 
    o.id AS order_id,
    u.full_name AS customer_name,
    p.name AS product_name,
    o.quantity,
    o.unit_price,
    o.total_price,
    o.order_date,
    o.status
FROM orders o
JOIN users u ON o.customer_id = u.id
JOIN products p ON o.product_id = p.id
ORDER BY o.order_date DESC;

-- Get payment details for specific order
SELECT 
    pay.id AS payment_id,
    pay.order_id,
    u.full_name AS customer_name,
    pay.amount,
    pay.payment_method,
    pay.payment_status,
    pay.payment_date,
    pay.transaction_id
FROM payments pay
JOIN users u ON pay.customer_id = u.id
WHERE pay.payment_status = 'completed'
ORDER BY pay.payment_date DESC;

-- Get products by category
SELECT 
    c.name AS category_name,
    p.name AS product_name,
    p.price,
    p.quantity,
    p.status
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
ORDER BY c.name, p.name;

-- Get customer order history
SELECT 
    u.username,
    u.email,
    COUNT(o.id) AS total_orders,
    SUM(o.total_price) AS total_spent,
    o.status
FROM users u
LEFT JOIN orders o ON u.id = o.customer_id
GROUP BY u.id, u.username, u.email, o.status
HAVING u.role = 'customer';

-- ============================================
-- RELATIONSHIPS SUMMARY
-- ============================================

/*
RELATIONSHIP 1: CATEGORIES (1) ──→ (∞) PRODUCTS
    - One category can have many products
    - When category deleted: product category reference set to NULL
    - Action: ON DELETE SET NULL

RELATIONSHIP 2: PRODUCTS (1) ──→ (∞) ORDERS
    - One product can be in many orders
    - When product deleted: associated orders are deleted
    - Action: ON DELETE CASCADE

RELATIONSHIP 3: USERS (1) ──→ (∞) ORDERS
    - One customer can place many orders
    - When customer deleted: all their orders are deleted
    - Action: ON DELETE CASCADE

RELATIONSHIP 4: ORDERS (1) ──→ (∞) PAYMENTS
    - One order can have multiple payment records
    - When order deleted: all payment records deleted
    - Action: ON DELETE CASCADE

RELATIONSHIP 5: USERS (1) ──→ (∞) PAYMENTS
    - One customer can have multiple payments
    - When customer deleted: all payment records deleted
    - Action: ON DELETE CASCADE
*/

-- ============================================
-- END OF SCHEMA
-- ============================================
