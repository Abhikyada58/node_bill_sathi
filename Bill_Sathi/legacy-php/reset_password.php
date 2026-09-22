<?php
/**
 * Reset Password Page
 * User arrives here from email link with a token
 */
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$token   = trim($_GET['token'] ?? '');
$valid   = false;
$expired = false;
$email   = '';

if (!empty($token)) {
    try {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        $row  = $stmt->fetch();

        if ($row) {
            if (strtotime($row['expires_at'] . ' UTC') >= time()) {
                $valid = true;
                $email = $row['email'];
            } else {
                $expired = true;
            }
        }
    } catch (Exception $e) {
        // DB error — treat as invalid
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Finsnce ERP</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .strength-bar { height: 4px; border-radius: 4px; background: #e2e8f0; margin-top: 8px; overflow: hidden; }
        .strength-bar-fill { height: 100%; width: 0; border-radius: 4px; transition: all 0.4s; }
        .strength-label { font-size: 11px; color: #94a3b8; margin-top: 4px; }
        .invalid-box, .expired-box {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.25);
            color: #b91c1c;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }
        .expired-box { background: rgba(245,158,11,0.08); border-color: rgba(245,158,11,0.3); color: #92400e; }
        .reset-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px auto;
            font-size: 22px; color: #fff;
            box-shadow: 0 6px 20px rgba(37,99,235,0.3);
        }
    </style>
<script src="assets/js/translator.js"></script>
</head>
<body>
    <div style="position: absolute; top: 20px; right: 20px; z-index: 1000;"><select class="languageSwitcher" style="padding: 8px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; cursor: pointer; color: #475569; font-weight: 500;"><option value="en">English</option><option value="gu">???????</option><option value="es">Espa�ol</option></select></div>
    <div class="auth-wrapper">
        <div class="auth-bg-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>

        <main class="auth-card">
            <div style="text-align:center; margin-bottom: 8px;">
                <div class="auth-logo" style="justify-content:center; margin-bottom:4px;">
                    <i class="fa-solid fa-wallet"></i>
                    <span class="text-gradient">Finsnce.</span>
                </div>
            </div>

            <?php if ($valid): ?>
                <div class="reset-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <header class="auth-header" style="margin-bottom: 8px;">
                    <p class="auth-subtitle">Set your new password</p>
                </header>
                <p style="font-size:13px; color:#64748b; text-align:center; margin-bottom:20px;">
                    Enter a strong new password for <strong><?php echo htmlspecialchars($email); ?></strong>
                </p>

                <div class="message-box" id="messageBox" role="alert"></div>

                <form class="auth-form" id="resetForm" novalidate>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group">
                        <div class="input-container">
                            <input type="password" id="newPassword" name="new_password"
                                   class="auth-input" placeholder="New Password" required
                                   autocomplete="new-password" minlength="8">
                            <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                            <button type="button" class="password-toggle" id="toggleNew">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <div class="strength-bar"><div class="strength-bar-fill" id="strengthFill"></div></div>
                        <div class="strength-label" id="strengthLabel"></div>
                    </div>

                    <div class="form-group">
                        <div class="input-container">
                            <input type="password" id="confirmPassword" name="confirm_password"
                                   class="auth-input" placeholder="Confirm New Password" required
                                   autocomplete="new-password">
                            <span class="input-icon"><i class="fa-solid fa-lock-open"></i></span>
                            <button type="button" class="password-toggle" id="toggleConfirm">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span class="btn-text">Update Password</span>
                        <span class="spinner" aria-hidden="true"></span>
                    </button>
                </form>

            <?php elseif ($expired): ?>
                <div class="expired-box">
                    <i class="fa-solid fa-clock" style="font-size:28px; margin-bottom:10px; display:block;"></i>
                    <strong>Link Expired</strong><br><br>
                    This password reset link has expired (links are valid for 30 minutes).<br><br>
                    <a href="forgot_password.php" style="color:#92400e; font-weight:600;">Request a new link →</a>
                </div>

            <?php else: ?>
                <div class="invalid-box">
                    <i class="fa-solid fa-circle-xmark" style="font-size:28px; margin-bottom:10px; display:block;"></i>
                    <strong>Invalid Link</strong><br><br>
                    This password reset link is invalid or has already been used.<br><br>
                    <a href="forgot_password.php" style="color:#b91c1c; font-weight:600;">Request a new link →</a>
                </div>
            <?php endif; ?>

            <footer class="auth-footer" style="margin-top:20px;">
                <p><a href="login.php">← Back to Login</a></p>
            </footer>
        </main>
    </div>

    <?php if ($valid): ?>
    <script>
    (function () {
        // Toggle password visibility
        function setupToggle(toggleId, inputId) {
            document.getElementById(toggleId).addEventListener('click', function () {
                const inp = document.getElementById(inputId);
                const icon = this.querySelector('i');
                inp.type = inp.type === 'password' ? 'text' : 'password';
                icon.className = inp.type === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
            });
        }
        setupToggle('toggleNew', 'newPassword');
        setupToggle('toggleConfirm', 'confirmPassword');

        // Password strength meter
        const pwInput     = document.getElementById('newPassword');
        const strengthFill = document.getElementById('strengthFill');
        const strengthLabel = document.getElementById('strengthLabel');
        const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e'];
        const labels = ['Weak', 'Fair', 'Good', 'Strong'];

        pwInput.addEventListener('input', function () {
            const val = this.value;
            let score = 0;
            if (val.length >= 8)          score++;
            if (/[A-Z]/.test(val))        score++;
            if (/[0-9]/.test(val))        score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const pct = (score / 4) * 100;
            strengthFill.style.width     = pct + '%';
            strengthFill.style.background = colors[score - 1] || '#e2e8f0';
            strengthLabel.textContent    = val.length ? labels[score - 1] || '' : '';
            strengthLabel.style.color    = colors[score - 1] || '#94a3b8';
        });

        // Form submit
        const form    = document.getElementById('resetForm');
        const msgBox  = document.getElementById('messageBox');
        const btn     = document.getElementById('submitBtn');

        function showMsg(msg, type = 'error') {
            msgBox.textContent = msg;
            msgBox.className = 'message-box show ' + type;
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            msgBox.className = 'message-box';

            const newPw  = document.getElementById('newPassword').value;
            const confPw = document.getElementById('confirmPassword').value;

            if (newPw.length < 8) { showMsg('Password must be at least 8 characters.'); return; }
            if (newPw !== confPw) { showMsg('Passwords do not match.'); return; }

            btn.disabled = true;
            btn.classList.add('loading');

            try {
                const res  = await fetch('auth/process_reset_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(new FormData(form)).toString()
                });
                const data = await res.json();

                if (data.success) {
                    showMsg('✅ Password updated! Redirecting to login...', 'success');
                    setTimeout(() => window.location.href = 'login.php', 2500);
                } else {
                    showMsg(data.message || 'An error occurred. Please try again.');
                }
            } catch (err) {
                showMsg('Network error. Please try again.');
            } finally {
                btn.disabled = false;
                btn.classList.remove('loading');
            }
        });
    })();
    </script>
    <?php endif; ?>
</body>
</html>
