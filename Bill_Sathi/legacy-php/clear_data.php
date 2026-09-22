<?php
/**
 * Clear All Website Data Script
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
    
    // Disable foreign key checks to allow truncating
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tables = [
        'payments',
        'transactions',
        'sales_bill_items',
        'sales_bills',
        'purchase_bill_items',
        'purchases',
        'expense_items',
        'expenses',
        'products',
        'parties',
        'audit_logs'
    ];
    
    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`");
        echo "Truncated table: $table\n";
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\nSUCCESS: All website data (bills, payments, expenses, products, parties, and logs) has been successfully cleared!\n";
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
}
