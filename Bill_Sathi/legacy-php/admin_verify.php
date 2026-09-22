<?php
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['admin_password'] ?? '';
    if ($password === 'shivansh') {
        $_SESSION['admin_verified'] = true;
        header('Location: register.php');
        exit;
    } else {
        $error = 'Incorrect Admin Password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Verification - Finance Manager</title>
    
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/login.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/translator.js"></script>
    <style>
        .error-message {
            color: #ef4444;
            background: #fef2f2;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 0.9rem;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>
    <div style="position: absolute; top: 20px; right: 20px; z-index: 1000;"><select class="languageSwitcher" style="padding: 8px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; cursor: pointer; color: #475569; font-weight: 500;"><option value="en">English</option><option value="gu">ગુજરાતી</option><option value="es">Español</option></select></div>

    <div class="auth-wrapper">
        <div class="auth-bg-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>

        <main class="auth-card" id="loginCard">
            <header class="auth-header">
                <div class="auth-logo" id="logo">
                    <i class="fa-solid fa-lock"></i>
                    <span class="text-gradient">Admin Verify.</span>
                </div>
                <p class="auth-subtitle">Please enter the admin password to create a new account.</p>
            </header>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="admin_verify.php">
                <div class="form-group">
                    <label for="admin_password" class="sr-only">Admin Password</label>
                    <div class="input-container">
                        <input type="password" id="admin_password" name="admin_password" class="auth-input" placeholder="Admin Password" required autofocus>
                        <i class="fa-solid fa-key input-icon"></i>
                    </div>
                </div>

                <button type="submit" class="btn-primary auth-submit">
                    <span class="btn-text">Verify & Continue</span>
                </button>
            </form>

            <footer class="auth-footer">
                <p><a href="login.php"><i class="fa-solid fa-arrow-left"></i> Back to Login</a></p>
            </footer>
        </main>
    </div>
</body>
</html>
