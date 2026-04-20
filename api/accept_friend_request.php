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

$stmt = $conn->prepare("UPDATE friends SET status='accepted' WHERE sender_id=? AND receiver_id=? AND status='pending'");
$stmt->bind_param("ii", $friend_id, $user_id);
$stmt->execute();

echo json_encode(['success'=>true]);