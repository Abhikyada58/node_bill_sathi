<?php
/**
 * Update User Profile
 * Processes profile settings submissions via AJAX
 * Returns JSON response
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Get and sanitize inputs
$fullName = trim($_POST['full_name'] ?? '');
$shopName = trim($_POST['shop_name'] ?? '');
$shopAddress = trim($_POST['shop_address'] ?? '');
$shopState = trim($_POST['shop_state'] ?? '');
$shopMobile = trim($_POST['shop_mobile'] ?? '');
$shopGstin = trim($_POST['shop_gstin'] ?? '');
$shopPan = trim($_POST['shop_pan'] ?? '');
$shopMsme = trim($_POST['shop_msme'] ?? '');
$bankName = trim($_POST['bank_name'] ?? '');
$bankAccNo = trim($_POST['bank_acc_no'] ?? '');
$bankAccType = trim($_POST['bank_acc_type'] ?? '');
$bankIfsc = trim($_POST['bank_ifsc'] ?? '');

// Validate inputs
if (empty($fullName)) {
    echo json_encode(['success' => false, 'message' => 'Administrator Account Name is required.']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Update user profile details in users table
    $stmt = $pdo->prepare("UPDATE users SET full_name = :full_name WHERE id = :id");
    $stmt->execute([
        'full_name' => $fullName,
        'id' => $_SESSION['user_id']
    ]);

    // Update shop and bank settings in user_information table
    $infoStmt = $pdo->prepare("INSERT INTO user_information (
        user_id, shop_name, shop_address, shop_state, shop_mobile, shop_gstin, shop_pan, shop_msme, 
        bank_name, bank_acc_no, bank_acc_type, bank_ifsc
    ) VALUES (
        :user_id, :shop_name, :shop_address, :shop_state, :shop_mobile, :shop_gstin, :shop_pan, :shop_msme, 
        :bank_name, :bank_acc_no, :bank_acc_type, :bank_ifsc
    ) ON DUPLICATE KEY UPDATE 
        shop_name = VALUES(shop_name),
        shop_address = VALUES(shop_address),
        shop_state = VALUES(shop_state),
        shop_mobile = VALUES(shop_mobile),
        shop_gstin = VALUES(shop_gstin),
        shop_pan = VALUES(shop_pan),
        shop_msme = VALUES(shop_msme),
        bank_name = VALUES(bank_name),
        bank_acc_no = VALUES(bank_acc_no),
        bank_acc_type = VALUES(bank_acc_type),
        bank_ifsc = VALUES(bank_ifsc)
    ");

    $infoStmt->execute([
        'user_id' => $_SESSION['user_id'],
        'shop_name' => $shopName,
        'shop_address' => $shopAddress,
        'shop_state' => $shopState,
        'shop_mobile' => $shopMobile,
        'shop_gstin' => $shopGstin,
        'shop_pan' => $shopPan,
        'shop_msme' => $shopMsme,
        'bank_name' => $bankName,
        'bank_acc_no' => $bankAccNo,
        'bank_acc_type' => $bankAccType,
        'bank_ifsc' => $bankIfsc
    ]);
    
    // Update session name
    $_SESSION['user_name'] = $fullName;
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile settings updated successfully!'
    ]);
    
} catch (PDOException $e) {
    error_log("Profile Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred during save operations: ' . $e->getMessage()]);
}
