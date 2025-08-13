<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create pricing_rules table
$create_pricing_table = "CREATE TABLE IF NOT EXISTS pricing_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    rule_type ENUM('Base Price', 'Demand Multiplier', 'Time Based', 'Distance Based', 'Class Multiplier', 'Special Event', 'Seasonal') NOT NULL,
    train_id INT NULL,
    class_type VARCHAR(50) NULL,
    multiplier DECIMAL(5,2) DEFAULT 1.00,
    fixed_amount DECIMAL(10,2) DEFAULT 0.00,
    start_date DATE NULL,
    end_date DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (train_id) REFERENCES trains(id)
)";
mysqli_query($db, $create_pricing_table);

// Create pricing_history table
$create_history_table = "CREATE TABLE IF NOT EXISTS pricing_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    train_id INT NOT NULL,
    class_type VARCHAR(50),
    base_price DECIMAL(10,2),
    final_price DECIMAL(10,2),
    applied_rules JSON,
    occupancy_rate DECIMAL(5,2) DEFAULT 0.00,
    booking_date DATE,
    journey_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (train_id) REFERENCES trains(id)
)";
mysqli_query($db, $create_history_table);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_pricing_rule'])) {
        $rule_name = mysqli_real_escape_string($db, $_POST['rule_name']);
        $rule_type = mysqli_real_escape_string($db, $_POST['rule_type']);
        $train_id = $_POST['train_id'] ? (int)$_POST['train_id'] : null;
        $class_type = mysqli_real_escape_string($db, $_POST['class_type']);
        $multiplier = (float)$_POST['multiplier'];
        $fixed_amount = (float)$_POST['fixed_amount'];
        $start_date = $_POST['start_date'] ?: null;
        $end_date = $_POST['end_date'] ?: null;
        $priority = (int)$_POST['priority'];
        
        $sql = "INSERT INTO pricing_rules (rule_name, rule_type, train_id, class_type, multiplier, fixed_amount, start_date, end_date, priority) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "ssissdssi", $rule_name, $rule_type, $train_id, $class_type, $multiplier, $fixed_amount, $start_date, $end_date, $priority);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Pricing rule added successfully!";
        } else {
            $error_msg = "Error adding pricing rule: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['calculate_price'])) {
        $train_id = (int)$_POST['calc_train_id'];
        $class_type = mysqli_real_escape_string($db, $_POST['calc_class_type']);
        $journey_date = $_POST['calc_journey_date'];
        $base_price = (float)$_POST['calc_base_price'];
        
        $calculated_price = calculateDynamicPrice($train_id, $class_type, $journey_date, $base_price, $db);
        $price_result = $calculated_price;
    }
}

// Get pricing rules and statistics
$pricing_rules = mysqli_query($db, "SELECT pr.*, t.name as train_name FROM pricing_rules pr LEFT JOIN trains t ON pr.train_id = t.id ORDER BY pr.priority DESC, pr.created_at DESC");
$trains = $fun->get_all_trains();

// Get pricing statistics
$stats = [
    'total_rules' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM pricing_rules WHERE is_active = 1"))['count'],
    'avg_price_increase' => mysqli_fetch_assoc(mysqli_query($db, "SELECT AVG((final_price - base_price) / base_price * 100) as avg FROM pricing_history WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"))['avg'] ?? 0,
    'revenue_impact' => mysqli_fetch_assoc(mysqli_query($db, "SELECT SUM(final_price - base_price) as impact FROM pricing_history WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"))['impact'] ?? 0,
    'high_demand_routes' => 3 // Simplified
];

// Dynamic pricing calculation function
function calculateDynamicPrice($train_id, $class_type, $journey_date, $base_price, $db) {
    $final_price = $base_price;
    $applied_rules = [];
    
    // Get applicable pricing rules
    $rules_query = "SELECT * FROM pricing_rules 
                   WHERE is_active = 1 
                   AND (train_id IS NULL OR train_id = $train_id)
                   AND (class_type IS NULL OR class_type = '' OR class_type = '$class_type')
                   AND (start_date IS NULL OR start_date <= '$journey_date')
                   AND (end_date IS NULL OR end_date >= '$journey_date')
                   ORDER BY priority DESC";
    
    $rules = mysqli_query($db, $rules_query);
    
    while ($rule = mysqli_fetch_assoc($rules)) {
        if ($rule['multiplier'] != 1.00) {
            $final_price *= $rule['multiplier'];
        }
        if ($rule['fixed_amount'] != 0.00) {
            $final_price += $rule['fixed_amount'];
        }
        
        $applied_rules[] = [
            'rule_name' => $rule['rule_name'],
            'rule_type' => $rule['rule_type'],
            'multiplier' => $rule['multiplier'],
            'fixed_amount' => $rule['fixed_amount']
        ];
    }
    
    // Log pricing calculation
    $applied_rules_json = json_encode($applied_rules);
    $occupancy_rate = 0.65; // Simplified
    
    $log_sql = "INSERT INTO pricing_history (train_id, class_type, base_price, final_price, applied_rules, occupancy_rate, booking_date, journey_date) 
                VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?)";
    $stmt = mysqli_prepare($db, $log_sql);
    mysqli_stmt_bind_param($stmt, "isddsds", $train_id, $class_type, $base_price, $final_price, $applied_rules_json, $occupancy_rate, $journey_date);
    mysqli_stmt_execute($stmt);
    
    return [
        'base_price' => $base_price,
        'final_price' => round($final_price, 2),
        'applied_rules' => $applied_rules,
        'price_change' => round((($final_price - $base_price) / $base_price) * 100, 2)
    ];
}
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Dynamic Pricing Engine</h3>
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
                    <a href="#">Dynamic Pricing</a>
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
                                    <i class="fas fa-tags"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Active Rules</p>
                                    <h4 class="card-title"><?= $stats['total_rules'] ?></h4>
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
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Avg Price Increase</p>
                                    <h4 class="card-title"><?= number_format($stats['avg_price_increase'], 1) ?>%</h4>
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
                                    <p class="card-category">Revenue Impact</p>
                                    <h4 class="card-title">₹<?= number_format($stats['revenue_impact'], 0) ?></h4>
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
                                    <i class="fas fa-fire"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">High Demand Routes</p>
                                    <h4 class="card-title"><?= $stats['high_demand_routes'] ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Add Pricing Rule -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-plus"></i> Add Pricing Rule
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label>Rule Name</label>
                                <input type="text" name="rule_name" class="form-control" placeholder="e.g., Peak Hour Surcharge" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Rule Type</label>
                                <select name="rule_type" class="form-select" required>
                                    <option value="Base Price">Base Price</option>
                                    <option value="Demand Multiplier">Demand Multiplier</option>
                                    <option value="Time Based">Time Based</option>
                                    <option value="Distance Based">Distance Based</option>
                                    <option value="Class Multiplier">Class Multiplier</option>
                                    <option value="Special Event">Special Event</option>
                                    <option value="Seasonal">Seasonal</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Train (Optional)</label>
                                <select name="train_id" class="form-select">
                                    <option value="">All Trains</option>
                                    <?php foreach ($trains as $train): ?>
                                        <option value="<?= $train['id'] ?>"><?= htmlspecialchars($train['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Class Type (Optional)</label>
                                <select name="class_type" class="form-select">
                                    <option value="">All Classes</option>
                                    <option value="Economy">Economy</option>
                                    <option value="Business">Business</option>
                                    <option value="First Class">First Class</option>
                                    <option value="AC1">AC 1 Tier</option>
                                    <option value="AC2">AC 2 Tier</option>
                                    <option value="AC3">AC 3 Tier</option>
                                    <option value="Sleeper">Sleeper</option>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Multiplier</label>
                                        <input type="number" step="0.01" name="multiplier" class="form-control" value="1.00" min="0.1" max="5.0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fixed Amount (₹)</label>
                                        <input type="number" step="0.01" name="fixed_amount" class="form-control" value="0.00">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" name="start_date" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" name="end_date" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Priority (1-10)</label>
                                <input type="number" name="priority" class="form-control" value="5" min="1" max="10">
                            </div>
                            
                            <div class="card-action">
                                <button type="submit" name="add_pricing_rule" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Add Rule
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Price Calculator -->
                <div class="card mt-3">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-calculator"></i> Price Calculator
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label>Train</label>
                                <select name="calc_train_id" class="form-select" required>
                                    <option value="">Select Train</option>
                                    <?php foreach ($trains as $train): ?>
                                        <option value="<?= $train['id'] ?>"><?= htmlspecialchars($train['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Class Type</label>
                                <select name="calc_class_type" class="form-select" required>
                                    <option value="Economy">Economy</option>
                                    <option value="Business">Business</option>
                                    <option value="First Class">First Class</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Journey Date</label>
                                <input type="date" name="calc_journey_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Base Price (₹)</label>
                                <input type="number" step="0.01" name="calc_base_price" class="form-control" placeholder="1000.00" required>
                            </div>
                            <button type="submit" name="calculate_price" class="btn btn-success">Calculate Price</button>
                        </form>
                        
                        <?php if (isset($price_result)): ?>
                            <div class="mt-3 p-3 bg-light rounded">
                                <h6>Price Calculation Result</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Base Price:</strong><br>
                                        ₹<?= number_format($price_result['base_price'], 2) ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Final Price:</strong><br>
                                        <span class="text-success">₹<?= number_format($price_result['final_price'], 2) ?></span>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <strong>Price Change:</strong> 
                                    <span class="badge badge-<?= $price_result['price_change'] >= 0 ? 'success' : 'danger' ?>">
                                        <?= $price_result['price_change'] >= 0 ? '+' : '' ?><?= $price_result['price_change'] ?>%
                                    </span>
                                </div>
                                <?php if (!empty($price_result['applied_rules'])): ?>
                                    <div class="mt-2">
                                        <strong>Applied Rules:</strong>
                                        <ul class="small mb-0">
                                            <?php foreach ($price_result['applied_rules'] as $rule): ?>
                                                <li><?= htmlspecialchars($rule['rule_name']) ?> (<?= $rule['multiplier'] ?>x, +₹<?= $rule['fixed_amount'] ?>)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Pricing Rules Table -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i> Pricing Rules
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="pricing-table" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Rule Name</th>
                                        <th>Type</th>
                                        <th>Train</th>
                                        <th>Class</th>
                                        <th>Multiplier</th>
                                        <th>Fixed Amount</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($rule = mysqli_fetch_assoc($pricing_rules)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($rule['rule_name']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $rule['rule_type'] == 'Base Price' ? 'primary' : ($rule['rule_type'] == 'Demand Multiplier' ? 'warning' : 'info') ?>">
                                                    <?= htmlspecialchars($rule['rule_type']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($rule['train_name']) ?: '<span class="text-muted">All</span>' ?></td>
                                            <td><?= htmlspecialchars($rule['class_type']) ?: '<span class="text-muted">All</span>' ?></td>
                                            <td><?= $rule['multiplier'] ?>x</td>
                                            <td>₹<?= number_format($rule['fixed_amount'], 2) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $rule['priority'] >= 8 ? 'danger' : ($rule['priority'] >= 5 ? 'warning' : 'secondary') ?>">
                                                    <?= $rule['priority'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $rule['is_active'] ? 'success' : 'danger' ?>">
                                                    <?= $rule['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="form-button-action">
                                                    <button type="button" class="btn btn-link btn-warning btn-lg" data-bs-toggle="tooltip" title="Edit Rule">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-link btn-danger btn-lg" data-bs-toggle="tooltip" title="Delete Rule">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
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
$(document).ready(function() {
    $('#pricing-table').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[ 6, "desc" ]]
    });
});
</script>
