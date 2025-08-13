<?php
/**
 * Email Configuration File
 * Update this file with your Gmail app password
 */

// Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'adityathakre.cse24@sbjit.edu.in');
define('SMTP_PASSWORD', 'lrlv bzge csqg ryse'); // Gmail app password
define('SMTP_FROM_NAME', 'IRCTC Railway');

/**
 * Instructions to generate Gmail App Password:
 * 
 * 1. Go to your Google Account: https://myaccount.google.com/
 * 2. Click on "Security" in the left sidebar
 * 3. Under "Signing in to Google", click "2-Step Verification"
 * 4. Scroll down and click "App passwords"
 * 5. Select "Mail" from the dropdown
 * 6. Click "Generate"
 * 7. Copy the 16-character password (ignore spaces)
 * 8. Replace 'YOUR_NEW_APP_PASSWORD_HERE' above with this password
 * 
 * Note: You must have 2-Factor Authentication enabled on your Gmail account
 * to generate app passwords.
 */

/**
 * Function to configure PHPMailer with standard settings
 */
function configureSMTP($mail) {
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    
    // Additional SMTP settings for better reliability
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Set default sender
    $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
    
    return $mail;
}
?>