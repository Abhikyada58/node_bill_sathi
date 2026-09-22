<?php
/**
 * Finance ERP Dashboard - Main Layout View
 * Session-protected
 */

require_once __DIR__ . '/auth/session_check.php';
require_once __DIR__ . '/config/database.php';

$shopName = '';
$shopAddress = '';
$shopState = '';
$shopMobile = '';
$shopGstin = '';
$shopPan = '';
$shopMsme = '';
$bankName = '';
$bankAccNo = '';
$bankAccType = '';
$bankIfsc = '';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT u.full_name, ui.* FROM users u LEFT JOIN user_information ui ON u.id = ui.user_id WHERE u.id = :id LIMIT 1");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $userName = htmlspecialchars($user['full_name']);
        $shopName = htmlspecialchars($user['shop_name'] ?? '');
        $shopAddress = htmlspecialchars($user['shop_address'] ?? '');
        $shopState = htmlspecialchars($user['shop_state'] ?? '');
        $shopMobile = htmlspecialchars($user['shop_mobile'] ?? '');
        $shopGstin = htmlspecialchars($user['shop_gstin'] ?? '');
        $shopPan = htmlspecialchars($user['shop_pan'] ?? '');
        $shopMsme = htmlspecialchars($user['shop_msme'] ?? '');
        $bankName = htmlspecialchars($user['bank_name'] ?? '');
        $bankAccNo = htmlspecialchars($user['bank_acc_no'] ?? '');
        $bankAccType = htmlspecialchars($user['bank_acc_type'] ?? '');
        $bankIfsc = htmlspecialchars($user['bank_ifsc'] ?? '');
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
    <title>ERP Dashboard - Finance Manager</title>
    
    <!-- Bootstrap 5 & FontAwesome CDNs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sales_bills.css">
    <style>
        /* Precision alignment to match the screenshot UI within the main dashboard */
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
                    <a data-target="dashboard" class="menu-item active">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span data-i18n="menu_dashboard">Dashboard</span>
                    </a>
                    <a href="sales_bills.php" class="menu-item">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span data-i18n="menu_sales">Sales Bill</span>
                    </a>
                    <a href="purchase_bills.php" class="menu-item">
                        <i class="fa-solid fa-receipt"></i>
                        <span data-i18n="menu_purchase">Purchase Bill</span>
                    </a>
                    <a data-target="delivery-challan" class="menu-item">
                        <i class="fa-solid fa-truck"></i>
                        <span data-i18n="menu_challan">Delivery Challan</span>
                    </a>
                    <a data-target="manage-firm" class="menu-item">
                        <i class="fa-solid fa-building"></i>
                        <span data-i18n="menu_firm">Manage Firm</span>
                    </a>
                    <a href="parties.php" class="menu-item">
                        <i class="fa-solid fa-users"></i>
                        <span data-i18n="menu_party">Manage Party</span>
                    </a>
                    <a href="products.php" class="menu-item">
                        <i class="fa-solid fa-box-open"></i>
                        <span data-i18n="menu_product">Product</span>
                    </a>
                    <a href="expenses.php" class="menu-item">
                        <i class="fa-solid fa-wallet"></i>
                        <span data-i18n="menu_expense">Expense Tracker</span>
                    </a>
                    <a href="transactions.php" class="menu-item">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        <span data-i18n="menu_transaction">Transaction</span>
                    </a>
                    <a data-target="reports" class="menu-item">
                        <i class="fa-solid fa-chart-column"></i>
                        <span data-i18n="menu_reports">Reports</span>
                    </a>
                    <a data-target="settings" class="menu-item">
                        <i class="fa-solid fa-gears"></i>
                        <span data-i18n="menu_settings">Setting</span>
                    </a>
                </nav>
            </div>
            
            <div class="sidebar-user">
                <div class="user-avatar" title="<?php echo $userName; ?>">
                    <?php echo $userInitials; ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo $userName; ?></span>
                    <span class="user-role" data-i18n="role_admin">Administrator</span>
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
                            <option value="gu">ગુજરાતી</option>
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

            <!-- Dashboard Content Bodies -->
            <main class="content-body">

                <!-- 1. DASHBOARD TAB VIEW -->
                <div id="tab-dashboard" class="tab-content active">
                    <div class="welcome-banner">
                        <div class="welcome-title">
                            <h2>Hello, <?php echo $userName; ?>!</h2>
                            <p>Here's a dynamic overview of your Enterprise Resource Planning metrics.</p>
                        </div>
                        <div class="date-badge">
                            <i class="fa-regular fa-calendar-days"></i>
                            <span><?php echo date('F j, Y'); ?></span>
                        </div>
                    </div>

                    <!-- Statistics grid (6 stats) -->
                    <section class="stats-grid">
                        <div class="stat-card sales">
                            <div class="stat-info">
                                <span class="stat-label">Total Sales</span>
                                <span class="stat-value" id="stat-total-sales">$0.00</span>
                            </div>
                            <div class="stat-icon-wrapper"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        </div>

                        <div class="stat-card purchase">
                            <div class="stat-info">
                                <span class="stat-label">Total Purchase</span>
                                <span class="stat-value" id="stat-total-purchase">$0.00</span>
                            </div>
                            <div class="stat-icon-wrapper"><i class="fa-solid fa-receipt"></i></div>
                        </div>

                        <div class="stat-card expense">
                            <div class="stat-info">
                                <span class="stat-label">Total Expenses</span>
                                <span class="stat-value" id="stat-total-expenses">$0.00</span>
                            </div>
                            <div class="stat-icon-wrapper"><i class="fa-solid fa-circle-arrow-up"></i></div>
                        </div>

                        <div class="stat-card profit">
                            <div class="stat-info">
                                <span class="stat-label">Total Profit</span>
                                <span class="stat-value" id="stat-total-profit">$0.00</span>
                            </div>
                            <div class="stat-icon-wrapper"><i class="fa-solid fa-chart-line"></i></div>
                        </div>

                        <div class="stat-card pending">
                            <div class="stat-info">
                                <span class="stat-label">Pending Payments</span>
                                <span class="stat-value" id="stat-pending-payments">$0.00</span>
                            </div>
                            <div class="stat-icon-wrapper"><i class="fa-solid fa-clock"></i></div>
                        </div>

                        <div class="stat-card revenue">
                            <div class="stat-info">
                                <span class="stat-label">Monthly Revenue</span>
                                <span class="stat-value" id="stat-monthly-revenue">$0.00</span>
                            </div>
                            <div class="stat-icon-wrapper"><i class="fa-solid fa-sack-dollar"></i></div>
                        </div>
                    </section>

                    <!-- Module Cards Grid (6 Modules) -->
                    <section style="display: flex; flex-direction: column; gap: var(--space-4);">
                        <h3 style="font-weight: 700;">ERP Module Control Center</h3>
                        <div class="modules-grid">
                            <!-- Sales Bill Module -->
                            <div class="module-card">
                                <div class="module-header">
                                    <div class="module-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                                    <span class="module-title">Sales Bill</span>
                                </div>
                                <p class="module-description">Generate client invoices, track product deliveries, manage partial bills, and verify pending customer GST payments.</p>
                                <div class="module-actions">
                                    <button class="module-btn primary btn-manage-module" data-module="sales-bill">Manage</button>
                                    <button class="module-btn secondary btn-open-module" data-module="sales-bill">Open</button>
                                    <button class="module-btn accent btn-add-module" data-module="sales-bill">Add New</button>
                                </div>
                            </div>

                            <!-- Purchase Bill Module -->
                            <div class="module-card">
                                <div class="module-header">
                                    <div class="module-icon"><i class="fa-solid fa-receipt"></i></div>
                                    <span class="module-title">Purchase Bill</span>
                                </div>
                                <p class="module-description">Log supplier invoices, manage bulk stock purchases, update local inventories, and record outgoing payouts.</p>
                                <div class="module-actions">
                                    <button class="module-btn primary btn-manage-module" data-module="purchase-bill">Manage</button>
                                    <button class="module-btn secondary btn-open-module" data-module="purchase-bill">Open</button>
                                    <button class="module-btn accent btn-add-module" data-module="purchase-bill">Add New</button>
                                </div>
                            </div>

                            <!-- Delivery Challan Module -->
                            <div class="module-card">
                                <div class="module-header">
                                    <div class="module-icon"><i class="fa-solid fa-truck"></i></div>
                                    <span class="module-title">Delivery Challan</span>
                                </div>
                                <p class="module-description">Create dispatch receipts, allocate transportation logs, manage warehouse quantities, and confirm buyer shipments.</p>
                                <div class="module-actions">
                                    <button class="module-btn primary btn-manage-module" data-module="delivery-challan">Manage</button>
                                    <button class="module-btn secondary btn-open-module" data-module="delivery-challan">Open</button>
                                    <button class="module-btn accent btn-add-module" data-module="delivery-challan">Add New</button>
                                </div>
                            </div>

                            <!-- Payment Module -->
                            <div class="module-card">
                                <div class="module-header">
                                    <div class="module-icon"><i class="fa-solid fa-money-bill-transfer"></i></div>
                                    <span class="module-title">Payment</span>
                                </div>
                                <p class="module-description">Record incoming client fees, outgoing manufacturer checks, cash deposits, and electronic bank transfer updates.</p>
                                <div class="module-actions">
                                    <button class="module-btn primary btn-manage-module" data-module="transactions">Manage</button>
                                    <button class="module-btn secondary btn-open-module" data-module="transactions">Open</button>
                                    <button class="module-btn accent btn-add-module" data-module="transactions">Add New</button>
                                </div>
                            </div>

                            <!-- Expense Tracker Module -->
                            <div class="module-card">
                                <div class="module-header">
                                    <div class="module-icon"><i class="fa-solid fa-circle-arrow-up"></i></div>
                                    <span class="module-title">Expense Tracker</span>
                                </div>
                                <p class="module-description">Track corporate overheads, office leasing contracts, employee utility packages, and server subscription receipts.</p>
                                <div class="module-actions">
                                    <button class="module-btn primary btn-manage-module" data-module="expenses">Manage</button>
                                    <button class="module-btn secondary btn-open-module" data-module="expenses">Open</button>
                                    <button class="module-btn accent btn-add-module" data-module="expenses">Add New</button>
                                </div>
                            </div>

                            <!-- Reports Module -->
                            <div class="module-card">
                                <div class="module-header">
                                    <div class="module-icon"><i class="fa-solid fa-chart-column"></i></div>
                                    <span class="module-title">Reports</span>
                                </div>
                                <p class="module-description">Generate comprehensive daily spreadsheets, year-end sheets, profit margins, and export standard Excel or PDF receipts.</p>
                                <div class="module-actions">
                                    <button class="module-btn primary btn-manage-module" data-module="reports">Manage</button>
                                    <button class="module-btn secondary btn-open-module" data-module="reports">Open</button>
                                    <button class="module-btn accent btn-add-module" data-module="reports">Add New</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Chart Line Area -->
                    <section class="dashboard-card" style="margin-top: var(--space-2);">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa-solid fa-chart-line"></i> Sales & Expenses Analytics</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="spendingChart"></canvas>
                        </div>
                    </section>
                </div>

                <!-- 2. SALES BILL TAB VIEW (Upgraded to match screenshot layout exactly) -->
                <div id="tab-sales-bill" class="tab-content">
                    <div id="sales-bill-list-panel" class="sales-bill-sub-view active">
                        <div class="main-card">
                            <!-- Top Tabs -->
                            <div class="segmented-tabs">
                                <button class="tab-btn active" id="tab-all-bills">All</button>
                                <button class="tab-btn" id="tab-tax-invoices">Tax Invoice</button>
                                <button class="tab-btn" id="tab-job-challans">Job Challan</button>
                            </div>

                            <!-- Action Control Line -->
                            <div class="control-row">
                                <div class="d-flex align-items-center">
                                    <span class="text-muted small me-2">Show</span>
                                    <select id="paginationLimit" class="form-select form-select-sm" style="width: auto;">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100" selected>100</option>
                                    </select>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="action-btn-circle" style="width: 32px; height: 32px;" onclick="toggleFilterPanel()" title="Toggle Filter Panel">
                                        <i class="fa-solid fa-filter"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm fw-bold px-3 d-flex align-items-center gap-2" style="border-radius:6px; border-color:#cbd5e1; color:#166534;" onclick="alert('Export Excel completed!')">
                                        <i class="fa-solid fa-file-excel"></i> Export Excel
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm fw-bold px-3 d-flex align-items-center gap-2" style="border-radius:6px; background:#2563eb; border-color:#2563eb;" onclick="toggleSalesFormView('form')">
                                        <i class="fa-solid fa-plus"></i> ADD BILL
                                    </button>
                                </div>
                            </div>

                            <!-- Filter drawer -->
                            <div class="filter-panel" id="filterDrawer">
                                <form id="filterForm" class="row g-2" onsubmit="event.preventDefault(); loadSalesBillsList();">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold mb-1">Search</label>
                                        <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Search bill no, customer...">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold mb-1">Customer</label>
                                        <select id="filterCustomer" class="form-select form-select-sm">
                                            <option value="">-- All Customers --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold mb-1">Status</label>
                                        <select id="filterStatus" class="form-select form-select-sm">
                                            <option value="">-- Choose Status --</option>
                                            <option value="PAID">PAID</option>
                                            <option value="UNPAID">UNPAID</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" id="filterDateFrom" class="form-control form-control-sm" placeholder="From Date">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" id="filterDateTo" class="form-control form-control-sm" placeholder="To Date">
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
                                        <!-- Dynamic Rows -->
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

                            <!-- Pagination -->
                            <div class="d-flex justify-content-between align-items-center py-3 px-4 bg-white border-top">
                                <span class="text-muted small" id="paginationInfo">Showing 0 to 0 of 0 entries</span>
                                <nav id="paginationNav">
                                    <!-- Pagination links -->
                                </nav>
                            </div>
                        </div>
                    </div>

                    <!-- Add Sales Bill Form view -->
                    <div id="sales-bill-form-panel" class="sales-bill-sub-view d-none">
                        <div class="d-flex align-items-center mb-3">
                            <button type="button" class="btn btn-link btn-sm ps-0 text-decoration-none" onclick="toggleSalesFormView('list')">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back to registry
                            </button>
                        </div>

                        <form id="createBillForm" class="modal-form">
                            <input type="hidden" name="action" value="create">

                            <div class="row g-4">
                                <div class="col-lg-9">
                                    <div class="card p-4 mb-4">
                                        <h5 class="h6 mb-3 font-weight-bold text-primary border-bottom pb-2">Party & Invoice Details</h5>
                                        <div class="row g-3">
                                            <div class="col-md-5">
                                                <label class="form-label font-weight-bold">Select Party (Customer) <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <select name="customer_id" id="formCustomerSelect" class="form-select" required>
                                                        <option value="">-- Choose Party --</option>
                                                    </select>
                                                    <button type="button" class="btn btn-outline-primary" onclick="openCustomerModal()"><i class="fa-solid fa-plus"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end justify-content-center">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="apply_gst" id="formApplyGst" checked onchange="calculateGrandTotal();">
                                                    <label class="form-check-label font-weight-bold" for="formApplyGst">Apply GST</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label font-weight-bold">Bill Date <span class="text-danger">*</span></label>
                                                <input type="date" name="bill_date" id="formBillDate" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" required onchange="calculateDueDate();">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label font-weight-bold">Due Days</label>
                                                <input type="number" name="due_days" id="formDueDays" class="form-control form-control-sm" value="30" min="0" oninput="calculateDueDate();">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label font-weight-bold">Due Date</label>
                                                <input type="date" id="formDueDate" class="form-control form-control-sm bg-light" readonly>
                                            </div>

                                            <div class="col-md-5">
                                                <label class="form-label font-weight-bold">Delivery Challan No.</label>
                                                <input type="text" name="challan_no" class="form-control form-control-sm" placeholder="CHL-YYYY-xxx">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label font-weight-bold">Challan Date</label>
                                                <input type="date" name="challan_date" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card p-4 mb-4">
                                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                            <h5 class="h6 font-weight-bold text-primary mb-0">Product line items</h5>
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addProductRow()"><i class="fa-solid fa-plus me-1"></i> Add Product Row</button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table product-table" id="itemsTable">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 35%;">Product Name <span class="text-danger">*</span></th>
                                                        <th style="width: 12%;">Item Code</th>
                                                        <th style="width: 10%;">HSN Code</th>
                                                        <th style="width: 10%;">Qty <span class="text-danger">*</span></th>
                                                        <th style="width: 10%;">Unit</th>
                                                        <th style="width: 12%;">Rate ($) <span class="text-danger">*</span></th>
                                                        <th style="width: 11%; text-align:right;">Amount ($)</th>
                                                        <th style="width: 5%;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Dynamic Rows -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="card p-4">
                                        <h5 class="h6 mb-3 font-weight-bold text-primary border-bottom pb-2">Remarks / Notes</h5>
                                        <div>
                                            <textarea name="remarks" class="form-control" rows="3" placeholder="Add comments here..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3">
                                    <div class="card summary-panel p-4 position-sticky" style="top: 85px;">
                                        <h5 class="h6 mb-3 border-bottom pb-2 font-weight-bold text-primary">Summary Panel</h5>
                                        
                                        <div class="summary-row">
                                            <span>Total Qty:</span>
                                            <span id="summaryTotalQty">0</span>
                                        </div>
                                        <div class="summary-row">
                                            <span>Gross Amount:</span>
                                            <span id="summaryGrossAmount">$0.00</span>
                                        </div>
                                        <div class="summary-row">
                                            <div class="d-flex align-items-center gap-1">
                                                <span>Disc. (%)</span>
                                                <input type="number" name="discount_percent" id="formDiscountPercent" class="form-control form-control-sm" style="width: 60px; padding: 2px 4px;" value="0" min="0" max="100" oninput="calculateGrandTotal();">
                                            </div>
                                            <span id="summaryDiscountAmount">-$0.00</span>
                                        </div>
                                        <div class="summary-row">
                                            <span>Taxable Amount:</span>
                                            <span id="summaryTaxableAmount">$0.00</span>
                                        </div>
                                        <div class="summary-row">
                                            <div class="d-flex align-items-center gap-1">
                                                <span>GST (%)</span>
                                                <select name="gst_percent" id="formGstPercent" class="form-select form-select-sm" style="width: 70px; padding: 2px 4px;" onchange="calculateGrandTotal();">
                                                    <option value="0">0%</option>
                                                    <option value="5">5%</option>
                                                    <option value="12">12%</option>
                                                    <option value="18" selected>18%</option>
                                                    <option value="28">28%</option>
                                                </select>
                                            </div>
                                            <span id="summaryGstAmount">$0.00</span>
                                        </div>
                                        <div class="summary-row">
                                            <span>Grand Total:</span>
                                            <span id="summaryGrandTotal" class="text-primary font-weight-bold">$0.00</span>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100 py-2 mt-4 font-weight-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Save Sales Bill</button>
                                        <button type="button" class="btn btn-outline-secondary w-100 mt-2 btn-sm" onclick="toggleSalesFormView('list')">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 3. PURCHASE BILL TAB VIEW -->
                <div id="tab-purchase-bill" class="tab-content">
                    <div class="table-card">
                        <div class="table-header">
                            <span class="table-title">Purchase Bill Registry</span>
                            <div class="table-actions">
                                <button class="module-btn primary" onclick="openCreateModal('transaction', 'Purchase')">Add New Purchase Bill</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="erp-table" id="purchase-bill-table">
                                <thead>
                                    <tr>
                                        <th>Bill No.</th>
                                        <th>Supplier</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 4. DELIVERY CHALLAN TAB VIEW -->
                <div id="tab-delivery-challan" class="tab-content">
                    <div class="table-card">
                        <div class="table-header">
                            <span class="table-title">Delivery Challan Registry</span>
                            <div class="table-actions">
                                <button class="module-btn primary" onclick="alert('Challan setup module linked!')">Add Delivery Slip</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="erp-table">
                                <thead>
                                    <tr>
                                        <th>Challan No.</th>
                                        <th>Client Name</th>
                                        <th>Veh. Number</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>CHL-2026-001</td>
                                        <td>Acme Corporation</td>
                                        <td>DL-3CA-4521</td>
                                        <td>June 10, 2026</td>
                                        <td><span class="badge success">Dispatched</span></td>
                                    </tr>
                                    <tr>
                                        <td>CHL-2026-002</td>
                                        <td>Beta Retailers</td>
                                        <td>MH-12EQ-8902</td>
                                        <td>June 8, 2026</td>
                                        <td><span class="badge success">Delivered</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 5. CUSTOMERS TAB VIEW -->
                <div id="tab-customers" class="tab-content">
                    <div class="table-card">
                        <div class="table-header">
                            <span class="table-title">Customer Database</span>
                            <div class="table-actions">
                                <div class="search-box">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" id="customerSearch" placeholder="Search Customer...">
                                </div>
                                <button class="module-btn primary" onclick="openCreateModal('customer')">Add Customer</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="erp-table" id="customers-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>GSTIN</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-container" id="customers-pagination"></div>
                    </div>
                </div>

                <!-- 6. SUPPLIERS TAB VIEW -->
                <div id="tab-suppliers" class="tab-content">
                    <div class="table-card">
                        <div class="table-header">
                            <span class="table-title">Supplier Database</span>
                            <div class="table-actions">
                                <div class="search-box">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" id="supplierSearch" placeholder="Search Supplier...">
                                </div>
                                <button class="module-btn primary" onclick="openCreateModal('supplier')">Add Supplier</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="erp-table" id="suppliers-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>GSTIN</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-container" id="suppliers-pagination"></div>
                    </div>
                </div>

                <!-- 7. PRODUCTS TAB VIEW -->
                <div id="tab-products" class="tab-content">
                    <div class="table-card">
                        <div class="table-header">
                            <span class="table-title">Product & Stock Inventory</span>
                            <div class="table-actions">
                                <div class="search-box">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" id="productSearch" placeholder="Search Product...">
                                </div>
                                <button class="module-btn primary" onclick="openCreateModal('product')">Add Product</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="erp-table" id="products-table">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock Quantity</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-container" id="products-pagination"></div>
                    </div>
                </div>

                <!-- 8. INCOME TAB VIEW -->
                <div id="tab-income" class="tab-content">
                    <div class="table-card">
                        <div class="table-header">
                            <span class="table-title">Revenue & Inflow Logs</span>
                            <button class="module-btn primary" onclick="openCreateModal('transaction', 'Sale')">Add Income Log</button>
                        </div>
                        <div class="table-responsive">
                            <table class="erp-table" id="income-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Description</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 9. EXPENSES TAB VIEW -->
                <div id="tab-expenses" class="tab-content">
                    <div class="table-card">
                        <div class="table-header">
                            <span class="table-title">Corporate Expenses Registry</span>
                            <button class="module-btn primary" onclick="openCreateModal('transaction', 'Expense')">Add Expense</button>
                        </div>
                        <div class="table-responsive">
                            <table class="erp-table" id="expenses-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category/Desc</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 10. TRANSACTIONS TAB VIEW -->
                <div id="tab-transactions" class="tab-content">
                    <div class="table-card">
                        <div class="table-header">
                            <span class="table-title">Unified General Ledger</span>
                            <div class="table-actions">
                                <div class="search-box">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" id="transactionSearch" placeholder="Search ledger...">
                                </div>
                                <div style="display:flex; gap:8px;">
                                    <input type="date" id="dateFilterFrom" class="form-input" style="padding: 6px; width:130px;">
                                    <input type="date" id="dateFilterTo" class="form-input" style="padding: 6px; width:130px;">
                                    <button class="module-btn secondary" id="btnApplyDateFilter">Filter</button>
                                </div>
                                <button class="module-btn primary" onclick="openCreateModal('transaction')">Record Entry</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="erp-table" id="transactions-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-container" id="transactions-pagination"></div>
                    </div>
                </div>

                <!-- 11. REPORTS TAB VIEW -->
                <div id="tab-reports" class="tab-content">
                    <div class="welcome-banner">
                        <div class="welcome-title">
                            <h2>Corporate Reports Center</h2>
                            <p>Generate financial statements and audit sheets for local filing.</p>
                        </div>
                    </div>

                    <div class="report-controls">
                        <button class="report-btn active" data-report="profit_loss">Profit & Loss Sheet</button>
                        <button class="report-btn" data-report="daily">Daily Breakdown</button>
                        <button class="report-btn" data-report="monthly">Monthly Audit</button>
                        <button class="report-btn" data-report="yearly">Yearly Breakdown</button>
                        <button class="report-btn" data-report="expense">Overhead Analysis</button>
                    </div>

                    <div class="table-card">
                        <div class="table-header">
                            <span class="table-title" id="report-title-label">Profit & Loss Statement</span>
                            <div class="table-actions">
                                <button class="module-btn secondary" onclick="alert('Export Excel completed.')"><i class="fa-solid fa-file-excel"></i> Export Excel</button>
                                <button class="module-btn primary" onclick="alert('Export PDF completed.')"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
                            </div>
                        </div>
                        <div class="table-responsive" id="reports-output-container" style="padding: var(--space-4);"></div>
                    </div>
                </div>

                <!-- 12. SETTINGS TAB VIEW -->
                <div id="tab-settings" class="tab-content">
                    <div class="dashboard-card" style="max-width: 600px;">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa-solid fa-user-gear"></i> System Settings</h3>
                        </div>
                        <form id="profileForm" style="display:flex; flex-direction:column; gap: var(--space-4); margin-top: var(--space-2);">
                            <div>
                                <label class="form-label">Administrator Account Name</label>
                                <input type="text" name="full_name" class="form-input" value="<?php echo $userName; ?>" required>
                            </div>
                            <div>
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-input" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" disabled>
                            </div>
                            
                            <hr style="margin: 10px 0; border-color: #e2e8f0;">
                            <h4 style="font-size: 16px; font-weight: 600; color: #1e293b; margin: 0;">Shop Details</h4>
                            
                            <div>
                                <label class="form-label">Shop Name</label>
                                <input type="text" name="shop_name" class="form-input" value="<?php echo $shopName; ?>" placeholder="Enter Shop Name">
                            </div>
                            <div>
                                <label class="form-label">Shop Mobile</label>
                                <input type="text" name="shop_mobile" class="form-input" value="<?php echo $shopMobile; ?>" placeholder="Enter Shop Mobile">
                            </div>
                            <div>
                                <label class="form-label">Shop Address</label>
                                <textarea name="shop_address" class="form-input" rows="2" placeholder="Enter Shop Address" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 14px; width: 100%; box-sizing: border-box; resize: vertical;"><?php echo $shopAddress; ?></textarea>
                            </div>
                            <div>
                                <label class="form-label">Shop State</label>
                                <input type="text" name="shop_state" class="form-input" value="<?php echo $shopState; ?>" placeholder="Enter Shop State">
                            </div>
                            <div>
                                <label class="form-label">Shop GSTIN</label>
                                <input type="text" name="shop_gstin" class="form-input" value="<?php echo $shopGstin; ?>" placeholder="Enter Shop GSTIN">
                            </div>
                            <div>
                                <label class="form-label">Shop PAN</label>
                                <input type="text" name="shop_pan" class="form-input" value="<?php echo $shopPan; ?>" placeholder="Enter Shop PAN">
                            </div>
                            <div>
                                <label class="form-label">Shop MSME No</label>
                                <input type="text" name="shop_msme" class="form-input" value="<?php echo $shopMsme; ?>" placeholder="Enter Shop MSME No">
                            </div>
                            
                            <hr style="margin: 10px 0; border-color: #e2e8f0;">
                            <h4 style="font-size: 16px; font-weight: 600; color: #1e293b; margin: 0;">Bank Details</h4>
                            
                            <div>
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-input" value="<?php echo $bankName; ?>" placeholder="Enter Bank Name">
                            </div>
                            <div>
                                <label class="form-label">Bank A/c No</label>
                                <input type="text" name="bank_acc_no" class="form-input" value="<?php echo $bankAccNo; ?>" placeholder="Enter Account Number">
                            </div>
                            <div>
                                <label class="form-label">Account Type</label>
                                <input type="text" name="bank_acc_type" class="form-input" value="<?php echo $bankAccType; ?>" placeholder="Enter Account Type (e.g. Current, Savings)">
                            </div>
                            <div>
                                <label class="form-label">Bank IFSC</label>
                                <input type="text" name="bank_ifsc" class="form-input" value="<?php echo $bankIfsc; ?>" placeholder="Enter Bank IFSC Code">
                            </div>
                            
                            <div style="margin-top: 10px;">
                                <button type="submit" class="module-btn primary" style="width: 140px; padding: 10px;">Save Profile</button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- ============================================
       MODALS FOR CRUD OPERATIONS
       ============================================ -->

    <!-- Modal: General Transaction (create/edit) -->
    <div class="modal-overlay" id="modal-transaction">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modal-transaction-title">Record Transaction</h3>
                <span class="modal-close" onclick="closeModal('transaction')"><i class="fa-solid fa-xmark"></i></span>
            </div>
            <div class="modal-body">
                <form class="modal-form" id="form-transaction">
                    <input type="hidden" name="action" id="action-transaction" value="create">
                    <input type="hidden" name="id" id="id-transaction" value="">
                    
                    <div>
                        <label class="form-label">Type</label>
                        <select name="type" id="type-transaction" class="form-input" required>
                            <option value="Sale">Sale</option>
                            <option value="Purchase">Purchase</option>
                            <option value="Expense">Expense</option>
                            <option value="Payment">Payment</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" id="amount-transaction" class="form-input" placeholder="0.00" min="0.01" required>
                    </div>
                    <div>
                        <label class="form-label">Date</label>
                        <input type="date" name="date" id="date-transaction" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Description / Remarks</label>
                        <input type="text" name="description" id="description-transaction" class="form-input" placeholder="Remarks...">
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="module-btn secondary" onclick="closeModal('transaction')">Cancel</button>
                        <button type="submit" class="module-btn primary">Save Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Record Sales Invoice Payment -->
    <div class="modal fade" id="modalPayment" tabindex="-1" aria-hidden="true" style="z-index: 1050;">
        <div class="modal-dialog">
            <div class="modal-content card border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title font-weight-bold" id="paymentModalTitle">Record Incoming Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="recordPaymentForm">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="sales_bill_id" id="paymentBillId">
                        
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Payment Mode</label>
                            <select name="payment_mode" class="form-select" required>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="UPI">UPI</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Reference Number</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="TXN-xxxxxx">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Amount Received ($)</label>
                            <input type="number" step="0.01" name="amount" id="paymentMaxAmount" class="form-control" placeholder="0.00" min="0.01" required>
                            <div class="form-text text-danger" id="paymentMaxWarning"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Add New Customer -->
    <div class="modal fade" id="modalCustomer" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog">
            <div class="modal-content card border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title font-weight-bold">Quick Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="quickAddCustomerForm">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Customer Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Company Name..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="billing@acme.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="98765...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Billing Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">GST Number</label>
                            <input type="text" name="gst_number" class="form-control" placeholder="15-character GSTIN" maxlength="15">
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Party</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: View Tax Invoice Preview -->
    <div class="modal fade" id="modalInvoice" tabindex="-1" aria-hidden="true" style="z-index: 1040;">
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
                    <div class="classic-invoice-box" style="border: 2px solid #000; padding: 20px; font-family: Arial, sans-serif; color: #000; font-size: 13px; background: #fff;">
                        
                        <!-- Shop header -->
                        <div class="text-center" style="border: 2px solid #000; padding: 10px; margin-bottom: 12px; background: #fff;">
                            <h2 style="margin: 0; font-size: 26px; font-weight: bold; letter-spacing: 1.5px; color: #000; text-transform: uppercase;" id="inv-preview-shop-name"><?php echo !empty($shopName) ? $shopName : 'Finsnce Corp'; ?></h2>
                        </div>
                        
                        <!-- Shop Info Row -->
                        <div class="row" style="margin: 0 0 12px 0; padding: 10px 0; border-bottom: 2px solid #000; border-top: 2px solid #000; font-size: 12px; line-height: 1.5;">
                            <div class="col-7" style="padding-left: 0;">
                                <strong>ADDRESS:</strong> <span id="inv-preview-shop-address"><?php echo !empty($shopAddress) ? $shopAddress : '12 corporate boulevard, tech towers, Sector 62'; ?></span><br>
                                <strong>State:</strong> <span id="inv-preview-shop-state"><?php echo !empty($shopState) ? $shopState : 'GUJARAT'; ?></span>
                            </div>
                            <div class="col-5" style="padding-right: 0; text-align: right; border-left: 1px solid #000; padding-left: 15px;">
                                <strong>Mobile No:</strong> <span id="inv-preview-shop-mobile"><?php echo !empty($shopMobile) ? $shopMobile : '-'; ?></span><br>
                                <strong>GSTIN:</strong> <span id="inv-preview-shop-gstin"><?php echo !empty($shopGstin) ? $shopGstin : '-'; ?></span><br>
                                <strong>PAN:</strong> <span id="inv-preview-shop-pan"><?php echo !empty($shopPan) ? $shopPan : '-'; ?></span><br>
                                <strong>MSME No:</strong> <span id="inv-preview-shop-msme"><?php echo !empty($shopMsme) ? $shopMsme : '-'; ?></span>
                            </div>
                        </div>
                        
                        <!-- Invoice Bar -->
                        <div class="text-center" style="border-bottom: 2px solid #000; margin-bottom: 12px; padding: 6px 0; background: #f8fafc;">
                            <strong style="font-size: 16px; letter-spacing: 2px; text-transform: uppercase;">TAX INVOICE</strong>
                        </div>
                        
                        <!-- Client and Bill Info Row -->
                        <div class="row style-client-bill" style="margin: 0 0 12px 0; border: 1px solid #000; min-height: 110px;">
                            <!-- Client Details -->
                            <div class="col-7" style="padding: 10px; border-right: 1px solid #000;">
                                <strong style="text-transform: uppercase;">BILLED TO:</strong> <span id="inv-preview-customer" style="font-weight: bold; text-transform: uppercase;">-</span><br>
                                <span style="display:inline-block; margin-top: 5px;"><strong>Address :</strong> <span id="inv-preview-address">-</span></span><br>
                                <span style="display:inline-block; margin-top: 5px;"><strong>GSTIN :</strong> <span id="inv-preview-gst">-</span> &nbsp;&nbsp;&nbsp;&nbsp; <strong>PAN :</strong> <span id="inv-preview-customer-pan">-</span></span>
                            </div>
                            <!-- Bill Details -->
                            <div class="col-5" style="padding: 10px; line-height: 1.6;">
                                <strong>Bill No.:</strong> <span id="inv-preview-bill-no" style="font-weight: bold;">-</span><br>
                                <strong>Bill Date:</strong> <span id="inv-preview-date" style="font-weight: bold;">-</span><br>
                                <strong>Challan:</strong> <span id="inv-preview-challan-no" style="font-weight: bold;">-</span>
                            </div>
                        </div>
                        
                        <!-- Items Table -->
                        <div style="border: 1px solid #000; margin-bottom: 12px;">
                            <table class="table mb-0" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                <thead>
                                    <tr style="border-bottom: 2px solid #000; background: #f8fafc; font-weight: bold;">
                                        <th style="width: 6%; text-align: center; border-right: 1px solid #000; padding: 8px;">Sr.</th>
                                        <th style="width: 48%; border-right: 1px solid #000; padding: 8px;">Item Name</th>
                                        <th style="width: 12%; text-align: center; border-right: 1px solid #000; padding: 8px;">HSN</th>
                                        <th style="width: 12%; text-align: center; border-right: 1px solid #000; padding: 8px;">Qty</th>
                                        <th style="width: 10%; text-align: right; border-right: 1px solid #000; padding: 8px;">Rate</th>
                                        <th style="width: 12%; text-align: right; padding: 8px;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="inv-preview-items" style="min-height: 250px;">
                                    <!-- Dynamic rows from JS -->
                                </tbody>
                                <tfoot>
                                    <tr style="border-top: 2px solid #000; font-weight: bold; background: #f8fafc;">
                                        <td colspan="3" style="text-align: right; border-right: 1px solid #000; padding: 8px;">Total</td>
                                        <td id="inv-preview-total-qty" style="text-align: center; border-right: 1px solid #000; padding: 8px;">0</td>
                                        <td style="border-right: 1px solid #000; padding: 8px;"></td>
                                        <td id="inv-preview-total-amount-sum" style="text-align: right; padding: 8px;">₹ 0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Calculations and Bottom Area -->
                        <div class="row" style="margin: 0;">
                            <!-- Terms and Bank Details -->
                            <div class="col-7" style="padding: 10px; border: 1px solid #000; font-size: 11px; line-height: 1.5;">
                                <strong>Amount in Words:</strong> <span id="inv-preview-amount-words" style="font-weight: bold; text-transform: uppercase;">-</span>
                                <hr style="margin: 8px 0; border-color: #000;">
                                <strong>Terms:</strong> 1. Subject to '<span id="inv-preview-shop-state-term"><?php echo !empty($shopState) ? $shopState : 'Gujarat'; ?></span>' Jurisdiction only. 2. (PAYMENT DUE TO 45 DAY) <?php echo !empty($shopMsme) ? '('.$shopMsme.')' : ''; ?>
                                <hr style="margin: 8px 0; border-color: #000;">
                                <strong>Bank Details:</strong><br>
                                <span style="font-size: 11px; line-height: 1.4; display: inline-block; margin-top: 4px;">
                                    Bank Name: <strong><?php echo !empty($bankName) ? $bankName : '-'; ?></strong> &nbsp;&nbsp;&nbsp;&nbsp;
                                    A/c No: <strong><?php echo !empty($bankAccNo) ? $bankAccNo : '-'; ?></strong><br>
                                    A/c Type: <strong><?php echo !empty($bankAccType) ? $bankAccType : 'CURRENT'; ?></strong> &nbsp;&nbsp;&nbsp;&nbsp;
                                    IFSC: <strong><?php echo !empty($bankIfsc) ? $bankIfsc : '-'; ?></strong>
                                </span>
                            </div>
                            
                            <!-- Financial Totals -->
                            <div class="col-5" style="padding: 0; border: 1px solid #000; border-left: none; line-height: 1.5; font-size: 12px;">
                                <div style="display: flex; justify-content: space-between; padding: 4px 10px; border-bottom: 1px solid #eee;">
                                    <span>Discount (<span id="inv-preview-discount-percent">0</span>%):</span>
                                    <span id="inv-preview-discount">- ₹ 0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 10px; border-bottom: 1px solid #eee; font-weight: bold;">
                                    <span>Taxable Amount:</span>
                                    <span id="inv-preview-taxable">₹ 0.00</span>
                                </div>
                                
                                <!-- CGST / SGST or IGST container -->
                                <div id="inv-preview-tax-breakdown">
                                    <!-- Populated dynamically via JS -->
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 4px 10px; border-bottom: 1px solid #eee;">
                                    <span>Total Tax:</span>
                                    <span id="inv-preview-total-tax">₹ 0.00</span>
                                </div>
                                
                                <!-- TDS/TCS row if any -->
                                <div id="inv-preview-tds-tcs-row" style="display: none; justify-content: space-between; padding: 4px 10px; border-bottom: 1px solid #eee; color: #10b981; font-weight: bold;">
                                    <span id="inv-preview-tds-tcs-label">TDS/TCS (0%):</span>
                                    <span id="inv-preview-tds-tcs-amount">+ ₹ 0.00</span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 6px 10px; background: #f8fafc; font-size: 14px; font-weight: bold; border-bottom: 1px solid #eee;">
                                    <span>Net Amount:</span>
                                    <span id="inv-preview-grand-total" style="color: #2563eb;">₹ 0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 10px; font-size: 13px; font-weight: bold;">
                                    <span>Round Amount:</span>
                                    <span id="inv-preview-round-amount">₹ 0.00</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Signature Row -->
                        <div class="row" style="margin: 25px 0 0 0; padding-top: 15px;">
                            <div class="col-8">
                                <div class="no-print mt-2">
                                    <h6 class="font-weight-bold text-primary mb-2">Payment History Logs</h6>
                                    <div class="timeline" id="inv-preview-timeline" style="font-size: 11px;">
                                        <!-- Log Items -->
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 text-center" style="border-top: 1px dashed #000; padding-top: 5px; font-size: 12px; align-self: flex-end;">
                                <strong>Signature</strong>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 no-print">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnShareWhatsApp"><i class="fa-brands fa-whatsapp text-success"></i> Share</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnShareEmail"><i class="fa-regular fa-envelope text-primary"></i> Email</button>
                    <button type="button" class="btn btn-primary" onclick="window.print();"><i class="fa-solid fa-print"></i> Print Invoice</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Products / Customer add overlay -->
    <div class="modal-overlay" id="modal-product">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modal-product-title">Add Product</h3>
                <span class="modal-close" onclick="closeModal('product')"><i class="fa-solid fa-xmark"></i></span>
            </div>
            <div class="modal-body">
                <form class="modal-form" id="form-product">
                    <input type="hidden" name="action" id="action-product" value="create">
                    <input type="hidden" name="id" id="id-product" value="">
                    
                    <div>
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" id="name-product" class="form-input" placeholder="Product name..." required>
                    </div>
                    <div>
                        <label class="form-label">Category</label>
                        <input type="text" name="category" id="category-product" class="form-input" placeholder="Category..." required>
                    </div>
                    <div>
                        <label class="form-label">Price per Unit</label>
                        <input type="number" step="0.01" name="price" id="price-product" class="form-input" placeholder="0.00" min="0" required>
                    </div>
                    <div>
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_quantity" id="stock-product" class="form-input" placeholder="0" min="0" required>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="module-btn secondary" onclick="closeModal('product')">Cancel</button>
                        <button type="submit" class="module-btn primary">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: General Customer -->
    <div class="modal-overlay" id="modal-customer-general">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modal-customer-general-title">Add Customer</h3>
                <span class="modal-close" onclick="closeModal('customer-general')"><i class="fa-solid fa-xmark"></i></span>
            </div>
            <div class="modal-body">
                <form class="modal-form" id="form-customer-general">
                    <input type="hidden" name="action" id="action-customer-general" value="create">
                    <input type="hidden" name="id" id="id-customer-general" value="">
                    
                    <div>
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="name" id="name-customer-general" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="email-customer-general" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="phone-customer-general" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Address</label>
                        <input type="text" name="address" id="address-customer-general" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">GST Number</label>
                        <input type="text" name="gst_number" id="gst-customer-general" class="form-input">
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="module-btn secondary" onclick="closeModal('customer-general')">Cancel</button>
                        <button type="submit" class="module-btn primary">Save Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Supplier -->
    <div class="modal-overlay" id="modal-supplier">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modal-supplier-title">Add Supplier</h3>
                <span class="modal-close" onclick="closeModal('supplier')"><i class="fa-solid fa-xmark"></i></span>
            </div>
            <div class="modal-body">
                <form class="modal-form" id="form-supplier">
                    <input type="hidden" name="action" id="action-supplier" value="create">
                    <input type="hidden" name="id" id="id-supplier" value="">
                    
                    <div>
                        <label class="form-label">Supplier Name</label>
                        <input type="text" name="name" id="name-supplier" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="email-supplier" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="phone-supplier" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Address</label>
                        <input type="text" name="address" id="address-supplier" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">GST Number</label>
                        <input type="text" name="gst_number" id="gst-supplier" class="form-input">
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="module-btn secondary" onclick="closeModal('supplier')">Cancel</button>
                        <button type="submit" class="module-btn primary">Save Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
