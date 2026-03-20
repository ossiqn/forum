<?php

function bbcode($text) {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    $text = preg_replace('/\[b\](.*?)\[\/b\]/is', '<strong>$1</strong>', $text);
    $text = preg_replace('/\[i\](.*?)\[\/i\]/is', '<em>$1</em>', $text);
    $text = preg_replace('/\[u\](.*?)\[\/u\]/is', '<u>$1</u>', $text);
    $text = preg_replace('/\[s\](.*?)\[\/s\]/is', '<del>$1</del>', $text);

    $text = preg_replace('/\[color=([a-zA-Z#0-9]+)\](.*?)\[\/color\]/is', '<span style="color:$1">$2</span>', $text);
    $text = preg_replace('/\[size=(\d+)\](.*?)\[\/size\]/is', '<span style="font-size:$1px">$2</span>', $text);

    $text = preg_replace_callback('/\[url=([^\]]+)\](.*?)\[\/url\]/is', function($m) {
        $url = filter_var(strip_tags($m[1]), FILTER_SANITIZE_URL);
        if (!filter_var($url, FILTER_VALIDATE_URL)) return $m[0];
        return '<a href="' . $url . '" target="_blank" rel="noopener nofollow">' . $m[2] . '</a>';
    }, $text);

    $text = preg_replace_callback('/\[url\](.*?)\[\/url\]/is', function($m) {
        $url = filter_var(strip_tags($m[1]), FILTER_SANITIZE_URL);
        if (!filter_var($url, FILTER_VALIDATE_URL)) return $m[0];
        return '<a href="' . $url . '" target="_blank" rel="noopener nofollow">' . $url . '</a>';
    }, $text);

    $text = preg_replace_callback('/\[img\](.*?)\[\/img\]/is', function($m) {
        $url = filter_var(strip_tags($m[1]), FILTER_SANITIZE_URL);
        if (!filter_var($url, FILTER_VALIDATE_URL)) return $m[0];
        return '<img src="' . $url . '" alt="image" style="max-width:100%;border-radius:8px;margin:6px 0" loading="lazy">';
    }, $text);

    $text = preg_replace_callback('/\[quote=([^\]]+)\](.*?)\[\/quote\]/is', function($m) {
        return '<div class="bb-quote"><div class="bb-quote-author"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 2v4c0 1.25.75 2 2 2h1c1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/></svg>' . htmlspecialchars($m[1]) . ' yazdı:</div><div class="bb-quote-body">' . $m[2] . '</div></div>';
    }, $text);

    $text = preg_replace_callback('/\[quote\](.*?)\[\/quote\]/is', function($m) {
        return '<div class="bb-quote"><div class="bb-quote-body">' . $m[1] . '</div></div>';
    }, $text);

    $text = preg_replace_callback('/\[code(?:=([a-z]+))?\](.*?)\[\/code\]/is', function($m) {
        $lang = !empty($m[1]) ? $m[1] : '';
        $code = htmlspecialchars_decode($m[2]);
        $code = trim($code);
        return '<div class="bb-code-wrap"><div class="bb-code-header"><span class="bb-code-lang">' . strtoupper($lang ?: 'CODE') . '</span><button class="bb-code-copy" onclick="copyCode(this)" title="Kopyala"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div><pre class="bb-code language-' . $lang . '"><code>' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</code></pre></div>';
    }, $text);

    $text = preg_replace('/\[spoiler\](.*?)\[\/spoiler\]/is',
        '<div class="bb-spoiler"><button class="bb-spoiler-btn" onclick="this.nextElementSibling.classList.toggle(\'open\')">Spoiler göster/gizle</button><div class="bb-spoiler-body">$1</div></div>', $text);

    $text = preg_replace('/\[list\](.*?)\[\/list\]/is', '<ul class="bb-list">$1</ul>', $text);
    $text = preg_replace('/\[list=1\](.*?)\[\/list\]/is', '<ol class="bb-list">$1</ol>', $text);
    $text = preg_replace('/\[\*\](.*?)(?=\[\*\]|\[\/list\])/is', '<li>$1</li>', $text);

    $text = preg_replace('/\[hr\]/i', '<hr class="bb-hr">', $text);
    $text = preg_replace('/\[center\](.*?)\[\/center\]/is', '<div style="text-align:center">$1</div>', $text);
    $text = preg_replace('/\[right\](.*?)\[\/right\]/is', '<div style="text-align:right">$1</div>', $text);

    $text = nl2br($text);

    return $text;
}
