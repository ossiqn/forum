<?php
$pageTitle = 'Ana Sayfa';
require_once 'includes/header.php';
$stats = getForumStats();

$recentThreads = db()->fetchAll(
    "SELECT t.*, u.username, u.avatar, u.role,
            f.name as forum_name, f.slug as forum_slug,
            c.name as cat_name, c.slug as cat_slug, c.color as cat_color,
            lu.username as last_user, lu.avatar as last_avatar,
            lp.created_at as last_post_at2
     FROM threads t
     JOIN users u ON u.id = t.user_id
     JOIN forums f ON f.id = t.forum_id
     JOIN categories c ON c.id = f.category_id
     LEFT JOIN posts lp ON lp.id = t.last_post_id
     LEFT JOIN users lu ON lu.id = lp.user_id
     WHERE t.is_deleted = 0
     ORDER BY t.last_post_at DESC
     LIMIT 20"
);

$categories = getCategories();
$onlineUsers = getOnlineUsers();
$onlineCount = count($onlineUsers);
$adminCount  = count(array_filter($onlineUsers, fn($u) => $u['role'] === 'admin'));
$modCount    = count(array_filter($onlineUsers, fn($u) => $u['role'] === 'moderator'));
$memberCount = $onlineCount - $adminCount - $modCount;
?>

<div style="max-width:1180px;margin:0 auto;padding:16px 14px 40px">

<div style="display:flex;align-items:center;gap:20px;padding:12px 20px;background:var(--bg-surface);border:1px solid var(--border-1);border-radius:12px;margin-bottom:20px;flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:18px">
        <?php foreach([
            [$stats['online_users'], 'Çevrimiçi', true],
            [$stats['total_users'],  'Üye',       false],
            [$stats['total_threads'],'Konu',       false],
            [$stats['total_posts'],  'Mesaj',      false],
        ] as [$v,$l,$dot]): ?>
        <div style="display:flex;align-items:center;gap:7px">
            <?php if($dot): ?><span class="online-dot"></span><?php endif; ?>
            <span style="font-family:'Clash Display',sans-serif;font-size:15px;font-weight:700;color:var(--ink-0)"><?= formatNumber($v) ?></span>
            <span style="font-size:12px;color:var(--ink-3);font-family:'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.06em"><?= $l ?></span>
        </div>
        <div style="width:1px;height:14px;background:var(--border-1)"></div>
        <?php endforeach; ?>
    </div>
    <div style="margin-left:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <?php if($adminCount>0): ?>
        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:var(--red-dim);border:1px solid rgba(255,95,87,.2);border-radius:999px;font-size:11.5px;font-weight:600;color:var(--red)"><span style="width:5px;height:5px;border-radius:50%;background:var(--red)"></span><?= $adminCount ?> Admin</span>
        <?php endif; ?>
        <?php if($modCount>0): ?>
        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:var(--green-dim);border:1px solid rgba(61,214,140,.2);border-radius:999px;font-size:11.5px;font-weight:600;color:var(--green)"><span style="width:5px;height:5px;border-radius:50%;background:var(--green)"></span><?= $modCount ?> Moderatör</span>
        <?php endif; ?>
        <?php if($memberCount>0): ?>
        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:var(--accent-dim);border:1px solid var(--accent-border);border-radius:999px;font-size:11.5px;font-weight:600;color:var(--accent)"><span style="width:5px;height:5px;border-radius:50%;background:var(--accent)"></span><?= $memberCount ?> Üye</span>
        <?php endif; ?>
    </div>
</div>

<div style="background:var(--bg-surface);border:1px solid var(--border-1);border-radius:14px;overflow:hidden;margin-bottom:28px">

    <div style="display:flex;align-items:center;border-bottom:1px solid var(--border-0);padding:0 4px;overflow-x:auto;scrollbar-width:none">
        <?php
        $tabs = [
            ['son-acilan',    'Son Açılan',    '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
            ['son-cevaplanan','Son Cevaplanan','<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
            ['populer',       'Popüler',       '<path d="M18 20V10M12 20V4M6 20v-6"/>'],
        ];
        $activeTab = $_GET['tab'] ?? 'son-acilan';
        foreach ($tabs as [$key,$label,$svg]):
        ?>
        <a href="?tab=<?= $key ?>" style="display:inline-flex;align-items:center;gap:7px;padding:12px 16px;font-size:13px;font-weight:600;font-family:'Satoshi',sans-serif;text-decoration:none;white-space:nowrap;border-bottom:2px solid <?= $activeTab===$key ? 'var(--accent)' : 'transparent' ?>;color:<?= $activeTab===$key ? 'var(--accent)' : 'var(--ink-3)' ?>;transition:color .15s">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $svg ?></svg>
            <?= $label ?>
        </a>
        <?php endforeach; ?>
        <?php foreach($categories as $cat): ?>
        <a href="?tab=cat-<?= $cat['slug'] ?>" style="display:inline-flex;align-items:center;gap:6px;padding:12px 14px;font-size:12.5px;font-weight:600;font-family:'Satoshi',sans-serif;text-decoration:none;white-space:nowrap;border-bottom:2px solid <?= $activeTab==='cat-'.$cat['slug'] ? $cat['color'] : 'transparent' ?>;color:<?= $activeTab==='cat-'.$cat['slug'] ? $cat['color'] : 'var(--ink-3)' ?>;transition:color .15s">
            <span style="width:7px;height:7px;border-radius:50%;background:<?= sanitize($cat['color']) ?>"></span>
            <?= sanitize($cat['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 140px 150px 130px;gap:0;padding:9px 16px;border-bottom:1px solid var(--border-0);background:var(--bg-raised)" class="thread-table-header-row">
        <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3);font-family:'JetBrains Mono',monospace">Konu Başlığı</div>
        <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3);font-family:'JetBrains Mono',monospace;text-align:center">Cevap / Görüntülenme</div>
        <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3);font-family:'JetBrains Mono',monospace">Son Cevap</div>
        <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3);font-family:'JetBrains Mono',monospace">Kategori</div>
    </div>

    <?php
    
    $filteredThreads = $recentThreads;
    if ($activeTab === 'son-cevaplanan') {
        usort($filteredThreads, fn($a,$b) => strtotime($b['last_post_at']) - strtotime($a['last_post_at']));
    } elseif ($activeTab === 'populer') {
        usort($filteredThreads, fn($a,$b) => ($b['reply_count'] + $b['view_count']) - ($a['reply_count'] + $a['view_count']));
    } elseif (str_starts_with($activeTab, 'cat-')) {
        $catSlugFilter = substr($activeTab, 4);
        $filteredThreads = array_filter($recentThreads, fn($t) => $t['cat_slug'] === $catSlugFilter);
    }
    if (empty($filteredThreads)):
    ?>
    <div style="padding:40px;text-align:center;color:var(--ink-3);font-size:14px">Bu kategoride henüz konu yok.</div>
    <?php else: ?>
    <?php foreach ($filteredThreads as $t): ?>
    <div style="display:grid;grid-template-columns:1fr 140px 150px 130px;gap:0;padding:10px 16px;border-bottom:1px solid var(--border-0);transition:background .15s;align-items:center" class="thread-table-row"
         onmouseover="this.style.background='var(--bg-raised)'" onmouseout="this.style.background=''">

        <div style="display:flex;align-items:center;gap:10px;min-width:0">
            <img src="<?= getAvatar(['avatar'=>$t['avatar'],'username'=>$t['username']]) ?>" alt=""
                 style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1.5px solid var(--border-1)">
            <div style="min-width:0">
                <a href="<?= SITE_URL ?>/thread.php?id=<?= $t['id'] ?>"
                   style="font-size:13.5px;font-weight:600;color:var(--ink-1);text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.4"
                   onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--ink-1)'">
                    <?php if($t['is_sticky']): ?><span style="display:inline-flex;align-items:center;padding:1px 6px;background:var(--amber-dim);border-radius:3px;font-size:10px;font-weight:700;color:var(--amber);margin-right:5px;font-family:'JetBrains Mono',monospace">SABİT</span><?php endif; ?>
                    <?= sanitize($t['title']) ?>
                </a>
                <div style="font-size:12px;color:var(--ink-3);margin-top:2px;font-family:'JetBrains Mono',monospace">
                    <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($t['username']) ?>" style="color:var(--accent);text-decoration:none" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'"><?= sanitize($t['username']) ?></a>
                    · <a href="<?= SITE_URL ?>/forum.php?forum=<?= sanitize($t['forum_slug']) ?>" style="color:var(--ink-3);text-decoration:none" onmouseover="this.style.color='var(--ink-1)'" onmouseout="this.style.color='var(--ink-3)'"><?= sanitize($t['forum_name']) ?></a>
                    · <?= timeAgo($t['created_at']) ?>
                </div>
            </div>
        </div>

        <div style="text-align:center;display:flex;align-items:center;justify-content:center;gap:10px">
            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:var(--ink-1)"><?= formatNumber($t['reply_count']) ?></span>
            <span style="width:7px;height:7px;border-radius:50%;background:<?= $t['reply_count']>0 ? 'var(--green)' : 'var(--ink-3)' ?>;flex-shrink:0"></span>
            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:var(--ink-1)"><?= formatNumber($t['view_count']) ?></span>
        </div>

        <div style="min-width:0">
            <?php if ($t['last_user']): ?>
            <div style="font-size:12.5px;font-weight:600;color:var(--ink-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($t['last_user']) ?>" style="color:var(--ink-1);text-decoration:none" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--ink-1)'"><?= sanitize($t['last_user']) ?></a>
            </div>
            <div style="font-size:11.5px;color:var(--ink-3);font-family:'JetBrains Mono',monospace;margin-top:1px"><?= timeAgo($t['last_post_at']) ?></div>
            <?php else: ?>
            <div style="font-size:12px;color:var(--ink-3)">—</div>
            <?php endif; ?>
        </div>

        <div>
            <a href="<?= SITE_URL ?>/forum.php?cat=<?= sanitize($t['cat_slug']) ?>"
               style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:5px;font-size:11.5px;font-weight:600;color:<?= sanitize($t['cat_color']) ?>;background:<?= sanitize($t['cat_color']) ?>18;border:1px solid <?= sanitize($t['cat_color']) ?>33;text-decoration:none;white-space:nowrap;max-width:150px;overflow:hidden;text-overflow:ellipsis">
                <?= sanitize($t['cat_name']) ?>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <div style="padding:11px 16px;text-align:center;border-top:1px solid var(--border-0)">
        <a href="<?= SITE_URL ?>/forum.php" style="font-size:13px;font-weight:600;color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:6px" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">
            Daha Fazla Konu Göster
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
    </div>
</div>

<?php echo renderAd('thread_between', 'mb-6'); ?>

<?php foreach ($categories as $cat):
    $catForums = getForumsByCategory($cat['id']);
    if (empty($catForums)) continue;
    $catThreadTotal = array_sum(array_column($catForums, 'thread_count'));
    $catPostTotal   = array_sum(array_column($catForums, 'post_count'));
?>

<div style="background:var(--bg-surface);border:1px solid var(--border-1);border-radius:14px;overflow:hidden;margin-bottom:20px">

    <div style="display:grid;grid-template-columns:1fr 260px 200px;background:var(--bg-raised);border-bottom:2px solid <?= sanitize($cat['color']) ?>;padding:0">
        <div style="display:flex;align-items:center;gap:12px;padding:12px 18px">
            <div style="width:32px;height:32px;border-radius:8px;background:<?= sanitize($cat['color']) ?>1a;border:1px solid <?= sanitize($cat['color']) ?>33;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="<?= sanitize($cat['color']) ?>" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
            <a href="<?= SITE_URL ?>/forum.php?cat=<?= sanitize($cat['slug']) ?>" style="font-family:'Clash Display',sans-serif;font-size:14px;font-weight:700;color:var(--ink-0);text-decoration:none;letter-spacing:-.01em" onmouseover="this.style.color='<?= sanitize($cat['color']) ?>'" onmouseout="this.style.color='var(--ink-0)'"><?= sanitize($cat['name']) ?></a>
        </div>
        <div style="display:flex;align-items:center;justify-content:center;padding:12px 16px;border-left:1px solid var(--border-0)">
            <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3);font-family:'JetBrains Mono',monospace">Son Mesaj</span>
        </div>
        <div style="display:flex;align-items:center;justify-content:center;gap:24px;padding:12px 16px;border-left:1px solid var(--border-0)">
            <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3);font-family:'JetBrains Mono',monospace">Konu / Mesaj</span>
        </div>
    </div>

    <?php foreach ($catForums as $forum):
        
        $viewing = db()->fetchOne("SELECT COUNT(DISTINCT u.id) as c FROM users u WHERE u.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND u.is_banned = 0", [], '');
        $viewingCount = (int)($viewing['c'] ?? 0);
    ?>
    <div style="display:grid;grid-template-columns:1fr 240px 180px;border-bottom:1px solid var(--border-0)" class="forum-list-row">

        <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 18px">
            <div style="width:44px;height:44px;border-radius:10px;background:var(--bg-elevated);border:1px solid var(--border-1);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--ink-3)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <a href="<?= SITE_URL ?>/forum.php?forum=<?= sanitize($forum['slug']) ?>"
                       style="font-size:14.5px;font-weight:700;color:var(--ink-0);text-decoration:none;font-family:'Satoshi',sans-serif;letter-spacing:-.01em"
                       onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--ink-0)'"><?= sanitize($forum['name']) ?></a>
                    <?php if ($viewingCount > 0): ?>
                    <span style="font-size:11px;color:var(--ink-3);font-family:'JetBrains Mono',monospace">(<?= $viewingCount ?> kişi görüntülüyor)</span>
                    <?php endif; ?>
                </div>
                <?php if ($forum['description']): ?>
                <p style="font-size:12.5px;color:var(--ink-3);margin-top:3px;line-height:1.5"><?= sanitize($forum['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div style="padding:14px 16px;border-left:1px solid var(--border-0);display:flex;align-items:center">
            <?php if ($forum['last_thread_title']): ?>
            <div style="min-width:0;width:100%">
                <a href="<?= SITE_URL ?>/thread.php?id=<?= (int)$forum['last_post_id'] ?>"
                   style="font-size:12.5px;font-weight:600;color:var(--ink-1);text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                   onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--ink-1)'"><?= sanitize($forum['last_thread_title']) ?></a>
                <div style="font-size:11.5px;color:var(--ink-3);margin-top:3px;font-family:'JetBrains Mono',monospace;display:flex;align-items:center;gap:6px">
                    <?php if ($forum['last_post_user']): ?>
                    <img src="<?= getAvatar(['avatar'=>$forum['last_post_avatar']??null,'username'=>$forum['last_post_user']]) ?>" alt=""
                         style="width:16px;height:16px;border-radius:50%;object-fit:cover">
                    <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($forum['last_post_user']) ?>" style="color:var(--accent);text-decoration:none"><?= sanitize($forum['last_post_user']) ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <span style="font-size:12px;color:var(--ink-3)">Henüz konu yok</span>
            <?php endif; ?>
        </div>

        <div style="padding:14px 16px;border-left:1px solid var(--border-0);display:flex;align-items:center;justify-content:center;gap:24px">
            <div style="text-align:center">
                <div style="font-family:'Clash Display',sans-serif;font-size:16px;font-weight:700;color:var(--ink-0);line-height:1"><?= formatNumber($forum['thread_count']) ?></div>
                <div style="font-size:10px;color:var(--ink-3);text-transform:uppercase;letter-spacing:.06em;font-family:'JetBrains Mono',monospace;margin-top:3px">Konu</div>
            </div>
            <div style="width:1px;height:28px;background:var(--border-0)"></div>
            <div style="text-align:center">
                <div style="font-family:'Clash Display',sans-serif;font-size:16px;font-weight:700;color:var(--ink-0);line-height:1"><?= formatNumber($forum['post_count']) ?></div>
                <div style="font-size:10px;color:var(--ink-3);text-transform:uppercase;letter-spacing:.06em;font-family:'JetBrains Mono',monospace;margin-top:3px">Mesaj</div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

</div>
<?php endforeach; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
