<?php
require 'vendor/autoload.php'; // Composer autoload
use Mpdf\Mpdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_ticket_email($data) {
    $name = $data['name'];
    $email = $data['email'];
    $from = $data['from'];
    $to = $data['to'];
    $date = $data['journey_date'];
    $class = $data['class_type'];
    $fare = $data['fare'];
    $distance = $data['distance'];

    // 1. Generate PDF ticket
    $mpdf = new Mpdf();
    $html = "
        <h2 style='text-align:center;'>Railway E-Ticket</h2>
        <p><strong>Name:</strong> $name</p>
        <p><strong>From:</strong> $from</p>
        <p><strong>To:</strong> $to</p>
        <p><strong>Date:</strong> $date</p>
        <p><strong>Class:</strong> $class</p>
        <p><strong>Fare:</strong> &#8377;$fare</p>
        <p><strong>Distance:</strong> $distance km</p>
    ";
    $pdfPath = __DIR__ . "/ticket_" . time() . ".pdf";
    $mpdf->WriteHTML($html);
    $mpdf->Output($pdfPath, 'F');

    // 2. Send email using PHPMailer
    $mail = new PHPMailer(true);

    try {
        // SMTP config
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'adityathakre.cse24@sbjit.edu.in'; // ✅ Change
        $mail->Password = 'ngzxsqmvaazythjq';    // ✅ Use App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Email details
        $mail->setFrom('adityathakre.cse24@sbjit.edu.in', 'Railway Booking System');
        $mail->addAddress($email, $name);
        $mail->Subject = 'Your Railway E-Ticket';
        $mail->Body = "Dear $name,\n\nYour ticket is attached.\n\nHappy journey!\nRailway Booking System";
        $mail->addAttachment($pdfPath, "E-Ticket.pdf");

        $mail->send();

        // Optionally delete PDF file after sending
        unlink($pdfPath);
        return true;

    } catch (Exception $e) {
        return "Email Error: " . $mail->ErrorInfo;
    }
}
