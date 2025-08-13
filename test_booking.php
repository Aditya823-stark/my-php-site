<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Booking Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4 text-center">🧪 Test Booking Form (Direct to Database)</h2>
    
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="add_passenger.php">
                        <h5 class="mb-3">Personal Details</h5>
                        <input type="text" name="name" class="form-control mb-2" placeholder="Full Name" value="John Doe" required>
                        <input type="number" name="age" class="form-control mb-2" placeholder="Age" value="30" required>
                        <select name="gender" class="form-control mb-2" required>
                            <option value="">Gender</option>
                            <option value="Male" selected>Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>

                        <h5 class="mt-3 mb-3">Contact Details</h5>
                        <input type="email" name="email" class="form-control mb-2" placeholder="Email" value="john@example.com" required>
                        <input type="tel" name="phone" class="form-control mb-2" placeholder="Phone" value="1234567890" required>
                        <input type="password" name="password" class="form-control mb-2" placeholder="Password" value="test123" required>

                        <h5 class="mt-3 mb-3">Journey Details</h5>
                        <select name="from_station_id" class="form-control mb-2" required>
                            <option value="">From Station</option>
                            <option value="1" selected>Station 1</option>
                            <option value="2">Station 2</option>
                            <option value="3">Station 3</option>
                        </select>

                        <select name="to_station_id" class="form-control mb-2" required>
                            <option value="">To Station</option>
                            <option value="1">Station 1</option>
                            <option value="2" selected>Station 2</option>
                            <option value="3">Station 3</option>
                        </select>

                        <select name="train_id" class="form-control mb-2" required>
                            <option value="">Select Train</option>
                            <option value="1" selected>Train 1</option>
                            <option value="2">Train 2</option>
                            <option value="3">Train 3</option>
                        </select>

                        <select name="class_type" class="form-control mb-2" required>
                            <option value="">Select Class</option>
                            <option value="General" selected>General</option>
                            <option value="Sleeper">Sleeper</option>
                            <option value="AC">AC</option>
                        </select>

                        <input type="date" name="journey_date" class="form-control mb-3" value="2024-01-15" required>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">🚀 Test Direct Booking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
