<?php
/**
 * Unified Transactions / Payments CRUD Handler
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
        // Pagination & Filters
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $search = trim($_GET['search'] ?? '');
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $mode = $_GET['mode'] ?? '';
        $type = $_GET['type'] ?? ''; // CREDIT (Sales) or DEBIT (Purchase)
        
        $where = ["p.user_id = :user_id"];
        $params = ['user_id' => $_SESSION['user_id']];

        if (!empty($search)) {
            $where[] = "(party_s.name LIKE :search OR party_p.name LIKE :search OR p.reference_number LIKE :search OR p.notes LIKE :search)";
            $params['search'] = "%$search%";
        }

        if (!empty($dateFrom)) {
            $where[] = "p.payment_date >= :date_from";
            $params['date_from'] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $where[] = "p.payment_date <= :date_to";
            $params['date_to'] = $dateTo;
        }

        if (!empty($mode)) {
            $where[] = "p.payment_mode = :mode";
            $params['mode'] = $mode;
        }

        if (!empty($type)) {
            if ($type === 'CREDIT') {
                $where[] = "p.sales_bill_id IS NOT NULL";
            } elseif ($type === 'DEBIT') {
                $where[] = "p.purchase_bill_id IS NOT NULL";
            }
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Total Count
        $countSql = "SELECT COUNT(*) FROM payments p
            LEFT JOIN sales_bills sb ON p.sales_bill_id = sb.id
            LEFT JOIN parties party_s ON sb.customer_id = party_s.id
            LEFT JOIN purchases pur ON p.purchase_bill_id = pur.id
            LEFT JOIN parties party_p ON pur.supplier_id = party_p.id
            $whereClause";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Get Rows
        $sql = "SELECT 
                    p.id,
                    p.payment_date,
                    p.reference_number,
                    p.amount,
                    p.payment_mode,
                    p.notes,
                    p.sales_bill_id,
                    p.purchase_bill_id,
                    sb.bill_number as sales_bill_number,
                    pur.bill_number as purchase_bill_number,
                    COALESCE(party_s.name, party_p.name) as party_name,
                    CASE 
                        WHEN p.sales_bill_id IS NOT NULL THEN 'CREDIT'
                        WHEN p.purchase_bill_id IS NOT NULL THEN 'DEBIT'
                        ELSE 'DEBIT'
                    END as tx_type
                FROM payments p
                LEFT JOIN sales_bills sb ON p.sales_bill_id = sb.id
                LEFT JOIN parties party_s ON sb.customer_id = party_s.id
                LEFT JOIN purchases pur ON p.purchase_bill_id = pur.id
                LEFT JOIN parties party_p ON pur.supplier_id = party_p.id
                $whereClause 
                ORDER BY p.payment_date DESC, p.id DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->execute();
        $payments = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $payments,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
        exit;
    }

    if ($action === 'get_parties') {
        $stmt = $pdo->prepare("SELECT id, name FROM parties WHERE user_id = :user_id ORDER BY name ASC");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $parties = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $parties]);
        exit;
    }

    if ($action === 'get_unpaid_bills') {
        $partyId = (int)($_GET['party_id'] ?? 0);
        $type = $_GET['type'] ?? 'Sales'; // Sales or Purchase

        if ($partyId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Party ID.']);
            exit;
        }

        if ($type === 'Sales') {
            $sql = "SELECT id, bill_number, bill_date, grand_total, paid_amount, 
                           (grand_total - paid_amount) as pending_amount
                    FROM sales_bills 
                    WHERE customer_id = :party_id AND status != 'PAID' AND user_id = :user_id
                    ORDER BY bill_date ASC, id ASC";
        } else {
            $sql = "SELECT id, bill_number, bill_date, amount as grand_total, paid_amount, 
                           (amount - paid_amount) as pending_amount
                    FROM purchases 
                    WHERE supplier_id = :party_id AND status != 'PAID' AND user_id = :user_id
                    ORDER BY bill_date ASC, id ASC";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['party_id' => $partyId, 'user_id' => $_SESSION['user_id']]);
        $bills = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $bills]);
        exit;
    }

    if ($action === 'create') {
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
        $paymentMode = $_POST['payment_mode'] ?? '';
        $refNo = trim($_POST['reference_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $type = $_POST['type'] ?? ''; // Sales or Purchase
        $partyId = (int)($_POST['party_id'] ?? 0);
        
        $allocations = [];
        if (isset($_POST['allocations']) && is_string($_POST['allocations'])) {
            $allocations = json_decode($_POST['allocations'], true);
        } elseif (isset($_POST['allocations']) && is_array($_POST['allocations'])) {
            $allocations = $_POST['allocations'];
        }

        if (empty($paymentMode) || $partyId <= 0 || empty($type) || empty($allocations)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields and select at least one bill.']);
            exit;
        }

        $pdo->beginTransaction();

        foreach ($allocations as $alloc) {
            $billId = (int)($alloc['bill_id'] ?? 0);
            $payAmount = (float)($alloc['pay_amount'] ?? 0);
            $settleAmount = (float)($alloc['settlement_amount'] ?? 0);

            if ($billId <= 0 || $payAmount <= 0) {
                continue;
            }

            if ($type === 'Sales') {
                // 1. Fetch Sales Bill
                $billStmt = $pdo->prepare("SELECT bill_number, grand_total, paid_amount FROM sales_bills WHERE id = :id AND user_id = :user_id FOR UPDATE");
                $billStmt->execute(['id' => $billId, 'user_id' => $_SESSION['user_id']]);
                $bill = $billStmt->fetch();

                if (!$bill) {
                    throw new Exception("Sales Bill ID $billId not found.");
                }

                $pendingAmount = $bill['grand_total'] - $bill['paid_amount'];
                if ($settleAmount > ($pendingAmount + 1.00)) {
                    throw new Exception("Settlement amount (₹ $settleAmount) cannot exceed pending invoice balance (₹ $pendingAmount) for Bill {$bill['bill_number']}.");
                }

                // 2. Insert Payment record
                $paySQL = "INSERT INTO payments (user_id, sales_bill_id, purchase_bill_id, payment_date, payment_mode, reference_number, amount, settlement_amount, notes) 
                           VALUES (:user_id, :bill_id, NULL, :pay_date, :mode, :ref, :amount, :settle_amount, :notes)";
                $payStmt = $pdo->prepare($paySQL);
                $payStmt->execute([
                    'user_id' => $_SESSION['user_id'],
                    'bill_id' => $billId,
                    'pay_date' => $paymentDate,
                    'mode' => $paymentMode,
                    'ref' => $refNo,
                    'amount' => $payAmount,
                    'settle_amount' => $settleAmount,
                    'notes' => $notes
                ]);
                $paymentId = $pdo->lastInsertId();

                // 3. Update Bill Paid Amount & Status
                $newPaidAmount = $bill['paid_amount'] + $settleAmount;
                $newStatus = 'UNPAID';
                if ($newPaidAmount >= ($bill['grand_total'] - 0.50)) {
                    $newStatus = 'PAID';
                    $newPaidAmount = $bill['grand_total'];
                } elseif ($newPaidAmount > 0) {
                    $newStatus = 'PARTIAL';
                }

                $pdo->prepare("UPDATE sales_bills SET paid_amount = :paid, status = :status WHERE id = :id AND user_id = :user_id")
                    ->execute(['paid' => $newPaidAmount, 'status' => $newStatus, 'id' => $billId, 'user_id' => $_SESSION['user_id']]);

                // 4. Log to transactions ledger
                $txnSQL = "INSERT INTO transactions (user_id, reference_id, type, amount, description, date) 
                           VALUES (:user_id, :ref_id, 'Payment', :amount, :desc, :date)";
                $pdo->prepare($txnSQL)->execute([
                    'user_id' => $_SESSION['user_id'],
                    'ref_id' => $paymentId,
                    'amount' => $payAmount,
                    'desc' => "Payment of ₹ " . number_format($payAmount, 2) . " received via $paymentMode for Invoice {$bill['bill_number']}",
                    'date' => $paymentDate
                ]);

                // 5. Log to audit_logs
                $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (:user_id, 'Payment Recorded', :details)")
                    ->execute(['user_id' => $_SESSION['user_id'], 'details' => "Received payment of ₹ " . number_format($payAmount, 2) . " for Sales Bill {$bill['bill_number']}. Status: $newStatus."]);

            } else {
                // 1. Fetch Purchase Bill
                $billStmt = $pdo->prepare("SELECT bill_number, amount as grand_total, paid_amount FROM purchases WHERE id = :id AND user_id = :user_id FOR UPDATE");
                $billStmt->execute(['id' => $billId, 'user_id' => $_SESSION['user_id']]);
                $bill = $billStmt->fetch();

                if (!$bill) {
                    throw new Exception("Purchase Bill ID $billId not found.");
                }

                $pendingAmount = $bill['grand_total'] - $bill['paid_amount'];
                if ($settleAmount > ($pendingAmount + 1.00)) {
                    throw new Exception("Settlement amount (₹ $settleAmount) cannot exceed pending invoice balance (₹ $pendingAmount) for Purchase Bill {$bill['bill_number']}.");
                }

                // 2. Insert Payment record
                $paySQL = "INSERT INTO payments (user_id, sales_bill_id, purchase_bill_id, payment_date, payment_mode, reference_number, amount, settlement_amount, notes) 
                           VALUES (:user_id, NULL, :bill_id, :pay_date, :mode, :ref, :amount, :settle_amount, :notes)";
                $payStmt = $pdo->prepare($paySQL);
                $payStmt->execute([
                    'user_id' => $_SESSION['user_id'],
                    'bill_id' => $billId,
                    'pay_date' => $paymentDate,
                    'mode' => $paymentMode,
                    'ref' => $refNo,
                    'amount' => $payAmount,
                    'settle_amount' => $settleAmount,
                    'notes' => $notes
                ]);
                $paymentId = $pdo->lastInsertId();

                // 3. Update Bill Paid Amount & Status
                $newPaidAmount = $bill['paid_amount'] + $settleAmount;
                $newStatus = 'UNPAID';
                if ($newPaidAmount >= ($bill['grand_total'] - 0.50)) {
                    $newStatus = 'PAID';
                    $newPaidAmount = $bill['grand_total'];
                } elseif ($newPaidAmount > 0) {
                    $newStatus = 'PARTIAL';
                }

                $pdo->prepare("UPDATE purchases SET paid_amount = :paid, status = :status WHERE id = :id AND user_id = :user_id")
                    ->execute(['paid' => $newPaidAmount, 'status' => $newStatus, 'id' => $billId, 'user_id' => $_SESSION['user_id']]);

                // 4. Log to transactions ledger
                $txnSQL = "INSERT INTO transactions (user_id, reference_id, type, amount, description, date) 
                           VALUES (:user_id, :ref_id, 'Payment', :amount, :desc, :date)";
                $pdo->prepare($txnSQL)->execute([
                    'user_id' => $_SESSION['user_id'],
                    'ref_id' => $paymentId,
                    'amount' => $payAmount,
                    'desc' => "Payment of ₹ " . number_format($payAmount, 2) . " paid via $paymentMode for Purchase Bill {$bill['bill_number']}",
                    'date' => $paymentDate
                ]);

                // 5. Log to audit_logs
                $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (:user_id, 'Payment Recorded', :details)")
                    ->execute(['user_id' => $_SESSION['user_id'], 'details' => "Paid payment of ₹ " . number_format($payAmount, 2) . " for Purchase Bill {$bill['bill_number']}. Status: $newStatus."]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Payment(s) recorded successfully!']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Payment ID.']);
            exit;
        }

        $pdo->beginTransaction();

        // 1. Fetch payment info
        $payStmt = $pdo->prepare("SELECT * FROM payments WHERE id = :id AND user_id = :user_id FOR UPDATE");
        $payStmt->execute(['id' => $id, 'user_id' => $_SESSION['user_id']]);
        $payment = $payStmt->fetch();

        if (!$payment) {
            throw new Exception("Payment record not found.");
        }

        // 2. Reverse allocations
        if ($payment['sales_bill_id'] !== null) {
            $billStmt = $pdo->prepare("SELECT grand_total, paid_amount FROM sales_bills WHERE id = :id AND user_id = :user_id FOR UPDATE");
            $billStmt->execute(['id' => $payment['sales_bill_id'], 'user_id' => $_SESSION['user_id']]);
            $bill = $billStmt->fetch();

            if ($bill) {
                $newPaidAmount = max(0, $bill['paid_amount'] - $payment['settlement_amount']);
                $newStatus = 'UNPAID';
                if ($newPaidAmount >= ($bill['grand_total'] - 0.50)) {
                    $newStatus = 'PAID';
                } elseif ($newPaidAmount > 0) {
                    $newStatus = 'PARTIAL';
                }

                $pdo->prepare("UPDATE sales_bills SET paid_amount = :paid, status = :status WHERE id = :id AND user_id = :user_id")
                    ->execute(['paid' => $newPaidAmount, 'status' => $newStatus, 'id' => $payment['sales_bill_id'], 'user_id' => $_SESSION['user_id']]);
            }
        } elseif ($payment['purchase_bill_id'] !== null) {
            $billStmt = $pdo->prepare("SELECT amount as grand_total, paid_amount FROM purchases WHERE id = :id AND user_id = :user_id FOR UPDATE");
            $billStmt->execute(['id' => $payment['purchase_bill_id'], 'user_id' => $_SESSION['user_id']]);
            $bill = $billStmt->fetch();

            if ($bill) {
                $newPaidAmount = max(0, $bill['paid_amount'] - $payment['settlement_amount']);
                $newStatus = 'UNPAID';
                if ($newPaidAmount >= ($bill['grand_total'] - 0.50)) {
                    $newStatus = 'PAID';
                } elseif ($newPaidAmount > 0) {
                    $newStatus = 'PARTIAL';
                }

                $pdo->prepare("UPDATE purchases SET paid_amount = :paid, status = :status WHERE id = :id AND user_id = :user_id")
                    ->execute(['paid' => $newPaidAmount, 'status' => $newStatus, 'id' => $payment['purchase_bill_id'], 'user_id' => $_SESSION['user_id']]);
            }
        }

        // 3. Delete from transactions ledger
        $pdo->prepare("DELETE FROM transactions WHERE reference_id = :ref_id AND type = 'Payment' AND user_id = :user_id")
            ->execute(['ref_id' => $id, 'user_id' => $_SESSION['user_id']]);

        // 4. Delete payment record
        $pdo->prepare("DELETE FROM payments WHERE id = :id AND user_id = :user_id")
            ->execute(['id' => $id, 'user_id' => $_SESSION['user_id']]);

        // 5. Log audit
        $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (:user_id, 'Payment Deleted', :details)")
            ->execute(['user_id' => $_SESSION['user_id'], 'details' => "Deleted payment of ₹ " . number_format($payment['amount'], 2) . " (Ref: {$payment['reference_number']})."]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Payment deleted successfully!']);
        exit;
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Transactions CRUD Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Operation error: ' . $e->getMessage()]);
}
