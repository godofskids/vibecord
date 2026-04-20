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
$check = $conn->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
$check->bind_param("ii", $group_id, $user_id);
$check->execute();
if($check->get_result()->num_rows === 0){
    echo json_encode(["error"=>"Not a member of this group"]);
    exit;
}

// Get group info
$group = $conn->prepare("SELECT id, name, creator_id FROM group_chats WHERE id = ?");
$group->bind_param("i", $group_id);
$group->execute();
$groupResult = $group->get_result()->fetch_assoc();

// Get messages with sender info
$stmt = $conn->prepare("
    SELECT gm.id, gm.message, gm.created_at, u.id as sender_id, u.username as sender_name
    FROM group_messages gm
    JOIN users u ON gm.sender_id = u.id
    WHERE gm.group_id = ?
    ORDER BY gm.created_at ASC
    LIMIT 100
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get members
$members = $conn->prepare("
    SELECT gm.user_id, u.username, gm.role, gm.joined_at
    FROM group_members gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.group_id = ?
");
$members->bind_param("i", $group_id);
$members->execute();
$memberList = $members->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    "success"=>true,
    "group"=>$groupResult,
    "messages"=>$messages,
    "members"=>$memberList
]);
?>