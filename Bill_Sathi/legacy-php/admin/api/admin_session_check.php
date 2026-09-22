<?php
/**
 * Admin API Session Guard
 * ─────────────────────────────────────────
 * Include at the top of every protected admin API file.
 * Returns HTTP 401 JSON if the request is not from an authenticated admin.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode([
        'success'  => false,
        'message'  => 'Unauthorized. Please log in as admin.',
        'redirect' => '../login.php',
    ]);
    exit;
}

// Periodically regenerate session ID (every 30 minutes)
if (!isset($_SESSION['admin_last_regen'])) {
    $_SESSION['admin_last_regen'] = time();
} elseif (time() - $_SESSION['admin_last_regen'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['admin_last_regen'] = time();
}
