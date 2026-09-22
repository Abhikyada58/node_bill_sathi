<?php
/**
 * Admin Panel Entry Point
 * Redirects to dashboard if logged in, otherwise to login.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
