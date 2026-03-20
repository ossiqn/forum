gsap.registerPlugin(ScrollTrigger);

document.addEventListener('DOMContentLoaded', () => {
    initPageEntrance();
    initNavbar();
    initMobileMenu();
    initDropdowns();
    initSearch();
    initScrollAnimations();
    initMicroInteractions();
    initCounters();
    initTheme();
    trackAdImpressions();
});

function initPageEntrance() {
    gsap.from('#navbar', { y: -70, opacity: 0, duration: 0.6, ease: 'power3.out' });
    gsap.from('.hero-eyebrow', { y: 20, opacity: 0, duration: 0.5, delay: 0.15, ease: 'power3.out' });
    gsap.from('.hero-title', { y: 35, opacity: 0, duration: 0.7, delay: 0.25, ease: 'power3.out' });
    gsap.from('.hero-sub', { y: 25, opacity: 0, duration: 0.6, delay: 0.4, ease: 'power3.out' });
    gsap.from('.hero-search-wrap', { y: 22, opacity: 0, duration: 0.6, delay: 0.52, ease: 'power3.out' });
    gsap.from('.stats-strip', { y: 18, opacity: 0, duration: 0.6, delay: 0.65, ease: 'power3.out' });
}

function initNavbar() {
    const nav = document.getElementById('navbar');
    if (!nav) return;
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 10);
    }, { passive: true });
}

function initMobileMenu() {
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');
    if (!btn || !menu) return;
    btn.addEventListener('click', () => {
        const open = !menu.classList.contains('hidden');
        if (open) {
            gsap.to(menu, { height: 0, opacity: 0, duration: 0.22, ease: 'power2.in', onComplete: () => { menu.classList.add('hidden'); menu.style.height = ''; menu.style.overflow = ''; } });
        } else {
            menu.classList.remove('hidden');
            gsap.fromTo(menu, { height: 0, opacity: 0 }, { height: 'auto', opacity: 1, duration: 0.28, ease: 'power2.out' });
        }
    });
}

function initDropdowns() {
    document.addEventListener('click', (e) => {
        const pairs = [
            ['notif-btn', 'notif-dropdown'],
            ['profile-nav-btn', 'profile-dropdown'],
        ];
        pairs.forEach(([btnId, dropId]) => {
            const btn = document.getElementById(btnId);
            const drop = document.getElementById(dropId);
            if (!btn || !drop) return;
            if (btn.contains(e.target)) {
                const visible = drop.style.opacity === '1';
                closeAllDropdowns();
                if (!visible) openDropdown(drop);
            } else if (!drop.contains(e.target)) {
                closeDropdown(drop);
            }
        });
    });
}

function openDropdown(el) {
    el.style.visibility = 'visible';
    gsap.fromTo(el, { opacity: 0, y: -8, scale: 0.97 }, { opacity: 1, y: 0, scale: 1, duration: 0.2, ease: 'power2.out' });
}

function closeDropdown(el) {
    if (!el || el.style.opacity === '0' || el.style.visibility === 'hidden') return;
    gsap.to(el, { opacity: 0, y: -8, scale: 0.97, duration: 0.15, ease: 'power2.in', onComplete: () => { el.style.visibility = 'hidden'; } });
}

function closeAllDropdowns() {
    ['notif-dropdown', 'profile-dropdown'].forEach(id => {
        const el = document.getElementById(id);
        if (el) closeDropdown(el);
    });
}

function initSearch() {
    const input = document.getElementById('global-search');
    const results = document.getElementById('search-results');
    if (!input || !results) return;
    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) { results.classList.add('hidden'); return; }
        results.innerHTML = '<div style="padding:16px"><div class="skeleton" style="height:16px;margin-bottom:8px"></div><div class="skeleton" style="height:12px;width:60%"></div></div>';
        results.classList.remove('hidden');
        timer = setTimeout(() => doSearch(q, results), 320);
    });
    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !results.contains(e.target)) results.classList.add('hidden');
    });
}

async function doSearch(q, container) {
    try {
        const r = await fetch(`${window.SITE_URL}/api/search.php?q=${encodeURIComponent(q)}`);
        const d = await r.json();
        if (!d.results?.length) { container.innerHTML = '<div style="padding:14px 16px;font-size:13px;color:var(--ink-3);text-align:center">Sonuç bulunamadı</div>'; return; }
        container.innerHTML = d.results.map(r => `<a href="${window.SITE_URL}/thread.php?id=${r.id}" class="search-result-item"><div class="result-title">${r.title}</div><div class="result-meta">${r.forum_name} · ${r.reply_count} cevap</div></a>`).join('');
    } catch { container.innerHTML = '<div style="padding:14px 16px;font-size:13px;color:var(--red);text-align:center">Hata</div>'; }
}

function initScrollAnimations() {
    gsap.utils.toArray('.category-section').forEach((el, i) => {
        gsap.from(el, { scrollTrigger: { trigger: el, start: 'top 88%', once: true }, y: 32, opacity: 0, duration: 0.55, delay: i * 0.04, ease: 'power3.out' });
    });
    gsap.utils.toArray('.forum-row').forEach((el, i) => {
        gsap.from(el, { scrollTrigger: { trigger: el, start: 'top 92%', once: true }, x: -16, opacity: 0, duration: 0.38, delay: i * 0.035, ease: 'power2.out' });
    });
    gsap.utils.toArray('.thread-row').forEach((el, i) => {
        gsap.from(el, { scrollTrigger: { trigger: el, start: 'top 94%', once: true }, x: -12, opacity: 0, duration: 0.32, delay: i * 0.03, ease: 'power2.out' });
    });
    gsap.utils.toArray('.post-wrapper').forEach((el, i) => {
        gsap.from(el, { scrollTrigger: { trigger: el, start: 'top 90%', once: true }, y: 20, opacity: 0, duration: 0.45, delay: i * 0.05, ease: 'power3.out' });
    });
    gsap.utils.toArray('.admin-stat-card').forEach((el, i) => {
        gsap.from(el, { scrollTrigger: { trigger: el, start: 'top 90%', once: true }, y: 20, opacity: 0, duration: 0.4, delay: i * 0.08, ease: 'power2.out' });
    });
}

function initMicroInteractions() {
    document.querySelectorAll('.btn-primary,.btn-ghost,.btn-danger,.btn-success').forEach(btn => {
        btn.addEventListener('mouseenter', () => gsap.to(btn, { scale: 1.03, duration: 0.15, ease: 'power2.out' }));
        btn.addEventListener('mouseleave', () => gsap.to(btn, { scale: 1, duration: 0.2, ease: 'power2.out' }));
        btn.addEventListener('mousedown', () => gsap.to(btn, { scale: 0.97, duration: 0.08 }));
        btn.addEventListener('mouseup', () => gsap.to(btn, { scale: 1.02, duration: 0.1 }));
    });
    document.querySelectorAll('.stat-strip-item').forEach(el => {
        el.addEventListener('mouseenter', () => gsap.to(el, { y: -2, duration: 0.2, ease: 'power2.out' }));
        el.addEventListener('mouseleave', () => gsap.to(el, { y: 0, duration: 0.25, ease: 'power2.out' }));
    });
}

function initCounters() {
    document.querySelectorAll('[data-counter]').forEach(el => {
        const target = parseInt(el.getAttribute('data-counter'));
        ScrollTrigger.create({
            trigger: el, start: 'top 88%', once: true,
            onEnter: () => {
                const obj = { val: 0 };
                gsap.to(obj, { val: target, duration: 1.4, ease: 'power2.out',
                    onUpdate: function() { el.textContent = fmtNum(Math.round(obj.val)); }
                });
            }
        });
    });
}

function fmtNum(n) {
    if (n >= 1000000) return (n/1000000).toFixed(1)+'M';
    if (n >= 1000) return (n/1000).toFixed(1)+'K';
    return n.toString();
}

function initTheme() {
    const saved = getCookie('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    setCookie('theme', next, 365);
    gsap.from('body', { opacity: 0.85, duration: 0.3 });
}

function getCookie(name) {
    const v = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return v ? v.pop() : '';
}

function setCookie(name, value, days) {
    const d = new Date(); d.setTime(d.getTime() + days * 86400000);
    document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
}

function showToast(message, type = 'info', duration = 4000) {
    const icons = {
        success: `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>`,
        error:   `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--red)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
        info:    `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
        warning: `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>`,
    };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `${icons[type]||icons.info}<span>${message}</span>`;
    document.getElementById('toast-container').appendChild(toast);
    gsap.from(toast, { x: 100, opacity: 0, duration: 0.3, ease: 'power3.out' });
    setTimeout(() => gsap.to(toast, { x: 100, opacity: 0, duration: 0.25, ease: 'power2.in', onComplete: () => toast.remove() }), duration);
}

async function likePost(postId, btn) {
    if (!window.isLoggedIn) { showToast('Beğenmek için giriş yapmalısınız', 'warning'); return; }
    try {
        const r = await fetch(`${window.SITE_URL}/api/like.php`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ post_id: postId, type: 'post' }) });
        const d = await r.json();
        if (d.success) {
            const c = btn.querySelector('.like-count'); if (c) c.textContent = d.count;
            btn.classList.toggle('liked', d.liked);
            gsap.to(btn, { scale: 1.25, duration: 0.12, ease: 'back.out', onComplete: () => gsap.to(btn, { scale: 1, duration: 0.2 }) });
        }
    } catch { showToast('Hata', 'error'); }
}

async function likeThread(threadId, btn) {
    if (!window.isLoggedIn) { showToast('Beğenmek için giriş yapmalısınız', 'warning'); return; }
    try {
        const r = await fetch(`${window.SITE_URL}/api/like.php`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ thread_id: threadId, type: 'thread' }) });
        const d = await r.json();
        if (d.success) {
            const c = btn.querySelector('.like-count'); if (c) c.textContent = d.count;
            btn.classList.toggle('liked', d.liked);
            gsap.to(btn, { scale: 1.25, duration: 0.12, ease: 'back.out', onComplete: () => gsap.to(btn, { scale: 1, duration: 0.2 }) });
        }
    } catch {}
}

function quotePost(postId, username, content) {
    const ta = document.getElementById('reply-textarea');
    if (!ta) { showToast('Hızlı cevap alanı bulunamadı', 'warning'); return; }
    ta.value = `[quote=${username}]${content.substring(0, 180).trim()}[/quote]\n\n` + ta.value;
    ta.focus(); ta.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function editorFormat(tag) {
    const ta = document.getElementById('reply-textarea') || document.getElementById('thread-content');
    if (!ta) return;
    const s = ta.selectionStart, e = ta.selectionEnd, sel = ta.value.substring(s, e);
    const t = { bold:['[b]','[/b]'], italic:['[i]','[/i]'], underline:['[u]','[/u]'], code:['[code]','[/code]'], quote:['[quote]','[/quote]'], spoiler:['[spoiler]','[/spoiler]'] }[tag];
    if (!t) return;
    ta.value = ta.value.substring(0, s) + t[0] + sel + t[1] + ta.value.substring(e);
    ta.selectionStart = s + t[0].length; ta.selectionEnd = e + t[0].length; ta.focus();
}

async function markAllRead() {
    try {
        await fetch(`${window.SITE_URL}/api/notifications.php`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({action:'mark_all_read'}) });
        document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
        const badge = document.querySelector('.notif-badge');
        if (badge) gsap.to(badge, { scale: 0, duration: 0.2, onComplete: () => badge.remove() });
        showToast('Bildirimler okundu', 'success', 2000);
    } catch {}
}

function confirmDelete(msg, url) {
    if (confirm(msg || 'Silmek istediğinize emin misiniz?')) window.location.href = url;
}

function trackAdClick(adId) {
    fetch(`${window.SITE_URL}/api/ad-click.php`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ ad_id: adId }) });
}

function trackAdImpressions() {
    document.querySelectorAll('.ad-slot[data-ad-id]').forEach(el => {
        const id = el.getAttribute('data-ad-id');
        if (id) fetch(`${window.SITE_URL}/api/ad-impression.php`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ ad_id: id }) });
    });
}

async function toggleFollow(threadId, btn) {
    if (!window.isLoggedIn) { showToast('Takip etmek için giriş yapmalısınız', 'warning'); return; }
    try {
        const r = await fetch(`${window.SITE_URL}/api/follow.php`, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ thread_id: threadId })
        });
        const d = await r.json();
        if (d.success) {
            btn.classList.toggle('following', d.following);
            btn.innerHTML = d.following
                ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg> Takipten Çık'
                : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg> Takip Et';
            gsap.to(btn, { scale: 1.15, duration: .12, ease: 'back.out', onComplete: () => gsap.to(btn, { scale: 1, duration: .15 }) });
            showToast(d.following ? 'Konu takip edildi' : 'Takip bırakıldı', 'success', 2000);
        }
    } catch { showToast('Hata', 'error'); }
}

function copyCode(btn) {
    const pre = btn.closest('.bb-code-wrap').querySelector('code');
    if (!pre) return;
    navigator.clipboard.writeText(pre.innerText).then(() => {
        btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
        btn.style.color = 'var(--green)';
        setTimeout(() => {
            btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
            btn.style.color = '';
        }, 2000);
    });
}

async function uploadImage(input) {
    const file = input.files[0];
    if (!file) return;
    const form = new FormData();
    form.append('file', file);
    showToast('Yükleniyor...', 'info', 2000);
    try {
        const r = await fetch(`${window.SITE_URL}/api/upload.php`, { method: 'POST', body: form });
        const d = await r.json();
        if (d.success) {
            const ta = document.getElementById('reply-textarea') || document.getElementById('thread-content');
            if (ta) {
                const pos = ta.selectionStart;
                ta.value = ta.value.substring(0, pos) + d.bbcode + ta.value.substring(pos);
                ta.focus();
            }
            showToast('Resim yüklendi!', 'success', 2500);
        } else {
            showToast(d.message || 'Yükleme hatası', 'error');
        }
    } catch { showToast('Yükleme başarısız', 'error'); }
    input.value = '';
}

function openNotifDropdown() {
    var badge = document.querySelector('.notif-badge');
    if (badge) {
        fetch(window.SITE_URL + '/api/notifications.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action:'mark_all_read'})
        });
        badge.style.transition = 'transform .2s ease, opacity .2s ease';
        badge.style.transform = 'scale(0)';
        badge.style.opacity = '0';
        setTimeout(function() { badge.remove(); }, 220);
        document.querySelectorAll('.notif-item.unread').forEach(function(el) {
            el.classList.remove('unread');
        });
    }
    var drop = document.getElementById('notif-dropdown');
    if (drop) {
        var visible = drop.style.opacity === '1';
        if (visible) { closeDropdown(drop); } else { openDropdown(drop); }
    }
}
