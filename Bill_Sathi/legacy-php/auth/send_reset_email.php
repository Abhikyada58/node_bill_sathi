<?php
/**
 * Send Password Reset Email
 * Generates a secure token, stores it, sends email via PHPMailer + Gmail SMTP
 */

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

// Load PHPMailer (manually downloaded to vendor/phpmailer/)
$phpmailerDir = __DIR__ . '/../vendor/phpmailer/';
require_once $phpmailerDir . 'Exception.php';
require_once $phpmailerDir . 'PHPMailer.php';
require_once $phpmailerDir . 'SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Read SMTP settings from .env
$mailHost     = getenv('MAIL_HOST')      ?: 'smtp.gmail.com';
$mailPort     = (int)(getenv('MAIL_PORT') ?: 587);
$mailUser     = getenv('MAIL_USERNAME')  ?: '';
$mailPass     = getenv('MAIL_PASSWORD')  ?: '';
$mailFromName = getenv('MAIL_FROM_NAME') ?: 'Finsnce ERP';

if (empty($mailUser) || empty($mailPass)) {
    echo json_encode(['success' => false, 'message' => 'Email not configured. Please add MAIL_USERNAME and MAIL_PASSWORD to your .env file.']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Ensure password_resets table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'your email not resiter']);
        exit;
    }

    // Remove old tokens for this email
    $pdo->prepare("DELETE FROM password_resets WHERE email = :email")->execute(['email' => $email]);

    // Generate secure token
    $token     = bin2hex(random_bytes(32));
    $expiresAt = gmdate('Y-m-d H:i:s', time() + 1800); // 30 minutes from now in UTC

    $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)")
        ->execute(['email' => $email, 'token' => $token, 'expires_at' => $expiresAt]);

    // Build reset URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $resetUrl = "$protocol://$host/finance/reset_password.php?token=$token";
    $userName = htmlspecialchars($user['full_name']);

    // Build HTML email body
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; max-width: 520px; margin: auto; background: #f8fafc; padding: 30px; border-radius: 16px;'>
        <div style='background: linear-gradient(135deg, #2563eb, #3b82f6); border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 24px;'>
            <h1 style='color: #fff; margin: 0; font-size: 22px; letter-spacing: 1px;'>🔐 Finsnce ERP</h1>
            <p style='color: rgba(255,255,255,0.85); margin: 8px 0 0; font-size: 14px;'>Password Reset Request</p>
        </div>
        <div style='background: #fff; border-radius: 12px; padding: 28px; border: 1px solid #e2e8f0;'>
            <p style='color: #374151; font-size: 15px; margin-top: 0;'>Hello <strong>$userName</strong>,</p>
            <p style='color: #64748b; font-size: 14px; line-height: 1.7;'>
                We received a request to reset the password for your Finsnce ERP account.<br>
                Click the button below to set a new password. This link will <strong>expire in 30 minutes</strong>.
            </p>
            <div style='text-align: center; margin: 28px 0;'>
                <a href='$resetUrl'
                   style='background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; text-decoration: none;
                          padding: 14px 36px; border-radius: 8px; font-size: 15px; font-weight: bold;
                          display: inline-block; box-shadow: 0 4px 15px rgba(37,99,235,0.3);'>
                    Reset My Password
                </a>
            </div>
            <p style='color: #94a3b8; font-size: 13px; text-align: center;'>
                If you did not request this, you can safely ignore this email.<br>Your password will remain unchanged.
            </p>
            <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
            <p style='color: #94a3b8; font-size: 11px; text-align: center; margin: 0;'>
                Or paste this link in your browser:<br>
                <a href='$resetUrl' style='color: #2563eb; word-break: break-all;'>$resetUrl</a>
            </p>
        </div>
        <p style='color: #cbd5e1; font-size: 11px; text-align: center; margin-top: 16px;'>
            © " . date('Y') . " Finsnce ERP · All rights reserved
        </p>
    </div>";

    // Send email via PHPMailer
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = $mailHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $mailUser;
    $mail->Password   = $mailPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $mailPort;

    $mail->setFrom($mailUser, $mailFromName);
    $mail->addAddress($email, $userName);

    $mail->isHTML(true);
    $mail->Subject = 'Reset Your Finsnce ERP Password';
    $mail->Body    = $htmlBody;
    $mail->AltBody = "Reset your password here: $resetUrl  (Expires in 30 minutes)";

    $mail->send();

    echo json_encode(['success' => true]);

} catch (MailException $e) {
    error_log("PHPMailer Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Reset Email Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again.']);
}

