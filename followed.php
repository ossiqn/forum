<?php
require_once 'includes/functions.php';
requireLogin();
$currentUser = currentUser();

$followed = db()->fetchAll("SELECT t.*, u.username, f.name as forum_name, f.slug as forum_slug FROM thread_follows tf JOIN threads t ON t.id = tf.thread_id JOIN users u ON u.id = t.user_id JOIN forums f ON f.id = t.forum_id WHERE tf.user_id = ? AND t.is_deleted = 0 ORDER BY t.last_post_at DESC", [$currentUser['id']], 'i');

$pageTitle = 'Takip Ettiğim Konular';
require_once 'includes/header.php';
?>
<div style="max-width:1100px;margin:0 auto;padding:20px 16px 60px">
    <div class="section-header mb-6">
        <h1 class="section-title">Takip Ettiğim Konular</h1>
        <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--ink-3)"><?= count($followed) ?> konu</span>
    </div>

    <?php if (empty($followed)): ?>
    <div class="empty-state card">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <h3>Henüz takip ettiğiniz konu yok</h3>
        <p style="font-size:13.5px;color:var(--ink-3)">Konularda "Takip Et" butonuna tıklayarak takip edebilirsiniz</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:6px">
        <?php foreach ($followed as $t): ?>
        <div style="display:flex;align-items:center;gap:14px;padding:14px 18px;background:var(--bg-surface);border:1px solid var(--border-1);border-radius:12px;transition:border-color .18s" onmouseover="this.style.borderColor='var(--accent-border)'" onmouseout="this.style.borderColor='var(--border-1)'">
            <div style="flex:1;min-width:0">
                <a href="<?= SITE_URL ?>/thread.php?id=<?= $t['id'] ?>" style="font-size:14px;font-weight:600;color:var(--ink-1);text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--ink-1)'"><?= sanitize($t['title']) ?></a>
                <div style="font-size:12px;color:var(--ink-3);margin-top:3px;font-family:'JetBrains Mono',monospace;display:flex;gap:12px;flex-wrap:wrap">
                    <span><?= sanitize($t['username']) ?></span>
                    <span><?= sanitize($t['forum_name']) ?></span>
                    <span>Son: <?= timeAgo($t['last_post_at']) ?></span>
                </div>
            </div>
            <div style="display:flex;gap:14px;flex-shrink:0;text-align:center">
                <div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:600;color:var(--ink-2)"><?= $t['reply_count'] ?></div>
                    <div style="font-size:10px;color:var(--ink-3);text-transform:uppercase;letter-spacing:.05em">Cevap</div>
                </div>
            </div>
            <button onclick="unfollowThread(<?= $t['id'] ?>, this)" style="padding:6px 12px;border-radius:7px;background:var(--red-dim);border:1px solid rgba(255,95,87,.2);color:var(--red);font-size:12px;cursor:pointer;font-family:'Satoshi',sans-serif;font-weight:500;transition:all .15s;flex-shrink:0" onmouseover="this.style.background='rgba(255,95,87,.2)'" onmouseout="this.style.background='var(--red-dim)'">Takibi Bırak</button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
async function unfollowThread(id, btn) {
    try {
        const r = await fetch(`${window.SITE_URL}/api/follow.php`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({thread_id:id})});
        const d = await r.json();
        if (d.success && !d.following) {
            const row = btn.closest('[style*="display:flex"]');
            gsap.to(row, {opacity:0,x:-20,duration:.25,onComplete:()=>row.remove()});
            showToast('Takip bırakıldı','success',2000);
        }
    } catch { showToast('Hata','error'); }
}
</script>

<?php require_once 'includes/footer.php'; ?>
