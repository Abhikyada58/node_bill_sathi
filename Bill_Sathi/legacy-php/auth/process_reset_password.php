<?php
/**
 * Process Password Reset
 * Validates token, updates password, clears token
 */

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$token      = trim($_POST['token'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');
$confirmPw  = trim($_POST['confirm_password'] ?? '');

// Validate inputs
if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reset token.']);
    exit;
}
if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}
if ($newPassword !== $confirmPw) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Verify token exists (compare expiry in PHP to avoid MySQL timezone mismatch)
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token LIMIT 1");
    $stmt->execute(['token' => $token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        echo json_encode(['success' => false, 'message' => 'This reset link is invalid or has already been used.']);
        exit;
    }

    // Check expiry using PHP time (avoids MySQL timezone issues)
    if (strtotime($reset['expires_at'] . ' UTC') < time()) {
        // Clean up expired token
        $pdo->prepare("DELETE FROM password_resets WHERE token = :token")->execute(['token' => $token]);
        echo json_encode(['success' => false, 'message' => 'This reset link has expired. Please request a new one.']);
        exit;
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update the user's password
    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE email = :email");
    $updateStmt->execute([
        'password_hash' => $hashedPassword,
        'email'         => $reset['email']
    ]);

    // Delete the used token (one-time use)
    $pdo->prepare("DELETE FROM password_resets WHERE token = :token")->execute(['token' => $token]);

    echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);

} catch (Exception $e) {
    error_log("Reset Password Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again later.']);
}
