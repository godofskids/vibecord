<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$server_id = intval($_POST['server_id'] ?? 0);
$username = $_POST['username'] ?? '';

if(!$server_id || !$username){
    echo json_encode(['success'=>false,'error'=>'Server ID and username required']);
    exit;
}

// Check if user is owner or has permission to invite
$ownerCheck = $conn->prepare("SELECT owner_id FROM servers WHERE id = ?");
$ownerCheck->bind_param("i", $server_id);
$ownerCheck->execute();
$ownerRes = $ownerCheck->get_result();

$canInvite = false;
if($ownerRow = $ownerRes->fetch_assoc()){
    if($ownerRow['owner_id'] == $user_id){
        $canInvite = true;
    }
}

if(!$canInvite){
    echo json_encode(['success'=>false,'error'=>'No permission to invite members']);
    exit;
}

// Find user by username
$userStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$userStmt->bind_param("s", $username);
$userStmt->execute();
$userRes = $userStmt->get_result();

if($row = $userRes->fetch_assoc()){
    $target_id = $row['id'];
    
    // Check if already a member
    $memberCheck = $conn->prepare("SELECT id FROM server_members WHERE server_id = ? AND user_id = ?");
    $memberCheck->bind_param("ii", $server_id, $target_id);
    $memberCheck->execute();
    $memberRes = $memberCheck->get_result();
    
    if($memberRes->num_rows > 0){
        echo json_encode(['success'=>false,'error'=>'User is already a member']);
        exit;
    }
    
    // Add to server
    $addStmt = $conn->prepare("INSERT INTO server_members(server_id, user_id) VALUES(?, ?)");
    $addStmt->bind_param("ii", $server_id, $target_id);
    $addStmt->execute();
    
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'error'=>'User not found']);
}