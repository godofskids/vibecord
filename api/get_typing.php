<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['typing'=>false, 'username'=>'']);
    exit;
}

$user_id = $_SESSION['user_id'];
$friend_id = intval($_GET['friend_id'] ?? 0);

if(!$friend_id){
    echo json_encode(['typing'=>false, 'username'=>'']);
    exit;
}

// Check if the other user is typing
$stmt = $conn->prepare("
    SELECT is_typing, user_id
    FROM typing_status
    WHERE user_id = ? AND target_user_id = ?
");
$stmt->bind_param("ii", $friend_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

$typing = false;
$typingUserId = null;

if($row = $res->fetch_assoc()){
    if($row['is_typing']){
        $typing = true;
        $typingUserId = $row['user_id'];
    }
}

// Get username of typing user
$username = '';
if($typing && $typingUserId){
    $stmt2 = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt2->bind_param("i", $typingUserId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if($row2 = $res2->fetch_assoc()){
        $username = $row2['username'];
    }
}

echo json_encode(['typing'=>$typing, 'username'=>$username]);