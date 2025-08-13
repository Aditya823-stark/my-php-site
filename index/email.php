<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Create PHPMailer instance
$mail = new PHPMailer(true);

try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp-relay.brevo.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ajinkyathakre993@gmail.com'; // ✅ Your verified sender email
    $mail->Password   = 'Wp23SxyRQYL6BbrP';           // ✅ Brevo SMTP key
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Sender and recipient
    $mail->setFrom('ajinkyathakre993@gmail.com', 'Ajinkya');
    $mail->addAddress('ajinkyathakre993@gmail.com', 'Ajinkya'); // Send to self for test

    // Email content
    $mail->isHTML(true);
    $mail->Subject = '✅ Brevo SMTP Test';
    $mail->Body    = '<strong>This is a test email sent via Brevo SMTP using PHPMailer.</strong>';

    $mail->send();
    echo "✅ Email sent successfully!";
} catch (Exception $e) {
    echo "❌ Email failed: {$mail->ErrorInfo}";
}
