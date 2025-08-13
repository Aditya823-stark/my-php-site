<?php
include("../connect/db.php");
$db = (new connect())->myconnect();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $q = mysqli_query($db, "INSERT INTO passenger_users (email, phone, password) VALUES ('$email', '$phone', '$password')");

    if ($q) {
        echo "<h3>✅ Registered! <a href='passenger_login.php'>Login Now</a></h3>";
    } else {
        echo "<h3>❌ Registration failed!</h3>";
    }
}
?>

<form method="POST">
    <h2>Register</h2>
    Email: <input name="email" type="email" required><br>
    Phone: <input name="phone" required><br>
    Password: <input name="password" type="password" required><br>
    <button type="submit">Register</button>
</form>
