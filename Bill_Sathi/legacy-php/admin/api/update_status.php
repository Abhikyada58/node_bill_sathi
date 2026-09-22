<?php
/**
 * Update User Status
 * POST: user_id, status (active | expired | suspended)
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
$status = trim($_POST['status']  ?? '');

$allowedStatuses = ['active', 'expired', 'suspended'];

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

if (!in_array($status, $allowedStatuses, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value. Must be: active, expired, or suspended.']);
    exit;
}

try {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    if (($user['role'] ?? '') === 'admin') {
        echo json_encode(['success' => false, 'message' => 'Cannot change status of admin accounts.']);
        exit;
    }

    $upd = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id AND role != 'admin'");
    $upd->execute(['status' => $status, 'id' => $userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Status updated to ' . ucfirst($status) . '.',
        'status'  => $status,
    ]);

} catch (PDOException $e) {
    error_log('Update Status Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}
