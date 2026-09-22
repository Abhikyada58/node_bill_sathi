<?php
/**
 * Finance Dashboard - Login Page
 */

session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Finance Manager</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Securely access your Finance Management Dashboard. Manage your income, expenses, budgets, and savings in one premium system.">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/login.css">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="assets/js/translator.js"></script>
</head>
<body>
    <div style="position: absolute; top: 20px; right: 20px; z-index: 1000;"><select class="languageSwitcher" style="padding: 8px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; cursor: pointer; color: #475569; font-weight: 500;"><option value="en">English</option><option value="gu">???????</option><option value="es">Espa�ol</option></select></div>

    <div class="auth-wrapper">
        <!-- Floating Backdrop Shapes -->
        <div class="auth-bg-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>

        <!-- Glassmorphism Auth Card -->
        <main class="auth-card" id="loginCard">
            <header class="auth-header">
                <div class="auth-logo" id="logo">
                    <i class="fa-solid fa-wallet"></i>
                    <span class="text-gradient">Finsnce.</span>
                </div>
                <p class="auth-subtitle">Welcome back! Please login to your account</p>
            </header>

            <!-- Message Notification Box -->
            <div class="message-box" id="messageBox" role="alert"></div>

            <form class="auth-form" id="loginForm" novalidate>
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email" class="sr-only">Email Address</label>
                    <div class="input-container">
                        <input type="email" id="email" name="email" class="auth-input" placeholder="Email Address" required autocomplete="email">
                        <span class="input-icon"><i class="fa-regular fa-envelope"></i></span>
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="sr-only">Password</label>
                    <div class="input-container">
                        <input type="password" id="password" name="password" class="auth-input" placeholder="Password" required autocomplete="current-password">
                        <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                        <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Extra Options -->
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit" id="submitBtn">
                    <span class="btn-text">Log In</span>
                    <span class="spinner" aria-hidden="true"></span>
                </button>
            </form>

            <footer class="auth-footer">
                <p>Don't have an account? <a href="admin_verify.php">Create Account</a></p>
            </footer>
        </main>
    </div>

    <!-- Scripts -->
    <script src="assets/js/login.js"></script>
</body>
</html>


