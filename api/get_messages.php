<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode([]);
    exit;
}

$channel_id = intval($_GET['channel_id']);
$user_id = $_SESSION['user_id'];

// Verify user has access to this channel (via server membership)
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
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT m.id, m.message, m.created_at, u.username
    FROM messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.channel_id=?
    ORDER BY m.id ASC
");
$stmt->bind_param("i", $channel_id);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while($row = $res->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);