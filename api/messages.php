<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');
header('Cache-Control: no-cache');

if (!isLoggedIn()) { echo json_encode(['error'=>'auth']); exit; }
$user = currentUser();

$raw    = file_get_contents('php://input');
$json   = json_decode($raw, true);
$action = $json['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'send') {
    $convoId = (int)($json['c'] ?? 0);
    $content = trim($json['content'] ?? '');
    if (!$convoId || $content === '') { echo json_encode(['error'=>'empty']); exit; }
    $convo = db()->fetchOne("SELECT * FROM dm_conversations WHERE id=? AND (user1_id=? OR user2_id=?)", [$convoId,$user['id'],$user['id']],'iii');
    if (!$convo) { echo json_encode(['error'=>'notfound']); exit; }
    $otherId = $convo['user1_id']==$user['id'] ? $convo['user2_id'] : $convo['user1_id'];
    $msgId   = db()->insert("INSERT INTO dm_messages (conversation_id,sender_id,content) VALUES (?,?,?)", [$convoId,$user['id'],$content],'iis');
    db()->query("UPDATE dm_conversations SET last_message_id=?,last_message_at=NOW() WHERE id=?", [$msgId,$convoId],'ii');
    createNotification($otherId,$user['id'],'reply',$convoId,'dm',$user['username'].' size mesaj gönderdi');
    echo json_encode(['id'=>$msgId,'sender_id'=>$user['id'],'content'=>$content,'created_at'=>date('Y-m-d H:i:s'),'username'=>$user['username'],'avatar'=>$user['avatar']??'']);
    exit;
}

if ($action === 'poll') {
    $convoId = (int)($_GET['c'] ?? 0);
    $lastId  = (int)($_GET['last_id'] ?? 0);
    if (!$convoId) { echo json_encode(['messages'=>[]]); exit; }
    $convo = db()->fetchOne("SELECT * FROM dm_conversations WHERE id=? AND (user1_id=? OR user2_id=?)", [$convoId,$user['id'],$user['id']],'iii');
    if (!$convo) { echo json_encode(['messages'=>[]]); exit; }
    $msgs = db()->fetchAll("SELECT dm.id,dm.sender_id,dm.content,dm.created_at,dm.is_read,u.username,u.avatar FROM dm_messages dm JOIN users u ON u.id=dm.sender_id WHERE dm.conversation_id=? AND dm.id>? ORDER BY dm.created_at ASC", [$convoId,$lastId],'ii');
    db()->query("UPDATE dm_messages SET is_read=1 WHERE conversation_id=? AND sender_id!=?", [$convoId,$user['id']],'ii');
    $otherId     = $convo['user1_id']==$user['id'] ? $convo['user2_id'] : $convo['user1_id'];
    $tmpFile     = sys_get_temp_dir().'/typing_'.$convoId.'_'.$otherId.'.json';
    $typingData  = null;
    if (file_exists($tmpFile) && (time()-filemtime($tmpFile))<3) {
        $typingData = json_decode(file_get_contents($tmpFile),true);
    }
    echo json_encode(['messages'=>$msgs,'typing'=>$typingData]);
    exit;
}

if ($action === 'typing') {
    $convoId = (int)($json['c'] ?? $_POST['c'] ?? 0);
    if ($convoId) {
        $tmpFile = sys_get_temp_dir().'/typing_'.$convoId.'_'.$user['id'].'.json';
        file_put_contents($tmpFile, json_encode(['username'=>$user['username'],'avatar'=>getAvatar($user),'time'=>time()]));
    }
    echo json_encode(['ok'=>true]);
    exit;
}

echo json_encode(['error'=>'unknown']);
