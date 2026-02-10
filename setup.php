<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'sme_system';

$conn = new mysqli($host, $user, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
if (! $conn->query("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    die("Failed to create database: " . $conn->error);
}

$conn->select_db($database);

// Create users table
$sqlUsers = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL UNIQUE,
    role ENUM('admin','customer') DEFAULT 'customer',
    phone VARCHAR(50) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','inactive') DEFAULT 'active',
    remember_token VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (! $conn->query($sqlUsers)) {
    die("Failed to create users table: " . $conn->error);
}

// Create products table
$sqlProducts = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (! $conn->query($sqlProducts)) {
    die("Failed to create products table: " . $conn->error);
}

// Create categories table
$sqlCategories = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (! $conn->query($sqlCategories)) {
    die("Failed to create categories table: " . $conn->error);
}

// Update products table to include category relationship
$sqlAlterProducts = "ALTER TABLE products ADD COLUMN IF NOT EXISTS category_id INT DEFAULT NULL AFTER image";
$conn->query($sqlAlterProducts);
$sqlAlterForeignKey = "ALTER TABLE products ADD CONSTRAINT IF NOT EXISTS fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL";
$conn->query($sqlAlterForeignKey);

// Create orders table
$sqlOrders = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    order_date DATE NOT NULL,
    status ENUM('pending','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (! $conn->query($sqlOrders)) {
    die("Failed to create orders table: " . $conn->error);
}

// Create payments table
$sqlPayments = "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','card','bank_transfer','online') DEFAULT 'cash',
    payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100) DEFAULT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_transaction (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (! $conn->query($sqlPayments)) {
    die("Failed to create payments table: " . $conn->error);
}

// Ensure uploads directory exists
$uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (! is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

// Insert default admin user if not exists
$adminUser = 'admin';
$adminPass = 'admin123';
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param('s', $adminUser);
$check->execute();
$res = $check->get_result();
if ($res->num_rows === 0) {
    $hashed = password_hash($adminPass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, 'admin', 'active')");
    $full = 'Administrator';
    $email = 'admin@example.com';
    $stmt->bind_param('ssss', $adminUser, $hashed, $full, $email);
    $stmt->execute();
}

echo "<div style='font-family: Arial, Helvetica, sans-serif; max-width:800px; margin:40px auto; background:#fff; padding:20px; border-radius:8px;'>";
echo "<h2>Setup Complete</h2>";
echo "<p>Database <strong>$database</strong> and tables were created (if they did not already exist).</p>";
echo "<p>Default admin account: <strong>admin</strong> / <strong>admin123</strong></p>";
echo "<p><a href='index.php'>Go to the application</a></p>";
echo "</div>";

$conn->close();

?>
