<?php
/**
 * Forgot Password Page
 * User enters email â†’ system sends reset link
 */
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Finsnce ERP</title>
    <meta name="description" content="Reset your Finsnce ERP account password.">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #2563eb;
            text-decoration: none;
            font-size: 13px;
            margin-bottom: 18px;
            font-weight: 500;
            transition: opacity 0.2s;
        }
        .back-link:hover { opacity: 0.75; }
        .reset-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
            font-size: 22px;
            color: #fff;
            box-shadow: 0 6px 20px rgba(37,99,235,0.3);
        }
        .step-text {
            font-size: 13px;
            color: #64748b;
            text-align: center;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .success-box {
            background: rgba(16,185,129,0.08);
            border: 1px solid rgba(16,185,129,0.3);
            color: #047857;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 13px;
            text-align: center;
            display: none;
        }
        .success-box.show { display: block; }
    </style>
<script src="assets/js/translator.js"></script>
</head>
<body>
    <div style="position: absolute; top: 20px; right: 20px; z-index: 1000;"><select class="languageSwitcher" style="padding: 8px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; cursor: pointer; color: #475569; font-weight: 500;"><option value="en">English</option><option value="gu">???????</option><option value="es">Español</option></select></div>
    <div class="auth-wrapper">
        <div class="auth-bg-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>

        <main class="auth-card" id="forgotCard">
            <a href="login.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Back to Login
            </a>

            <div class="reset-icon">
                <i class="fa-solid fa-key"></i>
            </div>

            <header class="auth-header" style="margin-bottom: 0;">
                <div class="auth-logo" id="logo" style="justify-content: center;">
                    <i class="fa-solid fa-wallet"></i>
                    <span class="text-gradient">Finsnce.</span>
                </div>
                <p class="auth-subtitle">Forgot your password?</p>
            </header>

            <p class="step-text">
                Enter your registered email address and we'll send you a link to reset your password.
            </p>

            <!-- Message Notification Box -->
            <div class="message-box" id="messageBox" role="alert"></div>

            <!-- Success Box -->
            <div class="success-box" id="successBox">
                <i class="fa-solid fa-circle-check" style="font-size:18px; margin-bottom:8px; display:block;"></i>
                <strong>Email Sent!</strong><br>
                Check your inbox for the reset link. It expires in <strong>30 minutes</strong>.
            </div>

            <form class="auth-form" id="forgotForm" novalidate>
                <div class="form-group">
                    <label for="email" class="sr-only">Email Address</label>
                    <div class="input-container">
                        <input type="email" id="email" name="email" class="auth-input"
                               placeholder="Enter your email address" required autocomplete="email">
                        <span class="input-icon"><i class="fa-regular fa-envelope"></i></span>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span class="btn-text">Send Reset Link</span>
                    <span class="spinner" aria-hidden="true"></span>
                </button>
            </form>

            <footer class="auth-footer">
                <p>Remembered your password? <a href="login.php">Log In</a></p>
            </footer>
        </main>
    </div>

    <script>
    (function () {
        const form     = document.getElementById('forgotForm');
        const msgBox   = document.getElementById('messageBox');
        const sucBox   = document.getElementById('successBox');
        const submitBtn = document.getElementById('submitBtn');

        function showMsg(msg, type = 'error') {
            msgBox.textContent = msg;
            msgBox.className = 'message-box show ' + type;
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            msgBox.className = 'message-box';
            sucBox.classList.remove('show');

            const email = document.getElementById('email').value.trim();
            if (!email) { showMsg('Please enter your email address.'); return; }

            submitBtn.disabled = true;
            submitBtn.classList.add('loading');

            try {
                const res  = await fetch('auth/send_reset_email.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'email=' + encodeURIComponent(email)
                });
                const data = await res.json();

                if (data.success) {
                    form.style.display = 'none';
                    sucBox.classList.add('show');
                } else {
                    showMsg(data.message || 'Something went wrong. Please try again.');
                }
            } catch (err) {
                showMsg('Network error. Please check your connection.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
            }
        });
    })();
    </script>
</body>
</html>
