<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$group_id = intval($_POST['group_id'] ?? 0);
$new_owner_id = intval($_POST['new_owner_id'] ?? 0);
$current_user_id = $_SESSION['user_id'];

if(!$group_id || !$new_owner_id){
    echo json_encode(["error"=>"Group ID and new owner ID required"]);
    exit;
}

// Check if current user is admin
$checkAdmin = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ? AND role = 'admin'");
$checkAdmin->bind_param("ii", $group_id, $current_user_id);
$checkAdmin->execute();
if($checkAdmin->get_result()->num_rows === 0){
    echo json_encode(["error"=>"Only admins can transfer ownership"]);
    exit;
}

// Prevent transferring to self
if($new_owner_id == $current_user_id){
    echo json_encode(["error"=>"You cannot transfer ownership to yourself"]);
    exit;
}

// Check if new owner is a member
$checkMember = $conn->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
$checkMember->bind_param("ii", $group_id, $new_owner_id);
$checkMember->execute();
if($checkMember->get_result()->num_rows === 0){
    echo json_encode(["error"=>"User is not a member of this group"]);
    exit;
}

// Update current admin to member
$stmt1 = $conn->prepare("UPDATE group_members SET role = 'member' WHERE group_id = ? AND user_id = ?");
$stmt1->bind_param("ii", $group_id, $current_user_id);
$stmt1->execute();

// Update new owner to admin
$stmt2 = $conn->prepare("UPDATE group_members SET role = 'admin' WHERE group_id = ? AND user_id = ?");
$stmt2->bind_param("ii", $group_id, $new_owner_id);
$stmt2->execute();

echo json_encode(["success"=>true, "message"=>"Ownership transferred"]);
?>