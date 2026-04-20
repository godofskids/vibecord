<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$message_id = intval($_POST['message_id'] ?? 0);
$emoji = trim($_POST['emoji'] ?? '👍');
$message_type = $_POST['message_type'] ?? 'group'; // 'group' or 'dm'
$user_id = $_SESSION['user_id'];

if(!$message_id){
    echo json_encode(["error"=>"Message ID required"]);
    exit;
}

if(empty($emoji)){
    echo json_encode(["error"=>"Emoji required"]);
    exit;
}

// Check if user already reacted with this emoji
$check = $conn->prepare("SELECT id FROM message_reactions WHERE message_id = ? AND message_type = ? AND user_id = ? AND emoji = ?");
$check->bind_param("isis", $message_id, $message_type, $user_id, $emoji);
$check->execute();
if($check->get_result()->num_rows > 0){
    // Remove reaction (toggle off)
    $stmt = $conn->prepare("DELETE FROM message_reactions WHERE message_id = ? AND message_type = ? AND user_id = ? AND emoji = ?");
    $stmt->bind_param("isis", $message_id, $message_type, $user_id, $emoji);
    $stmt->execute();
    echo json_encode(["success"=>true, "action"=>"removed"]);
} else {
    // Add reaction
    $stmt = $conn->prepare("INSERT INTO message_reactions (message_id, message_type, user_id, emoji) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $message_id, $message_type, $user_id, $emoji);
    $stmt->execute();
    echo json_encode(["success"=>true, "action"=>"added"]);
}
?>