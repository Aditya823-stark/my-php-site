<?php
include("./../connect/db.php");
include("./../connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

// Fetch POST data
$name = $_POST['name'] ?? '';
$fare = $_POST['fare'] ?? '';
$train_id = $_POST['train_id'] ?? '';
$from_station_id = $_POST['from_station_id'] ?? '';
$to_station_id = $_POST['to_station_id'] ?? '';
$selected_seats = $_POST['selected_seats'] ?? '';
$seat_count = $_POST['seat_count'] ?? 1;
$requested_seats = $_POST['requested_seats'] ?? $seat_count;
$vehicle_type = $_POST['vehicle_type'] ?? '';

// If fare is not provided, calculate it from route data
if (!$fare && $train_id && $from_station_id && $to_station_id) {
    $route = $fun->get_route($from_station_id, $to_station_id, $train_id);
    if ($route) {
        $fare_per_seat = $route['fare'];
        $total_seats = !empty($selected_seats) ? count(explode(',', $selected_seats)) : $requested_seats;
        $fare = $fare_per_seat * $total_seats; // Multiply by actual number of seats
    }
} else {
    // If fare is provided, ensure it's calculated for all seats
    $route = $fun->get_route($from_station_id, $to_station_id, $train_id);
    $fare_per_seat = $route['fare'] ?? ($fare / max($seat_count, 1));
    $total_seats = !empty($selected_seats) ? count(explode(',', $selected_seats)) : $requested_seats;
    $fare = $fare_per_seat * $total_seats;
}

// Check if required data is missing
if (!$fare || !$name) {
    echo "<div style='color: red; text-align: center; margin: 20px;'>";
    echo "<h3>❌ Missing required data for QR generation</h3>";
    echo "<p><strong>Debug Info:</strong></p>";
    echo "<p>Name: " . ($name ? htmlspecialchars($name) : 'MISSING') . "</p>";
    echo "<p>Fare: " . ($fare ? "₹" . htmlspecialchars($fare) : 'MISSING') . "</p>";
    echo "<p>Train ID: " . ($train_id ? htmlspecialchars($train_id) : 'MISSING') . "</p>";
    echo "<p>From Station: " . ($from_station_id ? htmlspecialchars($from_station_id) : 'MISSING') . "</p>";
    echo "<p>To Station: " . ($to_station_id ? htmlspecialchars($to_station_id) : 'MISSING') . "</p>";
    echo "<hr><p><strong>All POST Data:</strong></p><pre>";
    print_r($_POST);
    echo "</pre></div>";
    die();
}

// UPI ID for payment
$upi_id = 'adityathakre82@ybl';

// Clean and validate the data
$clean_name = trim($name);
$clean_fare = number_format((float)$fare, 2, '.', '');

// Build UPI URI with proper encoding
$upi_uri = "upi://pay?pa=" . urlencode($upi_id) .
           "&pn=" . urlencode($clean_name) .
           "&am=" . urlencode($clean_fare) .
           "&cu=INR" .
           "&tn=" . urlencode("Railway Ticket Payment");

// Generate QR code URL
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($upi_uri);

// Fetch QR image data with better error handling
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET'
    ]
]);

$imageData = @file_get_contents($qr_url, false, $context);
if (!$imageData) {
    echo "<div style='color: red; text-align: center; margin: 20px;'>";
    echo "<h3>❌ QR Code Generation Failed</h3>";
    echo "<p>Unable to generate QR code. Please try again or contact support.</p>";
    echo "<p><strong>Payment Details:</strong></p>";
    echo "<p>Name: " . htmlspecialchars($clean_name) . "</p>";
    echo "<p>Amount: ₹" . htmlspecialchars($clean_fare) . "</p>";
    echo "<p>UPI ID: " . htmlspecialchars($upi_id) . "</p>";
    echo "</div>";
    die();
}

// Encode QR image to base64 for embedding in the HTML
$base64 = base64_encode($imageData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .payment-container {
            max-width: 500px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .timer-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 20px auto;
            color: white;
            border: 6px solid #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .qr-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .qr-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        
        .qr-header h3 {
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .payment-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            border-left: 5px solid #667eea;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        
        .detail-value {
            color: #212529;
            font-weight: 500;
        }
        
        .qr-code-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 20px 0;
            display: inline-block;
        }
        
        .qr-code-container img {
            border-radius: 10px;
        }
        
        .total-amount {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px;
            border-radius: 15px;
            margin: 20px 0;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .upi-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
            color: #1976d2;
        }
        
        .status-message {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            font-weight: 500;
        }
        
        #continue-btn {
            display: none;
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            transition: all 0.3s ease;
        }
        
        #continue-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .payment-steps {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .payment-steps h6 {
            color: #667eea;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .payment-steps ol {
            margin: 0;
            padding-left: 20px;
        }
        
        .payment-steps li {
            margin-bottom: 8px;
            color: #495057;
        }
        
        .icon {
            font-size: 1.2em;
            margin-right: 8px;
        }
    </style>
</head>
<body>
<div class="payment-container">
    <div class="qr-box">
        <div class="qr-header">
            <h3><span class="icon">📱</span>Scan to Pay via UPI</h3>
        </div>
        
        <div class="payment-details">
            <div class="detail-row">
                <span class="detail-label"><span class="icon">👤</span>Passenger Name:</span>
                <span class="detail-value"><?= htmlspecialchars($clean_name) ?></span>
            </div>
            
            <?php if (!empty($selected_seats)): ?>
                <div class="detail-row">
                    <span class="detail-label"><span class="icon">🪑</span>Selected Seats:</span>
                    <span class="detail-value"><?= htmlspecialchars($selected_seats) ?> (<?= count(explode(',', $selected_seats)) ?> seat<?= count(explode(',', $selected_seats)) > 1 ? 's' : '' ?>)</span>
                </div>
            <?php else: ?>
                <div class="detail-row">
                    <span class="detail-label"><span class="icon">🪑</span>Requested Seats:</span>
                    <span class="detail-value"><?= $requested_seats ?> seat<?= $requested_seats > 1 ? 's' : '' ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($vehicle_type)): ?>
                <div class="detail-row">
                    <span class="detail-label"><span class="icon">🚗</span>Vehicle Type:</span>
                    <span class="detail-value"><?= htmlspecialchars($vehicle_type) ?></span>
                </div>
            <?php endif; ?>
            
            <div class="detail-row">
                <span class="detail-label"><span class="icon">💰</span>Fare per Seat:</span>
                <span class="detail-value">₹<?= number_format($fare_per_seat ?? 0, 2) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><span class="icon">🎫</span>Total Seats:</span>
                <span class="detail-value"><?= $total_seats ?></span>
            </div>
        </div>
        
        <div class="total-amount">
            <span class="icon">💳</span>Total Amount: ₹<?= htmlspecialchars($clean_fare) ?>
        </div>
        
        <div class="upi-info">
            <strong><span class="icon">🏦</span>UPI ID:</strong> <?= htmlspecialchars($upi_id) ?>
        </div>
        
        <div class="qr-code-container">
            <img src="data:image/png;base64,<?= $base64 ?>" alt="QR Code" width="250" height="250">
        </div>
        
        <div class="payment-steps">
            <h6><span class="icon">📋</span>How to Pay:</h6>
            <ol>
                <li>Open any UPI app (PhonePe, Google Pay, Paytm, etc.)</li>
                <li>Scan the QR code above</li>
                <li>Verify the amount and UPI ID</li>
                <li>Complete the payment</li>
                <li>Click "Continue" button below after payment</li>
            </ol>
        </div>
        
        <div class="status-message" id="wait-msg">
            <span class="icon">⏳</span>Waiting for payment confirmation...
        </div>
        
        <div class="timer-circle" id="countdown">30</div>

        <form method="POST" action="add_passenger.php">
            <!-- Pass the form data to the next page -->
            <?php foreach ($_POST as $key => $value): ?>
                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
            <?php endforeach; ?>
            <input type="hidden" name="payment_mode" value="Online">
            <button type="submit" id="continue-btn"><span class="icon">✅</span>Continue After Payment</button>
        </form>
    </div>
</div>

<script>
    let time = 30;
    const countdownEl = document.getElementById("countdown");
    const waitMsg = document.getElementById("wait-msg");
    const continueBtn = document.getElementById("continue-btn");

    const timer = setInterval(() => {
        time--;
        countdownEl.textContent = time;

        if (time <= 20) {
            countdownEl.style.background = time <= 10 ? 'linear-gradient(135deg, #dc3545, #c82333)' : 'linear-gradient(135deg, #ffc107, #e0a800)';
            continueBtn.style.display = "inline-block";
            waitMsg.innerHTML = '<span class="icon">💳</span>If paid, click the button or wait...';
        }

        if (time <= 0) {
            clearInterval(timer);
            waitMsg.innerHTML = '<span class="icon">🔄</span>Redirecting...';
            document.querySelector("form").submit();
        }
    }, 1000);
</script>
</body>
</html>
