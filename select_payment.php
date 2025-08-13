<?php
include("connect/db.php");

$formData = $_POST;

// Validate required fields
if (!isset($formData['from_station'], $formData['to_station'])) {
    die("❌ Missing route data.");
}

// Sanitize input
$db = (new connect())->myconnect();
$from = (int)$formData['from_station'];
$to = (int)$formData['to_station'];

$fare = 0;
$query = mysqli_query($db, "SELECT fare FROM routes WHERE from_station_id=$from AND to_station_id=$to LIMIT 1");

if ($query && mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    $fare = $row['fare'];
} else {
    die("❌ Route not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Select Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5 text-center">
    <h3 class="mb-4">Select Payment Method</h3>

    <form action="payment_qr.php" method="post" style="display:inline-block; margin-right:20px;">
        <?php foreach ($formData as $key => $val): ?>
            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
        <?php endforeach; ?>
        <input type="hidden" name="fare" value="<?= $fare ?>">
        <button type="submit" class="btn btn-success btn-lg">Pay Online (PhonePe)</button>
    </form>

    <form action="add_passenger.php" method="post" style="display:inline-block;">
        <?php foreach ($formData as $key => $val): ?>
            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
        <?php endforeach; ?>
        <input type="hidden" name="fare" value="<?= $fare ?>">
        <input type="hidden" name="payment_mode" value="Offline">
        <button type="submit" class="btn btn-secondary btn-lg">Pay Offline</button>
    </form>
</div>
</body>
</html>
