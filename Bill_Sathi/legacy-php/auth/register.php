<?php
/**
 * Registration Handler
 * Processes registration form submissions via AJAX
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
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate input
$errors = [];

if (empty($fullName)) {
    $errors[] = 'Full name is required.';
} elseif (strlen($fullName) < 2 || strlen($fullName) > 100) {
    $errors[] = 'Name must be between 2 and 100 characters.';
}

if (empty($email)) {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if (empty($password)) {
    $errors[] = 'Password is required.';
} elseif (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters long.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
        exit;
    }
    
    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    
    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (:name, :email, :password)");
    $stmt->execute([
        'name' => $fullName,
        'email' => $email,
        'password' => $passwordHash
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully! Redirecting to login...',
        'redirect' => 'login.php'
    ]);
    
} catch (PDOException $e) {
    error_log("Registration Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
