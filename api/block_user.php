<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$block_id = intval($_POST['block_id'] ?? 0);

if(!$block_id){
    echo json_encode(['success'=>false,'error'=>'No block_id provided']);
    exit;
}

// Prevent blocking yourself
if($block_id == $user_id){
    echo json_encode(['success'=>false,'error'=>'Cannot block yourself']);
    exit;
}

// Check if there's an existing relationship
$check = $conn->prepare("
    SELECT id FROM friends 
    WHERE (sender_id = ? AND receiver_id = ?) 
       OR (sender_id = ? AND receiver_id = ?)
");
$check->bind_param("iiii", $user_id, $block_id, $block_id, $user_id);
$check->execute();
$res = $check->get_result();

if($res->num_rows > 0){
    // Update existing record to blocked
    $stmt = $conn->prepare("
        UPDATE friends SET status = 'blocked'
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
    ");
    $stmt->bind_param("iiii", $user_id, $block_id, $block_id, $user_id);
    $stmt->execute();
} else {
    // Create new blocked record
    $stmt = $conn->prepare("INSERT INTO friends (sender_id, receiver_id, status) VALUES (?, ?, 'blocked')");
    $stmt->bind_param("ii", $user_id, $block_id);
    $stmt->execute();
}

echo json_encode(['success'=>true]);