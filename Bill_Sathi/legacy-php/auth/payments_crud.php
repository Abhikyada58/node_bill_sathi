<?php
/**
 * Payments CRUD & Allocation Handler
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
        $billId = (int)($_GET['sales_bill_id'] ?? 0);

        if ($billId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid sales bill ID.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT p.* FROM payments p JOIN sales_bills sb ON p.sales_bill_id = sb.id WHERE p.sales_bill_id = :bill_id AND sb.user_id = :user_id ORDER BY p.payment_date DESC, p.id DESC");
        $stmt->execute(['bill_id' => $billId, 'user_id' => $_SESSION['user_id']]);
        $payments = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $payments]);
        exit;
    }

    if ($action === 'create') {
        $billId = (int)($_POST['sales_bill_id'] ?? 0);
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
        $paymentMode = $_POST['payment_mode'] ?? '';
        $refNo = trim($_POST['reference_number'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $tdsAmount = (float)($_POST['tds_amount'] ?? 0);
        $settlementAmount = (float)($_POST['settlement_amount'] ?? $amount);
        $tdsPercent = (float)($_POST['tds_percent'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        // Validation
        if ($billId <= 0 || $amount <= 0 || empty($paymentMode)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required payment fields correctly.']);
            exit;
        }

        $allowedModes = ['Cash', 'Bank Transfer', 'UPI', 'Cheque'];
        if (!in_array($paymentMode, $allowedModes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid payment mode selected.']);
            exit;
        }

        $pdo->beginTransaction();

        // 1. Check current bill amounts
        $billStmt = $pdo->prepare("SELECT bill_number, grand_total, paid_amount FROM sales_bills WHERE id = :id AND user_id = :user_id");
        $billStmt->execute(['id' => $billId, 'user_id' => $_SESSION['user_id']]);
        $bill = $billStmt->fetch();

        if (!$bill) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Sales Bill not found.']);
            exit;
        }

        $pendingAmount = $bill['grand_total'] - $bill['paid_amount'];
        if ($settlementAmount > ($pendingAmount + 1.00)) { // Add minor tolerance for rounding
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => "Settlement amount (₹ $settlementAmount) cannot exceed pending invoice balance (₹ $pendingAmount)."]);
            exit;
        }

        // 2. Insert Payment record
        $paySQL = "INSERT INTO payments (user_id, sales_bill_id, payment_date, payment_mode, reference_number, amount, tds_amount, settlement_amount, tds_percent, notes) 
                   VALUES (:user_id, :bill_id, :pay_date, :mode, :ref, :amount, :tds_amount, :settlement_amount, :tds_percent, :notes)";
        $payStmt = $pdo->prepare($paySQL);
        $payStmt->execute([
            'user_id' => $_SESSION['user_id'],
            'bill_id' => $billId,
            'pay_date' => $paymentDate,
            'mode' => $paymentMode,
            'ref' => $refNo,
            'amount' => $amount,
            'tds_amount' => $tdsAmount,
            'settlement_amount' => $settlementAmount,
            'tds_percent' => $tdsPercent,
            'notes' => $notes
        ]);

        $paymentId = $pdo->lastInsertId();

        // 3. Update sales bill paid amount and status
        $newPaidAmount = $bill['paid_amount'] + $settlementAmount;
        
        $newStatus = 'UNPAID';
        if ($newPaidAmount >= ($bill['grand_total'] - 0.50)) { // tolerance for float rounding
            $newStatus = 'PAID';
            $newPaidAmount = $bill['grand_total']; // fully settled
        } elseif ($newPaidAmount > 0) {
            $newStatus = 'PARTIAL';
        }

        $updateBillSQL = "UPDATE sales_bills SET paid_amount = :paid, status = :status WHERE id = :id AND user_id = :user_id";
        $pdo->prepare($updateBillSQL)->execute([
            'paid' => $newPaidAmount,
            'status' => $newStatus,
            'id' => $billId,
            'user_id' => $_SESSION['user_id']
        ]);

        // 4. Record entry in general ledger transactions table
        $txnSQL = "INSERT INTO transactions (user_id, reference_id, type, amount, description, date) 
                   VALUES (:user_id, :ref_id, 'Payment', :amount, :desc, :date)";
        $pdo->prepare($txnSQL)->execute([
            'user_id' => $_SESSION['user_id'],
            'ref_id' => $paymentId,
            'amount' => $amount,
            'desc' => "Payment of ₹ " . number_format($amount, 2) . " (TDS ₹ " . number_format($tdsAmount, 2) . ") received via $paymentMode for Invoice {$bill['bill_number']}",
            'date' => $paymentDate
        ]);

        // 5. Audit Logging
        $auditSQL = "INSERT INTO audit_logs (user_id, action, details) VALUES (:user_id, 'Payment Recorded', :details)";
        $pdo->prepare($auditSQL)->execute([
            'user_id' => $_SESSION['user_id'],
            'details' => "Received payment of ₹ " . number_format($amount, 2) . " (TDS ₹ " . number_format($tdsAmount, 2) . ") for Sales Bill {$bill['bill_number']} via $paymentMode. New Status: $newStatus."
        ]);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Payment recorded successfully!',
            'new_status' => $newStatus,
            'new_paid' => $newPaidAmount
        ]);
        exit;
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Payments CRUD Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during payment operations: ' . $e->getMessage()]);
}
