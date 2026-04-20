<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$group_id = intval($_POST['group_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$sender_id = $_SESSION['user_id'];

if(!$group_id){
    echo json_encode(["error"=>"Group ID required"]);
    exit;
}

if(empty($message)){
    echo json_encode(["error"=>"Message cannot be empty"]);
    exit;
}

// Check if user is a member
$check = $conn->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
$check->bind_param("ii", $group_id, $sender_id);
$check->execute();
if($check->get_result()->num_rows === 0){
    echo json_encode(["error"=>"Not a member of this group"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO group_messages (group_id, sender_id, message) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $group_id, $sender_id, $message);
$stmt->execute();

echo json_encode([
    "success"=>true,
    "message_id"=>$stmt->insert_id
]);
?>