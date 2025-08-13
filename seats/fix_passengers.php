<?php
// Quick fix script to clean duplicate passengers and test form processing
include("./../connect/db.php");
$db = (new connect())->myconnect();

if (isset($_GET['clean'])) {
    // Remove duplicate passengers keeping only the latest one per seat
    $clean_query = "DELETE p1 FROM passengers p1
                   INNER JOIN passengers p2 
                   WHERE p1.id < p2.id 
                   AND p1.seat_no = p2.seat_no 
                   AND p1.email = p2.email 
                   AND p1.journey_date = p2.journey_date";
    
    if (mysqli_query($db, $clean_query)) {
        echo "<div class='alert alert-success'>✅ Duplicate passengers cleaned!</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Error: " . mysqli_error($db) . "</div>";
    }
}

// Show current passenger data
$recent_query = "SELECT id, name, seat_no, email, phone, journey_date, status, created_at 
                FROM passengers 
                ORDER BY created_at DESC 
                LIMIT 20";
$result = mysqli_query($db, $recent_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fix Passenger Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>🔧 Fix Passenger Data Issues</h2>
        
        <div class="mb-3">
            <a href="?clean=1" class="btn btn-warning" onclick="return confirm('Remove duplicate passengers?')">
                🧹 Clean Duplicates
            </a>
            <a href="view_passenger.php?id=<?= mysqli_fetch_assoc(mysqli_query($db, "SELECT MAX(id) as max_id FROM passengers"))['max_id'] ?? 1 ?>" class="btn btn-primary">
                👁️ View Latest Ticket
            </a>
        </div>
        
        <h3>Recent Passengers:</h3>
        <table class="table table-striped">
            <tr><th>ID</th><th>Name</th><th>Seat</th><th>Email</th><th>Phone</th><th>Journey Date</th><th>Status</th><th>Created</th></tr>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= $row['seat_no'] ?></td>
                    <td><?= $row['email'] ?></td>
                    <td><?= $row['phone'] ?></td>
                    <td><?= $row['journey_date'] ?></td>
                    <td><?= $row['status'] ?? 'Active' ?></td>
                    <td><?= $row['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
        
        <div class="alert alert-info">
            <h5>🔍 Issue Analysis:</h5>
            <p>The problem is that the same passenger name "Rutuja" is being saved for all seats instead of different names like "Rutuja" and "Riddhi".</p>
            <p><strong>Root Cause:</strong> The form in passenger_details.php is not properly capturing different names for different seats.</p>
            <p><strong>Solution:</strong> Use the "Clean Duplicates" button above and ensure each passenger form field has unique names.</p>
        </div>
    </div>
</body>
</html>