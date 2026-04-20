<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$name = trim($_POST['name'] ?? '');
$maxMembers = intval($_POST['max_members'] ?? 10);
$creator_id = $_SESSION['user_id'];

if(empty($name)){
    echo json_encode(["error"=>"Group name required"]);
    exit;
}

// Default limit is 10, warn if more
$warning = null;
if($maxMembers > 10){
    $warning = "Warning: Large groups may impact performance. Consider keeping it under 10.";
    $maxMembers = min($maxMembers, 100); // Hard cap at 100
}

$stmt = $conn->prepare("INSERT INTO group_chats (name, creator_id, max_members) VALUES (?, ?, ?)");
$stmt->bind_param("sii", $name, $creator_id, $maxMembers);
$stmt->execute();
$group_id = $stmt->insert_id;

// Add creator as admin
$stmt2 = $conn->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'admin')");
$stmt2->bind_param("ii", $group_id, $creator_id);
$stmt2->execute();

echo json_encode([
    "success"=>true, 
    "group_id"=>$group_id,
    "warning"=>$warning
]);
?>