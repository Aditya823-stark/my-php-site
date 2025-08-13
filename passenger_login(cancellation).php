<?php
include("connect/db.php");
include("connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

session_start();

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$email && !$password) {
    $error = "Please enter email and password.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = mysqli_query($db, "SELECT * FROM passengers WHERE email = '$email' AND password = '$password' LIMIT 1");
    if (mysqli_num_rows($login)) {
        $_SESSION['passenger'] = mysqli_fetch_assoc($login);
    } else {
        $error = "Invalid credentials.";
    }
}

// Handle cancellation
if (isset($_GET['cancel']) && $_GET['cancel'] == 'true' && isset($_SESSION['passenger'])) {
    $id = $_SESSION['passenger']['id'];
    mysqli_query($db, "DELETE FROM passengers WHERE id = $id");
    session_destroy();
    header("Location: passenger_login.php?cancelled=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Passenger Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-box {
            max-width: 450px;
            margin: 60px auto;
            padding: 30px;
            border-radius: 12px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .ticket-box {
            max-width: 650px;
            margin: 60px auto;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .confirm-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 15px;
            margin-top: 15px;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container">
    <?php if (isset($_GET['cancelled'])): ?>
        <div class="alert alert-success mt-5 text-center">
            Your ticket has been cancelled successfully.
        </div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['passenger'])): ?>
        <div class="login-box">
            <h4 class="mb-3 text-center">Passenger Login</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
                <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                <div class="d-grid">
                    <button class="btn btn-primary" type="submit">Login</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <?php $p = $_SESSION['passenger']; ?>
        <div class="ticket-box">
            <h4 class="text-center mb-4">Your Ticket</h4>
            <div class="mb-2"><strong>Name:</strong> <?= htmlspecialchars($p['name']) ?></div>
            <div class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($p['email']) ?></div>
            <div class="mb-2"><strong>Phone:</strong> <?= htmlspecialchars($p['phone']) ?></div>
            <div class="mb-2"><strong>From:</strong> <?= $fun->get_station_name($p['from_station_id']) ?></div>
            <div class="mb-2"><strong>To:</strong> <?= $fun->get_station_name($p['to_station_id']) ?></div>
            <div class="mb-2"><strong>Date:</strong> <?= $p['journey_date'] ?></div>
            <div class="mb-2"><strong>Class:</strong> <?= $p['class_type'] ?></div>
            <div class="mb-2"><strong>Fare:</strong> ₹<?= $p['fare'] ?> | <strong>Distance:</strong> <?= $p['distance'] ?> km</div>

            <div class="mt-4">
                <button class="btn btn-danger" onclick="showCancelConfirm()">❌ Cancel Ticket</button>
            </div>

            <div id="confirmBox" class="confirm-box mt-3 d-none">
                <p class="mb-2"><strong>Are you sure you want to cancel your ticket?</strong></p>
                <a href="?cancel=true" class="btn btn-outline-danger">Yes, Cancel</a>
                <button class="btn btn-outline-secondary" onclick="hideCancelConfirm()">No, Keep Ticket</button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function showCancelConfirm() {
        document.getElementById("confirmBox").classList.remove("d-none");
    }

    function hideCancelConfirm() {
        document.getElementById("confirmBox").classList.add("d-none");
    }
</script>

</body>
</html>
