<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$group_id = intval($_POST['group_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$user_id = $_SESSION['user_id'];

if(!$group_id){
    echo json_encode(["error"=>"Group ID required"]);
    exit;
}

if(empty($name)){
    echo json_encode(["error"=>"Group name required"]);
    exit;
}

// Check if user is admin (owner or admin)
$checkAdmin = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$checkAdmin->bind_param("ii", $group_id, $user_id);
$checkAdmin->execute();
$member = $checkAdmin->get_result()->fetch_assoc();
if(!$member || $member['role'] !== 'admin'){
    echo json_encode(["error"=>"Only admins can rename the group"]);
    exit;
}

$stmt = $conn->prepare("UPDATE group_chats SET name = ? WHERE id = ?");
$stmt->bind_param("si", $name, $group_id);
$stmt->execute();

echo json_encode(["success"=>true, "message"=>"Group renamed"]);
?>