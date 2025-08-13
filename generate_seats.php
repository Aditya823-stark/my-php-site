<?php
include("connect/db.php");

$db = (new connect())->myconnect();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $train_id = $_POST['train_id'];
    $journey_date = $_POST['journey_date'];
    $class_type = $_POST['class_type'];
    $seat_count = $_POST['seat_count'];

    $inserted = 0;

    for ($i = 1; $i <= $seat_count; $i++) {
        $seat_no = strtoupper(substr($class_type, 0, 2)) . str_pad($i, 2, '0', STR_PAD_LEFT);

        $exists = mysqli_query($db, "SELECT id FROM seats WHERE 
            train_id = $train_id AND class_type = '$class_type' 
            AND seat_no = '$seat_no' AND journey_date = '$journey_date'");

        if (mysqli_num_rows($exists) == 0) {
            mysqli_query($db, "INSERT INTO seats 
                (train_id, class_type, seat_no, journey_date, status) 
                VALUES 
                ($train_id, '$class_type', '$seat_no', '$journey_date', 'available')");
            $inserted++;
        }
    }

    echo "<p><b>$inserted seat(s) inserted for Train ID $train_id ($class_type) on $journey_date.</b></p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generate Seats</title>
</head>
<body>
    <h2>Generate Seats for Train</h2>
    <form method="post">
        <label>Train ID:</label>
        <input type="number" name="train_id" required><br><br>

        <label>Journey Date:</label>
        <input type="date" name="journey_date" required><br><br>

        <label>Class Type:</label>
        <select name="class_type">
            <option>General</option>
            <option>Sleeper</option>
            <option>AC</option>
            <option>AC Tier 1</option>
            <option>AC Tier 2</option>
            <option>AC Tier 3</option>
        </select><br><br>

        <label>Number of Seats to Generate:</label>
        <input type="number" name="seat_count" value="30" min="1" required><br><br>

        <button type="submit">Generate</button>
    </form>
</body>
</html>
