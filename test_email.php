<?php
require 'vendor/autoload.php';
require 'email_config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<h2>SMTP Email Test</h2>";

// Test SMTP connection
$mail = new PHPMailer(true);

try {
    // Configure SMTP using centralized config
    configureSMTP($mail);
    
    // Enable debug output for testing
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';

    // Test email content
    $mail->addAddress(SMTP_USERNAME, 'Test User'); // Send to self for testing
    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test - ' . date('Y-m-d H:i:s');
    $mail->Body = '
        <h3>SMTP Configuration Test</h3>
        <p>This is a test email to verify SMTP authentication is working correctly.</p>
        <p><strong>Test Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><strong>Status:</strong> ✅ SMTP Authentication Successful</p>
    ';

    $mail->send();
    echo "<div style='color:green; background:#d4edda; padding:15px; border:1px solid #c3e6cb; border-radius:5px; margin:20px 0;'>";
    echo "<h3>✅ SUCCESS!</h3>";
    echo "<p>SMTP authentication is working correctly.</p>";
    echo "<p>Test email sent successfully to: adityathakre.cse24@sbjit.edu.in</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color:red; background:#f8d7da; padding:15px; border:1px solid #f5c6cb; border-radius:5px; margin:20px 0;'>";
    echo "<h3>❌ FAILED!</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($mail->ErrorInfo) . "</p>";
    echo "<p><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Configuration Summary:</h3>";
echo "<ul>";
echo "<li><strong>SMTP Host:</strong> " . SMTP_HOST . "</li>";
echo "<li><strong>Port:</strong> " . SMTP_PORT . "</li>";
echo "<li><strong>Security:</strong> STARTTLS</li>";
echo "<li><strong>Username:</strong> " . SMTP_USERNAME . "</li>";
echo "<li><strong>App Password:</strong> " . (SMTP_PASSWORD === 'YOUR_NEW_APP_PASSWORD_HERE' ? '❌ NOT CONFIGURED' : '✅ CONFIGURED') . "</li>";
echo "</ul>";

if (SMTP_PASSWORD === 'YOUR_NEW_APP_PASSWORD_HERE') {
    echo "<div style='color:red; background:#f8d7da; padding:15px; border:1px solid #f5c6cb; border-radius:5px; margin:20px 0;'>";
    echo "<h4>⚠️ Configuration Required</h4>";
    echo "<p>Please update the app password in <code>email_config.php</code> file.</p>";
    echo "<p>Follow the instructions in the file to generate a new Gmail app password.</p>";
    echo "</div>";
}

echo "<p><a href='add_passenger.php' style='background:#0d6efd; color:white; padding:10px 15px; text-decoration:none; border-radius:5px;'>← Back to Add Passenger</a></p>";
?>