<?php
require '../vendor/autoload.php';
use Mpdf\Mpdf;

include("../connect/db.php");
include("../connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

$id = $_GET['id'] ?? 0;
$passenger = $fun->get_passenger_by_id($id);

if (!$passenger) {
    die("Passenger not found.");
}

$name = $passenger['name'];
$from = $fun->get_station_name($passenger['from_station_id']);
$to = $fun->get_station_name($passenger['to_station_id']);
$train = $fun->get_train_name($passenger['train_id']);
$date = $passenger['journey_date'];
$class = $passenger['class_type'];
$fare = $fun->format_rupees($passenger['fare']);
$distance = $passenger['distance'];

$html = "
    <h2 style='text-align:center;'>Railway E-Ticket</h2>
    <p><strong>Name:</strong> $name</p>
    <p><strong>From:</strong> $from</p>
    <p><strong>To:</strong> $to</p>
    <p><strong>Train:</strong> $train</p>
    <p><strong>Date:</strong> $date</p>
    <p><strong>Class:</strong> $class</p>
    <p><strong>Fare:</strong> ₹$fare</p>
    <p><strong>Distance:</strong> $distance km</p>
    <p><strong>Status:</strong> {$passenger['status']}</p>
";

$mpdf = new Mpdf();
$mpdf->WriteHTML($html);
$mpdf->Output("Ticket_{$name}.pdf", 'D'); // 'D' = Download
