<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

// --- Ensure user is logged in ---
if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_POST['username'] ?? '';

// --- Username required ---
if(!$username){
    echo json_encode(['success'=>false, 'error'=>'Username required']);
    exit;
}

// --- Find target user ---
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$target = $res->fetch_assoc();

if(!$target){
    echo json_encode(['success'=>false,'error'=>'User not found']);
    exit;
}
$target_id = $target['id'];

// --- Prevent sending to self ---
if($target_id == $user_id){
    echo json_encode(['success'=>false,'error'=>"Can't add yourself"]);
    exit;
}

// --- Check existing request or friendship ---
$stmt = $conn->prepare("
    SELECT * FROM friends 
    WHERE (sender_id=? AND receiver_id=?) 
       OR (sender_id=? AND receiver_id=?)
");
$stmt->bind_param("iiii", $user_id,$target_id,$target_id,$user_id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows > 0){
    echo json_encode(['success'=>false,'error'=>'Already friends or request exists']);
    exit;
}

// --- Insert friend request ---
$stmt = $conn->prepare("INSERT INTO friends (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("ii", $user_id, $target_id);
$stmt->execute();

echo json_encode(['success'=>true]);