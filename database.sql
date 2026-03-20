CREATE DATABASE IF NOT EXISTS nexusforum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nexusforum;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    cover_photo VARCHAR(255) DEFAULT NULL,
    role ENUM('member','moderator','admin') DEFAULT 'member',
    signature TEXT DEFAULT NULL,
    about TEXT DEFAULT NULL,
    post_count INT DEFAULT 0,
    thread_count INT DEFAULT 0,
    is_banned TINYINT(1) DEFAULT 0,
    ban_reason VARCHAR(255) DEFAULT NULL,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'folder',
    color VARCHAR(20) DEFAULT '#3b82f6',
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE forums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    display_order INT DEFAULT 0,
    thread_count INT DEFAULT 0,
    post_count INT DEFAULT 0,
    last_post_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    forum_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT NOT NULL,
    view_count INT DEFAULT 0,
    reply_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    is_sticky TINYINT(1) DEFAULT 0,
    is_locked TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    last_post_id INT DEFAULT NULL,
    last_post_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (forum_id) REFERENCES forums(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    content LONGTEXT NOT NULL,
    quote_post_id INT DEFAULT NULL,
    like_count INT DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    is_edited TINYINT(1) DEFAULT 0,
    edited_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE thread_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_thread_like (thread_id, user_id),
    FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_user_id INT DEFAULT NULL,
    type ENUM('reply','like','mention','system') NOT NULL,
    reference_id INT DEFAULT NULL,
    reference_type VARCHAR(20) DEFAULT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_type ENUM('thread','post','user') NOT NULL,
    reported_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    details TEXT DEFAULT NULL,
    status ENUM('pending','reviewed','dismissed') DEFAULT 'pending',
    reviewed_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE `thread_follows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `thread_user_unique` (`thread_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `description` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `awarded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_badge_unique` (`user_id`,`badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `profile_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_user_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `advertisements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `position` varchar(50) NOT NULL,
  `type` enum('code','image','text') NOT NULL DEFAULT 'code',
  `image_url` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `html_code` text DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, email, password, role, about) VALUES
('Admin', 'admin@nexusforum.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMZJaaaSwm.YG0u9XU6QLCZ.zO', 'admin', 'Forum yöneticisi'),
('Moderator', 'mod@nexusforum.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMZJaaaSwm.YG0u9XU6QLCZ.zO', 'moderator', 'Forum moderatörü'),
('TestUser', 'test@nexusforum.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMZJaaaSwm.YG0u9XU6QLCZ.zO', 'member', 'Test kullanıcısı');

INSERT INTO categories (name, slug, description, icon, color, display_order) VALUES
('Game Hacking', 'game-hacking', 'Oyun hileleri, cheat engine, trainer ve daha fazlası', 'gamepad-2', '#3b82f6', 1),
('Programming', 'programming', 'Yazılım geliştirme, kod paylaşımı ve teknik tartışmalar', 'code-2', '#8b5cf6', 2),
('Marketplace', 'marketplace', 'Alım-satım, servis ve ürün paylaşımları', 'shopping-bag', '#10b981', 3),
('General', 'general', 'Genel konular ve sohbet', 'message-circle', '#f59e0b', 4);

INSERT INTO forums (category_id, name, slug, description, display_order) VALUES
(1, 'Cheat Engine', 'cheat-engine', 'Cheat Engine kullanımı ve script paylaşımları', 1),
(1, 'External Hacks', 'external-hacks', 'Harici hack araçları ve geliştirme', 2),
(1, 'Internal Hacks', 'internal-hacks', 'DLL injection ve iç hack geliştirme', 3),
(2, 'C / C++', 'cpp', 'C ve C++ programlama dili', 1),
(2, 'Python', 'python', 'Python geliştirme ve scriptler', 2),
(2, 'Web Development', 'web-dev', 'Web teknolojileri ve framework\'ler', 3),
(3, 'Selling', 'selling', 'Ürün ve servis satışları', 1),
(3, 'Buying', 'buying', 'Alım talepleri', 2),
(4, 'Introductions', 'introductions', 'Kendinizi tanıtın', 1),
(4, 'Off-Topic', 'off-topic', 'Konu dışı sohbet', 2);

INSERT INTO threads (forum_id, user_id, title, slug, content, view_count, reply_count, is_sticky) VALUES
(1, 1, 'Cheat Engine Başlangıç Rehberi', 'cheat-engine-baslangic-rehberi', '<p>Bu rehberde Cheat Engine kullanımının temellerini öğreneceksiniz...</p>', 1520, 23, 1),
(1, 2, 'Memory Scanning Teknikleri', 'memory-scanning-teknikleri', '<p>Memory scanning için gelişmiş teknikler...</p>', 890, 12, 0),
(4, 3, 'Python ile Oyun Botu Yazımı', 'python-oyun-botu', '<p>PyAutoGUI ve OpenCV kullanarak basit bir oyun botu...</p>', 2340, 45, 0),
(2, 1, 'C++ ile DLL Injection', 'cpp-dll-injection', '<p>Windows API kullanarak DLL injection rehberi...</p>', 3100, 67, 1),
(9, 3, 'Merhaba NexusForum!', 'merhaba-nexusforum', '<p>Herkese merhaba, yeni üyeyim...</p>', 120, 5, 0);

INSERT INTO posts (thread_id, user_id, content) VALUES
(1, 2, '<p>Harika bir rehber, teşekkürler!</p>'),
(1, 3, '<p>Çok faydalı oldu, devamını bekliyorum.</p>'),
(2, 1, '<p>Pointer scanning bölümünü de ekleyebilir misiniz?</p>'),
(3, 1, '<p>Python botu için tesseract OCR de kullanılabilir.</p>'),
(4, 2, '<p>Manual mapping konusunu da işleyebilir misiniz?</p>');

CREATE TABLE IF NOT EXISTS advertisements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    position VARCHAR(50) NOT NULL,
    type ENUM('image','code','text') DEFAULT 'image',
    image_url VARCHAR(500) DEFAULT NULL,
    link_url VARCHAR(500) DEFAULT NULL,
    html_code TEXT DEFAULT NULL,
    alt_text VARCHAR(200) DEFAULT NULL,
    starts_at DATETIME DEFAULT NULL,
    ends_at DATETIME DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    click_count INT DEFAULT 0,
    impression_count INT DEFAULT 0,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_description', 'Modern, hızlı ve güvenli forum platformu'),
('site_keywords', 'forum, game hacking, programlama, marketplace'),
('default_theme', 'dark'),
('registration_open', '1'),
('maintenance_mode', '0'),
('ads_enabled', '1');

INSERT INTO advertisements (title, position, type, html_code, is_active, created_by) VALUES
('Demo Header Banner', 'header_banner', 'code', '<div style="display:flex;align-items:center;justify-content:center;height:90px;background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:10px;border:1px solid rgba(79,140,255,0.2);font-family:sans-serif;color:#4f8cff;font-size:13px;letter-spacing:0.05em;font-weight:600;">REKLAM ALANI — 970×90 — Header Banner</div>', 1, 1),
('Demo Sidebar', 'sidebar_top', 'code', '<div style="display:flex;align-items:center;justify-content:center;height:250px;background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:10px;border:1px solid rgba(79,140,255,0.2);font-family:sans-serif;color:#4f8cff;font-size:13px;letter-spacing:0.05em;font-weight:600;">REKLAM ALANI<br>300×250<br>Sidebar</div>', 1, 1);

-- Sosyal medya alanları (mevcut users tablosuna ALTER)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS website VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS twitter VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS github VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS discord VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS youtube VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS instagram VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS steam VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS twitch VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS location VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS birth_date DATE DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS gender ENUM('male','female','other','') DEFAULT '';

CREATE TABLE IF NOT EXISTS dm_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    last_message_id INT DEFAULT NULL,
    last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user1_deleted TINYINT(1) DEFAULT 0,
    user2_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_convo (user1_id, user2_id),
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dm_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    content TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES dm_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'award',
    color VARCHAR(20) DEFAULT '#5b7fff',
    bg_color VARCHAR(20) DEFAULT 'rgba(91,127,255,0.15)',
    type ENUM('auto','manual') DEFAULT 'auto',
    auto_condition VARCHAR(50) DEFAULT NULL,
    auto_value INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    awarded_by INT DEFAULT NULL,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_badge (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS thread_follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (thread_id, user_id),
    FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS profile_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_user_id INT NOT NULL,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS media_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT IGNORE INTO badges (name, slug, description, icon, color, bg_color, type, auto_condition, auto_value) VALUES
('Yeni Üye',    'new-member',    'Foruma katıldı',                    '👋', '#3dd68c', 'rgba(61,214,140,0.12)',  'auto', 'post_count', 0),
('Aktif Üye',   'active',        '50+ mesaj gönderdi',                '💬', '#5b7fff', 'rgba(91,127,255,0.12)',  'auto', 'post_count', 50),
('Veteran',     'veteran',       '200+ mesaj gönderdi',               '⚔️', '#ffb547', 'rgba(255,181,71,0.12)',  'auto', 'post_count', 200),
('Elite',       'elite',         '500+ mesaj gönderdi',               '💎', '#9b6dff', 'rgba(155,109,255,0.12)', 'auto', 'post_count', 500),
('Konu Açıcı',  'thread-starter','10+ konu açtı',                     '📝', '#38d9f5', 'rgba(56,217,245,0.12)',  'auto', 'thread_count', 10),
('Güvenilir',   'trusted',       'Yönetim tarafından onaylandı',      '✅', '#3dd68c', 'rgba(61,214,140,0.12)',  'manual', NULL, 0),
('Donör',       'donor',         'Foruma destek sağladı',             '❤️', '#ff5f57', 'rgba(255,95,87,0.12)',   'manual', NULL, 0),
('Geliştirici', 'developer',     'Forum geliştirici ekibinden',       '⚙️', '#38d9f5', 'rgba(56,217,245,0.12)',  'manual', NULL, 0);

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS theme VARCHAR(10) DEFAULT 'dark',
    ADD COLUMN IF NOT EXISTS unread_dm_count INT DEFAULT 0;

INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('site_logo', ''),
('allow_dm', '1'),
('allow_media_upload', '1'),
('max_upload_mb', '5'),
('online_threshold_minutes', '15'),
('posts_per_page', '15');
