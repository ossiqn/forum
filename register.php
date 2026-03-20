<?php
require_once 'includes/functions.php';
if (isLoggedIn()) { redirect(SITE_URL . '/index.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Geçersiz istek'; }
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (strlen($username) < 3 || strlen($username) > 50) $errors[] = 'Kullanıcı adı 3-50 karakter olmalıdır';
    if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) $errors[] = 'Kullanıcı adı sadece harf, rakam, nokta, tire ve alt çizgi içerebilir';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta adresi girin';
    if (strlen($password) < 6) $errors[] = 'Şifre en az 6 karakter olmalıdır';
    if ($password !== $passwordConfirm) $errors[] = 'Şifreler eşleşmiyor';

    if (empty($errors)) {
        $existUser = db()->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email], 'ss');
        if ($existUser) $errors[] = 'Bu kullanıcı adı veya e-posta zaten kullanılmakta';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $userId = db()->insert("INSERT INTO users (username, email, password) VALUES (?,?,?)", [$username, $email, $hash], 'sss');
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = 'member';
        redirect(SITE_URL . '/index.php');
    }
}

$pageTitle = 'Kayıt Ol';
require_once 'includes/header.php';
?>
<div style="min-height:calc(100vh - 62px);display:flex;align-items:center;justify-content:center;padding:24px 16px">
    <div class="auth-card">
        <div class="text-center mb-8">
            <div style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,var(--purple),var(--accent));margin-bottom:16px">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            </div>
            <h1 style="font-family:'Clash Display',sans-serif;font-size:22px;font-weight:700;color:var(--ink-0)">Kayıt Ol</h1>
            <p style="color:var(--ink-3);font-size:13.5px;margin-top:4px">Topluluğumuza katıl</p>
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
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" name="username" class="form-control" placeholder="kullanici_adi" value="<?= sanitize($_POST['username'] ?? '') ?>" required autofocus>
                <div class="form-hint">3-50 karakter, harf/rakam/._- kullanabilirsiniz</div>
            </div>
            <div class="form-group">
                <label class="form-label">E-posta</label>
                <input type="email" name="email" class="form-control" placeholder="ornek@email.com" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Şifre</label>
                <div style="position:relative">
                    <input type="password" name="password" id="pw1" class="form-control" placeholder="En az 6 karakter" required style="padding-right:44px">
                    <button type="button" onclick="document.getElementById('pw1').type=document.getElementById('pw1').type==='password'?'text':'password'" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-subtle);cursor:pointer">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Şifre Tekrar</label>
                <input type="password" name="password_confirm" class="form-control" placeholder="Şifrenizi tekrar girin" required>
            </div>
            <div class="mb-4 p-3 bg-surface-50 rounded-lg text-xs text-gray-500">
                Kayıt olarak <a href="#" style="color:var(--accent)">Kullanım Koşulları</a>'nı kabul etmiş sayılırsınız.
            </div>
            <button type="submit" class="btn-primary" style="width:100%;justify-content:center">Kayıt Ol</button>
        </form>
        <p style="text-align:center;font-size:13.5px;color:var(--ink-3);margin-top:24px">
            Zaten hesabın var mı?
            <a href="<?= SITE_URL ?>/login.php" style="color:var(--accent);font-weight:600;text-decoration:none">Giriş Yap</a>
        </p>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
