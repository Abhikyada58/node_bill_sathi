<?php
/**
 * Admin Panel - Database Migration Script
 * ─────────────────────────────────────────
 * Run ONCE at: http://localhost/finance/admin/migrate.php
 * After running successfully, restrict or delete this file.
 */

require_once __DIR__ . '/../config/database.php';

$results = [];
$hasError = false;

function addResult(array &$results, string $status, string $message): void {
    $results[] = compact('status', 'message');
}

try {
    $pdo = getDBConnection();

    // ── 1. Add `role` column ──────────────────────────────────────
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user'");
        addResult($results, 'success', "Column <code>role</code> added to <code>users</code> table.");
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            addResult($results, 'info', "Column <code>role</code> already exists — skipped.");
        } else { throw $e; }
    }

    // ── 2. Add `status` column ────────────────────────────────────
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active','expired','suspended') NOT NULL DEFAULT 'active'");
        addResult($results, 'success', "Column <code>status</code> added to <code>users</code> table.");
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            addResult($results, 'info', "Column <code>status</code> already exists — skipped.");
        } else { throw $e; }
    }

    // ── 3. Add `valid_until` column ───────────────────────────────
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN valid_until DATE DEFAULT NULL");
        addResult($results, 'success', "Column <code>valid_until</code> added to <code>users</code> table.");
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            addResult($results, 'info', "Column <code>valid_until</code> already exists — skipped.");
        } else { throw $e; }
    }

    // ── 4. Promote user id=1 (admin@finance.com) to admin ─────────
    $affected = $pdo->exec("UPDATE users SET role = 'admin', status = 'active' WHERE id = 1");
    addResult($results, 'success', "User ID 1 (<code>admin@finance.com</code>) promoted to admin role.");

    // ── 5. Seed dedicated admin account ───────────────────────────
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@billsathi.com' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hash = password_hash('Admin@123', PASSWORD_BCRYPT, ['cost' => 10]);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status) VALUES ('Super Admin', 'admin@billsathi.com', :hash, 'admin', 'active')");
        $stmt->execute(['hash' => $hash]);
        addResult($results, 'success', "New admin account created: <code>admin@billsathi.com</code> / <code>Admin@123</code>");
    } else {
        $pdo->exec("UPDATE users SET role = 'admin', status = 'active' WHERE email = 'admin@billsathi.com'");
        addResult($results, 'info', "Account <code>admin@billsathi.com</code> already exists — role confirmed as admin.");
    }

    // ── 6. Confirm original admin ────────────────────────────────
    $pdo->exec("UPDATE users SET role = 'admin', status = 'active' WHERE email = 'admin@finance.com'");
    addResult($results, 'success', "Account <code>admin@finance.com</code> confirmed as admin.");

    addResult($results, 'done', "✅ Migration complete! You can now <a href='login.php'>go to Admin Login</a>.");

} catch (PDOException $e) {
    $hasError = true;
    addResult($results, 'error', "❌ Database Error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Migration — Finance ERP</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0d1117; color: #e6edf3; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 32px; max-width: 600px; width: 100%; box-shadow: 0 8px 40px rgba(0,0,0,0.5); }
        h1 { font-size: 20px; font-weight: 700; margin-bottom: 6px; color: #e6edf3; }
        .subtitle { font-size: 13px; color: #8b949e; margin-bottom: 24px; }
        .result { display: flex; align-items: flex-start; gap: 10px; padding: 10px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 8px; line-height: 1.5; }
        .result.success  { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); color: #34d399; }
        .result.info     { background: rgba(88,166,255,0.1); border: 1px solid rgba(88,166,255,0.2); color: #79c0ff; }
        .result.error    { background: rgba(248,81,73,0.1); border: 1px solid rgba(248,81,73,0.2); color: #f85149; }
        .result.done     { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #10b981; font-weight: 600; margin-top: 16px; }
        .result a { color: #10b981; text-decoration: underline; }
        .icon { flex-shrink: 0; }
        code { background: rgba(255,255,255,0.08); padding: 1px 5px; border-radius: 4px; font-family: monospace; font-size: 12px; }
        .warn { margin-top: 20px; padding: 12px 14px; background: rgba(227,179,65,0.1); border: 1px solid rgba(227,179,65,0.2); border-radius: 8px; font-size: 12.5px; color: #e3b341; }
    </style>
</head>
<body>
<div class="card">
    <h1>🗄️ Admin Panel — Database Migration</h1>
    <p class="subtitle">Altering <code>users</code> table and seeding admin accounts</p>

    <?php foreach ($results as $r): ?>
        <div class="result <?= $r['status'] ?>">
            <span class="icon"><?php
                echo match($r['status']) {
                    'success' => '✅',
                    'info'    => 'ℹ️',
                    'error'   => '❌',
                    'done'    => '🎉',
                    default   => '•'
                };
            ?></span>
            <span><?= $r['message'] ?></span>
        </div>
    <?php endforeach; ?>

    <?php if (!$hasError): ?>
    <div class="warn">
        ⚠️ <strong>Security Note:</strong> After confirming everything works, delete or restrict access to this file (<code>admin/migrate.php</code>).
    </div>
    <?php endif; ?>
</div>
</body>
</html>
