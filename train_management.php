<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// INSERT train, station, route
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_train'])) {
        $name = mysqli_real_escape_string($db, $_POST['train_name']);
        mysqli_query($db, "INSERT INTO trains (name) VALUES ('$name')");
        $success_msg = "Train added successfully!";
    }
    if (isset($_POST['add_station'])) {
        $name = mysqli_real_escape_string($db, $_POST['station_name']);
        mysqli_query($db, "INSERT INTO stations (name) VALUES ('$name')");
        $success_msg = "Station added successfully!";
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
        $success_msg = "Route added successfully!";
    }
}

$stations = $fun->get_all_stations();
$trains = $fun->get_all_trains();
$routes = $fun->get_all_routes_with_train();
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Train & Route Management</h3>
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="index.php">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Railway Management</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Train Management</a>
                </li>
            </ul>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Add Train Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-train"></i> Add Train
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="train_name">Train Name</label>
                                <input type="text" name="train_name" id="train_name" class="form-control" placeholder="Enter train name" required>
                            </div>
                            <div class="card-action">
                                <button type="submit" name="add_train" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Add Train
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Station Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-map-marker-alt"></i> Add Station
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="station_name">Station Name</label>
                                <input type="text" name="station_name" id="station_name" class="form-control" placeholder="Enter station name" required>
                            </div>
                            <div class="card-action">
                                <button type="submit" name="add_station" class="btn btn-success">
                                    <i class="fa fa-plus"></i> Add Station
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Route Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-route"></i> Add Route
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="train_id">Select Train</label>
                                <select name="train_id" id="train_id" class="form-select" required>
                                    <option value="">Choose Train</option>
                                    <?php foreach ($trains as $train): ?>
                                        <option value="<?= $train['id'] ?>"><?= htmlspecialchars($train['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="from_station">From Station</label>
                                <select name="from_station" id="from_station" class="form-select" required>
                                    <option value="">Choose From Station</option>
                                    <?php foreach ($stations as $station): ?>
                                        <option value="<?= $station['id'] ?>"><?= htmlspecialchars($station['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="to_station">To Station</label>
                                <select name="to_station" id="to_station" class="form-select" required>
                                    <option value="">Choose To Station</option>
                                    <?php foreach ($stations as $station): ?>
                                        <option value="<?= $station['id'] ?>"><?= htmlspecialchars($station['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="distance">Distance (KM)</label>
                                <input type="number" name="distance" id="distance" class="form-control" placeholder="Distance in KM" required>
                            </div>
                            <div class="form-group">
                                <label for="rate">Rate Per KM (₹)</label>
                                <input type="number" step="0.01" name="rate" id="rate" class="form-control" placeholder="Rate per KM" required>
                            </div>
                            <div class="card-action">
                                <button type="submit" name="add_route" class="btn btn-warning">
                                    <i class="fa fa-plus"></i> Add Route
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Routes Table -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i> All Routes
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($routes) === 0): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No routes found. Add some routes to get started.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="routes-table" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Train</th>
                                            <th>From Station</th>
                                            <th>To Station</th>
                                            <th>Distance (KM)</th>
                                            <th>Fare (₹)</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($routes as $r): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-primary">
                                                        <?= htmlspecialchars($r['train_name']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($r['from_station']) ?></td>
                                                <td><?= htmlspecialchars($r['to_station']) ?></td>
                                                <td><?= $r['distance'] ?> KM</td>
                                                <td>₹<?= number_format($r['fare'], 2) ?></td>
                                                <td>
                                                    <div class="form-button-action">
                                                        <button type="button" class="btn btn-link btn-warning btn-lg" data-bs-toggle="tooltip" title="Edit Route">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-link btn-danger btn-lg" data-bs-toggle="tooltip" title="Delete Route">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trains and Stations Summary -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-train"></i> All Trains (<?= count($trains) ?>)
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($trains) === 0): ?>
                            <p class="text-muted">No trains added yet.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($trains as $train): ?>
                                    <div class="col-md-6 mb-2">
                                        <span class="badge badge-primary badge-lg">
                                            <?= htmlspecialchars($train['name']) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-map-marker-alt"></i> All Stations (<?= count($stations) ?>)
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($stations) === 0): ?>
                            <p class="text-muted">No stations added yet.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($stations as $station): ?>
                                    <div class="col-md-6 mb-2">
                                        <span class="badge badge-success badge-lg">
                                            <?= htmlspecialchars($station['name']) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#routes-table').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
    });
});
</script>
