<?php
require_once '../includes/functions.php';
requireAdmin();

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$whereClauses = ['t.is_deleted = 0'];
$params = [];
$types = '';

if ($search) {
    $whereClauses[] = "t.title LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

$whereStr = 'WHERE ' . implode(' AND ', $whereClauses);
$sql = "SELECT t.*, u.username, f.name as forum_name FROM threads t JOIN users u ON u.id = t.user_id JOIN forums f ON f.id = t.forum_id $whereStr ORDER BY t.created_at DESC";
$data = paginateQuery($sql, $params, $types, $page, 25);

$pageTitle = 'Konu Yönetimi';
require_once '../includes/header.php';
$token = generateCsrfToken();
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
                <a href="<?= SITE_URL ?>/admin/forums.php" class="admin-nav-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    Forum Yönetimi
                </a>
                <a href="<?= SITE_URL ?>/admin/threads.php" class="admin-nav-item active">
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
            <div class="section-header mb-6">
                <h1 class="text-2xl font-bold">Konu Yönetimi</h1>
                <span style="font-size:13.5px;color:var(--ink-3)"><?= $data['total'] ?> konu</span>
            </div>

            <div class="card mb-6">
                <form method="GET" class="flex gap-3">
                    <input type="text" name="search" class="form-control" placeholder="Konu başlığı ara..." value="<?= sanitize($search) ?>" style="flex:1">
                    <button type="submit" class="btn-primary">Ara</button>
                    <?php if ($search): ?>
                    <a href="<?= SITE_URL ?>/admin/threads.php" class="btn-ghost">Temizle</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card overflow-hidden p-0">
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Başlık</th>
                                <th>Yazar</th>
                                <th>Forum</th>
                                <th>Cevap</th>
                                <th>Görüntü</th>
                                <th>Tarih</th>
                                <th>Durum</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['items'] as $t): ?>
                            <tr>
                                <td>
                                    <a href="<?= SITE_URL ?>/thread.php?id=<?= $t['id'] ?>" class="font-medium hover:text-primary-light text-sm" target="_blank">
                                        <?= sanitize(mb_substr($t['title'], 0, 60)) ?>
                                    </a>
                                </td>
                                <td style="font-size:13.5px;color:var(--ink-2)"><?= sanitize($t['username']) ?></td>
                                <td style="font-size:13.5px;color:var(--ink-2)"><?= sanitize($t['forum_name']) ?></td>
                                <td class="font-mono text-sm"><?= $t['reply_count'] ?></td>
                                <td class="font-mono text-sm"><?= $t['view_count'] ?></td>
                                <td style="font-size:12px;color:var(--ink-3)"><?= formatDate($t['created_at'], 'd.m.Y') ?></td>
                                <td>
                                    <div class="flex gap-1 flex-wrap">
                                        <?php if ($t['is_sticky']): ?>
                                        <span class="badge badge-sticky text-xs">Sabit</span>
                                        <?php endif; ?>
                                        <?php if ($t['is_locked']): ?>
                                        <span class="badge badge-locked text-xs">Kilitli</span>
                                        <?php endif; ?>
                                        <?php if (!$t['is_sticky'] && !$t['is_locked']): ?>
                                        <span style="font-size:12px;color:var(--ink-3)">Normal</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="nav-dropdown-wrapper">
                                        <button class="btn-ghost text-xs px-2 py-1">İşlem ▾</button>
                                        <div class="nav-dropdown" style="right:0;left:auto;min-width:160px">
                                            <a href="<?= SITE_URL ?>/admin/thread-toggle.php?id=<?= $t['id'] ?>&action=sticky&token=<?= $token ?>" class="dropdown-item text-xs">
                                                <?= $t['is_sticky'] ? 'Sabiti Kaldır' : 'Sabitle' ?>
                                            </a>
                                            <a href="<?= SITE_URL ?>/admin/thread-toggle.php?id=<?= $t['id'] ?>&action=lock&token=<?= $token ?>" class="dropdown-item text-xs">
                                                <?= $t['is_locked'] ? 'Kilidi Aç' : 'Kilitle' ?>
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a href="#" onclick="confirmDelete('Konuyu silmek istediğinizden emin misiniz?','<?= SITE_URL ?>/admin/thread-delete.php?id=<?= $t['id'] ?>&token=<?= $token ?>')" class="dropdown-item text-xs text-danger">Sil</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($data['pages'] > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= min($data['pages'], 10); $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="page-btn <?= $i === $data['current'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
