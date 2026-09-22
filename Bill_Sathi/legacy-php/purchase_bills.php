<?php
/**
 * Finance ERP - Purchase Bills Module (Bootstrap 5 & Blue & White Theme)
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
    <title>Purchase Bill Management - Finsnce ERP</title>
    
    <!-- Bootstrap 5 & FontAwesome CDNs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Style variables inheritance -->
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        /* Precision alignment to match the screenshot UI */
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
        .segmented-tabs {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .tab-btn {
            flex: 1;
            max-width: 320px;
            padding: 14px 20px;
            border: none;
            background: transparent;
            font-weight: 600;
            font-size: 14px;
            color: #64748b;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        .tab-btn.active {
            color: #ffffff;
            background: #2563eb;
        }
        .control-row {
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
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
        .badge {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-paid {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-unpaid {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-partial {
            background: #ffedd5;
            color: #854d0e;
        }
        .action-icon-container {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
        }
        .action-btn-circle {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            transition: all 0.15s ease;
            cursor: pointer;
            padding: 0;
        }
        .action-btn-circle:hover {
            border-color: #2563eb;
            color: #2563eb;
            background: #eff6ff;
        }
        .action-btn-circle.pay-btn {
            background: #eff6ff;
            color: #2563eb;
            border-color: #bfdbfe;
        }
        .action-btn-circle.pay-btn:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }
        .action-btn-circle.delete-btn-dropdown {
            position: relative;
        }
        
        /* Filter Drawer Overlay */
        .filter-panel {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 24px;
            display: none;
        }
        .filter-panel.active {
            display: block;
        }
        .footer-total-row {
            font-weight: 700;
            background: #f8fafc;
            border-top: 2px solid #e2e8f0;
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
        .modal-payment-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 10px;
            background: #ffffff;
        }
        .modal-payment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: none;
        }
        .modal-payment-body {
            padding: 0 24px 24px 24px;
        }
        .modal-payment-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .payment-meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 13px;
        }
        .payment-meta-label {
            color: #64748b;
        }
        .payment-meta-value {
            font-weight: 600;
        }
        .btn-save-payment {
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
        .btn-save-payment:hover {
            background: #1d4ed8;
        }
        .btn-close-invoice {
            border: none;
            background: #fee2e2;
            color: #ef4444;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-close-invoice:hover {
            background: #fca5a5;
            color: #b91c1c;
        }
        
        /* Products Entry Table Design */
        .products-grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .products-grid-table th {
            font-weight: 700;
            font-size: 12px;
            color: #475569;
            padding: 8px 12px;
            text-transform: none;
            border: none;
        }
        .products-grid-table td {
            background: #f8fafc;
            padding: 10px 12px;
            border: none;
        }
        .products-grid-table tr td:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        .products-grid-table tr td:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        .products-grid-table input, 
        .products-grid-table select {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13px;
            color: #1e293b;
            outline: none;
            width: 100%;
        }
        .products-grid-table input:focus, 
        .products-grid-table select:focus {
            border-color: #2563eb;
        }
        .btn-remove-row {
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
        }
        .btn-remove-row:hover {
            background: #fee2e2;
            color: #ef4444;
        }
        .btn-add-row {
            background: #ffffff;
            border: 1.5px solid #2563eb;
            color: #2563eb;
            font-weight: 700;
            font-size: 12px;
            padding: 6px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-add-row:hover {
            background: #2563eb;
            color: #ffffff;
        }
        
        /* Left Column Add Attachment and Remarks */
        .attachment-box {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s;
        }
        .attachment-box:hover {
            border-color: #2563eb;
            background: #eff6ff;
        }
        .char-counter {
            font-size: 11px;
            color: #94a3b8;
            text-align: right;
            margin-top: 4px;
        }
        
        /* Right Calculations Cards */
        .calculation-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 13px;
            color: #475569;
        }
        .calculation-row.grand-total-row {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            margin-top: 8px;
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
                    <a href="purchase_bills.php" class="menu-item active">
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
                    <a href="products.php" class="menu-item">
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

                <!-- SECTION 1: PURCHASE BILL LIST VIEW -->
                <div id="purchase-bill-list-panel" class="tab-content active">
                    
                    <!-- View title header row -->
                    <div id="view-title-row" class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="page-title mb-0">Purchase Bill</h1>
                    </div>

                    <!-- Main content card list container -->
                    <div class="main-card">
                        
                        <!-- Segmented Top Tabs -->
                        <div class="segmented-tabs">
                            <button class="tab-btn active" id="tab-all-bills">All</button>
                            <button class="tab-btn" id="tab-with-tax">With Tax</button>
                            <button class="tab-btn" id="tab-without-tax">Without Tax</button>
                        </div>

                        <!-- Control row -->
                        <div class="control-row">
                            <!-- Left controls (Limit entries dropdown & Search) -->
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center">
                                    <span class="text-muted small me-2" style="white-space: nowrap;">Show</span>
                                    <select id="paginationLimit" class="form-select form-select-sm" style="width: auto;" onchange="currentPage=1; loadPurchaseBills();">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100" selected>100</option>
                                    </select>
                                </div>
                                <div class="d-flex align-items-center">
                                    <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Search" style="width: 200px; border-radius: 6px; border-color: #cbd5e1;" oninput="currentPage=1; loadPurchaseBills();">
                                </div>
                            </div>

                            <!-- Right actions (Filter Toggle, Export Excel, Add Purchase Bill) -->
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-light btn-sm px-3 py-2 position-relative" style="border-radius: 6px; border: 1px solid #cbd5e1; color: #475569; font-size: 13px; background: #ffffff;" onclick="toggleFilterPanel()">
                                    <i class="fa-solid fa-filter me-1"></i> Filter
                                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle" id="filterBadge" style="display: none;"></span>
                                </button>
                                
                                <button type="button" class="btn btn-light btn-sm px-3 py-2" style="border-radius: 6px; border: 1px solid #cbd5e1; color: #15803d; font-size: 13px; background: #ffffff; font-weight: 600;" onclick="exportToExcel()">
                                    <i class="fa-regular fa-file-excel me-1"></i> Export Excel
                                </button>

                                <button type="button" class="btn btn-primary btn-sm px-3 py-2" style="border-radius: 6px; background: #2563eb; border-color: #2563eb; font-weight: 700; font-size: 13px; text-transform: uppercase;" onclick="toggleView('form')">
                                    <i class="fa-solid fa-plus me-1"></i> Add Purchase Bill
                                </button>
                            </div>
                        </div>

                        <!-- Collapsible Filter Panel -->
                        <div id="filterDrawer" class="filter-panel">
                            <form id="filterForm" class="row g-3" onsubmit="event.preventDefault(); currentPage=1; loadPurchaseBills();">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold mb-1">Global Search</label>
                                    <input type="text" id="filterSearchGlobal" class="form-control form-control-sm" placeholder="Search Bill#, Party, Remarks...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold mb-1">Supplier</label>
                                    <select id="filterSupplier" class="form-select form-select-sm">
                                        <option value="">-- All Suppliers --</option>
                                        <!-- Loaded dynamically -->
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold mb-1">Payment Status</label>
                                    <select id="filterStatus" class="form-select form-select-sm">
                                        <option value="">-- Choose Status --</option>
                                        <option value="PAID">PAID</option>
                                        <option value="UNPAID">UNPAID</option>
                                        <option value="PARTIAL">PARTIAL</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold mb-1">From Date</label>
                                    <input type="date" id="filterDateFrom" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold mb-1">To Date</label>
                                    <input type="date" id="filterDateTo" class="form-control form-control-sm">
                                </div>
                                <div class="col-12 text-end mt-2">
                                    <button type="button" class="btn btn-light btn-sm me-2" onclick="resetFilters()">Reset</button>
                                    <button type="submit" class="btn btn-primary btn-sm px-3">Apply Filters</button>
                                </div>
                            </form>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table erp-table align-middle" id="purchaseBillsTable">
                                <thead>
                                    <tr>
                                        <th>Bill Date</th>
                                        <th>Bill No.</th>
                                        <th>Party Name</th>
                                        <th>Bill Type</th>
                                        <th>Total Amount</th>
                                        <th>Pending Amount</th>
                                        <th>Status</th>
                                        <th>Due Days</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                                <tfoot>
                                    <tr class="footer-total-row">
                                        <td id="tbl-total-count" class="ps-4">Total 0</td>
                                        <td colspan="3"></td>
                                        <td id="tbl-total-amount">0.00</td>
                                        <td id="tbl-total-pending" colspan="3">0.00</td>
                                        <td class="pe-4"></td>
                                    </tr>
                                </tfoot>
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

                <!-- SECTION 2: ADD PURCHASE BILL SCREEN -->
                <div id="purchase-bill-form-panel" class="tab-content">
                    <div class="d-flex align-items-center mb-4">
                        <a href="javascript:void(0)" onclick="toggleView('list')" class="text-decoration-none text-dark d-flex align-items-center gap-2">
                            <i class="fa-solid fa-arrow-left fs-4" style="color: #475569;"></i>
                            <h4 class="mb-0 fw-bold" style="color: #1e293b; font-size: 20px;" id="formViewTitle">Add Purchase Bill</h4>
                        </a>
                    </div>

                    <form id="createBillForm" class="modal-form">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="id" id="editBillId">
                        <input type="hidden" name="submit_action" id="formSubmitAction" value="save">

                        <div class="row g-4">
                            <!-- Left form data inputs -->
                            <div class="col-12">
                                <div class="card p-4 mb-3 border-0 shadow-sm" style="border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0 !important;">
                                    
                                    <div class="row g-3">
                                        <!-- Supplier Select Dropdown -->
                                        <div class="col-md-4">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 13px;">Party <span class="text-danger">*</span></label>
                                                <a href="javascript:void(0)" onclick="openAddPartyModal()" class="small text-decoration-none" style="font-size: 11px; color: #2563eb; font-weight: 600;"><i class="fa-solid fa-plus me-1"></i> Add Party</a>
                                            </div>
                                            <select name="supplier_id" id="formSupplierSelect" class="form-select form-select-sm py-2" required style="border-radius: 6px; border-color: #cbd5e1;">
                                                <option value="">Select Party</option>
                                                <!-- Populated dynamically -->
                                            </select>
                                            <div class="mt-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="apply_gst" id="formApplyGst" value="1" checked onchange="calculateGrandTotal();">
                                                    <label class="form-check-label small fw-semibold" for="formApplyGst" style="color: #475569;">Apply GST</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bill Number -->
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 13px;">Bill No. <span class="text-danger">*</span></label>
                                            <input type="text" name="bill_number" id="formBillNumber" class="form-control form-control-sm py-2" placeholder="Bill No." required style="border-radius: 6px; border-color: #cbd5e1;">
                                        </div>

                                        <!-- Bill Date -->
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 13px;">Bill Date</label>
                                            <input type="date" name="bill_date" id="formBillDate" class="form-control form-control-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;" onchange="updateDueDate();">
                                        </div>

                                        <!-- Due Days & Calculated Due Date -->
                                        <div class="col-md-4 offset-md-4">
                                            <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 13px;">Due Days</label>
                                            <input type="number" name="due_days" id="formDueDays" class="form-control form-control-sm py-2" placeholder="Enter Due Days" style="border-radius: 6px; border-color: #cbd5e1;" oninput="updateDueDate();">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 13px;">Due Date</label>
                                            <input type="text" id="formDueDateDisplay" class="form-control form-control-sm py-2 bg-light text-muted" value="DD/MM/YYYY" readonly style="border-radius: 6px; border-color: #cbd5e1;">
                                            <input type="hidden" name="due_date" id="formDueDate">
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Products section -->
                            <div class="col-12">
                                <div class="card p-4 mb-3 border-0 shadow-sm" style="border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0 !important;">
                                    <h5 class="fw-bold mb-3" style="color: #0f172a; font-size: 15px;">Products</h5>
                                    
                                    <div class="table-responsive">
                                        <table class="products-grid-table" id="formProductsTable">
                                            <thead>
                                                <tr>
                                                    <th style="width: 20%;">Product <span class="text-danger">*</span></th>
                                                    <th style="width: 10%;">Item Code</th>
                                                    <th style="width: 10%;">HSN Code</th>
                                                    <th style="width: 8%;">Qty <span class="text-danger">*</span></th>
                                                    <th style="width: 8%;">Unit <span class="text-danger">*</span></th>
                                                    <th style="width: 10%;">Rate (₹) *</th>
                                                    <th style="width: 12%;">Discount (%)</th>
                                                    <th style="width: 12%;">Tax (%) *</th>
                                                    <th style="width: 10%;">Amount (₹)</th>
                                                    <th style="width: 5%;"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Dynamically loaded rows -->
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-add-row" onclick="addProductRow();">
                                            <i class="fa-solid fa-plus me-1"></i> ADD PRODUCT
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Left Side: Attachment & Remarks -->
                            <div class="col-lg-6">
                                <div class="card p-4 border-0 shadow-sm mb-3" style="border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0 !important;">
                                    <!-- Add attachment placeholder -->
                                    <div class="attachment-box mb-3">
                                        <i class="fa-solid fa-cloud-arrow-up fs-2 text-primary mb-2"></i>
                                        <h6 class="mb-1 fw-bold text-dark" style="font-size: 13px;">Add Attachment</h6>
                                        <p class="text-muted small mb-0">JPG, JPEG, PNG, PDF</p>
                                    </div>

                                    <!-- Remark textarea -->
                                    <div>
                                        <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 12px;">Remark</label>
                                        <textarea name="remarks" id="formRemarks" class="form-control form-control-sm" rows="3" placeholder="Enter Remark" style="border-radius: 6px; border-color: #cbd5e1;" maxlength="200" oninput="updateCharCounter(this);"></textarea>
                                        <div class="char-counter" id="remarksCharCounter">0 / 200</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Side: Totals & Calculations -->
                            <div class="col-lg-6">
                                <div class="card p-4 border-0 shadow-sm" style="border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0 !important;">
                                    <div style="width: 100%; font-size: 13px;">
                                        <div class="calculation-row">
                                            <span>Total Qty.:</span>
                                            <span id="summaryTotalQty" class="fw-bold">0.00</span>
                                        </div>
                                        <div class="calculation-row">
                                            <span>Net Amount:</span>
                                            <span id="summaryNetAmount" class="fw-bold">₹ 0.00</span>
                                        </div>
                                        <div class="calculation-row text-danger">
                                            <span>Discount Amount:</span>
                                            <span id="summaryDiscountAmount" class="fw-bold">- ₹ 0.00</span>
                                        </div>
                                        <div class="calculation-row">
                                            <span>Taxable Amount:</span>
                                            <span id="summaryTaxableAmount" class="fw-bold">₹ 0.00</span>
                                        </div>
                                        <div class="calculation-row text-success">
                                            <span>Tax Amount (GST):</span>
                                            <span id="summaryGstAmount" class="fw-bold">+ ₹ 0.00</span>
                                        </div>
                                        
                                        <div class="calculation-row grand-total-row">
                                            <span>Total Amount:</span>
                                            <span id="summaryGrandTotal" class="text-dark">₹ 0.00</span>
                                        </div>
                                    </div>

                                    <!-- Submit action row -->
                                    <div class="d-flex justify-content-end gap-2 mt-4 pt-2">
                                        <button type="button" class="btn btn-success px-4 py-2 fw-semibold" style="border-radius: 6px; font-size: 13px; background: #16a34a; border-color: #16a34a;" onclick="submitBillForm('save_and_new')">
                                            Save & Create New
                                        </button>
                                        <button type="button" class="btn btn-light px-4 py-2 fw-semibold" style="border-radius: 6px; border: 1px solid #cbd5e1; color: #ef4444; font-size: 13px; background: #fee2e2;" onclick="toggleView('list')">
                                            CANCEL
                                        </button>
                                        <button type="button" class="btn btn-primary px-4 py-2 fw-bold text-uppercase" style="border-radius: 6px; font-size: 13px; background: #2563eb; border-color: #2563eb;" onclick="submitBillForm('save')">
                                            SUBMIT
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- ============================================
       MODALS
       ============================================ -->

    <!-- Modal: Record Payment -->
    <div class="modal fade" id="modalPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content modal-payment-card">
                <div class="modal-payment-header">
                    <h5 class="modal-payment-title">Record Payment</h5>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-payment-body">
                    <form id="recordPaymentForm">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="purchase_bill_id" id="paymentBillId">
                        
                        <!-- Metadata row -->
                        <div class="payment-meta-row">
                            <span class="payment-meta-label">Bill No.</span>
                            <span class="payment-meta-value" id="pay-meta-bill-no">-</span>
                        </div>
                        <div class="payment-meta-row mb-3 pb-3 border-bottom">
                            <span class="payment-meta-label">Party</span>
                            <span class="payment-meta-value" id="pay-meta-supplier">-</span>
                        </div>

                        <!-- Calculations card -->
                        <div class="d-flex justify-content-end mb-4">
                            <div style="width: 100%; max-width: 320px; font-size: 13px;">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Amount:</span>
                                    <span class="fw-semibold text-dark" id="pay-calc-total">₹ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold text-primary" style="font-size: 14px;">
                                    <span>Pending Amount (₹):</span>
                                    <span id="pay-calc-pending">₹ 0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Form inputs -->
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Payment Date</label>
                                <input type="date" name="payment_date" id="pay-input-date" class="form-control form-control-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Amount Paid</label>
                                <input type="number" step="0.01" name="amount" id="paymentMaxAmount" class="form-control form-control-sm py-2" placeholder="0.00" min="0.01" style="border-radius: 6px; border-color: #cbd5e1;" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Payment Mode</label>
                                <select name="payment_mode" class="form-select form-select-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;" required>
                                    <option value="">Select Payment Mode</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Note</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Enter Note" style="border-radius: 6px; border-color: #cbd5e1;"></textarea>
                                <div class="form-text small text-muted mt-1" style="font-size: 11px;">e.g. Reference number, cheque details, etc.</div>
                            </div>
                        </div>

                        <!-- Full Width Save Button -->
                        <button type="submit" class="btn-save-payment py-2 fw-bold text-uppercase">SAVE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: View Bill Details -->
    <div class="modal fade" id="modalViewBill" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content card border-0 shadow-lg">
                <style>
                    @media print {
                        body {
                            background: #fff !important;
                            color: #000 !important;
                        }
                        .no-print, .modal-header, .modal-footer, .sidebar, .header, .modal-backdrop, button, close {
                            display: none !important;
                            visibility: hidden !important;
                        }
                        .modal {
                            position: absolute;
                            left: 0;
                            top: 0;
                            margin: 0;
                            padding: 0;
                            overflow: visible !important;
                        }
                        .modal-dialog {
                            max-width: 100% !important;
                            width: 100% !important;
                            margin: 0 !important;
                            padding: 0 !important;
                        }
                        .modal-content {
                            border: none !important;
                            box-shadow: none !important;
                            background: transparent !important;
                        }
                        #invoice-print-area {
                            padding: 0 !important;
                            margin: 0 !important;
                        }
                        .classic-invoice-box {
                            border: 2px solid #000 !important;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                        .classic-invoice-box * {
                            border-color: #000 !important;
                        }
                    }
                </style>
                <div class="modal-header border-bottom-0 pb-0 no-print">
                    <h5 class="modal-title font-weight-bold">Purchase Bill Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="invoice-print-area">
                     <!-- Classic Invoice Layout Box -->
                    <div class="classic-invoice-box" style="border: 1px solid #000; font-family: Arial, sans-serif; color: #000; font-size: 12px; background: #fff;">
                        
                        <!-- Shop header -->
                        <div class="text-center" style="padding: 15px; background: #f8f9fa; border-bottom: 1px solid #000;">
                            <h2 style="margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 1px; color: #000; text-transform: uppercase;" id="pur-inv-preview-shop-name"><?php echo !empty($shopName) ? $shopName : 'Finsnce Corp'; ?></h2>
                        </div>
                        
                        <!-- Shop Info Row -->
                        <div class="row m-0" style="border-bottom: 1px solid #000;">
                            <div class="col-7 p-2" style="border-right: 1px solid #000;">
                                <span id="pur-inv-preview-shop-address"><?php echo !empty($shopAddress) ? $shopAddress : '12 corporate boulevard, tech towers, Sector 62'; ?></span><br>
                                <strong>State :-</strong> <span id="pur-inv-preview-shop-state" style="text-transform: uppercase;"><?php echo !empty($shopState) ? $shopState : 'GUJARAT'; ?></span>
                            </div>
                            <div class="col-5 p-2 text-end">
                                <strong>Mobile No :-</strong> <span id="pur-inv-preview-shop-mobile"><?php echo !empty($shopMobile) ? $shopMobile : '-'; ?></span><br>
                                <strong>GSTIN :-</strong> <span id="pur-inv-preview-shop-gstin"><?php echo !empty($shopGstin) ? $shopGstin : '-'; ?></span><br>
                                <strong>PAN :-</strong> <span id="pur-inv-preview-shop-pan"><?php echo !empty($shopPan) ? $shopPan : '-'; ?></span><br>
                                <strong>MSME No :-</strong> <span id="pur-inv-preview-shop-msme"><?php echo !empty($shopMsme) ? $shopMsme : '-'; ?></span>
                            </div>
                        </div>
                        
                        <!-- Invoice Bar -->
                        <div class="text-center" style="padding: 4px 0; background: #f8f9fa; border-bottom: 1px solid #000;">
                            <strong style="font-size: 14px; letter-spacing: 1px; text-transform: uppercase;">TAX INVOICE</strong>
                        </div>
                        
                        <!-- Client and Bill Info Row -->
                        <div class="row m-0" style="border-bottom: 1px solid #000; min-height: 90px;">
                            <div class="col-7 p-2" style="border-right: 1px solid #000;">
                                <strong>BILLED FROM : <span id="pur-inv-preview-customer" style="font-size: 14px; text-transform: uppercase;">-</span></strong><br>
                                <div style="margin-top: 5px;"><strong>Address :</strong> <span id="pur-inv-preview-address">-</span></div>
                                <div style="margin-top: 15px; display: flex; justify-content: space-between;">
                                    <span><strong>GSTIN :</strong> <span id="pur-inv-preview-gst">-</span></span>
                                    <span><strong>PAN :-</strong> <span id="pur-inv-preview-customer-pan">-</span></span>
                                </div>
                            </div>
                            <div class="col-5 p-2" style="line-height: 1.8;">
                                <div class="row"><div class="col-4 fw-bold">Bill No.:</div><div class="col-8 fw-bold" id="pur-inv-preview-bill-no">-</div></div>
                                <div class="row"><div class="col-4 fw-bold">Bill Date:</div><div class="col-8 fw-bold" id="pur-inv-preview-date">-</div></div>
                                <div class="row"><div class="col-4 fw-bold">Challan:</div><div class="col-8 fw-bold" id="pur-inv-preview-challan-no">-</div></div>
                            </div>
                        </div>
                        
                        <!-- Items Table -->
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 1px solid #000;">
                                    <th style="border-right: 1px solid #000; padding: 4px; text-align: center; width: 5%;">Sr.</th>
                                    <th style="border-right: 1px solid #000; padding: 4px; width: 45%;">Item Name</th>
                                    <th style="border-right: 1px solid #000; padding: 4px; text-align: center; width: 10%;">HSN</th>
                                    <th style="border-right: 1px solid #000; padding: 4px; text-align: center; width: 12%;">Qty</th>
                                    <th style="border-right: 1px solid #000; padding: 4px; text-align: right; width: 12%;">Rate</th>
                                    <th style="padding: 4px; text-align: right; width: 16%;">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="pur-inv-preview-items" style="vertical-align: top;">
                                <!-- Dynamic rows from JS -->
                            </tbody>
                            <tfoot>
                                <tr style="border-top: 1px solid #000; background: #f8f9fa; font-weight: bold;">
                                    <td colspan="3" style="text-align: right; border-right: 1px solid #000; padding: 4px 8px;">Total</td>
                                    <td id="pur-inv-preview-total-qty" style="text-align: center; border-right: 1px solid #000; padding: 4px;">0</td>
                                    <td style="border-right: 1px solid #000; padding: 4px;"></td>
                                    <td id="pur-inv-preview-total-amount-sum" style="text-align: right; padding: 4px;">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <!-- Footer Section -->
                        <div class="row m-0" style="border-top: 1px solid #000;">
                            <!-- Left: Terms and Bank Details -->
                            <div class="col-7 p-0" style="border-right: 1px solid #000;">
                                <div style="padding: 4px 8px; border-bottom: 1px solid #000;">
                                    <strong>Amount in Words :</strong> <i id="pur-inv-preview-amount-words" style="text-transform: uppercase;">-</i>
                                </div>
                                <div style="padding: 4px 8px; border-bottom: 1px solid #000; min-height: 60px;">
                                    <strong>Terms :</strong> 1. Subject to '<span id="pur-inv-preview-shop-state-term"><?php echo !empty($shopState) ? $shopState : 'Gujarat'; ?></span>' Jurisdiction only. 2.(PAYMENT DUE TO 45 DAY ) <?php echo !empty($shopMsme) ? '('.$shopMsme.')' : ''; ?>
                                </div>
                                <div class="text-center" style="padding: 2px 0; background: #f8f9fa; border-bottom: 1px solid #000;">
                                    <strong>Bank Details</strong>
                                </div>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 4px; width: 25%;"><strong>Bank:</strong> <?php echo !empty($bankName) ? $bankName : '-'; ?></td>
                                        <td style="border-bottom: 1px solid #000; padding: 2px 4px; width: 75%;"><strong>A/c No:</strong> <?php echo !empty($bankAccNo) ? $bankAccNo : '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 4px;"><strong>Type:</strong> <?php echo !empty($bankAccType) ? strtoupper($bankAccType) : '-'; ?></td>
                                        <td style="border-bottom: 1px solid #000; padding: 2px 4px;"><strong>IFSC:</strong> <?php echo !empty($bankIfsc) ? $bankIfsc : '-'; ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Right: Financial Totals -->
                            <div class="col-5 p-0">
                                <div style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000;">
                                    <span>Discount (<span id="pur-inv-preview-discount-percent">0</span>%)</span>
                                    <span id="pur-inv-preview-discount">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000;">
                                    <span>Taxable Amount</span>
                                    <span id="pur-inv-preview-taxable">0.00</span>
                                </div>
                                <div id="pur-inv-preview-tax-breakdown">
                                    <!-- Populated dynamically via JS -->
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000;">
                                    <span>Total Tax</span>
                                    <span id="pur-inv-preview-total-tax">0.00</span>
                                </div>
                                <!-- TDS/TCS row if any -->
                                <div id="pur-inv-preview-tds-tcs-row" style="display: none; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000;">
                                    <span id="pur-inv-preview-tds-tcs-label">TDS/TCS (0%)</span>
                                    <span id="pur-inv-preview-tds-tcs-amount">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000; font-weight: bold;">
                                    <span>Net Amount</span>
                                    <span id="pur-inv-preview-grand-total">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000; font-weight: bold;">
                                    <span>Round Amount</span>
                                    <span id="pur-inv-preview-round-amount">0.00</span>
                                </div>
                                <div style="padding: 4px 8px; min-height: 80px; position: relative;">
                                    <strong>Signature :</strong>
                                    <div style="position: absolute; bottom: 5px; right: 10px; width: 120px; border-bottom: 1px dashed #000;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row m-0 no-print">
                            <div class="col-12 p-2">
                                <h6 class="fw-bold mb-2">Payment History Logs</h6>
                                <div class="timeline" id="pur-inv-preview-timeline" style="font-size: 11px;">
                                    <!-- Log Items -->
                                </div>
                            </div>
                        </div>
                    </div>       
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 no-print">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnShareWhatsApp"><i class="fa-brands fa-whatsapp text-success"></i> Share</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnShareEmail"><i class="fa-regular fa-envelope text-primary"></i> Email</button>
                    <button type="button" class="btn btn-primary" onclick="downloadPurchaseInvoicePDF();"><i class="fa-solid fa-download"></i> Download PDF</button>
                    <button type="button" class="btn btn-primary" onclick="window.print();"><i class="fa-solid fa-print"></i> Print Bill</button>
                </div>
            </div>
        </div>
    </div>

    <!-- html2pdf JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    
    <!-- Modal: Add Party -->
    <div class="modal fade" id="modalAddParty" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-party-card">
                <div class="modal-party-header">
                    <h5 class="modal-party-title">Add Party</h5>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-party-body">
                    <form id="quickAddSupplierForm">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">GST No.</label>
                                <input type="text" name="gst_number" id="add-gst" class="form-control form-control-sm py-2" placeholder="Enter GST number" style="border-radius: 6px; border-color: #cbd5e1;" maxlength="15" oninput="populatePanFromGst(this.value, 'add-pan')">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">PAN Card</label>
                                <input type="text" name="pan_number" id="add-pan" class="form-control form-control-sm py-2" placeholder="Enter PANCard Number" style="border-radius: 6px; border-color: #cbd5e1;" maxlength="10">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Party Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm py-2" placeholder="Enter Party Name" style="border-radius: 6px; border-color: #cbd5e1;" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Owner Name</label>
                                <input type="text" name="owner_name" class="form-control form-control-sm py-2" placeholder="Enter party owner's name" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Address <span class="text-danger">*</span></label>
                                <textarea name="address" class="form-control form-control-sm" rows="2" placeholder="Enter full address" style="border-radius: 6px; border-color: #cbd5e1;" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">State <span class="text-danger">*</span></label>
                                <select name="state" class="form-select form-select-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;" required>
                                    <option value="">Select State</option>
                                    <option value="Gujarat" selected>Gujarat</option>
                                    <option value="Maharashtra">Maharashtra</option>
                                    <option value="Delhi">Delhi</option>
                                    <option value="Rajasthan">Rajasthan</option>
                                    <option value="Uttar Pradesh">Uttar Pradesh</option>
                                    <option value="Karnataka">Karnataka</option>
                                    <option value="Tamil Nadu">Tamil Nadu</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">City</label>
                                <select name="city" class="form-select form-select-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;">
                                    <option value="">Select City</option>
                                    <option value="Surat" selected>Surat</option>
                                    <option value="Ahmedabad">Ahmedabad</option>
                                    <option value="Mumbai">Mumbai</option>
                                    <option value="New Delhi">New Delhi</option>
                                    <option value="Jaipur">Jaipur</option>
                                    <option value="Bangalore">Bangalore</option>
                                    <option value="Chennai">Chennai</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Pincode</label>
                                <input type="text" name="pincode" class="form-control form-control-sm py-2" placeholder="Enter Pincode" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Contact Number</label>
                                <input type="text" name="phone" class="form-control form-control-sm py-2" placeholder="Enter mobile number" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Discount</label>
                                <input type="number" step="0.01" name="discount" class="form-control form-control-sm py-2" placeholder="Enter Discount" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Due Days</label>
                                <input type="number" name="due_days" class="form-control form-control-sm py-2" value="45" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Broker Name</label>
                                <input type="text" name="broker_name" class="form-control form-control-sm py-2" placeholder="Enter Broker Name" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Broker Mobile No</label>
                                <input type="text" name="broker_mobile" class="form-control form-control-sm py-2" placeholder="Enter mobile number" style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                        </div>

                        <!-- Full Width Save Button -->
                        <button type="submit" class="btn-save-party py-2 fw-bold text-uppercase">SAVE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Page Script controller -->
    <script src="assets/js/purchase_bills.js?v=<?php echo time(); ?>"></script>
</body>
</html>
