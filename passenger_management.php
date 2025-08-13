<?php
include("connect/db.php");
include("connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

// Handle delete
if (isset($_GET['delete_id'])) {
    $fun->delete_passenger((int)$_GET['delete_id']);
    header("Location: passenger_management.php?deleted=1");
    exit;
}

// Fetch all passengers
$passengers = $fun->get_all_passengers();
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Passenger Management</h3>
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
                    <a href="#">Passenger Management</a>
                </li>
            </ul>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> Passenger deleted successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">All Passengers</h4>
                            <a href="passenger_booking.php" class="btn btn-primary btn-round ms-auto">
                                <i class="fa fa-plus"></i>
                                Book New Ticket
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($passengers) === 0): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No passenger records found.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="passenger-table" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
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
                                                <td><?= htmlspecialchars($p['email']) ?></td>
                                                <td><?= htmlspecialchars($p['phone']) ?></td>
                                                <td><?= $fun->get_station_name($p['from_station_id']) ?></td>
                                                <td><?= $fun->get_station_name($p['to_station_id']) ?></td>
                                                <td><?= $fun->get_train_name($p['train_id']) ?></td>
                                                <td><?= $p['journey_date'] ?></td>
                                                <td>
                                                    <span class="badge badge-<?= strtolower($p['status']) === 'cancelled' ? 'danger' : 'success' ?>">
                                                        <?= htmlspecialchars($p['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="form-button-action">
                                                        <a href="view_passenger.php?id=<?= $p['id'] ?>" 
                                                           class="btn btn-link btn-primary btn-lg" 
                                                           data-bs-toggle="tooltip" 
                                                           title="View Ticket">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="edit_passenger.php?id=<?= $p['id'] ?>" 
                                                           class="btn btn-link btn-warning btn-lg" 
                                                           data-bs-toggle="tooltip" 
                                                           title="Edit Passenger">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <a href="?delete_id=<?= $p['id'] ?>" 
                                                           class="btn btn-link btn-danger btn-lg" 
                                                           data-bs-toggle="tooltip" 
                                                           title="Delete Passenger"
                                                           onclick="return confirm('Are you sure you want to delete this passenger?');">
                                                            <i class="fa fa-times"></i>
                                                        </a>
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
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#passenger-table').DataTable({
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
