<?php
/**
 * Get Dashboard Statistics
 * GET → JSON with total, active, expired, suspended counts (excludes admins)
 * Also auto-marks accounts as 'expired' where valid_until has passed.
 */

require_once __DIR__ . '/admin_session_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();

    // Auto-expire accounts where valid_until has passed and status is still 'active'
    $pdo->exec("
        UPDATE users
           SET status = 'expired'
         WHERE valid_until IS NOT NULL
           AND valid_until < CURDATE()
           AND status = 'active'
           AND role != 'admin'
    ");

    $stmt = $pdo->query("
        SELECT
            COUNT(*)                                               AS total,
            SUM(CASE WHEN status = 'active'    THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN status = 'expired'   THEN 1 ELSE 0 END) AS expired,
            SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) AS suspended
        FROM users
        WHERE role != 'admin'
    ");

    $row = $stmt->fetch();

    echo json_encode([
        'success'   => true,
        'total'     => (int)($row['total']     ?? 0),
        'active'    => (int)($row['active']    ?? 0),
        'expired'   => (int)($row['expired']   ?? 0),
        'suspended' => (int)($row['suspended'] ?? 0),
    ]);

} catch (PDOException $e) {
    error_log('Get Stats Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch statistics.']);
}
