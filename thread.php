<?php
require_once 'includes/functions.php';
require_once 'includes/bbcode.php';

$threadId = (int)($_GET['id'] ?? 0);
if (!$threadId) { header('Location: ' . SITE_URL . '/forum.php'); exit; }

$thread = db()->fetchOne("SELECT t.*, u.username, u.avatar, u.role, u.post_count, u.thread_count, u.created_at as user_joined, u.signature, u.about,
    f.name as forum_name, f.slug as forum_slug, c.name as cat_name, c.slug as cat_slug
    FROM threads t
    JOIN users u ON u.id = t.user_id
    JOIN forums f ON f.id = t.forum_id
    JOIN categories c ON c.id = f.category_id
    WHERE t.id = ? AND t.is_deleted = 0", [$threadId], 'i');

if (!$thread) { header('Location: ' . SITE_URL . '/forum.php'); exit; }

db()->query("UPDATE threads SET view_count = view_count + 1 WHERE id = ?", [$threadId], 'i');

$page = max(1, (int)($_GET['page'] ?? 1));
$postSql = "SELECT p.*, u.username, u.avatar, u.role, u.post_count, u.thread_count, u.signature, u.created_at as user_joined,
    qp.content as quote_content, qu.username as quote_username
    FROM posts p
    JOIN users u ON u.id = p.user_id
    LEFT JOIN posts qp ON qp.id = p.quote_post_id
    LEFT JOIN users qu ON qu.id = qp.user_id
    WHERE p.thread_id = ? AND p.is_deleted = 0
    ORDER BY p.created_at ASC";
$data = paginateQuery($postSql, [$threadId], 'i', $page, POSTS_PER_PAGE);

$currentUser = currentUser();
$isLiked = false;
if ($currentUser) {
    $likeCheck = db()->fetchOne("SELECT id FROM thread_likes WHERE thread_id = ? AND user_id = ?", [$threadId, $currentUser['id']], 'ii');
    $isLiked = (bool)$likeCheck;
}

$likedPostIds = [];
if ($currentUser) {
    $likedPosts = db()->fetchAll("SELECT post_id FROM post_likes WHERE user_id = ? AND post_id IN (SELECT id FROM posts WHERE thread_id = ?)", [$currentUser['id'], $threadId], 'ii');
    $likedPostIds = array_column($likedPosts, 'post_id');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isLoggedIn()) { header('Location: ' . SITE_URL . '/login.php'); exit; }
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { showToast('Geçersiz istek', 'error'); exit; }

    if ($_POST['action'] === 'reply' && !empty(trim($_POST['content']))) {
        if ($thread['is_locked'] && !isModerator()) {
            header('Location: ' . SITE_URL . '/thread.php?id=' . $threadId . '&error=locked');
            exit;
        }
        $content = trim($_POST['content']);
        $quoteId = !empty($_POST['quote_post_id']) ? (int)$_POST['quote_post_id'] : null;
        $postId = db()->insert("INSERT INTO posts (thread_id, user_id, content, quote_post_id) VALUES (?,?,?,?)",
            [$threadId, $currentUser['id'], $content, $quoteId], 'iisi');
        db()->query("UPDATE threads SET reply_count = reply_count + 1, last_post_id = ?, last_post_at = NOW() WHERE id = ?", [$postId, $threadId], 'ii');
        db()->query("UPDATE users SET post_count = post_count + 1 WHERE id = ?", [$currentUser['id']], 'i');
        checkAndAwardBadges($currentUser['id']);
        db()->query("UPDATE forums SET post_count = post_count + 1 WHERE id = ?", [$thread['forum_id']], 'i');
        if ($thread['user_id'] !== $currentUser['id']) {
            createNotification($thread['user_id'], $currentUser['id'], 'reply', $threadId, 'thread', $currentUser['username'] . ' konunuza cevap verdi: ' . mb_substr($thread['title'], 0, 50));
        }
        
        $totalPages = db()->fetchOne("SELECT CEIL(COUNT(*)/?) as pages FROM posts WHERE thread_id = ? AND is_deleted = 0", [POSTS_PER_PAGE, $threadId], 'ii');
        $redirectPage = $totalPages['pages'] ?? 1;
        header('Location: ' . SITE_URL . '/thread.php?id=' . $threadId . '&page=' . $redirectPage . '#post-' . $postId);
        exit;
    }
}

$pageTitle = $thread['title'];
require_once 'includes/header.php';
?>

<style>
.post-content-text { word-wrap: break-word !important; overflow-wrap: break-word !important; word-break: break-word !important; white-space: pre-wrap !important; max-width: 100%; }
.post-content-text img { max-width: 100%; height: auto; border-radius: 8px; margin: 10px 0; }
.post-box { display: flex; flex-direction: column; background: var(--bg-surface); border: 1px solid var(--border-1); border-radius: 12px; margin-bottom: 20px; overflow: hidden; }
@media (min-width: 768px) { .post-box { flex-direction: row; } }
.post-sidebar { width: 100%; background: var(--bg-raised); padding: 20px; border-bottom: 1px solid var(--border-0); display: flex; flex-direction: column; align-items: center; text-align: center; flex-shrink: 0; }
@media (min-width: 768px) { .post-sidebar { width: 220px; border-bottom: none; border-right: 1px solid var(--border-0); } }
.post-main { flex: 1; padding: 20px; min-width: 0; display: flex; flex-direction: column; }
.stat-row { width: 100%; display: flex; justify-content: space-between; font-size: 11px; color: var(--ink-2); background: var(--bg-surface); border: 1px solid var(--border-0); padding: 5px 10px; border-radius: 6px; margin-bottom: 5px; }
@keyframes spin { to { transform: rotate(360deg); } }
#drop-zone.dragover { border-color: var(--accent) !important; background: rgba(192, 160, 128, 0.05) !important; }
</style>

<div style="max-width:1100px;margin:0 auto;padding:20px 16px 60px">

    <nav class="breadcrumb" style="margin-bottom:24px">
        <a href="<?= SITE_URL ?>/index.php">Ana Sayfa</a>
        <span class="breadcrumb-sep">/</span>
        <a href="<?= SITE_URL ?>/forum.php?cat=<?= sanitize($thread['cat_slug']) ?>"><?= sanitize($thread['cat_name']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <a href="<?= SITE_URL ?>/forum.php?forum=<?= sanitize($thread['forum_slug']) ?>"><?= sanitize($thread['forum_name']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <span class="breadcrumb-current"><?= sanitize(mb_substr($thread['title'], 0, 40)) ?>...</span>
    </nav>

    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px; margin-bottom:24px;">
        <h1 style="font-size:22px; font-weight:700; color:var(--ink-0); font-family:'Clash Display',sans-serif; margin:0;">
            <?php if ($thread['is_sticky']): ?><span class="badge badge-sticky mr-2">Sabit</span><?php endif; ?>
            <?php if ($thread['is_locked']): ?><span class="badge badge-locked mr-2">Kilitli</span><?php endif; ?>
            <?= sanitize($thread['title']) ?>
        </h1>
        
        <div style="display:flex; align-items:center; gap:8px;">
            <button onclick="likeThread(<?= $threadId ?>, this)" class="btn-ghost <?= $isLiked ? 'text-accent' : '' ?>" style="padding:8px 12px; font-size:13px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="<?= $isLiked ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                <span class="like-count"><?= $thread['like_count'] ?></span>
            </button>
            <?php if (isLoggedIn()): ?>
            <?php $isFollowing = isFollowingThread($threadId, $currentUser['id']); ?>
            <button onclick="toggleFollow(<?= $threadId ?>, this)" class="btn-ghost <?= $isFollowing ? 'text-accent' : '' ?>" style="padding:8px 12px; font-size:13px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="<?= $isFollowing ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <?= $isFollowing ? 'Takipten Çık' : 'Takip Et' ?>
            </button>
            <?php endif; ?>
            <?php if (isModerator()): ?>
            <div class="nav-dropdown-wrapper">
                <button class="btn-ghost" style="padding:8px; border-radius:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                </button>
                <div class="nav-dropdown" style="right:0;left:auto;min-width:180px">
                    <a href="<?= SITE_URL ?>/admin/thread-edit.php?id=<?= $threadId ?>" class="dropdown-item">Düzenle</a>
                    <a href="<?= SITE_URL ?>/admin/thread-toggle.php?id=<?= $threadId ?>&action=sticky" class="dropdown-item"><?= $thread['is_sticky'] ? 'Sabiti Kaldır' : 'Sabitle' ?></a>
                    <a href="<?= SITE_URL ?>/admin/thread-toggle.php?id=<?= $threadId ?>&action=lock" class="dropdown-item"><?= $thread['is_locked'] ? 'Kilidi Aç' : 'Kilitle' ?></a>
                    <a href="#" onclick="confirmDelete('Konuyu silmek istediğinizden emin misiniz?','<?= SITE_URL ?>/admin/thread-delete.php?id=<?= $threadId ?>')" class="dropdown-item text-danger">Sil</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="post-box" id="post-op">
        <div class="post-sidebar">
            <img src="<?= getAvatar($thread) ?>" alt="" style="width:84px; height:84px; border-radius:14px; border:2px solid var(--border-1); padding:3px; background:var(--bg-surface); object-fit:cover; margin-bottom:12px;">
            <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($thread['username']) ?>" style="font-size:15px; font-weight:700; color:var(--ink-0); text-decoration:none; margin-bottom:6px;"><?= sanitize($thread['username']) ?></a>
            <?php $badge = getRoleBadge($thread['role']); ?>
            <span class="role-badge role-<?= $thread['role'] ?>" style="margin-bottom:16px; padding:4px 12px; font-size:11px;"><?= $badge['label'] ?></span>
            
            <div style="width:100%;">
                <div class="stat-row"><span>Mesaj</span> <strong style="color:var(--ink-0)"><?= formatNumber($thread['post_count']) ?></strong></div>
                <div class="stat-row"><span>Konu</span> <strong style="color:var(--ink-0)"><?= formatNumber($thread['thread_count']) ?></strong></div>
                <div class="stat-row"><span>Kayıt</span> <strong style="color:var(--ink-0)"><?= formatDate($thread['user_joined'], 'd.m.Y') ?></strong></div>
            </div>
        </div>
        <div class="post-main">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-0); padding-bottom:12px; margin-bottom:16px;">
                <span style="font-size:12px; color:var(--ink-3); font-family:'JetBrains Mono',monospace;"><?= formatDate($thread['created_at']) ?></span>
                <div style="display:flex; gap:12px; align-items:center;">
                    <span style="font-size:12px; font-weight:700; color:var(--ink-3);">#1</span>
                    <?php if (isLoggedIn()): ?>
                    <button type="button" onclick="quotePost(0, '<?= sanitize($thread['username']) ?>', document.querySelector('#post-op .post-content-text').innerText)" style="background:none; border:none; color:var(--ink-2); font-size:12px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:4px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 2v4c0 1.25.75 2 2 2h1c1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/></svg> Alıntıla
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="post-content-text" style="flex:1; color:var(--ink-1); font-size:15px; line-height:1.7;">
                <?= bbcode($thread['content']) ?>
            </div>
            
            <?php if ($thread['signature']): ?>
            <div style="margin-top:20px; padding-top:16px; border-top:1px dashed var(--border-0); font-size:13px; color:var(--ink-3);">
                <?= sanitize($thread['signature']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach ($data['items'] as $idx => $post): ?>
    <div class="post-box" id="post-<?= $post['id'] ?>">
        <div class="post-sidebar">
            <img src="<?= getAvatar($post) ?>" alt="" style="width:84px; height:84px; border-radius:14px; border:2px solid var(--border-1); padding:3px; background:var(--bg-surface); object-fit:cover; margin-bottom:12px;">
            <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($post['username']) ?>" style="font-size:15px; font-weight:700; color:var(--ink-0); text-decoration:none; margin-bottom:6px;"><?= sanitize($post['username']) ?></a>
            <?php $badge = getRoleBadge($post['role']); ?>
            <span class="role-badge role-<?= $post['role'] ?>" style="margin-bottom:16px; padding:4px 12px; font-size:11px;"><?= $badge['label'] ?></span>
            
            <div style="width:100%;">
                <div class="stat-row"><span>Mesaj</span> <strong style="color:var(--ink-0)"><?= formatNumber($post['post_count']) ?></strong></div>
                <div class="stat-row"><span>Konu</span> <strong style="color:var(--ink-0)"><?= formatNumber($post['thread_count']) ?></strong></div>
                <div class="stat-row"><span>Kayıt</span> <strong style="color:var(--ink-0)"><?= formatDate($post['user_joined'], 'd.m.Y') ?></strong></div>
            </div>
        </div>
        <div class="post-main">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-0); padding-bottom:12px; margin-bottom:16px;">
                <span style="font-size:12px; color:var(--ink-3); font-family:'JetBrains Mono',monospace;"><?= formatDate($post['created_at']) ?><?= $post['is_edited'] ? ' (düzenlendi)' : '' ?></span>
                <div style="display:flex; gap:12px; align-items:center;">
                    <span style="font-size:12px; font-weight:700; color:var(--ink-3);">#<?= ($page - 1) * POSTS_PER_PAGE + $idx + 2 ?></span>
                    
                    <button type="button" onclick="likePost(<?= $post['id'] ?>, this)" style="background:none; border:none; font-size:12px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:4px; color: <?= in_array($post['id'], $likedPostIds) ? 'var(--accent)' : 'var(--ink-2)' ?>;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="<?= in_array($post['id'], $likedPostIds) ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg> <span class="like-count"><?= $post['like_count'] ?></span>
                    </button>

                    <?php if (isLoggedIn()): ?>
                    <button type="button" onclick="quotePost(<?= $post['id'] ?>, '<?= sanitize($post['username']) ?>', document.querySelector('#post-<?= $post['id'] ?> .post-content-text').innerText)" style="background:none; border:none; color:var(--ink-2); font-size:12px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:4px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 2v4c0 1.25.75 2 2 2h1c1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/></svg> Alıntıla
                    </button>
                    <?php endif; ?>
                    
                    <?php if (isModerator() || ($currentUser && $currentUser['id'] == $post['user_id'])): ?>
                    <div class="nav-dropdown-wrapper">
                        <button type="button" style="background:none; border:none; color:var(--ink-2); cursor:pointer;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                        </button>
                        <div class="nav-dropdown" style="right:0;left:auto;min-width:150px">
                            <?php if ($currentUser && $currentUser['id'] == $post['user_id']): ?>
                            <a href="<?= SITE_URL ?>/edit-post.php?id=<?= $post['id'] ?>" class="dropdown-item">Düzenle</a>
                            <?php endif; ?>
                            <?php if (isModerator()): ?>
                            <a href="#" onclick="confirmDelete('Mesajı silmek istediğinizden emin misiniz?','<?= SITE_URL ?>/admin/post-delete.php?id=<?= $post['id'] ?>&thread=<?= $threadId ?>')" class="dropdown-item text-danger">Sil</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($post['quote_content']): ?>
            <div style="background:var(--bg-raised); border-left:3px solid var(--accent); padding:12px 16px; border-radius:0 8px 8px 0; margin-bottom:16px;">
                <div style="font-size:12px; font-weight:700; color:var(--ink-1); margin-bottom:6px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline;margin-right:4px"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 2v4c0 1.25.75 2 2 2h1c1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/></svg>
                    <?= sanitize($post['quote_username']) ?> yazdı:
                </div>
                <div style="font-size:13px; color:var(--ink-2); font-style:italic; line-height:1.5;"><?= nl2br(sanitize(mb_substr(strip_tags($post['quote_content']), 0, 200))) ?>...</div>
            </div>
            <?php endif; ?>
            
            <div class="post-content-text" style="flex:1; color:var(--ink-1); font-size:15px; line-height:1.7;">
                <?= bbcode($post['content']) ?>
            </div>
            
            <?php if ($post['signature']): ?>
            <div style="margin-top:20px; padding-top:16px; border-top:1px dashed var(--border-0); font-size:13px; color:var(--ink-3);">
                <?= sanitize($post['signature']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if ($data['pages'] > 1): ?>
    <div class="pagination" style="display:flex; justify-content:center; gap:8px; margin:30px 0;">
        <?php if ($data['current'] > 1): ?>
        <a href="?id=<?= $threadId ?>&page=<?= $data['current'] - 1 ?>" class="page-btn" style="padding:8px 14px; background:var(--bg-surface); border:1px solid var(--border-1); border-radius:8px; color:var(--ink-1); text-decoration:none;">Önceki</a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $data['current']-2); $i <= min($data['pages'], $data['current']+2); $i++): ?>
        <a href="?id=<?= $threadId ?>&page=<?= $i ?>" style="padding:8px 14px; border-radius:8px; text-decoration:none; font-weight:600; <?= $i === $data['current'] ? 'background:var(--accent); color:#fff;' : 'background:var(--bg-surface); border:1px solid var(--border-1); color:var(--ink-1);' ?>"><?= $i ?></a>
        <?php endfor; ?>
        
        <?php if ($data['current'] < $data['pages']): ?>
        <a href="?id=<?= $threadId ?>&page=<?= $data['current'] + 1 ?>" class="page-btn" style="padding:8px 14px; background:var(--bg-surface); border:1px solid var(--border-1); border-radius:8px; color:var(--ink-1); text-decoration:none;">Sonraki</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (isLoggedIn() && !$thread['is_locked']): ?>
    <div id="quick-reply" style="margin-top:40px; padding:24px; background:var(--bg-surface); border:1px solid var(--border-1); border-radius:12px">
        <h3 style="margin-bottom:16px; font-size:16px; font-weight:700; color:var(--ink-0); font-family:'Clash Display',sans-serif;">Hızlı Cevap</h3>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="quote_post_id" id="quote-post-id" value="">
            
            <div style="display:flex; gap:4px; padding:10px; background:var(--bg-raised); border:1px solid var(--border-0); border-bottom:none; border-radius:8px 8px 0 0; overflow-x:auto;">
                <button type="button" onclick="editorFormat('bold')" style="padding:6px 12px; background:none; border:none; cursor:pointer; color:var(--ink-1); border-radius:4px;"><b>B</b></button>
                <button type="button" onclick="editorFormat('italic')" style="padding:6px 12px; background:none; border:none; cursor:pointer; color:var(--ink-1); border-radius:4px;"><i>I</i></button>
                <button type="button" onclick="editorFormat('underline')" style="padding:6px 12px; background:none; border:none; cursor:pointer; color:var(--ink-1); border-radius:4px;"><u>U</u></button>
                <button type="button" onclick="editorFormat('code')" style="font-family:monospace; padding:6px 12px; background:none; border:none; cursor:pointer; color:var(--ink-1); border-radius:4px;">&lt;/&gt;</button>
                <button type="button" onclick="editorFormat('quote')" style="padding:6px 12px; background:none; border:none; cursor:pointer; color:var(--ink-1); border-radius:4px;">❝</button>
                <button type="button" onclick="editorFormat('spoiler')" style="padding:6px 12px; background:none; border:none; cursor:pointer; color:var(--ink-1); border-radius:4px;">👁</button>
                
                <button type="button" onclick="openImageModal('reply-content')" title="Resim Yükle" style="padding:6px 12px; background:none; border:none; cursor:pointer; color:var(--ink-1); border-radius:4px; display:inline-flex; align-items:center;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </button>
            </div>
            
            <textarea name="content" id="reply-content" style="width:100%; min-height:160px; padding:16px; border:1px solid var(--border-0); border-radius:0 0 8px 8px; background:var(--bg-base); color:var(--ink-0); font-size:14px; line-height:1.6; resize:vertical; margin-bottom:16px; outline:none;" placeholder="Cevabınızı buraya yazın..." required></textarea>
            
            <button type="submit" class="btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:12px 28px; background:var(--accent); color:#fff; border:none; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Cevap Gönder
            </button>
        </form>
    </div>
    <?php elseif ($thread['is_locked']): ?>
    <div style="margin-top:30px; padding:30px; background:var(--bg-surface); border:1px solid var(--border-1); border-radius:12px; text-align:center;">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin:0 auto 16px; color:var(--ink-3);"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <p style="color:var(--ink-2); font-weight:600; font-size:15px;">Bu konu yeni cevaplara kapatılmıştır.</p>
    </div>
    <?php endif; ?>
</div>

<div id="image-upload-modal" style="display: none; align-items: center; justify-content: center; position: fixed; inset: 0; z-index: 10000; padding: 20px; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);">
    <div style="position: relative; z-index: 1; width: 100%; max-width: 450px; background: var(--bg-surface); border: 1px solid var(--border-1); border-radius: 18px; box-shadow: 0 24px 80px rgba(0,0,0,0.7); padding: 24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <h3 style="font-family: 'Clash Display', sans-serif; font-size: 18px; font-weight: 700; color: var(--ink-0); margin:0;">Resim Yükle</h3>
            <button type="button" onclick="closeImageModal()" style="background:none; border:none; color:var(--ink-2); cursor:pointer;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div id="drop-zone" style="border: 2px dashed var(--border-1); border-radius: 12px; padding: 40px 20px; text-align: center; cursor: pointer; transition: all 0.3s ease; background: var(--bg-base);">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--ink-3)" stroke-width="1.5" style="margin: 0 auto 12px;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            <p style="color: var(--ink-1); font-size: 14px; font-weight: 600; margin-bottom: 4px;">Resmi buraya sürükleyin</p>
            <p style="color: var(--ink-3); font-size: 12px;">veya cihazdan seçmek için tıklayın</p>
            <input type="file" id="image-file-input" accept="image/*" style="display: none;">
        </div>

        <div id="upload-loading" style="display: none; text-align: center; padding: 30px 0;">
            <div style="width: 24px; height: 24px; border: 3px solid var(--accent); border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 12px;"></div>
            <p style="color: var(--ink-2); font-size: 13px;">Sunucuya yükleniyor, lütfen bekleyin...</p>
        </div>

        <div style="margin-top: 20px; text-align: center; border-top: 1px solid var(--border-0); padding-top: 16px;">
            <p style="color: var(--ink-3); font-size: 12px; margin-bottom: 8px;">Veya resmin internet linkini yapıştırın:</p>
            <div style="display:flex; gap:8px;">
                <input type="text" id="image-url-input" placeholder="https://..." style="flex:1; padding:10px 12px; background:var(--bg-base); border:1px solid var(--border-0); border-radius:8px; color:var(--ink-0); font-size:13px; outline:none;">
                <button type="button" onclick="insertImageFromUrlInput()" style="padding:10px 16px; border-radius:8px; font-size:13px; border:none; background:var(--accent); color:#fff; cursor:pointer; font-weight:600;">Ekle</button>
            </div>
        </div>
    </div>
</div>

<script>
function editorFormat(tag) {
    const ta = document.getElementById('reply-content');
    if (!ta) return;
    const s = ta.selectionStart, e = ta.selectionEnd, sel = ta.value.substring(s, e);
    const t = {
        bold:['[b]','[/b]'],
        italic:['[i]','[/i]'],
        underline:['[u]','[/u]'],
        code:['[code]','[/code]'],
        quote:['[quote]','[/quote]'],
        spoiler:['[spoiler]','[/spoiler]']
    }[tag];
    if (!t) return;
    ta.value = ta.value.substring(0, s) + t[0] + sel + t[1] + ta.value.substring(e);
    ta.selectionStart = s + t[0].length; ta.selectionEnd = e + t[0].length; ta.focus();
}

function quotePost(id, username, content) {
    const ta = document.getElementById('reply-content');
    const qId = document.getElementById('quote-post-id');
    if (!ta) return;
    if(qId && id > 0) qId.value = id;
    ta.value = `[quote=${username}]${content.trim()}[/quote]\n\n` + ta.value;
    ta.focus();
    ta.selectionStart = ta.value.length;
    const qr = document.getElementById('quick-reply');
    if(qr) qr.scrollIntoView({behavior: 'smooth', block: 'center'});
}

let currentTextareaId = 'reply-content';

function openImageModal(textareaId) {
    currentTextareaId = textareaId;
    const modal = document.getElementById('image-upload-modal');
    const dropZone = document.getElementById('drop-zone');
    const uploadLoading = document.getElementById('upload-loading');
    const urlInput = document.getElementById('image-url-input');
    
    if (modal && dropZone && uploadLoading && urlInput) {
        modal.style.display = 'flex';
        dropZone.style.display = 'block';
        uploadLoading.style.display = 'none';
        urlInput.value = '';
    }
}

function closeImageModal() {
    const modal = document.getElementById('image-upload-modal');
    if(modal) modal.style.display = 'none';
}

function insertBbcodeImage(url) {
    const ta = document.getElementById(currentTextareaId);
    if (!ta) return;
    const s = ta.selectionStart, e = ta.selectionEnd;
    const insertText = `[img]${url}[/img]`;
    ta.value = ta.value.substring(0, s) + insertText + ta.value.substring(e);
    ta.selectionStart = ta.selectionEnd = s + insertText.length;
    ta.focus();
    closeImageModal();
}

function insertImageFromUrlInput() {
    const urlInput = document.getElementById('image-url-input');
    if(urlInput && urlInput.value.trim()) insertBbcodeImage(urlInput.value.trim());
}

document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('image-file-input');

    if (dropZone && fileInput) {
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
        dropZone.addEventListener('dragleave', () => { dropZone.classList.remove('dragover'); });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length) uploadFile(e.dataTransfer.files[0]);
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length) uploadFile(this.files[0]);
        });
    }
});

function uploadFile(file) {
    if (!file.type.startsWith('image/')) {
        alert('Lütfen sadece resim (JPG, PNG vb.) dosyası seçin.');
        return;
    }

    const dropZone = document.getElementById('drop-zone');
    const uploadLoading = document.getElementById('upload-loading');
    
    if(dropZone) dropZone.style.display = 'none';
    if(uploadLoading) uploadLoading.style.display = 'block';

    const formData = new FormData();
    formData.append('image', file);

    fetch('<?= SITE_URL ?>/upload_image.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error("Sunucu yanıtı okunamadı.");
        }
    })
    .then(data => {
        if (data.success) {
            insertBbcodeImage(data.url);
        } else {
            alert(data.error || 'Resim yüklenirken sunucu hatası oluştu.');
            if(dropZone) dropZone.style.display = 'block';
            if(uploadLoading) uploadLoading.style.display = 'none';
        }
    })
    .catch(error => {
        alert('Sunucu ile bağlantı kurulamadı. Yükleme başarısız.');
        if(dropZone) dropZone.style.display = 'block';
        if(uploadLoading) uploadLoading.style.display = 'none';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>