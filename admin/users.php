<?php
require_once '../includes/functions.php';
requireAdmin();

$action = $_GET['action'] ?? '';
$userId = (int)($_GET['id'] ?? 0);

if ($action && $userId && verifyCsrfToken($_GET['token'] ?? '')) {
    $targetUser = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$userId], 'i');
    if ($targetUser && $targetUser['id'] !== currentUser()['id']) {
        if ($action === 'ban') {
            db()->query("UPDATE users SET is_banned = 1, ban_reason = ? WHERE id = ?", [$_GET['reason'] ?? 'Kural ihlali', $userId], 'si');
        } elseif ($action === 'unban') {
            db()->query("UPDATE users SET is_banned = 0, ban_reason = NULL WHERE id = ?", [$userId], 'i');
        } elseif (in_array($action, ['set_admin', 'set_moderator', 'set_member'])) {
            $role = str_replace('set_', '', $action);
            db()->query("UPDATE users SET role = ? WHERE id = ?", [$role, $userId], 'si');
        }
    }
    header('Location: ' . SITE_URL . '/admin/users.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

$whereClauses = [];
$params = [];
$types = '';

if ($search) {
    $whereClauses[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}
if ($roleFilter) {
    $whereClauses[] = "role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}

$whereStr = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
$data = paginateQuery("SELECT * FROM users $whereStr ORDER BY created_at DESC", $params, $types, $page, 25);

$token = generateCsrfToken();
$pageTitle = 'Kullanıcı Yönetimi';
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
                <a href="<?= SITE_URL ?>/admin/users.php" class="admin-nav-item active">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Kullanıcılar
                </a>
                <a href="<?= SITE_URL ?>/admin/forums.php" class="admin-nav-item">
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
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
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
            <div class="section-header mb-6">
                <h1 class="text-2xl font-bold">Kullanıcı Yönetimi</h1>
                <span style="font-size:13.5px;color:var(--ink-3)"><?= $data['total'] ?> kullanıcı</span>
            </div>

            <div class="card mb-6">
                <form method="GET" class="flex gap-3 flex-wrap">
                    <input type="text" name="search" class="form-control" placeholder="Kullanıcı adı veya e-posta..." value="<?= sanitize($search) ?>" style="flex:1;min-width:200px">
                    <select name="role" class="form-control" style="width:auto">
                        <option value="">Tüm Roller</option>
                        <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="moderator" <?= $roleFilter === 'moderator' ? 'selected' : '' ?>>Moderatör</option>
                        <option value="member" <?= $roleFilter === 'member' ? 'selected' : '' ?>>Üye</option>
                    </select>
                    <button type="submit" class="btn-primary">Filtrele</button>
                    <?php if ($search || $roleFilter): ?>
                    <a href="<?= SITE_URL ?>/admin/users.php" class="btn-ghost">Temizle</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card overflow-hidden p-0">
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kullanıcı</th>
                                <th>E-posta</th>
                                <th>Rol</th>
                                <th>Konu/Mesaj</th>
                                <th>Kayıt Tarihi</th>
                                <th>Durum</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['items'] as $u): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:12px">
                                        <img src="<?= getAvatar($u) ?>" alt="" class="w-8 h-8 rounded-full object-cover">
                                        <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($u['username']) ?>" class="font-medium hover:text-primary-light" target="_blank">
                                            <?= sanitize($u['username']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="text-gray-400 text-sm"><?= sanitize($u['email']) ?></td>
                                <td><span class="role-badge role-<?= $u['role'] ?>"><?= getRoleBadge($u['role'])['label'] ?></span></td>
                                <td class="font-mono text-sm"><?= $u['thread_count'] ?> / <?= $u['post_count'] ?></td>
                                <td style="font-size:13.5px;color:var(--ink-2)"><?= formatDate($u['created_at'], 'd.m.Y') ?></td>
                                <td>
                                    <?php if ($u['is_banned']): ?>
                                    <span class="badge badge-locked">Banlı</span>
                                    <?php else: ?>
                                    <span class="badge badge-new">Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['id'] !== currentUser()['id']): ?>
                                    <div class="nav-dropdown-wrapper">
                                        <button class="btn-ghost text-xs px-2 py-1">İşlem ▾</button>
                                        <div class="nav-dropdown" style="right:0;left:auto;min-width:180px">
                                            <?php if ($u['role'] !== 'admin'): ?>
                                            <a href="?action=set_admin&id=<?= $u['id'] ?>&token=<?= $token ?>" class="dropdown-item text-xs">Admin Yap</a>
                                            <?php endif; ?>
                                            <?php if ($u['role'] !== 'moderator'): ?>
                                            <a href="?action=set_moderator&id=<?= $u['id'] ?>&token=<?= $token ?>" class="dropdown-item text-xs">Moderatör Yap</a>
                                            <?php endif; ?>
                                            <?php if ($u['role'] !== 'member'): ?>
                                            <a href="?action=set_member&id=<?= $u['id'] ?>&token=<?= $token ?>" class="dropdown-item text-xs">Üye Yap</a>
                                            <?php endif; ?>
                                            <div class="dropdown-divider"></div>
                                            <?php if ($u['is_banned']): ?>
                                            <a href="?action=unban&id=<?= $u['id'] ?>&token=<?= $token ?>" class="dropdown-item text-xs text-success">Banı Kaldır</a>
                                            <?php else: ?>
                                            <a href="?action=ban&id=<?= $u['id'] ?>&token=<?= $token ?>&reason=Kural+ihlali" class="dropdown-item text-xs text-danger">Banla</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <span style="font-size:12px;color:var(--ink-3)">Siz</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($data['pages'] > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $data['pages']; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>" class="page-btn <?= $i === $data['current'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>