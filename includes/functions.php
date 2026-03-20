<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_set_cookie_params(SESSION_LIFETIME);
    session_start();
}

function currentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user === null) {
        $user = db()->fetchOne("SELECT * FROM users WHERE id = ? AND is_banned = 0", [$_SESSION['user_id']], 'i');
    }
    return $user;
}

function isLoggedIn() {
    return currentUser() !== null;
}

function isAdmin() {
    $user = currentUser();
    return $user && $user['role'] === 'admin';
}

function isModerator() {
    $user = currentUser();
    return $user && in_array($user['role'], ['admin', 'moderator']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect(SITE_URL . '/index.php');
    }
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateSlug($text) {
    $tr = ['ş','Ş','ı','İ','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç'];
    $en = ['s','s','i','i','g','g','u','u','o','o','c','c'];
    $text = str_replace($tr, $en, $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function uniqueSlug($table, $slug, $excludeId = null) {
    $original = $slug;
    $counter = 1;
    while (true) {
        $sql = "SELECT id FROM $table WHERE slug = ?";
        $params = [$slug];
        $types = 's';
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
            $types .= 'i';
        }
        $exists = db()->fetchOne($sql, $params, $types);
        if (!$exists) break;
        $slug = $original . '-' . $counter++;
    }
    return $slug;
}

function timeAgo($datetime) {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);
    if ($diff->y > 0) return $diff->y . ' yıl önce';
    if ($diff->m > 0) return $diff->m . ' ay önce';
    if ($diff->d > 0) return $diff->d . ' gün önce';
    if ($diff->h > 0) return $diff->h . ' saat önce';
    if ($diff->i > 0) return $diff->i . ' dakika önce';
    return 'Az önce';
}

function formatDate($datetime, $format = 'd.m.Y H:i') {
    return date($format, strtotime($datetime));
}

function formatNumber($number) {
    if ($number >= 1000000) return round($number / 1000000, 1) . 'M';
    if ($number >= 1000) return round($number / 1000, 1) . 'K';
    return $number;
}

function getAvatar($user, $size = 'medium') {
    if (!empty($user['avatar']) && file_exists(UPLOADS_PATH . 'avatars/' . $user['avatar'])) {
        return UPLOADS_URL . 'avatars/' . $user['avatar'];
    }
    return generateAvatarUrl($user['username'] ?? 'U');
}

function generateAvatarUrl($username) {
    $palettes = [
        ['#1a1a2e','#e2e8f0'],['#0d1b2a','#f1f5f9'],['#1e293b','#f8fafc'],
        ['#0f172a','#e2e8f0'],['#18181b','#fafafa'],['#1c1917','#f5f5f4'],
        ['#0a0a0a','#ffffff'],['#171717','#f5f5f5'],['#1a1a1a','#eeeeee'],
        ['#2d2d2d','#f0f0f0'],['#111827','#f9fafb'],['#0c0c0c','#e5e7eb'],
    ];
    $idx = abs(crc32($username)) % count($palettes);
    $bg  = $palettes[$idx][0];
    $fg  = $palettes[$idx][1];
    $letter = strtoupper(mb_substr($username, 0, 1));
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">'
         . '<rect width="80" height="80" fill="' . $bg . '"/>'
         . '<text x="40" y="40" font-family="Arial,sans-serif" font-size="34" font-weight="700" fill="' . $fg . '" text-anchor="middle" dominant-baseline="central">' . htmlspecialchars($letter) . '</text>'
         . '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function getCover($user) {
    if (!empty($user['cover_photo']) && file_exists(UPLOADS_PATH . 'covers/' . $user['cover_photo'])) {
        return UPLOADS_URL . 'covers/' . $user['cover_photo'];
    }
    return DEFAULT_COVER;
}

function uploadImage($file, $dir, $prefix = '') {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_AVATAR_SIZE) return false;
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) return false;
    
    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid() . '.' . $ext;
    $destination = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $destination)) return false;
    
    return $filename;
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

function getRoleBadge($role) {
    $badges = [
        'admin' => ['label' => 'Admin', 'class' => 'role-admin'],
        'moderator' => ['label' => 'Moderator', 'class' => 'role-moderator'],
        'member' => ['label' => 'Üye', 'class' => 'role-member'],
    ];
    return $badges[$role] ?? $badges['member'];
}

function getForumStats() {
    $stats = db()->fetchOne("SELECT 
        (SELECT COUNT(*) FROM users WHERE is_banned = 0) as total_users,
        (SELECT COUNT(*) FROM threads WHERE is_deleted = 0) as total_threads,
        (SELECT COUNT(*) FROM posts WHERE is_deleted = 0) as total_posts,
        (SELECT COUNT(*) FROM users WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)) as online_users
    ");
    return $stats;
}

function getCategories() {
    return db()->fetchAll("SELECT c.*, 
        (SELECT COUNT(*) FROM forums f WHERE f.category_id = c.id AND f.is_active = 1) as forum_count
        FROM categories c WHERE c.is_active = 1 ORDER BY c.display_order ASC");
}

function getForumsByCategory($categoryId) {
    return db()->fetchAll("SELECT f.*,
        t.title as last_thread_title, t.slug as last_thread_slug,
        t.id as last_thread_id,
        lu.username as last_post_user, lu.avatar as last_post_avatar
        FROM forums f
        LEFT JOIN threads t ON t.id = f.last_post_id
        LEFT JOIN posts lp ON lp.id = t.last_post_id
        LEFT JOIN users lu ON lu.id = lp.user_id
        WHERE f.category_id = ? AND f.is_active = 1
        ORDER BY f.display_order ASC", [$categoryId], 'i');
}

function getLatestThreads($forumId, $limit = 3) {
    return db()->fetchAll("SELECT t.*, u.username, u.avatar, u.role,
        f.name as forum_name, f.slug as forum_slug
        FROM threads t
        JOIN users u ON u.id = t.user_id
        JOIN forums f ON f.id = t.forum_id
        WHERE t.forum_id = ? AND t.is_deleted = 0
        ORDER BY t.last_post_at DESC LIMIT ?", [$forumId, $limit], 'ii');
}

function getNotifications($userId, $limit = 10) {
    return db()->fetchAll("SELECT n.*, u.username as from_username, u.avatar as from_avatar
        FROM notifications n
        LEFT JOIN users u ON u.id = n.from_user_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC LIMIT ?", [$userId, $limit], 'ii');
}

function getUnreadNotificationCount($userId) {
    $result = db()->fetchOne("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0", [$userId], 'i');
    return $result['count'] ?? 0;
}

function markNotificationsRead($userId) {
    db()->query("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$userId], 'i');
}

function createNotification($userId, $fromUserId, $type, $refId, $refType, $message) {
    db()->query("INSERT INTO notifications (user_id, from_user_id, type, reference_id, reference_type, message) VALUES (?,?,?,?,?,?)",
        [$userId, $fromUserId, $type, $refId, $refType, $message], 'iiisss');
}

function paginateQuery($sql, $params, $types, $page, $perPage) {
    $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as sub";
    $total = db()->fetchOne($countSql, $params, $types);
    $totalItems = $total['total'] ?? 0;
    $totalPages = ceil($totalItems / $perPage);
    $page = max(1, min($page, max(1, $totalPages)));
    $offset = ($page - 1) * $perPage;
    $paginatedSql = $sql . " LIMIT ? OFFSET ?";
    $paginatedParams = array_merge($params, [$perPage, $offset]);
    $paginatedTypes = $types . 'ii';
    $items = db()->fetchAll($paginatedSql, $paginatedParams, $paginatedTypes);
    return [
        'items' => $items,
        'total' => $totalItems,
        'pages' => $totalPages,
        'current' => $page,
        'per_page' => $perPage,
    ];
}

function updateLastSeen($userId) {
    db()->query("UPDATE users SET last_seen = NOW() WHERE id = ?", [$userId], 'i');
}

if (isLoggedIn()) {
    updateLastSeen($_SESSION['user_id']);
}

function getActiveAds($position) {
    return db()->fetchAll("SELECT * FROM advertisements WHERE position = ? AND is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY RAND() LIMIT 1", [$position], 's');
}

function renderAd($position, $class = '') {
    $ads = getActiveAds($position);
    if (empty($ads)) return '';
    $ad = $ads[0];
    db()->query("UPDATE advertisements SET impression_count = impression_count + 1 WHERE id = ?", [$ad['id']], 'i');
    $wrapper = '<div class="ad-slot ' . htmlspecialchars($class) . '" data-ad-id="' . (int)$ad['id'] . '">';
    $wrapper .= '<span class="ad-label">Reklam</span>';
    if ($ad['type'] === 'code' && $ad['html_code']) {
        $wrapper .= $ad['html_code'];
    } elseif ($ad['type'] === 'image' && $ad['image_url']) {
        $link = $ad['link_url'] ? 'href="' . htmlspecialchars($ad['link_url']) . '" target="_blank" rel="noopener nofollow"' : '';
        $wrapper .= '<a ' . $link . ' onclick="trackAdClick(' . (int)$ad['id'] . ')"><img src="' . htmlspecialchars($ad['image_url']) . '" alt="' . htmlspecialchars($ad['alt_text'] ?? '') . '" style="max-width:100%;border-radius:8px"></a>';
    } elseif ($ad['type'] === 'text') {
        $wrapper .= '<div class="ad-text">' . htmlspecialchars($ad['html_code'] ?? '') . '</div>';
    }
    $wrapper .= '</div>';
    return $wrapper;
}

function getSiteSetting($key, $default = '') {
    $row = db()->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = ?", [$key], 's');
    return $row ? $row['setting_value'] : $default;
}

function setSiteSetting($key, $value) {
    db()->query("INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = ?", [$key, $value, $value], 'sss');
}

function getUserTheme() {
    if (isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], ['dark','light'])) {
        return $_COOKIE['theme'];
    }
    return 'light';
}

function getUserBadges($userId) {
    return db()->fetchAll("SELECT b.* FROM user_badges ub JOIN badges b ON b.id = ub.badge_id WHERE ub.user_id = ? AND b.is_active = 1 ORDER BY ub.awarded_at ASC", [$userId], 'i');
}

function checkAndAwardBadges($userId) {
    $user = db()->fetchOne("SELECT post_count, thread_count FROM users WHERE id = ?", [$userId], 'i');
    if (!$user) return;
    $autoBadges = db()->fetchAll("SELECT * FROM badges WHERE type = 'auto' AND is_active = 1");
    foreach ($autoBadges as $badge) {
        $val = ($badge['auto_condition'] === 'post_count') ? $user['post_count'] : $user['thread_count'];
        if ($val >= $badge['auto_value']) {
            db()->query("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?,?)", [$userId, $badge['id']], 'ii');
        }
    }
}

function awardBadge($userId, $badgeSlug, $awardedBy = null) {
    $badge = db()->fetchOne("SELECT id FROM badges WHERE slug = ?", [$badgeSlug], 's');
    if (!$badge) return false;
    db()->query("INSERT IGNORE INTO user_badges (user_id, badge_id, awarded_by) VALUES (?,?,?)", [$userId, $badge['id'], $awardedBy], 'iii');
    return true;
}

function renderBadges($userId, $max = 5) {
    $badges = getUserBadges($userId);
    if (empty($badges)) return '';
    $html = '<div class="user-badges">';
    foreach (array_slice($badges, 0, $max) as $b) {
        $html .= '<span class="user-badge" style="background:' . htmlspecialchars($b['bg_color']) . ';color:' . htmlspecialchars($b['color']) . ';border-color:' . htmlspecialchars($b['color']) . '33" title="' . htmlspecialchars($b['name']) . ': ' . htmlspecialchars($b['description']) . '">' . $b['icon'] . ' ' . htmlspecialchars($b['name']) . '</span>';
    }
    $html .= '</div>';
    return $html;
}

function getDmConversations($userId) {
    return db()->fetchAll("SELECT dc.*,
        CASE WHEN dc.user1_id = ? THEN u2.id ELSE u1.id END as other_user_id,
        CASE WHEN dc.user1_id = ? THEN u2.username ELSE u1.username END as other_username,
        CASE WHEN dc.user1_id = ? THEN u2.avatar ELSE u1.avatar END as other_avatar,
        CASE WHEN dc.user1_id = ? THEN u2.role ELSE u1.role END as other_role,
        dm.content as last_content, dm.sender_id as last_sender_id, dm.is_read as last_is_read
        FROM dm_conversations dc
        JOIN users u1 ON u1.id = dc.user1_id
        JOIN users u2 ON u2.id = dc.user2_id
        LEFT JOIN dm_messages dm ON dm.id = dc.last_message_id
        WHERE (dc.user1_id = ? AND dc.user1_deleted = 0) OR (dc.user2_id = ? AND dc.user2_deleted = 0)
        ORDER BY dc.last_message_at DESC",
        [$userId, $userId, $userId, $userId, $userId, $userId], 'iiiiii');
}

function getOrCreateConversation($user1Id, $user2Id) {
    $min = min($user1Id, $user2Id);
    $max = max($user1Id, $user2Id);
    $convo = db()->fetchOne("SELECT * FROM dm_conversations WHERE user1_id = ? AND user2_id = ?", [$min, $max], 'ii');
    if (!$convo) {
        $id = db()->insert("INSERT INTO dm_conversations (user1_id, user2_id) VALUES (?,?)", [$min, $max], 'ii');
        return db()->fetchOne("SELECT * FROM dm_conversations WHERE id = ?", [$id], 'i');
    }
    return $convo;
}

function getUnreadDmCount($userId) {
    $r = db()->fetchOne("SELECT COUNT(*) as c FROM dm_messages dm JOIN dm_conversations dc ON dc.id = dm.conversation_id WHERE dm.is_read = 0 AND dm.sender_id != ? AND (dc.user1_id = ? OR dc.user2_id = ?)", [$userId, $userId, $userId], 'iii');
    return (int)($r['c'] ?? 0);
}

function isFollowingThread($threadId, $userId) {
    return (bool)db()->fetchOne("SELECT id FROM thread_follows WHERE thread_id = ? AND user_id = ?", [$threadId, $userId], 'ii');
}

function getOnlineUsers() {
    $minutes = (int)(getSiteSetting('online_threshold_minutes', 15));
    return db()->fetchAll("SELECT id, username, avatar, role FROM users WHERE last_seen >= DATE_SUB(NOW(), INTERVAL ? MINUTE) AND is_banned = 0 ORDER BY last_seen DESC LIMIT 50", [$minutes], 'i');
}

function uploadMedia($file, $userId) {
    $maxMb = (int)(getSiteSetting('max_upload_mb', 5));
    if ($file['error'] !== UPLOAD_ERR_OK) return ['error' => 'Yükleme hatası'];
    if ($file['size'] > $maxMb * 1024 * 1024) return ['error' => "Dosya max {$maxMb}MB olabilir"];
    
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed)) return ['error' => 'Sadece JPG, PNG, GIF, WEBP yüklenebilir'];
    
    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'media_' . uniqid() . '.' . strtolower($ext);
    $destination = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $destination)) return ['error' => 'Dosya kaydedilemedi'];
    
    $url = UPLOADS_URL . 'media/' . $filename;
    db()->query("INSERT INTO media_uploads (user_id, filename, original_name, file_size, mime_type, url) VALUES (?,?,?,?,?,?)",
        [$userId, $filename, $file['name'], $file['size'], $mime, $url], 'ississ');
    
    return ['url' => $url, 'filename' => $filename];
}

function getProfilePosts($profileUserId) {
    return db()->fetchAll("SELECT pp.*, u.username as author_username, u.avatar as author_avatar, u.role as author_role FROM profile_posts pp JOIN users u ON u.id = pp.author_id WHERE pp.profile_user_id = ? AND pp.is_deleted = 0 ORDER BY pp.created_at DESC LIMIT 20", [$profileUserId], 'i');
}
?>