<?php
/**
 * Session Check Middleware
 * Include this file at the top of any protected page.
 * Redirects to login if no active session exists.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Clear any stale session data
    session_unset();
    session_destroy();
    
    header('Location: login.php');
    exit;
}

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
