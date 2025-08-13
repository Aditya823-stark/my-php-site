<!-- select_seat_count.php -->
<?php extract($_POST); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Select Seat Count</title>
    <style>
        .seat-count { display: flex; justify-content: center; gap: 10px; margin-top: 50px; }
        .seat-count button { width: 40px; height: 40px; font-size: 16px; }
    </style>
</head>
<body>
<h2 align="center">How many seats do you want?</h2>
<form action="select_seats.php" method="post" style="text-align:center">
    <?php foreach ($_POST as $k => $v) echo "<input type='hidden' name='$k' value='$v'>"; ?>
    <div class="seat-count">
        <?php for ($i = 1; $i <= 10; $i++): ?>
            <button type="submit" name="seat_count" value="<?= $i ?>"><?= $i ?></button>
        <?php endfor; ?>
    </div>
</form>
</body>
</html>
