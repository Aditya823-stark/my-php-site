<?php
include("../connect/db.php");
include("../connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

// Handle delete
if (isset($_GET['delete_id'])) {
    $fun->delete_passenger((int)$_GET['delete_id']);
    header("Location: admin_panel_view.php?deleted=1");
    exit;
}

// Fetch all passengers
$passengers = $fun->get_all_passengers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Passenger Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="text-center mb-4">Passenger Management Panel</h2>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success text-center">Passenger deleted successfully.</div>
    <?php endif; ?>

    <?php if (count($passengers) === 0): ?>
        <div class="alert alert-info text-center">No passenger records found.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover text-center align-middle">
                <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Train</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($passengers as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= $fun->get_station_name($p['from_station_id']) ?></td>
                        <td><?= $fun->get_station_name($p['to_station_id']) ?></td>
                        <td><?= $fun->get_train_name($p['train_id']) ?></td>
                        <td><?= $p['journey_date'] ?></td>
                        <td>
                            <span class="badge bg-<?= strtolower($p['status']) === 'cancelled' ? 'danger' : 'success' ?>">
                                <?= htmlspecialchars($p['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="../view_passenger.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-success" target="_blank">Download</a>
                            <a href="edit_passenger.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="?delete_id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this passenger?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
