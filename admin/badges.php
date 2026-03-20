<?php
require_once '../includes/functions.php';
requireAdmin();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Geçersiz istek'; }
    $action = $_POST['action'] ?? '';

    if ($action === 'award' && empty($errors)) {
        $username  = trim($_POST['username'] ?? '');
        $badgeSlug = $_POST['badge_slug'] ?? '';
        $user = db()->fetchOne("SELECT id FROM users WHERE username = ?", [$username], 's');
        if (!$user) { $errors[] = 'Kullanıcı bulunamadı'; }
        elseif (!awardBadge($user['id'], $badgeSlug, currentUser()['id'])) { $errors[] = 'Rozet bulunamadı'; }
        else { $success = sanitize($username) . ' kullanıcısına rozet verildi'; }
    }

    if ($action === 'revoke' && empty($errors)) {
        $userId  = (int)($_POST['user_id'] ?? 0);
        $badgeId = (int)($_POST['badge_id'] ?? 0);
        if ($userId && $badgeId) {
            db()->query("DELETE FROM user_badges WHERE user_id = ? AND badge_id = ?", [$userId, $badgeId], 'ii');
            $success = 'Rozet geri alındı';
        }
    }

    if ($action === 'create_badge' && empty($errors)) {
        $name  = trim($_POST['name'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $icon  = trim($_POST['icon'] ?? '⭐');
        $color = trim($_POST['color'] ?? '#5b7fff');
        $type  = $_POST['type'] ?? 'manual';
        if ($name) {
            $slug = generateSlug($name);
            $bg   = $color . '1a';
            db()->query("INSERT INTO badges (name, slug, description, icon, color, bg_color, type) VALUES (?,?,?,?,?,?,?)", [$name, $slug, $desc, $icon, $color, $bg, $type], 'sssssss');
            $success = 'Rozet oluşturuldu';
        }
    }
}

$badges       = db()->fetchAll("SELECT * FROM badges ORDER BY type, name");
$recentAwards = db()->fetchAll("SELECT ub.*, u.username, b.name as badge_name, b.icon, b.color, b.bg_color FROM user_badges ub JOIN users u ON u.id = ub.user_id JOIN badges b ON b.id = ub.badge_id ORDER BY ub.awarded_at DESC LIMIT 20");

$pageTitle = 'Rozet Yönetimi';
require_once '../includes/header.php';

function adminSidebar($active) {
    global $errors;
    $items = [
        ['index.php','Dashboard','<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
        ['users.php','Kullanıcılar','<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
        ['forums.php','Forum Yönetimi','<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>'],
        ['threads.php','Konular','<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
        ['badges.php','Rozetler','<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>'],
        ['ads.php','Reklamlar','<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>'],
        ['reports.php','Raporlar','<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>'],
    ];
    echo '<aside class="admin-sidebar hidden lg:block"><div style="margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid var(--border-0)"><span style="font-family:\'JetBrains Mono\',monospace;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3)">Admin Panel</span></div><nav>';
    foreach ($items as [$file, $label, $svg]) {
        $isActive = strpos($active, $file) !== false;
        echo '<a href="'.SITE_URL.'/admin/'.$file.'" class="admin-nav-item '.($isActive?'active':'').'"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'.$svg.'</svg>'.$label.'</a>';
    }
    echo '<div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border-0)"><a href="'.SITE_URL.'/index.php" class="admin-nav-item"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>Siteye Dön</a></div></nav></aside>';
}
?>

<div style="max-width:1100px;margin:0 auto;padding:20px 16px 60px">
    <div style="display:flex;gap:20px;align-items:flex-start">
        <?php adminSidebar('badges.php'); ?>
        <main style="flex:1;min-width:0">
            <h1 class="section-title mb-6">Rozet Yönetimi</h1>

            <?php if ($success): ?>
            <div class="mb-4 p-3 rounded-xl" style="background:var(--green-dim);border:1px solid rgba(61,214,140,.2)"><p style="color:var(--green);font-size:13.5px"><?= sanitize($success) ?></p></div>
            <?php endif; ?>
            <?php if ($errors): ?>
            <div class="mb-4 p-3 rounded-xl" style="background:var(--red-dim);border:1px solid rgba(255,95,87,.2)"><?php foreach($errors as $e): ?><p style="color:var(--red);font-size:13.5px"><?= sanitize($e) ?></p><?php endforeach; ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
                <div class="card">
                    <h2 style="font-family:'Clash Display',sans-serif;font-size:15px;font-weight:700;color:var(--ink-0);margin-bottom:18px">Kullanıcıya Rozet Ver</h2>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="award">
                        <div class="form-group">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" name="username" class="form-control" placeholder="kullanici_adi" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rozet</label>
                            <select name="badge_slug" class="form-control" required>
                                <option value="">Seçin...</option>
                                <?php foreach ($badges as $b): ?>
                                <option value="<?= sanitize($b['slug']) ?>"><?= $b['icon'] ?> <?= sanitize($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary">Rozet Ver</button>
                    </form>
                </div>

                <div class="card">
                    <h2 style="font-family:'Clash Display',sans-serif;font-size:15px;font-weight:700;color:var(--ink-0);margin-bottom:18px">Yeni Rozet Oluştur</h2>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="create_badge">
                        <div class="form-group">
                            <label class="form-label">Ad</label>
                            <input type="text" name="name" class="form-control" placeholder="Örn: Pro Üye" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Açıklama</label>
                            <input type="text" name="description" class="form-control" placeholder="Kısa açıklama">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                            <div class="form-group">
                                <label class="form-label">İkon (emoji)</label>
                                <input type="text" name="icon" class="form-control" value="⭐" maxlength="4">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Renk</label>
                                <input type="color" name="color" class="form-control" value="#5b7fff" style="height:42px;padding:4px">
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">Oluştur</button>
                    </form>
                </div>
            </div>

            <div class="card mb-6">
                <h2 style="font-family:'Clash Display',sans-serif;font-size:15px;font-weight:700;color:var(--ink-0);margin-bottom:16px">Tüm Rozetler</h2>
                <div class="badge-grid">
                    <?php foreach ($badges as $b): ?>
                    <div class="badge-card">
                        <div class="badge-card-icon"><?= $b['icon'] ?></div>
                        <div style="flex:1;min-width:0">
                            <div class="badge-card-name"><?= sanitize($b['name']) ?></div>
                            <div class="badge-card-desc"><?= sanitize($b['description'] ?? '') ?></div>
                            <span style="font-size:10.5px;font-family:'JetBrains Mono',monospace;color:var(--ink-3)"><?= $b['type'] === 'auto' ? 'Otomatik' : 'Manuel' ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <h2 style="font-family:'Clash Display',sans-serif;font-size:15px;font-weight:700;color:var(--ink-0);margin-bottom:16px">Son Verilen Rozetler</h2>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead><tr><th>Kullanıcı</th><th>Rozet</th><th>Tarih</th><th>İşlem</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentAwards as $a): ?>
                        <tr>
                            <td><a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($a['username']) ?>" style="color:var(--accent);text-decoration:none"><?= sanitize($a['username']) ?></a></td>
                            <td><span style="background:<?= sanitize($a['bg_color']) ?>;color:<?= sanitize($a['color']) ?>;border:1px solid <?= sanitize($a['color']) ?>33;padding:2px 9px;border-radius:999px;font-size:12px;font-weight:700"><?= $a['icon'] ?> <?= sanitize($a['badge_name']) ?></span></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--ink-3)"><?= formatDate($a['awarded_at'], 'd.m.Y') ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Rozeti geri al?')" style="display:inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="revoke">
                                    <input type="hidden" name="user_id" value="<?= $a['user_id'] ?>">
                                    <input type="hidden" name="badge_id" value="<?= $a['badge_id'] ?>">
                                    <button type="submit" class="btn-danger" style="padding:4px 10px;font-size:12px">Geri Al</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
