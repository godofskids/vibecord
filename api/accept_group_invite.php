<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$invite_id = intval($_POST['invite_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if(!$invite_id){
    echo json_encode(["error"=>"Invitation ID required"]);
    exit;
}

// Get the invitation
$invite = $conn->prepare("SELECT group_id, user_id FROM group_invitations WHERE id = ? AND user_id = ? AND status = 'pending'");
$invite->bind_param("ii", $invite_id, $user_id);
$invite->execute();
$inviteData = $invite->get_result()->fetch_assoc();

if(!$inviteData){
    echo json_encode(["error"=>"Invitation not found"]);
    exit;
}

// Check member count limit
$group = $conn->prepare("SELECT max_members, (SELECT COUNT(*) FROM group_members WHERE group_id = ?) as current_count FROM group_chats WHERE id = ?");
$group->bind_param("ii", $inviteData['group_id'], $inviteData['group_id']);
$group->execute();
$groupInfo = $group->get_result()->fetch_assoc();
if($groupInfo['current_count'] >= $groupInfo['max_members']){
    // Update invitation status to declined
    $conn->query("UPDATE group_invitations SET status = 'declined' WHERE id = $invite_id");
    echo json_encode(["error"=>"Group is full"]);
    exit;
}

// Add user to group
$stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
$stmt->bind_param("ii", $inviteData['group_id'], $user_id);
$stmt->execute();

// Update invitation status
$conn->query("UPDATE group_invitations SET status = 'accepted' WHERE id = $invite_id");

echo json_encode(["success"=>true, "message"=>"Joined group"]);
?>