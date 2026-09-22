<?php
/**
 * Admin Login Page
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already authenticated admin → go to dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Finance ERP</title>
    <meta name="description" content="Secure admin login for Finance ERP Admin Panel">
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>

<div class="login-page">

    <div class="login-card">

        <!-- Logo -->
        <div class="login-logo">
            <div class="logo-circle">🛡️</div>
            <h1>Admin Panel</h1>
            <p>Finance ERP — Secure Access Only</p>
        </div>

        <!-- Error Alert -->
        <div class="alert-error" id="loginError" role="alert">
            <span>⚠️</span>
            <span id="loginErrorMsg">Invalid credentials.</span>
        </div>

        <!-- Login Form -->
        <form id="loginForm" novalidate>

            <div class="form-group">
                <label class="form-label" for="adminEmail">Email Address</label>
                <div class="input-wrap">
                    <span class="input-icon">✉️</span>
                    <input
                        type="email"
                        id="adminEmail"
                        name="email"
                        class="login-input"
                        placeholder="admin@billsathi.com"
                        autocomplete="email"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="adminPassword">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input
                        type="password"
                        id="adminPassword"
                        name="password"
                        class="login-input"
                        placeholder="Enter admin password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password">👁️</button>
                </div>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                <span class="spinner"></span>
                <span class="btn-txt">Sign In to Admin Panel</span>
            </button>

        </form>

        <!-- Footer meta -->
        <div class="login-meta">
            <p>Admin credentials: <strong>shivansh@gmail.com</strong> / <strong>shivansh</strong></p>
            <p style="margin-top:10px">
                <a href="../login.php" style="font-size:12px;color:var(--text-muted)">← Back to Finance ERP</a>
            </p>
        </div>

    </div><!-- /.login-card -->

</div><!-- /.login-page -->

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form      = document.getElementById('loginForm');
    const btn       = document.getElementById('loginBtn');
    const errBox    = document.getElementById('loginError');
    const errMsg    = document.getElementById('loginErrorMsg');
    const pwToggle  = document.getElementById('pwToggle');
    const pwInput   = document.getElementById('adminPassword');

    // Password show/hide
    pwToggle.addEventListener('click', () => {
        const show = pwInput.type === 'text';
        pwInput.type    = show ? 'password' : 'text';
        pwToggle.textContent = show ? '👁️' : '🙈';
    });

    // Form submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errBox.classList.remove('show');
        btn.classList.add('loading');

        try {
            const res  = await fetch('api/admin_login.php', { method: 'POST', body: new FormData(form) });
            const data = await res.json();

            if (data.success) {
                btn.innerHTML = '✅ Redirecting…';
                window.location.href = data.redirect || 'dashboard.php';
            } else {
                errMsg.textContent = data.message || 'Login failed.';
                errBox.classList.add('show');
                btn.classList.remove('loading');
            }
        } catch {
            errMsg.textContent = 'Network error. Please try again.';
            errBox.classList.add('show');
            btn.classList.remove('loading');
        }
    });

    // Clear error on input
    form.addEventListener('input', () => errBox.classList.remove('show'));
});
</script>

</body>
</html>
