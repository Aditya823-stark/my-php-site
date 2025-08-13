<?php
include(__DIR__ . '/../connect/db.php');
include(__DIR__ . '/../connect/fun.php');
require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = (new connect())->myconnect();
$fun = new fun($db);

// Get passengers data from POST or session
$passengers_data = [];
$total_fare = 0;

if (isset($_POST['passengers_data'])) {
    $passengers_data = json_decode($_POST['passengers_data'], true);
    $total_fare = $_POST['total_fare'] ?? 0;
} else {
    session_start();
    $passengers_data = $_SESSION['passengers_data'] ?? [];
    $total_fare = $_SESSION['total_fare'] ?? 0;
}

if (empty($passengers_data)) {
    echo "<h3 style='color:red;'>❌ No passenger data found. Please try again.</h3>";
    exit();
}

// Insert all passengers
$passenger_ids = [];
$main_passenger_data = $passengers_data[0]; // Use first passenger as main

foreach ($passengers_data as $passenger_data) {
    $passenger_id = $fun->add_passenger_with_seat($passenger_data);
    if ($passenger_id) {
        $passenger_ids[] = $passenger_id;
    }
}

$main_passenger_id = !empty($passenger_ids) ? $passenger_ids[0] : null;

if ($main_passenger_id) {
    $train = $fun->get_train_by_id($main_passenger_data['train_id']);
    $from = $fun->get_station_by_id($main_passenger_data['from_station_id']);
    $to = $fun->get_station_by_id($main_passenger_data['to_station_id']);
    $bookingDate = date("d-M-Y H:i:s");
    
    // Collect all seat numbers
    $all_seats = array_column($passengers_data, 'seat_no');
    sort($all_seats, SORT_NUMERIC);
    
    // Start PDF generation
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>IRCTC E-Ticket</title>
        <style>
            body {
                font-family: 'Segoe UI', sans-serif;
                font-size: 13px;
                margin: 0;
                padding: 20px;
                background-color: #f9fafc;
            }
            .container {
                max-width: 100%;
                background: #fff;
                border: 2px solid #0d6efd;
                border-radius: 8px;
                padding: 20px;
            }
            h2 {
                background-color: #0d6efd;
                color: #fff;
                padding: 10px;
                text-align: center;
                margin: 0 0 15px;
                border-radius: 4px;
                font-size: 18px;
            }
            .section {
                margin-bottom: 15px;
            }
            .section h3 {
                color: #0d6efd;
                border-bottom: 1px dashed #ccc;
                padding-bottom: 5px;
                font-size: 15px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 8px;
            }
            th, td {
                border: 1px solid #dee2e6;
                padding: 6px;
                font-size: 13px;
            }
            th {
                background-color: #f1f3f5;
                text-align: left;
            }
            .status-booked {
                color: green;
                font-weight: bold;
            }
            .footer {
                text-align: center;
                font-size: 11px;
                color: #6c757d;
                margin-top: 10px;
            }
            .passenger-row {
                background-color: #f8f9fa;
            }
            .passenger-row:nth-child(even) {
                background-color: #e9ecef;
            }
        </style>
    </head>
    <body>
    <div class="container">
        <h2>Electronic Reservation Slip (ERS) - Multiple Passengers</h2>

        <div class="section">
            <h3>Journey Details</h3>
            <table>
                <tr><th>From</th><td><?= $from['name'] ?></td></tr>
                <tr><th>To</th><td><?= $to['name'] ?></td></tr>
                <tr><th>Date of Journey</th><td><?= $main_passenger_data['journey_date'] ?></td></tr>
                <tr><th>Train</th><td><?= $train['name'] ?> (<?= $train['id'] ?>)</td></tr>
                <tr><th>Class</th><td><?= $main_passenger_data['class_type'] ?></td></tr>
                <tr><th>Total Seats Booked</th><td><?= count($all_seats) ?> seats</td></tr>
                <tr><th>Seat Numbers</th><td><?= implode(', ', $all_seats) ?></td></tr>
                <tr><th>Distance</th><td><?= $main_passenger_data['distance'] ?> KM</td></tr>
                <tr><th>Fare per Seat</th><td>&#8377;<?= number_format($main_passenger_data['fare'], 2) ?></td></tr>
                <tr><th>Total Fare</th><td>&#8377;<?= number_format($total_fare, 2) ?></td></tr>
                <tr><th>Payment Mode</th><td><?= $main_passenger_data['payment_mode'] ?></td></tr>
                <tr><th>Payment Status</th><td><?= $main_passenger_data['payment_mode'] === 'Online' ? 'Paid' : 'Payment Pending' ?></td></tr>
                <tr><th>Booking Time</th><td><?= $bookingDate ?></td></tr>
            </table>
        </div>

        <div class="section">
            <h3>Passenger & Seat Details</h3>
            <table>
                <tr>
                    <th>Seat #</th><th>Passenger Name</th><th>Age</th><th>Gender</th><th>Status</th><th>Payment</th>
                </tr>
                <?php foreach ($passengers_data as $index => $passenger): ?>
                    <tr class="passenger-row">
                        <td style="font-weight: bold; color: #0d6efd;"><?= $passenger['seat_no'] ?></td>
                        <td><?= htmlspecialchars($passenger['name']) ?></td>
                        <td><?= $passenger['age'] ?></td>
                        <td><?= $passenger['gender'] ?></td>
                        <td class="status-booked">CONFIRMED</td>
                        <td style="color: <?= $passenger['payment_mode'] === 'Online' ? 'green' : 'orange' ?>;">
                            <?= $passenger['payment_mode'] === 'Online' ? 'PAID' : 'PENDING' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
            <div style="margin-top: 15px; padding: 10px; background-color: #e3f2fd; border-radius: 5px;">
                <strong>Booking Summary:</strong><br>
                Total Passengers: <?= count($passengers_data) ?><br>
                Seat Numbers: <?= implode(', ', $all_seats) ?><br>
                Total Amount: &#8377;<?= number_format($total_fare, 2) ?>
            </div>
        </div>

        <div class="section">
            <h3>Primary Contact Info</h3>
            <table>
                <tr><th>Email</th><td><?= $main_passenger_data['email'] ?></td></tr>
                <tr><th>Phone</th><td><?= $main_passenger_data['phone'] ?></td></tr>
            </table>
        </div>

        <div class="footer">
            * Carry a valid government ID proof while traveling.<br>
            ** This is a computer-generated ticket. No signature required.<br>
            *** All passengers must be present during the journey.
        </div>
    </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    // Generate PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();

    // Save PDF
    $ticketDir = __DIR__ . "/tickets";
    if (!is_dir($ticketDir)) mkdir($ticketDir, 0755, true);
    $pdfPath = "$ticketDir/ticket_multiple_$main_passenger_id.pdf";
    file_put_contents($pdfPath, $pdfOutput);

    // Handle payment status
    $payment_status = $main_passenger_data['payment_mode'] === 'Online' ? 'Paid' : 'Pending';

    // Update payment status for all passengers
    foreach ($passenger_ids as $pid) {
        $update_payment_status = "UPDATE passengers SET payment_status = '$payment_status' WHERE id = $pid";
        mysqli_query($db, $update_payment_status);
    }

    // Send Email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'adityathakre.cse24@sbjit.edu.in';
        $mail->Password = 'lrlvbzgecsqgryse';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('adityathakre.cse24@sbjit.edu.in', 'IRCTC Railway');
        $mail->addAddress($main_passenger_data['email'], $main_passenger_data['name']);
        $mail->addAttachment($pdfPath);
        $mail->isHTML(true);
        $mail->Subject = 'Your IRCTC e-Ticket (ERS) - ' . count($passengers_data) . ' Passengers Confirmed';
        
        // Create passenger list for email
        $passenger_list = '';
        foreach ($passengers_data as $index => $passenger) {
            $passenger_list .= "<tr>
                <td>Seat " . $passenger['seat_no'] . "</td>
                <td>" . htmlspecialchars($passenger['name']) . "</td>
                <td>" . $passenger['age'] . "</td>
                <td>" . $passenger['gender'] . "</td>
            </tr>";
        }
        
        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .ticket-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .passenger-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .passenger-table th, .passenger-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .passenger-table th { background-color: #f2f2f2; }
                .footer { background-color: #6c757d; color: white; padding: 15px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>🚂 IRCTC E-Ticket Confirmation - Multiple Passengers</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($main_passenger_data['name']) . "</strong>,</p>
                
                <p>Your railway tickets have been successfully booked for " . count($passengers_data) . " passengers! Here are your booking details:</p>
                
                <div class='ticket-info'>
                    <h3>Journey Details</h3>
                    <p><strong>Train:</strong> " . htmlspecialchars($train['name']) . " (" . $train['id'] . ")</p>
                    <p><strong>From:</strong> " . htmlspecialchars($from['name']) . " <strong>To:</strong> " . htmlspecialchars($to['name']) . "</p>
                    <p><strong>Journey Date:</strong> " . htmlspecialchars($main_passenger_data['journey_date']) . "</p>
                    <p><strong>Class:</strong> " . htmlspecialchars($main_passenger_data['class_type']) . "</p>
                    <p><strong>Total Seats:</strong> " . count($passengers_data) . " (" . implode(', ', $all_seats) . ")</p>
                    <p><strong>Total Amount:</strong> &#8377;" . number_format($total_fare, 2) . "</p>
                    <p><strong>Payment Status:</strong> " . ($main_passenger_data['payment_mode'] === 'Online' ? '✅ Paid' : '⏳ Payment Pending') . "</p>
                </div>
                
                <div class='ticket-info'>
                    <h3>Passenger Details</h3>
                    <table class='passenger-table'>
                        <tr><th>Seat</th><th>Name</th><th>Age</th><th>Gender</th></tr>
                        $passenger_list
                    </table>
                </div>
                
                <div class='ticket-info'>
                    <h3>Important Instructions</h3>
                    <ul>
                        <li>Please carry a valid government ID proof while traveling</li>
                        <li>All passengers must be present during the journey</li>
                        <li>Report at the station at least 30 minutes before departure</li>
                        <li>Keep this e-ticket and the attached PDF for verification</li>
                        " . ($main_passenger_data['payment_mode'] === 'Offline' ? '<li><strong>⚠️ Please complete your payment as soon as possible</strong></li>' : '') . "
                    </ul>
                </div>
                
                <p>Your detailed e-ticket is attached as a PDF file.</p>
                <p><strong>Wishing you all a safe and comfortable journey!</strong></p>
            </div>
            <div class='footer'>
                <p>This is an auto-generated email from IRCTC Railway Booking System</p>
                <p>For support, please contact our customer service</p>
            </div>
        </body>
        </html>";
        
        $mail->Body = $emailBody;

        $mail->send();
        echo "<h3 style='color:green;'>✅ Tickets booked and emailed successfully for " . count($passengers_data) . " passengers!</h3>";
    } catch (Exception $e) {
        echo "<h3 style='color:orange;'>Tickets booked, but email failed: {$mail->ErrorInfo}</h3>";
    }

    // Success message with automatic redirect
    echo "<div style='text-align: center; padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 10px; margin: 20px;'>";
    echo "<h3 style='color: #155724;'>✅ Tickets booked successfully for " . count($passengers_data) . " passengers!</h3>";
    echo "<p style='color: #155724;'>Passenger IDs: " . implode(', ', $passenger_ids) . "</p>";
    echo "<p style='color: #155724;'>Main Passenger ID: $main_passenger_id</p>";
    echo "<p>Redirecting to view tickets...</p>";
    echo "<a href='view_passenger.php?id=$main_passenger_id' class='btn btn-primary'>👉 View Tickets Now</a>";
    echo "</div>";
    
    // Auto-redirect after 3 seconds
    echo "<script>";
    echo "setTimeout(function() { window.location.href = 'view_passenger.php?id=$main_passenger_id'; }, 3000);";
    echo "</script>";
    
    // Clear session data
    if (isset($_SESSION['passengers_data'])) {
        unset($_SESSION['passengers_data']);
        unset($_SESSION['total_fare']);
    }
} else {
    echo "<h3 style='color:red;'>❌ Failed to book tickets. Please try again.</h3>";
}
?>