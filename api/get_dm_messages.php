<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];
$friend_id = $_GET['friend_id'] ?? 0;

// Only messages between the two users - include sender_id
$stmt = $conn->prepare("
    SELECT m.id, m.message, m.created_at, m.sender_id, u.username 
    FROM dm_messages m
    JOIN users u ON u.id = m.sender_id
    WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)
    ORDER BY m.created_at ASC
");
$stmt->bind_param("iiii", $user_id,$friend_id,$friend_id,$user_id);
$stmt->execute();
$res = $stmt->get_result();
$messages = [];
while($row = $res->fetch_assoc()){
    $messages[] = $row;
}

echo json_encode($messages);