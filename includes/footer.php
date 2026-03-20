<?php
$fStats = getForumStats();
$fCats  = getCategories();
$fUser  = currentUser();
?>
</div>

<?php echo renderAd('footer_banner', 'ad-slot-footer hidden md:block'); ?>

<footer style="position:relative;margin-top:80px;border-top:1px solid var(--border-0);background:var(--bg-canvas);overflow:hidden">

    <div style="position:absolute;top:-60px;left:50%;transform:translateX(-50%);width:600px;height:120px;background:radial-gradient(ellipse,rgba(91,127,255,0.07) 0%,transparent 70%);pointer-events:none"></div>

    <div style="max-width:1180px;margin:0 auto;padding:40px 16px 0">

        <div style="display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,2fr);gap:64px;padding-bottom:44px;border-bottom:1px solid var(--border-0)">

            <div>
                <a href="<?= SITE_URL ?>/index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:14px">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <rect width="32" height="32" rx="9" fill="url(#flg3)"/>
                        <path d="M8 11h16M8 15.5h11M8 21h13" stroke="white" stroke-width="2.2" stroke-linecap="round"/>
                        <defs><linearGradient id="flg3" x1="0" y1="0" x2="32" y2="32"><stop offset="0%" stop-color="var(--accent)"/><stop offset="100%" stop-color="var(--purple)"/></linearGradient></defs>
                    </svg>
                    <span style="font-family:'Clash Display',sans-serif;font-size:18px;font-weight:700;color:var(--ink-0);letter-spacing:-.02em"><?= SITE_NAME ?></span>
                </a>

                <p style="font-size:13.5px;color:var(--ink-3);line-height:1.72;margin-bottom:22px;max-width:280px">Modern, hızlı ve güvenli forum platformu. Topluluğumuza katılın, bilginizi paylaşın.</p>

                <div style="display:inline-flex;align-items:stretch;border:1px solid var(--border-1);border-radius:12px;background:var(--bg-surface);overflow:hidden">
                    <?php
                    $fStatItems = [
                        [$fStats['online_users'],  'Çevrimiçi', true],
                        [$fStats['total_users'],   'Üye',       false],
                        [$fStats['total_threads'], 'Konu',      false],
                        [$fStats['total_posts'],   'Mesaj',     false],
                    ];
                    foreach ($fStatItems as $i => [$val, $lbl, $online]):
                    ?>
                    <?php if ($i > 0): ?><div style="width:1px;background:var(--border-0);flex-shrink:0"></div><?php endif; ?>
                    <div style="padding:13px 18px;text-align:center">
                        <div style="font-family:'Clash Display',sans-serif;font-size:17px;font-weight:700;color:var(--ink-0);line-height:1"><?= formatNumber($val) ?></div>
                        <div style="display:flex;align-items:center;justify-content:center;gap:5px;font-size:10.5px;color:var(--ink-3);margin-top:5px;font-family:'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.06em">
                            <?php if ($online): ?><span style="width:6px;height:6px;background:var(--green);border-radius:50%;box-shadow:0 0 5px var(--green);display:inline-block;animation:onlinePulse 2.5s ease-in-out infinite"></span><?php endif; ?>
                            <?= $lbl ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:40px">

                <div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--ink-3);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border-0)">Kategoriler</div>
                    <?php foreach ($fCats as $cat): ?>
                    <a href="<?= SITE_URL ?>/forum.php?cat=<?= sanitize($cat['slug']) ?>" style="display:flex;align-items:center;gap:7px;font-size:13.5px;color:var(--ink-2);text-decoration:none;padding:4px 0;font-weight:500;transition:color .15s" onmouseover="this.style.color='var(--ink-0)';this.style.transform='translateX(3px)'" onmouseout="this.style.color='var(--ink-2)';this.style.transform='translateX(0)'">
                        <span style="width:6px;height:6px;border-radius:50%;flex-shrink:0;background:<?= sanitize($cat['color']) ?>"></span>
                        <?= sanitize($cat['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--ink-3);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border-0)">Forum</div>
                    <?php
                    $fLinks = [
                        ['Tüm Forumlar', SITE_URL.'/forum.php'],
                        ['Son Konular',  SITE_URL.'/forum.php'],
                    ];
                    if ($fUser) $fLinks[] = ['Konu Aç', 'javascript:openNewThreadModal()'];
                    foreach ($fLinks as [$lbl, $href]):
                    ?>
                    <a href="<?= $href ?>" style="display:block;font-size:13.5px;color:var(--ink-2);text-decoration:none;padding:4px 0;font-weight:500;transition:color .15s" onmouseover="this.style.color='var(--ink-0)'" onmouseout="this.style.color='var(--ink-2)'"><?= $lbl ?></a>
                    <?php endforeach; ?>
                </div>

                <div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--ink-3);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border-0)">Hesap</div>
                    <?php if ($fUser): ?>
                    <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($fUser['username']) ?>" style="display:block;font-size:13.5px;color:var(--ink-2);text-decoration:none;padding:4px 0;font-weight:500;transition:color .15s" onmouseover="this.style.color='var(--ink-0)'" onmouseout="this.style.color='var(--ink-2)'">Profilim</a>
                    <a href="<?= SITE_URL ?>/settings.php" style="display:block;font-size:13.5px;color:var(--ink-2);text-decoration:none;padding:4px 0;font-weight:500;transition:color .15s" onmouseover="this.style.color='var(--ink-0)'" onmouseout="this.style.color='var(--ink-2)'">Ayarlar</a>
                    <?php if (isAdmin()): ?>
                    <a href="<?= SITE_URL ?>/admin/index.php" style="display:block;font-size:13.5px;color:var(--amber);text-decoration:none;padding:4px 0;font-weight:500" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">Admin Panel</a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/logout.php" style="display:block;font-size:13.5px;color:var(--red);text-decoration:none;padding:4px 0;font-weight:500" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">Çıkış Yap</a>
                    <?php else: ?>
                    <a href="<?= SITE_URL ?>/login.php" style="display:block;font-size:13.5px;color:var(--ink-2);text-decoration:none;padding:4px 0;font-weight:500;transition:color .15s" onmouseover="this.style.color='var(--ink-0)'" onmouseout="this.style.color='var(--ink-2)'">Giriş Yap</a>
                    <a href="<?= SITE_URL ?>/register.php" style="display:block;font-size:13.5px;color:var(--ink-2);text-decoration:none;padding:4px 0;font-weight:500;transition:color .15s" onmouseover="this.style.color='var(--ink-0)'" onmouseout="this.style.color='var(--ink-2)'">Kayıt Ol</a>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 0 26px;flex-wrap:wrap;gap:12px">
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                <span style="font-size:13px;color:var(--ink-3)">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Tüm hakları saklıdır.</span>
                <span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--ink-4);background:var(--bg-raised);border:1px solid var(--border-0);padding:2px 8px;border-radius:999px">v<?= SITE_VERSION ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
                <span style="display:inline-flex;align-items:center;gap:5px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--ink-3);background:var(--bg-raised);border:1px solid var(--border-0);padding:4px 10px;border-radius:999px">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:.5"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    PHP <?= PHP_MAJOR_VERSION ?>.<?= PHP_MINOR_VERSION ?>
                </span>
                <span style="display:inline-flex;align-items:center;gap:5px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--ink-3);background:var(--bg-raised);border:1px solid var(--border-0);padding:4px 10px;border-radius:999px">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:.5"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                    MySQL
                </span>
            </div>
        </div>

    </div>
</footer>

</div>

<div id="toast-container" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px"></div>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
    window.SITE_URL     = '<?= SITE_URL ?>';
    window.isLoggedIn   = <?= isLoggedIn() ? 'true' : 'false' ?>;
    window.currentUserId = <?= isLoggedIn() ? (int)currentUser()['id'] : 'null' ?>;
</script>

</body>
</html>
