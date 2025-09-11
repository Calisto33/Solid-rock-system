<?php
// Simple test script to verify Gmail connection
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Enable debug output
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';

    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ronaldbvirinyangwe@gmail.com';
    $mail->Password   = 'bkepemqcdyxxedlr';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Additional options
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Recipients
    $mail->setFrom('ronaldbvirinyangwe@gmail.com', 'Test Sender');
    $mail->addAddress('ronaldbvirinyangwe@gmail.com', 'Test Recipient'); // Send to yourself for testing

    // Content
    $mail->isHTML(false);
    $mail->Subject = 'Test Email Connection';
    $mail->Body    = 'This is a test email to verify the connection is working.';

    $mail->send();
    echo 'Message has been sent successfully!';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}<br>";
    echo "Exception: {$e->getMessage()}<br>";
}
?>