<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$group_id = intval($_POST['group_id'] ?? 0);
$user_id = intval($_POST['user_id'] ?? 0);
$inviter_id = $_SESSION['user_id'];

if(!$group_id || !$user_id){
    echo json_encode(["error"=>"Group ID and User ID required"]);
    exit;
}

// Check if inviter is admin
$checkAdmin = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ? AND role = 'admin'");
$checkAdmin->bind_param("ii", $group_id, $inviter_id);
$checkAdmin->execute();
if($checkAdmin->get_result()->num_rows === 0){
    echo json_encode(["error"=>"Only admins can invite users"]);
    exit;
}

// Check if user is already a member
$checkMember = $conn->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
$checkMember->bind_param("ii", $group_id, $user_id);
$checkMember->execute();
if($checkMember->get_result()->num_rows > 0){
    echo json_encode(["error"=>"User is already a member"]);
    exit;
}

// Check if invitation already exists
$checkInvite = $conn->prepare("SELECT id FROM group_invitations WHERE group_id = ? AND user_id = ? AND status = 'pending'");
$checkInvite->bind_param("ii", $group_id, $user_id);
$checkInvite->execute();
if($checkInvite->get_result()->num_rows > 0){
    echo json_encode(["error"=>"Invitation already sent"]);
    exit;
}

// Check member count limit
$group = $conn->prepare("SELECT max_members, (SELECT COUNT(*) FROM group_members WHERE group_id = ?) as current_count FROM group_chats WHERE id = ?");
$group->bind_param("ii", $group_id, $group_id);
$group->execute();
$groupInfo = $group->get_result()->fetch_assoc();
if($groupInfo['current_count'] >= $groupInfo['max_members']){
    echo json_encode(["error"=>"Group is full"]);
    exit;
}

// Create invitation
$stmt = $conn->prepare("INSERT INTO group_invitations (group_id, user_id) VALUES (?, ?)");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();

echo json_encode(["success"=>true, "message"=>"Invitation sent"]);
?>