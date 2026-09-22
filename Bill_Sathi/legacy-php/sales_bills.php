<?php
/**
 * Finance ERP - Sales Bills Module (Bootstrap 5 & Blue & White Theme)
 * Session-protected
 */

require_once __DIR__ . '/auth/session_check.php';
require_once __DIR__ . '/config/database.php';

$shopName    = 'Finsnce Corp';
$shopAddress = '12 Corporate Boulevard, Tech Towers, Sector 62';
$shopState   = 'Gujarat';
$shopMobile  = '';
$shopGstin   = '';
$shopPan     = '';
$shopMsme    = '';
$bankName    = '';
$bankAccNo   = '';
$bankAccType = '';
$bankIfsc    = '';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT u.full_name, ui.* FROM users u LEFT JOIN user_information ui ON u.id = ui.user_id WHERE u.id = :id LIMIT 1");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $userName    = htmlspecialchars($user['full_name']);
        if (!empty($user['shop_name']))    $shopName    = htmlspecialchars($user['shop_name']);
        if (!empty($user['shop_address'])) $shopAddress = htmlspecialchars($user['shop_address']);
        if (!empty($user['shop_state']))   $shopState   = htmlspecialchars($user['shop_state']);
        if (!empty($user['shop_mobile']))  $shopMobile  = htmlspecialchars($user['shop_mobile']);
        if (!empty($user['shop_gstin']))   $shopGstin   = htmlspecialchars($user['shop_gstin']);
        if (!empty($user['shop_pan']))     $shopPan     = htmlspecialchars($user['shop_pan']);
        if (!empty($user['shop_msme']))    $shopMsme    = htmlspecialchars($user['shop_msme']);
        if (!empty($user['bank_name']))    $bankName    = htmlspecialchars($user['bank_name']);
        if (!empty($user['bank_acc_no']))  $bankAccNo   = htmlspecialchars($user['bank_acc_no']);
        if (!empty($user['bank_acc_type']))$bankAccType = htmlspecialchars($user['bank_acc_type']);
        if (!empty($user['bank_ifsc']))    $bankIfsc    = htmlspecialchars($user['bank_ifsc']);
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
    <title>Sales Bill Management - Finsnce ERP</title>
    
    <!-- Bootstrap 5 & FontAwesome CDNs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Style variables inheritance -->
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sales_bills.css">
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
            gap: 8px;
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
            color: #0f172a;
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
    </style>
    <script>
        window.onerror = function(message, source, lineno, colno, error) {
            alert("JS Error: " + message + " at " + source + ":" + lineno + ":" + colno);
            return false;
        };
        window.addEventListener('unhandledrejection', function(event) {
            alert("Promise Rejected: " + event.reason);
        });
    </script>
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
                    <a href="sales_bills.php" class="menu-item active">
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

                <!-- Title Header -->
                <div id="view-title-row" class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="page-title mb-0">Sales Bill</h1>
                </div>

                <!-- SECTION 1: SALES BILLS LIST PANEL -->
                <div id="sales-bill-list-panel" class="tab-content active">
                    <div class="main-card">
                        
                        <!-- Top Tabs (All, Tax Invoice, Job Challan) -->
                        <div class="segmented-tabs">
                            <button class="tab-btn active" id="tab-all-bills">All</button>
                            <button class="tab-btn" id="tab-tax-invoices">Tax Invoice</button>
                            <button class="tab-btn" id="tab-job-challans">Job Challan</button>
                        </div>

                        <!-- Action Bar below Tabs -->
                        <div class="control-row">
                            <!-- Left: Show Entries Dropdown -->
                            <div class="d-flex align-items-center">
                                <span class="text-muted small me-2">Show</span>
                                <select id="paginationLimit" class="form-select form-select-sm" style="width: auto;" onchange="currentPage=1; loadSalesBills();">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100" selected>100</option>
                                </select>
                            </div>

                            <!-- Right: Filter, Excel and Add buttons -->
                            <div class="d-flex align-items-center gap-2">
                                <!-- Filter toggle button -->
                                <button type="button" class="action-btn-circle" style="width: 32px; height: 32px;" onclick="toggleFilterPanel()" title="Toggle Filter Panel">
                                    <i class="fa-solid fa-filter"></i>
                                </button>
                                <!-- Export Excel -->
                                <button type="button" class="btn btn-outline-success btn-sm fw-bold px-3 d-flex align-items-center gap-2" style="border-radius:6px; border-color:#cbd5e1; color:#166534;" onclick="alert('Export Excel sheet successfully completed!')">
                                    <i class="fa-solid fa-file-excel"></i> Export Excel
                                </button>
                                <!-- Add Bill -->
                                <button type="button" class="btn btn-primary btn-sm fw-bold px-3 d-flex align-items-center gap-2" style="border-radius:6px; background:#2563eb; border-color:#2563eb;" onclick="toggleView('form')">
                                    <i class="fa-solid fa-plus"></i> ADD BILL
                                </button>
                            </div>
                        </div>

                        <!-- Filter panel drawer (Collapsible) -->
                        <div class="filter-panel" id="filterDrawer">
                            <form id="filterForm" class="row g-2" onsubmit="event.preventDefault(); currentPage=1; loadSalesBills();">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold mb-1">Global Search</label>
                                    <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Search bill no, customer...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold mb-1">Select Customer</label>
                                    <select id="filterCustomer" class="form-select form-select-sm">
                                        <option value="">-- All Customers --</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold mb-1">Payment Status</label>
                                    <select id="filterStatus" class="form-select form-select-sm">
                                        <option value="">-- Choose Status --</option>
                                        <option value="PAID">PAID</option>
                                        <option value="UNPAID">UNPAID</option>
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
                            <table class="table erp-table align-middle" id="salesBillsTable">
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

                <!-- SECTION 2: ADD SALES BILL SCREEN -->
                <div id="sales-bill-form-panel" class="tab-content">
                    <div class="d-flex align-items-center mb-4">
                        <a href="javascript:void(0)" onclick="toggleView('list')" class="text-decoration-none text-dark d-flex align-items-center gap-2">
                            <i class="fa-solid fa-arrow-left fs-4" style="color: #475569;"></i>
                            <h4 class="mb-0 fw-bold" style="color: #1e293b; font-size: 20px;">Add Bill</h4>
                        </a>
                    </div>

                    <form id="createBillForm" class="modal-form">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="submit_action" id="formSubmitAction" value="save">

                        <div class="row g-4">
                            <!-- Left form data inputs -->
                            <div class="col-12">
                                <div class="card p-4 mb-3 border-0 shadow-sm" style="border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0 !important;">
                                    <div class="row g-4">
                                        <!-- Left Side -->
                                        <div class="col-lg-7 border-end pe-lg-4">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label mb-0 fw-bold" style="color: #475569; font-size: 13px;">Party <span class="text-danger">*</span></label>
                                                <a href="javascript:void(0)" onclick="openCustomerModal()" class="text-decoration-none small fw-bold" style="color: #2563eb; font-size: 12px;">+ Add Party</a>
                                            </div>
                                            <select name="customer_id" id="formCustomerSelect" class="form-select form-select-sm py-2" required style="border-radius: 6px; border-color: #cbd5e1;">
                                                <option value="">Select Party</option>
                                            </select>
                                            <div class="form-check mt-3">
                                                <input class="form-check-input" type="checkbox" name="apply_gst" id="formApplyGst" checked onchange="calculateGrandTotal();" style="border-color: #cbd5e1;">
                                                <label class="form-check-label small fw-bold" for="formApplyGst" style="color: #475569;">Apply GST</label>
                                            </div>
                                        </div>
                                        <!-- Right Side -->
                                        <div class="col-lg-5 ps-lg-4">
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 12px;">Bill No.</label>
                                                    <input type="text" name="bill_number" id="formBillNo" class="form-control form-control-sm py-2" placeholder="Bill No." style="border-radius: 6px; border-color: #cbd5e1;">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 12px;">Bill Date</label>
                                                    <input type="date" name="bill_date" id="formBillDate" class="form-control form-control-sm py-2" value="<?php echo date('Y-m-d'); ?>" required onchange="calculateDueDate();" style="border-radius: 6px; border-color: #cbd5e1;">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 12px;">Due Days</label>
                                                    <input type="number" name="due_days" id="formDueDays" class="form-control form-control-sm py-2" placeholder="Enter Due Days" min="0" oninput="calculateDueDate();" style="border-radius: 6px; border-color: #cbd5e1;">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 12px;">Due Date</label>
                                                    <input type="text" id="formDueDateDisplay" class="form-control form-control-sm py-2 bg-light text-muted" placeholder="DD/MM/YYYY" readonly style="border-radius: 6px; border-color: #cbd5e1;">
                                                    <input type="hidden" name="due_date" id="formDueDate">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="formNoChallan" onchange="toggleChallanBlock();" style="border-color: #cbd5e1;">
                                    <label class="form-check-label small fw-bold" for="formNoChallan" style="color: #475569;">Don't have challan?</label>
                                </div>

                                <!-- Challan container -->
                                <div id="challanContainerCard" class="card p-3 mb-3 border-0" style="border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                                    <div class="d-flex justify-content-between align-items-start align-items-md-center flex-column flex-md-row gap-2">
                                        <div class="row g-3 flex-grow-1 w-100">
                                            <div class="col-md-5">
                                                <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 12px;">Challan Number <span class="text-danger">*</span></label>
                                                <input type="text" name="challan_no" id="formChallanNo" class="form-control form-control-sm py-2" placeholder="Enter Challan no." style="border-radius: 6px; border-color: #cbd5e1;">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 12px;">Challan Date</label>
                                                <input type="date" name="challan_date" id="formChallanDate" class="form-control form-control-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;">
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-link text-danger text-decoration-none small fw-bold d-flex align-items-center gap-1 mt-md-3 px-0 align-self-end align-self-md-auto" onclick="deleteChallanBlock();" style="font-size: 13px; min-width: 120px;">
                                            <i class="fa-solid fa-trash-can"></i> Delete Challan
                                        </button>
                                    </div>
                                </div>

                                <!-- Products lists -->
                                <div class="card p-4 mb-3 border-0 shadow-sm" style="border-radius: 10px; background: #ffffff; border: 1px solid #e2e8f0 !important;">
                                    <div class="table-responsive">
                                        <table class="table align-middle product-table" id="itemsTable" style="margin-bottom: 0;">
                                            <thead>
                                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                                    <th style="width: 30%; font-size: 12px; font-weight: 700; color: #475569; padding: 10px 12px;">Product <span class="text-danger">*</span></th>
                                                    <th style="width: 13%; font-size: 12px; font-weight: 700; color: #475569; padding: 10px 12px;">Item Code</th>
                                                    <th style="width: 12%; font-size: 12px; font-weight: 700; color: #475569; padding: 10px 12px;">HSN Code</th>
                                                    <th style="width: 12%; font-size: 12px; font-weight: 700; color: #475569; padding: 10px 12px;">Qty <span class="text-danger">*</span></th>
                                                    <th style="width: 12%; font-size: 12px; font-weight: 700; color: #475569; padding: 10px 12px;">Unit <span class="text-danger">*</span></th>
                                                    <th style="width: 12%; font-size: 12px; font-weight: 700; color: #475569; padding: 10px 12px;">Rate (₹) <span class="text-danger">*</span></th>
                                                    <th style="width: 12%; font-size: 12px; font-weight: 700; color: #475569; padding: 10px 12px; text-align: right;">Amount (₹)</th>
                                                    <th style="width: 5%; padding: 10px 12px;"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Dynamic Rows -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3 py-2" onclick="addProductRow()" style="border-radius: 6px; border: 1px dashed #2563eb; color: #2563eb; font-size: 12px; background: transparent;">
                                            <i class="fa-solid fa-plus me-1"></i> ADD PRODUCT
                                        </button>
                                    </div>
                                </div>

                                <!-- Remarks & Calculations row -->
                                <div class="row g-4 mb-4">
                                    <!-- Left Side Buttons -->
                                    <div class="col-lg-6">
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <button type="button" id="btnCreateChallan" class="btn btn-primary btn-sm fw-bold px-3 py-2" onclick="createChallanBlock();" style="border-radius: 6px; background: #2563eb; border-color: #2563eb; font-size: 12px;">
                                                <i class="fa-solid fa-plus me-1"></i> Create New Challan
                                            </button>
                                            <button type="button" class="btn btn-light btn-sm fw-bold px-3 py-2" onclick="toggleRemarksBlock();" style="border-radius: 6px; border: 1px solid #cbd5e1; color: #475569; font-size: 12px; background: #ffffff;">
                                                Add Remarks
                                            </button>
                                        </div>
                                        <!-- Hidden Remarks Div -->
                                        <div id="remarksContainer" class="card p-3 border-0 shadow-sm" style="display: none; border-radius: 8px; background: #ffffff; border: 1px solid #cbd5e1 !important;">
                                            <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 12px;">Remarks / Notes</label>
                                            <textarea name="remarks" class="form-control form-control-sm" rows="3" placeholder="Add comments here..." style="border-radius: 6px; border-color: #cbd5e1;"></textarea>
                                        </div>
                                    </div>

                                    <!-- Right Side Calculations -->
                                    <div class="col-lg-6 d-flex justify-content-end">
                                        <div style="width: 100%; max-width: 400px;">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="small fw-bold" style="color: #475569;">Discount (%)</span>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="number" step="0.01" name="discount_percent" id="formDiscountPercent" class="form-control form-control-sm text-end" style="width: 80px; border-radius: 6px; border-color: #cbd5e1;" value="0" min="0" max="100" oninput="calculateGrandTotal();">
                                                    <span id="summaryDiscountLabel" class="small fw-bold" style="color: #1e293b; min-width: 80px; text-align: right;">0.00 ₹</span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <span class="small fw-bold" style="color: #475569;">GST (%)</span>
                                                <div class="d-flex align-items-center gap-2">
                                                    <select name="gst_percent" id="formGstPercent" class="form-select form-select-sm" style="width: 110px; border-radius: 6px; border-color: #cbd5e1;" onchange="calculateGrandTotal();">
                                                        <option value="0">GST 0%</option>
                                                        <option value="5">GST 5%</option>
                                                        <option value="12">GST 12%</option>
                                                        <option value="18" selected>GST 18%</option>
                                                        <option value="28">GST 28%</option>
                                                    </select>
                                                    <span id="summaryGstLabel" class="small fw-bold" style="color: #1e293b; min-width: 80px; text-align: right;">0.00 ₹</span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <span class="small fw-bold" style="color: #475569;">TDS/TCS</span>
                                                <div class="d-flex align-items-center gap-2">
                                                    <select name="tds_tcs_type" id="formTdsTcsType" class="form-select form-select-sm" style="width: 110px; border-radius: 6px; border-color: #cbd5e1;" onchange="onTdsTcsTypeChange();">
                                                        <option value="NONE" selected>NONE</option>
                                                        <option value="TDS">TDS</option>
                                                        <option value="TCS">TCS</option>
                                                    </select>
                                                    <div id="tdsTcsInputsContainer" class="d-flex align-items-center gap-2" style="display: none !important;">
                                                        <input type="number" step="0.01" name="tds_tcs_percent" id="formTdsTcsPercent" class="form-control form-control-sm text-end" style="width: 70px; border-radius: 6px; border-color: #cbd5e1;" value="0.00" min="0" max="100" oninput="onTdsTcsPercentChange();">
                                                        <span class="small" style="color: #475569;">%</span>
                                                        <input type="number" step="0.01" name="tds_tcs_amount" id="formTdsTcsAmount" class="form-control form-control-sm text-end" style="width: 90px; border-radius: 6px; border-color: #cbd5e1;" value="0.00" min="0" oninput="onTdsTcsAmountChange();">
                                                        <span class="small" style="color: #475569;">₹</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between mb-2" style="font-size: 13px; color: #64748b;">
                                                <span>Total Qty:</span>
                                                <span id="summaryTotalQty" class="fw-semibold">0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2" style="font-size: 13px; color: #64748b;">
                                                <span>Net Amount:</span>
                                                <span id="summaryGrossAmount" class="fw-semibold">₹ 0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2" style="font-size: 13px; color: #64748b;">
                                                <span>Discount Amount:</span>
                                                <span id="summaryDiscountAmount" class="fw-semibold">- ₹ 0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2" style="font-size: 13px; color: #64748b;">
                                                <span>Taxable Amount:</span>
                                                <span id="summaryTaxableAmount" class="fw-semibold">₹ 0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3" style="font-size: 13px; color: #64748b;">
                                                <span>Tax Amount:</span>
                                                <span id="summaryGstAmount" class="fw-semibold">+ ₹ 0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3 align-items-center" style="font-size: 13px; color: #64748b;">
                                                <span id="summaryTdsTcsLabel">TDS/TCS (0%):</span>
                                                <span id="summaryTdsTcsAmount" class="fw-semibold">₹ 0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between pt-2 border-top" style="font-size: 16px; font-weight: 700; color: #1e293b;">
                                                <span>Total Amount:</span>
                                                <span id="summaryGrandTotal" style="color: #1e293b;">₹ 0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    <button type="submit" onclick="document.getElementById('formSubmitAction').value='save_new';" class="btn btn-success fw-bold px-4 py-2" style="border-radius: 6px; background: #16a34a; border-color: #16a34a; font-size: 13px; color: white;">Save & Create New Bill</button>
                                    <button type="button" class="btn fw-bold px-4 py-2" onclick="toggleView('list')" style="border-radius: 6px; background: #fee2e2; color: #991b1b; border: none; font-size: 13px;">CANCEL</button>
                                    <button type="submit" onclick="document.getElementById('formSubmitAction').value='save';" class="btn btn-primary fw-bold px-4 py-2" style="border-radius: 6px; background: #2563eb; border-color: #2563eb; font-size: 13px; color: white;">SUBMIT</button>
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
                        <input type="hidden" name="sales_bill_id" id="paymentBillId">
                        
                        <!-- Metadata section -->
                        <div class="payment-meta-row">
                            <span class="payment-meta-label">Bill No.</span>
                            <span class="payment-meta-value" id="pay-meta-bill-no">-</span>
                        </div>
                        <div class="payment-meta-row mb-3 pb-3 border-bottom">
                            <span class="payment-meta-label">Billed To</span>
                            <span class="payment-meta-value" id="pay-meta-customer">-</span>
                        </div>

                        <!-- Calculations right aligned block -->
                        <div class="d-flex justify-content-end mb-4">
                            <div style="width: 100%; max-width: 320px; font-size: 13px;">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Net Amount :</span>
                                    <span class="fw-semibold text-dark" id="pay-calc-net">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 text-danger">
                                    <span class="text-muted" id="pay-calc-disc-label">Total Discount (0%):</span>
                                    <span class="fw-semibold" id="pay-calc-disc">- 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Taxable Amount :</span>
                                    <span class="fw-semibold text-dark" id="pay-calc-taxable">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span class="text-muted" id="pay-calc-gst-label">Tax Amount :</span>
                                    <span class="fw-semibold" id="pay-calc-gst">+ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 text-danger align-items-center">
                                    <span class="text-muted d-flex align-items-center gap-1">
                                        TDS (<span id="pay-tds-pct-display">1</span>%)
                                        <a href="javascript:void(0)" onclick="editTdsPercentage()" class="text-secondary" style="font-size: 11px;"><i class="fa-solid fa-pencil"></i></a>
                                    </span>
                                    <span class="fw-semibold" id="pay-calc-tds">- 0.00</span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between mb-2 fw-bold text-dark" style="font-size: 14px;">
                                    <span>Total Amount :</span>
                                    <span id="pay-calc-total">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold text-primary" style="font-size: 14px;">
                                    <span>Pending Amount (₹) :</span>
                                    <span id="pay-calc-pending">0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Form Input Fields Grid -->
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Payment Date</label>
                                <input type="date" name="payment_date" id="pay-input-date" class="form-control form-control-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Transaction Amount</label>
                                <input type="number" step="0.01" name="amount" id="paymentMaxAmount" class="form-control form-control-sm py-2" placeholder="0.00" min="0.01" style="border-radius: 6px; border-color: #cbd5e1;" oninput="onTxnAmountChange()" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Payment Mode</label>
                                <select name="payment_mode" class="form-select form-select-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;" required>
                                    <option value="">Select Payment Mode</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Settlement Amount</label>
                                <input type="number" step="0.01" name="settlement_amount" id="pay-settlement-amount" class="form-control form-control-sm py-2 bg-light text-muted" placeholder="0.00" style="border-radius: 6px; border-color: #cbd5e1;" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Note</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Enter Note" style="border-radius: 6px; border-color: #cbd5e1;"></textarea>
                                <div class="form-text small text-muted mt-1" style="font-size: 11px;">e.g. 'Chq#0478596' or 'Online Ref#BZ4859WH'</div>
                            </div>
                        </div>

                        <!-- TDS and hidden inputs -->
                        <input type="hidden" name="tds_percent" id="pay-tds-percent" value="1">
                        <input type="hidden" name="tds_amount" id="pay-tds-amount" value="0">

                        <!-- Full Width Save Button -->
                        <button type="submit" class="btn-save-payment py-2 fw-bold text-uppercase">SAVE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Add New Customer -->
    <div class="modal fade" id="modalCustomer" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-party-card">
                <div class="modal-party-header">
                    <h5 class="modal-party-title">Add Party</h5>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-party-body">
                    <form id="quickAddCustomerForm">
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

    <!-- Modal: TDS / TCS -->
    <div class="modal fade" id="modalTdsTcs" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content modal-payment-card">
                <div class="modal-payment-header">
                    <h5 class="modal-payment-title">TDS / TCS</h5>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label small fw-bold mb-2" style="color: #475569;">Select Tax:</label>
                        <div class="d-flex justify-content-between">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="modal_tds_tcs_type_radio" id="radioNone" value="NONE" checked>
                                <label class="form-check-label small fw-bold" for="radioNone" style="color: #475569;">NONE</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="modal_tds_tcs_type_radio" id="radioTds" value="TDS">
                                <label class="form-check-label small fw-bold" for="radioTds" style="color: #475569;">TDS</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="modal_tds_tcs_type_radio" id="radioTcs" value="TCS">
                                <label class="form-check-label small fw-bold" for="radioTcs" style="color: #475569;">TCS</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-bold" style="color: #475569;">Tax Percentage:</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" id="modalTdsTcsPercentInput" class="form-control" value="0.00" min="0" max="100">
                                <span class="input-group-text bg-light">%</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold" style="color: #475569;">Tax Amount:</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" id="modalTdsTcsAmountInput" class="form-control" value="0.00" min="0">
                                <span class="input-group-text bg-light">₹</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary w-100 fw-bold" onclick="saveTdsTcsSettings()">SAVE</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: View Tax Invoice -->
    <div class="modal fade" id="modalInvoice" tabindex="-1" aria-hidden="true">
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
                    <h5 class="modal-title font-weight-bold">Invoice Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="invoice-print-area">
                    
                    <!-- Classic Invoice Layout Box -->
                    <div class="classic-invoice-box" style="border: 1px solid #000; font-family: Arial, sans-serif; color: #000; font-size: 12px; background: #fff;">
                        
                        <!-- Shop header -->
                        <div class="text-center" style="padding: 15px; background: #f8f9fa; border-bottom: 1px solid #000;">
                            <h2 style="margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 1px; color: #000; text-transform: uppercase;" id="inv-preview-shop-name"><?php echo !empty($shopName) ? $shopName : 'Finsnce Corp'; ?></h2>
                        </div>
                        
                        <!-- Shop Info Row -->
                        <div class="row m-0" style="border-bottom: 1px solid #000;">
                            <div class="col-7 p-2" style="border-right: 1px solid #000;">
                                <span id="inv-preview-shop-address"><?php echo !empty($shopAddress) ? $shopAddress : '12 corporate boulevard, tech towers, Sector 62'; ?></span><br>
                                <strong>State :-</strong> <span id="inv-preview-shop-state" style="text-transform: uppercase;"><?php echo !empty($shopState) ? $shopState : 'GUJARAT'; ?></span>
                            </div>
                            <div class="col-5 p-2 text-end">
                                <strong>Mobile No :-</strong> <span id="inv-preview-shop-mobile"><?php echo !empty($shopMobile) ? $shopMobile : '-'; ?></span><br>
                                <strong>GSTIN :-</strong> <span id="inv-preview-shop-gstin"><?php echo !empty($shopGstin) ? $shopGstin : '-'; ?></span><br>
                                <strong>PAN :-</strong> <span id="inv-preview-shop-pan"><?php echo !empty($shopPan) ? $shopPan : '-'; ?></span><br>
                                <strong>MSME No :-</strong> <span id="inv-preview-shop-msme"><?php echo !empty($shopMsme) ? $shopMsme : '-'; ?></span>
                            </div>
                        </div>
                        
                        <!-- Invoice Bar -->
                        <div class="text-center" style="padding: 4px 0; background: #f8f9fa; border-bottom: 1px solid #000;">
                            <strong style="font-size: 14px; letter-spacing: 1px; text-transform: uppercase;">TAX INVOICE</strong>
                        </div>
                        
                        <!-- Client and Bill Info Row -->
                        <div class="row m-0" style="border-bottom: 1px solid #000; min-height: 90px;">
                            <div class="col-7 p-2" style="border-right: 1px solid #000;">
                                <strong>BILLED TO : <span id="inv-preview-customer" style="font-size: 14px; text-transform: uppercase;">-</span></strong><br>
                                <div style="margin-top: 5px;"><strong>Address :</strong> <span id="inv-preview-address">-</span></div>
                                <div style="margin-top: 15px; display: flex; justify-content: space-between;">
                                    <span><strong>GSTIN :</strong> <span id="inv-preview-gst">-</span></span>
                                    <span><strong>PAN :-</strong> <span id="inv-preview-customer-pan">-</span></span>
                                </div>
                            </div>
                            <div class="col-5 p-2" style="line-height: 1.8;">
                                <div class="row"><div class="col-4 fw-bold">Bill No.:</div><div class="col-8 fw-bold" id="inv-preview-bill-no">-</div></div>
                                <div class="row"><div class="col-4 fw-bold">Bill Date:</div><div class="col-8 fw-bold" id="inv-preview-date">-</div></div>
                                <div class="row"><div class="col-4 fw-bold">Challan:</div><div class="col-8 fw-bold" id="inv-preview-challan-no">-</div></div>
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
                            <tbody id="inv-preview-items" style="vertical-align: top;">
                                <!-- Dynamic rows from JS -->
                            </tbody>
                            <tfoot>
                                <tr style="border-top: 1px solid #000; background: #f8f9fa; font-weight: bold;">
                                    <td colspan="3" style="text-align: right; border-right: 1px solid #000; padding: 4px 8px;">Total</td>
                                    <td id="inv-preview-total-qty" style="text-align: center; border-right: 1px solid #000; padding: 4px;">0</td>
                                    <td style="border-right: 1px solid #000; padding: 4px;"></td>
                                    <td id="inv-preview-total-amount-sum" style="text-align: right; padding: 4px;">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <!-- Footer Section -->
                        <div class="row m-0" style="border-top: 1px solid #000;">
                            <!-- Left: Terms and Bank Details -->
                            <div class="col-7 p-0" style="border-right: 1px solid #000;">
                                <div style="padding: 4px 8px; border-bottom: 1px solid #000;">
                                    <strong>Amount in Words :</strong> <i id="inv-preview-amount-words" style="text-transform: uppercase;">-</i>
                                </div>
                                <div style="padding: 4px 8px; border-bottom: 1px solid #000; min-height: 60px;">
                                    <strong>Terms :</strong> 1. Subject to '<span id="inv-preview-shop-state-term"><?php echo !empty($shopState) ? $shopState : 'Gujarat'; ?></span>' Jurisdiction only. 2.(PAYMENT DUE TO 45 DAY ) <?php echo !empty($shopMsme) ? '('.$shopMsme.')' : ''; ?>
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
                                    <span>Discount (<span id="inv-preview-discount-percent">0</span>%)</span>
                                    <span id="inv-preview-discount">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000;">
                                    <span>Taxable Amount</span>
                                    <span id="inv-preview-taxable">0.00</span>
                                </div>
                                <div id="inv-preview-tax-breakdown">
                                    <!-- Populated dynamically via JS -->
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000;">
                                    <span>Total Tax</span>
                                    <span id="inv-preview-total-tax">0.00</span>
                                </div>
                                <!-- TDS/TCS row if any -->
                                <div id="inv-preview-tds-tcs-row" style="display: none; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000;">
                                    <span id="inv-preview-tds-tcs-label">TDS/TCS (0%)</span>
                                    <span id="inv-preview-tds-tcs-amount">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000; font-weight: bold;">
                                    <span>Net Amount</span>
                                    <span id="inv-preview-grand-total">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000; font-weight: bold;">
                                    <span>Round Amount</span>
                                    <span id="inv-preview-round-amount">0.00</span>
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
                                <div class="timeline" id="inv-preview-timeline" style="font-size: 11px;">
                                    <!-- Log Items -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 no-print">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnShareWhatsApp"><i class="fa-brands fa-whatsapp text-success"></i> Share</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnShareEmail"><i class="fa-regular fa-envelope text-primary"></i> Email</button>
                    <button type="button" class="btn btn-primary" onclick="downloadInvoicePDF();"><i class="fa-solid fa-download"></i> Download PDF</button>
                    <button type="button" class="btn btn-primary" onclick="window.print();"><i class="fa-solid fa-print"></i> Print Invoice</button>
                </div>
            </div>
        </div>
    </div>

    <!-- html2pdf JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <!-- Bootstrap 5 JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sales Bills JS Logic script -->
    <script src="assets/js/sales_bills.js?v=<?php echo time(); ?>"></script>
</body>
</html>
