$passenger_id = mysqli_insert_id($db);
$fare = $your_generated_fare;

echo "<h4>Scan QR to Pay ₹$fare</h4>";
echo "<img src='payment_qr.php?fare=$fare&id=$passenger_id' />";
