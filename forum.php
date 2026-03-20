<?php
require_once 'includes/functions.php';

$catSlug = $_GET['cat'] ?? null;
$forumSlug = $_GET['forum'] ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));

if ($forumSlug) {
    $forum = db()->fetchOne("SELECT f.*, c.name as cat_name, c.slug as cat_slug, c.color as cat_color FROM forums f JOIN categories c ON c.id = f.category_id WHERE f.slug = ? AND f.is_active = 1", [$forumSlug], 's');
    if (!$forum) { header('Location: ' . SITE_URL . '/forum.php'); exit; }
    $pageTitle = $forum['name'];
    $filterSql = "SELECT t.*, u.username, u.avatar, u.role, lpu.username as last_post_user FROM threads t JOIN users u ON u.id = t.user_id LEFT JOIN posts lp ON lp.id = t.last_post_id LEFT JOIN users lpu ON lpu.id = lp.user_id WHERE t.forum_id = ? AND t.is_deleted = 0 ORDER BY t.is_sticky DESC, t.last_post_at DESC";
    $filterParams = [(int)$forum['id']];
    $filterTypes = 'i';
    $data = paginateQuery($filterSql, $filterParams, $filterTypes, $page, THREADS_PER_PAGE);
} elseif ($catSlug) {
    $category = db()->fetchOne("SELECT * FROM categories WHERE slug = ? AND is_active = 1", [$catSlug], 's');
    if (!$category) { header('Location: ' . SITE_URL . '/forum.php'); exit; }
    $pageTitle = $category['name'];
    $forum = null;
    $data = null;
} else {
    $pageTitle = 'Tüm Forumlar';
    $category = null;
    $forum = null;
    $data = null;
}

require_once 'includes/header.php';
$categories = getCategories();
?>

<div style="max-width:1100px;margin:0 auto;padding:20px 16px 60px">

    <nav class="breadcrumb">
        <a href="<?= SITE_URL ?>/index.php">Ana Sayfa</a>
        <span class="breadcrumb-sep">/</span>
        <?php if ($forum): ?>
        <a href="<?= SITE_URL ?>/forum.php?cat=<?= sanitize($forum['cat_slug']) ?>"><?= sanitize($forum['cat_name']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <span class="breadcrumb-current"><?= sanitize($forum['name']) ?></span>
        <?php elseif ($category): ?>
        <a href="<?= SITE_URL ?>/forum.php">Forum</a>
        <span class="breadcrumb-sep">/</span>
        <span class="breadcrumb-current"><?= sanitize($category['name']) ?></span>
        <?php else: ?>
        <span class="breadcrumb-current">Forum</span>
        <?php endif; ?>
    </nav>

    <?php if ($forum && $data): ?>

    <div class="section-header mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white mb-1"><?= sanitize($forum['name']) ?></h1>
            <?php if ($forum['description']): ?>
            <p class="text-gray-400 text-sm"><?= sanitize($forum['description']) ?></p>
            <?php endif; ?>
        </div>
        <div style="display:flex;align-items:center;gap:12px">
            <span class="stat-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <?= formatNumber($forum['thread_count']) ?> Konu
            </span>
            <?php if (isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/new-thread.php?forum=<?= sanitize($forum['slug']) ?>" class="btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Yeni Konu
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($data['items'])): ?>
    <div class="empty-state card">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="mx-auto mb-4 text-gray-600"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <h3>Henüz konu yok</h3>
        <p class="text-sm mb-4">İlk konuyu açan sen ol!</p>
        <?php if (isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/new-thread.php?forum=<?= sanitize($forum['slug']) ?>" class="btn-primary">Konu Aç</a>
        <?php endif; ?>
    </div>
    <?php else: ?>

    <div class="bg-surface border border-surface-50 rounded-t-xl">
        <div class="flex items-center px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 border-b border-surface-50">
            <span class="flex-1">Konu</span>
            <span class="w-20 text-center hidden md:block">Cevap</span>
            <span class="w-20 text-center hidden md:block">Görüntü</span>
            <span class="w-36 text-right hidden lg:block">Son Mesaj</span>
        </div>
    </div>

    <?php foreach ($data['items'] as $thread): ?>
    <div class="thread-row <?= $thread['is_sticky'] ? 'sticky' : '' ?>" onclick="window.location='<?= SITE_URL ?>/thread.php?id=<?= (int)$thread['id'] ?>';" style="cursor:pointer">
        <img src="<?= getAvatar($thread) ?>" alt="" class="thread-avatar">
        <div style="flex:1;min-width:0">
            <a href="<?= SITE_URL ?>/thread.php?id=<?= (int)$thread['id'] ?>" class="thread-title" onclick="event.stopPropagation()">
                <?= sanitize($thread['title']) ?>
            </a>
            <div class="thread-badges">
                <?php if ($thread['is_sticky']): ?>
                <span class="badge badge-sticky">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="17" x2="12" y2="22"/><path d="M5 17H19V13L17 7H7L5 13Z"/><line x1="12" y1="2" x2="12" y2="7"/></svg>
                    Sabit
                </span>
                <?php endif; ?>
                <?php if ($thread['is_locked']): ?>
                <span class="badge badge-locked">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Kilitli
                </span>
                <?php endif; ?>
                <?php if ($thread['reply_count'] > 50): ?>
                <span class="badge badge-hot">Popüler</span>
                <?php endif; ?>
            </div>
            <div class="thread-meta">
                <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($thread['username']) ?>" class="hover:text-primary-light" onclick="event.stopPropagation()">
                    <?= sanitize($thread['username']) ?>
                </a>
                &bull; <?= timeAgo($thread['created_at']) ?>
            </div>
        </div>
        <div class="thread-counts">
            <div class="thread-count-item">
                <div class="thread-count-value"><?= formatNumber($thread['reply_count']) ?></div>
                <div class="thread-count-label">Cevap</div>
            </div>
            <div class="thread-count-item hidden md:block">
                <div class="thread-count-value"><?= formatNumber($thread['view_count']) ?></div>
                <div class="thread-count-label">Görüntü</div>
            </div>
        </div>
        <?php if ($thread['last_post_user']): ?>
        <div class="thread-last hidden lg:block">
            <div class="last-user"><?= sanitize($thread['last_post_user']) ?></div>
            <div class="last-time"><?= timeAgo($thread['last_post_at']) ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if ($data['pages'] > 1): ?>
    <div class="pagination">
        <?php if ($data['current'] > 1): ?>
        <a href="?forum=<?= sanitize($forumSlug) ?>&page=<?= $data['current'] - 1 ?>" class="page-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <?php endif; ?>
        <?php for ($i = max(1, $data['current']-2); $i <= min($data['pages'], $data['current']+2); $i++): ?>
        <a href="?forum=<?= sanitize($forumSlug) ?>&page=<?= $i ?>" class="page-btn <?= $i === $data['current'] ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($data['current'] < $data['pages']): ?>
        <a href="?forum=<?= sanitize($forumSlug) ?>&page=<?= $data['current'] + 1 ?>" class="page-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <?php else: ?>

    <?php
    $displayCats = $category ? [$category] : $categories;
    foreach ($displayCats as $cat):
        $forums = getForumsByCategory($cat['id']);
    ?>
    <div class="category-section mb-10">
        <div class="category-header">
            <div class="category-icon" style="background:<?= sanitize($cat['color']) ?>22; border: 1px solid <?= sanitize($cat['color']) ?>33;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= sanitize($cat['color']) ?>" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div>
                <h2 class="category-title">
                    <a href="?cat=<?= sanitize($cat['slug']) ?>" class="hover:text-primary-light transition-colors"><?= sanitize($cat['name']) ?></a>
                </h2>
                <?php if ($cat['description']): ?>
                <p class="category-desc"><?= sanitize($cat['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php foreach ($forums as $fr): ?>
        <a href="?forum=<?= sanitize($fr['slug']) ?>" class="forum-row">
            <div class="forum-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div style="flex:1;min-width:0">
                <div class="forum-name"><?= sanitize($fr['name']) ?></div>
                <?php if ($fr['description']): ?>
                <div class="forum-desc"><?= sanitize($fr['description']) ?></div>
                <?php endif; ?>
            </div>
            <div class="forum-stats">
                <div class="forum-stat-item">
                    <div class="forum-stat-value"><?= formatNumber($fr['thread_count']) ?></div>
                    <div class="forum-stat-label">Konu</div>
                </div>
                <div class="forum-stat-item">
                    <div class="forum-stat-value"><?= formatNumber($fr['post_count']) ?></div>
                    <div class="forum-stat-label">Mesaj</div>
                </div>
            </div>
            <?php if ($fr['last_thread_title']): ?>
            <div class="forum-last-post">
                <div class="last-post-title"><?= sanitize($fr['last_thread_title']) ?></div>
                <div class="last-post-meta"><?= sanitize($fr['last_post_user'] ?? '') ?></div>
            </div>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
