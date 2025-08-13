<?php
include("./../connect/db.php");
include("./../connect/fun.php");
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = (new connect())->myconnect();
$fun = new fun($db);

// Check if required data is provided
if (!isset($_POST['passenger_id']) || !isset($_POST['email']) || !isset($_POST['name'])) {
    echo "<div class='alert alert-danger'>Missing required information for cancellation.</div>";
    exit;
}

$passenger_id = (int)$_POST['passenger_id'];
$email = mysqli_real_escape_string($db, $_POST['email']);
$name = mysqli_real_escape_string($db, $_POST['name']);

// Get passenger details
$passenger_query = "SELECT * FROM passengers WHERE id = $passenger_id AND email = '$email' AND name = '$name'";
$passenger_result = mysqli_query($db, $passenger_query);

if (!$passenger_result || mysqli_num_rows($passenger_result) == 0) {
    echo "<div class='alert alert-danger'>Passenger not found or invalid details.</div>";
    exit;
}

$passenger_data = mysqli_fetch_assoc($passenger_result);

// Check if ticket is already cancelled
if ($passenger_data['status'] === 'cancelled') {
    echo "<div class='alert alert-warning'>This ticket is already cancelled.</div>";
    echo "<a href='view_passenger.php?id=$passenger_id' class='btn btn-primary'>Back to Ticket</a>";
    exit;
}

// Get all related passengers (same booking) - match by email, phone, and booking details
$related_passengers_query = "SELECT * FROM passengers
                            WHERE email = '$email'
                            AND phone = '{$passenger_data['phone']}'
                            AND train_id = {$passenger_data['train_id']}
                            AND journey_date = '{$passenger_data['journey_date']}'
                            AND from_station_id = {$passenger_data['from_station_id']}
                            AND to_station_id = {$passenger_data['to_station_id']}
                            AND class_type = '{$passenger_data['class_type']}'
                            AND status != 'cancelled'
                            ORDER BY CAST(seat_no AS UNSIGNED) ASC";

$related_result = mysqli_query($db, $related_passengers_query);
$all_passengers = [];
while ($row = mysqli_fetch_assoc($related_result)) {
    $all_passengers[] = $row;
}

// Update all related passengers to cancelled status
$cancelled_count = 0;
$cancelled_seats = [];

foreach ($all_passengers as $passenger) {
    $update_query = "UPDATE passengers SET status = 'cancelled' WHERE id = {$passenger['id']}";
    if (mysqli_query($db, $update_query)) {
        $cancelled_count++;
        if ($passenger['seat_no']) {
            $cancelled_seats[] = $passenger['seat_no'];
        }
    }
}

// Get station and train names for display
$from_station = $fun->get_station_name($passenger_data['from_station_id']);
$to_station = $fun->get_station_name($passenger_data['to_station_id']);
$train_name = $fun->get_train_name($passenger_data['train_id']);

// Send cancellation email
if ($cancelled_count > 0) {
    $mail = new PHPMailer(true);
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'adityathakre.cse24@sbjit.edu.in';
        $mail->Password = 'lrlvbzgecsqgryse';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->SMTPDebug = 0; // Set to 2 for debugging
        $mail->Timeout = 30;

        $mail->setFrom('adityathakre.cse24@sbjit.edu.in', 'IRCTC Railway');
        $mail->addAddress($passenger_data['email'], $passenger_data['name']);
        $mail->isHTML(true);
        $mail->Subject = 'IRCTC Ticket Cancellation Confirmation - ' . $cancelled_count . ' Seat(s)';
        
        // Create passenger list for email
        $passenger_list = '';
        foreach ($all_passengers as $passenger) {
            $passenger_list .= "<tr>
                <td>Seat " . $passenger['seat_no'] . "</td>
                <td>" . htmlspecialchars($passenger['name']) . "</td>
                <td>" . $passenger['age'] . "</td>
                <td>" . $passenger['gender'] . "</td>
                <td><span style='color: red; font-weight: bold;'>CANCELLED</span></td>
            </tr>";
        }
        
        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .ticket-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #dc3545; }
                .passenger-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .passenger-table th, .passenger-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .passenger-table th { background-color: #f2f2f2; }
                .footer { background-color: #6c757d; color: white; padding: 15px; text-align: center; font-size: 12px; }
                .alert { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>❌ IRCTC Ticket Cancellation Confirmation</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($passenger_data['name']) . "</strong>,</p>
                
                <p>Your railway ticket(s) have been successfully cancelled. Here are the cancellation details:</p>
                
                <div class='ticket-info'>
                    <h3>Journey Details</h3>
                    <p><strong>Train:</strong> " . htmlspecialchars($train_name) . "</p>
                    <p><strong>Route:</strong> " . htmlspecialchars($from_station) . " → " . htmlspecialchars($to_station) . "</p>
                    <p><strong>Journey Date:</strong> " . date('d M Y', strtotime($passenger_data['journey_date'])) . "</p>
                    <p><strong>Class:</strong> " . htmlspecialchars($passenger_data['class_type']) . "</p>
                    <p><strong>Cancellation Date:</strong> " . date('d M Y, H:i:s') . "</p>
                </div>
                
                <div class='ticket-info'>
                    <h3>Cancelled Passenger Details</h3>
                    <p><strong>Total Seats Cancelled:</strong> " . $cancelled_count . "</p>
                    " . (!empty($cancelled_seats) ? "<p><strong>Seat Numbers:</strong> " . implode(', ', $cancelled_seats) . "</p>" : "") . "
                    <table class='passenger-table'>
                        <tr><th>Seat</th><th>Name</th><th>Age</th><th>Gender</th><th>Status</th></tr>
                        $passenger_list
                    </table>
                </div>
                
                <div class='alert'>
                    <h3>🏦 Refund Information</h3>
                    <ul>
                        <li><strong>For online payments:</strong> Refund will be credited to your original payment method within 5-7 working days</li>
                        <li><strong>For offline payments:</strong> Please contact the booking office for refund procedures</li>
                        <li><strong>Cancellation charges:</strong> May apply as per railway rules</li>
                        <li><strong>Contact Information:</strong> " . htmlspecialchars($passenger_data['email']) . " | " . $passenger_data['phone'] . "</li>
                    </ul>
                </div>
                
                <p><strong>We apologize for any inconvenience caused and hope to serve you again in the future.</strong></p>
            </div>
            <div class='footer'>
                <p>This is an auto-generated email from IRCTC Railway Booking System</p>
                <p>For support, please contact our customer service</p>
            </div>
        </body>
        </html>";
        
        $mail->Body = $emailBody;
        $mail->send();
        
        $email_status = "<div class='alert alert-success mt-3'><i class='fas fa-envelope'></i> <strong>Email Sent:</strong> Cancellation confirmation has been sent to " . htmlspecialchars($passenger_data['email']) . "</div>";
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        $email_status = "<div class='alert alert-warning mt-3'><i class='fas fa-exclamation-triangle'></i> <strong>Email Failed:</strong> Cancellation processed but email could not be sent. Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    $email_status = "<div class='alert alert-danger mt-3'><i class='fas fa-times'></i> <strong>No Cancellation:</strong> No seats were cancelled.</div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Cancellation Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .cancellation-container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .cancellation-header {
            background-color: #dc3545;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
            margin: -30px -30px 30px -30px;
        }
        .success-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="cancellation-container">
    <div class="cancellation-header">
        <h2><i class="fas fa-times-circle"></i> Ticket Cancellation Confirmation</h2>
    </div>

    <?php if ($cancelled_count > 0): ?>
        <div class="text-center mb-4">
            <i class="fas fa-check-circle success-icon"></i>
            <h3 class="text-success">Cancellation Successful!</h3>
            <p class="lead">Your ticket(s) have been successfully cancelled.</p>
        </div>

        <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> Cancellation Details</h5>
            <div class="row">
                <div class="col-md-6">
                    <strong>Passenger Name:</strong> <?= htmlspecialchars($passenger_data['name']) ?><br>
                    <strong>Email:</strong> <?= htmlspecialchars($passenger_data['email']) ?><br>
                    <strong>Phone:</strong> <?= $passenger_data['phone'] ?><br>
                </div>
                <div class="col-md-6">
                    <strong>Train:</strong> <?= htmlspecialchars($train_name) ?><br>
                    <strong>Route:</strong> <?= htmlspecialchars($from_station) ?> → <?= htmlspecialchars($to_station) ?><br>
                    <strong>Journey Date:</strong> <?= date('d M Y', strtotime($passenger_data['journey_date'])) ?><br>
                </div>
            </div>
        </div>

        <div class="alert alert-warning">
            <h6><i class="fas fa-exclamation-triangle"></i> Cancelled Seats</h6>
            <p><strong>Total Seats Cancelled:</strong> <?= $cancelled_count ?></p>
            <?php if (!empty($cancelled_seats)): ?>
                <p><strong>Seat Numbers:</strong> <?= implode(', ', $cancelled_seats) ?></p>
            <?php endif; ?>
            <p><strong>Cancellation Time:</strong> <?= date('d M Y, H:i:s') ?></p>
        </div>

        <div class="alert alert-secondary">
            <h6><i class="fas fa-money-bill-wave"></i> Refund Information</h6>
            <p>Your refund will be processed according to the railway cancellation policy:</p>
            <ul>
                <li>For online payments: Refund will be credited to your original payment method within 5-7 working days</li>
                <li>For offline payments: Please contact the booking office for refund procedures</li>
                <li>Cancellation charges may apply as per railway rules</li>
            </ul>
        </div>

        <?php if (isset($email_status)) echo $email_status; ?>

        <div class="text-center mt-4">
            <a href="view_passenger.php?id=<?= $passenger_id ?>" class="btn btn-primary me-2">
                <i class="fas fa-eye"></i> View Cancelled Ticket
            </a>
            <a href="../" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>

    <?php else: ?>
        <div class="text-center mb-4">
            <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: #ffc107; margin-bottom: 20px;"></i>
            <h3 class="text-warning">Cancellation Failed!</h3>
            <p class="lead">Unable to cancel the ticket. Please try again or contact support.</p>
        </div>

        <div class="text-center mt-4">
            <a href="view_passenger.php?id=<?= $passenger_id ?>" class="btn btn-primary me-2">
                <i class="fas fa-arrow-left"></i> Back to Ticket
            </a>
            <a href="../" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>