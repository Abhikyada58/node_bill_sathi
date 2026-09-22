<?php
/**
 * Finance ERP - Expense Tracker Module (Bootstrap 5 & Blue & White Theme)
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
    <title>Expense Tracker - Finsnce ERP</title>
    
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
            border-bottom: 1px solid #e2e8f0;
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
        
        /* Segmented Tabs inside Form (Amount / Item) */
        .form-segmented-control {
            display: inline-flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-tab-btn {
            border: none;
            outline: none;
            padding: 6px 24px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            background: transparent;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .form-tab-btn.active {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        /* Attachment Box */
        .attachment-box {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s;
        }
        .attachment-box:hover {
            border-color: #2563eb;
            background: #eff6ff;
        }
        
        /* Note counter */
        .char-counter {
            font-size: 11px;
            color: #94a3b8;
            text-align: right;
            margin-top: 4px;
        }
        
        /* Items Grid */
        .items-grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .items-grid-table th {
            font-weight: 700;
            font-size: 12px;
            color: #475569;
            padding: 8px 12px;
            text-transform: none;
            border: none;
        }
        .items-grid-table td {
            background: #f8fafc;
            padding: 10px 12px;
            border: none;
        }
        .items-grid-table tr td:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }
        .items-grid-table tr td:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        .items-grid-table input {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13px;
            color: #1e293b;
            outline: none;
            width: 100%;
        }
        .items-grid-table input:focus {
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
        
        /* Inline payment logger */
        .payment-toggle-link {
            color: #2563eb;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 15px;
            cursor: pointer;
        }
        .payment-toggle-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }
        .payment-fields-block {
            display: none;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-top: 10px;
        }
        
        /* Standard action buttons */
        .btn-cancel-custom {
            background: #fee2e2;
            color: #ef4444;
            border: none;
            border-radius: 6px;
            padding: 10px 24px;
            font-weight: 700;
            font-size: 13px;
            transition: all 0.2s;
        }
        .btn-cancel-custom:hover {
            background: #fca5a5;
            color: #b91c1c;
        }
        .btn-save-new-custom {
            background: #334155;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
        }
        .btn-save-new-custom:hover {
            background: #1e293b;
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
                    <a href="products.php" class="menu-item">
                        <i class="fa-solid fa-box-open"></i>
                        <span>Product</span>
                    </a>
                    <a href="expenses.php" class="menu-item active">
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

                <!-- SECTION 1: EXPENSE LIST VIEW -->
                <div id="expense-list-panel" class="tab-content active">
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="page-title mb-0">Expense</h1>
                    </div>

                    <div class="main-card">
                        <!-- Control row -->
                        <div class="control-row">
                            <!-- Left controls (Limit entries dropdown & Search) -->
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center">
                                    <span class="text-muted small me-2" style="white-space: nowrap;">Show</span>
                                    <select id="paginationLimit" class="form-select form-select-sm" style="width: auto;" onchange="currentPage=1; loadExpenses();">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100" selected>100</option>
                                    </select>
                                </div>
                                <div class="d-flex align-items-center">
                                    <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Search" style="width: 200px; border-radius: 6px; border-color: #cbd5e1;" oninput="currentPage=1; loadExpenses();">
                                </div>
                            </div>

                            <!-- Right actions (Filter Toggle, Add Expense) -->
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-light btn-sm px-3 py-2 position-relative" style="border-radius: 6px; border: 1px solid #cbd5e1; color: #475569; font-size: 13px; background: #ffffff;" onclick="toggleFilterPanel()">
                                    <i class="fa-solid fa-filter me-1"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle" id="filterBadge" style="display: none;"></span>
                                </button>

                                <button type="button" class="btn btn-primary btn-sm px-3 py-2" style="border-radius: 6px; background: #2563eb; border-color: #2563eb; font-weight: 700; font-size: 13px; text-transform: uppercase;" onclick="toggleView('form')">
                                    <i class="fa-solid fa-plus me-1"></i> Add Expense
                                </button>
                            </div>
                        </div>

                        <!-- Collapsible Filter Panel -->
                        <div id="filterDrawer" class="filter-panel">
                            <form id="filterForm" class="row g-3" onsubmit="event.preventDefault(); currentPage=1; loadExpenses();">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold mb-1">Supplier</label>
                                    <select id="filterSupplier" class="form-select form-select-sm">
                                        <option value="">-- All Suppliers --</option>
                                        <!-- Loaded dynamically -->
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold mb-1">Category</label>
                                    <select id="filterCategory" class="form-select form-select-sm">
                                        <option value="">-- All Categories --</option>
                                        <option value="Raw Material">Raw Material</option>
                                        <option value="Salary">Salary</option>
                                        <option value="Rent">Rent</option>
                                        <option value="Service">Service</option>
                                        <option value="hostel fee">hostel fee</option>
                                        <option value="collej fees">collej fees</option>
                                        <option value="dhaga katig">dhaga katig</option>
                                        <option value="lon instolment">lon instolment</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold mb-1">Status</label>
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
                            <table class="table erp-table align-middle" id="expensesTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Supplier</th>
                                        <th>Amount (₹)</th>
                                        <th>Pending Amount</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                                <tfoot>
                                    <tr class="footer-total-row">
                                        <td id="tbl-total-count" class="ps-4">Total 0</td>
                                        <td colspan="2"></td>
                                        <td id="tbl-total-amount">0.00</td>
                                        <td id="tbl-total-pending" colspan="2">0.00</td>
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

                <!-- SECTION 2: ADD/EDIT EXPENSE SCREEN -->
                <div id="expense-form-panel" class="tab-content" style="display:none;">
                    <div class="d-flex align-items-center mb-4">
                        <a href="javascript:void(0)" onclick="toggleView('list')" class="text-decoration-none text-dark d-flex align-items-center gap-2">
                            <i class="fa-solid fa-arrow-left fs-4" style="color: #475569;"></i>
                            <h4 class="mb-0 fw-bold" style="color: #1e293b; font-size: 20px;" id="formViewTitle">Add Expense</h4>
                        </a>
                    </div>

                    <form id="expenseForm" class="modal-form">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="id" id="editExpenseId">
                        <input type="hidden" name="submit_action" id="formSubmitAction" value="save">

                        <div class="card p-4 border-0 shadow-sm mb-3" style="border-radius: 12px; background: #ffffff; border: 1px solid #e2e8f0 !important;">
                            
                            <!-- Segmented Tabs for Amount vs Itemized -->
                            <div class="form-segmented-control">
                                <button type="button" class="form-tab-btn active" id="btn-tab-amount" onclick="switchFormTab('amount')">Amount</button>
                                <button type="button" class="form-tab-btn" id="btn-tab-item" onclick="switchFormTab('item')">Item</button>
                            </div>

                            <div class="row g-4">
                                <!-- Date field -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 13px;">Expense Date <span class="text-danger">*</span></label>
                                    <input type="date" name="expense_date" id="formExpenseDate" class="form-control form-control-sm py-2" required style="border-radius: 6px; border-color: #cbd5e1;">
                                </div>

                                <!-- Expense Category select dropdown -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 13px;">Expense Category <span class="text-danger">*</span></label>
                                    <select name="category" id="formCategorySelect" class="form-select form-select-sm py-2" required style="border-radius: 6px; border-color: #cbd5e1;">
                                        <option value="">Expense Category</option>
                                        <option value="Raw Material">Raw Material</option>
                                        <option value="Salary">Salary</option>
                                        <option value="Rent">Rent</option>
                                        <option value="Service">Service</option>
                                        <option value="hostel fee">hostel fee</option>
                                        <option value="collej fees">collej fees</option>
                                        <option value="dhaga katig">dhaga katig</option>
                                        <option value="lon instolment">lon instolment</option>
                                        <option value="Office Expense">Office Expense</option>
                                        <option value="Utilities">Utilities</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <!-- Supplier dropdown select from parties -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 13px;">Supplier Name</label>
                                    <select name="supplier_id" id="formSupplierSelect" class="form-select form-select-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;">
                                        <option value="">Supplier</option>
                                        <!-- Loaded dynamically -->
                                    </select>
                                </div>

                                <!-- Amount container toggleable based on tab selection -->
                                <div class="col-md-6" id="formAmountInputContainer">
                                    <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 13px;">Amount (₹)<span class="text-danger">*</span></label>
                                    <input type="number" name="amount" id="formAmount" class="form-control form-control-sm py-2" step="0.01" placeholder="Enter Amount" required style="border-radius: 6px; border-color: #cbd5e1;">
                                </div>

                                <!-- Itemized grid list toggleable (Item tab) -->
                                <div class="col-12" id="formItemsTableContainer" style="display:none;">
                                    <h5 class="fw-bold mb-3" style="color: #0f172a; font-size: 14px;">Expense Items</h5>
                                    <div class="table-responsive">
                                        <table class="items-grid-table" id="formItemsTable">
                                            <thead>
                                                <tr>
                                                    <th style="width: 45%;">Item Description <span class="text-danger">*</span></th>
                                                    <th style="width: 15%;">Qty <span class="text-danger">*</span></th>
                                                    <th style="width: 15%;">Rate (₹) <span class="text-danger">*</span></th>
                                                    <th style="width: 20%;">Amount (₹)</th>
                                                    <th style="width: 5%;"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Dynamic rows -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-add-row" onclick="addExpenseItemRow();">
                                            <i class="fa-solid fa-plus me-1"></i> ADD ITEM
                                        </button>
                                    </div>
                                </div>

                                <!-- Add attachment upload area -->
                                <div class="col-md-6">
                                    <div class="attachment-box">
                                        <i class="fa-solid fa-cloud-arrow-up fs-2 text-primary mb-2"></i>
                                        <h6 class="mb-1 fw-bold text-dark" style="font-size: 13px;">Add Attachment</h6>
                                        <p class="text-muted small mb-0">JPG, JPEG, PNG</p>
                                        <input type="file" id="formAttachmentFile" style="display:none;" accept="image/*">
                                        <input type="hidden" name="attachment_path" id="formAttachmentPath">
                                    </div>
                                </div>

                                <!-- Notes textarea -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-1" style="color: #475569; font-size: 12px;">Add Note</label>
                                    <textarea name="notes" id="formNotes" class="form-control form-control-sm" rows="4" placeholder="Enter Note" style="border-radius: 6px; border-color: #cbd5e1;" maxlength="200" oninput="updateCharCounter(this);"></textarea>
                                    <div class="char-counter" id="notesCharCounter">0 / 200</div>
                                </div>

                                <!-- Add Payment toggler -->
                                <div class="col-12">
                                    <div class="payment-toggle-link" onclick="toggleInlinePaymentBlock();">
                                        <i class="fa-solid fa-hand-holding-dollar"></i>
                                        <span id="paymentLinkText">Add Payment</span>
                                    </div>
                                    
                                    <div class="payment-fields-block" id="inlinePaymentBlock">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label small fw-bold mb-1">Payment Date</label>
                                                <input type="date" name="payment_date" id="inlinePaymentDate" class="form-control form-control-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small fw-bold mb-1">Amount Paid (₹)</label>
                                                <input type="number" name="paid_amount" id="inlinePaymentAmount" class="form-control form-control-sm py-2" step="0.01" placeholder="0.00" style="border-radius: 6px; border-color: #cbd5e1;">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small fw-bold mb-1">Payment Mode</label>
                                                <select name="payment_mode" class="form-select form-select-sm py-2" style="border-radius: 6px; border-color: #cbd5e1;">
                                                    <option value="Cash">Cash</option>
                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                    <option value="UPI">UPI</option>
                                                    <option value="Cheque">Cheque</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form actions footer -->
                                <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                                    <button type="button" class="btn btn-save-new-custom px-4 py-2" onclick="submitExpenseForm('save_and_new')">
                                        SAVE & CREATE NEW
                                    </button>
                                    <button type="button" class="btn-cancel-custom px-4 py-2" onclick="toggleView('list')">
                                        CANCEL
                                    </button>
                                    <button type="button" class="btn btn-primary px-4 py-2 fw-bold text-uppercase" style="border-radius: 6px; font-size: 13px; background: #2563eb; border-color: #2563eb;" onclick="submitExpenseForm('save')">
                                        SUBMIT
                                    </button>
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
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="expense_id" id="paymentExpenseId">
                        
                        <!-- Metadata row -->
                        <div class="payment-meta-row">
                            <span class="payment-meta-label">Category</span>
                            <span class="payment-meta-value" id="pay-meta-category">-</span>
                        </div>
                        <div class="payment-meta-row mb-3 pb-3 border-bottom">
                            <span class="payment-meta-label">Supplier</span>
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
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold" style="color: #475569; font-size: 13px;">Note</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Enter Note" style="border-radius: 6px; border-color: #cbd5e1;"></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn-save-payment py-2 fw-bold text-uppercase">SAVE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: View Details -->
    <div class="modal fade" id="modalViewExpense" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content card border-0 shadow-lg" style="border-radius: 12px; background: #ffffff;">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title font-weight-bold" style="color: #0f172a; font-weight: 700;">Expense Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="viewExpenseContent">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Page Script controller -->
    <script src="assets/js/expenses.js"></script>
</body>
</html>
