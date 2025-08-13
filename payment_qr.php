<?php
$name = $_POST['name'] ?? '';
$fare = $_POST['fare'] ?? '';

if (!$fare || !$name) {
    die("❌ Missing name or fare for QR generation.");
}

$upi_id = 'adityathakre82@ybl';
$upi_uri = "upi://pay?pa=$upi_id&pn=$name&am=$fare&cu=INR";
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($upi_uri);
$imageData = @file_get_contents($qr_url);
if (!$imageData) {
    die("❌ QR image fetch failed.");
}
$base64 = base64_encode($imageData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .timer-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: auto;
            color: green;
            border: 5px solid #0d6efd;
        }
        .qr-box {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        #continue-btn {
            display: none;
        }
    </style>
</head>
<body class="bg-light">
<div class="container text-center mt-5">
    <div class="qr-box">
        <h3>Scan to Pay via UPI</h3>
        <p><strong>Name:</strong> <?= htmlspecialchars($name) ?></p>
        <p><strong>Amount:</strong> ₹<?= htmlspecialchars($fare) ?></p>
        <img src="data:image/png;base64,<?= $base64 ?>" alt="QR Code" class="mb-3">
        <p id="wait-msg">Waiting for payment confirmation...</p>
        <div class="timer-circle" id="countdown">30</div>

        <form method="POST" action="add_passenger.php">
            <?php foreach ($_POST as $key => $value): ?>
                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
            <?php endforeach; ?>
            <input type="hidden" name="payment_mode" value="Online">
            <button type="submit" class="btn btn-success mt-4" id="continue-btn">✅ Continue After Payment</button>
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
            countdownEl.style.color = time <= 10 ? 'red' : 'green';
            continueBtn.style.display = "inline-block";
            waitMsg.textContent = "If paid, click the button or wait...";
        }

        if (time <= 0) {
            clearInterval(timer);
            waitMsg.textContent = "Redirecting...";
            document.querySelector("form").submit();
        }
    }, 1000);
</script>
</body>
</html>
