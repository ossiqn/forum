<?php
require_once '../includes/functions.php';
requireAdmin();

$stats = getForumStats();
$recentUsers = db()->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$reports = db()->fetchAll("SELECT r.*, u.username as reporter_name FROM reports r JOIN users u ON u.id = r.reporter_id WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 10");
$recentThreads = db()->fetchAll("SELECT t.*, u.username, f.name as forum_name FROM threads t JOIN users u ON u.id = t.user_id JOIN forums f ON f.id = t.forum_id WHERE t.is_deleted = 0 ORDER BY t.created_at DESC LIMIT 10");

$pageTitle = 'Admin Dashboard';
require_once '../includes/header.php';
?>
<div style="max-width:1100px;margin:0 auto;padding:20px 16px 60px">
    <div style="display:flex;gap:20px;align-items:flex-start">
        <aside class="admin-sidebar hidden lg:block">
            <div class="mb-4 pb-4 border-b" style="border-color:var(--border-0)">
                <span style="font-family:'JetBrains Mono',monospace;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3);padding:0 4px">Admin Panel</span>
            </div>
            <nav>
                <a href="<?= SITE_URL ?>/admin/index.php" class="admin-nav-item active">
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
                <a href="<?= SITE_URL ?>/admin/ads.php" class="admin-nav-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    Reklamlar
                </a>
                <a href="<?= SITE_URL ?>/admin/reports.php" class="admin-nav-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                    Raporlar
                    <?php if (count($reports) > 0): ?>
                    <span class="notif-badge" style="position:static;margin-left:auto"><?= count($reports) ?></span>
                    <?php endif; ?>
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
            <h1 style="font-family:'Clash Display',sans-serif;font-size:24px;font-weight:700;color:var(--ink-0);margin-bottom:24px">Dashboard</h1>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:32px">
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background:rgba(59,130,246,0.15)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <div style="font-family:'Clash Display',sans-serif;font-size:24px;font-weight:700;color:var(--ink-0);line-height:1"><?= formatNumber($stats['total_users']) ?></div>
                    <div style="font-size:12px;color:var(--ink-3);margin-top:4px">Toplam Üye</div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background:rgba(139,92,246,0.15)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div style="font-family:'Clash Display',sans-serif;font-size:24px;font-weight:700;color:var(--ink-0);line-height:1"><?= formatNumber($stats['total_threads']) ?></div>
                    <div style="font-size:12px;color:var(--ink-3);margin-top:4px">Toplam Konu</div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background:rgba(16,185,129,0.15)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    </div>
                    <div style="font-family:'Clash Display',sans-serif;font-size:24px;font-weight:700;color:var(--ink-0);line-height:1"><?= formatNumber($stats['total_posts']) ?></div>
                    <div style="font-size:12px;color:var(--ink-3);margin-top:4px">Toplam Mesaj</div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon" style="background:rgba(245,158,11,0.15)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div style="font-family:'Clash Display',sans-serif;font-size:24px;font-weight:700;color:var(--ink-0);line-height:1"><?= $stats['online_users'] ?></div>
                    <div style="font-size:12px;color:var(--ink-3);margin-top:4px">Çevrimiçi</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:24px">
                <div class="card">
                    <h2 style="font-size:16px;font-weight:600;color:var(--ink-0);margin-bottom:16px">Son Kayıt Olan Üyeler</h2>
                    <div style="display:flex;flex-direction:column;gap:12px">
                        <?php foreach ($recentUsers as $u): ?>
                        <div style="display:flex;align-items:center;gap:12px">
                            <img src="<?= getAvatar($u) ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
                            <div style="flex:1;min-width:0">
                                <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($u['username']) ?>" style="font-size:14px;font-weight:600;color:var(--ink-0);text-decoration:none" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--ink-0)'"><?= sanitize($u['username']) ?></a>
                                <div style="font-size:12px;color:var(--ink-3)"><?= timeAgo($u['created_at']) ?></div>
                            </div>
                            <span class="role-badge role-<?= $u['role'] ?>"><?= getRoleBadge($u['role'])['label'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card">
                    <h2 style="font-size:16px;font-weight:600;color:var(--ink-0);margin-bottom:16px">Son Konular</h2>
                    <div style="display:flex;flex-direction:column;gap:12px">
                        <?php foreach ($recentThreads as $t): ?>
                        <div style="display:flex;align-items:flex-start;gap:10px">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" style="flex-shrink:0;margin-top:2px"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            <div style="flex:1;min-width:0">
                                <a href="<?= SITE_URL ?>/thread.php?id=<?= $t['id'] ?>" style="font-size:14px;font-weight:600;color:var(--ink-0);text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--ink-0)'"><?= sanitize($t['title']) ?></a>
                                <div style="font-size:12px;color:var(--ink-3)"><?= sanitize($t['username']) ?> &bull; <?= sanitize($t['forum_name']) ?> &bull; <?= timeAgo($t['created_at']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($reports)): ?>
            <div class="card" style="margin-top:24px">
                <h2 style="font-size:16px;font-weight:600;color:var(--ink-0);margin-bottom:16px;display:flex;align-items:center;gap:8px">
                    Bekleyen Raporlar
                    <span class="badge badge-hot"><?= count($reports) ?></span>
                </h2>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Raporlayan</th>
                                <th>Tür</th>
                                <th>Neden</th>
                                <th>Tarih</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $r): ?>
                            <tr>
                                <td style="color:var(--ink-1);font-weight:500"><?= sanitize($r['reporter_name']) ?></td>
                                <td><span class="badge" style="background:var(--bg-elevated);border-color:var(--border-1);color:var(--ink-2)"><?= sanitize($r['reported_type']) ?></span></td>
                                <td style="color:var(--ink-2)"><?= sanitize(mb_substr($r['reason'], 0, 50)) ?></td>
                                <td style="color:var(--ink-3);font-family:'JetBrains Mono',monospace"><?= timeAgo($r['created_at']) ?></td>
                                <td>
                                    <a href="<?= SITE_URL ?>/admin/report-review.php?id=<?= $r['id'] ?>" style="color:var(--accent);text-decoration:none;font-weight:600;font-size:13px" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">İncele</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>