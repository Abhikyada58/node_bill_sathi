<?php
/**
 * Application Entry Router
 */

session_start();

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // User is logged in, redirect to dashboard
    header('Location: dashboard.php');
} else {
    // User is not logged in, redirect to login page
    header('Location: login.php');
}
exit;
