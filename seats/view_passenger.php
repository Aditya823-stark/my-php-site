<?php
include("./../connect/db.php");
include("./../connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

$id = $_GET['id'] ?? 0;
$q = mysqli_query($db, "SELECT * FROM passengers WHERE id = $id");
$data = mysqli_fetch_assoc($q);

// Get all passengers with same booking details (for multiple seats)
// Match by email, phone, and booking details - this will get all passengers from the same booking
$related_passengers_query = "SELECT * FROM passengers
                            WHERE email = '{$data['email']}'
                            AND phone = '{$data['phone']}'
                            AND train_id = {$data['train_id']}
                            AND journey_date = '{$data['journey_date']}'
                            AND from_station_id = {$data['from_station_id']}
                            AND to_station_id = {$data['to_station_id']}
                            AND class_type = '{$data['class_type']}'
                            AND (status IS NULL OR status != 'cancelled')
                            ORDER BY CAST(seat_no AS UNSIGNED) ASC";

$related_passengers = mysqli_query($db, $related_passengers_query);

$all_passengers = [];
$seen_seats = [];
while ($row = mysqli_fetch_assoc($related_passengers)) {
    if (!in_array($row['seat_no'], $seen_seats)) {
        $all_passengers[] = $row;
        $seen_seats[] = $row['seat_no'];
    }
}


// Debug information
$debug_count = count($all_passengers);
$debug_query = $related_passengers_query;

if (!$data) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Passenger not found!</h4>";
    echo "<p><strong>Passenger ID:</strong> $id</p>";
    echo "<p><strong>Debug Info:</strong> No passenger found with this ID in the database.</p>";
    echo "<p><strong>Possible Solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Check if the passenger was successfully saved to the database</li>";
    echo "<li>Verify the passenger ID in the URL</li>";
    echo "<li>Go back to the booking form and try again</li>";
    echo "</ul>";
    echo "<a href='../form.php' class='btn btn-primary'>🔙 Back to Booking</a>";
    echo "</div>";
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
    <!-- Journey Information -->
    <h5 class="text-primary mb-3"><i class="fas fa-route"></i> Journey Information</h5>
    <table class="table table-bordered mb-4">
      <tr><th>From</th><td><?= $from_station ?></td></tr>
      <tr><th>To</th><td><?= $to_station ?></td></tr>
      <tr><th>Train</th><td><?= $train ?></td></tr>
      <tr><th>Class</th><td><?= $data['class_type'] ?></td></tr>
      <tr><th>Journey Date</th><td><?= date("d M Y", strtotime($data['journey_date'])) ?></td></tr>
      <tr><th>Departure Time</th><td><?php
        $departure_time = $data['departure_time'] ?? '08:00';
        echo '<i class="fas fa-clock text-primary"></i> ' . date('h:i A', strtotime($departure_time));
      ?></td></tr>
      <tr><th>Distance</th><td><?= is_numeric($distance) ? "$distance KM" : $distance ?></td></tr>
      <tr><th>Status</th><td><?= $statusText ?></td></tr>
    </table>

    <!-- Debug Information -->
    <div class="alert alert-info">
        <strong>Booking Info:</strong> Found <?= $debug_count ?> passengers for this booking.
        <br><strong>Current passenger ID:</strong> <?= $data['id'] ?>
        <br><strong>Search criteria:</strong> Email: <?= $data['email'] ?>, Phone: <?= $data['phone'] ?>
        <?php if ($debug_count == 0): ?>
            <br><span class="text-danger"><strong>Issue:</strong> No passengers found with matching criteria. This might indicate a data insertion problem.</span>
        <?php endif; ?>
    </div>

    <!-- Passenger & Seat Details -->
    <?php if (count($all_passengers) > 0): ?>
      <h5 class="text-primary mb-3"><i class="fas fa-users"></i> Passenger & Seat Details (<?= count($all_passengers) ?> Seats)</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead class="table-primary">
            <tr>
              <th>Seat #</th>
              <th>Passenger Name</th>
              <th>Age</th>
              <th>Gender</th>
              <th>Class</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($all_passengers as $passenger): ?>
              <tr>
                <td class="fw-bold text-primary">
                  <i class="fas fa-chair"></i> <?= $passenger['seat_no'] ?? 'N/A' ?>
                </td>
                <td><?= htmlspecialchars($passenger['name']) ?></td>
                <td><?= $passenger['age'] ?? 'N/A' ?></td>
                <td><?= $passenger['gender'] ?? 'N/A' ?></td>
                <td><?= $passenger['class_type'] ?? 'N/A' ?></td>
                <td><?= htmlspecialchars($passenger['email']) ?></td>
                <td><?= $passenger['phone'] ?? 'N/A' ?></td>
                <td>
                  <?php
                  $status = $passenger['status'] ?? 'booked';
                  if ($status === 'cancelled') {
                      echo '<span class="badge bg-danger">Cancelled</span>';
                  } else {
                      echo '<span class="badge bg-success">Confirmed</span>';
                  }
                  ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      
      <!-- Summary for Multiple Passengers -->
      <div class="alert alert-info">
        <h6><i class="fas fa-info-circle"></i> Booking Summary</h6>
        <div class="row">
          <div class="col-md-6">
            <strong>Total Seats:</strong> <?= count($all_passengers) ?><br>
            <strong>Seat Numbers:</strong> <?php
            $seats = array_column($all_passengers, 'seat_no');
            echo implode(', ', array_filter($seats));
            ?>
          </div>
          <div class="col-md-6">
            <strong>Fare per Seat:</strong> ₹<?= number_format($fare, 2) ?><br>
            <strong>Total Fare:</strong> <span class="text-success fw-bold">₹<?= number_format($fare * count($all_passengers), 2) ?></span>
          </div>
        </div>
      </div>
    <?php else: ?>
      <!-- Single Passenger Details -->
      <h5 class="text-primary mb-3"><i class="fas fa-user"></i> Passenger Details</h5>
      <table class="table table-bordered">
        <tr><th>Name</th><td><?= htmlspecialchars($data['name']) ?></td></tr>
        <tr><th>Age</th><td><?= $data['age'] ?? 'N/A' ?></td></tr>
        <tr><th>Gender</th><td><?= $data['gender'] ?? 'N/A' ?></td></tr>
        <tr><th>Class</th><td><?= $data['class_type'] ?? 'N/A' ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($data['email']) ?></td></tr>
        <tr><th>Phone</th><td><?= $data['phone'] ?? 'N/A' ?></td></tr>
        <tr><th>Seat Number</th><td>
          <?php if ($data['seat_no']): ?>
            <span class="badge bg-primary fs-6"><i class="fas fa-chair"></i> <?= $data['seat_no'] ?></span>
          <?php else: ?>
            <span class="text-muted">Not Assigned</span>
          <?php endif; ?>
        </td></tr>
        <tr><th>Fare</th><td>₹<?= number_format($fare, 2) ?></td></tr>
      </table>
    <?php endif; ?>

    <!-- Contact & Payment Information -->
    <h5 class="text-primary mb-3"><i class="fas fa-address-book"></i> Contact & Payment Information</h5>
    <table class="table table-bordered">
      <tr><th>Email</th><td><?= $data['email'] ?></td></tr>
      <tr><th>Phone</th><td><?= $data['phone'] ?></td></tr>
      <tr><th>Payment Status</th><td>
        <?php
        $payment_status = $data['payment_status'] ?? 'Unknown';
        if ($payment_status === 'Paid') {
            echo '<span class="badge bg-success fs-6">✅ Paid</span>';
        } elseif ($payment_status === 'Pending') {
            echo '<span class="badge bg-warning fs-6">⏳ Payment Pending</span>';
        } else {
            echo '<span class="badge bg-secondary fs-6">' . htmlspecialchars($payment_status) . '</span>';
        }
        ?>
      </td></tr>
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
