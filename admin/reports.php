<?php
require_once '../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $reportId = (int)($_POST['report_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($reportId && in_array($action, ['reviewed', 'dismissed'])) {
        db()->query("UPDATE reports SET status = ?, reviewed_by = ? WHERE id = ?", [$action, currentUser()['id'], $reportId], 'sii');
    }
}

$status = $_GET['status'] ?? 'pending';
$reports = db()->fetchAll("SELECT r.*, u.username as reporter_name FROM reports r JOIN users u ON u.id = r.reporter_id WHERE r.status = ? ORDER BY r.created_at DESC", [$status], 's');

$pageTitle = 'Raporlar';
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
                <a href="<?= SITE_URL ?>/admin/reports.php" class="admin-nav-item active">
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
                <h1 class="text-2xl font-bold">Raporlar</h1>
            </div>

            <div class="flex gap-2 mb-6">
                <a href="?status=pending" class="<?= $status === 'pending' ? 'btn-primary' : 'btn-ghost' ?> text-sm">Bekleyenler</a>
                <a href="?status=reviewed" class="<?= $status === 'reviewed' ? 'btn-primary' : 'btn-ghost' ?> text-sm">İncelenenler</a>
                <a href="?status=dismissed" class="<?= $status === 'dismissed' ? 'btn-primary' : 'btn-ghost' ?> text-sm">Reddedilenler</a>
            </div>

            <?php if (empty($reports)): ?>
            <div class="empty-state card">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="mx-auto mb-3"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                <p>Bu kategoride rapor yok</p>
            </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:16px">
                <?php foreach ($reports as $r): ?>
                <div class="card">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="badge <?= $r['reported_type'] === 'user' ? 'badge-hot' : 'badge-sticky' ?>">
                                    <?= sanitize($r['reported_type']) ?>
                                </span>
                                <span style="font-size:13.5px;color:var(--ink-2)">ID: #<?= $r['reported_id'] ?></span>
                            </div>
                            <p class="font-medium text-white mb-1"><?= sanitize($r['reason']) ?></p>
                            <?php if ($r['details']): ?>
                            <p style="font-size:13.5px;color:var(--ink-2)"><?= sanitize($r['details']) ?></p>
                            <?php endif; ?>
                            <div class="text-xs text-gray-500 mt-2">
                                Raporlayan: <span style="color:var(--accent)"><?= sanitize($r['reporter_name']) ?></span>
                                &bull; <?= timeAgo($r['created_at']) ?>
                            </div>
                        </div>
                        <?php if ($r['status'] === 'pending'): ?>
                        <div class="flex gap-2">
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="reviewed">
                                <button type="submit" class="btn-success text-sm">İncelendi</button>
                            </form>
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="dismissed">
                                <button type="submit" class="btn-ghost text-sm">Reddet</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>