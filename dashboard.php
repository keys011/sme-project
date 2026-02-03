<?php
require_once __DIR__ . '/../../includes/functions.php';

// Redirect to login if not authenticated
if (!isLoggedIn()) {
    redirect('/sme-pro-manager/modules/auth/login.php');
}

$user = getCurrentUser();
$pageTitle = "Dashboard";

// Redirect based on role for now
switch($user['role']) {
    case 'admin':
    case 'manager':
    case 'employee':
        // Show dashboard
        break;
    case 'customer':
        // Show customer portal
        redirect('/sme-pro-manager/modules/customer-portal/');
        break;
    default:
        redirect('/sme-pro-manager/modules/auth/logout.php');
}
?>

<?php include '../../includes/header.php'; ?>

<div class="dashboard-header">
    <h1>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
    <p>You are logged in as <strong><?php echo ucfirst($user['role']); ?></strong></p>
</div>

<div class="dashboard-cards">
    <div class="card">
        <h3>Total Customers</h3>
        <?php
        $conn = getDBConnection();
        $result = $conn->query("SELECT COUNT(*) as count FROM customers WHERE status = 'active'");
        $count = $result->fetch_assoc()['count'] ?? 0;
        ?>
        <div class="number"><?php echo $count; ?></div>
        <p>Active customers in system</p>
    </div>
    
    <div class="card">
        <h3>Total Products</h3>
        <?php
        $result = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'available'");
        $count = $result->fetch_assoc()['count'] ?? 0;
        ?>
        <div class="number"><?php echo $count; ?></div>
        <p>Available products</p>
    </div>
    
    <div class="card">
        <h3>Pending Orders</h3>
        <?php
        $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'");
        $count = $result->fetch_assoc()['count'] ?? 0;
        ?>
        <div class="number"><?php echo $count; ?></div>
        <p>Orders awaiting processing</p>
    </div>
    
    <div class="card">
        <h3>Revenue (Today)</h3>
        <?php
        $today = date('Y-m-d');
        $result = $conn->query("SELECT SUM(grand_total) as total FROM orders WHERE DATE(order_date) = '$today'");
        $total = $result->fetch_assoc()['total'] ?? 0;
        ?>
        <div class="number">$<?php echo number_format($total, 2); ?></div>
        <p>Today's sales</p>
    </div>
</div>

<div style="margin-top: 2rem;">
    <h2>Quick Actions</h2>
    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
        <a href="/sme-pro-manager/modules/customers/add.php" class="btn btn-primary">Add New Customer</a>
        <a href="/sme-pro-manager/modules/products/add.php" class="btn">Add New Product</a>
        <a href="/sme-pro-manager/modules/orders/create.php" class="btn">Create New Order</a>
    </div>
</div>

<div style="margin-top: 2rem; background: white; padding: 1.5rem; border-radius: 10px;">
    <h3>Recent Activity</h3>
    <?php
    $result = $conn->query("
        SELECT al.action, al.description, al.created_at, u.username 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    
    if ($result->num_rows > 0) {
        echo '<table style="width: 100%; margin-top: 1rem; border-collapse: collapse;">';
        echo '<tr style="background: #f8f9fa;">';
        echo '<th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #ddd;">Action</th>';
        echo '<th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #ddd;">User</th>';
        echo '<th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #ddd;">Description</th>';
        echo '<th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #ddd;">Time</th>';
        echo '</tr>';
        
        while ($row = $result->fetch_assoc()) {
            echo '<tr style="border-bottom: 1px solid #eee;">';
            echo '<td style="padding: 0.75rem;">' . htmlspecialchars($row['action']) . '</td>';
            echo '<td style="padding: 0.75rem;">' . htmlspecialchars($row['username'] ?? 'System') . '</td>';
            echo '<td style="padding: 0.75rem;">' . htmlspecialchars($row['description']) . '</td>';
            echo '<td style="padding: 0.75rem;">' . date('M d, H:i', strtotime($row['created_at'])) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No recent activity</p>';
    }
    ?>
</div>

<?php include '../../includes/footer.php'; ?>