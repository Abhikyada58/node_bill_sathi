<?php
/**
 * Delete User Account
 * POST: user_id → Permanently deletes user and all related data (CASCADE).
 */

require_once __DIR__ . '/admin_session_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$userId = (int)($_POST['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

// Prevent self-deletion
if ($userId === (int)$_SESSION['admin_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own admin account.']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Fetch user to confirm existence and check role
    $stmt = $pdo->prepare("SELECT id, full_name, email, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    if (($user['role'] ?? '') === 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin accounts cannot be deleted via this panel.']);
        exit;
    }

    // Delete — FK CASCADE will remove related records
    $del = $pdo->prepare("DELETE FROM users WHERE id = :id AND role != 'admin'");
    $del->execute(['id' => $userId]);

    if ($del->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'User could not be deleted.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => "User \"{$user['full_name']}\" ({$user['email']}) has been permanently deleted.",
    ]);

} catch (PDOException $e) {
    error_log('Delete User Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete user. Please try again.']);
}
