<?php
include("./../connect/db.php");
include("./../connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

// Get data from previous form
$selected_seats = $_POST['selected_seats'] ?? '';
$seat_count = $_POST['seat_count'] ?? 1;
$seats_array = !empty($selected_seats) ? explode(',', $selected_seats) : [];

// Sort seats in ascending order
if (!empty($seats_array)) {
    sort($seats_array, SORT_NUMERIC);
}

// Get other form data
$train_id = $_POST['train_id'] ?? '';
$from_station_id = $_POST['from_station_id'] ?? '';
$to_station_id = $_POST['to_station_id'] ?? '';
$class_type = $_POST['class_type'] ?? '';
$journey_date = $_POST['journey_date'] ?? '';
$payment_mode = $_POST['payment_mode'] ?? '';
$primary_email = $_POST['email'] ?? '';
$primary_phone = $_POST['phone'] ?? '';
$primary_password = $_POST['password'] ?? '';

// Get route information
$route = $fun->get_route($from_station_id, $to_station_id, $train_id);
$fare_per_seat = $route['fare'] ?? 0;
$distance = $route['distance'] ?? 0;
$total_fare = $fare_per_seat * count($seats_array);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_passengers'])) {
    // Debug: Log form submission
    error_log("Form submitted with " . count($seats_array) . " seats: " . implode(',', $seats_array));
    
    // Process all passenger data
    $passengers_data = [];
    
    for ($i = 0; $i < count($seats_array); $i++) {
        $name = $_POST["passenger_name_$i"] ?? '';
        $age = $_POST["passenger_age_$i"] ?? '';
        $gender = $_POST["passenger_gender_$i"] ?? '';
        
        $passenger_data = [
            'name' => $name,
            'age' => $age,
            'gender' => $gender,
            'seat_no' => $seats_array[$i],
            'email' => $primary_email,
            'phone' => $primary_phone,
            'password' => $primary_password,
            'train_id' => $train_id,
            'from_station_id' => $from_station_id,
            'to_station_id' => $to_station_id,
            'class_type' => $class_type,
            'journey_date' => $journey_date,
            'payment_mode' => $payment_mode,
            'fare' => $fare_per_seat,
            'distance' => $distance
        ];
        $passengers_data[] = $passenger_data;
        
        // Debug: Log each passenger with form field names
        error_log("Processing passenger $i: Name='$name' (from field passenger_name_$i), Age='$age', Gender='$gender', Seat={$seats_array[$i]}");
    }
    
    // Debug: Log total passengers processed
    error_log("Total passengers processed: " . count($passengers_data));
    
    // Store in session and redirect
    session_start();
    $_SESSION['passengers_data'] = $passengers_data;
    $_SESSION['total_fare'] = $total_fare;
    
    // Debug: Log session data
    error_log("Session data stored - Passengers: " . count($_SESSION['passengers_data']) . ", Total fare: " . $total_fare);
    
    // Redirect based on payment mode
    if ($payment_mode === 'Online') {
        // Prepare data for payment QR
        echo '<form id="redirectForm" method="POST" action="payment_qr.php">';
        echo '<input type="hidden" name="name" value="' . htmlspecialchars($passengers_data[0]['name']) . '">';
        echo '<input type="hidden" name="fare" value="' . $total_fare . '">';
        echo '<input type="hidden" name="train_id" value="' . $train_id . '">';
        echo '<input type="hidden" name="from_station_id" value="' . $from_station_id . '">';
        echo '<input type="hidden" name="to_station_id" value="' . $to_station_id . '">';
        echo '<input type="hidden" name="selected_seats" value="' . htmlspecialchars($selected_seats) . '">';
        echo '<input type="hidden" name="seat_count" value="' . count($seats_array) . '">';
        echo '<input type="hidden" name="payment_mode" value="Online">';
        echo '</form>';
        echo '<script>document.getElementById("redirectForm").submit();</script>';
    } else {
        // Redirect to add_passenger.php for offline payment
        echo '<form id="redirectForm" method="POST" action="add_multiple_passengers.php">';
        echo '<input type="hidden" name="passengers_data" value="' . htmlspecialchars(json_encode($passengers_data)) . '">';
        echo '<input type="hidden" name="total_fare" value="' . $total_fare . '">';
        echo '</form>';
        echo '<script>document.getElementById("redirectForm").submit();</script>';
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 1000px;
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .passenger-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
        }
        
        .seat-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .booking-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header-section">
            <h2><i class="fas fa-users"></i> Passenger Details</h2>
            <p class="mb-0">Please provide details for all passengers</p>
        </div>
        
        <div class="container-fluid p-4">
            <!-- Booking Summary -->
            <div class="booking-summary">
                <h5><i class="fas fa-info-circle"></i> Booking Summary</h5>
                <div class="row">
                    <div class="col-md-3">
                        <strong>Train:</strong> <?= htmlspecialchars($fun->get_train_name($train_id)) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Route:</strong> <?= htmlspecialchars($fun->get_station_name($from_station_id)) ?> → <?= htmlspecialchars($fun->get_station_name($to_station_id)) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Date:</strong> <?= date('d M Y', strtotime($journey_date)) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Class:</strong> <?= htmlspecialchars($class_type) ?>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-3">
                        <strong>Selected Seats:</strong> <?= implode(', ', $seats_array) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Total Seats:</strong> <?= count($seats_array) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Fare per Seat:</strong> ₹<?= number_format($fare_per_seat, 2) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Total Amount:</strong> <span class="text-success fw-bold">₹<?= number_format($total_fare, 2) ?></span>
                    </div>
                </div>
            </div>
            
            <form method="POST" id="passengerForm">
                <!-- Hidden fields to preserve data -->
                <input type="hidden" name="selected_seats" value="<?= htmlspecialchars($selected_seats) ?>">
                <input type="hidden" name="seat_count" value="<?= count($seats_array) ?>">
                <input type="hidden" name="train_id" value="<?= $train_id ?>">
                <input type="hidden" name="from_station_id" value="<?= $from_station_id ?>">
                <input type="hidden" name="to_station_id" value="<?= $to_station_id ?>">
                <input type="hidden" name="class_type" value="<?= htmlspecialchars($class_type) ?>">
                <input type="hidden" name="journey_date" value="<?= $journey_date ?>">
                <input type="hidden" name="payment_mode" value="<?= htmlspecialchars($payment_mode) ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($primary_email) ?>">
                <input type="hidden" name="phone" value="<?= htmlspecialchars($primary_phone) ?>">
                <input type="hidden" name="password" value="<?= htmlspecialchars($primary_password) ?>">
                
                <!-- Passenger Details -->
                <?php foreach ($seats_array as $index => $seat_no): ?>
                    <div class="passenger-card">
                        <div class="seat-badge">
                            <i class="fas fa-chair"></i> Seat <?= $seat_no ?>
                        </div>
                        
                        <h6>Passenger <?= $index + 1 ?> Details</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="passenger_name_<?= $index ?>" class="form-control" 
                                       placeholder="Enter full name for seat <?= $seat_no ?>" 
                                       id="passenger_name_<?= $index ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Age *</label>
                                <input type="number" name="passenger_age_<?= $index ?>" class="form-control" 
                                       placeholder="Age" min="1" max="120" 
                                       id="passenger_age_<?= $index ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gender *</label>
                                <select name="passenger_gender_<?= $index ?>" class="form-control" 
                                        id="passenger_gender_<?= $index ?>" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Debug: Show field names -->
                        <small class="text-muted">Field names: passenger_name_<?= $index ?>, passenger_age_<?= $index ?>, passenger_gender_<?= $index ?></small>
                    </div>
                <?php endforeach; ?>
                
                <!-- Contact Information Note -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> All passengers will use the same contact information (<?= htmlspecialchars($primary_email) ?>, <?= htmlspecialchars($primary_phone) ?>) provided in the booking form.
                </div>
                
                <!-- Debug Information -->
                <div class="alert alert-warning">
                    <i class="fas fa-bug"></i>
                    <strong>Debug Info:</strong> Processing <?= count($seats_array) ?> seats: <?= implode(', ', $seats_array) ?>
                    <br><strong>Payment Mode:</strong> <?= htmlspecialchars($payment_mode) ?>
                    <br><strong>Total Fare:</strong> ₹<?= number_format($total_fare, 2) ?>
                    <br><strong>Form Fields Expected:</strong> 
                    <?php for($i = 0; $i < count($seats_array); $i++): ?>
                        passenger_name_<?= $i ?>, passenger_age_<?= $i ?>, passenger_gender_<?= $i ?><?= $i < count($seats_array)-1 ? ', ' : '' ?>
                    <?php endfor; ?>
                </div>
                
                <!-- Submit Button -->
                <div class="text-center">
                    <button type="submit" name="submit_passengers" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="fas fa-arrow-right"></i> 
                        Proceed to <?= $payment_mode === 'Online' ? 'Payment' : 'Booking Confirmation' ?>
                    </button>
                    <br><br>
                    <button type="button" class="btn btn-secondary" onclick="debugForm()">
                        <i class="fas fa-bug"></i> Debug Form Data
                    </button>
                </div>
                
                <script>
                function debugForm() {
                    let formData = new FormData(document.getElementById('passengerForm'));
                    let debug = 'Form Data:\n';
                    for (let [key, value] of formData.entries()) {
                        debug += key + ': ' + value + '\n';
                    }
                    alert(debug);
                }
                </script>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-fill first passenger with primary booking details if available
        document.addEventListener('DOMContentLoaded', function() {
            // You can add auto-fill logic here if needed
            
            // Form validation and debugging
            document.getElementById('passengerForm').addEventListener('submit', function(e) {
                const requiredFields = this.querySelectorAll('input[required], select[required]');
                let isValid = true;
                let formData = new FormData(this);
                
                // Debug: Log all form data
                console.log('Form submission data:');
                for (let [key, value] of formData.entries()) {
                    console.log(key + ': ' + value);
                }
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                        console.log('Empty field:', field.name);
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields for all passengers.');
                    return false;
                }
                
                // Show confirmation with passenger names
                let passengerNames = [];
                for (let i = 0; i < <?= count($seats_array) ?>; i++) {
                    let name = document.getElementById('passenger_name_' + i).value;
                    if (name) passengerNames.push(name);
                }
                
                if (!confirm('Confirm booking for: ' + passengerNames.join(', ') + '?')) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>