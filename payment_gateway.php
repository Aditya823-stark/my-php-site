<?php
require '../vendor/autoload.php'; // adjust path if needed
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$name = $_GET['name'] ?? '';
$fare = $_GET['fare'] ?? '';

if (empty($name) || empty($fare)) {
    die("Missing field: name or fare");
}

$upi_id = "adityathakre82@ybl";
$upi_url = "upi://pay?pa=$upi_id&pn=" . urlencode($name) . "&am=" . urlencode($fare) . "&cu=INR";

// Generate QR Code
$qr = QrCode::create($upi_url);
$writer = new PngWriter();
$result = $writer->write($qr);

// Set header and display image
header('Content-Type: image/png');
echo $result->getString();
exit;
