<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$target_user_id = intval($_POST['user_id'] ?? 0);
$role_id = intval($_POST['role_id'] ?? 0);
$server_id = intval($_POST['server_id'] ?? 0);

if(!$target_user_id || !$role_id || !$server_id){
    echo json_encode(['success'=>false,'error'=>'Missing required fields']);
    exit;
}

// Check if user has permission to manage roles
$checkPerm = $conn->prepare("
    SELECT r.permissions
    FROM roles r
    JOIN user_roles ur ON r.id = ur.role_id
    WHERE r.server_id = ? AND ur.user_id = ?
");
$checkPerm->bind_param("ii", $server_id, $user_id);
$checkPerm->execute();
$permRes = $checkPerm->get_result();

$hasPermission = false;
while($pRow = $permRes->fetch_assoc()){
    $perms = json_decode($pRow['perms'], true);
    if(isset($perms['manage_roles']) && $perms['manage_roles']){
        $hasPermission = true;
        break;
    }
}

// Also allow server owner
$ownerCheck = $conn->prepare("SELECT owner_id FROM servers WHERE id = ?");
$ownerCheck->bind_param("i", $server_id);
$ownerCheck->execute();
$ownerRes = $ownerCheck->get_result();
if($ownerRow = $ownerRes->fetch_assoc()){
    if($ownerRow['owner_id'] == $user_id){
        $hasPermission = true;
    }
}

if(!$hasPermission){
    echo json_encode(['success'=>false,'error'=>'No permission to manage roles']);
    exit;
}

// Check if target user is a member of the server
$memberCheck = $conn->prepare("SELECT id FROM server_members WHERE server_id = ? AND user_id = ?");
$memberCheck->bind_param("ii", $server_id, $target_user_id);
$memberCheck->execute();
$memberRes = $memberCheck->get_result();

if($memberRes->num_rows == 0){
    echo json_encode(['success'=>false,'error'=>'User is not a member of this server']);
    exit;
}

// Assign role
$stmt = $conn->prepare("INSERT IGNORE INTO user_roles(user_id, role_id) VALUES(?, ?)");
$stmt->bind_param("ii", $target_user_id, $role_id);
$stmt->execute();

echo json_encode(['success'=>true]);