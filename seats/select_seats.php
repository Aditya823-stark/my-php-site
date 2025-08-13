<?php
include("./../connect/db.php");
include("./../connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

// Check required fields
$required = ['name', 'age', 'gender', 'email', 'phone', 'password', 'from_station_id', 'to_station_id', 'train_id', 'class_type', 'journey_date', 'payment_mode'];
foreach ($required as $field) {
    if (!isset($_POST[$field])) die("Missing required field: $field");
}

extract($_POST);

// Get booked seats from database
$booked_seats_query = "SELECT seat_no FROM passengers 
                       WHERE train_id = '$train_id' 
                       AND journey_date = '$journey_date' 
                       AND class_type = '$class_type' 
                       AND status != 'cancelled'";
$booked_result = mysqli_query($db, $booked_seats_query);
$booked_seats = [];
while ($row = mysqli_fetch_assoc($booked_result)) {
    $booked_seats[] = $row['seat_no'];
}

// Handle seat selection and booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selected_seats']) && !empty($_POST['selected_seats'])) {
    $selectedSeats = explode(',', $_POST['selected_seats']);
    $seat_count = count($selectedSeats);
    
    // Check if any selected seats are already booked
    $conflicting_seats = array_intersect($selectedSeats, $booked_seats);
    if (!empty($conflicting_seats)) {
        echo '<script>alert("Some seats are already booked: ' . implode(', ', $conflicting_seats) . '"); window.history.back();</script>';
        exit();
    }
    
    // Store seat information in session for later use
    session_start();
    $_SESSION['selected_seats'] = $selectedSeats;
    $_SESSION['seat_count'] = $seat_count;
    $_SESSION['booking_data'] = $_POST;
    
    // Check if multiple seats are selected - redirect to passenger details form
    if ($seat_count > 1) {
        // Redirect to passenger details form for multiple passengers
        echo '<form id="redirectForm" method="POST" action="passenger_details.php">';
        foreach ($_POST as $key => $value) {
            echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
        }
        echo '<input type="hidden" name="selected_seats" value="' . htmlspecialchars($_POST['selected_seats']) . '">';
        echo '<input type="hidden" name="seat_count" value="' . $seat_count . '">';
        echo '</form>';
        echo '<script>document.getElementById("redirectForm").submit();</script>';
    } else {
        // Single seat - proceed with original flow
        // Redirect based on payment mode
        if ($payment_mode === 'Online') {
            // Redirect to payment QR page
            echo '<form id="redirectForm" method="POST" action="payment_qr.php">';
            foreach ($_POST as $key => $value) {
                if ($key !== 'selected_seats') {
                    echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                }
            }
            echo '<input type="hidden" name="selected_seats" value="' . htmlspecialchars($_POST['selected_seats']) . '">';
            echo '<input type="hidden" name="seat_count" value="' . $seat_count . '">';
            echo '</form>';
            echo '<script>document.getElementById("redirectForm").submit();</script>';
        } else {
            // Redirect to add_passenger.php for offline payment
            echo '<form id="redirectForm" method="POST" action="add_passenger.php">';
            foreach ($_POST as $key => $value) {
                if ($key !== 'selected_seats') {
                    echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                }
            }
            echo '<input type="hidden" name="selected_seats" value="' . htmlspecialchars($_POST['selected_seats']) . '">';
            echo '<input type="hidden" name="seat_count" value="' . $seat_count . '">';
            echo '</form>';
            echo '<script>document.getElementById("redirectForm").submit();</script>';
        }
    }
    exit();
}

// Get route information for fare calculation
$route = $fun->get_route($from_station_id, $to_station_id, $train_id);
$fare_per_seat = $route['fare'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AC 2 Tier Coach Seat Selection</title>
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
            max-width: 1200px;
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="1" fill="white" opacity="0.1"/><circle cx="10" cy="90" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }
        
        .coach-container {
            padding: 30px;
            background: #f8f9fa;
        }
        
        .coach {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .coach::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .seat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            align-items: center;
            padding: 5px 0;
        }
        
        .berth {
            width: 55px;
            height: 55px;
            line-height: 55px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            border-radius: 12px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            user-select: none;
        }
        
        .berth:hover:not(.booked) {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .berth.selected {
            border: 3px solid #28a745;
            background: #28a745 !important;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .berth.selected::after {
            content: '✓';
            position: absolute;
            top: -8px;
            right: -8px;
            background: #fff;
            color: #28a745;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 12px;
            line-height: 20px;
            font-weight: bold;
        }
        
        .lower { 
            background: linear-gradient(135deg, #ffeaa7, #fdcb6e);
            color: #2d3436;
        }
        .upper { 
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
        }
        .side-lower { 
            background: linear-gradient(135deg, #55efc4, #00b894);
            color: #2d3436;
        }
        .side-upper { 
            background: linear-gradient(135deg, #fd79a8, #e84393);
            color: white;
        }
        
        .booked { 
            background: linear-gradient(135deg, #ff7675, #d63031) !important;
            color: white !important;
            cursor: not-allowed !important;
            opacity: 0.7;
        }
        
        .booked::before {
            content: '✗';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 18px;
            font-weight: bold;
        }
        
        .toilet {
            background: linear-gradient(135deg, #636e72, #2d3436);
            color: white;
            text-align: center;
            font-size: 11px;
            padding: 8px 4px;
            border-radius: 8px;
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin: 25px 0;
            padding: 20px;
            background: rgba(255,255,255,0.8);
            border-radius: 12px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        .seat-counter {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            font-weight: 600;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .seat-counter:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        
        .booking-info {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #2d3436;
        }
        
        .proceed-btn {
            background: linear-gradient(135deg, #00b894, #55efc4);
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 184, 148, 0.3);
        }
        
        .proceed-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 184, 148, 0.4);
            background: linear-gradient(135deg, #55efc4, #00b894);
        }
        
        .proceed-btn:disabled {
            background: #ddd;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header-section {
                padding: 20px;
            }
            
            .coach-container {
                padding: 15px;
            }
            
            .coach {
                padding: 15px;
            }
            
            .berth {
                width: 45px;
                height: 45px;
                line-height: 45px;
                font-size: 12px;
            }
            
            .toilet {
                width: 45px;
                height: 45px;
                font-size: 10px;
            }
            
            .legend {
                gap: 10px;
            }
            
            .seat-counter {
                bottom: 10px;
                right: 10px;
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .seat-row {
                margin-bottom: 8px;
            }
            
            .berth {
                width: 40px;
                height: 40px;
                line-height: 40px;
                font-size: 11px;
            }
            
            .toilet {
                width: 40px;
                height: 40px;
                font-size: 9px;
            }
        }
        
        /* Animation for seat selection */
        @keyframes seatSelect {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1.1); }
        }
        
        .berth.selecting {
            animation: seatSelect 0.3s ease;
        }
        
        /* Time slot styles */
        .time-slot-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom: 10px;
        }
        
        .time-slot-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }
        
        .time-slot-card input[type="radio"]:checked + label {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 8px;
        }
        
        .time-slot-card input[type="radio"]:checked + label .text-muted {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        
        .time-slot-card input[type="radio"] {
            display: none;
        }
        
        .time-slot-card label {
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header-section">
            <h2><i class="fas fa-train"></i> AC 2 Tier Coach - Seat Selection</h2>
            <p class="mb-0">Choose your preferred seats for a comfortable journey</p>
        </div>
        
        <div class="coach-container">
            <!-- Booking Information -->
            <div class="booking-info">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Train</div>
                        <div class="info-value"><?= htmlspecialchars($fun->get_train_name($train_id)) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">From - To</div>
                        <div class="info-value"><?= htmlspecialchars($fun->get_station_name($from_station_id)) ?> → <?= htmlspecialchars($fun->get_station_name($to_station_id)) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Journey Date</div>
                        <div class="info-value"><?= date('d M Y', strtotime($journey_date)) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Class</div>
                        <div class="info-value"><?= htmlspecialchars($class_type) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Fare per Seat</div>
                        <div class="info-value">₹<?= number_format($fare_per_seat, 2) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Mode</div>
                        <div class="info-value"><?= htmlspecialchars($payment_mode) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Seats to Select</div>
                        <div class="info-value"><?= $_POST['requested_seats'] ?? 1 ?> Seat<?= ($_POST['requested_seats'] ?? 1) > 1 ? 's' : '' ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Time Slot Selection -->
            <div class="booking-info">
                <h6 class="text-primary mb-3"><i class="fas fa-clock"></i> Select Departure Time</h6>
                <div class="row g-2">
                    <div class="col-md-3 col-6">
                        <div class="form-check time-slot-card">
                            <input class="form-check-input" type="radio" name="departure_time" id="time1" value="06:00" checked>
                            <label class="form-check-label w-100 text-center p-2" for="time1">
                                <i class="fas fa-sun text-warning"></i><br>
                                <strong>06:00 AM</strong><br>
                                <small class="text-muted">Early Morning</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="form-check time-slot-card">
                            <input class="form-check-input" type="radio" name="departure_time" id="time2" value="08:00">
                            <label class="form-check-label w-100 text-center p-2" for="time2">
                                <i class="fas fa-sun text-orange"></i><br>
                                <strong>08:00 AM</strong><br>
                                <small class="text-muted">Morning</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="form-check time-slot-card">
                            <input class="form-check-input" type="radio" name="departure_time" id="time3" value="12:00">
                            <label class="form-check-label w-100 text-center p-2" for="time3">
                                <i class="fas fa-sun text-warning"></i><br>
                                <strong>12:00 PM</strong><br>
                                <small class="text-muted">Afternoon</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="form-check time-slot-card">
                            <input class="form-check-input" type="radio" name="departure_time" id="time4" value="16:00">
                            <label class="form-check-label w-100 text-center p-2" for="time4">
                                <i class="fas fa-sun text-info"></i><br>
                                <strong>04:00 PM</strong><br>
                                <small class="text-muted">Evening</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="form-check time-slot-card">
                            <input class="form-check-input" type="radio" name="departure_time" id="time5" value="18:00">
                            <label class="form-check-label w-100 text-center p-2" for="time5">
                                <i class="fas fa-moon text-primary"></i><br>
                                <strong>06:00 PM</strong><br>
                                <small class="text-muted">Late Evening</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="form-check time-slot-card">
                            <input class="form-check-input" type="radio" name="departure_time" id="time6" value="20:00">
                            <label class="form-check-label w-100 text-center p-2" for="time6">
                                <i class="fas fa-moon text-dark"></i><br>
                                <strong>08:00 PM</strong><br>
                                <small class="text-muted">Night</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="form-check time-slot-card">
                            <input class="form-check-input" type="radio" name="departure_time" id="time7" value="22:00">
                            <label class="form-check-label w-100 text-center p-2" for="time7">
                                <i class="fas fa-moon text-secondary"></i><br>
                                <strong>10:00 PM</strong><br>
                                <small class="text-muted">Late Night</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="form-check time-slot-card">
                            <input class="form-check-input" type="radio" name="departure_time" id="time8" value="00:00">
                            <label class="form-check-label w-100 text-center p-2" for="time8">
                                <i class="fas fa-moon text-dark"></i><br>
                                <strong>12:00 AM</strong><br>
                                <small class="text-muted">Midnight</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Seat Selection Instructions -->
            <div class="alert alert-info mb-4">
                <h6><i class="fas fa-info-circle"></i> Seat Selection Instructions</h6>
                <ul class="mb-0">
                    <li>You need to select exactly <strong><?= $_POST['requested_seats'] ?? 1 ?></strong> seat<?= ($_POST['requested_seats'] ?? 1) > 1 ? 's' : '' ?> as requested in your booking form</li>
                    <li>Red seats with ✗ are already booked and cannot be selected</li>
                    <li>Click on available seats to select/deselect them</li>
                    <li>Selected seats will show a green checkmark ✓</li>
                </ul>
            </div>
            
            <!-- Legend -->
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color lower"></div>
                    <span>Lower Berth</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color upper"></div>
                    <span>Upper Berth</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color side-lower"></div>
                    <span>Side Lower</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color side-upper"></div>
                    <span>Side Upper</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color booked"></div>
                    <span>Booked</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #28a745;"></div>
                    <span>Selected</span>
                </div>
            </div>
            
            <form method="POST" id="seatForm">
                <!-- Pass all the required data -->
                <?php foreach ($_POST as $key => $value): ?>
                    <?php if ($key !== 'selected_seats'): ?>
                        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <div class="coach">
                    <!-- Toilets at top -->
                    <div class="seat-row justify-content-between mb-4">
                        <div class="toilet"><i class="fas fa-restroom"></i><br>TOILET</div>
                        <div class="toilet"><i class="fas fa-restroom"></i><br>TOILET</div>
                    </div>

                    <?php
                    // AC 2 Tier layout (46 seats total)
                    $layout = [
                        ['1' => 'lower', '2' => 'upper'],
                        ['3' => 'lower', '4' => 'upper', '5' => 'side-upper'],
                        ['6' => 'lower', '7' => 'upper', '8' => 'side-lower'],
                        ['9' => 'lower', '10' => 'upper', '11' => 'side-upper'],
                        ['12' => 'lower', '13' => 'upper', '14' => 'side-lower'],
                        ['15' => 'lower', '16' => 'upper', '17' => 'side-upper'],
                        ['18' => 'lower', '19' => 'upper', '20' => 'side-lower'],
                        ['21' => 'lower', '22' => 'upper', '23' => 'side-upper'],
                        ['24' => 'lower', '25' => 'upper', '26' => 'side-lower'],
                        ['27' => 'lower', '28' => 'upper', '29' => 'side-upper'],
                        ['30' => 'lower', '31' => 'upper', '32' => 'side-lower'],
                        ['33' => 'lower', '34' => 'upper', '35' => 'side-upper'],
                        ['36' => 'lower', '37' => 'upper', '38' => 'side-lower'],
                        ['39' => 'lower', '40' => 'upper', '41' => 'side-upper'],
                        ['42' => 'lower', '43' => 'upper', '44' => 'side-lower'],
                        ['45' => 'side-upper', '46' => 'side-lower'],
                    ];

                    foreach ($layout as $row) {
                        echo '<div class="seat-row">';
                        foreach ($row as $num => $type) {
                            $classes = "$type berth";
                            if (in_array($num, $booked_seats)) {
                                $classes .= " booked";
                            }
                            echo "<div class='$classes' data-seat='$num' onclick='toggleSeat(this)'>$num</div>";
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
                
                <input type="hidden" name="selected_seats" id="selectedSeats">
                <input type="hidden" name="departure_time" id="selectedTime" value="06:00">
                
                <div class="text-center mt-4">
                    <button class="btn proceed-btn" type="submit" id="proceedBtn" disabled>
                        <i class="fas fa-arrow-right"></i> Proceed to <?= $payment_mode === 'Online' ? 'Payment' : 'Booking' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Seat Counter -->
    <div class="seat-counter" id="seatCounter">
        <i class="fas fa-chair"></i> <span id="seatCount">0</span> Seats Selected
        <div style="font-size: 12px; margin-top: 5px;">
            Total: ₹<span id="totalFare">0</span>
        </div>
    </div>

    <script>
        let selectedSeats = [];
        const farePerSeat = <?= $fare_per_seat ?>;
        const maxSeatsAllowed = <?= $_POST['requested_seats'] ?? 1 ?>; // Get requested seats from form
        
        function toggleSeat(seatElement) {
            if (seatElement.classList.contains('booked')) {
                return;
            }
            
            const seatNumber = seatElement.dataset.seat;
            
            // Add selecting animation
            seatElement.classList.add('selecting');
            setTimeout(() => seatElement.classList.remove('selecting'), 300);
            
            if (seatElement.classList.contains('selected')) {
                // Deselect seat
                seatElement.classList.remove('selected');
                selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
            } else {
                // Check if maximum seats limit reached
                if (selectedSeats.length >= maxSeatsAllowed) {
                    alert(`You can only select ${maxSeatsAllowed} seat${maxSeatsAllowed > 1 ? 's' : ''} as requested in the booking form.`);
                    return;
                }
                // Select seat
                seatElement.classList.add('selected');
                selectedSeats.push(seatNumber);
            }
            
            updateSeatCounter();
            updateHiddenInput();
            updateProceedButton();
        }
        
        function updateSeatCounter() {
            const seatCount = selectedSeats.length;
            const totalFare = seatCount * farePerSeat;
            
            document.getElementById('seatCount').textContent = seatCount;
            document.getElementById('totalFare').textContent = totalFare.toFixed(2);
            
            // Update counter visibility
            const counter = document.getElementById('seatCounter');
            if (seatCount > 0) {
                counter.style.display = 'block';
            } else {
                counter.style.display = 'none';
            }
        }
        
        function updateHiddenInput() {
            document.getElementById('selectedSeats').value = selectedSeats.join(',');
        }
        
        function updateProceedButton() {
            const proceedBtn = document.getElementById('proceedBtn');
            if (selectedSeats.length > 0) {
                proceedBtn.disabled = false;
                proceedBtn.innerHTML = `<i class="fas fa-arrow-right"></i> Proceed with ${selectedSeats.length} Seat${selectedSeats.length > 1 ? 's' : ''} (₹${(selectedSeats.length * farePerSeat).toFixed(2)})`;
            } else {
                proceedBtn.disabled = true;
                proceedBtn.innerHTML = '<i class="fas fa-arrow-right"></i> Select Seats to Continue';
            }
        }
        
        // Initialize
        updateSeatCounter();
        
        // Time slot selection handling
        document.querySelectorAll('input[name="departure_time"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('selectedTime').value = this.value;
                updateProceedButton();
            });
        });
        
        // Form submission with confirmation
        document.getElementById('seatForm').addEventListener('submit', function(e) {
            if (selectedSeats.length === 0) {
                e.preventDefault();
                alert('Please select at least one seat.');
                return;
            }
            
            const selectedTime = document.querySelector('input[name="departure_time"]:checked').value;
            const timeFormatted = new Date('2000-01-01 ' + selectedTime).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            const confirmation = confirm(`Confirm booking ${selectedSeats.length} seat(s): ${selectedSeats.join(', ')} for ₹${(selectedSeats.length * farePerSeat).toFixed(2)}\nDeparture Time: ${timeFormatted}?`);
            if (!confirmation) {
                e.preventDefault();
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Clear all selections
                selectedSeats = [];
                document.querySelectorAll('.berth.selected').forEach(seat => {
                    seat.classList.remove('selected');
                });
                updateSeatCounter();
                updateHiddenInput();
                updateProceedButton();
            }
        });
    </script>
</body>
</html>
