<?php
// Simple test to check form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h3>Form Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>Processing Multiple Passengers:</h3>";
    $seats = ['6', '9']; // Test seats
    
    for ($i = 0; $i < count($seats); $i++) {
        $name = $_POST["passenger_name_$i"] ?? 'NOT_FOUND';
        $age = $_POST["passenger_age_$i"] ?? 'NOT_FOUND';
        $gender = $_POST["passenger_gender_$i"] ?? 'NOT_FOUND';
        
        echo "<p><strong>Passenger $i:</strong> Name='$name', Age='$age', Gender='$gender', Seat={$seats[$i]}</p>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Multiple Passenger Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test Multiple Passenger Form</h2>
        <form method="POST">
            <!-- Passenger 0 (Seat 6) -->
            <div class="card mb-3">
                <div class="card-header">Passenger 1 - Seat 6</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label>Name:</label>
                            <input type="text" name="passenger_name_0" class="form-control" value="Rutuja" required>
                        </div>
                        <div class="col-md-4">
                            <label>Age:</label>
                            <input type="number" name="passenger_age_0" class="form-control" value="25" required>
                        </div>
                        <div class="col-md-4">
                            <label>Gender:</label>
                            <select name="passenger_gender_0" class="form-control" required>
                                <option value="Female" selected>Female</option>
                                <option value="Male">Male</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Passenger 1 (Seat 9) -->
            <div class="card mb-3">
                <div class="card-header">Passenger 2 - Seat 9</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label>Name:</label>
                            <input type="text" name="passenger_name_1" class="form-control" value="Riddhi" required>
                        </div>
                        <div class="col-md-4">
                            <label>Age:</label>
                            <input type="number" name="passenger_age_1" class="form-control" value="23" required>
                        </div>
                        <div class="col-md-4">
                            <label>Gender:</label>
                            <select name="passenger_gender_1" class="form-control" required>
                                <option value="Female" selected>Female</option>
                                <option value="Male">Male</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Test Form Submission</button>
        </form>
    </div>
</body>
</html>