<?php
require_once 'includes/functions.php';

$q       = trim($_GET['q'] ?? '');
$type    = $_GET['type'] ?? 'threads';
$forumId = (int)($_GET['forum'] ?? 0);
$userId  = (int)($_GET['user'] ?? 0);
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));

$results = [];
$total   = 0;
$pages   = 0;
$perPage = 15;

function highlight($text, $q) {
    if (!$q) return sanitize($text);
    return preg_replace('/(' . preg_quote(htmlspecialchars($q, ENT_QUOTES, 'UTF-8'), '/') . ')/i',
        '<mark class="search-result-highlight">$1</mark>',
        sanitize($text));
}

if ($q && strlen($q) >= 2) {
    if ($type === 'threads') {
        $where  = "t.title LIKE ? AND t.is_deleted = 0";
        $params = ["%$q%"];
        $types  = 's';
        if ($forumId) { $where .= " AND t.forum_id = ?"; $params[] = $forumId; $types .= 'i'; }
        if ($userId)  { $where .= " AND t.user_id = ?";  $params[] = $userId;  $types .= 'i'; }
        if ($dateFrom){ $where .= " AND t.created_at >= ?"; $params[] = $dateFrom; $types .= 's'; }
        if ($dateTo)  { $where .= " AND t.created_at <= ?"; $params[] = $dateTo . ' 23:59:59'; $types .= 's'; }
        $data = paginateQuery("SELECT t.*, u.username, u.avatar, f.name as forum_name, f.slug as forum_slug FROM threads t JOIN users u ON u.id = t.user_id JOIN forums f ON f.id = t.forum_id WHERE $where ORDER BY t.created_at DESC", $params, $types, $page, $perPage);
        $results = $data['items']; $total = $data['total']; $pages = $data['pages'];
    } elseif ($type === 'posts') {
        $where  = "p.content LIKE ? AND p.is_deleted = 0";
        $params = ["%$q%"];
        $types  = 's';
        if ($userId) { $where .= " AND p.user_id = ?"; $params[] = $userId; $types .= 'i'; }
        $data = paginateQuery("SELECT p.*, u.username, u.avatar, t.title as thread_title, t.id as thread_id, f.name as forum_name FROM posts p JOIN users u ON u.id = p.user_id JOIN threads t ON t.id = p.thread_id JOIN forums f ON f.id = t.forum_id WHERE $where ORDER BY p.created_at DESC", $params, $types, $page, $perPage);
        $results = $data['items']; $total = $data['total']; $pages = $data['pages'];
    } elseif ($type === 'users') {
        $data = paginateQuery("SELECT * FROM users WHERE (username LIKE ? OR about LIKE ?) AND is_banned = 0 ORDER BY post_count DESC", ["%$q%", "%$q%"], 'ss', $page, $perPage);
        $results = $data['items']; $total = $data['total']; $pages = $data['pages'];
    }
}

$forums = db()->fetchAll("SELECT id, name FROM forums WHERE is_active = 1 ORDER BY name");

$pageTitle = $q ? "\"$q\" için arama sonuçları" : 'Arama';
require_once 'includes/header.php';
?>

<div style="max-width:1100px;margin:0 auto;padding:20px 16px 60px">

    <div class="section-header mb-6">
        <h1 class="section-title">Arama</h1>
        <?php if ($total > 0): ?>
        <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--ink-3)"><?= number_format($total) ?> sonuç</span>
        <?php endif; ?>
    </div>

    <div class="search-filter-bar">
        <form method="GET" style="display:contents">
            <div style="flex:1;min-width:220px">
                <label style="font-size:11.5px;color:var(--ink-3);font-family:'JetBrains Mono',monospace;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.06em">Arama Terimi</label>
                <div style="position:relative">
                    <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--ink-3)" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" name="q" class="form-control" style="padding-left:38px" placeholder="Aramak istediğiniz..." value="<?= sanitize($q) ?>" autofocus>
                </div>
            </div>
            <div style="min-width:140px">
                <label style="font-size:11.5px;color:var(--ink-3);font-family:'JetBrains Mono',monospace;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.06em">Tür</label>
                <select name="type" class="form-control">
                    <option value="threads" <?= $type==='threads'?'selected':'' ?>>Konular</option>
                    <option value="posts"   <?= $type==='posts'?'selected':'' ?>>Mesajlar</option>
                    <option value="users"   <?= $type==='users'?'selected':'' ?>>Üyeler</option>
                </select>
            </div>
            <?php if ($type === 'threads'): ?>
            <div style="min-width:160px">
                <label style="font-size:11.5px;color:var(--ink-3);font-family:'JetBrains Mono',monospace;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:.06em">Forum</label>
                <select name="forum" class="form-control">
                    <option value="">Tümü</option>
                    <?php foreach ($forums as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= $forumId==$f['id']?'selected':'' ?>><?= sanitize($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div style="align-self:flex-end">
                <button type="submit" class="btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    Ara
                </button>
            </div>
        </form>
    </div>

    <?php if ($q && strlen($q) < 2): ?>
    <div class="card" style="text-align:center;padding:32px;color:var(--ink-3)">En az 2 karakter giriniz</div>
    <?php elseif ($q && empty($results)): ?>
    <div class="empty-state card">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <h3>"<?= sanitize($q) ?>" için sonuç bulunamadı</h3>
        <p style="font-size:13.5px">Farklı anahtar kelimeler deneyin</p>
    </div>
    <?php elseif (!empty($results)): ?>

    <?php if ($type === 'threads'): ?>
    <?php foreach ($results as $r): ?>
    <a href="<?= SITE_URL ?>/thread.php?id=<?= $r['id'] ?>" class="search-result-card">
        <div class="search-result-title"><?= highlight($r['title'], $q) ?></div>
        <div class="search-result-meta">
            <span><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><?= sanitize($r['username']) ?></span>
            <span><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg><?= sanitize($r['forum_name']) ?></span>
            <span><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><?= $r['reply_count'] ?> cevap</span>
            <span><?= timeAgo($r['created_at']) ?></span>
        </div>
    </a>
    <?php endforeach; ?>

    <?php elseif ($type === 'posts'): ?>
    <?php foreach ($results as $r): ?>
    <a href="<?= SITE_URL ?>/thread.php?id=<?= $r['thread_id'] ?>#post-<?= $r['id'] ?>" class="search-result-card">
        <div class="search-result-title" style="font-size:13.5px;color:var(--ink-2);margin-bottom:6px"><?= sanitize($r['thread_title']) ?></div>
        <div class="search-result-excerpt"><?= highlight(mb_substr(strip_tags($r['content']), 0, 200), $q) ?>…</div>
        <div class="search-result-meta">
            <span><?= sanitize($r['username']) ?></span>
            <span><?= sanitize($r['forum_name']) ?></span>
            <span><?= timeAgo($r['created_at']) ?></span>
        </div>
    </a>
    <?php endforeach; ?>

    <?php elseif ($type === 'users'): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px">
    <?php foreach ($results as $r): ?>
    <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($r['username']) ?>" style="display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--bg-surface);border:1px solid var(--border-1);border-radius:12px;text-decoration:none;transition:border-color .18s" onmouseover="this.style.borderColor='var(--accent-border)'" onmouseout="this.style.borderColor='var(--border-1)'">
        <img src="<?= getAvatar($r) ?>" alt="" style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:1.5px solid var(--border-1)">
        <div>
            <div style="font-size:14px;font-weight:700;color:var(--ink-0)"><?= highlight($r['username'], $q) ?></div>
            <span class="role-badge role-<?= $r['role'] ?>" style="margin-top:4px"><?= getRoleBadge($r['role'])['label'] ?></span>
            <div style="font-size:11.5px;color:var(--ink-3);margin-top:4px;font-family:'JetBrains Mono',monospace"><?= $r['post_count'] ?> mesaj</div>
        </div>
    </a>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php
        $baseUrl = SITE_URL . '/search.php?q=' . urlencode($q) . '&type=' . $type . '&forum=' . $forumId;
        if ($page > 1): ?>
        <a href="<?= $baseUrl ?>&page=<?= $page-1 ?>" class="page-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-3); $i <= min($pages,$page+3); $i++): ?>
        <a href="<?= $baseUrl ?>&page=<?= $i ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
        <a href="<?= $baseUrl ?>&page=<?= $page+1 ?>" class="page-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php elseif (!$q): ?>
    <div style="text-align:center;padding:48px;color:var(--ink-3)">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin:0 auto 16px;opacity:.25;display:block"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <p style="font-size:15px">Ne aramak istiyorsunuz?</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
