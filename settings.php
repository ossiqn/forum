<?php
require_once 'includes/functions.php';
requireLogin();
$currentUser = currentUser();
$tab = $_GET['tab'] ?? 'profile';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Geçersiz istek'; }
    $action = $_POST['action'] ?? '';

    if ($action === 'profile' && empty($errors)) {
        
        $about     = isset($_POST['about']) ? trim($_POST['about']) : ($currentUser['about'] ?? '');
        $signature = isset($_POST['signature']) ? trim($_POST['signature']) : ($currentUser['signature'] ?? '');
        $location  = isset($_POST['location']) ? trim($_POST['location']) : ($currentUser['location'] ?? '');
        $birthDate = isset($_POST['birth_date']) ? $_POST['birth_date'] : ($currentUser['birth_date'] ?? '');
        
        $genderRaw = isset($_POST['gender']) ? $_POST['gender'] : ($currentUser['gender'] ?? '');
        $gender    = in_array($genderRaw, ['male','female','other','']) ? $genderRaw : '';

        $website         = isset($_POST['website']) ? trim($_POST['website']) : ($currentUser['website'] ?? '');
        $twitter         = isset($_POST['twitter']) ? trim(ltrim($_POST['twitter'], '@')) : ($currentUser['twitter'] ?? '');
        $github          = isset($_POST['github']) ? trim($_POST['github']) : ($currentUser['github'] ?? '');
        $discord         = isset($_POST['discord']) ? trim($_POST['discord']) : ($currentUser['discord'] ?? '');
        $youtube         = isset($_POST['youtube']) ? trim(ltrim($_POST['youtube'], '@')) : ($currentUser['youtube'] ?? '');
        $instagram       = isset($_POST['instagram']) ? trim(ltrim($_POST['instagram'], '@')) : ($currentUser['instagram'] ?? '');
        $telegramUser    = isset($_POST['telegram_user']) ? trim(ltrim($_POST['telegram_user'], '@')) : ($currentUser['telegram_user'] ?? '');
        $telegramChannel = isset($_POST['telegram_channel']) ? trim(ltrim($_POST['telegram_channel'], '@')) : ($currentUser['telegram_channel'] ?? '');

        if (isset($_POST['website']) && $website && !filter_var($website, FILTER_VALIDATE_URL)) $errors[] = 'Website URL formatı hatalı';
        if (isset($_POST['about']) && strlen($about) > 1000) $errors[] = 'Hakkımda alanı max 1000 karakter';
        if (isset($_POST['signature']) && strlen($signature) > 500) $errors[] = 'İmza max 500 karakter';

        $avatarName = $currentUser['avatar'] ?? null;
        if (!empty($_POST['avatar_base64'])) {
            $base64 = $_POST['avatar_base64'];
            list($type, $base64) = explode(';', $base64);
            list(, $base64)      = explode(',', $base64);
            $data = base64_decode($base64);
            $newName = 'av_' . time() . '_' . uniqid() . '.jpg';
            if (file_put_contents(UPLOADS_PATH.'avatars/'.$newName, $data)) {
                if ($avatarName && file_exists(UPLOADS_PATH.'avatars/'.$avatarName)) unlink(UPLOADS_PATH.'avatars/'.$avatarName);
                $avatarName = $newName;
            } else { $errors[] = 'Avatar kaydedilemedi'; }
        } elseif (!empty($_FILES['avatar']['name'])) {
            $up = uploadImage($_FILES['avatar'], 'avatars', 'av_');
            if ($up) {
                if ($avatarName && file_exists(UPLOADS_PATH.'avatars/'.$avatarName)) unlink(UPLOADS_PATH.'avatars/'.$avatarName);
                $avatarName = $up;
            } else { $errors[] = 'Avatar yüklenemedi'; }
        }

        $coverName = $currentUser['cover_photo'] ?? null;
        if (!empty($_POST['cover_base64'])) {
            $base64 = $_POST['cover_base64'];
            list($type, $base64) = explode(';', $base64);
            list(, $base64)      = explode(',', $base64);
            $data = base64_decode($base64);
            $newName = 'cv_' . time() . '_' . uniqid() . '.jpg';
            if (file_put_contents(UPLOADS_PATH.'covers/'.$newName, $data)) {
                if ($coverName && file_exists(UPLOADS_PATH.'covers/'.$coverName)) unlink(UPLOADS_PATH.'covers/'.$coverName);
                $coverName = $newName;
            } else { $errors[] = 'Kapak fotoğrafı kaydedilemedi'; }
        } elseif (!empty($_FILES['cover']['name'])) {
            $up = uploadImage($_FILES['cover'], 'covers', 'cv_');
            if ($up) {
                if ($coverName && file_exists(UPLOADS_PATH.'covers/'.$coverName)) unlink(UPLOADS_PATH.'covers/'.$coverName);
                $coverName = $up;
            } else { $errors[] = 'Kapak fotoğrafı yüklenemedi'; }
        }

        if (empty($errors)) {
            db()->query("UPDATE users SET about=?, signature=?, avatar=?, cover_photo=?, location=?, birth_date=?, gender=?, website=?, twitter=?, github=?, discord=?, youtube=?, instagram=?, telegram_user=?, telegram_channel=? WHERE id=?",
                [$about, $signature, $avatarName, $coverName, $location ?: null, $birthDate ?: null, $gender, $website ?: null, $twitter ?: null, $github ?: null, $discord ?: null, $youtube ?: null, $instagram ?: null, $telegramUser ?: null, $telegramChannel ?: null, $currentUser['id']],
                'sssssssssssssssi');
            $success = 'Ayarlar başarıyla güncellendi';
            $currentUser = db()->fetchOne("SELECT * FROM users WHERE id=?", [$currentUser['id']], 'i');
        }
    }

    if ($action === 'password' && empty($errors)) {
        $cur  = $_POST['current_password'] ?? '';
        $new  = $_POST['new_password'] ?? '';
        $conf = $_POST['new_password_confirm'] ?? '';
        if (!password_verify($cur, $currentUser['password'])) $errors[] = 'Mevcut şifre hatalı';
        if (strlen($new) < 6) $errors[] = 'Yeni şifre en az 6 karakter olmalıdır';
        if ($new !== $conf) $errors[] = 'Şifreler eşleşmiyor';
        if (empty($errors)) {
            db()->query("UPDATE users SET password=? WHERE id=?", [password_hash($new, PASSWORD_BCRYPT), $currentUser['id']], 'si');
            $success = 'Şifre başarıyla güncellendi';
        }
    }
}

$pageTitle = 'Ayarlar';
require_once 'includes/header.php';
?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<style>
.settings-container { max-width: 1100px; margin: 0 auto; padding: 40px 16px 80px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.section-title { font-family: 'Clash Display', sans-serif; font-size: 28px; font-weight: 700; color: var(--ink-0); margin: 0; }
.profile-tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid var(--border-1); padding-bottom: 10px; }
.profile-tab { padding: 10px 20px; font-weight: 600; font-size: 14px; color: var(--ink-2); text-decoration: none; border-radius: 8px; transition: all 0.2s; }
.profile-tab:hover { background: var(--bg-raised); color: var(--ink-0); }
.profile-tab.active { background: var(--accent); color: #fff; }
.card { background: var(--bg-surface); border-radius: 16px; padding: 30px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-1); margin-bottom: 30px; transition: all 0.2s; }
.card-title { font-family: 'Clash Display', sans-serif; font-size: 18px; font-weight: 700; color: var(--ink-0); margin-bottom: 24px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border-0); padding-bottom: 15px; }
.form-group { margin-bottom: 20px; }
.form-label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--ink-2); }
.form-label span { color: var(--ink-3); font-weight: 400; font-size: 12px; }
.form-input { width: 100%; padding: 12px 16px; border: 1px solid var(--border-1); border-radius: 10px; font-size: 14px; background: var(--bg-raised); color: var(--ink-1); transition: all 0.2s; outline: none; }
.form-input:focus { border-color: var(--accent-border); box-shadow: 0 0 0 3px var(--accent-dim); background: var(--bg-surface); color: var(--ink-0); }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.input-icon-wrapper { position: relative; display: flex; align-items: center; }
.input-icon-wrapper svg { position: absolute; left: 14px; width: 18px; height: 18px; color: var(--ink-3); }
.input-icon-wrapper .form-input { padding-left: 42px; }
.btn-primary { background: var(--accent); color: #fff; padding: 14px 32px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 14.5px; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px var(--accent-glow); background: var(--accent-hover); }
.btn-ghost { background: var(--bg-raised); color: var(--ink-2); border: 1px solid var(--border-1); padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 500; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
.btn-ghost:hover { background: var(--bg-elevated); color: var(--ink-0); border-color: var(--border-2); }
.rich-textarea { background: var(--bg-raised); border-left: 3px solid var(--accent); }
</style>

<div class="settings-container">

    <div class="section-header">
        <h1 class="section-title">Hesap Ayarları</h1>
        <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($currentUser['username']) ?>" class="btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Profili Görüntüle
        </a>
    </div>

    <div class="profile-tabs">
        <a href="?tab=profile" class="profile-tab <?= $tab==='profile'?'active':'' ?>">Kişisel Bilgiler</a>
        <a href="?tab=social" class="profile-tab <?= $tab==='social'?'active':'' ?>">Sosyal Medya</a>
        <a href="?tab=password" class="profile-tab <?= $tab==='password'?'active':'' ?>">Güvenlik & Şifre</a>
    </div>

    <?php if ($success): ?>
    <div class="mb-5 p-4" style="background:var(--green-dim);border:1px solid rgba(61,214,140,.3);border-radius:12px;">
        <p style="color:var(--green);font-size:14px;font-weight:500;display:flex;align-items:center;gap:10px;margin:0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <?= sanitize($success) ?>
        </p>
    </div>
    <?php endif; ?>

    <?php if ($errors): ?>
    <div class="mb-5 p-4" style="background:var(--red-dim);border:1px solid rgba(255,95,87,.3);border-radius:12px;">
        <?php foreach ($errors as $e): ?>
        <p style="color:var(--red);font-size:14px;font-weight:500;margin:4px 0;display:flex;align-items:center;gap:10px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= sanitize($e) ?>
        </p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($tab === 'profile'): ?>
    <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="profile">
        <input type="hidden" name="avatar_base64" id="avatar_base64">
        <input type="hidden" name="cover_base64" id="cover_base64">

        <div class="card">
            <h2 class="card-title">Görünüm Ayarları</h2>
            <div style="display:flex;gap:40px;align-items:center;flex-wrap:wrap">
                <div style="text-align:center;position:relative;">
                    <img src="<?= getAvatar($currentUser) ?>" id="av-preview" alt="" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--bg-surface);box-shadow:var(--shadow-sm);display:block;margin:0 auto 16px">
                    <label class="btn-ghost" style="font-size:12px;padding:6px 12px;">
                        Avatar Seç (Max 15MB)
                        <input type="file" name="avatar" accept="image/*" onchange="openCropper(this, 'avatar')" style="display:none">
                    </label>
                </div>
                <div style="text-align:center;flex:1;min-width:250px;">
                    <img src="<?= getCover($currentUser) ?>" id="cover-preview" alt="" style="width:100%;height:100px;border-radius:12px;object-fit:cover;border:1px solid var(--border-1);display:block;margin:0 auto 16px">
                    <label class="btn-ghost" style="font-size:12px;padding:6px 12px;">
                        Kapak Fotoğrafı Seç (Max 15MB)
                        <input type="file" name="cover" accept="image/*" onchange="openCropper(this, 'cover')" style="display:none">
                    </label>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">Genel Bilgiler</h2>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Konum</label>
                    <input type="text" name="location" value="<?= sanitize($currentUser['location'] ?? '') ?>" class="form-input" placeholder="Örn: İstanbul, Türkiye">
                </div>
                <div class="form-group">
                    <label class="form-label">Cinsiyet</label>
                    <select name="gender" class="form-input" style="appearance:none;">
                        <option value="">Belirtilmedi</option>
                        <option value="male" <?= ($currentUser['gender']??'')==='male'?'selected':'' ?>>Erkek</option>
                        <option value="female" <?= ($currentUser['gender']??'')==='female'?'selected':'' ?>>Kadın</option>
                        <option value="other" <?= ($currentUser['gender']??'')==='other'?'selected':'' ?>>Diğer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Doğum Tarihi</label>
                    <input type="date" name="birth_date" value="<?= $currentUser['birth_date'] ?? '' ?>" class="form-input">
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">Hakkımda & İmza</h2>
            <div class="form-group">
                <label class="form-label">Hakkımda <span>(Profilinizde görünür, max 1000 karakter)</span></label>
                <textarea name="about" class="form-input" style="min-height:120px;resize:vertical;" placeholder="Kendinizden bahsedin..."><?= sanitize($currentUser['about'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Forum İmzası <span>(Mesajlarınızın altında görünür, max 500 karakter)</span></label>
                <textarea name="signature" class="form-input rich-textarea" style="min-height:80px;resize:vertical;" placeholder="İmzanızı buraya yazın..."><?= sanitize($currentUser['signature'] ?? '') ?></textarea>
            </div>
        </div>

        <div style="text-align:right">
            <button type="submit" class="btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Değişiklikleri Kaydet
            </button>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($tab === 'social'): ?>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="profile">

        <div class="card">
            <h2 class="card-title">Bağlantılar ve Sosyal Ağlar</h2>
            <div class="grid-2">
                
                <div class="form-group">
                    <label class="form-label">Kişisel Website</label>
                    <div class="input-icon-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        <input type="url" name="website" value="<?= sanitize($currentUser['website'] ?? '') ?>" class="form-input" placeholder="https://site.com">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">GitHub</label>
                    <div class="input-icon-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
                        <input type="text" name="github" value="<?= sanitize($currentUser['github'] ?? '') ?>" class="form-input" placeholder="kullaniciadi">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Discord</label>
                    <div class="input-icon-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M12 2v20M8 8l8 8M16 8l-8 8"/></svg>
                        <input type="text" name="discord" value="<?= sanitize($currentUser['discord'] ?? '') ?>" class="form-input" placeholder="kullaniciadi">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Twitter / X</label>
                    <div class="input-icon-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
                        <input type="text" name="twitter" value="<?= sanitize($currentUser['twitter'] ?? '') ?>" class="form-input" placeholder="@kullaniciadi">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">YouTube</label>
                    <div class="input-icon-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33 2.78 2.78 0 0 0 1.94 2c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.33 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
                        <input type="text" name="youtube" value="<?= sanitize($currentUser['youtube'] ?? '') ?>" class="form-input" placeholder="@kanaladi">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Instagram</label>
                    <div class="input-icon-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                        <input type="text" name="instagram" value="<?= sanitize($currentUser['instagram'] ?? '') ?>" class="form-input" placeholder="@kullaniciadi">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Telegram (Kullanıcı)</label>
                    <div class="input-icon-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                        <input type="text" name="telegram_user" value="<?= sanitize($currentUser['telegram_user'] ?? '') ?>" class="form-input" placeholder="@kullaniciadi">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Telegram (Kanal)</label>
                    <div class="input-icon-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5L6 9H2v6h4l5 4V5zM15.54 8.46a5 5 0 0 1 0 7.07M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
                        <input type="text" name="telegram_channel" value="<?= sanitize($currentUser['telegram_channel'] ?? '') ?>" class="form-input" placeholder="@kanaladi">
                    </div>
                </div>

            </div>
        </div>

        <div style="text-align:right">
            <button type="submit" class="btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Bağlantıları Kaydet
            </button>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($tab === 'password'): ?>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="password">

        <div class="card" style="max-width:550px;margin:0 auto;">
            <h2 class="card-title">Şifre Değiştir</h2>
            
            <div class="form-group">
                <label class="form-label">Mevcut Şifre</label>
                <input type="password" name="current_password" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Yeni Şifre</label>
                <input type="password" name="new_password" class="form-input" required minlength="6">
            </div>
            <div class="form-group">
                <label class="form-label">Yeni Şifre (Tekrar)</label>
                <input type="password" name="new_password_confirm" class="form-input" required>
            </div>

            <div style="margin-top:30px">
                <button type="submit" class="btn-primary" style="width:100%;justify-content:center">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Güvenlik Bilgilerini Güncelle
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>

</div>

<div id="cropModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:9999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(5px)">
    <div style="background:var(--bg-surface);padding:24px;border-radius:16px;width:100%;max-width:700px;border:1px solid var(--border-1);box-shadow:var(--shadow-xl)">
        <h3 style="color:var(--ink-0);margin-bottom:20px;font-family:'Clash Display',sans-serif;font-size:18px;font-weight:700">Görseli Ayarla</h3>
        <div style="max-height:55vh;overflow:hidden;margin-bottom:20px;border-radius:12px;background:var(--bg-raised)">
            <img id="cropImage" src="" style="max-width:100%;display:block;">
        </div>
        <div style="text-align:right;display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn-ghost" onclick="closeCropModal()">İptal Et</button>
            <button type="button" class="btn-primary" id="cropBtn">Onayla ve Kırp</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
let cropper = null;
let currentCropType = '';

function openCropper(input, type) {
    if (input.files && input.files[0]) {
        if (input.files[0].size > 15 * 1024 * 1024) {
            alert('Lütfen 15MB veya daha küçük boyutlu bir dosya seçin.');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('cropImage').src = e.target.result;
            document.getElementById('cropModal').style.display = 'flex';
            currentCropType = type;
            
            if (cropper) { cropper.destroy(); }
            
            const aspectRatio = type === 'avatar' ? 1 : 2.5; 
            
            cropper = new Cropper(document.getElementById('cropImage'), {
                aspectRatio: aspectRatio,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
            });
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function closeCropModal() {
    document.getElementById('cropModal').style.display = 'none';
    if (cropper) { cropper.destroy(); cropper = null; }
    
    let avInput = document.querySelector('input[name="avatar"]');
    let cvInput = document.querySelector('input[name="cover"]');
    if(avInput) avInput.value = '';
    if(cvInput) cvInput.value = '';
}

document.getElementById('cropBtn').addEventListener('click', function() {
    if (!cropper) return;
    
    const canvas = cropper.getCroppedCanvas({
        width: currentCropType === 'avatar' ? 400 : 1200,
        height: currentCropType === 'avatar' ? 400 : 480
    });
    
    const base64 = canvas.toDataURL('image/jpeg', 0.9);
    
    if (currentCropType === 'avatar') {
        document.getElementById('av-preview').src = base64;
        document.getElementById('avatar_base64').value = base64;
    } else {
        document.getElementById('cover-preview').src = base64;
        document.getElementById('cover_base64').value = base64;
    }
    
    closeCropModal();
});
</script>

<?php require_once 'includes/footer.php'; ?>