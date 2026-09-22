<?php
/**
 * Admin Dashboard
 * Protected — redirect to login if not an authenticated admin.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Prepare admin display info
$adminName  = htmlspecialchars($_SESSION['admin_name']  ?? 'Admin', ENT_QUOTES, 'UTF-8');
$adminEmail = htmlspecialchars($_SESSION['admin_email'] ?? '',       ENT_QUOTES, 'UTF-8');

// Build initials (up to 2 chars)
$nameParts     = preg_split('/\s+/', trim($_SESSION['admin_name'] ?? 'A'));
$adminInitials = strtoupper($nameParts[0][0] ?? 'A');
if (count($nameParts) > 1) {
    $adminInitials .= strtoupper($nameParts[count($nameParts) - 1][0]);
}

// Tomorrow (min for custom date picker)
$tomorrow = date('Y-m-d', strtotime('+1 day'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Finance ERP</title>
    <meta name="description" content="Admin account management dashboard — Finance ERP">
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>

<div class="admin-layout">

    <!-- ═══════════════════ SIDEBAR ═══════════════════ -->
    <aside class="sidebar" id="sidebar">

        <a href="dashboard.php" class="sidebar-logo">
            <div class="logo-icon">🛡️</div>
            <div class="logo-text">
                <span class="logo-name">Finance ERP</span>
                <span class="logo-sub">Admin Panel</span>
            </div>
        </a>

        <nav class="sidebar-nav">
            <span class="nav-section-label">Main</span>

            <a href="dashboard.php" class="nav-item active">
                <span class="nav-icon">📊</span>
                Dashboard
            </a>

            <span class="nav-section-label">Quick Links</span>

            <a href="../dashboard.php" class="nav-item" target="_blank" rel="noopener">
                <span class="nav-icon">🏠</span>
                Finance ERP App
            </a>

            <a href="../register.php" class="nav-item" target="_blank" rel="noopener">
                <span class="nav-icon">➕</span>
                Register New User
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-profile">
                <div class="admin-avatar"><?= $adminInitials ?></div>
                <div class="admin-info">
                    <span class="admin-name"><?= $adminName ?></span>
                    <span class="admin-role-tag">Administrator</span>
                </div>
            </div>
            <a href="api/admin_logout.php" class="btn-logout">
                <span>🚪</span> Sign Out
            </a>
        </div>

    </aside>
    <!-- /SIDEBAR -->

    <!-- ═══════════════════ MAIN CONTENT ═══════════════════ -->
    <main class="main-content">

        <!-- TOP HEADER -->
        <header class="top-header">
            <div class="header-title">
                <h1>Account Management</h1>
                <p>Manage user accounts, validity periods, and account status</p>
            </div>
            <div class="header-right">
                <div class="live-badge">
                    <span class="dot"></span>
                    Live System
                </div>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <div class="page-content">

            <!-- ── STAT CARDS ────────────────────────────── -->
            <div class="stats-grid">

                <div class="stat-card total">
                    <div class="stat-icon">👥</div>
                    <div class="stat-body">
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value" id="statTotal">0</div>
                        <div class="stat-sub">All registered accounts</div>
                    </div>
                </div>

                <div class="stat-card active">
                    <div class="stat-icon">✅</div>
                    <div class="stat-body">
                        <div class="stat-label">Active</div>
                        <div class="stat-value" id="statActive">0</div>
                        <div class="stat-sub">Currently active</div>
                    </div>
                </div>

                <div class="stat-card expired">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-body">
                        <div class="stat-label">Expired</div>
                        <div class="stat-value" id="statExpired">0</div>
                        <div class="stat-sub">Validity lapsed</div>
                    </div>
                </div>

                <div class="stat-card suspended">
                    <div class="stat-icon">🚫</div>
                    <div class="stat-body">
                        <div class="stat-label">Suspended</div>
                        <div class="stat-value" id="statSuspended">0</div>
                        <div class="stat-sub">Manually suspended</div>
                    </div>
                </div>

            </div><!-- /stats-grid -->

            <!-- ── USER MANAGEMENT TABLE ─────────────────── -->
            <div class="section-card">

                <!-- Section header with search + filter -->
                <div class="section-header">
                    <div class="section-title">
                        All Users
                        <span class="count-badge" id="userCountBadge">Loading…</span>
                    </div>
                    <div class="table-controls">

                        <!-- Search -->
                        <div class="search-wrap">
                            <span class="search-icon">🔍</span>
                            <input
                                type="search"
                                id="searchInput"
                                class="search-input"
                                placeholder="Search name or email…"
                                autocomplete="off"
                                aria-label="Search users"
                            >
                        </div>

                        <!-- Filter tabs -->
                        <div class="filter-tabs" role="tablist" aria-label="Filter by status">
                            <button class="filter-tab active" data-filter="all"       role="tab">All</button>
                            <button class="filter-tab"        data-filter="active"    role="tab">Active</button>
                            <button class="filter-tab"        data-filter="expired"   role="tab">Expired</button>
                            <button class="filter-tab"        data-filter="suspended" role="tab">Suspended</button>
                        </div>

                    </div>
                </div>

                <!-- Table -->
                <div class="table-wrap">
                    <table class="users-table" aria-label="Users table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Valid Until</th>
                                <th>Change Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <!-- Rendered by admin.js -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination footer -->
                <div class="table-footer">
                    <span class="pagination-info" id="paginationInfo"></span>
                    <div class="pagination" id="paginationWrap"></div>
                </div>

            </div><!-- /section-card -->

        </div><!-- /page-content -->

    </main>
    <!-- /MAIN CONTENT -->

</div><!-- /admin-layout -->


<!-- ═══════════════════ MODALS ═══════════════════ -->

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModalOverlay" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="modal">

        <div class="modal-header">
            <div class="modal-title" id="deleteModalTitle">
                <div class="modal-icon danger">🗑️</div>
                Delete User Account
            </div>
            <button class="modal-close" aria-label="Close modal">✕</button>
        </div>

        <div class="modal-body">
            <p class="modal-message">
                You are about to permanently delete <strong id="deleteUserName"></strong>.
                This action cannot be undone.
            </p>
            <div class="modal-warn">
                ⚠️ All associated data — bills, transactions, products, and reports — will be permanently removed due to cascade deletion.
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Cancel</button>
            <button
                class="btn"
                id="confirmDeleteBtn"
                style="background:var(--danger);color:#fff;border-color:var(--danger);gap:6px"
            >
                🗑️ Delete Permanently
            </button>
        </div>

    </div>
</div>

<!-- Set Validity Modal -->
<div class="modal-overlay" id="validityModalOverlay" role="dialog" aria-modal="true" aria-labelledby="validityModalTitle">
    <div class="modal">

        <div class="modal-header">
            <div class="modal-title" id="validityModalTitle">
                <div class="modal-icon success">📅</div>
                Set Account Validity
            </div>
            <button class="modal-close" aria-label="Close modal">✕</button>
        </div>

        <div class="modal-body">
            <p class="modal-message" style="margin-bottom:16px">
                Setting expiry date for <strong id="validityUserName"></strong>
            </p>

            <!-- Preset duration buttons -->
            <div class="validity-options">
                <button class="validity-opt" data-days="7">7 Days</button>
                <button class="validity-opt" data-days="30">30 Days</button>
                <button class="validity-opt" data-days="90">90 Days</button>
                <button class="validity-opt" data-days="180">180 Days</button>
                <button class="validity-opt" data-days="365">1 Year</button>
                <button class="validity-opt" data-days="730">2 Years</button>
            </div>

            <div class="validity-divider">or pick a custom date</div>

            <div class="form-group">
                <label class="form-label" for="customDate">Custom Expiry Date</label>
                <input
                    type="date"
                    id="customDate"
                    class="form-input"
                    min="<?= $tomorrow ?>"
                    aria-label="Custom expiry date"
                >
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Cancel</button>
            <button class="btn btn-primary" id="confirmValidityBtn">
                ✅ Set Validity
            </button>
        </div>

    </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer" aria-live="polite"></div>

<script src="assets/admin.js"></script>
</body>
</html>
