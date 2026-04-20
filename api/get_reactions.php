<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$message_ids = $_POST['message_ids'] ?? '';
$message_type = $_POST['message_type'] ?? 'group'; // 'group' or 'dm'

if(empty($message_ids)){
    echo json_encode(["success"=>true, "reactions"=>[]]);
    exit;
}

$ids = array_map('intval', explode(',', $message_ids));
$ids = array_filter($ids);

if(empty($ids)){
    echo json_encode(["success"=>true, "reactions"=>[]]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$stmt = $conn->prepare("
    SELECT mr.message_id, mr.emoji, u.username 
    FROM message_reactions mr
    JOIN users u ON mr.user_id = u.id
    WHERE mr.message_id IN ($placeholders) AND mr.message_type = ?
");
$stmt->bind_param($types . 's', ...array_merge($ids, [$message_type]));
$stmt->execute();
$reactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group by message_id
$grouped = [];
foreach($reactions as $r){
    if(!isset($grouped[$r['message_id']])){
        $grouped[$r['message_id']] = [];
    }
    $grouped[$r['message_id']][] = ['emoji'=>$r['emoji'], 'username'=>$r['username']];
}

echo json_encode(["success"=>true, "reactions"=>$grouped]);
?>