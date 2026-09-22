<?php
/**
 * Set Account Validity
 * POST: user_id + either days (7|30|90|180|365) or custom_date (YYYY-MM-DD)
 * Sets valid_until and re-activates expired accounts automatically.
 */

require_once __DIR__ . '/admin_session_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$userId     = (int)($_POST['user_id']     ?? 0);
$days       = (int)($_POST['days']        ?? 0);
$customDate = trim($_POST['custom_date']  ?? '');

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

$allowedDays = [7, 30, 90, 180, 365, 730];
$validUntil  = null;

if (!empty($customDate)) {
    // Validate and parse custom date
    $dt = DateTime::createFromFormat('Y-m-d', $customDate);
    if (!$dt || $dt->format('Y-m-d') !== $customDate) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.']);
        exit;
    }
    $today = new DateTime('today');
    if ($dt <= $today) {
        echo json_encode(['success' => false, 'message' => 'Expiry date must be in the future.']);
        exit;
    }
    $validUntil = $customDate;

} elseif ($days > 0) {
    if (!in_array($days, $allowedDays, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid duration. Allowed: 7, 30, 90, 180, 365, 730 days.']);
        exit;
    }
    $dt = new DateTime('today');
    $dt->modify("+{$days} days");
    $validUntil = $dt->format('Y-m-d');

} else {
    echo json_encode(['success' => false, 'message' => 'Please select a duration or enter a custom expiry date.']);
    exit;
}

try {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    if (($user['role'] ?? '') === 'admin') {
        echo json_encode(['success' => false, 'message' => 'Cannot set validity for admin accounts.']);
        exit;
    }

    // Set valid_until and restore 'active' status if currently expired
    $upd = $pdo->prepare("
        UPDATE users
           SET valid_until = :valid_until,
               status = CASE WHEN status = 'expired' THEN 'active' ELSE status END
         WHERE id = :id
           AND role != 'admin'
    ");
    $upd->execute(['valid_until' => $validUntil, 'id' => $userId]);

    echo json_encode([
        'success'     => true,
        'message'     => "Account validity for \"{$user['full_name']}\" set to {$validUntil}.",
        'valid_until' => $validUntil,
    ]);

} catch (PDOException $e) {
    error_log('Set Validity Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to set validity. Please try again.']);
}
