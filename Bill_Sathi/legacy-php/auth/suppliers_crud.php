<?php
/**
 * Suppliers CRUD Handler
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? 'read';

try {
    $pdo = getDBConnection();

    if ($action === 'read') {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $search = trim($_GET['search'] ?? '');
        
        $where = ["user_id = :user_id"];
        $params = ['user_id' => $_SESSION['user_id']];

        if (!empty($search)) {
            $where[] = "(name LIKE :search OR email LIKE :search OR phone LIKE :search OR gst_number LIKE :search)";
            $params['search'] = "%$search%";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Total Count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM parties $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get Rows
        $sql = "SELECT * FROM parties $whereClause ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->execute();
        $suppliers = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $suppliers,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        exit;
    }

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $gst = trim($_POST['gst_number'] ?? '');

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Name is a required field.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO parties (user_id, name, email, phone, address, gst_number) VALUES (:user_id, :name, :email, :phone, :address, :gst)");
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'gst' => $gst
        ]);

        echo json_encode(['success' => true, 'message' => 'Supplier added successfully!']);
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $gst = trim($_POST['gst_number'] ?? '');

        if ($id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE parties SET name = :name, email = :email, phone = :phone, address = :address, gst_number = :gst WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'gst' => $gst,
            'id' => $id,
            'user_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Supplier updated successfully!']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM parties WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $id, 'user_id' => $_SESSION['user_id']]);

        echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully!']);
        exit;
    }

} catch (PDOException $e) {
    error_log("Suppliers CRUD Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database operation error.']);
}
