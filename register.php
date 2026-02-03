<?php
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = "Register";
$hideHeader = true;
$hideFooter = true;

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/sme-pro-manager/modules/dashboard/');
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $full_name = sanitize($_POST['full_name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $role = 'customer'; // Default role for self-registration
    
    // Validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match('/[A-Z]/', $password) || 
               !preg_match('/[a-z]/', $password) || 
               !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $conn = getDBConnection();
        
        // Check username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Username already exists";
        }
        
        // Check email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email already registered";
        }
    }
    
    // If no errors, create user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $email, $hashed_password, $full_name, $role, $phone, $address);
        
        if ($stmt->execute()) {
            $success = true;
            logActivity('registration', "New user registered: $username");
            
            // Auto-login after registration
            $user_id = $stmt->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            
            // Redirect based on role
            $redirect_url = '/sme-pro-manager/modules/dashboard/';
            
            // Show success message and redirect
            echo "<script>
                setTimeout(function() {
                    window.location.href = '$redirect_url';
                }, 3000);
            </script>";
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Join SMEPro Manager to streamline your business operations</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Registration Successful!</strong> 
                <p>You are being redirected to your dashboard...</p>
                <p>If you are not redirected, <a href="/sme-pro-manager/modules/dashboard/">click here</a></p>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="form-errors">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form id="registerForm" method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           required 
                           minlength="3">
                    <small>Minimum 3 characters, letters and numbers only</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required 
                           minlength="8">
                    <div class="password-requirements">
                        Must be at least 8 characters with uppercase, lowercase, and number
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           class="form-control" 
                           required 
                           minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" 
                              name="address" 
                              class="form-control" 
                              rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                    Create Account
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="/sme-pro-manager/modules/auth/login.php">Login here</a></p>
                <p style="margin-top: 0.5rem; font-size: 0.9rem;">
                    By registering, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const rules = [
                { selector: '#full_name', name: 'Full name', required: true },
                { selector: '#username', name: 'Username', required: true, minLength: 3 },
                { selector: '#email', name: 'Email', required: true, type: 'email' },
                { selector: '#password', name: 'Password', required: true, type: 'password', minLength: 8 },
                { selector: '#confirm_password', name: 'Passwords', required: true, match: '#password' }
            ];
            
            if (!validateForm('registerForm', rules)) {
                e.preventDefault();
            }
        });
    }
    
    // Real-time password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            updateStrengthIndicator(strength);
        });
    }
});

function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    return strength;
}

function updateStrengthIndicator(strength) {
    let indicator = document.getElementById('password-strength');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'password-strength';
        indicator.style.cssText = 'margin-top: 5px; height: 5px; border-radius: 3px; background: #ddd;';
        passwordInput.parentNode.appendChild(indicator);
    }
    
    let color, width;
    switch(strength) {
        case 0:
        case 1:
            color = '#f56565'; width = '20%'; break;
        case 2:
            color = '#ed8936'; width = '40%'; break;
        case 3:
            color = '#ecc94b'; width = '60%'; break;
        case 4:
            color = '#48bb78'; width = '80%'; break;
        case 5:
            color = '#38a169'; width = '100%'; break;
    }
    
    indicator.style.background = `linear-gradient(90deg, ${color} ${width}, #ddd ${width})`;
}
</script>

<?php include '../../includes/footer.php'; ?>