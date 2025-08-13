<!-- confirm_booking.php -->
<?php extract($_POST); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking Summary</title>
    <style>
        .summary { max-width: 600px; margin: auto; background: #e8f4ea; padding: 20px; border-radius: 10px; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <div class="summary">
        <h2>Booking Summary</h2>
        <p><b>Name:</b> <?= $name ?></p>
        <p><b>Email:</b> <?= $email ?></p>
        <p><b>Phone:</b> <?= $phone ?></p>
        <p><b>Train ID:</b> <?= $train_id ?> | <b>Time:</b> <?= $departure_time ?></p>
        <p><b>Seats:</b> <?= $selected_seats ?></p>
        <p><b>Journey Date:</b> <?= $journey_date ?></p>
        <form method="post" action="add_passenger.php">
            <?php foreach ($_POST as $k => $v) echo "<input type='hidden' name='$k' value='$v'>"; ?>
            <center><button type="submit">Pay & Book</button></center>
        </form>
    </div>
</body>
</html>
