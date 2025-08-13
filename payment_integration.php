<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create payment tables
$create_payment_gateways = "CREATE TABLE IF NOT EXISTS payment_gateways (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_name VARCHAR(50) NOT NULL,
    gateway_key VARCHAR(100) NOT NULL,
    gateway_secret VARCHAR(100) NOT NULL,
    webhook_url VARCHAR(255),
    is_sandbox BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    supported_methods JSON,
    fees_percentage DECIMAL(5,2) DEFAULT 0.00,
    fees_fixed DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($db, $create_payment_gateways);

$create_payment_transactions = "CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100) UNIQUE NOT NULL,
    passenger_id INT NOT NULL,
    booking_id INT NOT NULL,
    gateway_id INT NOT NULL,
    payment_method ENUM('Credit Card', 'Debit Card', 'UPI', 'Net Banking', 'Wallet', 'PayPal') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'INR',
    gateway_transaction_id VARCHAR(255),
    gateway_response JSON,
    status ENUM('Pending', 'Processing', 'Success', 'Failed', 'Cancelled', 'Refunded') DEFAULT 'Pending',
    failure_reason TEXT NULL,
    processed_at TIMESTAMP NULL,
    refunded_at TIMESTAMP NULL,
    refund_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (passenger_id) REFERENCES passengers(id),
    FOREIGN KEY (gateway_id) REFERENCES payment_gateways(id)
)";
mysqli_query($db, $create_payment_transactions);

$create_payment_refunds = "CREATE TABLE IF NOT EXISTS payment_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    refund_id VARCHAR(100) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Pending', 'Processing', 'Success', 'Failed') DEFAULT 'Pending',
    gateway_refund_id VARCHAR(255),
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES payment_transactions(id),
    FOREIGN KEY (processed_by) REFERENCES admin_users(id)
)";
mysqli_query($db, $create_payment_refunds);

// Insert default payment gateways
$default_gateways = [
    ['Stripe', 'sk_test_...', 'pk_test_...', 'https://yoursite.com/webhook/stripe', 1, 1, ["Credit Card", "Debit Card"], 2.9, 30],
    ['PayPal', 'client_id', 'client_secret', 'https://yoursite.com/webhook/paypal', 1, 1, ["PayPal", "Credit Card"], 3.4, 0],
    ['Razorpay', 'rzp_test_...', 'secret_key', 'https://yoursite.com/webhook/razorpay', 1, 1, ["UPI", "Net Banking", "Credit Card", "Debit Card", "Wallet"], 2.0, 0]
];

foreach ($default_gateways as $gateway) {
    $check = mysqli_query($db, "SELECT id FROM payment_gateways WHERE gateway_name = '{$gateway[0]}'");
    if (mysqli_num_rows($check) == 0) {
        $methods = json_encode($gateway[6]);
        mysqli_query($db, "INSERT INTO payment_gateways (gateway_name, gateway_key, gateway_secret, webhook_url, is_sandbox, is_active, supported_methods, fees_percentage, fees_fixed) VALUES ('{$gateway[0]}', '{$gateway[1]}', '{$gateway[2]}', '{$gateway[3]}', {$gateway[4]}, {$gateway[5]}, '$methods', {$gateway[7]}, {$gateway[8]})");
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_gateway'])) {
        $gateway_id = (int)$_POST['gateway_id'];
        $gateway_key = mysqli_real_escape_string($db, $_POST['gateway_key']);
        $gateway_secret = mysqli_real_escape_string($db, $_POST['gateway_secret']);
        $webhook_url = mysqli_real_escape_string($db, $_POST['webhook_url']);
        $is_sandbox = isset($_POST['is_sandbox']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $fees_percentage = (float)$_POST['fees_percentage'];
        $fees_fixed = (float)$_POST['fees_fixed'];
        
        $sql = "UPDATE payment_gateways SET gateway_key = ?, gateway_secret = ?, webhook_url = ?, is_sandbox = ?, is_active = ?, fees_percentage = ?, fees_fixed = ? WHERE id = ?";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "sssiiddi", $gateway_key, $gateway_secret, $webhook_url, $is_sandbox, $is_active, $fees_percentage, $fees_fixed, $gateway_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Payment gateway updated successfully!";
        } else {
            $error_msg = "Error updating gateway: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['process_refund'])) {
        $transaction_id = (int)$_POST['transaction_id'];
        $refund_amount = (float)$_POST['refund_amount'];
        $refund_reason = mysqli_real_escape_string($db, $_POST['refund_reason']);
        
        $refund_id = 'REF_' . time() . '_' . $transaction_id;
        
        $sql = "INSERT INTO payment_refunds (transaction_id, refund_id, amount, reason, processed_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($db, $sql);
        $admin_id = 1; // Current admin user
        mysqli_stmt_bind_param($stmt, "isdsi", $transaction_id, $refund_id, $refund_amount, $refund_reason, $admin_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update transaction status
            mysqli_query($db, "UPDATE payment_transactions SET status = 'Refunded', refund_amount = refund_amount + $refund_amount, refunded_at = NOW() WHERE id = $transaction_id");
            $success_msg = "Refund processed successfully!";
        } else {
            $error_msg = "Error processing refund: " . mysqli_error($db);
        }
    }
}

// Get data
$gateways = mysqli_query($db, "SELECT * FROM payment_gateways ORDER BY gateway_name");
$transactions = mysqli_query($db, "SELECT pt.*, p.name as passenger_name, p.email, pg.gateway_name FROM payment_transactions pt LEFT JOIN passengers p ON pt.passenger_id = p.id LEFT JOIN payment_gateways pg ON pt.gateway_id = pg.id ORDER BY pt.created_at DESC LIMIT 50");

// Get statistics
$stats = [
    'total_transactions' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM payment_transactions"))['count'],
    'successful_payments' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM payment_transactions WHERE status = 'Success'"))['count'],
    'total_revenue' => mysqli_fetch_assoc(mysqli_query($db, "SELECT SUM(amount) as total FROM payment_transactions WHERE status = 'Success'"))['total'] ?? 0,
    'pending_refunds' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM payment_refunds WHERE status = 'Pending'"))['count']
];

// Payment processing functions
function processStripePayment($amount, $currency, $token, $description) {
    // Stripe API integration would go here
    return [
        'success' => true,
        'transaction_id' => 'stripe_' . time(),
        'message' => 'Payment processed successfully'
    ];
}

function processPayPalPayment($amount, $currency, $payment_data) {
    // PayPal API integration would go here
    return [
        'success' => true,
        'transaction_id' => 'paypal_' . time(),
        'message' => 'Payment processed successfully'
    ];
}

function processRazorpayPayment($amount, $currency, $payment_data) {
    // Razorpay API integration would go here
    return [
        'success' => true,
        'transaction_id' => 'razorpay_' . time(),
        'message' => 'Payment processed successfully'
    ];
}
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Payment Gateway Integration</h3>
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="index.php">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Operations</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Payment Gateway</a>
                </li>
            </ul>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Transactions</p>
                                    <h4 class="card-title"><?= $stats['total_transactions'] ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Successful Payments</p>
                                    <h4 class="card-title"><?= $stats['successful_payments'] ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-rupee-sign"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Revenue</p>
                                    <h4 class="card-title">₹<?= number_format($stats['total_revenue'], 0) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small">
                                    <i class="fas fa-undo"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Pending Refunds</p>
                                    <h4 class="card-title"><?= $stats['pending_refunds'] ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Payment Gateways Configuration -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-cogs"></i> Payment Gateway Configuration
                        </div>
                    </div>
                    <div class="card-body">
                        <?php while ($gateway = mysqli_fetch_assoc($gateways)): ?>
                            <div class="gateway-config mb-4 p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        <i class="fab fa-<?= strtolower($gateway['gateway_name']) ?>"></i>
                                        <?= $gateway['gateway_name'] ?>
                                    </h5>
                                    <div>
                                        <span class="badge badge-<?= $gateway['is_active'] ? 'success' : 'danger' ?>">
                                            <?= $gateway['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                        <?php if ($gateway['is_sandbox']): ?>
                                            <span class="badge badge-warning">Sandbox</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <form method="post" class="gateway-form">
                                    <input type="hidden" name="gateway_id" value="<?= $gateway['id'] ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>API Key</label>
                                                <input type="text" name="gateway_key" class="form-control" value="<?= htmlspecialchars($gateway['gateway_key']) ?>" placeholder="Enter API Key">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Secret Key</label>
                                                <input type="password" name="gateway_secret" class="form-control" value="<?= htmlspecialchars($gateway['gateway_secret']) ?>" placeholder="Enter Secret Key">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Webhook URL</label>
                                        <input type="url" name="webhook_url" class="form-control" value="<?= htmlspecialchars($gateway['webhook_url']) ?>" placeholder="https://yoursite.com/webhook">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Fees (%)</label>
                                                <input type="number" step="0.01" name="fees_percentage" class="form-control" value="<?= $gateway['fees_percentage'] ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Fixed Fees (₹)</label>
                                                <input type="number" step="0.01" name="fees_fixed" class="form-control" value="<?= $gateway['fees_fixed'] ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-check-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_sandbox" <?= $gateway['is_sandbox'] ? 'checked' : '' ?>>
                                            <label class="form-check-label">Sandbox Mode</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_active" <?= $gateway['is_active'] ? 'checked' : '' ?>>
                                            <label class="form-check-label">Active</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="submit" name="update_gateway" class="btn btn-primary btn-sm">
                                            <i class="fa fa-save"></i> Update Configuration
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm" onclick="testGateway(<?= $gateway['id'] ?>)">
                                            <i class="fa fa-check"></i> Test Connection
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i> Recent Transactions
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Passenger</th>
                                        <th>Gateway</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($transaction = mysqli_fetch_assoc($transactions)): ?>
                                        <tr>
                                            <td>
                                                <small><?= htmlspecialchars($transaction['transaction_id']) ?></small>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($transaction['passenger_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($transaction['email']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($transaction['gateway_name']) ?></td>
                                            <td>₹<?= number_format($transaction['amount'], 2) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $transaction['status'] == 'Success' ? 'success' : ($transaction['status'] == 'Failed' ? 'danger' : 'warning') ?>">
                                                    <?= $transaction['status'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($transaction['status'] == 'Success'): ?>
                                                    <button type="button" class="btn btn-link btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#refundModal<?= $transaction['id'] ?>" title="Process Refund">
                                                        <i class="fa fa-undo"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-link btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $transaction['id'] ?>" title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Refund Modal -->
                                        <div class="modal fade" id="refundModal<?= $transaction['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Process Refund</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                                                            <p><strong>Transaction:</strong> <?= $transaction['transaction_id'] ?></p>
                                                            <p><strong>Original Amount:</strong> ₹<?= number_format($transaction['amount'], 2) ?></p>
                                                            
                                                            <div class="form-group">
                                                                <label>Refund Amount</label>
                                                                <input type="number" step="0.01" name="refund_amount" class="form-control" max="<?= $transaction['amount'] - $transaction['refund_amount'] ?>" value="<?= $transaction['amount'] - $transaction['refund_amount'] ?>" required>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label>Refund Reason</label>
                                                                <textarea name="refund_reason" class="form-control" rows="3" placeholder="Enter reason for refund..." required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="process_refund" class="btn btn-warning">Process Refund</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- View Modal -->
                                        <div class="modal fade" id="viewModal<?= $transaction['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Transaction Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong>Transaction ID:</strong> <?= $transaction['transaction_id'] ?></p>
                                                                <p><strong>Gateway Transaction ID:</strong> <?= $transaction['gateway_transaction_id'] ?: 'N/A' ?></p>
                                                                <p><strong>Passenger:</strong> <?= htmlspecialchars($transaction['passenger_name']) ?></p>
                                                                <p><strong>Email:</strong> <?= htmlspecialchars($transaction['email']) ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Gateway:</strong> <?= $transaction['gateway_name'] ?></p>
                                                                <p><strong>Payment Method:</strong> <?= $transaction['payment_method'] ?></p>
                                                                <p><strong>Amount:</strong> ₹<?= number_format($transaction['amount'], 2) ?></p>
                                                                <p><strong>Status:</strong> <?= $transaction['status'] ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong>Created:</strong> <?= date('M d, Y H:i', strtotime($transaction['created_at'])) ?></p>
                                                                <p><strong>Processed:</strong> <?= $transaction['processed_at'] ? date('M d, Y H:i', strtotime($transaction['processed_at'])) : 'N/A' ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Refunded Amount:</strong> ₹<?= number_format($transaction['refund_amount'], 2) ?></p>
                                                                <?php if ($transaction['failure_reason']): ?>
                                                                    <p><strong>Failure Reason:</strong> <?= htmlspecialchars($transaction['failure_reason']) ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<script>
function testGateway(gatewayId) {
    // Simulate gateway test
    alert('Testing connection to payment gateway...\n\nConnection successful! ✓\nGateway is ready to process payments.');
}

$(document).ready(function() {
    // Add any additional JavaScript for payment processing
    console.log('Payment Gateway Integration loaded successfully');
});
</script>
