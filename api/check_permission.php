<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['has_permission'=>false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$server_id = intval($_GET['server_id'] ?? 0);
$permission = $_GET['permission'] ?? '';

if(!$server_id || !$permission){
    echo json_encode(['has_permission'=>false]);
    exit;
}

// Check if user is server owner
$ownerCheck = $conn->prepare("SELECT owner_id FROM servers WHERE id = ?");
$ownerCheck->bind_param("i", $server_id);
$ownerCheck->execute();
$ownerRes = $ownerCheck->get_result();

if($ownerRow = $ownerRes->fetch_assoc()){
    if($ownerRow['owner_id'] == $user_id){
        echo json_encode(['has_permission'=>true]);
        exit;
    }
}

// Get user's role permissions
$stmt = $conn->prepare("
    SELECT r.permissions
    FROM roles r
    JOIN user_roles ur ON r.id = ur.role_id
    WHERE ur.user_id = ? AND r.server_id = ?
");
$stmt->bind_param("ii", $user_id, $server_id);
$stmt->execute();
$res = $stmt->get_result();

$hasPermission = false;
while($row = $res->fetch_assoc()){
    $perms = json_decode($row['permissions'], true);
    if(isset($perms[$permission]) && $perms[$permission]){
        $hasPermission = true;
        break;
    }
}

echo json_encode(['has_permission'=>$hasPermission]);