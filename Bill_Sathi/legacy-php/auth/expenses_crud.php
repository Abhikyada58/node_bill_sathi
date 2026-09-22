<?php
/**
 * Expense Tracker CRUD Handler with Transaction Atomicity
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
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $supplierId = trim($_GET['supplier_id'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $category = trim($_GET['category'] ?? '');

        $where = ["e.user_id = :user_id"];
        $params = ['user_id' => $_SESSION['user_id']];

        if (!empty($search)) {
            $where[] = "(e.category LIKE :search OR s.name LIKE :search OR e.notes LIKE :search)";
            $params['search'] = "%$search%";
        }
        if (!empty($dateFrom)) {
            $where[] = "e.expense_date >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where[] = "e.expense_date <= :date_to";
            $params['date_to'] = $dateTo;
        }
        if (!empty($supplierId)) {
            $where[] = "e.supplier_id = :supplier_id";
            $params['supplier_id'] = $supplierId;
        }
        if (!empty($status)) {
            $where[] = "e.status = :status";
            $params['status'] = $status;
        }
        if (!empty($category)) {
            $where[] = "e.category = :category";
            $params['category'] = $category;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM expenses e LEFT JOIN parties s ON e.supplier_id = s.id $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get rows
        $sql = "SELECT e.*, s.name as supplier_name, s.address as address
                FROM expenses e 
                LEFT JOIN parties s ON e.supplier_id = s.id 
                $whereClause 
                ORDER BY e.expense_date DESC, e.id DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->execute();
        $expenses = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $expenses,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        exit;
    }

    if ($action === 'create') {
        $expenseDate = $_POST['expense_date'] ?? date('Y-m-d');
        $category = trim($_POST['category'] ?? '');
        $supplierId = $_POST['supplier_id'] !== '' ? (int)$_POST['supplier_id'] : null;
        $amount = (float)($_POST['amount'] ?? 0);
        $paidAmount = (float)($_POST['paid_amount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        // Items JSON for itemized grid
        $itemsJSON = $_POST['items'] ?? '[]';
        $items = json_decode($itemsJSON, true);

        if (empty($category) || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please provide Expense Category and Amount.']);
            exit;
        }

        // Determine status
        $status = 'UNPAID';
        if ($paidAmount >= $amount) {
            $status = 'PAID';
        } else if ($paidAmount > 0) {
            $status = 'PARTIAL';
        }

        $pdo->beginTransaction();

        // Insert expense
        $ins = $pdo->prepare("INSERT INTO expenses (user_id, expense_date, category, supplier_id, amount, paid_amount, status, notes) 
                              VALUES (:user_id, :expense_date, :category, :supplier_id, :amount, :paid_amount, :status, :notes)");
        
        $ins->execute([
            'user_id' => $_SESSION['user_id'],
            'expense_date' => $expenseDate,
            'category' => $category,
            'supplier_id' => $supplierId,
            'amount' => $amount,
            'paid_amount' => $paidAmount,
            'status' => $status,
            'notes' => !empty($notes) ? $notes : null
        ]);

        $expenseId = $pdo->lastInsertId();

        // Insert items
        $insItem = $pdo->prepare("INSERT INTO expense_items (expense_id, description, quantity, rate, amount) 
                                  VALUES (:expense_id, :description, :quantity, :rate, :amount)");
        
        if (!empty($items)) {
            foreach ($items as $item) {
                $desc = trim($item['description'] ?? '');
                $qty = (float)($item['quantity'] ?? 1);
                $rate = (float)($item['rate'] ?? 0);
                $amt = (float)($item['amount'] ?? 0);

                if (empty($desc) || $qty <= 0) continue;

                $insItem->execute([
                    'expense_id' => $expenseId,
                    'description' => $desc,
                    'quantity' => $qty,
                    'rate' => $rate,
                    'amount' => $amt
                ]);
            }
        } else {
            // Default to single item matching the category
            $insItem->execute([
                'expense_id' => $expenseId,
                'description' => $category,
                'quantity' => 1,
                'rate' => $amount,
                'amount' => $amount
            ]);
        }

        // Add transaction ledger record
        $supplierName = $supplierId ? getSupplierName($pdo, $supplierId) : 'Generic';
        $desc = "Expense: " . $category . " (" . $supplierName . ")";
        $ledger = $pdo->prepare("INSERT INTO transactions (user_id, reference_id, type, amount, description, date) VALUES (:user_id, :ref_id, 'Expense', :amount, :desc, :date)");
        $ledger->execute([
            'user_id' => $_SESSION['user_id'],
            'ref_id' => $expenseId,
            'amount' => $amount,
            'desc' => $desc,
            'date' => $expenseDate
        ]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Expense logged successfully!', 'id' => $expenseId]);
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $expenseDate = $_POST['expense_date'] ?? date('Y-m-d');
        $category = trim($_POST['category'] ?? '');
        $supplierId = $_POST['supplier_id'] !== '' ? (int)$_POST['supplier_id'] : null;
        $amount = (float)($_POST['amount'] ?? 0);
        $paidAmount = (float)($_POST['paid_amount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        // Items JSON
        $itemsJSON = $_POST['items'] ?? '[]';
        $items = json_decode($itemsJSON, true);

        if ($id <= 0 || empty($category) || $amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
            exit;
        }

        $status = 'UNPAID';
        if ($paidAmount >= $amount) {
            $status = 'PAID';
        } else if ($paidAmount > 0) {
            $status = 'PARTIAL';
        }

        $pdo->beginTransaction();

        // Update record
        $upd = $pdo->prepare("UPDATE expenses SET expense_date = :expense_date, category = :category, supplier_id = :supplier_id, amount = :amount, paid_amount = :paid_amount, status = :status, notes = :notes WHERE id = :id AND user_id = :user_id");
        $upd->execute([
            'expense_date' => $expenseDate,
            'category' => $category,
            'supplier_id' => $supplierId,
            'amount' => $amount,
            'paid_amount' => $paidAmount,
            'status' => $status,
            'notes' => !empty($notes) ? $notes : null,
            'id' => $id,
            'user_id' => $_SESSION['user_id']
        ]);

        // Clean existing items
        $pdo->prepare("DELETE ei FROM expense_items ei JOIN expenses e ON ei.expense_id = e.id WHERE ei.expense_id = ? AND e.user_id = ?")->execute([$id, $_SESSION['user_id']]);

        // Re-insert items
        $insItem = $pdo->prepare("INSERT INTO expense_items (expense_id, description, quantity, rate, amount) 
                                  VALUES (:expense_id, :description, :quantity, :rate, :amount)");
        if (!empty($items)) {
            foreach ($items as $item) {
                $desc = trim($item['description'] ?? '');
                $qty = (float)($item['quantity'] ?? 1);
                $rate = (float)($item['rate'] ?? 0);
                $amt = (float)($item['amount'] ?? 0);

                if (empty($desc) || $qty <= 0) continue;

                $insItem->execute([
                    'expense_id' => $id,
                    'description' => $desc,
                    'quantity' => $qty,
                    'rate' => $rate,
                    'amount' => $amt
                ]);
            }
        } else {
            $insItem->execute([
                'expense_id' => $id,
                'description' => $category,
                'quantity' => 1,
                'rate' => $amount,
                'amount' => $amount
            ]);
        }

        // Update general transaction log
        $supplierName = $supplierId ? getSupplierName($pdo, $supplierId) : 'Generic';
        $desc = "Expense: " . $category . " (" . $supplierName . ")";
        $updLedger = $pdo->prepare("UPDATE transactions SET amount = :amount, description = :desc, date = :date WHERE reference_id = :ref_id AND type = 'Expense' AND user_id = :user_id");
        $updLedger->execute([
            'amount' => $amount,
            'desc' => $desc,
            'date' => $expenseDate,
            'ref_id' => $id,
            'user_id' => $_SESSION['user_id']
        ]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Expense updated successfully!']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        $pdo->beginTransaction();

        $del = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
        $del->execute([$id, $_SESSION['user_id']]);

        // Clean ledger transactions too
        $pdo->prepare("DELETE FROM transactions WHERE reference_id = ? AND type = 'Expense' AND user_id = ?")->execute([$id, $_SESSION['user_id']]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Expense deleted successfully!']);
        exit;
    }

    if ($action === 'read_items') {
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT ei.* FROM expense_items ei JOIN expenses e ON ei.expense_id = e.id WHERE ei.expense_id = ? AND e.user_id = ? ORDER BY ei.id ASC");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $items = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $items]);
        exit;
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Expenses CRUD Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database operation error.']);
}

function getSupplierName($pdo, $supplierId) {
    $stmt = $pdo->prepare("SELECT name FROM parties WHERE id = ? LIMIT 1");
    $stmt->execute([$supplierId]);
    return $stmt->fetchColumn() ?: 'Supplier';
}
