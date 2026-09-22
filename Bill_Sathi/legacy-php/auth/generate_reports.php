<?php
/**
 * Reports Data Generator
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$type = $_GET['type'] ?? 'profit_loss';

try {
    $pdo = getDBConnection();
    $data = [];

    if ($type === 'daily') {
        // Daily breakdown of Sales, Purchases and Expenses for the last 15 days
        $sql = "SELECT date, 
                       SUM(CASE WHEN type='Sale' THEN amount ELSE 0 END) as sales,
                       SUM(CASE WHEN type='Purchase' THEN amount ELSE 0 END) as purchases,
                       SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END) as expenses
                FROM transactions 
                WHERE date >= CURRENT_DATE() - INTERVAL 15 DAY AND user_id = :user_id
                GROUP BY date 
                ORDER BY date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $data = $stmt->fetchAll();
    } 
    elseif ($type === 'monthly') {
        // Monthly breakdown for current year
        $sql = "SELECT MONTHNAME(date) as month, MONTH(date) as month_num,
                       SUM(CASE WHEN type='Sale' THEN amount ELSE 0 END) as sales,
                       SUM(CASE WHEN type='Purchase' THEN amount ELSE 0 END) as purchases,
                       SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END) as expenses
                FROM transactions 
                WHERE YEAR(date) = YEAR(CURRENT_DATE()) AND user_id = :user_id
                GROUP BY MONTH(date) 
                ORDER BY MONTH(date) DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $data = $stmt->fetchAll();
    } 
    elseif ($type === 'yearly') {
        // Yearly breakdown
        $sql = "SELECT YEAR(date) as year,
                       SUM(CASE WHEN type='Sale' THEN amount ELSE 0 END) as sales,
                       SUM(CASE WHEN type='Purchase' THEN amount ELSE 0 END) as purchases,
                       SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END) as expenses
                FROM transactions 
                WHERE user_id = :user_id
                GROUP BY YEAR(date) 
                ORDER BY YEAR(date) DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $data = $stmt->fetchAll();
    } 
    elseif ($type === 'expense') {
        // Expense categorization breakdown
        $sql = "SELECT category, SUM(amount) as total, COUNT(*) as count 
                FROM expenses 
                WHERE user_id = :user_id
                GROUP BY category 
                ORDER BY total DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $data = $stmt->fetchAll();
    } 
    else { // profit_loss
        // Summary calculations
        $salesStmt = $pdo->prepare("SELECT IFNULL(SUM(grand_total), 0) FROM sales_bills WHERE user_id = :user_id");
        $salesStmt->execute(['user_id' => $_SESSION['user_id']]);
        $salesVal = (float)$salesStmt->fetchColumn();

        $purchStmt = $pdo->prepare("SELECT IFNULL(SUM(amount), 0) FROM purchases WHERE user_id = :user_id");
        $purchStmt->execute(['user_id' => $_SESSION['user_id']]);
        $purchVal = (float)$purchStmt->fetchColumn();

        $expStmt = $pdo->prepare("SELECT IFNULL(SUM(amount), 0) FROM expenses WHERE user_id = :user_id");
        $expStmt->execute(['user_id' => $_SESSION['user_id']]);
        $expVal = (float)$expStmt->fetchColumn();

        $data = [
            'revenue' => [
                'sales' => $salesVal,
                'total_revenue' => $salesVal
            ],
            'operating_costs' => [
                'purchases' => $purchVal,
                'expenses' => $expVal,
                'total_costs' => $purchVal + $expVal
            ],
            'net_profit' => $salesVal - ($purchVal + $expVal)
        ];
    }

    echo json_encode([
        'success' => true,
        'type' => $type,
        'data' => $data
    ]);

} catch (PDOException $e) {
    error_log("Reports Generator Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to calculate database report metrics.']);
}
