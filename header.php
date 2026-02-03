<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMEPro Manager - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    <link rel="stylesheet" href="/sme-pro-manager/assets/css/style.css">
    <style>
        /* Additional auth-specific styles */
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .auth-box {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .auth-header p {
            color: #666;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        .auth-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .form-errors {
            background: #fed7d7;
            color: #742a2a;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            border: 1px solid #fc8181;
        }
        
        .form-errors ul {
            list-style: none;
            margin-left: 0;
        }
        
        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <?php if (!isset($hideHeader) || !$hideHeader): ?>
    <header>
        <div class="container header-content">
            <div class="logo">
                <a href="/sme-pro-manager/index.php" style="color: white; text-decoration: none;">
                    SMEPro Manager
                </a>
            </div>
            <nav>
                <ul>
                    <?php if (isLoggedIn()): ?>
                        <?php $user = getCurrentUser(); ?>
                        <li><a href="/sme-pro-manager/modules/dashboard/">Dashboard</a></li>
                        <li><a href="/sme-pro-manager/modules/customers/">Customers</a></li>
                        <li><a href="/sme-pro-manager/modules/products/">Products</a></li>
                        <li><a href="/sme-pro-manager/modules/orders/">Orders</a></li>
                        <li class="user-menu">
                            <span style="color: #ddd;"><?php echo htmlspecialchars($user['full_name']); ?></span>
                            <span style="margin-left: 10px; color: #ffd700; font-size: 0.9rem;">
                                (<?php echo ucfirst($user['role']); ?>)
                            </span>
                            <a href="/sme-pro-manager/modules/auth/logout.php" 
                               style="margin-left: 15px; color: #ff6b6b;">Logout</a>
                        </li>
                    <?php else: ?>
                        <li><a href="/sme-pro-manager/index.php">Home</a></li>
                        <li><a href="/sme-pro-manager/modules/auth/login.php">Login</a></li>
                        <li><a href="/sme-pro-manager/modules/auth/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <?php endif; ?>
    
    <div class="main-content">
        <div class="container">