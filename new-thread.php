<?php
require_once 'includes/functions.php';
requireLogin();

$forumSlug = $_GET['forum'] ?? '';
$forum = db()->fetchOne("SELECT f.*, c.name as cat_name, c.slug as cat_slug FROM forums f JOIN categories c ON c.id = f.category_id WHERE f.slug = ?", [$forumSlug], 's');
if (!$forum) { header('Location: ' . SITE_URL . '/forum.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) { $errors[] = 'Geçersiz istek'; }
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (strlen($title) < 5) $errors[] = 'Başlık en az 5 karakter olmalıdır';
    if (strlen($title) > 255) $errors[] = 'Başlık en fazla 255 karakter olabilir';
    if (strlen($content) < 10) $errors[] = 'İçerik en az 10 karakter olmalıdır';
    
    if (empty($errors)) {
        $currentUser = currentUser();
        $slug = uniqueSlug('threads', generateSlug($title));
        $threadId = db()->insert("INSERT INTO threads (forum_id, user_id, title, slug, content) VALUES (?,?,?,?,?)",
            [$forum['id'], $currentUser['id'], $title, $slug, $content], 'iisss');
            
        db()->query("UPDATE forums SET thread_count = thread_count + 1, post_count = post_count + 1, last_post_id = ? WHERE id = ?", [$threadId, $forum['id']], 'ii');
        db()->query("UPDATE users SET thread_count = thread_count + 1 WHERE id = ?", [$currentUser['id']], 'i');
        
        header('Location: ' . SITE_URL . '/thread.php?id=' . $threadId);
        exit;
    }
}

$ad_code = '';
$pageTitle = 'Yeni Konu - ' . $forum['name'];
require_once 'includes/header.php';
?>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
#drop-zone.dragover { border-color: var(--accent) !important; background: rgba(192, 160, 128, 0.05) !important; }
</style>

<div style="max-width:1100px;margin:0 auto;padding:20px 16px 60px">
    <nav class="breadcrumb" style="margin-bottom:20px">
        <a href="<?= SITE_URL ?>/index.php">Ana Sayfa</a>
        <span class="breadcrumb-sep">/</span>
        <a href="<?= SITE_URL ?>/forum.php?cat=<?= sanitize($forum['cat_slug']) ?>"><?= sanitize($forum['cat_name']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <a href="<?= SITE_URL ?>/forum.php?forum=<?= sanitize($forum['slug']) ?>"><?= sanitize($forum['name']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <span class="breadcrumb-current">Yeni Konu</span>
    </nav>

    <?php if (!empty($ad_code)): ?>
    <div style="margin-bottom:20px;text-align:center;overflow:hidden;border-radius:8px">
        <?= $ad_code ?>
    </div>
    <?php endif; ?>

    <div class="card" style="background:var(--bg-surface); border:1px solid var(--border-1); border-radius:12px; padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;padding-bottom:16px;border-bottom:1px solid var(--border-0)">
            <h1 style="font-family:'Clash Display',sans-serif;font-size:18px;font-weight:700;color:var(--ink-0);margin:0;">Yeni Konu: <?= sanitize($forum['name']) ?></h1>
        </div>

        <?php if ($errors): ?>
        <div style="margin-bottom:16px;padding:12px 16px;border-radius:10px;background:var(--red-dim);border:1px solid rgba(255,95,87,.2)">
            <?php foreach ($errors as $e): ?><p style="color:var(--red);font-size:13.5px;margin:0;"><?= sanitize($e) ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label" style="display:block;margin-bottom:8px;font-weight:600;color:var(--ink-1);font-size:14px;">Konu Başlığı</label>
                <input type="text" name="title" style="width:100%;padding:12px 16px;border:1px solid var(--border-0);border-radius:8px;background:var(--bg-base);color:var(--ink-0);font-size:14px;outline:none;" placeholder="Açıklayıcı bir başlık yazın..." value="<?= sanitize($_POST['title'] ?? '') ?>" required maxlength="255" autofocus>
            </div>
            <div class="form-group" style="margin-bottom:20px">
                <label class="form-label" style="display:block;margin-bottom:8px;font-weight:600;color:var(--ink-1);font-size:14px;">İçerik</label>
                <div style="display:flex;gap:4px;padding:10px;background:var(--bg-raised);border:1px solid var(--border-0);border-bottom:none;border-radius:8px 8px 0 0;overflow-x:auto;">
                    <button type="button" onclick="editorFormatMain('bold')" style="padding:6px 12px;background:none;border:none;cursor:pointer;color:var(--ink-1);border-radius:4px"><b>B</b></button>
                    <button type="button" onclick="editorFormatMain('italic')" style="padding:6px 12px;background:none;border:none;cursor:pointer;color:var(--ink-1);border-radius:4px"><i>I</i></button>
                    <button type="button" onclick="editorFormatMain('underline')" style="padding:6px 12px;background:none;border:none;cursor:pointer;color:var(--ink-1);border-radius:4px"><u>U</u></button>
                    <button type="button" onclick="editorFormatMain('code')" style="font-family:monospace;padding:6px 12px;background:none;border:none;cursor:pointer;color:var(--ink-1);border-radius:4px">&lt;/&gt;</button>
                    <button type="button" onclick="editorFormatMain('quote')" style="padding:6px 12px;background:none;border:none;cursor:pointer;color:var(--ink-1);border-radius:4px">❝</button>
                    <button type="button" onclick="editorFormatMain('spoiler')" style="padding:6px 12px;background:none;border:none;cursor:pointer;color:var(--ink-1);border-radius:4px">👁</button>
                    <button type="button" onclick="openImageModal('thread-content')" title="Resim Yükle" style="padding:6px 12px;background:none;border:none;cursor:pointer;color:var(--ink-1);border-radius:4px;display:inline-flex;align-items:center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </button>
                </div>
                <textarea name="content" id="thread-content" style="width:100%;min-height:250px;padding:16px;border:1px solid var(--border-0);border-radius:0 0 8px 8px;background:var(--bg-base);color:var(--ink-0);font-size:14px;line-height:1.6;resize:vertical;outline:none;" placeholder="Konu içeriğini buraya yazın..." required><?= htmlspecialchars($_POST['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div style="display:flex;align-items:center;gap:10px;margin-top:6px">
                <button type="submit" style="display:flex;align-items:center;gap:8px;padding:12px 28px;background:var(--accent);color:#ffffff;border:none;border-radius:8px;font-weight:600;font-size:14px;cursor:pointer;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Konuyu Aç
                </button>
                <a href="<?= SITE_URL ?>/forum.php?forum=<?= sanitize($forum['slug']) ?>" style="padding:12px 28px;background:var(--bg-raised);border:1px solid var(--border-0);color:var(--ink-1);border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">İptal</a>
            </div>
        </form>
    </div>
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
                <button type="button" onclick="insertImageFromUrlInput()" style="padding:10px 16px; border-radius:8px; font-size:13px; border:none; background:var(--accent); color:#ffffff; cursor:pointer; font-weight:600;">Ekle</button>
            </div>
        </div>
    </div>
</div>

<script>
function editorFormatMain(tag) {
    const ta = document.getElementById('thread-content');
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

let currentTextareaId = 'thread-content';

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