<?php
include("../connect/db.php");
include("../connect/fun.php");

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

// Fetch routes based on search parameters
$route_sql = "SELECT r.id, s1.name AS from_station, s2.name AS to_station, r.distance, r.fare, t.name AS train_name
              FROM routes r
              JOIN stations s1 ON r.from_station_id = s1.id
              JOIN stations s2 ON r.to_station_id = s2.id
              JOIN train_routes tr ON tr.route_id = r.id
              JOIN trains t ON tr.train_id = t.id";

if ($from_station && $to_station) {
    $route_sql .= " WHERE r.from_station_id = $from_station AND r.to_station_id = $to_station";
}

$routes = mysqli_query($db, $route_sql);

// Get fare and distance for selected route
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
        .payment-options label {
            margin-right: 20px;
        }
        
        /* Modal Styles */
        .vehicle-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .vehicle-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .vehicle-card.selected {
            border-color: #0d6efd;
            background-color: #e3f2fd;
        }
        
        .vehicle-card.selected .card-body {
            background-color: transparent;
        }
        
        #seatCountDisplay {
            min-width: 60px;
            text-align: center;
        }
        
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .modal-header {
            border-bottom: none;
        }
        
        .modal-footer {
            border-top: none;
            padding-top: 0;
        }
        
        /* Form validation styles */
        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        
        .is-invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
    </style>
</head>
<body class="bg-light">

<!-- Seat Selection Modal -->
<div class="modal fade" id="seatSelectionModal" tabindex="-1" aria-labelledby="seatSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="seatSelectionModalLabel">
                    <i class="fas fa-chair"></i> Select Number of Seats
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <h6 class="text-muted">How many seats would you like to book?</h6>
                    <small class="text-info">Maximum 10 seats per booking</small>
                </div>
                
                <!-- Vehicle Type Selection -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Select Vehicle Type:</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="card vehicle-card" data-max-seats="2" onclick="selectVehicleType(this, 'Bike', 2)">
                                <div class="card-body text-center">
                                    <i class="fas fa-motorcycle fa-2x text-primary mb-2"></i>
                                    <h6>Bike</h6>
                                    <small class="text-muted">Max 2 seats</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card vehicle-card" data-max-seats="5" onclick="selectVehicleType(this, 'Car', 5)">
                                <div class="card-body text-center">
                                    <i class="fas fa-car fa-2x text-success mb-2"></i>
                                    <h6>Car</h6>
                                    <small class="text-muted">Max 5 seats</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card vehicle-card" data-max-seats="8" onclick="selectVehicleType(this, 'Van', 8)">
                                <div class="card-body text-center">
                                    <i class="fas fa-shuttle-van fa-2x text-warning mb-2"></i>
                                    <h6>Van</h6>
                                    <small class="text-muted">Max 8 seats</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card vehicle-card" data-max-seats="10" onclick="selectVehicleType(this, 'Bus', 10)">
                                <div class="card-body text-center">
                                    <i class="fas fa-bus fa-2x text-info mb-2"></i>
                                    <h6>Bus</h6>
                                    <small class="text-muted">Max 10 seats</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seat Count Selection -->
                <div class="mb-4" id="seatCountSection" style="display: none;">
                    <label class="form-label fw-bold">Number of Seats:</label>
                    <div class="d-flex align-items-center justify-content-center">
                        <button type="button" class="btn btn-outline-secondary" onclick="changeSeatCount(-1)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="mx-4 fs-2 fw-bold text-primary" id="seatCountDisplay">1</span>
                        <button type="button" class="btn btn-outline-secondary" onclick="changeSeatCount(1)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted">Selected: <span id="selectedVehicleType">-</span> (Max: <span id="maxSeatsDisplay">-</span> seats)</small>
                    </div>
                </div>

                <!-- Fare Preview -->
                <div class="alert alert-info" id="farePreview" style="display: none;">
                    <div class="d-flex justify-content-between">
                        <span>Fare per seat:</span>
                        <span>₹<span id="farePerSeat">0</span></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total seats:</span>
                        <span id="totalSeatsPreview">0</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total Amount:</span>
                        <span>₹<span id="totalFarePreview">0</span></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="proceedToSeats" onclick="proceedWithSeats()" disabled>
                    <i class="fas fa-arrow-right"></i> Proceed to Seat Selection
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <h2 class="mb-4 text-center">Railway Ticket Booking</h2>

    <div class="row">
        <!-- Booking Form -->
        <div class="col-md-6">
            <div class="form-section">
                <form method="POST" action="select_seats.php" id="bookingForm">

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
                    <input type="password" name="password" class="form-control mb-2" placeholder="Password" value="<?= htmlspecialchars($password) ?>" required>

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

                    <!-- Payment Mode: Required -->
                    <h5 class="mt-3 mb-3">Payment Mode</h5>
                    <div class="payment-options mb-3">
                        <label>
                            <input type="radio" name="payment_mode" value="Online" required> Online
                        </label>
                        <label>
                            <input type="radio" name="payment_mode" value="Offline" required> Offline
                        </label>
                    </div>

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
                        <button type="button" class="btn btn-primary" onclick="validateAndShowModal()">Book Ticket</button>
                    </div>
                    
                    <!-- Hidden fields for seat selection -->
                    <input type="hidden" name="requested_seats" id="requestedSeats" value="1">
                    <input type="hidden" name="vehicle_type" id="vehicleType" value="">
                    <input type="hidden" name="password" value="<?= htmlspecialchars($password) ?>">
                </form>
            </div>
        </div>

        <!-- Route View with Search Option -->
        <div class="col-md-6">
            <div class="form-section">
                <h5 class="mb-3">Available Routes (Fare & Distance)</h5>
                <form method="POST" action="form.php" class="mb-3">
                    <select name="from_station_id" class="form-control mb-2" onchange="this.form.submit();" required>
                        <option value="">From Station</option>
                        <?php mysqli_data_seek($stations, 0); while($row = mysqli_fetch_assoc($stations)) { ?>
                            <option value="<?= $row['id'] ?>" <?= ($row['id'] == $from_station) ? 'selected' : '' ?>>
                                <?= $row['name'] ?>
                            </option>
                        <?php } ?>
                    </select>

                    <select name="to_station_id" class="form-control mb-2" onchange="this.form.submit();" required>
                        <option value="">To Station</option>
                        <?php mysqli_data_seek($stations, 0); while($row = mysqli_fetch_assoc($stations)) { ?>
                            <option value="<?= $row['id'] ?>" <?= ($row['id'] == $to_station) ? 'selected' : '' ?>>
                                <?= $row['name'] ?>
                            </option>
                        <?php } ?>
                    </select>
                </form>

                <?php if (mysqli_num_rows($routes)): ?>
                    <?php while($route = mysqli_fetch_assoc($routes)): ?>
                        <div class="route-box">
                            <strong>Train:</strong> <?= $route['train_name'] ?><br>
                            <strong>From:</strong> <?= $route['from_station'] ?> | <strong>To:</strong> <?= $route['to_station'] ?><br>
                            <strong>Fare:</strong> ₹<?= $route['fare'] ?> | <strong>Distance:</strong> <?= $route['distance'] ?> km
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-danger">No routes available for this combination.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

<script>
let currentSeatCount = 1;
let maxSeats = 10;
let selectedVehicle = '';
let farePerSeat = <?= $selected_fare ?: 0 ?>;

// Modal functionality
const seatModal = new bootstrap.Modal(document.getElementById('seatSelectionModal'));

function validateAndShowModal() {
    // Validate form first
    const form = document.getElementById('bookingForm');
    
    // Check all required fields manually to avoid browser validation clearing password
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    let firstInvalidField = null;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
            if (!firstInvalidField) {
                firstInvalidField = field;
            }
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        if (firstInvalidField) {
            firstInvalidField.focus();
            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        alert('Please fill in all required fields.');
        return;
    }
    
    // Update fare if available
    const fareElement = document.querySelector('input[name="fare"]');
    if (fareElement && fareElement.value) {
        farePerSeat = parseFloat(fareElement.value);
        document.getElementById('farePerSeat').textContent = farePerSeat.toFixed(2);
    }
    
    // Show modal
    seatModal.show();
}

function selectVehicleType(element, vehicleType, maxSeatsAllowed) {
    // Remove previous selection
    document.querySelectorAll('.vehicle-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selection to clicked card
    element.classList.add('selected');
    
    // Update variables
    selectedVehicle = vehicleType;
    maxSeats = maxSeatsAllowed;
    currentSeatCount = 1;
    
    // Update display
    document.getElementById('selectedVehicleType').textContent = vehicleType;
    document.getElementById('maxSeatsDisplay').textContent = maxSeatsAllowed;
    document.getElementById('seatCountDisplay').textContent = currentSeatCount;
    
    // Show seat count section
    document.getElementById('seatCountSection').style.display = 'block';
    
    // Update fare preview
    updateFarePreview();
    
    // Enable proceed button
    document.getElementById('proceedToSeats').disabled = false;
}

function changeSeatCount(change) {
    const newCount = currentSeatCount + change;
    
    if (newCount >= 1 && newCount <= maxSeats) {
        currentSeatCount = newCount;
        document.getElementById('seatCountDisplay').textContent = currentSeatCount;
        updateFarePreview();
    }
}

function updateFarePreview() {
    if (farePerSeat > 0 && currentSeatCount > 0) {
        const totalFare = farePerSeat * currentSeatCount;
        
        document.getElementById('farePerSeat').textContent = farePerSeat.toFixed(2);
        document.getElementById('totalSeatsPreview').textContent = currentSeatCount;
        document.getElementById('totalFarePreview').textContent = totalFare.toFixed(2);
        
        document.getElementById('farePreview').style.display = 'block';
    } else {
        document.getElementById('farePreview').style.display = 'none';
    }
}

function proceedWithSeats() {
    // Update hidden fields
    document.getElementById('requestedSeats').value = currentSeatCount;
    document.getElementById('vehicleType').value = selectedVehicle;
    
    // Hide modal
    seatModal.hide();
    
    // Submit form
    document.getElementById('bookingForm').submit();
}

// Initialize fare preview if fare is already available
if (farePerSeat > 0) {
    document.getElementById('farePerSeat').textContent = farePerSeat.toFixed(2);
}
</script>

</body>
</html>
