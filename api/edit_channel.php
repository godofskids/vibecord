<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$channel_id = intval($_POST['channel_id'] ?? 0);
$name = $_POST['name'] ?? '';

if(!$channel_id || !$name){
    echo json_encode(['success'=>false,'error'=>'Channel ID and name required']);
    exit;
}

// Verify user is a member of the server that owns this channel
$check = $conn->prepare("
    SELECT servers.owner_id
    FROM channels
    JOIN servers ON channels.server_id = servers.id
    WHERE channels.id = ?
");
$check->bind_param("i", $channel_id);
$check->execute();
$res = $check->get_result();

if($row = $res->fetch_assoc()){
    if($row['owner_id'] != $_SESSION['user_id']){
        echo json_encode(['success'=>false,'error'=>'Only server owner can edit channels']);
        exit;
    }
} else {
    echo json_encode(['success'=>false,'error'=>'Channel not found']);
    exit;
}

$stmt = $conn->prepare("UPDATE channels SET name = ? WHERE id = ?");
$stmt->bind_param("si", $name, $channel_id);
$stmt->execute();

echo json_encode(['success'=>true]);