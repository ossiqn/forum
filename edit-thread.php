<?php
require_once 'includes/functions.php';
requireLogin();

$threadId    = (int)($_GET['id'] ?? 0);
$currentUser = currentUser();

$thread = db()->fetchOne("SELECT t.*, f.slug as forum_slug, f.name as forum_name, c.name as cat_name, c.slug as cat_slug FROM threads t JOIN forums f ON f.id = t.forum_id JOIN categories c ON c.id = f.category_id WHERE t.id = ? AND t.is_deleted = 0", [$threadId], 'i');

if (!$thread) { redirect(SITE_URL . '/index.php'); }
if ($thread['user_id'] !== $currentUser['id'] && !isModerator()) { redirect(SITE_URL . '/thread.php?id=' . $threadId); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Geçersiz istek'; }
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (strlen($title) < 5)   $errors[] = 'Başlık en az 5 karakter olmalıdır';
    if (strlen($title) > 255) $errors[] = 'Başlık en fazla 255 karakter';
    if (strlen($content) < 10) $errors[] = 'İçerik çok kısa';

    if (empty($errors)) {
        $newSlug = uniqueSlug('threads', generateSlug($title), $threadId);
        db()->query("UPDATE threads SET title = ?, content = ?, slug = ?, updated_at = NOW() WHERE id = ?", [$title, $content, $newSlug, $threadId], 'sssi');
        redirect(SITE_URL . '/thread.php?id=' . $threadId);
    }
}

$ad_code = setting('ad_thread_edit');

$pageTitle = 'Konuyu Düzenle';
require_once 'includes/header.php';
?>
<div style="max-width:1100px;margin:0 auto;padding:20px 16px 60px">

    <nav class="breadcrumb">
        <a href="<?= SITE_URL ?>/index.php">Ana Sayfa</a>
        <span class="breadcrumb-sep">/</span>
        <a href="<?= SITE_URL ?>/forum.php?cat=<?= sanitize($thread['cat_slug']) ?>"><?= sanitize($thread['cat_name']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <a href="<?= SITE_URL ?>/forum.php?forum=<?= sanitize($thread['forum_slug']) ?>"><?= sanitize($thread['forum_name']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <a href="<?= SITE_URL ?>/thread.php?id=<?= $threadId ?>"><?= sanitize(mb_substr($thread['title'], 0, 40)) ?>...</a>
        <span class="breadcrumb-sep">/</span>
        <span class="breadcrumb-current">Düzenle</span>
    </nav>

    <?php if (!empty($ad_code)): ?>
    <div style="margin-bottom:20px;text-align:center;overflow:hidden;border-radius:8px">
        <?= $ad_code ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;padding-bottom:16px;border-bottom:1px solid var(--border-0)">
            <h1 style="font-family:'Clash Display',sans-serif;font-size:18px;font-weight:700;color:var(--ink-0)">Konuyu Düzenle</h1>
            <span style="font-family:'JetBrains Mono',monospace;font-size:11.5px;color:var(--ink-3);background:var(--bg-raised);border:1px solid var(--border-0);padding:3px 10px;border-radius:999px"><?= sanitize($thread['forum_name']) ?></span>
        </div>

        <?php if ($errors): ?>
        <div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;background:var(--red-dim);border:1px solid rgba(255,95,87,.2)">
            <?php foreach ($errors as $e): ?><p style="color:var(--red);font-size:13.5px"><?= sanitize($e) ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label" style="display:block;margin-bottom:8px;font-weight:600;color:var(--ink-1)">Başlık</label>
                <input type="text" name="title" class="form-control" style="width:100%;padding:10px 14px;border:1px solid var(--border-0);border-radius:8px;background:var(--bg-raised);color:var(--ink-0);font-size:14px" value="<?= sanitize($_POST['title'] ?? $thread['title']) ?>" required maxlength="255" autofocus>
            </div>
            <div class="form-group" style="margin-bottom:20px">
                <label class="form-label" style="display:block;margin-bottom:8px;font-weight:600;color:var(--ink-1)">İçerik</label>
                <div class="editor-toolbar" style="display:flex;gap:4px;padding:8px;background:var(--bg-raised);border:1px solid var(--border-0);border-bottom:none;border-radius:8px 8px 0 0">
                    <button type="button" class="editor-btn" onclick="editorFormatMain('bold')" style="padding:6px 12px;background:transparent;border:none;cursor:pointer;color:var(--ink-1);border-radius:4px"><b>B</b></button>
                    <button type="button" class="editor-btn" onclick="editorFormatMain('italic')" style="padding:6px 12px;background:transparent;border:none;cursor:pointer;color:var(--ink-1);border-radius:4px"><i>I</i></button>
                    <button type="button" class="editor-btn" onclick="editorFormatMain('underline')" style="padding:6px 12px;background:transparent;border:none;cursor:pointer;color:var(--ink-1);border-radius:4px"><u>U</u></button>
                    <button type="button" class="editor-btn" onclick="editorFormatMain('code')" style="font-family:monospace;padding:6px 12px;background:transparent;border:none;cursor:pointer;color:var(--ink-1);border-radius:4px">&lt;/&gt;</button>
                    <button type="button" class="editor-btn" onclick="editorFormatMain('quote')" style="padding:6px 12px;background:transparent;border:none;cursor:pointer;color:var(--ink-1);border-radius:4px">❝</button>
                </div>
                <textarea name="content" id="thread-content" class="editor-textarea" style="width:100%;min-height:250px;padding:14px;border:1px solid var(--border-0);border-radius:0 0 8px 8px;background:var(--bg-base);color:var(--ink-0);font-size:14px;line-height:1.6;resize:vertical" required><?= htmlspecialchars($_POST['content'] ?? $thread['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div style="display:flex;align-items:center;gap:10px;margin-top:6px">
                <button type="submit" class="btn-primary" style="display:flex;align-items:center;gap:6px;padding:10px 20px;background:var(--blue);color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Değişiklikleri Kaydet
                </button>
                <a href="<?= SITE_URL ?>/thread.php?id=<?= $threadId ?>" class="btn-ghost" style="padding:10px 20px;color:var(--ink-2);text-decoration:none;font-weight:500">İptal</a>
                <?php if (isModerator()): ?>
                <button type="button" onclick="confirmDelete('Konuyu silmek istediğinizden emin misiniz?', '<?= SITE_URL ?>/admin/thread-delete.php?id=<?= $threadId ?>&token=<?= generateCsrfToken() ?>')" class="btn-danger" style="display:flex;align-items:center;gap:6px;padding:10px 16px;background:var(--red-dim);color:var(--red);border:1px solid rgba(255,95,87,.2);border-radius:8px;font-weight:600;cursor:pointer;margin-left:auto">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                    Konuyu Sil
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
function editorFormatMain(tag) {
    const ta = document.getElementById('thread-content');
    const s = ta.selectionStart, e = ta.selectionEnd, sel = ta.value.substring(s, e);
    const t = {bold:['[b]','[/b]'],italic:['[i]','[/i]'],underline:['[u]','[/u]'],code:['[code]','[/code]'],quote:['[quote]','[/quote]']}[tag];
    if (!t) return;
    ta.value = ta.value.substring(0, s) + t[0] + sel + t[1] + ta.value.substring(e);
    ta.selectionStart = s + t[0].length; ta.selectionEnd = e + t[0].length; ta.focus();
}
</script>

<?php require_once 'includes/footer.php'; ?>