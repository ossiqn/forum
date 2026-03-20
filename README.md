# NexusForum — Kurulum Rehberi

## Sistem Gereksinimleri
- PHP 8.0 veya üzeri
- MySQL 5.7 / MariaDB 10.3+
- Apache (mod_rewrite etkin) veya Nginx
- GD veya Imagick (resim işleme için)

## Kurulum Adımları

### 1. Veritabanını Oluşturun
```sql
mysql -u root -p < database.sql
```
Varsayılan test şifresi: `password123`  
Admin: `admin@nexusforum.com`  
Moderator: `mod@nexusforum.com`  
Üye: `test@nexusforum.com`

### 2. Config Dosyasını Düzenleyin
`includes/config.php` dosyasını açın ve aşağıdaki değerleri güncelleyin:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'veritabani_kullanici');
define('DB_PASS', 'veritabani_sifre');
define('DB_NAME', 'nexusforum');
define('SITE_URL', 'https://siteniz.com/forum');
```

### 3. Klasör İzinlerini Ayarlayın
```bash
chmod 755 uploads/
chmod 755 uploads/avatars/
chmod 755 uploads/covers/
```

### 4. Apache / Nginx Yapılandırması

**Apache:** `AllowOverride All` ayarının aktif olduğundan emin olun.

**Nginx:**
```nginx
location /forum/ {
    try_files $uri $uri/ /forum/index.php?$query_string;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Dosya Yapısı
```
forum/
├── index.php           — Ana sayfa
├── forum.php           — Kategori & forum listesi
├── thread.php          — Konu detayı & cevaplar
├── new-thread.php      — Yeni konu oluşturma
├── profile.php         — Kullanıcı profili
├── settings.php        — Hesap ayarları
├── login.php           — Giriş sayfası
├── register.php        — Kayıt sayfası
├── logout.php          — Çıkış
├── edit-post.php       — Mesaj düzenleme
├── database.sql        — Veritabanı şeması + örnek veriler
├── .htaccess           — Apache ayarları
├── admin/
│   ├── index.php       — Admin dashboard
│   ├── users.php       — Kullanıcı yönetimi
│   ├── forums.php      — Forum yönetimi
│   ├── threads.php     — Konu yönetimi
│   ├── reports.php     — Rapor yönetimi
│   └── ...
├── api/
│   ├── like.php        — Beğeni API
│   ├── search.php      — Arama API
│   └── notifications.php — Bildirim API
├── includes/
│   ├── config.php      — Konfigürasyon
│   ├── db.php          — Veritabanı sınıfı
│   ├── functions.php   — Yardımcı fonksiyonlar
│   ├── header.php      — Site başlığı
│   └── footer.php      — Site alt bilgisi
├── assets/
│   ├── css/main.css    — Ana stil dosyası
│   └── js/main.js      — Ana JavaScript
└── uploads/
    ├── avatars/        — Kullanıcı avatarları
    └── covers/         — Kapak fotoğrafları
```

## Özellikler
- Koyu tema (Dark mode) tasarımı
- GSAP ile scroll animasyonları
- Glassmorphism efektleri
- Tam responsive (mobil / tablet / masaüstü)
- CSRF koruması
- Admin paneli (kullanıcı, forum, konu, rapor yönetimi)
- AJAX arama & beğeni sistemi
- Bildirim sistemi
- Profil sayfası + kapak fotoğrafı + avatar
- BBCode editör
- Sayfalama sistemi
- Konu sabitleme & kilitleme
- Kullanıcı rütbeleri (Admin, Moderatör, Üye)
- Kullanıcı yasaklama (Ban)

## Güvenlik Notları
- Şifreler bcrypt ile hashlenir
- Tüm formlar CSRF token ile korunur
- SQL injection'a karşı prepared statements
- XSS koruması için htmlspecialchars
- Dosya yükleme MIME type doğrulaması
