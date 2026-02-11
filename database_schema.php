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
if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    die("Failed to create database: " . $conn->error);
}
$conn->select_db($database);
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
    remember_token VARCHAR(255) DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sqlUsers)) {
    $error_users = "Failed to create users table: " . $conn->error;
} else {
    $error_users = "";
}
$sqlCategories = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','inactive') DEFAULT 'active',
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sqlCategories)) {
    $error_categories = "Failed to create categories table: " . $conn->error;
} else {
    $error_categories = "";
}

$sqlProducts = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','inactive') DEFAULT 'active',
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sqlProducts)) {
    $error_products = "Failed to create products table: " . $conn->error;
} else {
    $error_products = "";
}

$sqlOrders = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    order_date DATE NOT NULL,
    status ENUM('pending','processing','completed','cancelled') DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_product (product_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sqlOrders)) {
    $error_orders = "Failed to create orders table: " . $conn->error;
} else {
    $error_orders = "";
}

$sqlPayments = "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','credit_card','debit_card','bank_transfer','online_gateway') DEFAULT 'cash',
    payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100) UNIQUE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_customer (customer_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sqlPayments)) {
    $error_payments = "Failed to create payments table: " . $conn->error;
} else {
    $error_payments = "";
}

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
    $admin_created = true;
} else {
    $admin_created = false;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SME System - Database Schema & Relationships</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .status-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .status-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .status-card:hover {
            transform: translateY(-5px);
        }
        
        .status-card.success {
            border-left: 5px solid #10b981;
        }
        
        .status-card.error {
            border-left: 5px solid #ef4444;
        }
        
        .status-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        
        .status-card p {
            color: #666;
            font-size: 0.95em;
            line-height: 1.6;
        }
        
        .status-icon {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
            font-weight: bold;
            color: white;
        }
        
        .status-icon.success {
            background: #10b981;
        }
        
        .status-icon.error {
            background: #ef4444;
        }
        
        .schema-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .schema-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8em;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .table-card {
            background: #f8f9fa;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .table-card:hover {
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.2);
            transform: translateY(-3px);
        }
        
        .table-card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.3em;
            display: flex;
            align-items: center;
        }
        
        .table-icon {
            width: 30px;
            height: 30px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .table-card ul {
            list-style: none;
        }
        
        .table-card li {
            padding: 8px 0;
            color: #555;
            font-size: 0.95em;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .table-card li:last-child {
            border-bottom: none;
        }
        
        .field-type {
            color: #764ba2;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .relationships {
            background: linear-gradient(135deg, #e0e7ff 0%, #f3e8ff 100%);
            padding: 25px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .relationships h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        
        .relationship-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #764ba2;
            display: flex;
            align-items: center;
        }
        
        .relationship-arrow {
            color: #764ba2;
            font-weight: bold;
            margin: 0 15px;
            font-size: 1.2em;
        }
        
        .relation-text {
            color: #555;
            flex: 1;
        }
        
        .diagram {
            background: white;
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
            overflow-x: auto;
        }
        
        .entity {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 15px 25px;
            margin: 10px;
            border-radius: 6px;
            font-weight: bold;
            min-width: 150px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .entity.users {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .entity.products {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .entity.categories {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .entity.orders {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: #333;
        }
        
        .entity.payments {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: #333;
        }
        
        .sql-code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            line-height: 1.6;
        }
        
        .sql-code pre {
            margin: 0;
        }
        
        .keyword {
            color: #66d9ef;
            font-weight: bold;
        }
        
        .string {
            color: #e6db74;
        }
        
        .comment {
            color: #75715e;
        }
        
        .success-message {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        
        .footer {
            text-align: center;
            color: white;
            padding: 20px;
            margin-top: 40px;
        }
        
        .nav-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #f0f0f0;
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 1.8em;
            }
            
            .tables-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🗄️ SME System - Database Schema</h1>
            <p>Comprehensive database with 5 interconnected tables</p>
        </header>
        
        <!-- Status Section -->
        <div class="status-container">
            <div class="status-card <?php echo $error_users ? 'error' : 'success'; ?>">
                <h3><span class="status-icon <?php echo $error_users ? 'error' : 'success'; ?>">✓</span>Users Table</h3>
                <p><?php echo $error_users ? $error_users : '✓ Created successfully'; ?></p>
            </div>
            
            <div class="status-card <?php echo $error_categories ? 'error' : 'success'; ?>">
                <h3><span class="status-icon <?php echo $error_categories ? 'error' : 'success'; ?>">✓</span>Categories Table</h3>
                <p><?php echo $error_categories ? $error_categories : '✓ Created successfully'; ?></p>
            </div>
            
            <div class="status-card <?php echo $error_products ? 'error' : 'success'; ?>">
                <h3><span class="status-icon <?php echo $error_products ? 'error' : 'success'; ?>">✓</span>Products Table</h3>
                <p><?php echo $error_products ? $error_products : '✓ Created successfully'; ?></p>
            </div>
            
            <div class="status-card <?php echo $error_orders ? 'error' : 'success'; ?>">
                <h3><span class="status-icon <?php echo $error_orders ? 'error' : 'success'; ?>">✓</span>Orders Table</h3>
                <p><?php echo $error_orders ? $error_orders : '✓ Created successfully'; ?></p>
            </div>
            
            <div class="status-card <?php echo $error_payments ? 'error' : 'success'; ?>">
                <h3><span class="status-icon <?php echo $error_payments ? 'error' : 'success'; ?>">✓</span>Payments Table</h3>
                <p><?php echo $error_payments ? $error_payments : '✓ Created successfully'; ?></p>
            </div>
        </div>
        
        <?php if ($admin_created): ?>
        <div class="success-message">
            ✓ Default admin account created: <strong>admin / admin123</strong>
        </div>
        <?php endif; ?>
        
        <!-- Schema Details Section -->
        <div class="schema-section">
            <h2>📋 Database Tables Overview</h2>
            
            <div class="tables-grid">
                <!-- Users Table -->
                <div class="table-card">
                    <h3><span class="table-icon">1</span>Users<span class="field-type">(Customer & Admin)</span></h3>
                    <ul>
                        <li><strong>id</strong> <span class="field-type">INT, PK</span></li>
                        <li><strong>username</strong> <span class="field-type">VARCHAR, UNIQUE</span></li>
                        <li><strong>password</strong> <span class="field-type">VARCHAR</span></li>
                        <li><strong>full_name</strong> <span class="field-type">VARCHAR</span></li>
                        <li><strong>email</strong> <span class="field-type">VARCHAR, UNIQUE</span></li>
                        <li><strong>role</strong> <span class="field-type">ENUM (admin, customer)</span></li>
                        <li><strong>phone</strong> <span class="field-type">VARCHAR</span></li>
                        <li><strong>address</strong> <span class="field-type">TEXT</span></li>
                        <li><strong>status</strong> <span class="field-type">ENUM (active, inactive)</span></li>
                    </ul>
                </div>
                
                <!-- Categories Table -->
                <div class="table-card">
                    <h3><span class="table-icon">2</span>Categories</h3>
                    <ul>
                        <li><strong>id</strong> <span class="field-type">INT, PK</span></li>
                        <li><strong>name</strong> <span class="field-type">VARCHAR, UNIQUE</span></li>
                        <li><strong>description</strong> <span class="field-type">TEXT</span></li>
                        <li><strong>status</strong> <span class="field-type">ENUM (active, inactive)</span></li>
                        <li><strong>created_at</strong> <span class="field-type">TIMESTAMP</span></li>
                    </ul>
                </div>
                
                <!-- Products Table -->
                <div class="table-card">
                    <h3><span class="table-icon">3</span>Products</h3>
                    <ul>
                        <li><strong>id</strong> <span class="field-type">INT, PK</span></li>
                        <li><strong>category_id</strong> <span class="field-type">INT, FK</span></li>
                        <li><strong>name</strong> <span class="field-type">VARCHAR</span></li>
                        <li><strong>price</strong> <span class="field-type">DECIMAL</span></li>
                        <li><strong>quantity</strong> <span class="field-type">INT</span></li>
                        <li><strong>description</strong> <span class="field-type">TEXT</span></li>
                        <li><strong>image</strong> <span class="field-type">VARCHAR</span></li>
                        <li><strong>status</strong> <span class="field-type">ENUM (active, inactive)</span></li>
                    </ul>
                </div>
                
                <!-- Orders Table -->
                <div class="table-card">
                    <h3><span class="table-icon">4</span>Orders</h3>
                    <ul>
                        <li><strong>id</strong> <span class="field-type">INT, PK</span></li>
                        <li><strong>customer_id</strong> <span class="field-type">INT, FK → users</span></li>
                        <li><strong>product_id</strong> <span class="field-type">INT, FK → products</span></li>
                        <li><strong>quantity</strong> <span class="field-type">INT</span></li>
                        <li><strong>unit_price</strong> <span class="field-type">DECIMAL</span></li>
                        <li><strong>total_price</strong> <span class="field-type">DECIMAL</span></li>
                        <li><strong>order_date</strong> <span class="field-type">DATE</span></li>
                        <li><strong>status</strong> <span class="field-type">ENUM</span></li>
                    </ul>
                </div>
                
                <!-- Payments Table -->
                <div class="table-card">
                    <h3><span class="table-icon">5</span>Payments</h3>
                    <ul>
                        <li><strong>id</strong> <span class="field-type">INT, PK</span></li>
                        <li><strong>order_id</strong> <span class="field-type">INT, FK → orders</span></li>
                        <li><strong>customer_id</strong> <span class="field-type">INT, FK → users</span></li>
                        <li><strong>amount</strong> <span class="field-type">DECIMAL</span></li>
                        <li><strong>payment_method</strong> <span class="field-type">ENUM</span></li>
                        <li><strong>payment_status</strong> <span class="field-type">ENUM</span></li>
                        <li><strong>transaction_id</strong> <span class="field-type">VARCHAR, UNIQUE</span></li>
                        <li><strong>payment_date</strong> <span class="field-type">DATETIME</span></li>
                    </ul>
                </div>
            </div>
            
            <!-- Relationships Section -->
            <div class="relationships">
                <h3>🔗 Table Relationships (Referential Integrity)</h3>
                
                <div class="relationship-item">
                    <span class="entity users">USERS</span>
                    <span class="relationship-arrow">1 → ∞</span>
                    <span class="entity orders">ORDERS</span>
                    <div class="relation-text" style="flex: initial; margin-left: 10px;">
                        <strong>users.id</strong> → <strong>orders.customer_id</strong>
                        <br/><small>One user can have many orders</small>
                    </div>
                </div>
                
                <div class="relationship-item">
                    <span class="entity products">PRODUCTS</span>
                    <span class="relationship-arrow">1 → ∞</span>
                    <span class="entity orders">ORDERS</span>
                    <div class="relation-text" style="flex: initial; margin-left: 10px;">
                        <strong>products.id</strong> → <strong>orders.product_id</strong>
                        <br/><small>One product can be in many orders</small>
                    </div>
                </div>
                
                <div class="relationship-item">
                    <span class="entity categories">CATEGORIES</span>
                    <span class="relationship-arrow">1 → ∞</span>
                    <span class="entity products">PRODUCTS</span>
                    <div class="relation-text" style="flex: initial; margin-left: 10px;">
                        <strong>categories.id</strong> → <strong>products.category_id</strong>
                        <br/><small>One category has many products</small>
                    </div>
                </div>
                
                <div class="relationship-item">
                    <span class="entity orders">ORDERS</span>
                    <span class="relationship-arrow">1 → ∞</span>
                    <span class="entity payments">PAYMENTS</span>
                    <div class="relation-text" style="flex: initial; margin-left: 10px;">
                        <strong>orders.id</strong> → <strong>payments.order_id</strong>
                        <br/><small>One order can have many payment records</small>
                    </div>
                </div>
                
                <div class="relationship-item">
                    <span class="entity users">USERS</span>
                    <span class="relationship-arrow">1 → ∞</span>
                    <span class="entity payments">PAYMENTS</span>
                    <div class="relation-text" style="flex: initial; margin-left: 10px;">
                        <strong>users.id</strong> → <strong>payments.customer_id</strong>
                        <br/><small>One customer can have many payment records</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ER Diagram Section -->
        <div class="schema-section">
            <h2>🎨 Entity Relationship Diagram</h2>
            
            <div class="diagram">
                <svg viewBox="0 0 1200 600" style="width:100%; height: auto; min-height: 400px;">
                    <!-- Define arrows -->
                    <defs>
                        <marker id="arrowhead" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                            <polygon points="0 0, 10 3, 0 6" fill="#667eea" />
                        </marker>
                    </defs>
                    
                    <!-- USERS Box -->
                    <rect x="50" y="50" width="200" height="150" fill="#667eea" rx="8" stroke="#333" stroke-width="2"/>
                    <text x="150" y="85" text-anchor="middle" fill="white" font-weight="bold" font-size="16">USERS</text>
                    <line x1="50" y1="100" x2="250" y2="100" stroke="white" stroke-width="1"/>
                    <text x="60" y="120" fill="white" font-size="12">• id (PK)</text>
                    <text x="60" y="140" fill="white" font-size="12">• username</text>
                    <text x="60" y="160" fill="white" font-size="12">• email</text>
                    <text x="60" y="180" fill="white" font-size="12">• role, password</text>
                    
                    <!-- CATEGORIES Box -->
                    <rect x="500" y="50" width="200" height="150" fill="#4facfe" rx="8" stroke="#333" stroke-width="2"/>
                    <text x="600" y="85" text-anchor="middle" fill="white" font-weight="bold" font-size="16">CATEGORIES</text>
                    <line x1="500" y1="100" x2="700" y2="100" stroke="white" stroke-width="1"/>
                    <text x="510" y="120" fill="white" font-size="12">• id (PK)</text>
                    <text x="510" y="140" fill="white" font-size="12">• name</text>
                    <text x="510" y="160" fill="white" font-size="12">• description</text>
                    <text x="510" y="180" fill="white" font-size="12">• status</text>
                    
                    <!-- PRODUCTS Box -->
                    <rect x="950" y="50" width="200" height="150" fill="#f093fb" rx="8" stroke="#333" stroke-width="2"/>
                    <text x="1050" y="85" text-anchor="middle" fill="white" font-weight="bold" font-size="16">PRODUCTS</text>
                    <line x1="950" y1="100" x2="1150" y2="100" stroke="white" stroke-width="1"/>
                    <text x="960" y="120" fill="white" font-size="12">• id (PK)</text>
                    <text x="960" y="140" fill="white" font-size="12">• category_id (FK)</text>
                    <text x="960" y="160" fill="white" font-size="12">• name, price</text>
                    <text x="960" y="180" fill="white" font-size="12">• quantity</text>
                    
                    <!-- ORDERS Box -->
                    <rect x="300" y="350" width="220" height="150" fill="#43e97b" rx="8" stroke="#333" stroke-width="2"/>
                    <text x="410" y="385" text-anchor="middle" fill="#333" font-weight="bold" font-size="16">ORDERS</text>
                    <line x1="300" y1="400" x2="520" y2="400" stroke="#333" stroke-width="1"/>
                    <text x="310" y="420" fill="#333" font-size="12">• id (PK)</text>
                    <text x="310" y="440" fill="#333" font-size="12">• customer_id (FK)</text>
                    <text x="310" y="460" fill="#333" font-size="12">• product_id (FK)</text>
                    <text x="310" y="480" fill="#333" font-size="12">• quantity, total_price</text>
                    
                    <!-- PAYMENTS Box -->
                    <rect x="700" y="350" width="220" height="150" fill="#fa709a" rx="8" stroke="#333" stroke-width="2"/>
                    <text x="810" y="385" text-anchor="middle" fill="white" font-weight="bold" font-size="16">PAYMENTS</text>
                    <line x1="700" y1="400" x2="920" y2="400" stroke="white" stroke-width="1"/>
                    <text x="710" y="420" fill="white" font-size="12">• id (PK)</text>
                    <text x="710" y="440" fill="white" font-size="12">• order_id (FK)</text>
                    <text x="710" y="460" fill="white" font-size="12">• customer_id (FK)</text>
                    <text x="710" y="480" fill="white" font-size="12">• amount, payment_method</text>
                    
                    <!-- Relationship Lines -->
                    <!-- USERS to ORDERS -->
                    <path d="M 150 200 Q 250 280 350 350" stroke="#667eea" stroke-width="2" fill="none" marker-end="url(#arrowhead)"/>
                    <text x="220" y="260" fill="#667eea" font-size="11" font-weight="bold">1:∞</text>
                    
                    <!-- CATEGORIES to PRODUCTS -->
                    <path d="M 700 125 Q 825 125 950 125" stroke="#4facfe" stroke-width="2" fill="none" marker-end="url(#arrowhead)"/>
                    <text x="820" y="120" fill="#4facfe" font-size="11" font-weight="bold">1:∞</text>
                    
                    <!-- PRODUCTS to ORDERS -->
                    <path d="M 1050 200 Q 850 280 470 350" stroke="#f093fb" stroke-width="2" fill="none" marker-end="url(#arrowhead)"/>
                    <text x="800" y="260" fill="#f093fb" font-size="11" font-weight="bold">1:∞</text>
                    
                    <!-- ORDERS to PAYMENTS -->
                    <path d="M 520 425 Q 600 425 700 425" stroke="#43e97b" stroke-width="2" fill="none" marker-end="url(#arrowhead)"/>
                    <text x="610" y="420" fill="#43e97b" font-size="11" font-weight="bold">1:∞</text>
                    
                    <!-- USERS to PAYMENTS -->
                    <path d="M 250 200 Q 450 280 750 350" stroke="#667eea" stroke-width="2" fill="none" marker-end="url(#arrowhead)"/>
                    <text x="500" y="260" fill="#667eea" font-size="11" font-weight="bold">1:∞</text>
                </svg>
            </div>
            
            <p style="text-align: center; color: #666; margin-top: 20px; font-style: italic;">
                The diagram shows all 5 tables and their relationships. Each arrow indicates a one-to-many (1:∞) relationship with referential integrity.
            </p>
        </div>
        
        <!-- Summary Stats -->
        <div class="schema-section">
            <h2>📊 Database Summary</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div style="background: #f0f0f0; padding: 20px; border-radius: 8px; text-align: center;">
                    <h4 style="color: #667eea; font-size: 2em; margin: 10px 0;">5</h4>
                    <p style="color: #666;">Total Tables</p>
                </div>
                
                <div style="background: #f0f0f0; padding: 20px; border-radius: 8px; text-align: center;">
                    <h4 style="color: #764ba2; font-size: 2em; margin: 10px 0;">5</h4>
                    <p style="color: #666;">Foreign Key Relationships</p>
                </div>
                
                <div style="background: #f0f0f0; padding: 20px; border-radius: 8px; text-align: center;">
                    <h4 style="color: #f5576c; font-size: 2em; margin: 10px 0;">InnoDB</h4>
                    <p style="color: #666;">Engine (ACID Compliant)</p>
                </div>
                
                <div style="background: #f0f0f0; padding: 20px; border-radius: 8px; text-align: center;">
                    <h4 style="color: #45b6fe; font-size: 2em; margin: 10px 0;">utf8mb4</h4>
                    <p style="color: #666;">Character Set</p>
                </div>
            </div>
        </div>
        
        <!-- Navigation Buttons -->
        <div class="nav-buttons">
            <a href="index.php" class="btn btn-primary">Go to Application</a>
            <a href="login.php" class="btn btn-secondary">Login</a>
        </div>
        
        <footer class="footer">
            <p>✓ SME System Database Successfully Initialized</p>
            <p style="font-size: 0.9em; margin-top: 10px;">Database: <strong>sme_system</strong> | Charset: <strong>utf8mb4</strong></p>
        </footer>
    </div>
</body>
</html>
