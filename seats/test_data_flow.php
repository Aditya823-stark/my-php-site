<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🧪 Test Data Flow - Seats Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4 text-center">🧪 Test Data Flow - Passenger Details to View</h2>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Test Complete Booking Flow</h5>
                    <p class="card-text">This form will test the complete data flow from passenger details to view passenger page.</p>
                    
                    <form method="POST" action="add_multiple_passengers.php">
                        <input type="hidden" name="passengers_data" value='[
                            {
                                "name": "Test User 1",
                                "age": "30",
                                "gender": "Male",
                                "seat_no": "A1",
                                "email": "test@example.com",
                                "phone": "1234567890",
                                "password": "test123",
                                "train_id": "1",
                                "from_station_id": "1",
                                "to_station_id": "2",
                                "class_type": "General",
                                "journey_date": "2024-01-15",
                                "payment_mode": "Offline",
                                "fare": "100",
                                "distance": "50"
                            },
                            {
                                "name": "Test User 2",
                                "age": "25",
                                "gender": "Female",
                                "seat_no": "A2",
                                "email": "test@example.com",
                                "phone": "1234567890",
                                "password": "test123",
                                "train_id": "1",
                                "from_station_id": "1",
                                "to_station_id": "2",
                                "class_type": "General",
                                "journey_date": "2024-01-15",
                                "payment_mode": "Offline",
                                "fare": "100",
                                "distance": "50"
                            }
                        ]'>
                        <input type="hidden" name="total_fare" value="200">
                        
                        <div class="alert alert-info">
                            <h6>📋 Test Data Preview:</h6>
                            <ul>
                                <li><strong>Passengers:</strong> Test User 1 (Seat A1), Test User 2 (Seat A2)</li>
                                <li><strong>Journey:</strong> Station 1 → Station 2</li>
                                <li><strong>Train:</strong> Train 1</li>
                                <li><strong>Date:</strong> 2024-01-15</li>
                                <li><strong>Total Fare:</strong> ₹200</li>
                            </ul>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                🚀 Test Complete Booking Flow
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Manual Steps to Test</h5>
                    <ol>
                        <li><strong>Step 1:</strong> Click the "Test Complete Booking Flow" button above</li>
                        <li><strong>Step 2:</strong> You should see a success message with passenger IDs</li>
                        <li><strong>Step 3:</strong> The page should automatically redirect to view_passenger.php</li>
                        <li><strong>Step 4:</strong> You should see both passengers (Test User 1 & Test User 2) displayed correctly</li>
                    </ol>
                    
                    <div class="alert alert-warning">
                        <strong>⚠️ Expected Result:</strong> After booking, you should see both test passengers with their details (names, ages, seats A1 & A2) displayed in the view passenger page.
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">🔍 Check Recent Bookings</h5>
                    <p>Click below to see the most recent passenger bookings:</p>
                    
                    <?php
                    include("./../connect/db.php");
                    $db = (new connect())->myconnect();
                    
                    $recent_query = "SELECT id, name, email, seat_no, journey_date, created_at 
                                   FROM passengers 
                                   ORDER BY created_at DESC 
                                   LIMIT 5";
                    $recent_result = mysqli_query($db, $recent_query);
                    
                    if ($recent_result && mysqli_num_rows($recent_result) > 0) {
                        echo "<div class='table-responsive'>";
                        echo "<table class='table table-striped'>";
                        echo "<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Seat</th><th>Journey Date</th><th>Action</th></tr></thead>";
                        echo "<tbody>";
                        
                        while ($row = mysqli_fetch_assoc($recent_result)) {
                            echo "<tr>";
                            echo "<td>" . $row['id'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . ($row['seat_no'] ?: 'N/A') . "</td>";
                            echo "<td>" . $row['journey_date'] . "</td>";
                            echo "<td><a href='view_passenger.php?id=" . $row['id'] . "' class='btn btn-sm btn-outline-primary'>View</a></td>";
                            echo "</tr>";
                        }
                        
                        echo "</tbody></table>";
                        echo "</div>";
                    } else {
                        echo "<div class='alert alert-info'>No recent bookings found.</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
