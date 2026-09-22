<?php
/**
 * Purchase Bills CRUD Handler with Transaction Atomicity
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
        // Read filters & parameters
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 100);
        $offset = ($page - 1) * $limit;

        $search = trim($_GET['search'] ?? '');
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $supplierId = trim($_GET['supplier_id'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $billNo = trim($_GET['bill_number'] ?? '');
        $applyGst = isset($_GET['apply_gst']) ? trim($_GET['apply_gst']) : ''; // '1' (With Tax), '0' (Without Tax)
        $sortBy = $_GET['sort_by'] ?? 'bill_date';
        $sortOrder = $_GET['sort_order'] ?? 'DESC';

        // Validate sort columns
        $allowedSort = ['bill_date', 'bill_number', 'amount', 'status'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'bill_date';
        }
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $where = ["p.user_id = :user_id"];
        $params = ['user_id' => $_SESSION['user_id']];

        if (!empty($search)) {
            $where[] = "(p.bill_number LIKE :search OR s.name LIKE :search OR p.remarks LIKE :search)";
            $params['search'] = "%$search%";
        }
        if (!empty($dateFrom)) {
            $where[] = "p.bill_date >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where[] = "p.bill_date <= :date_to";
            $params['date_to'] = $dateTo;
        }
        if (!empty($supplierId)) {
            $where[] = "p.supplier_id = :supplier_id";
            $params['supplier_id'] = $supplierId;
        }
        if (!empty($status)) {
            $where[] = "p.status = :status";
            $params['status'] = $status;
        }
        if (!empty($billNo)) {
            $where[] = "p.bill_number LIKE :bill_no";
            $params['bill_no'] = "%$billNo%";
        }
        if ($applyGst !== '') {
            $where[] = "p.apply_gst = :apply_gst";
            $params['apply_gst'] = (int)$applyGst;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM purchases p JOIN parties s ON p.supplier_id = s.id $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get rows
        $sql = "SELECT p.*, s.name as supplier_name, s.email as email, s.address as address, s.gst_number as supplier_gst,
                       DATEDIFF(p.due_date, CURRENT_DATE()) as due_days_left
                FROM purchases p 
                JOIN parties s ON p.supplier_id = s.id 
                $whereClause 
                ORDER BY p.$sortBy $sortOrder 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->execute();
        $bills = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $bills,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        exit;
    }

    if ($action === 'create') {
        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $billNo = trim($_POST['bill_number'] ?? '');
        $billDate = $_POST['bill_date'] ?? date('Y-m-d');
        $dueDays = (int)($_POST['due_days'] ?? 0);
        $dueDate = $_POST['due_date'] ?? $billDate;
        $applyGst = (int)($_POST['apply_gst'] ?? 0);
        
        $discountPercent = (float)($_POST['discount_percent'] ?? 0);
        $discountAmount = (float)($_POST['discount_amount'] ?? 0);
        $gstPercent = (float)($_POST['gst_percent'] ?? 0);
        $gstAmount = (float)($_POST['gst_amount'] ?? 0);
        $taxableAmount = (float)($_POST['taxable_amount'] ?? 0);
        $grandTotal = (float)($_POST['grand_total'] ?? 0);
        $paidAmount = (float)($_POST['paid_amount'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        // Parse items JSON
        $itemsJSON = $_POST['items'] ?? '[]';
        $items = json_decode($itemsJSON, true);

        if ($supplierId <= 0 || empty($billNo) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Please provide all required fields, including at least one product.']);
            exit;
        }

        // Set status
        $status = 'UNPAID';
        if ($paidAmount >= $grandTotal) {
            $status = 'PAID';
        } else if ($paidAmount > 0) {
            $status = 'PARTIAL';
        }

        $pdo->beginTransaction();

        // Check duplicates
        $check = $pdo->prepare("SELECT id FROM purchases WHERE bill_number = ? AND user_id = ? LIMIT 1");
        $check->execute([$billNo, $_SESSION['user_id']]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'A purchase bill with this number already exists.']);
            $pdo->rollBack();
            exit;
        }

        // Insert purchase
        $ins = $pdo->prepare("INSERT INTO purchases (user_id, supplier_id, bill_number, bill_date, due_days, due_date, apply_gst, discount_percent, discount_amount, gst_percent, gst_amount, taxable_amount, amount, paid_amount, status, remarks) 
                              VALUES (:user_id, :supplier_id, :bill_number, :bill_date, :due_days, :due_date, :apply_gst, :discount_percent, :discount_amount, :gst_percent, :gst_amount, :taxable_amount, :amount, :paid_amount, :status, :remarks)");
        
        $ins->execute([
            'user_id' => $_SESSION['user_id'],
            'supplier_id' => $supplierId,
            'bill_number' => $billNo,
            'bill_date' => $billDate,
            'due_days' => $dueDays,
            'due_date' => $dueDate,
            'apply_gst' => $applyGst,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'gst_percent' => $gstPercent,
            'gst_amount' => $gstAmount,
            'taxable_amount' => $taxableAmount,
            'amount' => $grandTotal,
            'paid_amount' => $paidAmount,
            'status' => $status,
            'remarks' => !empty($remarks) ? $remarks : null
        ]);

        $purchaseId = $pdo->lastInsertId();

        // Insert items
        $insItem = $pdo->prepare("INSERT INTO purchase_bill_items (purchase_bill_id, product_id, product_name, item_code, hsn_code, quantity, unit, rate, amount, gst_percent, gst_amount) 
                                  VALUES (:purchase_bill_id, :product_id, :product_name, :item_code, :hsn_code, :quantity, :unit, :rate, :amount, :gst_percent, :gst_amount)");
        
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $productName = trim($item['product_name'] ?? '');
            $qty = (float)($item['quantity'] ?? 0);
            $rate = (float)($item['rate'] ?? 0);
            $amt = (float)($item['amount'] ?? 0);
            $gstPct = (float)($item['gst_percent'] ?? 0);
            $gstAmt = (float)($item['gst_amount'] ?? 0);

            if ($productId <= 0 || empty($productName) || $qty <= 0) {
                continue; // Skip invalid rows
            }

            $insItem->execute([
                'purchase_bill_id' => $purchaseId,
                'product_id' => $productId,
                'product_name' => $productName,
                'item_code' => !empty($item['item_code']) ? trim($item['item_code']) : null,
                'hsn_code' => !empty($item['hsn_code']) ? trim($item['hsn_code']) : null,
                'quantity' => $qty,
                'unit' => !empty($item['unit']) ? trim($item['unit']) : 'Pcs',
                'rate' => $rate,
                'amount' => $amt,
                'gst_percent' => $gstPct,
                'gst_amount' => $gstAmt
            ]);
        }

        // Add to general transactions ledger
        $desc = "Purchase Bill " . $billNo . " (" . getSupplierName($pdo, $supplierId) . ")";
        $ledger = $pdo->prepare("INSERT INTO transactions (user_id, reference_id, type, amount, description, date) VALUES (:user_id, :ref_id, 'Purchase', :amount, :desc, :date)");
        $ledger->execute([
            'user_id' => $_SESSION['user_id'],
            'ref_id' => $purchaseId,
            'amount' => $grandTotal,
            'desc' => $desc,
            'date' => $billDate
        ]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Purchase bill created successfully!', 'id' => $purchaseId]);
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $billNo = trim($_POST['bill_number'] ?? '');
        $billDate = $_POST['bill_date'] ?? date('Y-m-d');
        $dueDays = (int)($_POST['due_days'] ?? 0);
        $dueDate = $_POST['due_date'] ?? $billDate;
        $applyGst = (int)($_POST['apply_gst'] ?? 0);
        
        $discountPercent = (float)($_POST['discount_percent'] ?? 0);
        $discountAmount = (float)($_POST['discount_amount'] ?? 0);
        $gstPercent = (float)($_POST['gst_percent'] ?? 0);
        $gstAmount = (float)($_POST['gst_amount'] ?? 0);
        $taxableAmount = (float)($_POST['taxable_amount'] ?? 0);
        $grandTotal = (float)($_POST['grand_total'] ?? 0);
        $paidAmount = (float)($_POST['paid_amount'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        // Parse items JSON
        $itemsJSON = $_POST['items'] ?? '[]';
        $items = json_decode($itemsJSON, true);

        if ($id <= 0 || $supplierId <= 0 || empty($billNo) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Please provide all required fields, including at least one product.']);
            exit;
        }

        // Set status
        $status = 'UNPAID';
        if ($paidAmount >= $grandTotal) {
            $status = 'PAID';
        } else if ($paidAmount > 0) {
            $status = 'PARTIAL';
        }

        $pdo->beginTransaction();

        // Check duplicate bill number excluding current ID
        $check = $pdo->prepare("SELECT id FROM purchases WHERE bill_number = ? AND id != ? AND user_id = ? LIMIT 1");
        $check->execute([$billNo, $id, $_SESSION['user_id']]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'A purchase bill with this number already exists.']);
            $pdo->rollBack();
            exit;
        }

        // Update purchase record
        $upd = $pdo->prepare("UPDATE purchases SET supplier_id = :supplier_id, bill_number = :bill_number, bill_date = :bill_date, due_days = :due_days, due_date = :due_date, apply_gst = :apply_gst, discount_percent = :discount_percent, discount_amount = :discount_amount, gst_percent = :gst_percent, gst_amount = :gst_amount, taxable_amount = :taxable_amount, amount = :amount, paid_amount = :paid_amount, status = :status, remarks = :remarks WHERE id = :id AND user_id = :user_id");
        
        $upd->execute([
            'supplier_id' => $supplierId,
            'bill_number' => $billNo,
            'bill_date' => $billDate,
            'due_days' => $dueDays,
            'due_date' => $dueDate,
            'apply_gst' => $applyGst,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'gst_percent' => $gstPercent,
            'gst_amount' => $gstAmount,
            'taxable_amount' => $taxableAmount,
            'amount' => $grandTotal,
            'paid_amount' => $paidAmount,
            'status' => $status,
            'remarks' => !empty($remarks) ? $remarks : null,
            'id' => $id,
            'user_id' => $_SESSION['user_id']
        ]);

        // Clean existing items
        $pdo->prepare("DELETE pbi FROM purchase_bill_items pbi JOIN purchases p ON pbi.purchase_bill_id = p.id WHERE pbi.purchase_bill_id = ? AND p.user_id = ?")->execute([$id, $_SESSION['user_id']]);

        // Re-insert items
        $insItem = $pdo->prepare("INSERT INTO purchase_bill_items (purchase_bill_id, product_id, product_name, item_code, hsn_code, quantity, unit, rate, amount, gst_percent, gst_amount) 
                                  VALUES (:purchase_bill_id, :product_id, :product_name, :item_code, :hsn_code, :quantity, :unit, :rate, :amount, :gst_percent, :gst_amount)");
        
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $productName = trim($item['product_name'] ?? '');
            $qty = (float)($item['quantity'] ?? 0);
            $rate = (float)($item['rate'] ?? 0);
            $amt = (float)($item['amount'] ?? 0);
            $gstPct = (float)($item['gst_percent'] ?? 0);
            $gstAmt = (float)($item['gst_amount'] ?? 0);

            if ($productId <= 0 || empty($productName) || $qty <= 0) {
                continue;
            }

            $insItem->execute([
                'purchase_bill_id' => $id,
                'product_id' => $productId,
                'product_name' => $productName,
                'item_code' => !empty($item['item_code']) ? trim($item['item_code']) : null,
                'hsn_code' => !empty($item['hsn_code']) ? trim($item['hsn_code']) : null,
                'quantity' => $qty,
                'unit' => !empty($item['unit']) ? trim($item['unit']) : 'Pcs',
                'rate' => $rate,
                'amount' => $amt,
                'gst_percent' => $gstPct,
                'gst_amount' => $gstAmt
            ]);
        }

        // Update general transactions ledger
        $desc = "Purchase Bill " . $billNo . " (" . getSupplierName($pdo, $supplierId) . ")";
        $updLedger = $pdo->prepare("UPDATE transactions SET amount = :amount, description = :desc, date = :date WHERE reference_id = :ref_id AND type = 'Purchase' AND user_id = :user_id");
        $updLedger->execute([
            'amount' => $grandTotal,
            'desc' => $desc,
            'date' => $billDate,
            'ref_id' => $id,
            'user_id' => $_SESSION['user_id']
        ]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Purchase bill updated successfully!']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        $pdo->beginTransaction();

        $del = $pdo->prepare("DELETE FROM purchases WHERE id = ? AND user_id = ?");
        $del->execute([$id, $_SESSION['user_id']]);

        // Clean ledger transactions too
        $pdo->prepare("DELETE FROM transactions WHERE reference_id = ? AND type = 'Purchase' AND user_id = ?")->execute([$id, $_SESSION['user_id']]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Purchase bill deleted successfully!']);
        exit;
    }

    if ($action === 'read_items') {
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT pbi.* FROM purchase_bill_items pbi JOIN purchases p ON pbi.purchase_bill_id = p.id WHERE pbi.purchase_bill_id = ? AND p.user_id = ? ORDER BY pbi.id ASC");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $items = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $items]);
        exit;
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Purchase Bills CRUD Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database operation error: ' . $e->getMessage()]);
}

function getSupplierName($pdo, $supplierId) {
    $stmt = $pdo->prepare("SELECT name FROM parties WHERE id = ? LIMIT 1");
    $stmt->execute([$supplierId]);
    return $stmt->fetchColumn() ?: 'Supplier';
}
