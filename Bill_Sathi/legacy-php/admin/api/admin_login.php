<?php
/**
 * Admin Login Handler
 * POST: email, password → JSON response
 */

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$email    = trim($_POST['email']    ?? '');
$password =      $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, role FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No account found with this email address.']);
        exit;
    }

    if (($user['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Access denied. This account does not have admin privileges.']);
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Please try again.']);
        exit;
    }

    // Create admin session
    session_regenerate_id(true);
    $_SESSION['is_admin']        = true;
    $_SESSION['admin_id']        = (int)$user['id'];
    $_SESSION['admin_name']      = $user['full_name'];
    $_SESSION['admin_email']     = $user['email'];
    $_SESSION['admin_login_time']= time();
    $_SESSION['admin_last_regen']= time();

    echo json_encode([
        'success'  => true,
        'message'  => 'Welcome back, ' . $user['full_name'] . '!',
        'redirect' => 'dashboard.php',
    ]);

} catch (PDOException $e) {
    error_log('Admin Login Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
}
