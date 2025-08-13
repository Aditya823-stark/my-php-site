<?php
include("connect/db.php");
include("connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

$message = "";
$ticket = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $phone = $_POST['phone'];

  $ticket = $fun->get_passenger_by_credentials($email, $phone);
  if (!$ticket) {
    $message = "Invalid login details or ticket not found.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Passenger Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f5f8fa; }
    .container { max-width: 700px; margin-top: 50px; }
    .ticket-card { border: 2px dashed #333; border-radius: 15px; padding: 25px; background: #fff; }
  </style>
</head>
<body>
<div class="container">
  <h3 class="text-center mb-4">Passenger Ticket Login</h3>

  <?php if ($message): ?>
    <div class="alert alert-danger"><?= $message ?></div>
  <?php endif; ?>

  <?php if (!$ticket): ?>
    <form method="POST" class="card p-4 shadow-sm">
      <div class="mb-3">
        <label>Email:</label>
        <input type="email" name="email" required class="form-control">
      </div>
      <div class="mb-3">
        <label>Phone:</label>
        <input type="text" name="phone" required class="form-control">
      </div>
      <button class="btn btn-primary">View Ticket</button>
    </form>
  <?php else: ?>
    <div class="ticket-card shadow-sm">
      <h4 class="text-center text-primary">Your Ticket</h4>
      <hr>
      <p><strong>Name:</strong> <?= $ticket['name'] ?> | <strong>Gender:</strong> <?= $ticket['gender'] ?></p>
      <p><strong>Age:</strong> <?= $ticket['age'] ?> | <strong>Phone:</strong> <?= $ticket['phone'] ?></p>
      <p><strong>Email:</strong> <?= $ticket['email'] ?></p>
      <hr>
      <p><strong>From:</strong> <?= $ticket['from_station'] ?> → <strong>To:</strong> <?= $ticket['to_station'] ?></p>
      <p><strong>Class:</strong> <?= $ticket['class_type'] ?> | <strong>Date:</strong> <?= $ticket['journey_date'] ?></p>
      <p><strong>Distance:</strong> <?= $ticket['distance'] ?> km | <strong>Fare:</strong> ₹<?= $ticket['fare'] ?></p>
      <hr>
      <form method="POST" action="cancel_ticket.php" onsubmit="return confirm('Are you sure you want to cancel this ticket?');">
        <input type="hidden" name="id" value="<?= $ticket['id'] ?>">
        <button class="btn btn-danger">Cancel Ticket</button>
      </form>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
