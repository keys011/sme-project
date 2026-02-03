<?php
// fix_database.php - Add missing phone column

$host = "localhost";
$user = "root";
$password = "";
$database = "sme_system";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>🔧 Fixing Database Schema</h2>";

// Check if phone column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
if ($result->num_rows == 0) {
    // Add phone column
    $sql = "ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER email";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✅ Added 'phone' column to users table</p>";
    } else {
        echo "<p style='color: red;'>❌ Error adding phone column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ 'phone' column already exists</p>";
}

// Check other tables
$tables = ['products', 'orders'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        echo "<p style='color: red;'>❌ Table '$table' is missing!</p>";
        
        if ($table == 'products') {
            $sql = "CREATE TABLE products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                quantity INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->query($sql);
            echo "<p style='color: green;'>✅ Created products table</p>";
        }
        
        if ($table == 'orders') {
            $sql = "CREATE TABLE orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT,
                product_id INT,
                quantity INT,
                total_price DECIMAL(10,2),
                order_date DATE,
                status ENUM('pending','completed','cancelled') DEFAULT 'pending'
            )";
            $conn->query($sql);
            echo "<p style='color: green;'>✅ Created orders table</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    }
}

echo "<hr>";
echo "<h3>🎉 Database Fix Complete!</h3>";
echo "<p><a href='index.php' style='background: #0066cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Application</a></p>";

$conn->close();
?>