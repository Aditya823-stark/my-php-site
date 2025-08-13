<?php
session_start();
include("../connect/db.php");

$db = (new connect())->myconnect();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($db, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM passengers WHERE email = '$email' ORDER BY id DESC LIMIT 1";
    $res = mysqli_query($db, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        
        if (password_verify($password, $row['password'])) {
            $_SESSION['passenger_id'] = $row['id'];
            $_SESSION['passenger_user_id'] = $row['passenger_user_id'];
            $_SESSION['passenger_name'] = $row['name'];
            header("Location: passenger_dashboard.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Passenger Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container p-5">
    <h2 class="mb-4">Passenger Login</h2>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>Email address</label>
            <input type="email" name="email" required class="form-control">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>
</body>
</html>
