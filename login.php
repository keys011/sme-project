<?php
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = "Login";
$hideHeader = true;
$hideFooter = true;

if (isLoggedIn()) {
    redirect('/sme-pro-manager/modules/dashboard/');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $errors[] = "Please enter both username and password";
    } else {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT id, username, email, password, role, full_name, status FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['status'] !== 'active') {
                $errors[] = "Your account is " . $user['status'] . ". Please contact administrator.";
            } else {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    logActivity('login', "User logged in");
                    
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
                        
                        $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        $stmt->bind_param("si", $token, $user['id']);
                        $stmt->execute();
                    }
                    
                    $redirect_url = '/sme-pro-manager/modules/dashboard/';
                    redirect($redirect_url);
                } else {
                    $errors[] = "Invalid username or password";
                    logActivity('failed_login', "Failed login attempt for username: $username");
                }
            }
        } else {
            $errors[] = "Invalid username or password";
            logActivity('failed_login', "Failed login attempt for username: $username");
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p>Login to access your SME management dashboard</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="form-errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email *</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control" 
                       required>
                <div style="text-align: right; margin-top: 5px;">
                    <a href="#" style="font-size: 0.9rem; color: #667eea;">Forgot Password?</a>
                </div>
            </div>
            
            <div class="form-group" style="display: flex; align-items: center;">
                <input type="checkbox" 
                       id="remember" 
                       name="remember" 
                       style="margin-right: 10px;">
                <label for="remember" style="margin-bottom: 0;">Remember me for 30 days</label>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                Login to Account
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="/sme-pro-manager/modules/auth/register.php">Register here</a></p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                Demo Accounts:<br>
                Admin: admin / admin123<br>
                Employee: employee / employee123
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const rules = [
                { selector: '#username', name: 'Username/Email', required: true },
                { selector: '#password', name: 'Password', required: true }
            ];
            
            if (!validateForm('loginForm', rules)) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>