<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$friend_id = $_POST['friend_id'] ?? 0;
$message = $_POST['message'] ?? '';

if(!$message){
    echo json_encode(['success'=>false,'error'=>'Message is empty']);
    exit;
}

// Insert message into dm_messages table
$stmt = $conn->prepare("INSERT INTO dm_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $user_id, $friend_id, $message);
$stmt->execute();

echo json_encode(['success'=>true]);