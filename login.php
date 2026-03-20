<?php
require_once 'includes/functions.php';
if (isLoggedIn()) { redirect(SITE_URL . '/index.php'); }

$errors = [];
$redirect = $_GET['redirect'] ?? SITE_URL . '/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Geçersiz istek'; }
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($errors)) {
        $user = db()->fetchOne("SELECT * FROM users WHERE email = ?", [$email], 's');
        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_banned']) {
                $errors[] = 'Hesabınız yasaklanmıştır.' . ($user['ban_reason'] ? ' Neden: ' . $user['ban_reason'] : '');
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                db()->query("UPDATE users SET last_seen = NOW() WHERE id = ?", [$user['id']], 'i');
                redirect($redirect);
            }
        } else {
            $errors[] = 'E-posta veya şifre hatalı';
        }
    }
}

$pageTitle = 'Giriş Yap';
require_once 'includes/header.php';
?>
<div style="min-height:calc(100vh - 62px);display:flex;align-items:center;justify-content:center;padding:24px 16px">
    <div class="auth-card">
        <div class="text-center mb-8">
            <div style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,var(--accent),var(--purple));margin-bottom:16px">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            </div>
            <h1 style="font-family:'Clash Display',sans-serif;font-size:22px;font-weight:700;color:var(--ink-0)">Giriş Yap</h1>
            <p style="color:var(--ink-3);font-size:13.5px;margin-top:4px">Hesabına hoş geldin</p>
        </div>
        <?php if ($errors): ?>
        <div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;background:var(--red-dim);border:1px solid rgba(255,95,87,.2)">
            <?php foreach ($errors as $e): ?>
            <p style="color:var(--red);font-size:13.5px"><?= sanitize($e) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label">E-posta</label>
                <input type="email" name="email" class="form-control" placeholder="ornek@email.com" value="<?= sanitize($_POST['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Şifre</label>
                <div style="position:relative">
                    <input type="password" name="password" id="password-field" class="form-control" placeholder="••••••••" required style="padding-right:44px">
                    <button type="button" onclick="togglePassword()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-subtle);cursor:pointer">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-primary w-full justify-center mt-2" style="width:100%">
                Giriş Yap
            </button>
        </form>
        <p style="text-align:center;font-size:13.5px;color:var(--ink-3);margin-top:24px">
            Hesabın yok mu?
            <a href="<?= SITE_URL ?>/register.php" style="color:var(--accent);font-weight:600;text-decoration:none">Kayıt Ol</a>
        </p>
    </div>
</div>
<script>
function togglePassword() {
    const f = document.getElementById('password-field');
    f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
<?php require_once 'includes/footer.php'; ?>
