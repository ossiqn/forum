<?php
require_once '../includes/functions.php';
requireAdmin();

if (!defined('AD_POSITIONS')) {
    define('AD_POSITIONS', [
        'header_banner' => 'Header Banner (970x90)',
        'footer_banner' => 'Footer Banner',
        'sidebar_top' => 'Sidebar Üst'
    ]);
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Geçersiz istek'; }
    $action = $_POST['action'] ?? '';

    if ($action === 'create' && empty($errors)) {
        $title    = trim($_POST['title'] ?? '');
        $position = $_POST['position'] ?? '';
        $type     = $_POST['type'] ?? 'code';
        $linkUrl  = trim($_POST['link_url'] ?? '');
        $htmlCode = $_POST['html_code'] ?? '';
        $altText  = trim($_POST['alt_text'] ?? '');
        $startsAt = !empty($_POST['starts_at']) ? $_POST['starts_at'] : null;
        $endsAt   = !empty($_POST['ends_at']) ? $_POST['ends_at'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $imageUrl = null;
        if ($type === 'image' && !empty($_FILES['ad_image']['name'])) {
            $up = uploadImage($_FILES['ad_image'], 'avatars', 'ad_');
            if ($up) {
                $imageUrl = UPLOADS_URL . 'avatars/' . $up;
            } else { 
                $errors[] = 'Görsel yüklenemedi. Lütfen boyutu ve formatı kontrol edin.'; 
            }
        }

        if (empty($title)) $errors[] = 'Başlık zorunludur';
        if (!array_key_exists($position, AD_POSITIONS)) $errors[] = 'Geçersiz pozisyon seçildi';

        if (empty($errors)) {
            try {
                db()->query("INSERT INTO advertisements (title, position, type, image_url, link_url, html_code, alt_text, starts_at, ends_at, is_active, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [$title, $position, $type, $imageUrl, $linkUrl ?: null, $htmlCode ?: null, $altText ?: null, $startsAt, $endsAt, $isActive, currentUser()['id']],
                    'sssssssssii');
                $success = 'Reklam başarıyla oluşturuldu ve eklendi.';
            } catch (\Exception $e) {
                $errors[] = 'Veritabanı Hatası: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'toggle' && empty($errors)) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                db()->query("UPDATE advertisements SET is_active = NOT is_active WHERE id = ?", [$id], 'i');
                $success = 'Reklam durumu güncellendi';
            } catch (\Exception $e) {
                $errors[] = 'Hata: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'delete' && empty($errors)) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                db()->query("DELETE FROM advertisements WHERE id = ?", [$id], 'i');
                $success = 'Reklam tamamen silindi';
            } catch (\Exception $e) {
                $errors[] = 'Hata: ' . $e->getMessage();
            }
        }
    }
}

$ads = [];
try {
    $ads = db()->fetchAll("SELECT a.*, u.username as creator FROM advertisements a LEFT JOIN users u ON u.id = a.created_by ORDER BY a.created_at DESC");
} catch (\Exception $e) {
    $errors[] = 'Reklamlar çekilemedi, lütfen veritabanı tablosunu kontrol edin. Hata: ' . $e->getMessage();
}

$pageTitle = 'Reklam Yönetimi';
require_once '../includes/header.php';
?>
<div style="max-width:1100px;margin:0 auto;padding:20px 16px 60px">
    <div style="display:flex;gap:20px;align-items:flex-start">
        <aside class="admin-sidebar hidden lg:block">
            <div class="mb-4 pb-4 border-b" style="border-color:var(--border-0)">
                <span style="font-family:'JetBrains Mono',monospace;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3);padding:0 4px">Admin Panel</span>
            </div>
            <nav>
                <a href="<?= SITE_URL ?>/admin/index.php" class="admin-nav-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
                <a href="<?= SITE_URL ?>/admin/users.php" class="admin-nav-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    Kullanıcılar
                </a>
                <a href="<?= SITE_URL ?>/admin/forums.php" class="admin-nav-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    Forum Yönetimi
                </a>
                <a href="<?= SITE_URL ?>/admin/threads.php" class="admin-nav-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Konular
                </a>
                <a href="<?= SITE_URL ?>/admin/badges.php" class="admin-nav-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                    Rozetler
                </a>
                <a href="<?= SITE_URL ?>/admin/ads.php" class="admin-nav-item active">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    Reklamlar
                </a>
                <a href="<?= SITE_URL ?>/admin/reports.php" class="admin-nav-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                    Raporlar
                </a>
                <div class="mt-4 pt-4" style="border-top:1px solid var(--border-0)">
                    <a href="<?= SITE_URL ?>/index.php" class="admin-nav-item">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                        Siteye Dön
                    </a>
                </div>
            </nav>
        </aside>

        <main style="flex:1;min-width:0">
            <div class="section-header mb-6">
                <h1 class="section-title">Reklam Yönetimi</h1>
                <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--ink-3)"><?= count($ads) ?> reklam</span>
            </div>

            <?php if ($success): ?>
            <div class="mb-4 p-3 rounded-xl" style="background:var(--green-dim);border:1px solid rgba(61,214,140,.2)">
                <p style="color:var(--green);font-size:13.5px"><?= sanitize($success) ?></p>
            </div>
            <?php endif; ?>
            <?php if ($errors): ?>
            <div class="mb-4 p-3 rounded-xl" style="background:var(--red-dim);border:1px solid rgba(255,95,87,.2)">
                <?php foreach ($errors as $e): ?><p style="color:var(--red);font-size:13.5px;margin-bottom:4px;"><?= sanitize($e) ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="card mb-7">
                <h2 style="font-family:'Clash Display',sans-serif;font-size:15px;font-weight:700;color:var(--ink-0);margin-bottom:20px">Basit Reklam Ekle</h2>
                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Reklam Adı / Başlığı</label>
                            <input type="text" name="title" class="form-control" placeholder="Örn: Ana Sayfa Header Banner" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gösterilecek Konum</label>
                            <select name="position" class="form-control" required>
                                <?php foreach (AD_POSITIONS as $key => $label): ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Reklam Türü</label>
                            <select name="type" class="form-control" id="ad-type" onchange="adTypeChange(this.value)">
                                <option value="image">Görsel (Banner) Yükle</option>
                                <option value="code">HTML / Adsense Kodu Gir</option>
                            </select>
                        </div>
                        <div class="form-group" id="link-field">
                            <label class="form-label">Tıklanınca Gidilecek Link</label>
                            <input type="url" name="link_url" class="form-control" placeholder="https://siteadresi.com">
                        </div>
                    </div>

                    <div id="image-field" class="form-group" style="padding:15px;background:var(--bg-raised);border-radius:10px;border:1px dashed var(--border-2);margin-top:10px;">
                        <label class="form-label">Reklam Görseli Seç (970x90 vs.)</label>
                        <input type="file" name="ad_image" class="form-control" accept="image/*" style="background:var(--bg-surface)">
                    </div>

                    <div id="code-field" class="form-group" style="display:none;margin-top:10px;">
                        <label class="form-label">HTML veya Adsense Kodu</label>
                        <textarea name="html_code" class="form-control" rows="5" placeholder='Kodu buraya yapıştırın...' style="font-family:'JetBrains Mono',monospace;font-size:13px"></textarea>
                    </div>

                    <div class="form-group" style="display:flex;align-items:center;gap:10px;margin-top:20px;padding:15px;background:var(--bg-raised);border-radius:10px;">
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked style="width:18px;height:18px;cursor:pointer;">
                        <label for="is_active" style="font-size:14px;font-weight:600;color:var(--ink-0);cursor:pointer;user-select:none;">Reklamı Hemen Sitede Göster (Aktif Et)</label>
                    </div>

                    <div style="text-align:right;margin-top:20px">
                        <button type="submit" class="btn-primary" style="padding:12px 30px;font-size:14px;">Reklamı Kaydet ve Ekle</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2 style="font-family:'Clash Display',sans-serif;font-size:15px;font-weight:700;color:var(--ink-0);margin-bottom:20px">Eklenmiş Reklamlar</h2>
                
                <?php if(empty($ads)): ?>
                <div class="empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto;opacity:0.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    <p style="margin-top:10px;color:var(--ink-3)">Henüz eklenmiş bir reklam bulunmuyor.</p>
                </div>
                <?php else: ?>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Başlık</th>
                                <th>Konum</th>
                                <th>Tür</th>
                                <th>Durum</th>
                                <th style="text-align:right">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ads as $ad): ?>
                            <tr>
                                <td style="font-family:'JetBrains Mono',monospace;color:var(--ink-3)">#<?= $ad['id'] ?></td>
                                <td style="font-weight:600;color:var(--ink-1)"><?= sanitize($ad['title']) ?></td>
                                <td>
                                    <span style="font-size:11px;padding:3px 8px;background:var(--bg-elevated);border:1px solid var(--border-1);border-radius:6px;font-family:'JetBrains Mono',monospace;">
                                        <?= sanitize($ad['position']) ?>
                                    </span>
                                </td>
                                <td><span class="badge" style="background:var(--bg-raised);color:var(--ink-2);border-color:var(--border-1)"><?= $ad['type'] === 'image' ? 'Görsel' : 'Kod' ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                                        <button type="submit" style="background:none;border:none;cursor:pointer;padding:0">
                                            <?php if($ad['is_active']): ?>
                                            <span style="font-size:11px;padding:3px 8px;background:var(--green-dim);color:var(--green);border-radius:6px;font-weight:600;">Yayında</span>
                                            <?php else: ?>
                                            <span style="font-size:11px;padding:3px 8px;background:var(--red-dim);color:var(--red);border-radius:6px;font-weight:600;">Kapalı</span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td style="text-align:right">
                                    <form method="POST" style="display:inline" onsubmit="event.preventDefault(); openConfirmModal('Bu reklamı tamamen silmek istediğinize emin misiniz?', this);">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                                        <button type="submit" class="btn-danger" style="padding:6px 10px;font-size:12px">Sil</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
function adTypeChange(val) {
    const codeField = document.getElementById('code-field');
    const imgField = document.getElementById('image-field');
    const linkField = document.getElementById('link-field');
    
    if (val === 'code') {
        codeField.style.display = 'block';
        imgField.style.display = 'none';
        linkField.style.display = 'none';
    } else {
        codeField.style.display = 'none';
        imgField.style.display = 'block';
        linkField.style.display = 'block';
    }
}
document.addEventListener("DOMContentLoaded", () => {
    adTypeChange(document.getElementById('ad-type').value);
});
</script>

<?php require_once '../includes/footer.php'; ?>