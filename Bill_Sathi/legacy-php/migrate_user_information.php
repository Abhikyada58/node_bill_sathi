<?php
// Migration script: migrate_user_information.php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();

    echo "1. Creating user_information table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_information (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        shop_name VARCHAR(150) DEFAULT NULL,
        shop_address TEXT DEFAULT NULL,
        shop_state VARCHAR(100) DEFAULT NULL,
        shop_mobile VARCHAR(20) DEFAULT NULL,
        shop_gstin VARCHAR(15) DEFAULT NULL,
        shop_pan VARCHAR(10) DEFAULT NULL,
        shop_msme VARCHAR(50) DEFAULT NULL,
        bank_name VARCHAR(100) DEFAULT NULL,
        bank_acc_no VARCHAR(50) DEFAULT NULL,
        bank_acc_type VARCHAR(50) DEFAULT NULL,
        bank_ifsc VARCHAR(20) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "✓ user_information table created.\n\n";

    echo "2. Copying data from users to user_information...\n";
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertStmt = $pdo->prepare("INSERT IGNORE INTO user_information (
        user_id, shop_name, shop_address, shop_state, shop_mobile, shop_gstin, shop_pan, shop_msme, 
        bank_name, bank_acc_no, bank_acc_type, bank_ifsc
    ) VALUES (
        :user_id, :shop_name, :shop_address, :shop_state, :shop_mobile, :shop_gstin, :shop_pan, :shop_msme, 
        :bank_name, :bank_acc_no, :bank_acc_type, :bank_ifsc
    )");

    $migratedCount = 0;
    foreach ($users as $user) {
        // Only insert if columns exist in users table (to handle re-runs gracefully)
        if (array_key_exists('shop_name', $user)) {
            $insertStmt->execute([
                'user_id' => $user['id'],
                'shop_name' => $user['shop_name'],
                'shop_address' => $user['shop_address'],
                'shop_state' => $user['shop_state'],
                'shop_mobile' => $user['shop_mobile'],
                'shop_gstin' => $user['shop_gstin'],
                'shop_pan' => $user['shop_pan'],
                'shop_msme' => $user['shop_msme'],
                'bank_name' => $user['bank_name'],
                'bank_acc_no' => $user['bank_acc_no'],
                'bank_acc_type' => $user['bank_acc_type'],
                'bank_ifsc' => $user['bank_ifsc']
            ]);
            $migratedCount++;
        }
    }
    echo "✓ Migrated $migratedCount users.\n\n";

    echo "3. Dropping legacy columns from users table...\n";
    $columnsToDrop = [
        'shop_name', 'shop_address', 'shop_state', 'shop_mobile', 'shop_gstin', 'shop_pan', 'shop_msme',
        'bank_name', 'bank_acc_no', 'bank_acc_type', 'bank_ifsc'
    ];

    foreach ($columnsToDrop as $col) {
        try {
            $pdo->exec("ALTER TABLE users DROP COLUMN $col");
            echo "✓ Dropped $col\n";
        } catch (PDOException $e) {
            // Column might already be dropped
            echo "- Skipped dropping $col (might not exist)\n";
        }
    }

    echo "\nMigration complete successfully!\n";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage());
}
?>
