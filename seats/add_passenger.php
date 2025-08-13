<?php
include(__DIR__ . '/../connect/db.php');  // Adjust path as needed
include(__DIR__ . '/../connect/fun.php');  // Adjust path as needed
require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = (new connect())->myconnect();
$fun = new fun($db);

// Collect form data - The data comes from the session or POST
session_start();

// Get data from session if available (from select_seats.php)
$session_data = $_SESSION['booking_data'] ?? [];

// Merge session data with POST data, POST takes priority
$data = [
    'train_id' => $_POST['train_id'] ?? $session_data['train_id'] ?? 0,
    'name' => trim($_POST['name'] ?? $session_data['name'] ?? ''),
    'age' => $_POST['age'] ?? $session_data['age'] ?? '',
    'gender' => $_POST['gender'] ?? $session_data['gender'] ?? '',
    'email' => trim($_POST['email'] ?? $session_data['email'] ?? ''),
    'phone' => trim($_POST['phone'] ?? $session_data['phone'] ?? ''),
    'password' => $_POST['password'] ?? $session_data['password'] ?? '',
    'from_station_id' => $_POST['from_station_id'] ?? $session_data['from_station_id'] ?? 0,
    'to_station_id' => $_POST['to_station_id'] ?? $session_data['to_station_id'] ?? 0,
    'class_type' => $_POST['class_type'] ?? $session_data['class_type'] ?? '',
    'journey_date' => $_POST['journey_date'] ?? $session_data['journey_date'] ?? '',
    'payment_mode' => $_POST['payment_mode'] ?? $session_data['payment_mode'] ?? 'Offline',
    'departure_time' => $_POST['departure_time'] ?? $session_data['departure_time'] ?? '08:00', // Default time
];

// Debug: Log the received data
error_log("Received POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($session_data, true));
error_log("Processed data array: " . print_r($data, true));

// Validate required fields
$required_fields = ['name', 'email', 'phone', 'class_type'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    echo "<div class='alert alert-danger'>Missing required fields: " . implode(', ', $missing_fields) . "</div>";
    echo "<div class='alert alert-info'>Debug - POST data:<pre>" . print_r($_POST, true) . "</pre></div>";
    echo "<div class='alert alert-info'>Debug - Session data:<pre>" . print_r($session_data, true) . "</pre></div>";
    echo "<div class='alert alert-info'>Debug - Final data:<pre>" . print_r($data, true) . "</pre></div>";
    exit;
}

// Handle selected seats
$selected_seats = $_POST['selected_seats'] ?? '';
$seat_count = $_POST['seat_count'] ?? 1;
$seats_array = !empty($selected_seats) ? explode(',', $selected_seats) : [];

// Sort seats in ascending order
if (!empty($seats_array)) {
    sort($seats_array, SORT_NUMERIC);
}

// Get fare and distance
$route = $fun->get_route($data['from_station_id'], $data['to_station_id'], $data['train_id']);
$fare_per_seat = $route['fare'] ?? 0;
$data['fare'] = $fare_per_seat * max(count($seats_array), 1); // Total fare for all seats
$data['distance'] = $route['distance'] ?? 0;

// Insert passenger with seat information
$passenger_ids = [];
if (!empty($seats_array)) {
    foreach ($seats_array as $seat_no) {
        $seat_data = $data;
        $seat_data['seat_no'] = trim($seat_no);
        $seat_data['fare'] = $fare_per_seat; // Individual seat fare
        $passenger_id = $fun->add_passenger_with_seat($seat_data);
        if ($passenger_id) {
            $passenger_ids[] = $passenger_id;
        }
    }
} else {
    // Fallback for single passenger without specific seat
    $data['fare'] = $fare_per_seat; // Single seat fare
    $passenger_id = $fun->add_passenger($data);
    if ($passenger_id) {
        $passenger_ids[] = $passenger_id;
    }
}

$main_passenger_id = !empty($passenger_ids) ? $passenger_ids[0] : null;

if ($main_passenger_id) {
    $train = $fun->get_train_by_id($data['train_id']);
    $from = $fun->get_station_by_id($data['from_station_id']);
    $to = $fun->get_station_by_id($data['to_station_id']);
    $bookingDate = date("d-M-Y H:i:s");

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
        </style>
    </head>
    <body>
    <div class="container">
        <h2>Electronic Reservation Slip (ERS)</h2>

        <div class="section">
            <h3>Journey Details</h3>
            <table>
                <tr><th>From</th><td><?= $from['name'] ?></td></tr>
                <tr><th>To</th><td><?= $to['name'] ?></td></tr>
                <tr><th>Date of Journey</th><td><?= $data['journey_date'] ?></td></tr>
                <tr><th>Departure Time</th><td><?php
                    $time = $data['departure_time'] ?? '08:00';
                    echo date('h:i A', strtotime($time));
                ?></td></tr>
                <tr><th>Train</th><td><?= $train['name'] ?> (<?= $train['id'] ?>)</td></tr>
                <tr><th>Class</th><td><?= $data['class_type'] ?></td></tr>
                <tr><th>Seats Booked</th><td><?= !empty($seats_array) ? implode(', ', $seats_array) : 'Not Assigned' ?> (<?= max(count($seats_array), 1) ?> seat<?= max(count($seats_array), 1) > 1 ? 's' : '' ?>)</td></tr>
                <tr><th>Distance</th><td><?= $data['distance'] ?> KM</td></tr>
                <tr><th>Fare per Seat</th><td>&#8377;<?= number_format($fare_per_seat, 2) ?></td></tr>
                <tr><th>Total Fare</th><td>&#8377;<?= number_format($fare_per_seat * max(count($seats_array), 1), 2) ?></td></tr>
                <tr><th>Payment Mode</th><td><?= $data['payment_mode'] ?></td></tr>
                <tr><th>Payment Status</th><td><?= $data['payment_mode'] === 'Online' ? 'Paid' : 'Payment Pending' ?></td></tr>
                <tr><th>Booking Time</th><td><?= $bookingDate ?></td></tr>
            </table>
        </div>

        <div class="section">
            <h3>Passenger & Seat Details</h3>
            <table>
                <tr>
                    <th>Seat #</th><th>Passenger Name</th><th>Age</th><th>Gender</th><th>Status</th><th>Payment</th>
                </tr>
                <?php if (!empty($seats_array)): ?>
                    <?php foreach ($seats_array as $index => $seat_no): ?>
                        <tr>
                            <td style="font-weight: bold; color: #0d6efd;"><?= trim($seat_no) ?></td>
                            <td><?= htmlspecialchars($data['name']) ?></td>
                            <td><?= $data['age'] ?></td>
                            <td><?= $data['gender'] ?></td>
                            <td class="status-booked">CONFIRMED</td>
                            <td style="color: <?= $data['payment_mode'] === 'Online' ? 'green' : 'orange' ?>;">
                                <?= $data['payment_mode'] === 'Online' ? 'PAID' : 'PENDING' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td>N/A</td>
                        <td><?= htmlspecialchars($data['name']) ?></td>
                        <td><?= $data['age'] ?></td>
                        <td><?= $data['gender'] ?></td>
                        <td class="status-booked">CONFIRMED</td>
                        <td style="color: <?= $data['payment_mode'] === 'Online' ? 'green' : 'orange' ?>;">
                            <?= $data['payment_mode'] === 'Online' ? 'PAID' : 'PENDING' ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
            
            <?php if (!empty($seats_array) && count($seats_array) > 1): ?>
                <div style="margin-top: 15px; padding: 10px; background-color: #e3f2fd; border-radius: 5px;">
                    <strong>Booking Summary:</strong><br>
                    Total Seats: <?= count($seats_array) ?><br>
                    Seat Numbers: <?= implode(', ', $seats_array) ?><br>
                    Total Amount: &#8377;<?= number_format($fare_per_seat * count($seats_array), 2) ?>
                </div>
            <?php elseif (!empty($seats_array)): ?>
                <div style="margin-top: 15px; padding: 10px; background-color: #e3f2fd; border-radius: 5px;">
                    <strong>Booking Summary:</strong><br>
                    Seat Number: <?= implode(', ', $seats_array) ?><br>
                    Amount: &#8377;<?= number_format($fare_per_seat, 2) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Contact Info</h3>
            <table>
                <tr><th>Email</th><td><?= $data['email'] ?></td></tr>
                <tr><th>Phone</th><td><?= $data['phone'] ?></td></tr>
            </table>
        </div>

        <div class="footer">
            * Carry a valid government ID proof while traveling.<br>
            ** This is a computer-generated ticket. No signature required.
        </div>
    </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    // Generate PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A5', 'landscape'); // Single-page
    $dompdf->render();
    $pdfOutput = $dompdf->output();

    // Save PDF
    $ticketDir = __DIR__ . "/tickets";
    if (!is_dir($ticketDir)) mkdir($ticketDir, 0755, true);
    $pdfPath = "$ticketDir/ticket_$main_passenger_id.pdf";
    file_put_contents($pdfPath, $pdfOutput);

    // Handle payment status
    if ($data['payment_mode'] == 'Online') {
        // Trigger payment gateway QR code or redirect to payment platform
        echo "<h3 style='color:green;'>Please make the payment using the QR code below:</h3>";
        // QR code generation logic here (Razorpay, Stripe, etc.)

        // Set status as 'Paid' once payment is successful (simulation here)
        $payment_status = 'Paid';
    } else {
        // Set payment status as 'Pending' for Offline payments
        $payment_status = 'Pending';
    }

    // Save payment status to database for all passenger records
    foreach ($passenger_ids as $pid) {
        $update_payment_status = "UPDATE passengers SET payment_status = '$payment_status' WHERE id = $pid";
        mysqli_query($db, $update_payment_status);
    }
// Send Email
include(__DIR__ . '/../email_config.php');
$mail = new PHPMailer(true);
try {
    // Use the email configuration function
    $mail = configureSMTP($mail);

    // Validate email before adding
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid or empty email address: " . $data['email']);
    }

    $mail->addAddress($data['email'], $data['name']);
        $mail->addAttachment($pdfPath);
        $mail->isHTML(true);
        $mail->Subject = 'Your IRCTC e-Ticket (ERS) - ' . max(count($seats_array), 1) . ' Seat' . (max(count($seats_array), 1) > 1 ? 's' : '') . ' Confirmed';
        
        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #0d6efd; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .ticket-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .seat-info { background-color: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
                .footer { background-color: #6c757d; color: white; padding: 15px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>🚂 IRCTC E-Ticket Confirmation</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($data['name']) . "</strong>,</p>
                
                <p>Your railway ticket has been successfully booked! Here are your booking details:</p>
                
                <div class='ticket-info'>
                    <h3>Journey Details</h3>
                    <p><strong>Train:</strong> " . htmlspecialchars($train['name']) . " (" . $train['id'] . ")</p>
                    <p><strong>From:</strong> " . htmlspecialchars($from['name']) . " <strong>To:</strong> " . htmlspecialchars($to['name']) . "</p>
                    <p><strong>Journey Date:</strong> " . htmlspecialchars($data['journey_date']) . "</p>
                    <p><strong>Departure Time:</strong> " . date('h:i A', strtotime($data['departure_time'] ?? '08:00')) . "</p>
                    <p><strong>Class:</strong> " . htmlspecialchars($data['class_type']) . "</p>
                </div>
                
                <div class='seat-info'>
                    <h3>Seat Information</h3>
                    <p><strong>Seat Numbers:</strong> " . (!empty($seats_array) ? implode(', ', $seats_array) : 'Not Assigned') . "</p>
                    <p><strong>Total Seats:</strong> " . max(count($seats_array), 1) . "</p>
                    <p><strong>Fare per Seat:</strong> &#8377;" . number_format($fare_per_seat, 2) . "</p>
                    <p><strong>Total Amount:</strong> &#8377;" . number_format($fare_per_seat * max(count($seats_array), 1), 2) . "</p>
                    <p><strong>Payment Mode:</strong> " . htmlspecialchars($data['payment_mode']) . "</p>
                    <p><strong>Payment Status:</strong> " . ($data['payment_mode'] === 'Online' ? '✅ Paid' : '⏳ Payment Pending') . "</p>
                </div>
                
                <div class='ticket-info'>
                    <h3>Important Instructions</h3>
                    <ul>
                        <li>Please carry a valid government ID proof while traveling</li>
                        <li>Report at the station at least 30 minutes before departure</li>
                        <li>Keep this e-ticket and the attached PDF for verification</li>
                        " . ($data['payment_mode'] === 'Offline' ? '<li><strong>⚠️ Please complete your payment as soon as possible</strong></li>' : '') . "
                    </ul>
                </div>
                
                <p>Your detailed e-ticket is attached as a PDF file.</p>
                <p><strong>Wishing you a safe and comfortable journey!</strong></p>
            </div>
            <div class='footer'>
                <p>This is an auto-generated email from IRCTC Railway Booking System</p>
                <p>For support, please contact our customer service</p>
            </div>
        </body>
        </html>";
        
        $mail->Body = $emailBody;

        $mail->send();
        echo "<h3 style='color:green;'>✅ Ticket booked and emailed successfully!</h3>";
    } catch (Exception $e) {
        echo "<h3 style='color:orange;'>Ticket booked, but email failed: {$mail->ErrorInfo}</h3>";
    }

    echo "<a href='view_passenger.php?id=$main_passenger_id' target='_blank'>👉 View Ticket</a>";
} else {
    echo "<h3 style='color:red;'>❌ Failed to book ticket. Please try again.</h3>";
}
?>
