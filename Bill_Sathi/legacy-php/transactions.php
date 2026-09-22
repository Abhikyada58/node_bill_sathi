<?php
/**
 * Finance ERP - Transaction Management Module
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
    <title>Transaction Management - Finsnce ERP</title>
    
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
        .badge-credit {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-debit {
            background: #fee2e2;
            color: #991b1b;
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
        .action-btn-circle.delete-btn:hover {
            border-color: #ef4444;
            color: #ef4444;
            background: #fee2e2;
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
        
        /* Modal Add Payment */
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
        .char-counter {
            font-size: 11px;
            color: #94a3b8;
            text-align: right;
            margin-top: 4px;
        }

        /* Nested pending bills table styling */
        .nested-bills-table {
            width: 100%;
            font-size: 12px;
            margin-top: 10px;
        }
        .nested-bills-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            padding: 8px;
            border-bottom: 1.5px solid #cbd5e1;
        }
        .nested-bills-table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .nested-bills-table input[type="number"] {
            width: 90px;
            padding: 4px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            font-size: 12px;
        }
        
        .nested-bills-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            padding: 12px;
            margin-top: 10px;
            max-height: 250px;
            overflow-y: auto;
        }
        
        .total-amount-summary {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            text-align: right;
            margin-top: 15px;
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
                    <a href="expenses.php" class="menu-item">
                        <i class="fa-solid fa-wallet"></i>
                        <span>Expense Tracker</span>
                    </a>
                    <a href="transactions.php" class="menu-item active">
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
                    <h1 class="page-title mb-0">Payments History</h1>
                </div>

                <!-- Main Card Panel -->
                <div class="main-card">
                    
                    <!-- Control Row -->
                    <div class="control-row">
                        <!-- Left controls: Limit entries & search -->
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center">
                                <span class="text-muted small me-2" style="white-space: nowrap;">Show</span>
                                <select id="paginationLimit" class="form-select form-select-sm" style="width: auto;" onchange="currentPage=1; loadPayments();">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center">
                                <input type="text" id="paymentsSearch" class="form-control form-control-sm" placeholder="Search" style="width: 200px; border-radius: 6px; border-color: #cbd5e1;" oninput="currentPage=1; loadPayments();">
                            </div>
                        </div>

                        <!-- Right actions: Export, Filters, Add Payment -->
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-light btn-sm px-3 py-2" style="border-radius: 6px; border: 1px solid #cbd5e1; color: #475569; font-size: 13px; background: #ffffff;" onclick="exportToExcel()" title="Export Excel">
                                <i class="fa-solid fa-file-excel text-success me-1"></i> Excel
                            </button>

                            <button type="button" class="btn btn-light btn-sm px-3 py-2 position-relative" style="border-radius: 6px; border: 1px solid #cbd5e1; color: #475569; font-size: 13px; background: #ffffff;" onclick="toggleFilterPanel()">
                                <i class="fa-solid fa-filter me-1"></i> Filters
                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle" id="filterBadge" style="display: none;"></span>
                            </button>

                            <button type="button" class="btn btn-primary btn-sm px-3 py-2" style="border-radius: 6px; background: #2563eb; border-color: #2563eb; font-weight: 700; font-size: 13px; text-transform: uppercase;" onclick="openAddPaymentModal()">
                                <i class="fa-solid fa-plus me-1"></i> Add New Payment
                            </button>
                        </div>
                    </div>

                    <!-- Collapsible Filter Drawer -->
                    <div id="filterDrawer" class="filter-panel">
                        <form id="filterForm" class="row g-3" onsubmit="event.preventDefault(); currentPage=1; loadPayments();">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold mb-1">Transaction Type</label>
                                <select id="filterType" class="form-select form-select-sm">
                                    <option value="">-- All Types --</option>
                                    <option value="CREDIT">CREDIT (Sales Receipts)</option>
                                    <option value="DEBIT">DEBIT (Purchase Payments)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold mb-1">Payment Mode</label>
                                <select id="filterMode" class="form-select form-select-sm">
                                    <option value="">-- All Modes --</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold mb-1">From Date</label>
                                <input type="date" id="filterDateFrom" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold mb-1">To Date</label>
                                <input type="date" id="filterDateTo" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 text-end mt-2">
                                <button type="button" class="btn btn-light btn-sm me-2" onclick="resetFilters()">Reset</button>
                                <button type="submit" class="btn btn-primary btn-sm px-3">Apply Filters</button>
                            </div>
                        </form>
                    </div>

                    <!-- Payments Table -->
                    <div class="table-responsive">
                        <table class="table erp-table align-middle" id="paymentsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Ref. Number</th>
                                    <th>Party Name</th>
                                    <th>Amount (₹)</th>
                                    <th>Type</th>
                                    <th>Transaction For</th>
                                    <th>Mode</th>
                                    <th>Note</th>
                                    <th class="text-end pe-4">Action</th>
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
                            <!-- Populated dynamically -->
                        </nav>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- ============================================
       MODALS
       ============================================ -->

    <!-- Modal: Add New Payment -->
    <div class="modal fade" id="modalAddPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-payment-card">
                <div class="modal-payment-header">
                    <h5 class="modal-payment-title">Record Payment Allocation</h5>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-payment-body">
                    <form id="paymentForm">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold mb-1" style="color: #475569;">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" id="formPaymentDate" class="form-control form-control-sm py-2" required style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label small fw-bold mb-1 d-block" style="color: #475569;">Transaction For <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group" aria-label="Transaction Type Radios">
                                    <input type="radio" class="btn-check" name="type" id="typeSales" value="Sales" checked onchange="onTypeChange()">
                                    <label class="btn btn-outline-primary btn-sm py-2" for="typeSales">Sales (Credit Receipt)</label>

                                    <input type="radio" class="btn-check" name="type" id="typePurchase" value="Purchase" onchange="onTypeChange()">
                                    <label class="btn btn-outline-primary btn-sm py-2" for="typePurchase">Purchase (Debit Payment)</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold mb-1" style="color: #475569;">Party Name <span class="text-danger">*</span></label>
                                <select name="party_id" id="formPartySelect" class="form-select form-select-sm py-2" required style="border-radius: 6px; border-color: #cbd5e1;" onchange="onPartyChange()">
                                    <option value="">-- Choose Party --</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold mb-1" style="color: #475569;">Payment Mode <span class="text-danger">*</span></label>
                                <select name="payment_mode" class="form-select form-select-sm py-2" required style="border-radius: 6px; border-color: #cbd5e1;">
                                    <option value="Cash" selected>Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold mb-1" style="color: #475569;">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control form-control-sm py-2" placeholder="Ref/Chq No." style="border-radius: 6px; border-color: #cbd5e1;">
                            </div>
                        </div>

                        <!-- Nested Pending Invoices Table Grid -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold mb-1 text-dark">Select Pending Bills to Allocate Payment</label>
                            <div class="nested-bills-card">
                                <table class="nested-bills-table" id="nestedBillsTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%;">Select</th>
                                            <th style="width: 20%;">Bill Number</th>
                                            <th style="width: 20%;">Bill Date</th>
                                            <th style="width: 15%;">Pending Balance</th>
                                            <th style="width: 15%;">Pay Amount</th>
                                            <th style="width: 15%;">Settle Amount</th>
                                            <th style="width: 10%;">Remaining</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Please select a party first.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="total-amount-summary">
                                Total Payment Amount: <span id="totalPaymentAmountSummary" class="text-primary">₹ 0.00</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold mb-1" style="color: #475569;">Payment Notes</label>
                            <textarea name="notes" id="formNotes" class="form-control form-control-sm" rows="3" placeholder="Add additional payment notes..." style="border-radius: 6px; border-color: #cbd5e1;" maxlength="250" oninput="updateNotesCharCounter(this)"></textarea>
                            <div class="char-counter" id="notesCharCounter">0 / 250</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                            <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal" style="font-weight: 700; font-size: 13px;">CANCEL</button>
                            <button type="submit" class="btn btn-primary px-4 py-2" style="background: #2563eb; border-color: #2563eb; font-weight: 700; font-size: 13px;">RECORD PAYMENT</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SheetJS for excel export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="assets/js/transactions.js"></script>
</body>
</html>
