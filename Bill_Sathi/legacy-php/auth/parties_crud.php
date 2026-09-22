<?php
/**
 * Parties CRUD Handler (Unified Customers & Suppliers)
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
        $limit = (int)($_GET['limit'] ?? 100);
        $offset = ($page - 1) * $limit;
        
        $search = trim($_GET['search'] ?? '');
        
        $where = ["p.user_id = :user_id"];
        $params = ['user_id' => $_SESSION['user_id']];

        if (!empty($search)) {
            $where[] = "(p.name LIKE :search OR p.gst_number LIKE :search OR p.pan_number LIKE :search OR p.phone LIKE :search OR p.owner_name LIKE :search)";
            $params['search'] = "%$search%";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Total Count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM parties p $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get Rows with outstanding receivables and payables calculated
        $sql = "SELECT p.*, 
                       (SELECT IFNULL(SUM(grand_total - paid_amount), 0) FROM sales_bills WHERE customer_id = p.id) as receivables,
                       (SELECT IFNULL(SUM(amount), 0) FROM purchases WHERE supplier_id = p.id AND status != 'Paid') as payables
                FROM parties p 
                $whereClause 
                ORDER BY p.id DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->execute();
        $parties = $stmt->fetchAll();

        // Calculate net balance for each party
        foreach ($parties as &$party) {
            $receivables = (float)$party['receivables'];
            $payables = (float)$party['payables'];
            $party['net_balance'] = $receivables - $payables;
        }

        echo json_encode([
            'success' => true,
            'data' => $parties,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        exit;
    }

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $gst = trim($_POST['gst_number'] ?? '');
        $pan = trim($_POST['pan_number'] ?? '');
        $owner = trim($_POST['owner_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $discount = (float)($_POST['discount'] ?? 0.00);
        $due_days = (int)($_POST['due_days'] ?? 45);
        $broker_name = trim($_POST['broker_name'] ?? '');
        $broker_mobile = trim($_POST['broker_mobile'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Party Name is a required field.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO parties (user_id, name, email, phone, address, gst_number, pan_number, owner_name, state, city, pincode, discount, due_days, broker_name, broker_mobile) 
                               VALUES (:user_id, :name, :email, :phone, :address, :gst, :pan, :owner, :state, :city, :pincode, :discount, :due_days, :broker_name, :broker_mobile)");
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'gst' => $gst,
            'pan' => $pan,
            'owner' => $owner,
            'state' => $state,
            'city' => $city,
            'pincode' => $pincode,
            'discount' => $discount,
            'due_days' => $due_days,
            'broker_name' => $broker_name,
            'broker_mobile' => $broker_mobile
        ]);

        echo json_encode(['success' => true, 'message' => 'Party added successfully!', 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $gst = trim($_POST['gst_number'] ?? '');
        $pan = trim($_POST['pan_number'] ?? '');
        $owner = trim($_POST['owner_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $pincode = trim($_POST['pincode'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $discount = (float)($_POST['discount'] ?? 0.00);
        $due_days = (int)($_POST['due_days'] ?? 45);
        $broker_name = trim($_POST['broker_name'] ?? '');
        $broker_mobile = trim($_POST['broker_mobile'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE parties SET name = :name, email = :email, phone = :phone, address = :address, gst_number = :gst, pan_number = :pan, owner_name = :owner, state = :state, city = :city, pincode = :pincode, discount = :discount, due_days = :due_days, broker_name = :broker_name, broker_mobile = :broker_mobile WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'gst' => $gst,
            'pan' => $pan,
            'owner' => $owner,
            'state' => $state,
            'city' => $city,
            'pincode' => $pincode,
            'discount' => $discount,
            'due_days' => $due_days,
            'broker_name' => $broker_name,
            'broker_mobile' => $broker_mobile,
            'id' => $id,
            'user_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Party updated successfully!']);
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

        echo json_encode(['success' => true, 'message' => 'Party deleted successfully!']);
        exit;
    }

} catch (PDOException $e) {
    error_log("Parties CRUD Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database operation error: ' . $e->getMessage()]);
}
