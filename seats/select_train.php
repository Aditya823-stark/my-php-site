<?php
include("./../connect/db.php");
include("./../connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

// Validate required fields
$required = ['name', 'age', 'gender', 'email', 'phone', 'password', 'from_station_id', 'to_station_id', 'journey_date', 'class_type', 'payment_mode'];
foreach ($required as $field) {
    if (!isset($_POST[$field])) {
        die("Missing required field: $field");
    }
}

// Collect passenger details
$passenger = [];
foreach ($required as $field) {
    $passenger[$field] = $_POST[$field];
}

// Fetch available trains
$trains = $fun->get_trains_between($passenger['from_station_id'], $passenger['to_station_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Train</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f3f6;
            margin: 0;
            padding: 20px;
        }

        .train-box {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .train-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .train-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            color: #555;
            font-size: 14px;
        }

        .train-meta img {
            height: 20px;
            vertical-align: middle;
        }

        .time-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .time-btn {
            padding: 10px 16px;
            border-radius: 6px;
            border: 2px solid #ccc;
            background-color: #fff;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            color: #333;
            text-align: center;
        }

        .time-btn:hover {
            background-color: #e6f2ff;
            border-color: #007bff;
        }

        .highlight-yellow {
            border-color: #f1c40f;
            color: #f39c12;
        }

        .highlight-green {
            border-color: #2ecc71;
            color: #27ae60;
        }

        .note {
            margin-top: 10px;
            font-size: 13px;
            color: #999;
        }

        .legend {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #555;
        }

        .legend span {
            margin: 0 10px;
            padding: 6px 10px;
            border-radius: 5px;
        }

        .green-box {
            background-color: #e9fbe9;
            color: #27ae60;
            border: 1px solid #2ecc71;
        }

        .yellow-box {
            background-color: #fff9e6;
            color: #f39c12;
            border: 1px solid #f1c40f;
        }
    </style>
</head>
<body>

<h2 style="text-align:center; margin-bottom: 10px;">🚄 Choose a Train & Departure Time</h2>
<p style="text-align:center; margin-bottom: 30px; font-size: 15px; color: #555;">
    <strong>Note:</strong> Yellow color denotes <b>Food & Beverages</b> availability.
</p>

<?php if (!empty($trains)) { ?>
    <?php foreach ($trains as $train): ?>
        <div class="train-box">
            <div class="train-header">
                <?= htmlspecialchars($train['train_name']) ?> (Train #<?= htmlspecialchars($train['train_id']) ?>)
            </div>
            <div class="train-meta">
                <span>Distance: <?= htmlspecialchars($train['distance']) ?> km</span>
                <span>Fare: ₹<?= htmlspecialchars($train['fare']) ?></span>
                <span><img src="https://img.icons8.com/color/20/restaurant.png"/> Food & Beverages</span>
            </div>

            <form action="select_seats.php" method="POST">
                <?php foreach ($passenger as $key => $value): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endforeach; ?>
                <input type="hidden" name="train_id" value="<?= htmlspecialchars($train['train_id']) ?>">

                <div class="time-grid">
                    <?php
                    $times = ['08:00 AM', '10:00 AM', '12:00 PM', '02:00 PM', '04:00 PM', '06:00 PM', '08:00 PM', '10:00 PM'];
                    foreach ($times as $i => $time):
                        $highlight = $i % 2 === 0 ? 'highlight-yellow' : 'highlight-green';
                    ?>
                        <button type="submit" name="departure_time" value="<?= $time ?>" class="time-btn <?= $highlight ?>">
                            <?= $time ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </form>

            <div class="note"><?= ($train['train_id'] % 2 === 0) ? 'Non-cancellable' : 'Cancellation available' ?></div>
        </div>
    <?php endforeach; ?>

    <div class="legend">
        <span class="yellow-box">🥪 Food & Beverages</span>
        <span class="green-box">✅ Standard</span>
    </div>
<?php } else { ?>
    <p style="text-align:center; font-size:18px; color:#e74c3c;">❌ No trains found for the selected route.</p>
<?php } ?>

</body>
</html>
