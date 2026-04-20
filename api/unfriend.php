<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$friend_id = intval($_POST['friend_id'] ?? 0);

if(!$friend_id){
    echo json_encode(['success'=>false,'error'=>'No friend_id provided']);
    exit;
}

// Remove friendship (either direction)
$stmt = $conn->prepare("
    DELETE FROM friends 
    WHERE (sender_id = ? AND receiver_id = ?) 
       OR (sender_id = ? AND receiver_id = ?)
");
$stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
$stmt->execute();

echo json_encode(['success'=>true]);