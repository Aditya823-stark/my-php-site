<?php
require '../vendor/autoload.php';
include("../connect/db.php");
include("../connect/fun.php");

use Mpdf\Mpdf;

$db = (new connect())->myconnect();
$fun = new fun($db);

$id = $_GET['id'] ?? 0;
$q = mysqli_query($db, "SELECT * FROM passengers WHERE id = $id");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    die("Passenger not found.");
}

$statusText = ($data['status'] === 'cancelled') 
    ? '<span style="color:red; font-weight:bold;">Cancelled</span>' 
    : '<span style="color:green; font-weight:bold;">Booked</span>';

$from_station = $fun->get_station_name($data['from_station_id']);
$to_station = $fun->get_station_name($data['to_station_id']);
$train = $fun->get_train_name($data['train_id']);

$route_sql = "SELECT r.fare, r.distance 
              FROM routes r 
              JOIN train_routes tr ON tr.route_id = r.id 
              WHERE r.from_station_id = {$data['from_station_id']} 
                AND r.to_station_id = {$data['to_station_id']} 
                AND tr.train_id = {$data['train_id']} 
              LIMIT 1";
$route_q = mysqli_query($db, $route_sql);
$route = $route_q ? mysqli_fetch_assoc($route_q) : null;

$fare = $route['fare'] ?? $data['fare'] ?? 'Not Available';
$distance = $route['distance'] ?? $data['distance'] ?? 'Not Available';

$mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
$mpdf->WriteHTML('
<style>
body { font-family: "Segoe UI", sans-serif; background-color: #f8f9fa; }
.ticket-container {
  width: 100%;
  padding: 20px;
  background: #fff;
  border-radius: 10px;
  border: 1px solid #ccc;
}
.ticket-header {
  background-color: #0d6efd;
  color: white;
  padding: 10px;
  border-radius: 10px 10px 0 0;
  text-align: center;
}
.ticket-title {
  margin: 0;
  font-size: 22px;
}
.ticket-details th {
  text-align: left;
  width: 35%;
  background-color: #f2f2f2;
}
.table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.table th, .table td {
  border: 1px solid #ddd;
  padding: 8px;
}
</style>

<div class="ticket-container">
  <div class="ticket-header">
    <h2 class="ticket-title">IRCTC E-Ticket</h2>
    <small>Passenger ID: ' . $data['id'] . '</small>
  </div>

  <table class="table">
    <tr><th>Name</th><td>' . htmlspecialchars($data['name']) . '</td></tr>
    <tr><th>Age</th><td>' . $data['age'] . '</td></tr>
    <tr><th>Gender</th><td>' . $data['gender'] . '</td></tr>
    <tr><th>Status</th><td>' . $statusText . '</td></tr>
    <tr><th>Email</th><td>' . $data['email'] . '</td></tr>
    <tr><th>Phone</th><td>' . $data['phone'] . '</td></tr>
    <tr><th>From</th><td>' . $from_station . '</td></tr>
    <tr><th>To</th><td>' . $to_station . '</td></tr>
    <tr><th>Train</th><td>' . $train . '</td></tr>
    <tr><th>Class</th><td>' . $data['class_type'] . '</td></tr>
    <tr><th>Journey Date</th><td>' . date("d M Y", strtotime($data['journey_date'])) . '</td></tr>
    <tr><th>Distance</th><td>' . (is_numeric($distance) ? $distance . ' KM' : $distance) . '</td></tr>
    <tr><th>Fare</th><td>' . (is_numeric($fare) ? '&#8377;' . number_format($fare, 2) : $fare) . '</td></tr>
  </table>
</div>
');

$filename = 'Ticket_' . $data['id'] . '.pdf';
$mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
?>
