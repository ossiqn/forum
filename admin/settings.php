<?php
require_once '../includes/functions.php';
requireAdmin();

$success = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Geçersiz istek'; }
    if (empty($errors)) {
        $fields = ['site_description','site_keywords','default_theme','registration_open','maintenance_mode','ads_enabled','allow_dm','allow_media_upload','max_upload_mb','online_threshold_minutes','posts_per_page'];
        foreach ($fields as $key) {
            if (isset($_POST[$key])) {
                setSiteSetting($key, trim($_POST[$key]));
            }
        }
        $success = 'Ayarlar kaydedildi';
    }
}

$settings = [];
$rows = db()->fetchAll("SELECT setting_key, setting_value FROM site_settings");
foreach ($rows as $row) { $settings[$row['setting_key']] = $row['setting_value']; }

$pageTitle = 'Site Ayarları';
require_once '../includes/header.php';
?>
<div style="max-width:1100px;margin:0 auto;padding:20px 16px 60px">
    <div style="display:flex;gap:20px;align-items:flex-start">
        <aside class="admin-sidebar hidden lg:block">
            <div style="margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid var(--border-0)">
                <span style="font-family:'JetBrains Mono',monospace;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3)">Admin Panel</span>
            </div>
            <nav>
                <?php
                $navItems = [
                    ['index.php','Dashboard','<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
                    ['users.php','Kullanıcılar','<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
                    ['forums.php','Forum Yönetimi','<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>'],
                    ['threads.php','Konular','<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
                    ['badges.php','Rozetler','<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>'],
                    ['ads.php','Reklamlar','<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>'],
                    ['reports.php','Raporlar','<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>'],
                    ['settings.php','Site Ayarları','<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M16.24 7.76a6 6 0 0 1 0 8.49M4.93 4.93a10 10 0 0 0 0 14.14M7.76 7.76a6 6 0 0 0 0 8.49"/>'],
                ];
                foreach ($navItems as [$file, $label, $svg]):
                    $active = strpos($_SERVER['PHP_SELF'], $file) !== false;
                ?>
                <a href="<?= SITE_URL ?>/admin/<?= $file ?>" class="admin-nav-item <?= $active ? 'active' : '' ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $svg ?></svg>
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
                <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border-0)">
                    <a href="<?= SITE_URL ?>/index.php" class="admin-nav-item">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                        Siteye Dön
                    </a>
                </div>
            </nav>
        </aside>

        <main style="flex:1;min-width:0">
            <h1 class="section-title mb-6">Site Ayarları</h1>

            <?php if ($success): ?>
            <div class="mb-5 p-3 rounded-xl" style="background:var(--green-dim);border:1px solid rgba(61,214,140,.2)"><p style="color:var(--green);font-size:13.5px;display:flex;align-items:center;gap:8px"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><?= sanitize($success) ?></p></div>
            <?php endif; ?>

            <form method="POST">
                <?= csrfField() ?>

                <div class="card mb-6">
                    <h2 style="font-family:'Clash Display',sans-serif;font-size:15px;font-weight:700;color:var(--ink-0);margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--border-0)">Genel Ayarlar</h2>
                    <div class="form-group">
                        <label class="form-label">Site Açıklaması</label>
                        <input type="text" name="site_description" class="form-control" value="<?= sanitize($settings['site_description'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Site Anahtar Kelimeleri</label>
                        <input type="text" name="site_keywords" class="form-control" value="<?= sanitize($settings['site_keywords'] ?? '') ?>" placeholder="forum, game, hack, ...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Varsayılan Tema</label>
                        <select name="default_theme" class="form-control">
                            <option value="dark" <?= ($settings['default_theme']??'dark')==='dark'?'selected':'' ?>>Karanlık</option>
                            <option value="light" <?= ($settings['default_theme']??'')==='light'?'selected':'' ?>>Aydınlık</option>
                        </select>
                    </div>
                </div>

                <div class="card mb-6">
                    <h2 style="font-family:'Clash Display',sans-serif;font-size:15px;font-weight:700;color:var(--ink-0);margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--border-0)">Özellik Ayarları</h2>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <?php
                        $toggles = [
                            ['registration_open',  'Yeni Kayıt Açık',     'Kullanıcılar kayıt olabilir'],
                            ['maintenance_mode',   'Bakım Modu',          'Giriş yapmamış kullanıcılara bakım mesajı göster'],
                            ['ads_enabled',        'Reklamlar Aktif',     'Reklam alanları gösterilsin'],
                            ['allow_dm',           'Özel Mesaj Sistemi',  'Kullanıcılar birbirine DM atabilir'],
                            ['allow_media_upload', 'Medya Yükleme',       'Konu/cevaplarda resim yükleme'],
                        ];
                        foreach ($toggles as [$key, $label, $desc]):
                            $val = $settings[$key] ?? '1';
                        ?>
                        <label style="display:flex;align-items:flex-start;gap:12px;padding:14px;background:var(--bg-raised);border:1px solid var(--border-1);border-radius:10px;cursor:pointer">
                            <input type="checkbox" name="<?= $key ?>" value="1" <?= $val === '1' ? 'checked' : '' ?> style="width:18px;height:18px;margin-top:1px;accent-color:var(--accent)">
                            <div>
                                <div style="font-size:14px;font-weight:600;color:var(--ink-0)"><?= $label ?></div>
                                <div style="font-size:12px;color:var(--ink-3);margin-top:2px"><?= $desc ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card mb-6">
                    <h2 style="font-family:'Clash Display',sans-serif;font-size:15px;font-weight:700;color:var(--ink-0);margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--border-0)">Sayısal Ayarlar</h2>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
                        <div class="form-group">
                            <label class="form-label">Max Yükleme (MB)</label>
                            <input type="number" name="max_upload_mb" class="form-control" value="<?= (int)($settings['max_upload_mb'] ?? 5) ?>" min="1" max="50">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Çevrimiçi Eşiği (dk)</label>
                            <input type="number" name="online_threshold_minutes" class="form-control" value="<?= (int)($settings['online_threshold_minutes'] ?? 15) ?>" min="1" max="60">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sayfa Başına Mesaj</label>
                            <input type="number" name="posts_per_page" class="form-control" value="<?= (int)($settings['posts_per_page'] ?? 15) ?>" min="5" max="50">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Ayarları Kaydet
                </button>
            </form>
        </main>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
