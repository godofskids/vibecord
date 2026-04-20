<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$group_id = intval($_POST['group_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if(!$group_id){
    echo json_encode(["error"=>"Group ID required"]);
    exit;
}

// Check if user is a member
$check = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$check->bind_param("ii", $group_id, $user_id);
$check->execute();
$member = $check->get_result()->fetch_assoc();

if(!$member){
    echo json_encode(["error"=>"You are not a member of this group"]);
    exit;
}

// If user is the only admin, they can't leave - they must transfer ownership first
if($member['role'] === 'admin'){
    $adminCount = $conn->prepare("SELECT COUNT(*) as cnt FROM group_members WHERE group_id = ? AND role = 'admin'");
    $adminCount->bind_param("i", $group_id);
    $adminCount->execute();
    $count = $adminCount->get_result()->fetch_assoc()['cnt'];
    
    // TODO: Delete the group chat if you're the only user and you leave
    /*if($count == 1){
        echo json_encode(["error"=>"You are the only admin. Transfer ownership before leaving."]);
        exit;
    }*/
}

// Check if this is the last member - if so, delete the group
$memberCount = $conn->prepare("SELECT COUNT(*) as cnt FROM group_members WHERE group_id = ?");
$memberCount->bind_param("i", $group_id);
$memberCount->execute();
$count = $memberCount->get_result()->fetch_assoc()['cnt'];

if($count == 1){
    // Delete all messages first
    $conn->query("DELETE FROM group_messages WHERE group_id = $group_id");
    // Delete all members
    $conn->query("DELETE FROM group_members WHERE group_id = $group_id");
    // Delete the group
    $conn->query("DELETE FROM group_chats WHERE id = $group_id");
    echo json_encode(["success"=>true, "deleted"=>true, "message"=>"Group deleted"]);
    exit;
}

// Remove member
$stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();

echo json_encode(["success"=>true, "message"=>"Left the group"]);
?>