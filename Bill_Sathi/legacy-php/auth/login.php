<?php
/**
 * Login Handler
 * Processes login form submissions via AJAX
 * Returns JSON responses
 */

session_start();
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Get and sanitize input
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Look up user by email
    $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, avatar_url, status, valid_until, role FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'This email address is not registered. Please create an account first before attempting to log in.']);
        exit;
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }

    // Auto-expire account if valid_until has passed
    if (!empty($user['valid_until']) && $user['status'] === 'active') {
        $expiry = new DateTime($user['valid_until']);
        $today  = new DateTime('today');
        if ($expiry < $today) {
            $pdo->prepare("UPDATE users SET status = 'expired' WHERE id = :id")
                ->execute(['id' => $user['id']]);
            $user['status'] = 'expired';
        }
    }

    // Block login for expired or suspended accounts
    if ($user['status'] === 'expired') {
        echo json_encode([
            'success' => false,
            'message' => 'Your account has expired. Please contact the administrator to renew your access.'
        ]);
        exit;
    }

    if ($user['status'] === 'suspended') {
        echo json_encode([
            'success' => false,
            'message' => 'Your account has been suspended. Please contact the administrator for assistance.'
        ]);
        exit;
    }
    
    // Regenerate session ID to prevent fixation attacks
    session_regenerate_id(true);

    // ── Admin: set admin session and redirect to admin panel ──
    if (($user['role'] ?? '') === 'admin') {
        $_SESSION['is_admin']         = true;
        $_SESSION['admin_id']         = (int)$user['id'];
        $_SESSION['admin_name']       = $user['full_name'];
        $_SESSION['admin_email']      = $user['email'];
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_last_regen'] = time();

        echo json_encode([
            'success'  => true,
            'message'  => 'Welcome, Admin! Redirecting to Admin Panel...',
            'redirect' => 'admin/dashboard.php'
        ]);
        exit;
    }

    // ── Regular user session ──
    $_SESSION['user_id']           = $user['id'];
    $_SESSION['user_name']         = $user['full_name'];
    $_SESSION['user_email']        = $user['email'];
    $_SESSION['user_avatar']       = $user['avatar_url'];
    $_SESSION['last_regeneration'] = time();
    $_SESSION['login_time']        = time();

    echo json_encode([
        'success'  => true,
        'message'  => 'Login successful! Redirecting...',
        'redirect' => 'dashboard.php'
    ]);
    
} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}

