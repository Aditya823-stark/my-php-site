<?php
include("connect/db.php");
include("connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

// Fetch all stations and trains
$stations = mysqli_query($db, "SELECT * FROM stations");
$trains = mysqli_query($db, "SELECT * FROM trains");

// Get submitted values (if any)
$name = $_POST['name'] ?? '';
$age = $_POST['age'] ?? '';
$gender = $_POST['gender'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$password = $_POST['password'] ?? '';
$from_station = $_POST['from_station_id'] ?? '';
$to_station = $_POST['to_station_id'] ?? '';
$train_id = $_POST['train_id'] ?? '';
$class_type = $_POST['class_type'] ?? '';
$journey_date = $_POST['journey_date'] ?? '';
$action = $_POST['action'] ?? '';

$selected_fare = '';
$selected_distance = '';

if ($from_station && $to_station && $train_id) {
    $route_sql = "SELECT r.fare, r.distance 
                  FROM routes r
                  JOIN train_routes tr ON tr.route_id = r.id
                  WHERE r.from_station_id = $from_station 
                    AND r.to_station_id = $to_station 
                    AND tr.train_id = $train_id
                  LIMIT 1";
    $res = mysqli_query($db, $route_sql);
    if (mysqli_num_rows($res)) {
        $row = mysqli_fetch_assoc($res);
        $selected_fare = $row['fare'];
        $selected_distance = $row['distance'];
    }
}

$routes = mysqli_query($db, "
    SELECT 
        r.id, 
        s1.name AS from_station, 
        s2.name AS to_station,
        r.distance,
        r.fare,
        t.name AS train_name
    FROM routes r
    JOIN stations s1 ON r.from_station_id = s1.id
    JOIN stations s2 ON r.to_station_id = s2.id
    JOIN train_routes tr ON tr.route_id = r.id
    JOIN trains t ON tr.train_id = t.id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Railway Ticket Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .route-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
        }
        .form-section {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4 text-center">Railway Ticket Booking</h2>

    <div class="row">
        <!-- Booking Form -->
        <div class="col-md-6">
            <div class="form-section">
<form method="POST" action="<?= ($action === 'preview') ? 'form.php' : 'payment_qr.php' ?>">



                    <input type="hidden" name="action" value="submit">
                    <input type="hidden" name="fare" value="<?= $selected_fare ?>">
                    <input type="hidden" name="distance" value="<?= $selected_distance ?>">

                    <h5 class="mb-3">Personal Details</h5>
                    <input type="text" name="name" class="form-control mb-2" placeholder="Full Name" value="<?= htmlspecialchars($name) ?>" required>
                    <input type="number" name="age" class="form-control mb-2" placeholder="Age" value="<?= htmlspecialchars($age) ?>" required>
                    <select name="gender" class="form-control mb-2" required>
                        <option value="">Gender</option>
                        <option <?= ($gender == 'Male') ? 'selected' : '' ?>>Male</option>
                        <option <?= ($gender == 'Female') ? 'selected' : '' ?>>Female</option>
                        <option <?= ($gender == 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>

                    <h5 class="mt-3 mb-3">Contact Details</h5>
                    <input type="email" name="email" class="form-control mb-2" placeholder="Email" value="<?= htmlspecialchars($email) ?>" required>
                    <input type="text" name="phone" class="form-control mb-2" placeholder="Phone Number" value="<?= htmlspecialchars($phone) ?>" required>
                    <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>

                    <h5 class="mt-3 mb-3">Journey Details</h5>
                    <select name="from_station_id" class="form-control mb-2" required>
                        <option value="">From Station</option>
                        <?php mysqli_data_seek($stations, 0); while($row = mysqli_fetch_assoc($stations)) { ?>
                            <option value="<?= $row['id'] ?>" <?= ($row['id'] == $from_station) ? 'selected' : '' ?>>
                                <?= $row['name'] ?>
                            </option>
                        <?php } ?>
                    </select>

                    <select name="to_station_id" class="form-control mb-2" required>
                        <option value="">To Station</option>
                        <?php mysqli_data_seek($stations, 0); while($row = mysqli_fetch_assoc($stations)) { ?>
                            <option value="<?= $row['id'] ?>" <?= ($row['id'] == $to_station) ? 'selected' : '' ?>>
                                <?= $row['name'] ?>
                            </option>
                        <?php } ?>
                    </select>

                    <select name="train_id" class="form-control mb-2" onchange="this.form.action='form.php'; this.form.querySelector('[name=action]').value='preview'; this.form.submit();" required>
                        <option value="">Select Train</option>
                        <?php while($train = mysqli_fetch_assoc($trains)) { ?>
                            <option value="<?= $train['id'] ?>" <?= ($train['id'] == $train_id) ? 'selected' : '' ?>>
                                <?= $train['name'] ?>
                            </option>
                        <?php } ?>
                    </select>

                    <select name="class_type" class="form-control mb-2" required>
                        <option value="">Select Class</option>
                        <option <?= ($class_type == 'General') ? 'selected' : '' ?>>General</option>
                        <option <?= ($class_type == 'Sleeper') ? 'selected' : '' ?>>Sleeper</option>
                        <option <?= ($class_type == 'AC') ? 'selected' : '' ?>>AC</option>
                        <option <?= ($class_type == 'AC Tier 1') ? 'selected' : '' ?>>AC Tier 1</option>
                        <option <?= ($class_type == 'AC Tier 2') ? 'selected' : '' ?>>AC Tier 2</option>
                        <option <?= ($class_type == 'AC Tier 3') ? 'selected' : '' ?>>AC Tier 3</option>
                    </select>

                    <input type="date" name="journey_date" class="form-control mb-3" value="<?= $journey_date ?>" required>

                    <?php if ($selected_fare && $selected_distance): ?>
                        <div class="alert alert-info">
                            <strong>Distance:</strong> <?= $selected_distance ?> km<br>
                            <strong>Fare:</strong> ₹<?= $selected_fare ?>
                        </div>
                    <?php elseif ($train_id && $from_station && $to_station): ?>
                        <div class="alert alert-warning">
                            No route found for this Train & Station combination.
                        </div>
                    <?php endif; ?>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Book Ticket</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Route View -->
        <div class="col-md-6">
            <div class="form-section">
                <h5 class="mb-3">Available Routes (Fare & Distance)</h5>
                <?php while($r = mysqli_fetch_assoc($routes)) { ?>
                    <div class="route-box">
                        <strong><?= $r['from_station'] ?> ➝ <?= $r['to_station'] ?></strong><br>
                        Train: <?= $r['train_name'] ?><br>
                        Distance: <?= $r['distance'] ?> km<br>
                        Fare: ₹<?= $r['fare'] ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
