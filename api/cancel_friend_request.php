<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$target_id = intval($_POST['target_id'] ?? 0);

if(!$target_id){
    echo json_encode(['success'=>false,'error'=>'No target user']);
    exit;
}

// Delete the pending friend request that the current user sent
$stmt = $conn->prepare("DELETE FROM friends WHERE sender_id=? AND receiver_id=? AND status='pending'");
$stmt->bind_param("ii", $user_id, $target_id);
$stmt->execute();

if($stmt->affected_rows > 0){
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'error'=>'No pending request found']);
}