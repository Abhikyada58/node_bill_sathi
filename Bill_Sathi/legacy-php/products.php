<?php
/**
 * Finance ERP - Product Management Module
 * Session-protected
 */

require_once __DIR__ . '/auth/session_check.php';
require_once __DIR__ . '/config/database.php';

$shopName = 'Finsnce Corp';
$shopAddress = '12 corporate boulevard, tech towers, Sector 62';
$shopState = 'Gujarat';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT u.full_name, ui.* FROM users u LEFT JOIN user_information ui ON u.id = ui.user_id WHERE u.id = :id LIMIT 1");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $userName = htmlspecialchars($user['full_name']);
        if (!empty($user['shop_name'])) {
            $shopName = htmlspecialchars($user['shop_name']);
        }
        if (!empty($user['shop_address'])) {
            $shopAddress = htmlspecialchars($user['shop_address']);
        }
        if (!empty($user['shop_state'])) {
            $shopState = htmlspecialchars($user['shop_state']);
        }
    }
} catch (Exception $e) {
    $userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
}

$userInitials = '';
if (!empty($userName)) {
    $parts = explode(' ', $userName);
    $userInitials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
} else {
    $userInitials = 'U';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Finsnce ERP</title>
    
    <!-- Bootstrap 5 & FontAwesome CDNs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Style variables inheritance -->
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .page-title {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
        }
        .main-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.02);
            padding: 0;
            overflow: hidden;
        }
        .control-row {
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-responsive {
            padding: 0;
        }
        .erp-table {
            width: 100%;
            margin-bottom: 0;
        }
        .erp-table th {
            background: #f8fafc !important;
            font-weight: 700;
            font-size: 13px;
            color: #475569;
            padding: 12px 16px;
            border-bottom: 2px solid #e2e8f0;
            text-transform: none;
        }
        .erp-table td {
            padding: 14px 16px;
            font-size: 13px;
            color: #1e293b;
            border-bottom: 1px solid #f1f5f9;
        }
        .erp-table tbody tr:last-child td {
            border-bottom: none;
        }
        .btn-add-product {
            border-radius: 6px;
            background: #2563eb;
            border-color: #2563eb;
            font-weight: 700;
            font-size: 13px;
            padding: 8px 18px;
            color: white;
            text-transform: uppercase;
        }
        .btn-add-product:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: white;
        }
        .btn-close-custom {
            border: none;
            background: #f1f5f9;
            color: #475569;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 14px;
            cursor: pointer;
            outline: none;
        }
        .btn-close-custom:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .modal-product-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 10px;
            background: #ffffff;
        }
        .modal-product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: none;
        }
        .modal-product-body {
            padding: 0 24px 24px 24px;
        }
        .modal-product-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .btn-save-product {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            background: #2563eb;
            border: none;
            border-radius: 8px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-save-product:hover {
            background: #1d4ed8;
        }
        .action-dropdown-btn {
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 16px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .action-dropdown-btn:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        .char-counter {
            font-size: 11px;
            color: #94a3b8;
            text-align: right;
            margin-top: 4px;
        }
    </style>
<script src="assets/js/translator.js"></script>
</head>
<body>

    <div class="dashboard-container">
        
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="fa-solid fa-wallet"></i>
                <span class="text-gradient">Finsnce ERP</span>
            </div>
            
            <div class="sidebar-menu-wrapper">
                <nav class="sidebar-menu">
                    <a href="dashboard.php" class="menu-item">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="sales_bills.php" class="menu-item">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Sales Bill</span>
                    </a>
                    <a href="purchase_bills.php" class="menu-item">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Purchase Bill</span>
                    </a>
                    <a href="dashboard.php#delivery-challan" class="menu-item">
                        <i class="fa-solid fa-truck"></i>
                        <span>Delivery Challan</span>
                    </a>
                    <a href="dashboard.php#manage-firm" class="menu-item">
                        <i class="fa-solid fa-building"></i>
                        <span>Manage Firm</span>
                    </a>
                    <a href="parties.php" class="menu-item">
                        <i class="fa-solid fa-users"></i>
                        <span>Manage Party</span>
                    </a>
                    <a href="products.php" class="menu-item active">
                        <i class="fa-solid fa-box-open"></i>
                        <span>Product</span>
                    </a>
                    <a href="expenses.php" class="menu-item">
                        <i class="fa-solid fa-wallet"></i>
                        <span>Expense Tracker</span>
                    </a>
                    <a href="transactions.php" class="menu-item">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        <span>Transaction</span>
                    </a>
                    <a href="dashboard.php#reports" class="menu-item">
                        <i class="fa-solid fa-chart-column"></i>
                        <span>Reports</span>
                    </a>
                    <a href="dashboard.php#settings" class="menu-item">
                        <i class="fa-solid fa-gears"></i>
                        <span>Setting</span>
                    </a>
                </nav>
            </div>
            
            <div class="sidebar-user">
                <div class="user-avatar" title="<?php echo $userName; ?>">
                    <?php echo $userInitials; ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo $userName; ?></span>
                    <span class="user-role">Administrator</span>
                </div>
            </div>
        </aside>

        <!-- Main Workspace Area -->
        <div class="main-content">
            
            <!-- Header (Top Navbar) -->
            <header class="header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="company-name">
                        <span><?php echo $shopName; ?></span>
                    </div>
                </div>
                
                <div class="header-right">
                    <!-- Financial Year Selector -->
                    <div class="nav-selector" title="Financial Year">
                        <i class="fa-solid fa-calendar-minus"></i>
                        <select id="financialYear">
                            <option value="2026-2027">FY 2026-27</option>
                            <option value="2025-2026">FY 2025-26</option>
                        </select>
                    </div>

                    <!-- Language Switcher -->
                    <div class="nav-selector" title="Language Switcher">
                        <i class="fa-solid fa-globe"></i>
                        <select id="languageSwitcher">
                            <option value="en">English</option>
                            <option value="es">Español</option>
                        </select>
                    </div>

                    <!-- Notifications -->
                    <button class="header-btn" aria-label="Notifications">
                        <i class="fa-regular fa-bell"></i>
                        <span class="notification-badge"></span>
                    </button>

                    <!-- Profile Sign Out -->
                    <a href="auth/logout.php" class="header-btn" title="Sign Out">
                        <i class="fa-solid fa-power-off"></i>
                    </a>
                </div>
            </header>

            <!-- Content Workspace -->
            <div class="content-body container-fluid py-4">

                <!-- Title Header -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="page-title mb-0">Product</h1>
                </div>

                <!-- Main Card container -->
                <div class="main-card">
                    
                    <!-- Control row -->
                    <div class="control-row">
                        <!-- Left: Show Entries Dropdown & Search -->
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center">
                                <span class="text-muted small me-2" style="white-space: nowrap;">Show</span>
                                <select id="paginationLimit" class="form-select form-select-sm" style="width: auto;" onchange="currentPage=1; loadProducts();">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100" selected>100</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center">
                                <input type="text" id="productSearch" class="form-control form-control-sm" placeholder="Search" style="width: 200px; border-radius: 6px; border-color: #cbd5e1;" oninput="currentPage=1; loadProducts();">
                            </div>
                        </div>

                        <!-- Right: Add Product Button -->
                        <div>
                            <button type="button" class="btn btn-add-product d-flex align-items-center gap-2" onclick="openAddProductModal()">
                                <i class="fa-solid fa-plus"></i> Add Product
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table erp-table align-middle" id="productsTable">
                            <thead>
                                <tr>
                                    <th style="width: 45%;">Product Name</th>
                                    <th style="width: 15%; text-align: right; padding-right: 20px;">Item Code</th>
                                    <th style="width: 15%; text-align: right; padding-right: 20px;">HSN Code</th>
                                    <th style="width: 10%; text-align: right; padding-right: 20px;">Rate</th>
                                    <th style="width: 10%; text-align: center;">Unit</th>
                                    <th style="width: 5%; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loaded dynamically via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-top">
                        <span class="text-muted small" id="paginationInfo">Showing 0 to 0 of 0 entries</span>
                        <nav id="paginationNav">
                            <!-- Pagination buttons -->
                        </nav>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- ============================================
       MODALS
       ============================================ -->

    <!-- Modal: Add Product -->
    <div class="modal fade" id="modalAddProduct" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content modal-product-card">
                <div class="modal-product-header">
                    <h5 class="modal-product-title">Add Product</h5>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-product-body">
                    <form id="addProductForm">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm py-2" placeholder="Enter Product Name" style="border-radius: 6px; border-color: #cbd5e1;" required>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-4">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Rate</label>
                                <input type="number" step="0.01" name="price" class="form-control form-control-sm py-2" placeholder="Enter Rate" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Unit <span class="text-danger">*</span></label>
                                <select name="unit" class="form-select form-select-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;" required>
                                    <option value="Pcs" selected>Pcs</option>
                                    <option value="Meters">Meters</option>
                                    <option value="Spools">Spools</option>
                                    <option value="Sets">Sets</option>
                                    <option value="Kgs">Kgs</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">GST</label>
                                <select name="gst_percent" class="form-select form-select-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;">
                                    <option value="">Select GST</option>
                                    <option value="0">GST 0%</option>
                                    <option value="5">GST 5%</option>
                                    <option value="12">GST 12%</option>
                                    <option value="18">GST 18%</option>
                                    <option value="28">GST 28%</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Item Code</label>
                                <input type="text" name="item_code" class="form-control form-control-sm py-2" placeholder="Enter Item Code" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                            <div class="col-6">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 13px;">HSN Code</label>
                                    <a href="https://www.google.com/search?q=hsn+code+finder" target="_blank" class="small text-decoration-none" style="font-size: 11px; color: #2563eb;">Find Code <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 9px;"></i></a>
                                </div>
                                <input type="text" name="hsn_code" class="form-control form-control-sm py-2" placeholder="Enter HSN Code" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Description</label>
                            <textarea name="description" id="add-desc" class="form-control form-control-sm" rows="3" placeholder="Enter Description" style="border-radius: 6px; border-color: #cbd5e1;" maxlength="250" oninput="updateCharCounter(this, 'add-counter')"></textarea>
                            <div class="char-counter" id="add-counter">0 / 250</div>
                        </div>

                        <!-- Full Width Save Button -->
                        <button type="submit" class="btn-save-product py-2 fw-bold text-uppercase">SAVE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Product -->
    <div class="modal fade" id="modalEditProduct" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content modal-product-card">
                <div class="modal-product-header">
                    <h5 class="modal-product-title">Edit Product</h5>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-product-body">
                    <form id="editProductForm">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit-product-id">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit-name" class="form-control form-control-sm py-2" placeholder="Enter Product Name" style="border-radius: 6px; border-color: #cbd5e1;" required>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-4">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Rate</label>
                                <input type="number" step="0.01" name="price" id="edit-price" class="form-control form-control-sm py-2" placeholder="Enter Rate" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Unit <span class="text-danger">*</span></label>
                                <select name="unit" id="edit-unit" class="form-select form-select-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;" required>
                                    <option value="Pcs">Pcs</option>
                                    <option value="Meters">Meters</option>
                                    <option value="Spools">Spools</option>
                                    <option value="Sets">Sets</option>
                                    <option value="Kgs">Kgs</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">GST</label>
                                <select name="gst_percent" id="edit-gst-percent" class="form-select form-select-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;">
                                    <option value="">Select GST</option>
                                    <option value="0">GST 0%</option>
                                    <option value="5">GST 5%</option>
                                    <option value="12">GST 12%</option>
                                    <option value="18">GST 18%</option>
                                    <option value="28">GST 28%</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Item Code</label>
                                <input type="text" name="item_code" id="edit-item-code" class="form-control form-control-sm py-2" placeholder="Enter Item Code" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                            <div class="col-6">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 13px;">HSN Code</label>
                                    <a href="https://www.google.com/search?q=hsn+code+finder" target="_blank" class="small text-decoration-none" style="font-size: 11px; color: #2563eb;">Find Code <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 9px;"></i></a>
                                </div>
                                <input type="text" name="hsn_code" id="edit-hsn-code" class="form-control form-control-sm py-2" placeholder="Enter HSN Code" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Description</label>
                            <textarea name="description" id="edit-desc" class="form-control form-control-sm" rows="3" placeholder="Enter Description" style="border-radius: 6px; border-color: #cbd5e1;" maxlength="250" oninput="updateCharCounter(this, 'edit-counter')"></textarea>
                            <div class="char-counter" id="edit-counter">0 / 250</div>
                        </div>

                        <!-- Full Width Save Button -->
                        <button type="submit" class="btn-save-product py-2 fw-bold text-uppercase">SAVE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Products JS Logic script -->
    <script src="assets/js/products.js"></script>
</body>
</html>
