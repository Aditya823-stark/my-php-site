<?php
session_start();
if (!isset($_SESSION['passenger_id'])) {
    header("Location: passenger_login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Passenger Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card p-4">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['passenger_name']) ?>!</h2>
        <p>You are successfully logged in.</p>
        <a href="form.php" class="btn btn-success">Book a Ticket</a>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</div>
</body>
</html>
