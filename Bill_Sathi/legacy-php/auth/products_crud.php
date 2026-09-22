<?php
/**
 * Products CRUD Handler
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
        $category = trim($_GET['category'] ?? '');
        
        $where = ["user_id = :user_id"];
        $params = ['user_id' => $_SESSION['user_id']];

        if (!empty($search)) {
            $where[] = "(name LIKE :search OR category LIKE :search)";
            $params['search'] = "%$search%";
        }

        if (!empty($category)) {
            $where[] = "category = :category";
            $params['category'] = $category;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Total Count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get Rows
        $sql = "SELECT * FROM products $whereClause ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->execute();
        $products = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $products,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        exit;
    }

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $price = (float)($_POST['price'] ?? 0); // rate
        $stock = (int)($_POST['stock_quantity'] ?? 100);
        $gstPercent = (float)($_POST['gst_percent'] ?? 0.00);
        $itemCode = trim($_POST['item_code'] ?? '');
        $hsnCode = trim($_POST['hsn_code'] ?? '');
        $unit = trim($_POST['unit'] ?? 'Pcs');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Product Name is a required field.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO products (user_id, name, category, price, gst_percent, stock_quantity, item_code, hsn_code, unit, description) VALUES (:user_id, :name, :category, :price, :gst_percent, :stock, :item_code, :hsn_code, :unit, :description)");
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'name' => $name,
            'category' => $category,
            'price' => $price,
            'gst_percent' => $gstPercent,
            'stock' => $stock,
            'item_code' => !empty($itemCode) ? $itemCode : null,
            'hsn_code' => !empty($hsnCode) ? $hsnCode : null,
            'unit' => $unit,
            'description' => !empty($description) ? $description : null
        ]);

        echo json_encode(['success' => true, 'message' => 'Product added successfully!']);
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $price = (float)($_POST['price'] ?? 0); // rate
        $stock = (int)($_POST['stock_quantity'] ?? 100);
        $gstPercent = (float)($_POST['gst_percent'] ?? 0.00);
        $itemCode = trim($_POST['item_code'] ?? '');
        $hsnCode = trim($_POST['hsn_code'] ?? '');
        $unit = trim($_POST['unit'] ?? 'Pcs');
        $description = trim($_POST['description'] ?? '');

        if ($id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE products SET name = :name, category = :category, price = :price, gst_percent = :gst_percent, stock_quantity = :stock, item_code = :item_code, hsn_code = :hsn_code, unit = :unit, description = :description WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            'name' => $name,
            'category' => $category,
            'price' => $price,
            'gst_percent' => $gstPercent,
            'stock' => $stock,
            'item_code' => !empty($itemCode) ? $itemCode : null,
            'hsn_code' => !empty($hsnCode) ? $hsnCode : null,
            'unit' => $unit,
            'description' => !empty($description) ? $description : null,
            'id' => $id,
            'user_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Product updated successfully!']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $id, 'user_id' => $_SESSION['user_id']]);

        echo json_encode(['success' => true, 'message' => 'Product deleted successfully!']);
        exit;
    }

} catch (PDOException $e) {
    error_log("Products CRUD Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database operation error.']);
}
