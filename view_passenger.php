<?php
include("connect/db.php");
include("connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

$id = $_GET['id'] ?? 0;
$q = mysqli_query($db, "SELECT * FROM passengers WHERE id = $id");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    echo "Passenger not found.";
    exit;
}

$statusText = ($data['status'] === 'cancelled') 
    ? '<span class="text-danger fw-bold">Cancelled</span>' 
    : '<span class="text-success fw-bold">Booked</span>';

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

// PDF path setup
$pdf_file = "tickets/ticket_{$data['id']}.pdf";
?>

<!DOCTYPE html>
<html>
<head>
  <title>Passenger E-Ticket</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', sans-serif;
    }

    .ticket-container {
      max-width: 800px;
      margin: 50px auto;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      padding: 30px;
    }

    .ticket-header {
      background-color: #0d6efd;
      color: white;
      padding: 20px;
      border-radius: 10px 10px 0 0;
      text-align: center;
    }

    .ticket-title {
      margin: 0;
      font-size: 24px;
    }

    .ticket-details th {
      width: 30%;
      background-color: #f8f9fa;
    }

    @media print {
      .no-print {
        display: none;
      }
    }
  </style>
</head>
<body>

<div class="ticket-container">
  <div class="ticket-header">
    <h2 class="ticket-title">IRCTC E-Ticket</h2>
    <small>Passenger ID: <?= $data['id'] ?></small>
  </div>

  <div class="ticket-details mt-4">
    <table class="table table-bordered">
      <tr><th>Name</th><td><?= htmlspecialchars($data['name']) ?></td></tr>
      <tr><th>Age</th><td><?= $data['age'] ?></td></tr>
      <tr><th>Gender</th><td><?= $data['gender'] ?></td></tr>
      <tr><th>Status</th><td><?= $statusText ?></td></tr>
      <tr><th>Email</th><td><?= $data['email'] ?></td></tr>
      <tr><th>Phone</th><td><?= $data['phone'] ?></td></tr>
      <tr><th>From</th><td><?= $from_station ?></td></tr>
      <tr><th>To</th><td><?= $to_station ?></td></tr>
      <tr><th>Train</th><td><?= $train ?></td></tr>
      <tr><th>Class</th><td><?= $data['class_type'] ?></td></tr>
      <tr><th>Journey Date</th><td><?= date("d M Y", strtotime($data['journey_date'])) ?></td></tr>
      <tr><th>Distance</th><td><?= is_numeric($distance) ? "$distance KM" : $distance ?></td></tr>
      <tr><th>Fare</th><td><?= is_numeric($fare) ? "₹" . number_format($fare, 2) : $fare ?></td></tr>
    </table>
  </div>

  <div class="row no-print mt-4 g-3 justify-content-center">
    <div class="col-md-3">
      <a href="javascript:window.print()" class="btn btn-primary w-100 py-2 fw-bold rounded">🖨️ Print Ticket</a>
    </div>

    <?php if ($data['status'] !== 'cancelled'): ?>
      <div class="col-md-3">
        <form action="cancel_ticket.php" method="post" onsubmit="return confirm('Are you sure you want to cancel this ticket?');">
          <input type="hidden" name="passenger_id" value="<?= $data['id'] ?>">
          <input type="hidden" name="email" value="<?= $data['email'] ?>">
          <input type="hidden" name="name" value="<?= $data['name'] ?>">
          <button type="submit" class="btn btn-danger w-100 py-2 fw-bold rounded">❌ Cancel Ticket</button>
        </form>
      </div>
    <?php endif; ?>

    <?php if (file_exists($pdf_file)): ?>
      <div class="col-md-3">
        <a href="<?= $pdf_file ?>" class="btn btn-success w-100 py-2 fw-bold rounded" download>
          📄 Download PDF
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
