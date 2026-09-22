<?php
/**
 * Get Users List
 * GET ?search=&filter=all|active|expired|suspended&page=1
 * Returns paginated user records (excludes admin accounts).
 */

require_once __DIR__ . '/admin_session_check.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'all');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$allowedFilters = ['all', 'active', 'expired', 'suspended'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

try {
    $pdo = getDBConnection();

    // Auto-expire overdue accounts before fetching
    $pdo->exec("
        UPDATE users
           SET status = 'expired'
         WHERE valid_until IS NOT NULL
           AND valid_until < CURDATE()
           AND status = 'active'
           AND role != 'admin'
    ");

    // Build WHERE clause dynamically
    $conditions = ["role != 'admin'"];
    $params     = [];

    if (!empty($search)) {
        $conditions[] = "(full_name LIKE :search OR email LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }

    if ($filter !== 'all') {
        $conditions[] = "status = :filter";
        $params['filter'] = $filter;
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    // Total count for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM users $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetch()['cnt'];

    // Fetch page of users
    $dataStmt = $pdo->prepare("
        SELECT id, full_name, email, role, status, valid_until,
               DATE_FORMAT(created_at, '%Y-%m-%d') AS created_at
          FROM users
         $where
         ORDER BY created_at DESC
         LIMIT $limit OFFSET $offset
    ");
    $dataStmt->execute($params);
    $users = $dataStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'users'   => $users,
        'total'   => $total,
        'page'    => $page,
        'pages'   => max(1, (int)ceil($total / $limit)),
    ]);

} catch (PDOException $e) {
    error_log('Get Users Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch users.']);
}
