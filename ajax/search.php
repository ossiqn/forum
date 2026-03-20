<?php
$funcs = dirname(__DIR__) . '/includes/functions.php';
if (!file_exists($funcs)) {
    $funcs = dirname(__DIR__) . '/functions.php';
}
require_once $funcs;

$baseUrl = preg_replace('/\/ajax$/i', '', SITE_URL);
$q = trim($_GET['q'] ?? ($_POST['q'] ?? ''));

if (empty($q) || mb_strlen($q) < 2) {
    exit;
}

$q_like = '%' . $q . '%';

$threads = db()->fetchAll("SELECT id, title FROM threads WHERE title LIKE ? AND is_deleted = 0 ORDER BY id DESC LIMIT 5", [$q_like], 's');

$users = db()->fetchAll("SELECT id, username, avatar FROM users WHERE username LIKE ? AND is_banned = 0 ORDER BY id DESC LIMIT 3", [$q_like], 's');

if (empty($threads) && empty($users)) {
    echo '<div style="padding:12px;text-align:center;color:var(--ink-3);font-size:13px;">Sonuç bulunamadı</div>';
} else {
    if (!empty($users)) {
        echo '<div style="padding:8px 16px;font-size:11px;font-weight:700;color:var(--ink-3);text-transform:uppercase;letter-spacing:0.05em;background:var(--bg-canvas);">Kullanıcılar</div>';
        foreach ($users as $u) {
            $avatar = getAvatar($u);
            echo '<a href="' . $baseUrl . '/profile.php?u=' . urlencode($u['username']) . '" style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--border-0);color:var(--ink-1);text-decoration:none;font-size:13.5px;font-weight:600;" onmouseover="this.style.background=\'var(--bg-raised)\'" onmouseout="this.style.background=\'transparent\'">';
            echo '<img src="' . $avatar . '" style="width:24px;height:24px;border-radius:50%;object-fit:cover;">';
            echo sanitize($u['username']);
            echo '</a>';
        }
    }
    
    if (!empty($threads)) {
        echo '<div style="padding:8px 16px;font-size:11px;font-weight:700;color:var(--ink-3);text-transform:uppercase;letter-spacing:0.05em;background:var(--bg-canvas);">Konular</div>';
        foreach ($threads as $t) {
            echo '<a href="' . $baseUrl . '/thread.php?id=' . $t['id'] . '" style="display:block;padding:12px 16px;border-bottom:1px solid var(--border-0);color:var(--ink-1);text-decoration:none;font-size:13.5px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" onmouseover="this.style.background=\'var(--bg-raised)\'" onmouseout="this.style.background=\'transparent\'">' . sanitize($t['title']) . '</a>';
        }
    }
}
?>