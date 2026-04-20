<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$message_id = intval($_POST['message_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if(!$message_id){
    echo json_encode(["error"=>"Message ID required"]);
    exit;
}

// Check if user owns this message
$check = $conn->prepare("SELECT id FROM dm_messages WHERE id = ? AND sender_id = ?");
$check->bind_param("ii", $message_id, $user_id);
$check->execute();
if($check->get_result()->num_rows === 0){
    echo json_encode(["error"=>"You can only delete your own messages"]);
    exit;
}

// Delete the message
$stmt = $conn->prepare("DELETE FROM dm_messages WHERE id = ?");
$stmt->bind_param("i", $message_id);
$stmt->execute();

echo json_encode(["success"=>true, "message"=>"Message deleted"]);
?>