<?php
include("./../connect/db.php");

$db = (new connect())->myconnect();

// Check if seat_no column exists, if not add it
$check_seat_no = mysqli_query($db, "SHOW COLUMNS FROM passengers LIKE 'seat_no'");
if (mysqli_num_rows($check_seat_no) == 0) {
    $add_seat_no = "ALTER TABLE passengers ADD COLUMN seat_no VARCHAR(10) NULL AFTER class_type";
    if (mysqli_query($db, $add_seat_no)) {
        echo "✅ Added seat_no column to passengers table<br>";
    } else {
        echo "❌ Failed to add seat_no column: " . mysqli_error($db) . "<br>";
    }
} else {
    echo "✅ seat_no column already exists<br>";
}

// Check if payment_status column exists, if not add it
$check_payment_status = mysqli_query($db, "SHOW COLUMNS FROM passengers LIKE 'payment_status'");
if (mysqli_num_rows($check_payment_status) == 0) {
    $add_payment_status = "ALTER TABLE passengers ADD COLUMN payment_status VARCHAR(20) DEFAULT 'Pending' AFTER distance";
    if (mysqli_query($db, $add_payment_status)) {
        echo "✅ Added payment_status column to passengers table<br>";
    } else {
        echo "❌ Failed to add payment_status column: " . mysqli_error($db) . "<br>";
    }
} else {
    echo "✅ payment_status column already exists<br>";
}

// Check if status column exists, if not add it
$check_status = mysqli_query($db, "SHOW COLUMNS FROM passengers LIKE 'status'");
if (mysqli_num_rows($check_status) == 0) {
    $add_status = "ALTER TABLE passengers ADD COLUMN status VARCHAR(20) DEFAULT 'booked' AFTER payment_status";
    if (mysqli_query($db, $add_status)) {
        echo "✅ Added status column to passengers table<br>";
    } else {
        echo "❌ Failed to add status column: " . mysqli_error($db) . "<br>";
    }
} else {
    echo "✅ status column already exists<br>";
}

echo "<br><strong>Database update completed!</strong><br>";
echo "<a href='form.php'>Go to Booking Form</a>";
?>