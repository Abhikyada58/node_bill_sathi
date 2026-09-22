<?php
require __DIR__ . '/config/env.php';
require __DIR__ . '/vendor/phpmailer/Exception.php';
require __DIR__ . '/vendor/phpmailer/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = getenv('MAIL_HOST');
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('MAIL_USERNAME');
    $mail->Password   = getenv('MAIL_PASSWORD');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->setFrom(getenv('MAIL_USERNAME'), 'Finsnce ERP');
    $mail->addAddress(getenv('MAIL_USERNAME'), 'Test');
    $mail->isHTML(true);
    $mail->Subject = 'Test Email - Finsnce ERP';
    $mail->Body    = '<b>SMTP Test: Email is working correctly!</b>';
    $mail->send();
    echo "SUCCESS: Email sent!" . PHP_EOL;
} catch (Exception $e) {
    echo "FAILED: " . $mail->ErrorInfo . PHP_EOL;
}
