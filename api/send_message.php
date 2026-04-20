<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$channel_id = intval($_POST['channel_id']);
$msg = trim($_POST['message'] ?? '');

if(!$msg){
    echo json_encode(['success'=>false,'error'=>'Message is empty']);
    exit;
}

$check = $conn->prepare("
    SELECT servers.id
    FROM channels
    JOIN servers ON channels.server_id = servers.id
    JOIN server_members ON server_members.server_id = servers.id
    WHERE channels.id=? AND server_members.user_id=?
");
$check->bind_param("ii", $channel_id, $user_id);
$check->execute();
$res = $check->get_result();

if($res->num_rows == 0){
    echo json_encode(['success'=>false,'error'=>'No permission']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO messages(channel_id, user_id, message) VALUES(?, ?, ?)");
$stmt->bind_param("iis", $channel_id, $user_id, $msg);
$stmt->execute();

echo json_encode(['success'=>true]);