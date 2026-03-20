<?php
require_once __DIR__ . '/../includes/functions.php';
$currentUser = currentUser();
$stats = getForumStats();
$unreadCount = $currentUser ? getUnreadNotificationCount($currentUser['id']) : 0;
$categories = getCategories();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$userTheme = getUserTheme();
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?= $userTheme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
    <meta name="description" content="<?= isset($pageDesc) ? sanitize($pageDesc) : 'Modern forum platformu' ?>">
    <link rel="preconnect" href="https://api.fontshare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://api.fontshare.com/v2/css?f[]=clash-display@400,500,600,700&f[]=satoshi@400,500,600,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Satoshi', 'sans-serif'],
                        display: ['Clash Display', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace']
                    }
                }
            }
        }
    </script>
    <style>
        .nav-icon-wrapper:hover #profile-dropdown {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }
        #profile-dropdown.show-menu {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }
        
        #ossiqn-mobile-menu {
            display: none;
            position: fixed !important;
            top: 60px !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            background: var(--bg-surface) !important;
            z-index: 9999999 !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            padding-bottom: 40px !important;
        }
        #ossiqn-mobile-menu.menu-active {
            display: block !important;
            animation: fadeInMenu 0.2s ease-out forwards;
        }

        @keyframes fadeInMenu {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .mobile-menu-item {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 14px 20px !important;
            color: var(--ink-0) !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            border-bottom: 1px solid var(--border-0) !important;
            transition: background 0.2s !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .mobile-menu-item:active {
            background: var(--bg-raised) !important;
        }
        .mobile-menu-item svg {
            flex-shrink: 0 !important;
        }
        
        @media (max-width: 1024px) {
            .desktop-only { display: none !important; }
        }
    </style>
</head>
<body>
<div class="bg-blobs" aria-hidden="true">
    <div class="bg-blob bg-blob-1"></div>
    <div class="bg-blob bg-blob-2"></div>
    <div class="bg-blob bg-blob-3"></div>
</div>

<div id="app-wrapper">

<nav id="navbar" style="position: fixed; top: 0; left: 0; width: 100%; height: 60px; z-index: 99999; background: var(--bg-surface); border-bottom: 1px solid var(--border-1);">
    <div class="mx-auto px-4" style="max-width: 1100px; height: 100%;">
        <div style="display:flex;align-items:center;justify-content:space-between; height: 100%; gap: 10px;">

            <div class="flex items-center gap-2 sm:gap-6" style="flex-shrink: 0;">
                <a href="<?= SITE_URL ?>/index.php" class="logo-link flex-shrink-0">
                    <svg class="logo-icon" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="32" height="32" rx="9" fill="url(#lg1)"/>
                        <path d="M8 11h16M8 16h11M8 21h13" stroke="white" stroke-width="2.2" stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="lg1" x1="0" y1="0" x2="32" y2="32">
                                <stop offset="0%" stop-color="#5b7fff"/>
                                <stop offset="100%" stop-color="#9b6dff"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <span class="logo-text hidden sm:block"><?= SITE_NAME ?></span>
                </a>

                <div class="hidden lg:flex items-center gap-1 desktop-only">
                    <a href="<?= SITE_URL ?>/index.php" class="nav-link <?= $currentPage==='index'?'active':'' ?>">Ana Sayfa</a>
                    <div class="nav-dropdown-wrapper">
                        <button class="nav-link flex items-center gap-1">
                            Forum
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="nav-dropdown">
                            <?php foreach ($categories as $cat): ?>
                            <a href="<?= SITE_URL ?>/forum.php?cat=<?= sanitize($cat['slug']) ?>" class="dropdown-item">
                                <span class="dropdown-dot" style="background:<?= sanitize($cat['color']) ?>"></span>
                                <?= sanitize($cat['name']) ?>
                                <span class="dropdown-count"><?= $cat['forum_count'] ?></span>
                            </a>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?= SITE_URL ?>/forum.php" class="dropdown-item" style="color:var(--accent)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                Tüm Forumlar
                            </a>
                        </div>
                    </div>
                    <a href="<?= SITE_URL ?>/forum.php" class="nav-link <?= $currentPage==='forum'?'active':'' ?>">Kategoriler</a>
                </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;flex:1;min-width:0;">
                
                <div class="relative search-wrapper desktop-only" style="width:100%;max-width:180px;">
                    <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--ink-2);pointer-events:none;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" id="global-search" class="search-input" style="width:100%;padding:8px 10px 8px 30px;border-radius:8px;background:var(--bg-base);border:1px solid var(--border-1);color:var(--ink-0);font-size:13px;outline:none;" placeholder="Ara..." autocomplete="off">
                    <div id="search-results" class="search-dropdown hidden"></div>
                </div>

                <button class="lg:hidden" onclick="focusMobileSearch(event)" title="Arama" style="display:flex !important; align-items:center; justify-content:center; width:34px; height:34px; border:none; background:transparent; color:var(--ink-0) !important; cursor:pointer; flex-shrink:0; padding:0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--ink-0)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--ink-0) !important;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>

                <button id="theme-toggle-btn" onclick="toggleTheme()" title="Tema Değiştir" style="display:flex !important; align-items:center; justify-content:center; width:34px; height:34px; border:none; background:transparent; color:var(--ink-0) !important; cursor:pointer; flex-shrink:0; padding:0;">
                    <svg class="icon-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--ink-0)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="stroke: var(--ink-0) !important;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    <svg class="icon-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--ink-0)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none; stroke: var(--ink-0) !important;"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                </button>

                <?php if ($currentUser): ?>
                <?php $dmUnread = getUnreadDmCount($currentUser['id']); ?>
                
                <a href="<?= SITE_URL ?>/messages.php" class="nav-icon-btn desktop-only" style="text-decoration:none;position:relative;color:var(--ink-0) !important;" title="Mesajlar">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <?php if ($dmUnread > 0): ?>
                    <span style="position:absolute;top:-4px;right:-4px;min-width:16px;height:16px;background:var(--accent);color:white;font-size:9px;font-weight:700;border-radius:999px;display:flex;align-items:center;justify-content:center;padding:0 3px;border:2px solid var(--bg-canvas)"><?= $dmUnread > 9 ? '9+' : $dmUnread ?></span>
                    <?php endif; ?>
                </a>
                
                <div class="nav-icon-wrapper desktop-only">
                    <button class="nav-icon-btn" id="notif-btn" onclick="openNotifDropdown(event)" style="color:var(--ink-0) !important;">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <?php if ($unreadCount > 0): ?>
                        <span class="notif-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="nav-dropdown notif-dropdown" id="notif-dropdown">
                        <div class="dropdown-header">
                            <span>Bildirimler</span>
                            <button onclick="markAllRead()">Tümünü oku</button>
                        </div>
                        <?php $notifications = getNotifications($currentUser['id'], 5); ?>
                        <?php if (empty($notifications)): ?>
                        <div class="empty-state text-sm" style="padding:20px">Bildirim yok</div>
                        <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                        <div class="notif-item <?= !$n['is_read']?'unread':'' ?>">
                            <div class="notif-avatar">
                                <?php if ($n['from_avatar']): ?>
                                <img src="<?= UPLOADS_URL ?>avatars/<?= sanitize($n['from_avatar']) ?>" alt="">
                                <?php else: ?>
                                <div class="notif-avatar-default"><?= strtoupper(substr($n['from_username']??'S',0,1)) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="notif-content">
                                <p><?= sanitize($n['message']) ?></p>
                                <span><?= timeAgo($n['created_at']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="nav-icon-wrapper desktop-only">
                    <button class="profile-nav-btn" id="profile-nav-btn" onclick="toggleProfileMenu(event)">
                        <img src="<?= getAvatar($currentUser) ?>" alt="">
                        <span class="hidden md:block text-sm font-medium" style="color:var(--ink-1)"><?= sanitize($currentUser['username']) ?></span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="nav-dropdown profile-dropdown" id="profile-dropdown">
                        <div class="dropdown-profile-header">
                            <img src="<?= getAvatar($currentUser) ?>" alt="" style="width:38px;height:38px;border-radius:50%;object-fit:cover">
                            <div>
                                <p style="font-family:'Clash Display',sans-serif;font-weight:700;font-size:14px;color:var(--ink-0)"><?= sanitize($currentUser['username']) ?></p>
                                <span class="role-badge role-<?= $currentUser['role'] ?>"><?= getRoleBadge($currentUser['role'])['label'] ?></span>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($currentUser['username']) ?>" class="dropdown-item">Profilim</a>
                        <a href="<?= SITE_URL ?>/settings.php" class="dropdown-item">Ayarlar</a>
                        <?php if (isAdmin()): ?>
                        <a href="<?= SITE_URL ?>/admin/index.php" class="dropdown-item text-amber">Admin Panel</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= SITE_URL ?>/logout.php" class="dropdown-item text-red">Çıkış Yap</a>
                    </div>
                </div>
                <?php else: ?>
                <div class="desktop-only" style="display:flex;gap:8px;">
                    <a href="<?= SITE_URL ?>/login.php" class="btn-ghost">Giriş</a>
                    <a href="<?= SITE_URL ?>/register.php" class="btn-primary">Kayıt Ol</a>
                </div>
                <?php endif; ?>

                <?php if (isLoggedIn()): ?>
                <button class="btn-primary desktop-only" id="new-thread-btn" onclick="openNewThreadModal()" style="padding:7px 14px;font-size:13px">
                    <span class="hidden sm:inline">Konu Aç</span>
                </button>
                <?php endif; ?>

                <button class="lg:hidden" id="ossiqn-mobile-btn" onclick="toggleOssiqnMenu(event)" style="display:flex !important; align-items:center; justify-content:center; width:34px; height:34px; border:none; background:transparent; color:var(--ink-0) !important; cursor:pointer; flex-shrink:0; padding:0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--ink-0)" stroke-width="2" style="stroke: var(--ink-0) !important; pointer-events:none;"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
            </div>
        </div>
    </div>
</nav>

<div id="ossiqn-mobile-menu">
    
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-0);">
        <div style="position:relative; display:flex; align-items:center;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute; left:14px; color:var(--ink-2);"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" id="mobile-search-input-field" placeholder="Forumda ara..." style="width:100%; padding: 10px 10px 10px 38px; background:var(--bg-base); border:1px solid var(--border-1); border-radius:8px; color:var(--ink-0); font-size:14px; outline:none;">
        </div>
    </div>

    <?php if ($currentUser): ?>
    <div style="padding: 16px 20px; display:flex; align-items:center; gap:12px; border-bottom: 1px solid var(--border-0);">
        <img src="<?= getAvatar($currentUser) ?>" alt="" style="width:42px; height:42px; border-radius:50%; border:1px solid var(--border-1); object-fit:cover; flex-shrink:0;">
        <div>
            <div style="font-weight:700; color:var(--ink-0); font-size:15px; font-family:'Clash Display',sans-serif;"><?= sanitize($currentUser['username']) ?></div>
            <div style="font-size:11px; color:var(--ink-2); margin-top:2px;"><?= getRoleBadge($currentUser['role'])['label'] ?></div>
        </div>
    </div>

    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-0);">
        <button onclick="openNewThreadModal(); toggleOssiqnMenu(event);" style="width:100%; padding:12px; background:var(--accent); color:#fff; border:none; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Yeni Konu Aç
        </button>
    </div>

    <a href="<?= SITE_URL ?>/messages.php" class="mobile-menu-item">
        <span style="display:flex; align-items:center; gap:12px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Mesajlar
        </span>
        <?php if ($dmUnread > 0): ?>
        <span style="background:var(--accent);color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:700;"><?= $dmUnread ?></span>
        <?php endif; ?>
    </a>
    
    <a href="<?= SITE_URL ?>/notifications.php" class="mobile-menu-item">
        <span style="display:flex; align-items:center; gap:12px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            Bildirimler
        </span>
        <?php if ($unreadCount > 0): ?>
        <span style="background:var(--red);color:#fff;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:700;"><?= $unreadCount ?></span>
        <?php endif; ?>
    </a>
    <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($currentUser['username']) ?>" class="mobile-menu-item">
        <span style="display:flex; align-items:center; gap:12px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profilim
        </span>
    </a>
    <?php if (isAdmin()): ?>
    <a href="<?= SITE_URL ?>/admin/index.php" class="mobile-menu-item" style="color:var(--amber) !important;">
        <span style="display:flex; align-items:center; gap:12px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Admin Panel
        </span>
    </a>
    <?php endif; ?>
    
    <?php else: ?>
    <div style="padding: 16px 20px; display:flex; gap:10px; border-bottom: 1px solid var(--border-0);">
        <a href="<?= SITE_URL ?>/login.php" style="flex:1; text-align:center; padding:12px; background:var(--bg-raised); border:1px solid var(--border-1); border-radius:8px; color:var(--ink-0); text-decoration:none; font-weight:700; font-size:14px;">Giriş Yap</a>
        <a href="<?= SITE_URL ?>/register.php" style="flex:1; text-align:center; padding:12px; background:var(--accent); border-radius:8px; color:#fff; text-decoration:none; font-weight:700; font-size:14px;">Kayıt Ol</a>
    </div>
    <?php endif; ?>

    <div style="padding: 16px 20px 8px; font-size:11px; color:var(--ink-3); font-weight:700; text-transform:uppercase; letter-spacing:1px;">Sayfalar</div>
    <a href="<?= SITE_URL ?>/index.php" class="mobile-menu-item">
        <span style="display:flex; align-items:center; gap:12px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Ana Sayfa
        </span>
    </a>
    <a href="<?= SITE_URL ?>/forum.php" class="mobile-menu-item">
        <span style="display:flex; align-items:center; gap:12px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><line x1="3" x2="21" y1="9" y2="9"/><line x1="9" x2="9" y1="21" y2="9"/></svg>
            Tüm Forumlar
        </span>
    </a>
    
    <div style="padding: 16px 20px 8px; font-size:11px; color:var(--ink-3); font-weight:700; text-transform:uppercase; letter-spacing:1px;">Kategoriler</div>
    <?php foreach ($categories as $cat): ?>
    <a href="<?= SITE_URL ?>/forum.php?cat=<?= sanitize($cat['slug']) ?>" class="mobile-menu-item" style="padding-top: 10px !important; padding-bottom: 10px !important; border-bottom: none !important;">
        <span style="display:flex; align-items:center;">
            <span style="display:inline-block;width:6px;height:6px;border-radius:50%;margin-right:12px;background:<?= sanitize($cat['color']) ?>"></span>
            <?= sanitize($cat['name']) ?>
        </span>
    </a>
    <?php endforeach; ?>
    
    <?php if ($currentUser): ?>
    <div style="margin-top: 16px; border-top: 1px solid var(--border-0);">
        <a href="<?= SITE_URL ?>/logout.php" class="mobile-menu-item" style="color:var(--red) !important; justify-content:center !important; border-bottom:none !important;">Çıkış Yap</a>
    </div>
    <?php endif; ?>
</div>

<?php if (isLoggedIn()):
    $__forums = db()->fetchAll("SELECT f.id,f.name,f.slug,c.name as cn,c.color FROM forums f JOIN categories c ON c.id=f.category_id WHERE f.is_active=1 ORDER BY c.display_order,f.display_order");
?>
<div id="ntm" style="display:none;position:fixed;inset:0;z-index:9999999;align-items:center;justify-content:center;padding:20px">
    <div onclick="document.getElementById('ntm').style.display='none';document.body.style.overflow=''" style="position:absolute;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(8px)"></div>
    <div style="position:relative;z-index:1;background:var(--bg-surface);border:1px solid var(--border-1);border-radius:18px;width:100%;max-width:540px;max-height:85vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,0.7)">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid var(--border-0);position:sticky;top:0;background:var(--bg-surface);border-radius:18px 18px 0 0;z-index:2">
            <span style="font-family:'Clash Display',sans-serif;font-size:16px;font-weight:700;color:var(--ink-0)">Yeni Konu Aç</span>
            <button onclick="document.getElementById('ntm').style.display='none';document.body.style.overflow=''" style="width:30px;height:30px;border-radius:8px;background:var(--bg-raised);border:1px solid var(--border-1);color:var(--ink-0);cursor:pointer;display:flex;align-items:center;justify-content:center">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div style="padding:18px 22px 22px">
            <p style="font-size:10.5px;color:var(--ink-3);margin-bottom:13px;font-family:'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.09em">Forum Seç</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <?php foreach ($__forums as $__f): ?>
                <a href="<?= SITE_URL ?>/new-thread.php?forum=<?= urlencode($__f['slug']) ?>" style="padding:11px 13px;background:var(--bg-raised);border:1px solid var(--border-1);border-radius:10px;display:flex;align-items:center;gap:10px;text-decoration:none;transition:border-color .15s,background .15s" onmouseover="this.style.borderColor='var(--accent-border)';this.style.background='var(--accent-dim)'" onmouseout="this.style.borderColor='var(--border-1)';this.style.background='var(--bg-raised)'">
                    <span style="width:9px;height:9px;border-radius:50%;flex-shrink:0;background:<?= $__f['color'] ?>"></span>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--ink-1)"><?= htmlspecialchars($__f['name']) ?></div>
                        <div style="font-size:11px;color:var(--ink-3);font-family:'JetBrains Mono',monospace;margin-top:1px"><?= htmlspecialchars($__f['cn']) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script>
function openNewThreadModal(){
    var m=document.getElementById('ntm');
    if(!m) return;
    m.style.display='flex';
    document.body.style.overflow='hidden';
}
function closeNewThreadModal(){ 
    var m=document.getElementById('ntm');
    if(m){m.style.display='none';document.body.style.overflow='';}
}
function goNewThread(slug){
    window.location.href='<?= SITE_URL ?>/new-thread.php?forum='+encodeURIComponent(slug);
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeNewThreadModal();});
</script>
<?php endif; ?>

<div id="custom-confirm-modal" class="modal-overlay" style="display: flex; align-items: center; justify-content: center; position: fixed; inset: 0; z-index: 10000; opacity: 0; visibility: hidden; padding: 20px;">
    <div onclick="closeConfirmModal()" style="position: absolute; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);"></div>
    <div class="modal-box" style="position: relative; z-index: 1; max-width: 400px; text-align: center; padding: 30px 24px; background: var(--bg-surface); border: 1px solid var(--border-1); border-radius: 18px; box-shadow: 0 24px 80px rgba(0,0,0,0.7);">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--red-dim); color: var(--red); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <h3 style="font-family: 'Clash Display', sans-serif; font-size: 20px; font-weight: 700; color: var(--ink-0); margin-bottom: 10px;">Emin misiniz?</h3>
        <p id="confirm-modal-msg" style="font-size: 14px; color: var(--ink-2); margin-bottom: 24px; line-height: 1.6;"></p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button type="button" class="btn-ghost" onclick="closeConfirmModal()" style="padding: 10px 20px;">İptal Et</button>
            <a href="#" id="confirm-modal-btn" class="btn-danger" style="padding: 10px 20px; text-decoration: none;">Evet, Onaylıyorum</a>
        </div>
    </div>
</div>

<script>
function focusMobileSearch(e) {
    e.preventDefault();
    var menu = document.getElementById('ossiqn-mobile-menu');
    if (menu.style.display !== 'block') {
        toggleOssiqnMenu(e);
    }
    setTimeout(() => {
        document.getElementById('mobile-search-input-field').focus();
    }, 100);
}

function toggleOssiqnMenu(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    var menu = document.getElementById('ossiqn-mobile-menu');
    
    if (menu) {
        if (menu.style.display === 'none' || menu.style.display === '') {
            menu.style.display = 'block'; 
            document.body.style.overflow = 'hidden'; 
        } else {
            menu.style.display = 'none'; 
            document.body.style.overflow = ''; 
        }
    }
}

function toggleProfileMenu(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    var menu = document.getElementById('profile-dropdown');
    if (menu) { menu.classList.toggle('show-menu'); }
}

document.addEventListener('click', function(e) {
    var profileMenu = document.getElementById('profile-dropdown');
    var profileBtn = document.getElementById('profile-nav-btn');
    if (profileMenu && profileMenu.classList.contains('show-menu')) {
        if (!profileMenu.contains(e.target) && profileBtn && !profileBtn.contains(e.target)) {
            profileMenu.classList.remove('show-menu');
        }
    }

    var mobileMenu = document.getElementById('ossiqn-mobile-menu');
    var mobileBtn = document.getElementById('ossiqn-mobile-btn');
    
    if (mobileMenu && mobileMenu.style.display === 'block') {
        if (!mobileMenu.contains(e.target) && mobileBtn && !mobileBtn.contains(e.target)) {
            mobileMenu.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
});

function openConfirmModal(msg, action) {
    document.getElementById('confirm-modal-msg').innerText = msg;
    const btn = document.getElementById('confirm-modal-btn');
    btn.onclick = null;
    btn.removeAttribute('href');
    
    if (typeof action === 'string') {
        btn.href = action;
        btn.onclick = function() { closeConfirmModal(); };
    } else {
        btn.href = 'javascript:void(0)';
        btn.onclick = function(e) {
            e.preventDefault();
            action.submit();
        };
    }
    const modal = document.getElementById('custom-confirm-modal');
    modal.style.opacity = '1';
    modal.style.visibility = 'visible';
    modal.classList.add('open');
}

function closeConfirmModal() {
    const modal = document.getElementById('custom-confirm-modal');
    modal.style.opacity = '0';
    modal.style.visibility = 'hidden';
    modal.classList.remove('open');
}

function confirmDelete(msg, url) {
    openConfirmModal(msg, url);
    return false;
}
</script>

<div style="padding-top:75px">
<?php
$headerAd = null;
try {
    $headerAd = db()->fetchOne("SELECT * FROM advertisements WHERE position = 'header_banner' AND is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY id DESC LIMIT 1");
} catch(Exception $e) {}

if ($headerAd):
    $adHtml = '';
    if ($headerAd['type'] === 'image' && !empty($headerAd['image_url'])) {
        $link = !empty($headerAd['link_url']) ? sanitize($headerAd['link_url']) : '#';
        $adHtml = '<a href="'.$link.'" target="_blank" rel="nofollow"><img src="'.sanitize($headerAd['image_url']).'" style="width:100%;border-radius:10px;display:block;" alt="Reklam"></a>';
    } else {
        $adHtml = $headerAd['html_code'];
    }
?>
<div style="max-width:970px;margin:0 auto;padding:15px 16px 0">
    <div style="position:relative">
        <span style="position:absolute;top:6px;right:8px;background:var(--bg-surface);color:var(--ink-3);font-size:9px;font-weight:700;font-family:'JetBrains Mono',monospace;padding:2px 6px;border-radius:999px;border:1px solid var(--border-1);z-index:10;pointer-events:none;">REKLAM</span>
        <?= $adHtml ?>
    </div>
</div>
<?php endif; ?>