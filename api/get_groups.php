<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get groups the user is a member of
$stmt = $conn->prepare("
    SELECT gc.id, gc.name, gc.creator_id, gc.max_members, gm.role, gm.joined_at,
           (SELECT COUNT(*) FROM group_members WHERE group_id = gc.id) as member_count
    FROM group_chats gc
    JOIN group_members gm ON gc.id = gm.group_id
    WHERE gm.user_id = ?
    ORDER BY gm.joined_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get pending invitations
$invites = $conn->prepare("
    SELECT gi.id, gi.group_id, gi.created_at, gc.name as group_name, u.username as inviter_name
    FROM group_invitations gi
    JOIN group_chats gc ON gi.group_id = gc.id
    JOIN users u ON gc.creator_id = u.id
    WHERE gi.user_id = ? AND gi.status = 'pending'
    ORDER BY gi.created_at DESC
");
$invites->bind_param("i", $user_id);
$invites->execute();
$invitations = $invites->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    "success"=>true,
    "groups"=>$groups,
    "invitations"=>$invitations
]);
?>