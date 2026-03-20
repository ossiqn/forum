<?php
require_once 'includes/functions.php';
require_once 'includes/bbcode.php';

$username = $_GET['u'] ?? '';
if (!$username) { redirect(SITE_URL . '/index.php'); }

$profileUser = db()->fetchOne("SELECT * FROM users WHERE username = ?", [$username], 's');
if (!$profileUser || $profileUser['is_banned']) { redirect(SITE_URL . '/index.php'); }

$currentUser = currentUser();
$activeTab   = $_GET['tab'] ?? 'about';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn()) { redirect(SITE_URL . '/login.php'); }

    if (isset($_POST['delete_wall']) && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $wallId = (int)$_POST['delete_wall'];
        $wp = db()->fetchOne("SELECT * FROM profile_posts WHERE id = ?", [$wallId], 'i');
        if ($wp && ($currentUser['id'] === $profileUser['id'] || $currentUser['id'] === $wp['author_id'] || isAdmin())) {
            db()->query("UPDATE profile_posts SET is_deleted = 1 WHERE id = ?", [$wallId], 'i');
        }
        redirect(SITE_URL . '/profile.php?u=' . urlencode($username) . '&tab=wall');
    }

    if (isset($_POST['wall_post']) && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $wallContent = trim($_POST['wall_content'] ?? '');
        if ($wallContent && strlen($wallContent) <= 500) {
            db()->query("INSERT INTO profile_posts (profile_user_id, author_id, content) VALUES (?,?,?)",
                [$profileUser['id'], $currentUser['id'], $wallContent], 'iis');
            if ($profileUser['id'] !== $currentUser['id']) {
                createNotification($profileUser['id'], $currentUser['id'], 'mention', $profileUser['id'], 'user',
                    $currentUser['username'] . ' duvarınıza yazdı');
            }
        }
        redirect(SITE_URL . '/profile.php?u=' . urlencode($username) . '&tab=wall');
    }
}

$threads = db()->fetchAll(
    "SELECT t.*, f.name as forum_name, f.slug as forum_slug, c.name as cat_name
     FROM threads t
     JOIN forums f ON f.id = t.forum_id
     JOIN categories c ON c.id = f.category_id
     WHERE t.user_id = ? AND t.is_deleted = 0
     ORDER BY t.created_at DESC LIMIT 30",
    [$profileUser['id']], 'i'
);

$posts = db()->fetchAll(
    "SELECT p.*, t.title as thread_title, t.id as thread_id
     FROM posts p
     JOIN threads t ON t.id = p.thread_id
     WHERE p.user_id = ? AND p.is_deleted = 0
     ORDER BY p.created_at DESC LIMIT 30",
    [$profileUser['id']], 'i'
);

$receivedLikes = db()->fetchOne(
    "SELECT COALESCE(SUM(like_count),0) as c FROM posts WHERE user_id = ? AND is_deleted = 0",
    [$profileUser['id']], 'i'
);

$wallPosts = getProfilePosts($profileUser['id']);
$badges    = getUserBadges($profileUser['id']);

function safeSocial($user, $key) {
    return isset($user[$key]) ? trim($user[$key]) : '';
}

function buildSocialLinks($u) {
    $links = '';
    $items = [
        ['website',   safeSocial($u,'website'),   'website',   '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>'],
        ['twitter',   safeSocial($u,'twitter'),   'https://twitter.com/', '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>'],
        ['github',    safeSocial($u,'github'),    'https://github.com/', '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>'],
        ['discord',   safeSocial($u,'discord'),   '',          '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057c.003.02.015.04.03.05a19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>'],
        ['instagram', safeSocial($u,'instagram'), 'https://instagram.com/', '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>'],
        ['youtube',   safeSocial($u,'youtube'),   'https://youtube.com/@', '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.54C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>'],
        ['steam',     safeSocial($u,'steam'),     'https://steamcommunity.com/id/', '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>'],
        ['twitch',    safeSocial($u,'twitch'),    'https://twitch.tv/', '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2H3v16h5v4l4-4h5l4-4V2zm-10 9V7m5 4V7"/></svg>'],
    ];

    $colors = [
        'website'=>'#5b7fff','twitter'=>'#1d9bf0','github'=>'var(--ink-1)',
        'discord'=>'#5865f2','instagram'=>'#e1306c','youtube'=>'#ff0000',
        'steam'=>'var(--ink-2)','twitch'=>'#9146ff',
    ];

    foreach ($items as [$key, $val, $prefix, $icon]) {
        if (!$val) continue;
        $href = ($key === 'website') ? $val : (strpos($val,'http')===0 ? $val : $prefix . ltrim($val,'@/'));
        $label = ($key === 'twitter' || $key === 'instagram') ? '@' . ltrim($val,'@') : $val;
        $col = $colors[$key] ?? 'var(--accent)';
        $links .= '<a href="' . htmlspecialchars($href) . '" target="_blank" rel="noopener nofollow"'
            . ' style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:999px;font-size:12.5px;font-weight:600;color:' . $col . ';background:' . $col . '18;border:1px solid ' . $col . '30;text-decoration:none;transition:opacity .15s" onmouseover="this.style.opacity=\'.7\'" onmouseout="this.style.opacity=\'1\'">'
            . $icon . htmlspecialchars($label) . '</a>';
    }
    return $links;
}

$pageTitle = $profileUser['username'] . ' — Profil';
require_once 'includes/header.php';

$hasCover = !empty($profileUser['cover_photo']) && file_exists(UPLOADS_PATH . 'covers/' . $profileUser['cover_photo']);
$coverStyle = $hasCover
    ? 'background-image:url(' . UPLOADS_URL . 'covers/' . htmlspecialchars($profileUser['cover_photo']) . ');background-size:cover;background-position:center'
    : 'background:linear-gradient(135deg,var(--bg-elevated),var(--bg-overlay))';
?>

<div style="max-width:900px;margin:0 auto;padding:20px 16px 60px">

<div style="border-radius:14px;overflow:hidden;border:1px solid var(--border-1);margin-bottom:20px">

    <div style="height:180px;<?= $coverStyle ?>;position:relative">
        <div style="position:absolute;inset:0;background:linear-gradient(135deg,var(--accent-dim),var(--purple-dim))"></div>
        <?php if ($currentUser && $currentUser['id'] === $profileUser['id']): ?>
        <a href="<?= SITE_URL ?>/settings.php?tab=profile" style="position:absolute;bottom:12px;right:14px;display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:rgba(0,0,0,.45);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);border-radius:999px;font-size:12px;font-weight:500;color:rgba(255,255,255,.85);text-decoration:none">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Kapak Değiştir
        </a>
        <?php endif; ?>
    </div>

    <div style="background:var(--bg-surface);padding:0 20px 20px">
        <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:-44px;margin-bottom:16px">
            <div style="display:flex;align-items:flex-end;gap:14px">
                <div style="position:relative">
                    <img src="<?= getAvatar($profileUser) ?>" alt=""
                        style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--bg-surface);box-shadow:0 0 0 1.5px var(--border-2);display:block">
                    <?php if ($currentUser && $currentUser['id'] === $profileUser['id']): ?>
                    <a href="<?= SITE_URL ?>/settings.php?tab=profile" style="position:absolute;bottom:2px;right:2px;width:24px;height:24px;border-radius:50%;background:var(--accent);border:2px solid var(--bg-surface);display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
                <div style="padding-bottom:4px">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <h1 style="font-family:'Clash Display',sans-serif;font-size:22px;font-weight:700;color:var(--ink-0);letter-spacing:-.025em;line-height:1"><?= htmlspecialchars($profileUser['username']) ?></h1>
                        <span class="role-badge role-<?= $profileUser['role'] ?>"><?= getRoleBadge($profileUser['role'])['label'] ?></span>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:7px">
                        <?php if (safeSocial($profileUser,'location')): ?>
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:var(--ink-3);font-family:'JetBrains Mono',monospace">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?= htmlspecialchars($profileUser['location']) ?>
                        </span>
                        <?php endif; ?>
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:var(--ink-3);font-family:'JetBrains Mono',monospace">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?= timeAgo($profileUser['last_seen']) ?>
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:var(--ink-3);font-family:'JetBrains Mono',monospace">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?= formatDate($profileUser['created_at'], 'd.m.Y') ?>
                        </span>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;padding-bottom:4px">
                <?php if ($currentUser && $currentUser['id'] === $profileUser['id']): ?>
                <a href="<?= SITE_URL ?>/settings.php" class="btn-primary" style="font-size:12.5px;padding:7px 15px">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Düzenle
                </a>
                <?php elseif ($currentUser): ?>
                <a href="<?= SITE_URL ?>/messages.php?u=<?= urlencode($profileUser['username']) ?>" class="btn-ghost" style="font-size:12.5px;padding:7px 15px">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Mesaj
                </a>
                <?php endif; ?>
                <?php if (isAdmin() && $currentUser && $currentUser['id'] !== $profileUser['id']): ?>
                <a href="<?= SITE_URL ?>/admin/users.php?search=<?= urlencode($profileUser['username']) ?>" class="btn-ghost" style="font-size:12.5px;padding:7px 15px;color:var(--amber)">Yönet</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($badges)): ?>
        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px">
            <?php foreach ($badges as $b): ?>
            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;background:<?= htmlspecialchars($b['bg_color']) ?>;color:<?= htmlspecialchars($b['color']) ?>;border:1px solid <?= htmlspecialchars($b['color']) ?>44" title="<?= htmlspecialchars($b['description'] ?? '') ?>"><?= $b['icon'] ?> <?= htmlspecialchars($b['name']) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php $socialHtml = buildSocialLinks($profileUser); if ($socialHtml): ?>
        <div style="display:flex;flex-wrap:wrap;gap:7px;margin-bottom:12px"><?= $socialHtml ?></div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;border:1px solid var(--border-1);border-radius:10px;overflow:hidden;margin-bottom:18px">
            <?php foreach ([
                [$profileUser['thread_count'], 'Konu'],
                [$profileUser['post_count'],   'Mesaj'],
                [$receivedLikes['c'] ?? 0,     'Beğeni'],
                [formatDate($profileUser['created_at'],'d.m.Y'), 'Katılım'],
            ] as $i => [$v, $l]): ?>
            <?php if ($i > 0): ?><div style="width:1px;background:var(--border-0)"></div><?php endif; ?>
            <div style="padding:12px 8px;text-align:center">
                <div style="font-family:'Clash Display',sans-serif;font-size:17px;font-weight:700;color:var(--ink-0);line-height:1"><?= is_numeric($v) ? formatNumber((int)$v) : $v ?></div>
                <div style="font-size:10px;color:var(--ink-3);font-family:'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.06em;margin-top:4px"><?= $l ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="display:flex;border-bottom:1px solid var(--border-0);gap:0;overflow-x:auto;scrollbar-width:none">
            <?php foreach ([
                ['about',   'Hakkında'],
                ['threads', 'Konular (' . count($threads) . ')'],
                ['posts',   'Cevaplar (' . count($posts) . ')'],
                ['wall',    'Duvar (' . count($wallPosts) . ')'],
            ] as [$tk, $tl]): ?>
            <a href="?u=<?= urlencode($username) ?>&tab=<?= $tk ?>"
               style="padding:10px 16px;font-size:13.5px;font-weight:600;font-family:'Clash Display',sans-serif;text-decoration:none;white-space:nowrap;border-bottom:2px solid <?= $activeTab===$tk ? 'var(--accent)' : 'transparent' ?>;color:<?= $activeTab===$tk ? 'var(--ink-0)' : 'var(--ink-3)' ?>;transition:color .15s;letter-spacing:-.01em"
               onmouseover="this.style.color='var(--ink-0)'" onmouseout="this.style.color='<?= $activeTab===$tk ? 'var(--ink-0)' : 'var(--ink-3)' ?>'"><?= $tl ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($activeTab === 'about'): ?>
<div style="display:flex;flex-direction:column;gap:12px">
    <?php if (!empty($profileUser['about'])): ?>
    <div style="background:var(--bg-surface);border:1px solid var(--border-1);border-radius:12px;padding:20px">
        <h3 style="font-family:'Clash Display',sans-serif;font-size:14px;font-weight:700;color:var(--ink-0);margin-bottom:12px;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:1px solid var(--border-0)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Hakkımda
        </h3>
        <p style="font-size:14.5px;line-height:1.8;color:var(--ink-1)"><?= nl2br(htmlspecialchars($profileUser['about'])) ?></p>
    </div>
    <?php endif; ?>
    <?php if (!empty($profileUser['signature'])): ?>
    <div style="background:var(--bg-surface);border:1px solid var(--border-1);border-radius:12px;padding:20px">
        <h3 style="font-family:'Clash Display',sans-serif;font-size:14px;font-weight:700;color:var(--ink-0);margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--border-0)">İmza</h3>
        <p style="font-size:13.5px;line-height:1.7;color:var(--ink-2);font-style:italic"><?= nl2br(htmlspecialchars($profileUser['signature'])) ?></p>
    </div>
    <?php endif; ?>
    <?php if (empty($profileUser['about']) && empty($profileUser['signature'])): ?>
    <div style="background:var(--bg-surface);border:1px solid var(--border-1);border-radius:12px;padding:40px 20px;text-align:center;color:var(--ink-3)">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin:0 auto 12px;display:block;opacity:.3"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <p style="font-size:14px">Henüz bilgi eklenmemiş</p>
        <?php if ($currentUser && $currentUser['id'] === $profileUser['id']): ?>
        <a href="<?= SITE_URL ?>/settings.php" class="btn-primary" style="margin-top:14px;font-size:13px;padding:8px 18px">Profili Düzenle</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php elseif ($activeTab === 'threads'): ?>
<div style="display:flex;flex-direction:column;gap:6px">
    <?php if (empty($threads)): ?>
    <div style="background:var(--bg-surface);border:1px solid var(--border-1);border-radius:12px;padding:40px 20px;text-align:center;color:var(--ink-3)">
        <p>Henüz konu açılmamış</p>
    </div>
    <?php else: ?>
    <?php foreach ($threads as $t): ?>
    <a href="<?= SITE_URL ?>/thread.php?id=<?= $t['id'] ?>"
       style="display:flex;align-items:center;gap:12px;padding:13px 16px;background:var(--bg-surface);border:1px solid var(--border-1);border-radius:11px;text-decoration:none;transition:border-color .15s"
       onmouseover="this.style.borderColor='var(--accent-border)'" onmouseout="this.style.borderColor='var(--border-1)'">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" style="flex-shrink:0;opacity:.7"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <div style="flex:1;min-width:0">
            <div style="font-size:14px;font-weight:600;color:var(--ink-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($t['title']) ?></div>
            <div style="font-size:11.5px;color:var(--ink-3);margin-top:2px;font-family:'JetBrains Mono',monospace"><?= htmlspecialchars($t['cat_name']) ?> / <?= htmlspecialchars($t['forum_name']) ?> · <?= timeAgo($t['created_at']) ?></div>
        </div>
        <div style="display:flex;gap:12px;flex-shrink:0">
            <span style="font-family:'JetBrains Mono',monospace;font-size:12.5px;color:var(--ink-2)"><?= $t['reply_count'] ?> cevap</span>
            <span style="font-family:'JetBrains Mono',monospace;font-size:12.5px;color:var(--ink-3)"><?= formatNumber($t['view_count']) ?> görüntü</span>
        </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php elseif ($activeTab === 'posts'): ?>
<div style="display:flex;flex-direction:column;gap:6px">
    <?php if (empty($posts)): ?>
    <div style="background:var(--bg-surface);border:1px solid var(--border-1);border-radius:12px;padding:40px 20px;text-align:center;color:var(--ink-3)">
        <p>Henüz cevap yazılmamış</p>
    </div>
    <?php else: ?>
    <?php foreach ($posts as $p): ?>
    <a href="<?= SITE_URL ?>/thread.php?id=<?= $p['thread_id'] ?>#post-<?= $p['id'] ?>"
       style="display:block;padding:13px 16px;background:var(--bg-surface);border:1px solid var(--border-1);border-radius:11px;text-decoration:none;transition:border-color .15s"
       onmouseover="this.style.borderColor='var(--accent-border)'" onmouseout="this.style.borderColor='var(--border-1)'">
        <div style="font-size:12px;color:var(--ink-3);margin-bottom:5px;font-family:'JetBrains Mono',monospace;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span style="font-weight:600;color:var(--ink-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:300px"><?= htmlspecialchars($p['thread_title']) ?></span>
            <span style="margin-left:auto"><?= timeAgo($p['created_at']) ?></span>
        </div>
        <div style="font-size:13.5px;color:var(--ink-2);line-height:1.55"><?= htmlspecialchars(mb_substr(strip_tags($p['content']), 0, 200)) ?>…</div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php elseif ($activeTab === 'wall'): ?>
<div style="display:flex;flex-direction:column;gap:10px">
    <?php if ($currentUser): ?>
    <div style="background:var(--bg-surface);border:1px solid var(--border-1);border-radius:12px;padding:18px">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="wall_post" value="1">
            <div style="margin-bottom:10px">
                <textarea name="wall_content" class="editor-textarea" style="min-height:80px" placeholder="<?= htmlspecialchars($profileUser['username']) ?>'a bir şeyler yaz..." maxlength="500" required></textarea>
            </div>
            <button type="submit" class="btn-primary" style="font-size:13px;padding:8px 16px">Gönder</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if (empty($wallPosts)): ?>
    <div style="background:var(--bg-surface);border:1px solid var(--border-1);border-radius:12px;padding:40px 20px;text-align:center;color:var(--ink-3)">
        <p>Henüz duvar yazısı yok</p>
    </div>
    <?php else: ?>
    <?php foreach ($wallPosts as $wp): ?>
    <div style="background:var(--bg-surface);border:1px solid var(--border-1);border-radius:11px;padding:14px 16px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
            <img src="<?= getAvatar(['avatar'=>$wp['author_avatar'],'username'=>$wp['author_username']]) ?>" alt=""
                 style="width:32px;height:32px;border-radius:50%;object-fit:cover">
            <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($wp['author_username']) ?>"
               style="font-size:13.5px;font-weight:700;color:var(--ink-1);text-decoration:none"
               onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--ink-1)'"><?= htmlspecialchars($wp['author_username']) ?></a>
            <span style="margin-left:auto;font-size:11px;color:var(--ink-3);font-family:'JetBrains Mono',monospace"><?= timeAgo($wp['created_at']) ?></span>
            <?php if ($currentUser && ($currentUser['id'] === $profileUser['id'] || $currentUser['id'] === $wp['author_id'] || isAdmin())): ?>
            <form method="POST" style="margin:0" onsubmit="return confirm('Silinsin mi?')">
                <?= csrfField() ?>
                <input type="hidden" name="delete_wall" value="<?= $wp['id'] ?>">
                <button type="submit" style="background:none;border:none;color:var(--ink-3);cursor:pointer;padding:2px;display:flex;align-items:center" onmouseover="this.style.color='var(--red)'" onmouseout="this.style.color='var(--ink-3)'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                </button>
            </form>
            <?php endif; ?>
        </div>
        <p style="font-size:14px;color:var(--ink-1);line-height:1.6"><?= nl2br(htmlspecialchars($wp['content'])) ?></p>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
