<?php
/**
 * SMEs Management System
 * Complete application with routing in single file
 */

// ============================================
// CONFIGURATION & BOOTSTRAP
// ============================================
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Change to 1 for debugging
ini_set('log_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'smes_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'SMEs Management System');
define('DEBUG_MODE', true);

// Color Scheme
define('PRIMARY_COLOR', '#4361ee');
define('SECONDARY_COLOR', '#3a0ca3');
define('SUCCESS_COLOR', '#2ecc71');
define('DANGER_COLOR', '#e74c3c');
define('WARNING_COLOR', '#f39c12');
define('LIGHT_BG', '#f8f9fa');
define('DARK_BG', '#2c3e50');

// Initialize Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . (DEBUG_MODE ? $e->getMessage() : "Please check configuration"));
}

// ============================================
// HELPER FUNCTIONS
// ============================================
if (!function_exists('esc')) {
    function esc($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('get_current_user')) {
    function get_current_user($pdo) {
        if (!is_logged_in()) return null;
        
        static $user = null;
        if ($user === null) {
            $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_role'] = $user['role'];
            }
        }
        return $user;
    }
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
            header('Location: ?page=login');
            exit;
        }
    }
}

if (!function_exists('require_role')) {
    function require_role($allowedRoles = []) {
        require_login();
        $user = get_current_user($GLOBALS['pdo']);
        if (!$user || !in_array($user['role'], (array)$allowedRoles, true)) {
            http_response_code(403);
            render_template('403');
            exit;
        }
    }
}

if (!function_exists('validate_required')) {
    function validate_required($value) {
        return !empty(trim($value ?? ''));
    }
}

if (!function_exists('validate_email')) {
    function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('add_message')) {
    function add_message($type, $message) {
        $_SESSION['messages'][] = ['type' => $type, 'text' => $message];
    }
}

if (!function_exists('show_messages')) {
    function show_messages() {
        if (empty($_SESSION['messages'])) return '';
        
        $html = '';
        foreach ($_SESSION['messages'] as $msg) {
            $bg_color = $msg['type'] === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 
                       ($msg['type'] === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 
                       'bg-blue-100 border-blue-400 text-blue-700');
            
            $html .= <<<HTML
            <div class="{$bg_color} border px-4 py-3 rounded mb-4">
                {$msg['text']}
            </div>
HTML;
        }
        $_SESSION['messages'] = [];
        return $html;
    }
}

if (!function_exists('render_template')) {
    function render_template($template, $data = []) {
        extract($data);
        include __DIR__ . "/templates/{$template}.php";
    }
}

// ============================================
// ROUTING
// ============================================
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? '';

// Define public pages (no login required)
$public_pages = ['login', 'register', 'logout'];

// Handle routing
switch ($page) {
    case 'login':
        handle_login();
        break;
    case 'register':
        handle_register();
        break;
    case 'logout':
        handle_logout();
        break;
    case 'dashboard':
        handle_dashboard();
        break;
    case 'sme':
        handle_sme($action);
        break;
    case 'users':
        handle_users();
        break;
    case 'profile':
        handle_profile();
        break;
    default:
        if (!is_logged_in() && !in_array($page, $public_pages)) {
            redirect('?page=login');
        } else {
            handle_dashboard();
        }
}

// ============================================
// PAGE HANDLERS
// ============================================

function handle_login() {
    global $pdo;
    
    if (is_logged_in()) {
        redirect('?page=dashboard');
    }
    
    $errors = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $errors[] = 'Username and password are required';
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                add_message('success', 'Login successful!');
                redirect($_SESSION['redirect_to'] ?? '?page=dashboard');
            } else {
                $errors[] = 'Invalid username or password';
            }
        }
    }
    
    render_login_page($errors);
}

function handle_register() {
    global $pdo;
    
    if (is_logged_in()) {
        redirect('?page=dashboard');
    }
    
    $errors = [];
    $old = $_POST;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($username)) $errors[] = 'Username is required';
        if (empty($email) || !validate_email($email)) $errors[] = 'Valid email is required';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
        if ($password !== $confirm_password) $errors[] = 'Passwords do not match';
        
        // Check for existing user
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Username or email already exists';
            }
        }
        
        // Create user
        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'Staff')");
            if ($stmt->execute([$username, $email, $hash])) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                add_message('success', 'Registration successful! Welcome!');
                redirect('?page=dashboard');
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
    
    render_register_page($errors, $old);
}

function handle_logout() {
    session_destroy();
    redirect('?page=login');
}

function handle_dashboard() {
    global $pdo;
    require_login();
    
    $user = get_current_user($pdo);
    
    // Search and filter parameters
    $search = $_GET['search'] ?? '';
    $industry = $_GET['industry'] ?? '';
    $location = $_GET['location'] ?? '';
    
    // Build query
    $sql = "SELECT s.*, u.username as owner_name FROM smes s 
            JOIN users u ON s.owner_user_id = u.id 
            WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (s.name LIKE ? OR s.contact_email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($industry)) {
        $sql .= " AND s.industry = ?";
        $params[] = $industry;
    }
    
    if (!empty($location)) {
        $sql .= " AND s.location LIKE ?";
        $params[] = "%$location%";
    }
    
    // For non-admins, show only their SMEs
    if ($user['role'] !== 'Admin') {
        $sql .= " AND s.owner_user_id = ?";
        $params[] = $user['id'];
    }
    
    $sql .= " ORDER BY s.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $smes = $stmt->fetchAll();
    
    // Get unique industries for filter dropdown
    $industries_stmt = $pdo->query("SELECT DISTINCT industry FROM smes ORDER BY industry");
    $industries = $industries_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    render_dashboard($user, $smes, $industries, $search, $industry, $location);
}

function handle_sme($action) {
    global $pdo;
    require_login();
    
    $user = get_current_user($pdo);
    $id = $_GET['id'] ?? 0;
    
    switch ($action) {
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $errors = [];
                $data = [
                    'name' => trim($_POST['name'] ?? ''),
                    'industry' => trim($_POST['industry'] ?? ''),
                    'location' => trim($_POST['location'] ?? ''),
                    'contact_email' => trim($_POST['contact_email'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                ];
                
                // Validation
                if (empty($data['name'])) $errors[] = 'Business name is required';
                if (empty($data['industry'])) $errors[] = 'Industry is required';
                if (empty($data['location'])) $errors[] = 'Location is required';
                if (empty($data['contact_email']) || !validate_email($data['contact_email'])) {
                    $errors[] = 'Valid contact email is required';
                }
                
                if (empty($errors)) {
                    $stmt = $pdo->prepare("INSERT INTO smes (name, industry, location, contact_email, phone, owner_user_id) 
                                           VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$data['name'], $data['industry'], $data['location'], 
                                        $data['contact_email'], $data['phone'], $user['id']])) {
                        add_message('success', 'SME created successfully!');
                        redirect('?page=dashboard');
                    } else {
                        $errors[] = 'Failed to create SME';
                    }
                }
                
                render_sme_form('create', $errors, $data);
            } else {
                render_sme_form('create');
            }
            break;
            
        case 'edit':
            if (!$id) redirect('?page=dashboard');
            
            // Get SME
            $stmt = $pdo->prepare("SELECT * FROM smes WHERE id = ?");
            $stmt->execute([$id]);
            $sme = $stmt->fetch();
            
            if (!$sme) {
                add_message('error', 'SME not found');
                redirect('?page=dashboard');
            }
            
            // Authorization
            if ($user['role'] !== 'Admin' && $sme['owner_user_id'] != $user['id']) {
                add_message('error', 'You do not have permission to edit this SME');
                redirect('?page=dashboard');
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $errors = [];
                $data = [
                    'name' => trim($_POST['name'] ?? ''),
                    'industry' => trim($_POST['industry'] ?? ''),
                    'location' => trim($_POST['location'] ?? ''),
                    'contact_email' => trim($_POST['contact_email'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                ];
                
                // Validation (same as create)
                if (empty($data['name'])) $errors[] = 'Business name is required';
                if (empty($data['industry'])) $errors[] = 'Industry is required';
                if (empty($data['location'])) $errors[] = 'Location is required';
                if (empty($data['contact_email']) || !validate_email($data['contact_email'])) {
                    $errors[] = 'Valid contact email is required';
                }
                
                if (empty($errors)) {
                    $stmt = $pdo->prepare("UPDATE smes SET name = ?, industry = ?, location = ?, 
                                           contact_email = ?, phone = ?, updated_at = NOW() 
                                           WHERE id = ?");
                    if ($stmt->execute([$data['name'], $data['industry'], $data['location'], 
                                        $data['contact_email'], $data['phone'], $id])) {
                        add_message('success', 'SME updated successfully!');
                        redirect('?page=dashboard');
                    } else {
                        $errors[] = 'Failed to update SME';
                    }
                }
                
                render_sme_form('edit', $errors, $data, $id);
            } else {
                render_sme_form('edit', [], $sme, $id);
            }
            break;
            
        case 'delete':
            require_role(['Admin']);
            
            if (!$id) redirect('?page=dashboard');
            
            $stmt = $pdo->prepare("DELETE FROM smes WHERE id = ?");
            if ($stmt->execute([$id])) {
                add_message('success', 'SME deleted successfully');
            } else {
                add_message('error', 'Failed to delete SME');
            }
            redirect('?page=dashboard');
            break;
            
        default:
            redirect('?page=dashboard');
    }
}

function handle_users() {
    global $pdo;
    require_role(['Admin']);
    
    $user = get_current_user($pdo);
    
    // Handle role change
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        $new_role = $_POST['role'];
        
        // Prevent self-demotion
        if ($user_id == $user['id'] && $new_role !== 'Admin') {
            add_message('error', 'You cannot remove your own Admin role');
        } elseif (in_array($new_role, ['Admin', 'Staff'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            if ($stmt->execute([$new_role, $user_id])) {
                add_message('success', 'User role updated successfully');
            } else {
                add_message('error', 'Failed to update user role');
            }
        }
    }
    
    // Get all users
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
    
    render_users_page($users);
}

function handle_profile() {
    global $pdo;
    require_login();
    
    $user = get_current_user($pdo);
    $errors = [];
    $success = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $email = trim($_POST['email'] ?? '');
            
            if (empty($email) || !validate_email($email)) {
                $errors[] = 'Valid email is required';
            } else {
                // Check if email exists (excluding current user)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user['id']]);
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = 'Email already exists';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                    if ($stmt->execute([$email, $user['id']])) {
                        $success = 'Profile updated successfully';
                        // Refresh user data
                        $_SESSION['user'] = null;
                    } else {
                        $errors[] = 'Failed to update profile';
                    }
                }
            }
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $current_hash = $stmt->fetchColumn();
            
            if (!password_verify($current_password, $current_hash)) {
                $errors[] = 'Current password is incorrect';
            } elseif (strlen($new_password) < 6) {
                $errors[] = 'New password must be at least 6 characters';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'New passwords do not match';
            } else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                if ($stmt->execute([$new_hash, $user['id']])) {
                    $success = 'Password changed successfully';
                } else {
                    $errors[] = 'Failed to change password';
                }
            }
        }
    }
    
    render_profile_page($user, $errors, $success);
}

// ============================================
// RENDER FUNCTIONS
// ============================================

function render_layout($title, $content) {
    $user = get_current_user($GLOBALS['pdo']);
    $current_page = $_GET['page'] ?? 'dashboard';
    
    // Precompute active classes to avoid expressions inside heredoc strings
    $dashboard_active = $current_page == 'dashboard' ? 'active' : '';
    $users_active = $current_page == 'users' ? 'active' : '';
    $profile_active = $current_page == 'profile' ? 'active' : '';
    $login_active = $current_page == 'login' ? 'active' : '';
    $register_active = $current_page == 'register' ? 'active' : '';
    
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{$title} - SMEs Management</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                min-height: 100vh;
                color: #333;
            }
            
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 20px;
            }
            
            /* Header & Navigation */
            .header {
                background: white;
                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                position: sticky;
                top: 0;
                z-index: 100;
            }
            
            .nav-container {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 1rem 0;
            }
            
            .logo {
                font-size: 1.5rem;
                font-weight: 700;
                color: #4361ee;
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .logo i {
                font-size: 1.8rem;
            }
            
            .nav-links {
                display: flex;
                gap: 1.5rem;
                align-items: center;
            }
            
            .nav-link {
                color: #555;
                text-decoration: none;
                font-weight: 500;
                padding: 0.5rem 0.75rem;
                border-radius: 6px;
                transition: all 0.3s ease;
            }
            
            .nav-link:hover, .nav-link.active {
                background: #4361ee;
                color: white;
            }
            
            .user-menu {
                display: flex;
                align-items: center;
                gap: 1rem;
            }
            
            .user-avatar {
                width: 40px;
                height: 40px;
                background: #4361ee;
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
            }
            
            /* Main Content */
            .main-content {
                padding: 2rem 0;
                min-height: calc(100vh - 120px);
            }
            
            .page-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 2rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid #e1e5eb;
            }
            
            .page-title {
                font-size: 1.8rem;
                color: #2c3e50;
                font-weight: 700;
            }
            
            .btn {
                display: inline-block;
                padding: 0.6rem 1.2rem;
                background: #4361ee;
                color: white;
                border: none;
                border-radius: 6px;
                text-decoration: none;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                font-size: 0.95rem;
            }
            
            .btn:hover {
                background: #3a56d4;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
            }
            
            .btn-outline {
                background: transparent;
                border: 2px solid #4361ee;
                color: #4361ee;
            }
            
            .btn-outline:hover {
                background: #4361ee;
                color: white;
            }
            
            .btn-danger {
                background: #e74c3c;
            }
            
            .btn-danger:hover {
                background: #c0392b;
                box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
            }
            
            .btn-success {
                background: #2ecc71;
            }
            
            .btn-success:hover {
                background: #27ae60;
                box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
            }
            
            /* Cards */
            .card {
                background: white;
                border-radius: 12px;
                padding: 1.5rem;
                box-shadow: 0 6px 16px rgba(0,0,0,0.08);
                margin-bottom: 1.5rem;
                transition: transform 0.3s ease;
            }
            
            .card:hover {
                transform: translateY(-4px);
            }
            
            /* Forms */
            .form-group {
                margin-bottom: 1.5rem;
            }
            
            .form-label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 600;
                color: #2c3e50;
            }
            
            .form-control {
                width: 100%;
                padding: 0.75rem 1rem;
                border: 2px solid #e1e5eb;
                border-radius: 8px;
                font-size: 1rem;
                transition: border-color 0.3s ease;
            }
            
            .form-control:focus {
                outline: none;
                border-color: #4361ee;
                box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            }
            
            /* Tables */
            .table-container {
                overflow-x: auto;
            }
            
            .table {
                width: 100%;
                border-collapse: collapse;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            }
            
            .table th {
                background: #f8f9fa;
                padding: 1rem;
                text-align: left;
                font-weight: 600;
                color: #2c3e50;
                border-bottom: 2px solid #e1e5eb;
            }
            
            .table td {
                padding: 1rem;
                border-bottom: 1px solid #e1e5eb;
            }
            
            .table tr:hover {
                background: #f8fafc;
            }
            
            /* Badges */
            .badge {
                display: inline-block;
                padding: 0.25rem 0.75rem;
                border-radius: 20px;
                font-size: 0.85rem;
                font-weight: 600;
            }
            
            .badge-admin {
                background: rgba(67, 97, 238, 0.1);
                color: #4361ee;
            }
            
            .badge-staff {
                background: rgba(46, 204, 113, 0.1);
                color: #27ae60;
            }
            
            /* Alerts */
            .alert {
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1.5rem;
                border-left: 4px solid;
            }
            
            .alert-success {
                background: rgba(46, 204, 113, 0.1);
                border-color: #2ecc71;
                color: #27ae60;
            }
            
            .alert-error {
                background: rgba(231, 76, 60, 0.1);
                border-color: #e74c3c;
                color: #c0392b;
            }
            
            /* Stats Cards */
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            }
            
            .stat-card {
                background: white;
                border-radius: 12px;
                padding: 1.5rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            }
            
            .stat-icon {
                width: 60px;
                height: 60px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.8rem;
                color: white;
            }
            
            .stat-content h3 {
                font-size: 1.5rem;
                margin-bottom: 0.25rem;
            }
            
            .stat-content p {
                color: #666;
                font-size: 0.9rem;
            }
            
            /* Footer */
            .footer {
                background: #2c3e50;
                color: white;
                padding: 2rem 0;
                margin-top: 3rem;
                text-align: center;
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .nav-container {
                    flex-direction: column;
                    gap: 1rem;
                }
                
                .nav-links {
                    flex-wrap: wrap;
                    justify-content: center;
                }
                
                .page-header {
                    flex-direction: column;
                    gap: 1rem;
                    align-items: flex-start;
                }
            }
            
            .actions {
                display: flex;
                gap: 0.5rem;
            }
            
            .action-btn {
                padding: 0.25rem 0.5rem;
                border-radius: 4px;
                text-decoration: none;
                font-size: 0.85rem;
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
            }
            
            .action-edit {
                background: rgba(52, 152, 219, 0.1);
                color: #3498db;
            }
            
            .action-delete {
                background: rgba(231, 76, 60, 0.1);
                color: #e74c3c;
            }
        </style>
    </head>
    <body>
        <header class="header">
            <div class="container nav-container">
                <a href="?page=dashboard" class="logo">
                    <i class="fas fa-building"></i>
                    <span>SMEs Manager</span>
                </a>
                
                <nav class="nav-links">
HTML;
    
    if (is_logged_in()) {
        echo <<<HTML
                    <a href="?page=dashboard" class="nav-link {$dashboard_active}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
HTML;
        
        if ($user && $user['role'] === 'Admin') {
            echo <<<HTML
                    <a href="?page=users" class="nav-link {$users_active}">
                        <i class="fas fa-users"></i> Users
                    </a>
HTML;
        }
        
        echo <<<HTML
                    <a href="?page=profile" class="nav-link {$profile_active}">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="?page=logout" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
HTML;
    } else {
        echo <<<HTML
                    <a href="?page=login" class="nav-link {$login_active}">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="?page=register" class="nav-link {$register_active}">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
HTML;
    }
    
    echo <<<HTML
                </nav>
HTML;
    
    if (is_logged_in() && $user) {
        $username = esc($user['username']);
        $initial = strtoupper(substr($username, 0, 1));
        $role = $user['role'];
        $role_badge = $role == 'Admin' ? 'badge-admin' : 'badge-staff';
        
        echo <<<HTML
                <div class="user-menu">
                    <div class="user-avatar" title="{$username}">{$initial}</div>
                    <div>
                        <div style="font-weight: 600;">{$username}</div>
                        <div style="font-size: 0.85rem; color: #666;">
                            <span class="badge {$role_badge}">
                                {$role}
                            </span>
                        </div>
                    </div>
                </div>
HTML;
    }
    
    echo <<<HTML
            </div>
        </header>
        
        <main class="main-content">
            <div class="container">
                {$content}
            </div>
        </main>
        
        <footer class="footer">
            <div class="container">
                <p>&copy; 2023 SMEs Management System. All rights reserved.</p>
                <p style="margin-top: 0.5rem; opacity: 0.8; font-size: 0.9rem;">
                    <i class="fas fa-code"></i> Built with PHP & MySQL
                </p>
            </div>
        </footer>
        
        <script>
            // Simple confirmation for delete actions
            document.addEventListener('DOMContentLoaded', function() {
                const deleteButtons = document.querySelectorAll('.btn-delete-confirm');
                deleteButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                            e.preventDefault();
                        }
                    });
                });
            });
        </script>
    </body>
    </html>
HTML;
}