<?php

$host = "localhost";
$user = "root";
$password = "";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup SME System</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .btn { background: #0066cc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1> SME System Setup</h1>
";

// Create connection
$conn = new mysqli($host, $user, $password);

if ($conn->connect_error) {
    die("<p class='error'>Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p>Connected to MySQL server</p>";

// Create database
if ($conn->query("CREATE DATABASE IF NOT EXISTS sme_system")) {
    echo "<p>Database 'sme_system' created</p>";
} else {
    echo "<p class='error'>Error creating database: " . $conn->error . "</p>";
}

// Select database
$conn->select_db("sme_system");

// Create users table
// ... (code ya mwanzo) ...

// Create users table (WITH PHONE COLUMN)
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),  // ADDED THIS LINE
    role ENUM('admin','customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// ... (rest ya code) ...

if ($conn->query($sql)) {
    echo "<p>Users table created</p>";
} else {
    echo "<p class='error'>Error creating users table: " . $conn->error . "</p>";
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "<p>Products table created</p>";
} else {
    echo "<p class='error'>Error creating products table: " . $conn->error . "</p>";
}

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    product_id INT,
    quantity INT,
    total_price DECIMAL(10,2),
    order_date DATE,
    status ENUM('pending','completed','cancelled') DEFAULT 'pending'
)";

if ($conn->query($sql)) {
    echo "<p>Orders table created</p>";
} else {
    echo "<p class='error'>Error creating orders table: " . $conn->error . "</p>";
}

// Add admin user
$password_hash = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("DELETE FROM users WHERE username='admin'");

$sql = "INSERT INTO users (username, password, full_name, email, role) 
        VALUES ('admin', '$password_hash', 'System Administrator', 'admin@sme.com', 'admin')";

if ($conn->query($sql)) {
    echo "<p>Admin user created</p>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
} else {
    echo "<p class='error'>Error creating admin: " . $conn->error . "</p>";
}

// Add sample products
$products = [
    ['Laptop Dell XPS 13', 1299.99, 15],
    ['Wireless Mouse Logitech', 29.99, 50],
    ['Mechanical Keyboard RGB', 89.99, 30],
    ['27-inch 4K Monitor', 349.99, 20],
    ['Webcam HD 1080p', 59.99, 40],
    ['USB-C Hub 7-in-1', 39.99, 60],
    ['External SSD 1TB', 129.99, 25],
    ['Noise Cancelling Headphones', 199.99, 35]
];

foreach ($products as $product) {
    $conn->query("INSERT IGNORE INTO products (name, price, quantity) 
                  VALUES ('$product[0]', $product[1], $product[2])");
}
echo "<p>Sample products added</p>";

echo "<hr>
    <h2>Setup Complete!</h2>
    <p>Your SME Management System is ready to use.</p>
    <p style='margin-top: 30px;'>
        <a href='index.php' class='btn'>Launch Application</a>
    </p>
";

$conn->close();

echo "
    </div>
</body>
</html>
";
?>