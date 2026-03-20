<?php
require_once '../includes/functions.php';
requireAdmin();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Geçersiz istek'; }
    $action = $_POST['action'] ?? '';

    if ($action === 'add_category' && empty($errors)) {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#3b82f6');
        $order = (int)($_POST['display_order'] ?? 0);
        if (strlen($name) < 2) { $errors[] = 'Kategori adı en az 2 karakter olmalı'; }
        if (empty($errors)) {
            $slug = uniqueSlug('categories', generateSlug($name));
            db()->query("INSERT INTO categories (name, slug, description, color, display_order) VALUES (?,?,?,?,?)", [$name, $slug, $desc, $color, $order], 'ssssi');
            $success = 'Kategori eklendi';
        }
    }

    if ($action === 'add_forum' && empty($errors)) {
        $catId = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $order = (int)($_POST['display_order'] ?? 0);
        if (!$catId || strlen($name) < 2) { $errors[] = 'Geçersiz veri'; }
        if (empty($errors)) {
            $slug = uniqueSlug('forums', generateSlug($name));
            db()->query("INSERT INTO forums (category_id, name, slug, description, display_order) VALUES (?,?,?,?,?)", [$catId, $name, $slug, $desc, $order], 'isssi');
            $success = 'Forum eklendi';
        }
    }

    if ($action === 'delete_category') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) db()->query("DELETE FROM categories WHERE id = ?", [$id], 'i');
        $success = 'Kategori silindi';
    }

    if ($action === 'delete_forum') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) db()->query("DELETE FROM forums WHERE id = ?", [$id], 'i');
        $success = 'Forum silindi';
    }

    if ($action === 'edit_category') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#3b82f6');
        $order = (int)($_POST['display_order'] ?? 0);
        if ($id && strlen($name) >= 2) {
            db()->query("UPDATE categories SET name = ?, description = ?, color = ?, display_order = ? WHERE id = ?", [$name, $desc, $color, $order, $id], 'sssii');
            $success = 'Kategori güncellendi';
        }
    }

    if ($action === 'edit_forum') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $order = (int)($_POST['display_order'] ?? 0);
        $catId = (int)($_POST['category_id'] ?? 0);
        if ($id && strlen($name) >= 2) {
            db()->query("UPDATE forums SET name = ?, description = ?, display_order = ?, category_id = ? WHERE id = ?", [$name, $desc, $order, $catId, $id], 'ssiii');
            $success = 'Forum güncellendi';
        }
    }
}

$categories = db()->fetchAll("SELECT c.*, COUNT(f.id) as forum_count FROM categories c LEFT JOIN forums f ON f.category_id = c.id GROUP BY c.id ORDER BY c.display_order");
$forums = db()->fetchAll("SELECT f.*, c.name as cat_name FROM forums f JOIN categories c ON c.id = f.category_id ORDER BY c.display_order, f.display_order");

$pageTitle = 'Forum Yönetimi';
require_once '../includes/header.php';
?>
<div style="max-width:1100px;margin:0 auto;padding:20px 16px 60px">
    <div style="display:flex;gap:20px;align-items:flex-start">
        <aside class="admin-sidebar hidden lg:block">
            <div class="mb-4 pb-4 border-b border-surface-50">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 px-2">Admin Panel</span>
            </div>
            <nav>
                <a href="<?= SITE_URL ?>/admin/index.php" class="admin-nav-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
                <a href="<?= SITE_URL ?>/admin/users.php" class="admin-nav-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    Kullanıcılar
                </a>
                <a href="<?= SITE_URL ?>/admin/forums.php" class="admin-nav-item active">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    Forum Yönetimi
                </a>
                <a href="<?= SITE_URL ?>/admin/threads.php" class="admin-nav-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Konular
                </a>
                <a href="<?= SITE_URL ?>/admin/badges.php" class="admin-nav-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                    Rozetler
                </a>
                <a href="<?= SITE_URL ?>/admin/ads.php" class="admin-nav-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    Reklamlar
                </a>
                <a href="<?= SITE_URL ?>/admin/reports.php" class="admin-nav-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                    Raporlar
                </a>
                <div class="mt-4 pt-4 border-t border-surface-50">
                    <a href="<?= SITE_URL ?>/index.php" class="admin-nav-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                        Siteye Dön
                    </a>
                </div>
            </nav>
        </aside>

        <main style="flex:1;min-width:0">
            <h1 class="text-2xl font-bold mb-6">Forum Yönetimi</h1>

            <?php if ($success): ?>
            <div class="mb-4 p-3 bg-green-900/20 border border-green-700/30 rounded-lg text-green-400 text-sm"><?= sanitize($success) ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
            <div class="mb-4 p-3 bg-red-900/20 border border-red-700/30 rounded-lg">
                <?php foreach ($errors as $e): ?><p class="text-red-400 text-sm"><?= sanitize($e) ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="card">
                    <h2 class="text-base font-semibold mb-4">Yeni Kategori Ekle</h2>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="add_category">
                        <div class="form-group">
                            <label class="form-label">Kategori Adı</label>
                            <input type="text" name="name" class="form-control" placeholder="Game Hacking" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Açıklama</label>
                            <input type="text" name="description" class="form-control" placeholder="Kısa açıklama...">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-group">
                                <label class="form-label">Renk</label>
                                <input type="color" name="color" class="form-control" value="#3b82f6" style="height:42px;padding:4px">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sıra</label>
                                <input type="number" name="display_order" class="form-control" value="0" min="0">
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">Kategori Ekle</button>
                    </form>
                </div>

                <div class="card">
                    <h2 class="text-base font-semibold mb-4">Yeni Forum Ekle</h2>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="add_forum">
                        <div class="form-group">
                            <label class="form-label">Kategori</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">Seçin...</option>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Forum Adı</label>
                            <input type="text" name="name" class="form-control" placeholder="Cheat Engine" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Açıklama</label>
                            <input type="text" name="description" class="form-control" placeholder="Kısa açıklama...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sıra</label>
                            <input type="number" name="display_order" class="form-control" value="0" min="0">
                        </div>
                        <button type="submit" class="btn-primary">Forum Ekle</button>
                    </form>
                </div>
            </div>

            <div class="card overflow-hidden p-0 mb-6">
                <div class="p-4 border-b border-surface-50">
                    <h2 style="font-weight:600">Kategoriler (<?= count($categories) ?>)</h2>
                </div>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Adı</th>
                                <th>Slug</th>
                                <th>Forum Sayısı</th>
                                <th>Sıra</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $c): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <span class="w-3 h-3 rounded-full flex-shrink-0" style="background:<?= sanitize($c['color']) ?>"></span>
                                        <?= sanitize($c['name']) ?>
                                    </div>
                                </td>
                                <td class="font-mono text-xs text-gray-400"><?= sanitize($c['slug']) ?></td>
                                <td><?= $c['forum_count'] ?></td>
                                <td><?= $c['display_order'] ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <button onclick="openEditCategory(<?= htmlspecialchars(json_encode($c)) ?>)" class="text-primary-light text-xs hover:underline">Düzenle</button>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Silmek istediğinize emin misiniz? Tüm alt forumlar ve konular da silinecektir!')">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="text-danger text-xs hover:underline">Sil</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card overflow-hidden p-0">
                <div class="p-4 border-b border-surface-50">
                    <h2 style="font-weight:600">Forumlar (<?= count($forums) ?>)</h2>
                </div>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Adı</th>
                                <th>Kategori</th>
                                <th>Konular</th>
                                <th>Mesajlar</th>
                                <th>Sıra</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($forums as $f): ?>
                            <tr>
                                <td style="font-weight:500"><?= sanitize($f['name']) ?></td>
                                <td style="font-size:13.5px;color:var(--ink-2)"><?= sanitize($f['cat_name']) ?></td>
                                <td class="font-mono text-sm"><?= $f['thread_count'] ?></td>
                                <td class="font-mono text-sm"><?= $f['post_count'] ?></td>
                                <td><?= $f['display_order'] ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <button onclick="openEditForum(<?= htmlspecialchars(json_encode($f)) ?>)" class="text-primary-light text-xs hover:underline">Düzenle</button>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Forumu silmek istediğinize emin misiniz?')">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_forum">
                                            <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                            <button type="submit" class="text-danger text-xs hover:underline">Sil</button>
                                        </form>
                                    </div>
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

<div id="edit-category-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm" style="display:none">
    <div class="bg-surface border border-surface-50 rounded-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">Kategoriyi Düzenle</h3>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit_category">
            <input type="hidden" name="id" id="edit-cat-id">
            <div class="form-group">
                <label class="form-label">Ad</label>
                <input type="text" name="name" id="edit-cat-name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Açıklama</label>
                <input type="text" name="description" id="edit-cat-desc" class="form-control">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="form-group">
                    <label class="form-label">Renk</label>
                    <input type="color" name="color" id="edit-cat-color" class="form-control" style="height:42px;padding:4px">
                </div>
                <div class="form-group">
                    <label class="form-label">Sıra</label>
                    <input type="number" name="display_order" id="edit-cat-order" class="form-control">
                </div>
            </div>
            <div class="flex gap-3 mt-2">
                <button type="submit" class="btn-primary">Kaydet</button>
                <button type="button" onclick="closeModal('edit-category-modal')" class="btn-ghost">İptal</button>
            </div>
        </form>
    </div>
</div>

<div id="edit-forum-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm" style="display:none">
    <div class="bg-surface border border-surface-50 rounded-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">Forumu Düzenle</h3>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit_forum">
            <input type="hidden" name="id" id="edit-forum-id">
            <div class="form-group">
                <label class="form-label">Kategori</label>
                <select name="category_id" id="edit-forum-cat" class="form-control">
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Ad</label>
                <input type="text" name="name" id="edit-forum-name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Açıklama</label>
                <input type="text" name="description" id="edit-forum-desc" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Sıra</label>
                <input type="number" name="display_order" id="edit-forum-order" class="form-control">
            </div>
            <div class="flex gap-3 mt-2">
                <button type="submit" class="btn-primary">Kaydet</button>
                <button type="button" onclick="closeModal('edit-forum-modal')" class="btn-ghost">İptal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditCategory(data) {
    document.getElementById('edit-cat-id').value = data.id;
    document.getElementById('edit-cat-name').value = data.name;
    document.getElementById('edit-cat-desc').value = data.description || '';
    document.getElementById('edit-cat-color').value = data.color || '#3b82f6';
    document.getElementById('edit-cat-order').value = data.display_order;
    openModal('edit-category-modal');
}

function openEditForum(data) {
    document.getElementById('edit-forum-id').value = data.id;
    document.getElementById('edit-forum-name').value = data.name;
    document.getElementById('edit-forum-desc').value = data.description || '';
    document.getElementById('edit-forum-order').value = data.display_order;
    document.getElementById('edit-forum-cat').value = data.category_id;
    openModal('edit-forum-modal');
}

function openModal(id) {
    const m = document.getElementById(id);
    m.style.display = 'flex';
    gsap.from(m.querySelector('div'), { scale: 0.9, opacity: 0, duration: 0.25, ease: 'back.out' });
}

function closeModal(id) {
    const m = document.getElementById(id);
    gsap.to(m.querySelector('div'), { scale: 0.9, opacity: 0, duration: 0.2, onComplete: () => m.style.display = 'none' });
}
</script>
<?php require_once '../includes/footer.php'; ?>
