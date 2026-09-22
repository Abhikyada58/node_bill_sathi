<?php
/**
 * Dynamic Statistics and Chart Data Generator
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();

    // 1. Total Sales
    $salesStmt = $pdo->prepare("SELECT IFNULL(SUM(grand_total), 0) FROM sales_bills WHERE user_id = :user_id");
    $salesStmt->execute(['user_id' => $_SESSION['user_id']]);
    $totalSales = (float)$salesStmt->fetchColumn();

    // 2. Total Purchase
    $purchStmt = $pdo->prepare("SELECT IFNULL(SUM(amount), 0) FROM purchases WHERE user_id = :user_id");
    $purchStmt->execute(['user_id' => $_SESSION['user_id']]);
    $totalPurchase = (float)$purchStmt->fetchColumn();

    // 3. Total Expenses
    $expStmt = $pdo->prepare("SELECT IFNULL(SUM(amount), 0) FROM expenses WHERE user_id = :user_id");
    $expStmt->execute(['user_id' => $_SESSION['user_id']]);
    $totalExpenses = (float)$expStmt->fetchColumn();

    // 4. Total Profit
    $totalProfit = $totalSales - $totalPurchase - $totalExpenses;

    // 5. Pending Payments (Unpaid bills in Sales + Purchases)
    $pendingSalesStmt = $pdo->prepare("SELECT IFNULL(SUM(grand_total - paid_amount), 0) FROM sales_bills WHERE status != 'PAID' AND user_id = :user_id");
    $pendingSalesStmt->execute(['user_id' => $_SESSION['user_id']]);
    $pendingSales = (float)$pendingSalesStmt->fetchColumn();
    
    $pendingPurchStmt = $pdo->prepare("SELECT IFNULL(SUM(amount - paid_amount), 0) FROM purchases WHERE status != 'PAID' AND user_id = :user_id");
    $pendingPurchStmt->execute(['user_id' => $_SESSION['user_id']]);
    $pendingPurch = (float)$pendingPurchStmt->fetchColumn();
    
    $pendingPayments = $pendingSales + $pendingPurch;

    // 6. Monthly Revenue (Current Month Sales)
    $monthRevenueStmt = $pdo->prepare("SELECT IFNULL(SUM(grand_total), 0) FROM sales_bills WHERE MONTH(bill_date) = MONTH(CURRENT_DATE()) AND YEAR(bill_date) = YEAR(CURRENT_DATE()) AND user_id = :user_id");
    $monthRevenueStmt->execute(['user_id' => $_SESSION['user_id']]);
    $monthlyRevenue = (float)$monthRevenueStmt->fetchColumn();

    // 7. Chart Data (Monthly Sales & Expenses for last 6 months)
    $chartData = [];
    $months = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $months[] = date('M', strtotime("-$i months"));
    }

    $salesMonthly = [];
    $expensesMonthly = [];

    for ($i = 5; $i >= 0; $i--) {
        $targetMonth = date('m', strtotime("-$i months"));
        $targetYear = date('Y', strtotime("-$i months"));

        // Sales for month
        $sStmt = $pdo->prepare("SELECT IFNULL(SUM(grand_total), 0) FROM sales_bills WHERE MONTH(bill_date) = :month AND YEAR(bill_date) = :year AND user_id = :user_id");
        $sStmt->execute(['month' => $targetMonth, 'year' => $targetYear, 'user_id' => $_SESSION['user_id']]);
        $salesMonthly[] = (float)$sStmt->fetchColumn();

        // Expenses for month
        $eStmt = $pdo->prepare("SELECT IFNULL(SUM(amount), 0) FROM expenses WHERE MONTH(expense_date) = :month AND YEAR(expense_date) = :year AND user_id = :user_id");
        $eStmt->execute(['month' => $targetMonth, 'year' => $targetYear, 'user_id' => $_SESSION['user_id']]);
        $expensesMonthly[] = (float)$eStmt->fetchColumn();
    }

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_sales' => $totalSales,
            'total_purchase' => $totalPurchase,
            'total_expenses' => $totalExpenses,
            'total_profit' => $totalProfit,
            'pending_payments' => $pendingPayments,
            'monthly_revenue' => $monthlyRevenue
        ],
        'chart' => [
            'labels' => $months,
            'sales' => $salesMonthly,
            'expenses' => $expensesMonthly
        ]
    ]);

} catch (PDOException $e) {
    error_log("Statistics Fetch Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to calculate database statistics.']);
}
