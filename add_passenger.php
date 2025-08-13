<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("connect/db.php");
include("connect/fun.php");
require 'vendor/autoload.php';
require 'email_config.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Debug: Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("<h3 style='color:red;'>❌ No form data received. Please submit the form properly.</h3>");
}

// Debug: Show all received POST data
echo "<h3>📋 Debug Information:</h3>";
echo "<p><strong>Form submitted successfully!</strong></p>";
echo "<details><summary>Click to view all form data</summary><pre>" . print_r($_POST, true) . "</pre></details>";

try {
    $db = (new connect())->myconnect();
    if (!$db) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    echo "<p style='color:green;'>✅ Database connected successfully</p>";
    $fun = new fun($db);
} catch (Exception $e) {
    die("<h3 style='color:red;'>❌ Database Error: " . $e->getMessage() . "</h3>");
}

// Collect form data
$data = [
    'train_id' => $_POST['train_id'] ?? 0,
    'name' => $_POST['name'] ?? '',
    'age' => $_POST['age'] ?? '',
    'gender' => $_POST['gender'] ?? '',
    'email' => $_POST['email'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'password' => $_POST['password'] ?? '',
    'from_station_id' => $_POST['from_station_id'] ?? 0,
    'to_station_id' => $_POST['to_station_id'] ?? 0,
    'class_type' => $_POST['class_type'] ?? '',
    'journey_date' => $_POST['journey_date'] ?? '',
];

// Get fare and distance
$route = $fun->get_route($data['from_station_id'], $data['to_station_id'], $data['train_id']);
$data['fare'] = $route['fare'] ?? 0;
$data['distance'] = $route['distance'] ?? 0;

// Insert passenger
$passenger_id = $fun->add_passenger($data);

if ($passenger_id) {
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
                <tr><th>Train</th><td><?= $train['name'] ?> (<?= $train['id'] ?>)</td></tr>
                <tr><th>Class</th><td><?= $data['class_type'] ?></td></tr>
                <tr><th>Distance</th><td><?= $data['distance'] ?> KM</td></tr>
                <tr><th>Fare</th><td>&#8377;<?= number_format($data['fare'], 2) ?></td></tr>
                <tr><th>Booking Time</th><td><?= $bookingDate ?></td></tr>
            </table>
        </div>

        <div class="section">
            <h3>Passenger Details</h3>
            <table>
                <tr>
                    <th>#</th><th>Name</th><th>Age</th><th>Gender</th><th>Status</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td><?= htmlspecialchars($data['name']) ?></td>
                    <td><?= $data['age'] ?></td>
                    <td><?= $data['gender'] ?></td>
                    <td class="status-booked">BOOKED</td>
                </tr>
            </table>
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
    $pdfPath = "$ticketDir/ticket_$passenger_id.pdf";
    file_put_contents($pdfPath, $pdfOutput);

    // Send Email
    $mail = new PHPMailer(true);
    try {
        // Configure SMTP using centralized config
        configureSMTP($mail);
        
        // Enable verbose debug output (remove in production)
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = 'html';

        // Email content
        $mail->addAddress($data['email'], $data['name']);
        $mail->addAttachment($pdfPath, "IRCTC_Ticket_$passenger_id.pdf");
        $mail->isHTML(true);
        $mail->Subject = 'Your IRCTC e-Ticket (ERS) - Booking ID: ' . $passenger_id;
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #0d6efd; text-align: center;'>IRCTC Railway Booking</h2>
                <p>Dear " . htmlspecialchars($data['name']) . ",</p>
                <p>Your IRCTC e-ticket has been successfully booked! Please find your ticket attached to this email.</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #0d6efd; margin-top: 0;'>Journey Summary:</h3>
                    <p><strong>From:</strong> " . htmlspecialchars($from['name']) . "</p>
                    <p><strong>To:</strong> " . htmlspecialchars($to['name']) . "</p>
                    <p><strong>Date:</strong> " . htmlspecialchars($data['journey_date']) . "</p>
                    <p><strong>Train:</strong> " . htmlspecialchars($train['name']) . " (" . $train['id'] . ")</p>
                    <p><strong>Class:</strong> " . htmlspecialchars($data['class_type']) . "</p>
                    <p><strong>Fare:</strong> ₹" . number_format($data['fare'], 2) . "</p>
                </div>
                
                <p><strong>Important Notes:</strong></p>
                <ul>
                    <li>Please carry a valid government ID proof while traveling</li>
                    <li>Report at the station at least 30 minutes before departure</li>
                    <li>Keep this e-ticket handy during your journey</li>
                </ul>
                
                <p>Thank you for choosing IRCTC Railway!</p>
                <p style='color: #6c757d; font-size: 12px;'>This is an automated email. Please do not reply.</p>
            </div>
        ";

        $mail->send();
        echo "<h3 style='color:green;'>✅ Ticket booked and emailed successfully!</h3>";
        echo "<p style='color:green;'>📧 Confirmation email sent to: " . htmlspecialchars($data['email']) . "</p>";
        
    } catch (Exception $e) {
        // Log the detailed error for debugging
        error_log("Email Error for Passenger ID $passenger_id: " . $mail->ErrorInfo);
        
        echo "<h3 style='color:orange;'>⚠️ Ticket booked successfully, but email delivery failed.</h3>";
        echo "<p style='color:orange;'>📧 Email: " . htmlspecialchars($data['email']) . "</p>";
        echo "<p style='color:red;'>Error: " . htmlspecialchars($mail->ErrorInfo) . "</p>";
        echo "<p style='color:blue;'>💡 Please download your ticket manually from the link below.</p>";
    }

    echo "<a href='view_passenger.php?id=$passenger_id' target='_blank'>👉 View Ticket</a>";
} else {
    echo "<h3 style='color:red;'>❌ Failed to book ticket. Please try again.</h3>";
}
?>