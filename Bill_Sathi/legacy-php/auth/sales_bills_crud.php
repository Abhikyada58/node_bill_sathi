<?php
/**
 * Sales Bills CRUD Handler with Transaction Atomicity
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
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;

        $search = trim($_GET['search'] ?? '');
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $customerId = trim($_GET['customer_id'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $billNo = trim($_GET['bill_number'] ?? '');
        $challanNo = trim($_GET['challan_number'] ?? '');
        $sortBy = $_GET['sort_by'] ?? 'bill_date';
        $sortOrder = $_GET['sort_order'] ?? 'DESC';

        // Validate sort columns to prevent SQL injection
        $allowedSort = ['bill_date', 'bill_number', 'grand_total', 'paid_amount', 'status'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'bill_date';
        }
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $where = ["sb.user_id = :user_id"];
        $params = ['user_id' => $_SESSION['user_id']];

        if (!empty($search)) {
            $where[] = "(sb.bill_number LIKE :search OR c.name LIKE :search OR sb.remarks LIKE :search)";
            $params['search'] = "%$search%";
        }
        if (!empty($dateFrom)) {
            $where[] = "sb.bill_date >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where[] = "sb.bill_date <= :date_to";
            $params['date_to'] = $dateTo;
        }
        if (!empty($customerId)) {
            $where[] = "sb.customer_id = :customer_id";
            $params['customer_id'] = $customerId;
        }
        if (!empty($status)) {
            $where[] = "sb.status = :status";
            $params['status'] = $status;
        }
        if (!empty($billNo)) {
            $where[] = "sb.bill_number LIKE :bill_no";
            $params['bill_no'] = "%$billNo%";
        }
        if (!empty($challanNo)) {
            $where[] = "sb.challan_no LIKE :challan_no";
            $params['challan_no'] = "%$challanNo%";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sales_bills sb JOIN parties c ON sb.customer_id = c.id $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get rows
        $sql = "SELECT sb.*, c.name as customer_name, c.email as email, c.address as address, c.gst_number as customer_gst, c.pan_number as customer_pan, c.state as customer_state,
                       DATEDIFF(sb.due_date, CURRENT_DATE()) as due_days_left
                FROM sales_bills sb 
                JOIN parties c ON sb.customer_id = c.id 
                $whereClause 
                ORDER BY sb.$sortBy $sortOrder 
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

    if ($action === 'read_items') {
        $billId = (int)($_GET['sales_bill_id'] ?? 0);

        if ($billId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid sales bill ID.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT sbi.* FROM sales_bill_items sbi JOIN sales_bills sb ON sbi.sales_bill_id = sb.id WHERE sbi.sales_bill_id = :bill_id AND sb.user_id = :user_id ORDER BY sbi.id ASC");
        $stmt->execute(['bill_id' => $billId, 'user_id' => $_SESSION['user_id']]);
        $items = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $items
        ]);
        exit;
    }

    if ($action === 'get_next_bill_number') {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sales_bills WHERE user_id = :user_id");
        $countStmt->execute(['user_id' => $_SESSION['user_id']]);
        $billCount = (int)$countStmt->fetchColumn() + 1;
        echo json_encode(['success' => true, 'next_bill_number' => $billCount]);
        exit;
    }

    if ($action === 'create') {
        // Form post handling
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $billDate = $_POST['bill_date'] ?? date('Y-m-d');
        $dueDays = (int)($_POST['due_days'] ?? 0);
        $applyGst = isset($_POST['apply_gst']) ? 1 : 0;
        
        $challanNo = trim($_POST['challan_no'] ?? '');
        $challanDate = $_POST['challan_date'] ?? null;
        if (empty($challanDate)) $challanDate = null;

        $discountPercent = (float)($_POST['discount_percent'] ?? 0.00);
        $gstPercent = (float)($_POST['gst_percent'] ?? 0.00);
        $remarks = trim($_POST['remarks'] ?? '');

        // TDS/TCS parsing
        $tdsTcsType = trim($_POST['tds_tcs_type'] ?? 'NONE');
        $tdsTcsPercent = (float)($_POST['tds_tcs_percent'] ?? 0.00);
        $tdsTcsAmount = (float)($_POST['tds_tcs_amount'] ?? 0.00);

        // Products rows parsing (expected arrays)
        $productIds = $_POST['product_ids'] ?? [];
        $productNames = $_POST['product_names'] ?? [];
        $itemCodes = $_POST['item_codes'] ?? [];
        $hsnCodes = $_POST['hsn_codes'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        $units = $_POST['units'] ?? [];
        $rates = $_POST['rates'] ?? [];

        // Validations
        if ($customerId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select a customer (Party).']);
            exit;
        }

        if (empty($productIds) || count($productIds) === 0) {
            echo json_encode(['success' => false, 'message' => 'At least one product item line is required.']);
            exit;
        }

        // Start Transaction block
        $pdo->beginTransaction();

        // Generate Invoice Bill Number
        $billNumberInput = trim($_POST['bill_number'] ?? '');
        if (!empty($billNumberInput)) {
            $billNumber = $billNumberInput;
            
            // Check if this custom bill number already exists to prevent duplicate entry errors
            $checkStmt = $pdo->prepare("SELECT id FROM sales_bills WHERE bill_number = :bill_num AND user_id = :user_id LIMIT 1");
            $checkStmt->execute(['bill_num' => $billNumber, 'user_id' => $_SESSION['user_id']]);
            if ($checkStmt->fetch()) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => "Invoice number $billNumber already exists."]);
                exit;
            }
        } else {
            $year = date('Y', strtotime($billDate));
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sales_bills WHERE user_id = :user_id");
            $countStmt->execute(['user_id' => $_SESSION['user_id']]);
            $billCount = (int)$countStmt->fetchColumn() + 1;
            $billNumber = "BIL-$year-" . str_pad($billCount, 3, '0', STR_PAD_LEFT);
        }

        // Due date calculation
        $dueDate = date('Y-m-d', strtotime($billDate . " + $dueDays days"));

        // Items calculations and insertions
        $grossAmount = 0.00;
        $totalQuantity = 0.00;
        $itemInsertions = [];

        for ($i = 0; $i < count($productIds); $i++) {
            $pId = (int)$productIds[$i];
            $qty = (float)$quantities[$i];
            $rate = (float)$rates[$i];
            $pName = trim($productNames[$i]);
            $code = trim($itemCodes[$i] ?? '');
            $hsn = trim($hsnCodes[$i] ?? '');
            $unit = trim($units[$i] ?? 'Pcs');

            if ($qty <= 0 || $rate <= 0) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Quantity and Rate must be greater than zero for all product rows.']);
                exit;
            }

            $amount = $qty * $rate;
            $grossAmount += $amount;
            $totalQuantity += $qty;

            $itemInsertions[] = [
                'product_id' => $pId,
                'product_name' => $pName,
                'item_code' => $code,
                'hsn_code' => $hsn,
                'quantity' => $qty,
                'unit' => $unit,
                'rate' => $rate,
                'amount' => $amount
            ];
        }

        // Apply discount calculations
        $discountAmount = ($grossAmount * $discountPercent) / 100;
        $taxableAmount = $grossAmount - $discountAmount;

        // Apply tax calculations
        $gstAmount = 0.00;
        if ($applyGst) {
            $gstAmount = ($taxableAmount * $gstPercent) / 100;
        }

        $grandTotal = $taxableAmount + $gstAmount;
        if ($tdsTcsType !== 'NONE') {
            $grandTotal += $tdsTcsAmount;
        }

        // Create Sales Bill header
        $billSQL = "INSERT INTO sales_bills (user_id, customer_id, bill_number, bill_date, due_days, due_date, challan_no, challan_date, apply_gst, discount_percent, discount_amount, gst_percent, gst_amount, taxable_amount, grand_total, paid_amount, status, remarks, tds_tcs_type, tds_tcs_percent, tds_tcs_amount) 
                    VALUES (:user_id, :customer_id, :bill_number, :bill_date, :due_days, :due_date, :challan_no, :challan_date, :apply_gst, :discount_percent, :discount_amount, :gst_percent, :gst_amount, :taxable_amount, :grand_total, 0.00, 'UNPAID', :remarks, :tds_tcs_type, :tds_tcs_percent, :tds_tcs_amount)";
        
        $billStmt = $pdo->prepare($billSQL);
        $billStmt->execute([
            'user_id' => $_SESSION['user_id'],
            'customer_id' => $customerId,
            'bill_number' => $billNumber,
            'bill_date' => $billDate,
            'due_days' => $dueDays,
            'due_date' => $dueDate,
            'challan_no' => $challanNo,
            'challan_date' => $challanDate,
            'apply_gst' => $applyGst,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'gst_percent' => $gstPercent,
            'gst_amount' => $gstAmount,
            'taxable_amount' => $taxableAmount,
            'grand_total' => $grandTotal,
            'remarks' => $remarks,
            'tds_tcs_type' => $tdsTcsType,
            'tds_tcs_percent' => $tdsTcsPercent,
            'tds_tcs_amount' => $tdsTcsAmount
        ]);

        $salesBillId = $pdo->lastInsertId();

        // Insert Item lines
        $itemSQL = "INSERT INTO sales_bill_items (sales_bill_id, product_id, product_name, item_code, hsn_code, quantity, unit, rate, amount) 
                    VALUES (:sales_bill_id, :product_id, :product_name, :item_code, :hsn_code, :quantity, :unit, :rate, :amount)";
        $itemStmt = $pdo->prepare($itemSQL);

        foreach ($itemInsertions as $item) {
            $item['sales_bill_id'] = $salesBillId;
            $itemStmt->execute($item);

            // Deduct stock quantity in catalog
            $stockSQL = "UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :p_id AND user_id = :user_id";
            $pdo->prepare($stockSQL)->execute(['qty' => $item['quantity'], 'p_id' => $item['product_id'], 'user_id' => $_SESSION['user_id']]);
        }

        // Record entry into general ledger transactions table
        $txnSQL = "INSERT INTO transactions (user_id, reference_id, type, amount, description, date) 
                   VALUES (:user_id, :ref_id, 'Sale', :amount, :desc, :date)";
        $pdo->prepare($txnSQL)->execute([
            'user_id' => $_SESSION['user_id'],
            'ref_id' => $salesBillId,
            'amount' => $grandTotal,
            'desc' => "Sales Bill Invoice $billNumber generated",
            'date' => $billDate
        ]);

        // Audit Logging
        $auditSQL = "INSERT INTO audit_logs (user_id, action, details) VALUES (:user_id, 'Sales Bill Created', :details)";
        $pdo->prepare($auditSQL)->execute([
            'user_id' => $_SESSION['user_id'],
            'details' => "Sales Bill invoice $billNumber recorded for customer $customerId. Total amount: $grandTotal."
        ]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Sales Bill $billNumber created successfully!", 'bill_id' => $salesBillId]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        $pdo->beginTransaction();

        // Fetch bill details for inventory rollback and audit log
        $billStmt = $pdo->prepare("SELECT bill_number, customer_id FROM sales_bills WHERE id = :id AND user_id = :user_id");
        $billStmt->execute(['id' => $id, 'user_id' => $_SESSION['user_id']]);
        $bill = $billStmt->fetch();

        if ($bill) {
            // Restore inventory stocks
            $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM sales_bill_items WHERE sales_bill_id = :id");
            $itemsStmt->execute(['id' => $id]);
            $items = $itemsStmt->fetchAll();

            foreach ($items as $item) {
                $restoreSQL = "UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :p_id AND user_id = :user_id";
                $pdo->prepare($restoreSQL)->execute(['qty' => $item['quantity'], 'p_id' => $item['product_id'], 'user_id' => $_SESSION['user_id']]);
            }

            // Delete sales bill (cascade deletes item lines & payments automatically via foreign constraints)
            $deleteStmt = $pdo->prepare("DELETE FROM sales_bills WHERE id = :id AND user_id = :user_id");
            $deleteStmt->execute(['id' => $id, 'user_id' => $_SESSION['user_id']]);

            // Delete transaction from general ledger
            $deleteTxn = $pdo->prepare("DELETE FROM transactions WHERE reference_id = :ref_id AND type = 'Sale' AND user_id = :user_id");
            $deleteTxn->execute(['ref_id' => $id, 'user_id' => $_SESSION['user_id']]);

            // Audit Log
            $auditSQL = "INSERT INTO audit_logs (user_id, action, details) VALUES (:user_id, 'Sales Bill Deleted', :details)";
            $pdo->prepare($auditSQL)->execute([
                'user_id' => $_SESSION['user_id'],
                'details' => "Sales Bill invoice {$bill['bill_number']} deleted. Inventory stocks rolled back."
            ]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => "Invoice {$bill['bill_number']} successfully deleted."]);
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Bill not found.']);
        }
        exit;
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Sales Bills CRUD Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during database operations: ' . $e->getMessage()]);
}
