<?php
require_once 'includes/functions.php';
requireLogin();
$me   = currentUser();
$convos = getDmConversations($me['id']);
$cid  = (int)($_GET['c'] ?? 0);
$uname = $_GET['u'] ?? '';
if ($uname && !$cid) {
    $ou = db()->fetchOne("SELECT * FROM users WHERE username=? AND is_banned=0", [$uname],'s');
    if ($ou && $ou['id'] !== $me['id']) {
        $cv = getOrCreateConversation($me['id'], $ou['id']);
        redirect(SITE_URL.'/messages.php?c='.$cv['id']);
    }
}
$convo = $other = null;
$msgs  = [];
$lastId = 0;
if ($cid) {
    $convo = db()->fetchOne("SELECT * FROM dm_conversations WHERE id=? AND (user1_id=? OR user2_id=?)", [$cid,$me['id'],$me['id']],'iii');
    if ($convo) {
        $oid   = $convo['user1_id']==$me['id'] ? $convo['user2_id'] : $convo['user1_id'];
        $other = db()->fetchOne("SELECT * FROM users WHERE id=?", [$oid],'i');
        $msgs  = db()->fetchAll("SELECT dm.*,u.username,u.avatar FROM dm_messages dm JOIN users u ON u.id=dm.sender_id WHERE dm.conversation_id=? ORDER BY dm.created_at ASC", [$cid],'i');
        db()->query("UPDATE dm_messages SET is_read=1 WHERE conversation_id=? AND sender_id!=?", [$cid,$me['id']],'ii');
        if ($msgs) $lastId = (int)end($msgs)['id'];
    }
}
$myAv    = getAvatar($me);
$otherAv = $other ? getAvatar($other) : '';
$isOnline = $other && (time()-strtotime($other['last_seen'])) < 300;
$statusTxt = $isOnline ? 'Şu an aktif' : ($other ? timeAgo($other['last_seen']).' önce aktifti' : '');
$pageTitle = 'Mesajlar';
require_once 'includes/header.php';
?>
<style>
.mp{display:flex;height:calc(100vh - 62px);overflow:hidden}
.mp-sb{width:290px;flex-shrink:0;display:flex;flex-direction:column;border-right:1px solid var(--border-1);background:var(--bg-surface)}
.mp-sb-top{padding:14px 16px;border-bottom:1px solid var(--border-0);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.mp-sb-title{font-family:'Clash Display',sans-serif;font-size:15px;font-weight:700;color:var(--ink-0)}
.mp-list{flex:1;overflow-y:auto;padding:6px}
.mp-item{display:flex;align-items:center;gap:10px;padding:10px 11px;border-radius:10px;text-decoration:none;transition:background .12s;position:relative}
.mp-item:hover,.mp-item.active{background:var(--bg-raised)}
.mp-item.active{background:var(--accent-dim)}
.mp-item-av{width:42px;height:42px;border-radius:50%;object-fit:cover;flex-shrink:0}
.mp-item-name{font-size:13.5px;font-weight:700;color:var(--ink-0);line-height:1.3}
.mp-item-prev{font-size:12px;color:var(--ink-3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:155px;margin-top:1px}
.mp-item-time{font-size:10px;color:var(--ink-3);font-family:'JetBrains Mono',monospace;flex-shrink:0;align-self:flex-start;margin-top:2px}
.mp-unread-dot{position:absolute;top:11px;right:11px;width:7px;height:7px;background:var(--accent);border-radius:50%}
.mp-main{flex:1;display:flex;flex-direction:column;overflow:hidden;background:var(--bg-canvas)}
.mp-head{display:flex;align-items:center;gap:12px;padding:13px 18px;border-bottom:1px solid var(--border-0);background:var(--bg-surface);flex-shrink:0}
.mp-head-av{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--border-1)}
.mp-head-name{font-family:'Clash Display',sans-serif;font-size:15px;font-weight:700;color:var(--ink-0);text-decoration:none;letter-spacing:-.01em}
.mp-head-status{font-size:12px;color:var(--ink-3);margin-top:1px;font-family:'JetBrains Mono',monospace}
.mp-head-status.online{color:var(--green);font-weight:600}
.mp-body{flex:1;overflow-y:auto;padding:18px 16px;display:flex;flex-direction:column;gap:4px;scroll-behavior:smooth}
.mp-datesep{text-align:center;margin:10px 0;position:relative}
.mp-datesep::before{content:'';position:absolute;left:0;right:0;top:50%;height:1px;background:var(--border-0)}
.mp-datesep span{position:relative;background:var(--bg-canvas);padding:0 10px;font-size:11px;color:var(--ink-3);font-family:'JetBrains Mono',monospace}
.mp-row{display:flex;align-items:flex-end;gap:7px;animation:mpIn .18s ease}
@keyframes mpIn{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:translateY(0)}}
.mp-row.mine{flex-direction:row-reverse}
.mp-av{width:26px;height:26px;border-radius:50%;object-fit:cover;flex-shrink:0;margin-bottom:16px}
.mp-wrap{display:flex;flex-direction:column;gap:2px;max-width:65%}
.mp-row.mine .mp-wrap{align-items:flex-end}
.mp-bubble{padding:9px 13px;border-radius:14px;font-size:14px;line-height:1.55;word-break:break-word;position:relative}
.mp-bubble.theirs{background:var(--bg-surface);border:1px solid var(--border-1);border-bottom-left-radius:3px;color:var(--ink-1)}
.mp-bubble.mine{background:var(--accent);color:#fff;border-bottom-right-radius:3px}
.mp-acts{position:absolute;top:-28px;right:0;display:none;gap:2px;background:var(--bg-surface);border:1px solid var(--border-1);border-radius:8px;padding:3px;box-shadow:var(--shadow-sm);z-index:10}
.mp-row.mine .mp-acts{right:auto;left:0}
.mp-bubble:hover .mp-acts{display:flex}
.mp-act{width:22px;height:22px;border-radius:5px;background:none;border:none;cursor:pointer;color:var(--ink-2);display:flex;align-items:center;justify-content:center;transition:all .12s}
.mp-act:hover{background:var(--bg-elevated);color:var(--ink-0)}
.mp-meta{display:flex;align-items:center;gap:5px;padding:0 3px}
.mp-time{font-size:10.5px;color:var(--ink-3);font-family:'JetBrains Mono',monospace}
.mp-tick{color:var(--ink-3);display:flex;align-items:center;transition:color .3s}
.mp-tick.seen{color:var(--accent)}
.mp-like{background:none;border:none;cursor:pointer;padding:0;color:var(--ink-3);display:flex;align-items:center;transition:all .15s}
.mp-like.liked{color:var(--red)}
.mp-like svg{transition:transform .15s}
.mp-like:hover svg{transform:scale(1.2)}
.mp-typing{display:none;align-items:center;gap:7px;padding:5px 4px;font-size:12px;color:var(--ink-3);font-family:'JetBrains Mono',monospace}
.mp-typing.show{display:flex}
.mp-tdots{display:flex;gap:3px;align-items:center}
.mp-tdot{width:5px;height:5px;border-radius:50%;background:var(--ink-3);animation:td 1.2s infinite}
.mp-tdot:nth-child(2){animation-delay:.2s}
.mp-tdot:nth-child(3){animation-delay:.4s}
@keyframes td{0%,60%,100%{opacity:.3;transform:translateY(0)}30%{opacity:1;transform:translateY(-4px)}}
.mp-foot{padding:10px 14px 12px;border-top:1px solid var(--border-0);background:var(--bg-surface);flex-shrink:0}
.mp-qprev{display:none;align-items:center;gap:8px;padding:7px 10px;margin-bottom:8px;background:var(--accent-dim);border-left:2px solid var(--accent);border-radius:0 8px 8px 0}
.mp-qprev.show{display:flex}
.mp-qtext{flex:1;font-size:12.5px;color:var(--ink-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mp-wrap2{display:flex;align-items:flex-end;gap:8px;background:var(--bg-raised);border:1.5px solid var(--border-1);border-radius:18px;padding:7px 7px 7px 14px;transition:border-color .2s}
.mp-wrap2:focus-within{border-color:var(--accent-border);background:var(--bg-surface)}
.mp-input{flex:1;background:transparent;border:none;outline:none;resize:none;font-family:'Satoshi',sans-serif;font-size:14.5px;color:var(--ink-0);line-height:1.55;min-height:24px;max-height:110px;padding:2px 0}
.mp-input::placeholder{color:var(--ink-3)}
.mp-sbtn{width:36px;height:36px;border-radius:10px;background:var(--accent);border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s;box-shadow:0 2px 8px var(--accent-glow)}
.mp-sbtn:hover{background:var(--accent-hover);transform:scale(1.07)}
.mp-sbtn:active{transform:scale(.94)}
.mp-sbtn:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none}
.mp-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;color:var(--ink-3);text-align:center;padding:30px}
.mp-back{display:none;width:34px;height:34px;border-radius:9px;background:var(--bg-raised);border:1px solid var(--border-1);color:var(--ink-2);cursor:pointer;align-items:center;justify-content:center;flex-shrink:0}
.ndm-ov{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);backdrop-filter:blur(8px);align-items:center;justify-content:center;padding:16px}
.ndm-box{background:var(--bg-surface);border:1px solid var(--border-1);border-radius:16px;width:100%;max-width:380px;padding:22px;box-shadow:var(--shadow-xl)}
@media(max-width:768px){
.mp-sb{width:100%;position:absolute;inset:62px 0 0 0;z-index:5;transition:transform .22s cubic-bezier(.4,0,.2,1)}
.mp-sb.gone{transform:translateX(-100%)}
.mp-main{width:100%}
.mp-back{display:flex}
.mp-body{padding:14px 10px}
.mp-foot{padding:8px 10px 10px}
.mp-wrap2{border-radius:14px;padding:6px 6px 6px 12px}
.mp-input{font-size:14px}
.mp-head{padding:11px 14px}
}
</style>
<div class="mp">
<div class="mp-sb" id="sb">
    <div class="mp-sb-top">
        <span class="mp-sb-title">Mesajlar</span>
        <button onclick="document.getElementById('ndm').style.display='flex'" style="display:flex;align-items:center;gap:5px;padding:5px 12px;background:var(--accent-dim);border:1px solid var(--accent-border);border-radius:999px;color:var(--accent);font-size:12.5px;font-weight:600;cursor:pointer;font-family:'Satoshi',sans-serif">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Yeni
        </button>
    </div>
    <div class="mp-list">
        <?php if (empty($convos)): ?>
        <div style="padding:30px 14px;text-align:center;color:var(--ink-3);font-size:13px">Henüz konuşma yok</div>
        <?php else: foreach ($convos as $c):
            $cAv = getAvatar(['avatar'=>$c['other_avatar'],'username'=>$c['other_username']]);
            $unrd = ($c['last_sender_id'] != $me['id']) && !$c['last_is_read'];
        ?>
        <a href="<?= SITE_URL ?>/messages.php?c=<?= $c['id'] ?>" class="mp-item <?= $cid==$c['id']?'active':'' ?>">
            <img src="<?= $cAv ?>" class="mp-item-av" alt="">
            <div style="flex:1;min-width:0">
                <div class="mp-item-name"><?= htmlspecialchars($c['other_username']) ?></div>
                <div class="mp-item-prev"><?= htmlspecialchars(mb_substr($c['last_content']??'—',0,45)) ?></div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:3px;flex-shrink:0">
                <span class="mp-item-time"><?= timeAgo($c['last_message_at']) ?></span>
                <?php if ($unrd): ?><span class="mp-unread-dot"></span><?php endif; ?>
            </div>
        </a>
        <?php endforeach; endif; ?>
    </div>
</div>
<div class="mp-main">
<?php if ($convo && $other): ?>
    <div class="mp-head">
        <button class="mp-back" onclick="document.getElementById('sb').classList.remove('gone')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <img src="<?= $otherAv ?>" class="mp-head-av" alt="">
        <div style="flex:1;min-width:0">
            <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($other['username']) ?>" class="mp-head-name"><?= htmlspecialchars($other['username']) ?></a>
            <div class="mp-head-status <?= $isOnline?'online':'' ?>">
                <?= $isOnline ? '<span class="online-dot" style="margin-right:4px;vertical-align:middle"></span>' : '' ?>
                <?= htmlspecialchars($statusTxt) ?>
            </div>
        </div>
        <a href="<?= SITE_URL ?>/profile.php?u=<?= urlencode($other['username']) ?>" class="btn-ghost" style="font-size:12px;padding:6px 12px;flex-shrink:0">Profil</a>
    </div>
    <div class="mp-body" id="body">
    <?php $pd=''; foreach ($msgs as $msg):
        $mine = $msg['sender_id']==$me['id'];
        $dd   = date('d.m.Y',strtotime($msg['created_at']));
        $dl   = $dd===date('d.m.Y')?'Bugün':($dd===date('d.m.Y',strtotime('-1 day'))?'Dün':$dd);
    ?>
    <?php if ($dd!==$pd): $pd=$dd; ?>
    <div class="mp-datesep"><span><?= $dl ?></span></div>
    <?php endif; ?>
    <div class="mp-row <?= $mine?'mine':'' ?>" id="msg-<?= $msg['id'] ?>" data-id="<?= $msg['id'] ?>">
        <?php if (!$mine): ?>
        <img src="<?= getAvatar(['avatar'=>$msg['avatar'],'username'=>$msg['username']]) ?>" class="mp-av" alt="">
        <?php endif; ?>
        <div class="mp-wrap">
            <div class="mp-bubble <?= $mine?'mine':'theirs' ?>" data-txt="<?= htmlspecialchars($msg['content']) ?>" data-author="<?= htmlspecialchars($msg['username']) ?>">
                <?= nl2br(htmlspecialchars($msg['content'])) ?>
                <div class="mp-acts">
                    <button class="mp-act" onclick="qMsg(this)" title="Alıntı"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 2v4c0 1.25.75 2 2 2h1c1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 2v4c0 1.25.75 2 2 2h1c1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/></svg></button>
                    <button class="mp-act" onclick="cpMsg(this)" title="Kopyala"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>
                </div>
            </div>
            <div class="mp-meta">
                <span class="mp-time"><?= date('H:i',strtotime($msg['created_at'])) ?></span>
                <?php if ($mine): ?>
                <span class="mp-tick <?= $msg['is_read']?'seen':'' ?>">
                    <?php if ($msg['is_read']): ?><svg width="15" height="10" viewBox="0 0 22 12" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="1 6 5 10 11 2"/><polyline points="9 6 13 10 19 2"/></svg>
                    <?php else: ?><svg width="12" height="10" viewBox="0 0 16 12" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="1 6 5 10 13 2"/></svg><?php endif; ?>
                </span>
                <?php endif; ?>
                <button class="mp-like" onclick="lMsg(<?= $msg['id'] ?>,this)" data-liked="0"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></button>
            </div>
        </div>
    </div>
    <?php endforeach; if (empty($msgs)): ?>
    <div style="margin:auto;text-align:center;color:var(--ink-3)">
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin:0 auto 10px;display:block;opacity:.25"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <p style="font-size:14px">İlk mesajı gönder!</p>
    </div>
    <?php endif; ?>
    </div>
    <div class="mp-foot">
        <div class="mp-typing" id="typi"><div class="mp-tdots"><div class="mp-tdot"></div><div class="mp-tdot"></div><div class="mp-tdot"></div></div><span id="tname" style="font-size:12px;margin-left:5px"></span><span style="font-size:12px"> yazıyor...</span></div>
        <div class="mp-qprev" id="qp"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" style="flex-shrink:0"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 2v4c0 1.25.75 2 2 2h1c1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/></svg><span class="mp-qtext" id="qtx"></span><button onclick="cQ()" style="background:none;border:none;color:var(--ink-3);cursor:pointer;padding:0;display:flex;flex-shrink:0"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
        <div class="mp-wrap2">
            <textarea class="mp-input" id="inp" placeholder="Mesaj yaz..." rows="1" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();snd()}" oninput="grow(this);sTyp()"></textarea>
            <button class="mp-sbtn" id="sbtn" onclick="snd()"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
        </div>
    </div>
<?php else: ?>
    <div class="mp-empty">
        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="opacity:.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <p style="font-size:15px;font-weight:600;color:var(--ink-1)">Bir konuşma seç</p>
        <p style="font-size:13.5px">veya yeni mesaj başlat</p>
        <button onclick="document.getElementById('ndm').style.display='flex'" class="btn-primary" style="margin-top:8px"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Yeni Mesaj</button>
    </div>
<?php endif; ?>
</div>
</div>

<div class="ndm-ov" id="ndm" onclick="event.target===this&&(this.style.display='none')">
    <div class="ndm-box">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
            <span style="font-family:'Clash Display',sans-serif;font-size:16px;font-weight:700;color:var(--ink-0)">Yeni Mesaj</span>
            <button onclick="document.getElementById('ndm').style.display='none'" style="width:28px;height:28px;border-radius:7px;background:var(--bg-raised);border:1px solid var(--border-1);color:var(--ink-2);cursor:pointer;display:flex;align-items:center;justify-content:center"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <form action="<?= SITE_URL ?>/messages.php" method="GET">
            <div class="form-group"><label class="form-label">Kullanıcı Adı</label><input type="text" name="u" class="form-control" placeholder="kullanici_adi" required autofocus></div>
            <button type="submit" class="btn-primary" style="width:100%;justify-content:center">Devam Et</button>
        </form>
    </div>
</div>

<script>
var CID=<?= $cid ?>,MY_ID=<?= $me['id'] ?>,MY_AV=<?= json_encode($myAv) ?>,OAV=<?= json_encode($otherAv) ?>,BASE=<?= json_encode(SITE_URL) ?>,lastId=<?= $lastId ?>,qd=null,pt=null,tt=null;
function scrollEnd(s){var b=document.getElementById('body');if(b)b.scrollTo({top:b.scrollHeight,behavior:s?'smooth':'auto'});}
scrollEnd(false);
function grow(e){e.style.height='auto';e.style.height=Math.min(e.scrollHeight,110)+'px';}
async function snd(){
    var inp=document.getElementById('inp');
    var txt=inp.value.trim();
    if(!txt||!CID)return;
    if(qd){txt=qd.a+' kullanıcısına yanıt:\n"'+qd.t+'"\n\n'+txt;cQ();}
    var btn=document.getElementById('sbtn');
    btn.disabled=true;
    var orig=txt;
    inp.value='';inp.style.height='auto';
    var tid='tmp'+Date.now();
    var b=document.getElementById('body');
    if(b){var el=mkB({id:tid,sender_id:MY_ID,content:orig,created_at:new Date().toISOString().replace('T',' ').substr(0,19),username:'',avatar:''});el.style.opacity='.5';b.appendChild(el);scrollEnd(true);}
    try{
        var r=await fetch(BASE+'/api/messages.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'send',c:CID,content:orig})});
        var d=await r.json();
        if(d.id){var el=document.getElementById('msg-'+tid);if(el){el.id='msg-'+d.id;el.setAttribute('data-id',d.id);el.style.opacity='1';}lastId=d.id;}
        else{var el=document.getElementById('msg-'+tid);if(el)el.remove();showToast('Gönderilemedi','error');}
    }catch(e){var el=document.getElementById('msg-'+tid);if(el)el.remove();showToast('Bağlantı hatası','error');}
    btn.disabled=false;
    document.getElementById('inp').focus();
}
function mkB(m){
    var mine=m.sender_id==MY_ID,av=mine?MY_AV:OAV,t=(m.created_at||'').substr(11,5)||new Date().toTimeString().substr(0,5);
    var txt=(m.content||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
    var r=document.createElement('div');
    r.className='mp-row'+(mine?' mine':'');r.id='msg-'+m.id;r.setAttribute('data-id',m.id);
    r.innerHTML=(!mine?'<img src="'+av+'" class="mp-av" alt="">':'')+
    '<div class="mp-wrap"><div class="mp-bubble '+(mine?'mine':'theirs')+'" data-txt="'+((m.content||'').replace(/"/g,'&quot;'))+'" data-author="'+(m.username||'')+'">'+txt+
    '<div class="mp-acts"><button class="mp-act" onclick="qMsg(this)" title="Alıntı"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 2v4c0 1.25.75 2 2 2h1c1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 2v4c0 1.25.75 2 2 2h1c1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/></svg></button>'+
    '<button class="mp-act" onclick="cpMsg(this)" title="Kopyala"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button></div></div>'+
    '<div class="mp-meta"><span class="mp-time">'+t+'</span>'+
    (mine?'<button class="mp-like" onclick="lMsg('+m.id+',this)" data-liked="0"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></button>':'')+
    '</div></div>';
    return r;
}
async function poll(){
    if(!CID)return;
    try{
        var r=await fetch(BASE+'/api/messages.php?action=poll&c='+CID+'&last_id='+lastId);
        var d=await r.json();
        if(d.messages&&d.messages.length){
            var b=document.getElementById('body');
            var ab=b.scrollHeight-b.scrollTop-b.clientHeight<80;
            d.messages.forEach(function(m){if(!document.getElementById('msg-'+m.id)){b.appendChild(mkB(m));lastId=Math.max(lastId,parseInt(m.id));}});
            if(ab)scrollEnd(true);
        }
        if(d.typing){
            var tn=document.getElementById('tname');if(tn)tn.textContent=d.typing.username;
            var ti=document.getElementById('typi');if(ti){ti.classList.add('show');clearTimeout(window._th);window._th=setTimeout(function(){ti.classList.remove('show')},3000);}
        }
    }catch(e){}
}
if(CID){pt=setInterval(poll,2500);}
document.addEventListener('visibilitychange',function(){if(document.hidden){clearInterval(pt);}else if(CID){poll();pt=setInterval(poll,2500);}});
function sTyp(){if(!CID)return;clearTimeout(tt);fetch(BASE+'/api/messages.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'typing',c:CID})});tt=setTimeout(function(){},2000);}
function qMsg(btn){var b=btn.closest('.mp-bubble');qd={t:b.getAttribute('data-txt').substr(0,80),a:b.getAttribute('data-author')};document.getElementById('qtx').textContent=qd.a+': '+qd.t;document.getElementById('qp').classList.add('show');document.getElementById('inp').focus();}
function cQ(){qd=null;document.getElementById('qp').classList.remove('show');}
function cpMsg(btn){var b=btn.closest('.mp-bubble');navigator.clipboard.writeText(b.getAttribute('data-txt')).then(function(){showToast('Kopyalandı','success',1500);});}
function lMsg(id,btn){var l=btn.getAttribute('data-liked')==='1';btn.setAttribute('data-liked',l?'0':'1');btn.classList.toggle('liked',!l);var s=btn.querySelector('svg');if(s)s.setAttribute('fill',l?'none':'currentColor');if(!l){btn.style.transform='scale(1.5)';setTimeout(function(){btn.style.transform='scale(1)'},180);showToast('❤️','success',800);}}
if(window.innerWidth<=768&&CID){var sb=document.getElementById('sb');if(sb)sb.classList.add('gone');}
document.addEventListener('keydown',function(e){if(e.key==='Escape')document.getElementById('ndm').style.display='none';});
</script>
<?php require_once 'includes/footer.php'; ?>
