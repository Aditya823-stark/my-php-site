<?php
require 'connect/db.php';
require 'connect/fun.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

$db = (new connect())->myconnect();
mysqli_set_charset($db, "utf8mb4"); // ✅ Ensure UTF-8 compatibility

$passenger_id = $_POST['passenger_id'] ?? 0;
$email = $_POST['email'] ?? '';
$name = $_POST['name'] ?? '';

if ($passenger_id) {
    $update = mysqli_query($db, "UPDATE passengers SET status = 'cancelled' WHERE id = $passenger_id");

    if ($update) {
        $q = mysqli_query($db, "SELECT * FROM passengers WHERE id = $passenger_id");
        $data = mysqli_fetch_assoc($q);

        $fun = new fun($db);
        $from = $fun->get_station_name($data['from_station_id']);
        $to = $fun->get_station_name($data['to_station_id']);
        $train = $fun->get_train_name($data['train_id']);
        $journeyDate = date("d M Y", strtotime($data['journey_date']));

        $route = $fun->get_route($data['from_station_id'], $data['to_station_id'], $data['train_id']);
        $fare = $route['fare'] ?? 0;
        $refund = number_format($fare, 2);

        // -------- PDF CONTENT --------
        $html = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body {
                    font-family: DejaVu Sans, sans-serif;
                    margin: 0;
                    padding: 30px;
                    background: #f5f7fa;
                }
                .box {
                    max-width: 680px;
                    margin: auto;
                    background: #ffffff;
                    border: 1px solid #dee2e6;
                    border-radius: 8px;
                    padding: 20px;
                    box-sizing: border-box;
                }
                h2 {
                    text-align: center;
                    font-size: 22px;
                    margin-bottom: 20px;
                    color: #343a40;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 14px;
                }
                th, td {
                    text-align: left;
                    padding: 10px;
                    border: 1px solid #dee2e6;
                }
                th {
                    background-color: #f1f3f5;
                }
                .status-cancelled {
                    color: red;
                    font-weight: bold;
                }
                .footer {
                    margin-top: 20px;
                    font-size: 12px;
                    text-align: center;
                    color: #666;
                    border-top: 1px dashed #ccc;
                    padding-top: 10px;
                }
            </style>
        </head>
        <body>
            <div class='box'>
                <h2>IRCTC Ticket Cancellation Receipt</h2>
                <p>Dear <strong>{$data['name']}</strong>, your ticket has been <span class='status-cancelled'>CANCELLED</span>.</p>
                <table>
                    <tr><th>Passenger ID (PNR)</th><td>{$data['id']}</td></tr>
                    <tr><th>Name</th><td>{$data['name']}</td></tr>
                    <tr><th>From</th><td>{$from}</td></tr>
                    <tr><th>To</th><td>{$to}</td></tr>
                    <tr><th>Train</th><td>{$train}</td></tr>
                    <tr><th>Journey Date</th><td>{$journeyDate}</td></tr>
                    <tr><th>Fare</th><td>₹{$refund}</td></tr>
                    <tr><th>Refund Amount</th><td><strong>₹{$refund}</strong></td></tr>
                    <tr><th>Status</th><td class='status-cancelled'>CANCELLED</td></tr>
                </table>
                <div class='footer'>
                    Your refund will be processed to your original payment method within <b>5–7 business days</b>.<br>
                    Thank you for using IRCTC Railway Services.
                </div>
            </div>
        </body>
        </html>";

        // -------- Generate PDF --------
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A5', 'landscape');
        $dompdf->render();

        $pdfDir = __DIR__ . '/cancelled_tickets';
        if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);
        $pdfPath = "$pdfDir/cancelled_ticket_$passenger_id.pdf";
        file_put_contents($pdfPath, $dompdf->output());

        // -------- Send Email --------
        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'adityathakre.cse24@sbjit.edu.in';
            $mail->Password = 'phabotnrmczdclqo';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('adityathakre.cse24@sbjit.edu.in', 'IRCTC Railway');
            $mail->addAddress($email, $name);
            $mail->addAttachment($pdfPath);

            $mail->isHTML(true);
            $mail->Subject = '❌ Ticket Cancelled - IRCTC';
            $mail->Body = "
                <div style='font-family:Segoe UI,sans-serif;padding:20px;background:#ffffff;'>
                    <h2 style='color:#dc3545;'>❌ Ticket Cancelled</h2>
                    <p>Dear <b>$name</b>,</p>
                    <p>Your ticket with PNR <b>$passenger_id</b> has been <span style='color:red;'>cancelled</span>.</p>
                    <p><b>From:</b> $from<br><b>To:</b> $to<br><b>Journey Date:</b> $journeyDate</p>
                    <p><b>Refund Amount:</b> ₹$refund</p>
                    <p>Your refund will be processed to your original payment method within <b>5–7 business days</b>.</p>
                    <br><p>Thank you,<br><b>IRCTC Railway</b></p>
                </div>";

            $mail->send();
        } catch (Exception $e) {
            echo "Mail Error: " . $mail->ErrorInfo;
        }

        echo "<script>alert('✅ Ticket cancelled and email sent.'); window.location.href='view_passenger.php?id=$passenger_id';</script>";
    } else {
        echo "<h3 style='color:red;'>❌ Ticket cancellation failed. Try again.</h3>";
    }
} else {
    echo "Invalid passenger ID.";
}
?>
