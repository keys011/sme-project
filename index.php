<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$password = "";
$database = "sme_system";
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("
        <div style='padding: 20px; background: #ffcccc; border-radius: 5px;'>
            <h2>⚠️ Database Connection Error</h2>
            <p><strong>Error:</strong> " . $conn->connect_error . "</p>
            <p>Please run the setup first: <a href='setup.php'>setup.php</a></p>
        </div>
    ");
}

session_start();

function loginUser($username, $password) {
    global $conn;
    
    $username = trim($conn->real_escape_string($username));
    
    $sql = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'] ? $user['full_name'] : $user['username'];
            return true;
        }
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'customer';
}

function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    die("
        <div style='padding: 20px; background: #fff3cd; border-radius: 5px;'>
            <h2>⚠️ Database Not Setup</h2>
            <p>Database tables not found. Please run setup first.</p>
            <p><a href='setup.php' style='background: #0066cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run Setup</a></p>
        </div>
    ");
}

$check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'image'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL");
}

if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

$login_error = "";
$register_error = "";
$register_success = "";
$product_success = "";
$order_success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        
        if (loginUser($username, $password)) {
            header("Location: ?page=home");
            exit();
        } else {
            $login_error = "Invalid username or password!";
        }
    }
    
    if (isset($_POST['register'])) {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        
        $check = $conn->query("SELECT id FROM users WHERE username='$username'");
        if ($check->num_rows > 0) {
            $register_error = "Username already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, password, full_name, email, role) 
                    VALUES ('$username', '$hashed_password', '$full_name', '$email', 'customer')";
            
            if ($conn->query($sql)) {
                $register_success = "Registration successful! You can now login.";
            } else {
                $register_error = "Error: " . $conn->error;
            }
        }
    }
    
    if (isset($_POST['add_product']) && isAdmin()) {
        $name = sanitize($_POST['name']);
        $price = floatval($_POST['price']);
        $quantity = intval($_POST['quantity']);
        
        $image_path = null;
        
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == UPLOAD_ERR_OK) {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $file_name = $_FILES['product_image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $file_size = $_FILES['product_image']['size'];
            
            if (in_array($file_ext, $allowed_extensions)) {
                if ($file_size <= 2097152)
                     { 
                    $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
                    $upload_path = 'uploads/' . $new_filename;
                    
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                        $image_path = $upload_path;
                    }
                }
            }
        }
        
        if ($image_path) {
            $sql = "INSERT INTO products (name, price, quantity, image) 
                    VALUES ('$name', $price, $quantity, '$image_path')";
        } else {
            $sql = "INSERT INTO products (name, price, quantity) 
                    VALUES ('$name', $price, $quantity)";
        }
        
        if ($conn->query($sql)) {
            $product_success = "Product added successfully!";
        }
    }
    
    if (isset($_POST['place_order']) && isCustomer()) {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $customer_id = $_SESSION['user_id'];
        
        $product_result = $conn->query("SELECT price FROM products WHERE id=$product_id");
        if ($product_result->num_rows > 0) {
            $product = $product_result->fetch_assoc();
            $total_price = $product['price'] * $quantity;
            
            $sql = "INSERT INTO orders (customer_id, product_id, quantity, total_price, order_date) 
                    VALUES ($customer_id, $product_id, $quantity, $total_price, CURDATE())";
            
            if ($conn->query($sql)) {
                $order_success = "Order placed successfully!";
            }
        }
    }
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SME Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            margin-bottom: 25px;
            text-align: center;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .menu {
            background: white;
            padding: 18px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .nav-links {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #555;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-links a:hover {
            background: #667eea;
            color: white;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            color: white;
        }
        
        .badge.admin {
            background: #dc3545;
        }
        
        .badge.customer {
            background: #28a745;
        }
        
        .content {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            min-height: 500px;
        }
        
        h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #f0f0f0;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        .alert.info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 5px solid #17a2b8;
        }
        
        .form-group {
            margin-bottom: 20px;
        }

       
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }
        
        button, .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .login-container {
            display: flex;
            gap: 30px;
            margin-top: 30px;
        }
        
        .login-box {
            flex: 1;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }
        
        .product-card {
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 10px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        
        .default-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2em;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: white;
        }
        
        .image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
            margin-top: 10px;
        }
        a {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>SME Management System</h1>
            <p>Complete Business Solution</p>
        </div>
        
        <!-- NAVIGATION MENU -->
        <div class="menu">
            <div class="nav-links">
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="?page=home">🏠 Dashboard</a>
                        <a href="?page=customers">👥 Customers</a>
                        <a href="?page=products">📦 Products</a>
                        <a href="?page=orders">📋 Orders</a>
                    <?php elseif (isCustomer()): ?>
                        <a href="?page=home">🏠 Dashboard</a>
                        <a href="?page=products">🛍️ Shop</a>
                        <a href="?page=myorders">📦 My Orders</a>
                    <?php endif; ?>
                    <a href="?page=logout" class="btn btn-danger" style="padding: 8px 15px; font-size: 14px;">🚪 Logout</a>
                <?php else: ?>
                    <a href="?page=home">🏠 Home</a>
                    <a href="?page=products">📦 Products</a>
                    <a href="?page=login">🔑 Login</a>
                    <a href="?page=register">📝 Register</a>
                <?php endif; ?>
            </div>
            
            <div class="user-info">
                <?php if (isLoggedIn()): ?>
                    <span style="font-weight: 600;"><?php echo $_SESSION['full_name']; ?></span>
                    <?php if (isAdmin()): ?>
                        <span class="badge admin">ADMIN</span>
                    <?php elseif (isCustomer()): ?>
                        <span class="badge customer">CUSTOMER</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        
        <div class="content">
            <?php
            if (!empty($login_error)) echo '<div class="alert error">' . $login_error . '</div>';
            if (!empty($register_error)) echo '<div class="alert error">' . $register_error . '</div>';
            if (!empty($register_success)) echo '<div class="alert success">' . $register_success . '</div>';
            if (!empty($product_success)) echo '<div class="alert success">' . $product_success . '</div>';
            if (!empty($order_success)) echo '<div class="alert success">' . $order_success . '</div>';
            if ($page == 'logout') {
                session_destroy();
                echo '<script>alert("Logged out successfully!"); window.location.href = "?page=home";</script>';
                exit();
            }
            
            switch($page):
                case 'home':
                    if (!isLoggedIn()):
            ?>
                        <h2>Welcome to SME Management System</h2>
                        <p class="alert info">Please login to access the system</p>
                        
                        <div class="login-container">
                            <div class="login-box">
                                <h3>👤 Customer Login</h3>
                                <form method="POST">
                                    <input type="hidden" name="login" value="1">
                                    
                                    <div class="form-group">
                                        <label>Username or Email</label>
                                        <input type="text" name="username" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input type="password" name="password" required>
                                    </div>
                                    
                                    <button type="submit" class="btn">Customer Login</button>
                                </form>
                                
                                <p style="margin-top: 20px;">
                                    Don't have an account? 
                                    <a href="?page=register">Register here</a>
                                </p>
                            </div>
                            
                            <div class="login-box">
                                <h3>Admin Login</h3>
                                <form method="POST">
                                    <input type="hidden" name="login" value="1">
                                    
                                    <div class="form-group">
                                        <label>Admin Username</label>
                                        <input type="text" name="username" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Admin Password</label>
                                        <input type="password" name="password" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-danger">Admin Login</button>
                                </form>
                                
                            </div>
                        </div>
            <?php
                    else:
                        if (isAdmin()):
                            $total_customers_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='customer'");
                            $total_customers = $total_customers_result ? $total_customers_result->fetch_assoc() : ['total' => 0];
                            
                            $total_products_result = $conn->query("SELECT COUNT(*) as total FROM products");
                            $total_products = $total_products_result ? $total_products_result->fetch_assoc() : ['total' => 0];
                            
                            $total_orders_result = $conn->query("SELECT COUNT(*) as total FROM orders");
                            $total_orders = $total_orders_result ? $total_orders_result->fetch_assoc() : ['total' => 0];
                            
                            $revenue_result = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE status='completed'");
                            $revenue = $revenue_result ? $revenue_result->fetch_assoc() : ['total' => 0];
            ?>
                            <h2>Admin Dashboard</h2>
                            <p>Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>
                            
                            <div class="dashboard-stats">
                                <div class="stat-card">
                                    <h3>Total Customers</h3>
                                    <div style="font-size: 2em; font-weight: bold; color: #333;">
                                        <?php echo $total_customers['total']; ?>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <h3>Total Products</h3>
                                    <div style="font-size: 2em; font-weight: bold; color: #333;">
                                        <?php echo $total_products['total']; ?>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <h3>Total Orders</h3>
                                    <div style="font-size: 2em; font-weight: bold; color: #333;">
                                        <?php echo $total_orders['total']; ?>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <h3>Total Revenue</h3>
                                    <div style="font-size: 2em; font-weight: bold; color: #333;">
                                        Tsh<?php echo number_format($revenue['total'] ?? 0, 2); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 30px;">
                                <h3>Quick Actions</h3>
                                <div style="display: flex; gap: 10px; margin-top: 15px;">
                                    <a href="?page=products" class="btn">📦 Manage Products</a>
                                    <a href="?page=customers" class="btn">👥 View Customers</a>
                                    <a href="?page=orders" class="btn">📋 View Orders</a>
                                </div>
                            </div>
            <?php
                        elseif (isCustomer()):
                            $customer_id = $_SESSION['user_id'];
                            
                            $my_orders_result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE customer_id=$customer_id");
                            $my_orders = $my_orders_result ? $my_orders_result->fetch_assoc() : ['total' => 0];
                            
                            $pending_orders_result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE customer_id=$customer_id AND status='pending'");
                            $pending_orders = $pending_orders_result ? $pending_orders_result->fetch_assoc() : ['total' => 0];
                            
                            $total_spent_result = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE customer_id=$customer_id AND status='completed'");
                            $total_spent = $total_spent_result ? $total_spent_result->fetch_assoc() : ['total' => 0];
            ?>
                            <h2>👤 Customer Dashboard</h2>
                            <p>Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>
                            
                            <div class="dashboard-stats">
                                <div class="stat-card">
                                    <h3>My Orders</h3>
                                    <div style="font-size: 2em; font-weight: bold; color: #333;">
                                        <?php echo $my_orders['total']; ?>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <h3>Pending Orders</h3>
                                    <div style="font-size: 2em; font-weight: bold; color: #333;">
                                        <?php echo $pending_orders['total']; ?>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <h3>Total Spent</h3>
                                    <div style="font-size: 2em; font-weight: bold; color: #333;">
                                        Tsh<?php echo number_format($total_spent['total'] ?? 0, 2); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 30px;">
                                <h3>Quick Actions</h3>
                                <div style="display: flex; gap: 10px; margin-top: 15px;">
                                    <a href="?page=products" class="btn btn-success">🛍️ Shop Products</a>
                                    <a href="?page=myorders" class="btn">📦 My Orders</a>
                                </div>
                            </div>
            <?php
                        endif;
                    endif;
                    break;
                    
                case 'login':
                    if (isLoggedIn()) {
                        echo '<script>window.location.href = "?page=home";</script>';
                        exit();
                    }
            ?>
                    <h2>🔑 Login</h2>
                    <form method="POST" style="max-width: 400px;">
                        <input type="hidden" name="login" value="1">
                        
                        <div class="form-group">
                            <label>Username or Email</label>
                            <input type="text" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required>
                        </div>
                        
                        <button type="submit" class="btn">Login</button>
                    </form>
                    
                    <p style="margin-top: 20px;">
                        Don't have an account? <a href="?page=register">Register here</a>
                    </p>
            <?php
                    break;
                    
                case 'register':
                    if (isLoggedIn()) {
                        echo '<script>window.location.href = "?page=home";</script>';
                        exit();
                    }
            ?>
                    <h2>📝 Register New Customer</h2>
                    <form method="POST" style="max-width: 500px;">
                        <input type="hidden" name="register" value="1">
                        
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Username *</label>
                            <input type="text" name="username" required>
                            <small style="color: #666;">Choose a unique username</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Phone Number (Optional)</label>
                            <input type="text" name="phone">
                            <small style="color: #666;">This field is optional</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" required>
                            <small style="color: #666;">Minimum 8 characters</small>
                        </div>
                        
                        <button type="submit" class="btn btn-success">Create Account</button>
                    </form>
                    
                    <p style="margin-top: 20px;">
                        Already have an account? <a href="?page=login">Login here</a>
                    </p>
            <?php
                    break;
                    
                case 'products':
                    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product']) && isAdmin()) {
                        $name = sanitize($_POST['name']);
                        $price = floatval($_POST['price']);
                        $quantity = intval($_POST['quantity']);
                        
                        $image_path = null;
                        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == UPLOAD_ERR_OK) {
                            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                            $file_name = $_FILES['product_image']['name'];
                            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            $file_size = $_FILES['product_image']['size'];
                            
                    
                            if (in_array($file_ext, $allowed_extensions)) {
                                if ($file_size <= 2097152) { 
                                    $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
                                    $upload_path = 'uploads/' . $new_filename;
                                    
                                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                                        $image_path = $upload_path;
                                    }
                                }
                            }
                        }
                        
                        if ($image_path) {
                            $sql = "INSERT INTO products (name, price, quantity, image) 
                                    VALUES ('$name', $price, $quantity, '$image_path')";
                        } else {
                            $sql = "INSERT INTO products (name, price, quantity) 
                                    VALUES ('$name', $price, $quantity)";
                        }
                        
                        if ($conn->query($sql)) {
                            $product_success = "Product added successfully!";
                        }
                    }
                    
                    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product']) && isAdmin()) {
                        $product_id = intval($_POST['product_id']);
                        $product_result = $conn->query("SELECT image FROM products WHERE id=$product_id");
                        if ($product_result->num_rows > 0) {
                            $product = $product_result->fetch_assoc();
                            if ($product['image'] && file_exists($product['image'])) {
                                unlink($product['image']);
                            }
                        }
                        
                        $sql = "DELETE FROM products WHERE id = $product_id";
                        
                        if ($conn->query($sql)) {
                            echo '<div class="alert success">Product deleted successfully!</div>';
                        } else {
                            echo '<div class="alert error">Error: ' . $conn->error . '</div>';
                        }
                    }
                    
                    // Handle order placement
                    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order']) && isCustomer()) {
                        $product_id = intval($_POST['product_id']);
                        $quantity = intval($_POST['quantity']);
                        $customer_id = $_SESSION['user_id'];
                        
                        // Get product price
                        $product_result = $conn->query("SELECT price FROM products WHERE id=$product_id");
                        if ($product_result && $product_result->num_rows > 0) {
                            $product = $product_result->fetch_assoc();
                            $total_price = $product['price'] * $quantity;
                            
                            $sql = "INSERT INTO orders (customer_id, product_id, quantity, total_price, order_date) 
                                    VALUES ($customer_id, $product_id, $quantity, $total_price, CURDATE())";
                            
                            if ($conn->query($sql)) {
                                echo '<div class="alert success">Order placed successfully!</div>';
                            }
                        }
                    }
            ?>
                    <h2>📦 Products</h2>
                    
                    <?php if (isAdmin()): ?>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                            <h3>Add New Product</h3>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="add_product" value="1">
                                
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label>Product Name *</label>
                                        <input type="text" name="name" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Price (Tsh) *</label>
                                        <input type="number" name="price" step="0.01" required>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label>Quantity *</label>
                                        <input type="number" name="quantity" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Product Image</label>
                                        <input type="file" name="product_image" accept="image/*" id="productImageInput">
                                        <small style="color: #666;">Upload JPG, PNG or GIF (max 2MB)</small>
                                        <div id="imagePreview"></div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success">Add Product</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <div class="products-grid">
                        <?php
                        $result = $conn->query("SELECT * FROM products ORDER BY name");
                        if ($result && $result->num_rows > 0):
                            while($row = $result->fetch_assoc()):
                        ?>
                            <div class="product-card">
                                <!-- Product Image Display -->
                                <div style="text-align: center;">
                                    <?php if (!empty($row['image']) && file_exists($row['image'])): ?>
                                        <img src="<?php echo $row['image']; ?>" 
                                             alt="<?php echo htmlspecialchars($row['name']); ?>"
                                             class="product-image">
                                    <?php else: ?>
                                        <div class="default-image">
                                            📦
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <div style="font-size: 1.5em; color: #28a745; margin: 10px 0;">
                                    Tsh<?php echo number_format($row['price'], 2); ?>
                                </div>
                                <div style="color: #666; margin: 10px 0;">
                                    Stock: <?php echo $row['quantity']; ?> units
                                </div>
                                
                                <?php if (isLoggedIn() && isCustomer()): ?>
                                    <form method="POST" style="margin-top: 15px;">
                                        <input type="hidden" name="place_order" value="1">
                                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                        
                                        <div style="display: flex; gap: 10px;">
                                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $row['quantity']; ?>" style="width: 80px;">
                                            <button type="submit" class="btn btn-success">Add to Cart</button>
                                        </div>
                                    </form>
                                <?php elseif (!isLoggedIn()): ?>
                                    <p style="color: #666; margin-top: 15px;">
                                        <a href="?page=login">Login</a> to purchase
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (isAdmin()): ?>
                                    <div style="margin-top: 15px; display: flex; gap: 10px;">
                                        <a href="?page=edit_product&id=<?php echo $row['id']; ?>" 
                                           class="btn" style="padding: 8px 15px; font-size: 14px;">✏️ Edit</a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_product" 
                                                    class="btn btn-danger" style="padding: 8px 15px; font-size: 14px;"
                                                    onclick="return confirm('Delete this product?')">🗑️ Delete</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                                <p style="color: #666;">No products found.</p>
                                <?php if (isAdmin()): ?>
                                    <p style="margin-top: 10px;">Add your first product using the form above.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
            <?php
                    break;
                    
                case 'edit_product':
                    if (!isAdmin()) {
                        echo '<div class="alert error">Admin only</div>';
                        break;
                    }
                    
                    $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                    
                    // Get product data
                    $product_result = $conn->query("SELECT * FROM products WHERE id = $product_id");
                    if (!$product_result || $product_result->num_rows == 0) {
                        echo '<div class="alert error">Product not found</div>';
                        break;
                    }
                    $product = $product_result->fetch_assoc();
                    
                    // Handle update
                    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
                        $name = sanitize($_POST['name']);
                        $price = floatval($_POST['price']);
                        $quantity = intval($_POST['quantity']);
                        
                        $sql = "UPDATE products SET 
                                name = '$name', 
                                price = $price, 
                                quantity = $quantity 
                                WHERE id = $product_id";
                        
                        if ($conn->query($sql)) {
                            echo '<div class="alert success">Product updated successfully!</div>';
                            // Refresh product data
                            $product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
                        } else {
                            echo '<div class="alert error">Error: ' . $conn->error . '</div>';
                        }
                    }
                    
                    // Handle delete
                    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
                        // Delete image file if exists
                        if ($product['image'] && file_exists($product['image'])) {
                            unlink($product['image']);
                        }
                        
                        $sql = "DELETE FROM products WHERE id = $product_id";
                        if ($conn->query($sql)) {
                            echo '<div class="alert success">Product deleted successfully!</div>';
                            echo '<script>setTimeout(() => window.location.href = "?page=products", 2000);</script>';
                            break;
                        } else {
                            echo '<div class="alert error">Error: ' . $conn->error . '</div>';
                        }
                    }
                    ?>
                    
                    <h2>✏️ Edit Product</h2>
                    
                    <!-- Display current product image -->
                    <div style="margin-bottom: 20px; text-align: center;">
                        <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
                            <img src="<?php echo $product['image']; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="max-width: 300px; max-height: 200px; object-fit: contain; border-radius: 8px;">
                            <p><small>Current Image</small></p>
                        <?php else: ?>
                            <div style="width: 300px; height: 200px; background: #f0f0f0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; color: #999;">
                                No Image
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" style="max-width: 500px;">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Price (Tsh)</label>
                            <input type="number" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" required>
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="submit" name="update_product" class="btn btn-success">Update Product</button>
                            <button type="submit" name="delete_product" class="btn btn-danger" 
                                    onclick="return confirm('Delete this product permanently?')">Delete Product</button>
                            <a href="?page=products" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                    
                    <div style="margin-top: 30px; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <h4>📊 Product Info</h4>
                        <p><strong>Product ID:</strong> <?php echo $product['id']; ?></p>
                        <?php if (!empty($product['image'])): ?>
                            <p><strong>Image Path:</strong> <?php echo $product['image']; ?></p>
                        <?php endif; ?>
                        <?php if (isset($product['created_at'])): ?>
                            <p><strong>Created:</strong> <?php echo $product['created_at']; ?></p>
                        <?php endif; ?>
                    </div>
                    <?php
                    break;
                    
                case 'myorders':
                    if (!isLoggedIn() || !isCustomer()) {
                        echo '<div class="alert error">Please login as customer to view orders</div>';
                        break;
                    }
                    
                    $customer_id = $_SESSION['user_id'];
                    $orders = $conn->query("
                        SELECT o.*, p.name as product_name, p.image as product_image 
                        FROM orders o 
                        JOIN products p ON o.product_id = p.id 
                        WHERE o.customer_id = $customer_id 
                        ORDER BY o.order_date DESC
                    ");
            ?>
                    <h2>📦 My Orders</h2>
                    
                    <?php if ($orders && $orders->num_rows > 0): ?>
                        <div class="products-grid">
                            <?php while($row = $orders->fetch_assoc()): ?>
                            <div class="product-card">
                                <!-- Product Image in Order -->
                                <div style="text-align: center;">
                                    <?php if (!empty($row['product_image']) && file_exists($row['product_image'])): ?>
                                        <img src="<?php echo $row['product_image']; ?>" 
                                             alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                                             class="product-image">
                                    <?php else: ?>
                                        <div class="default-image">
                                            📦
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                                <div style="color: #28a745; margin: 10px 0;">
                                    Quantity: <?php echo $row['quantity']; ?>
                                </div>
                                <div style="font-size: 1.2em; color: #333; margin: 10px 0;">
                                    Total: Tsh<?php echo number_format($row['total_price'], 2); ?>
                                </div>
                                <div style="color: #666; margin: 10px 0;">
                                    Date: <?php echo $row['order_date']; ?>
                                </div>
                                <div style="margin-top: 10px;">
                                    <?php 
                                    if ($row['status'] == 'pending') echo '<span style="background: orange; color: white; padding: 5px 10px; border-radius: 5px;">Pending</span>';
                                    elseif ($row['status'] == 'completed') echo '<span style="background: green; color: white; padding: 5px 10px; border-radius: 5px;">Completed</span>';
                                    else echo '<span style="background: red; color: white; padding: 5px 10px; border-radius: 5px;">Cancelled</span>';
                                    ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #666; text-align: center; padding: 40px;">
                            You have no orders yet. <a href="?page=products">Start shopping!</a>
                        </p>
                    <?php endif; ?>
            <?php
                    break;
                    
                case 'customers':
                    if (!isAdmin()) {
                        echo '<div class="alert error">Access denied. Admin only.</div>';
                        break;
                    }
                    
                    $customers = $conn->query("SELECT * FROM users WHERE role='customer' ORDER BY created_at DESC");
            ?>
                    <h2>👥 Customers</h2>
                    
                    <?php if ($customers && $customers->num_rows > 0): ?>
                        <table>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Joined</th>
                            </tr>
                            <?php while($row = $customers->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p style="color: #666; text-align: center; padding: 20px;">
                            No customers found.
                        </p>
                    <?php endif; ?>
            <?php
                    break;
                    
                case 'orders':
                    if (!isAdmin()) {
                        echo '<div class="alert error">Access denied. Admin only.</div>';
                        break;
                    }
                    
                    $orders = $conn->query("
                        SELECT o.*, u.username, p.name as product_name, p.image as product_image 
                        FROM orders o 
                        JOIN users u ON o.customer_id = u.id 
                        JOIN products p ON o.product_id = p.id 
                        ORDER BY o.order_date DESC
                    ");
            ?>
                    <h2>📋 Orders</h2>
                    
                    <?php if ($orders && $orders->num_rows > 0): ?>
                        <table>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Image</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                            <?php while($row = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td>
                                    <?php if (!empty($row['product_image']) && file_exists($row['product_image'])): ?>
                                        <img src="<?php echo $row['product_image']; ?>" 
                                             alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 5px; color: #999;">
                                            📦
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td>Tsh<?php echo number_format($row['total_price'], 2); ?></td>
                                <td><?php echo $row['order_date']; ?></td>
                                <td>
                                    <?php 
                                    if ($row['status'] == 'pending') echo '<span style="background: orange; color: white; padding: 5px 10px; border-radius: 5px;">Pending</span>';
                                    elseif ($row['status'] == 'completed') echo '<span style="background: green; color: white; padding: 5px 10px; border-radius: 5px;">Completed</span>';
                                    else echo '<span style="background: red; color: white; padding: 5px 10px; border-radius: 5px;">Cancelled</span>';
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p style="color: #666; text-align: center; padding: 20px;">
                            No orders found.
                        </p>
                    <?php endif; ?>
            <?php
                    break;
                    
                default:
                    echo '<script>window.location.href = "?page=home";</script>';
                    break;
            endswitch;
            ?>
        </div>
        
    
        <div class="footer">
            <p>SME Management System &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>
    
    <script>
    
    document.addEventListener('DOMContentLoaded', function() {
        
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        
        const logoutLinks = document.querySelectorAll('a[href*="logout"]');
        logoutLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to logout?')) {
                    e.preventDefault();
                }
            });
        });
        
        
        const imageInput = document.getElementById('productImageInput');
        if (imageInput) {
            const imagePreview = document.getElementById('imagePreview');
            
            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'image-preview';
                        imagePreview.innerHTML = '';
                        imagePreview.appendChild(img);
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
    });
    </script>
</body>
</html>
<?php
$conn->close();
?>