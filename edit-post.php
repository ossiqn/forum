<?php
require_once 'includes/functions.php';
requireLogin();

$postId = (int)($_GET['id'] ?? 0);
$currentUser = currentUser();

$post = db()->fetchOne("SELECT p.*, t.id as thread_id, t.forum_id FROM posts p JOIN threads t ON t.id = p.thread_id WHERE p.id = ? AND p.is_deleted = 0", [$postId], 'i');

if (!$post || ($post['user_id'] !== $currentUser['id'] && !isModerator())) {
    redirect(SITE_URL . '/index.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Geçersiz istek'; }
    $content = trim($_POST['content'] ?? '');
    if (strlen($content) < 5) $errors[] = 'İçerik çok kısa';
    if (empty($errors)) {
        db()->query("UPDATE posts SET content = ?, is_edited = 1, edited_at = NOW() WHERE id = ?", [$content, $postId], 'si');
        redirect(SITE_URL . '/thread.php?id=' . $post['thread_id'] . '#post-' . $postId);
    }
}

$pageTitle = 'Mesajı Düzenle';
require_once 'includes/header.php';
?>
<div style="max-width:1100px;margin:0 auto;padding:20px 16px 60px">
    <div class="card">
        <h1 class="text-xl font-bold mb-6">Mesajı Düzenle</h1>
        <?php if ($errors): ?>
        <div class="mb-4 p-3 bg-red-900/20 border border-red-700/30 rounded-lg">
            <?php foreach ($errors as $e): ?><p class="text-red-400 text-sm"><?= sanitize($e) ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="editor-toolbar">
                <button type="button" class="editor-btn" onclick="editorFormat('bold')"><b>B</b></button>
                <button type="button" class="editor-btn" onclick="editorFormat('italic')"><i>I</i></button>
                <button type="button" class="editor-btn" onclick="editorFormat('underline')"><u>U</u></button>
                <button type="button" class="editor-btn" onclick="editorFormat('code')" style="font-family:monospace">&lt;/&gt;</button>
            </div>
            <textarea name="content" id="reply-textarea" class="editor-textarea" style="min-height:180px" required><?= htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="flex gap-3 mt-4">
                <button type="submit" class="btn-primary">Kaydet</button>
                <a href="<?= SITE_URL ?>/thread.php?id=<?= $post['thread_id'] ?>" class="btn-ghost">İptal</a>
            </div>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
