<?php
include('../connect/db.php');
include('../connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// INSERT train, station, route
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_train'])) {
        $name = mysqli_real_escape_string($db, $_POST['train_name']);
        mysqli_query($db, "INSERT INTO trains (name) VALUES ('$name')");
    }
    if (isset($_POST['add_station'])) {
        $name = mysqli_real_escape_string($db, $_POST['station_name']);
        mysqli_query($db, "INSERT INTO stations (name) VALUES ('$name')");
    }
    if (isset($_POST['add_route'])) {
        $from = (int)$_POST['from_station'];
        $to = (int)$_POST['to_station'];
        $train_id = (int)$_POST['train_id'];
        $distance = (float)$_POST['distance'];
        $rate = (float)$_POST['rate'];
        $fare = $distance * $rate;

        mysqli_query($db, "INSERT INTO routes (from_station_id, to_station_id, distance, fare) VALUES ($from, $to, $distance, $fare)");
        $route_id = mysqli_insert_id($db);
        mysqli_query($db, "INSERT INTO train_routes (train_id, route_id) VALUES ($train_id, $route_id)");
    }
}

$stations = $fun->get_all_stations();
$trains = $fun->get_all_trains();
$routes = $fun->get_all_routes_with_train();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Railway System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">
    <h2 class="mb-4">Admin Panel - Manage Trains, Stations & Routes</h2>
    <div class="row">
        <div class="col-md-4">
            <form method="post" class="card p-3 mb-3">
                <h5>Add Train</h5>
                <input type="text" name="train_name" class="form-control mb-2" placeholder="Train Name" required>
                <button type="submit" name="add_train" class="btn btn-primary">Add Train</button>
            </form>
        </div>

        <div class="col-md-4">
            <form method="post" class="card p-3 mb-3">
                <h5>Add Station</h5>
                <input type="text" name="station_name" class="form-control mb-2" placeholder="Station Name" required>
                <button type="submit" name="add_station" class="btn btn-success">Add Station</button>
            </form>
        </div>

        <div class="col-md-4">
            <form method="post" class="card p-3 mb-3">
                <h5>Add Route</h5>
                <select name="train_id" class="form-select mb-2" required>
                    <option value="">Select Train</option>
                    <?php foreach ($trains as $train): ?>
                        <option value="<?= $train['id'] ?>"><?= htmlspecialchars($train['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="from_station" class="form-select mb-2" required>
                    <option value="">From Station</option>
                    <?php foreach ($stations as $station): ?>
                        <option value="<?= $station['id'] ?>"><?= htmlspecialchars($station['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="to_station" class="form-select mb-2" required>
                    <option value="">To Station</option>
                    <?php foreach ($stations as $station): ?>
                        <option value="<?= $station['id'] ?>"><?= htmlspecialchars($station['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="distance" class="form-control mb-2" placeholder="Distance (KM)" required>
                <input type="number" step="0.01" name="rate" class="form-control mb-2" placeholder="Rate Per KM (₹)" required>
                <button type="submit" name="add_route" class="btn btn-warning">Add Route</button>
            </form>
        </div>
    </div>

    <h4 class="mt-4">All Routes</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-sm table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Train</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Distance (KM)</th>
                    <th>Fare (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routes as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['train_name']) ?></td>
                        <td><?= htmlspecialchars($r['from_station']) ?></td>
                        <td><?= htmlspecialchars($r['to_station']) ?></td>
                        <td><?= $r['distance'] ?></td>
                        <td>&#8377;<?= number_format($r['fare'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
