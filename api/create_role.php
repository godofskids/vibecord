<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$server_id = intval($_GET['server_id'] ?? 0);

if(!$server_id){
    echo json_encode(['success'=>false,'error'=>'No server_id']);
    exit;
}

// Check if user is server owner
$check = $conn->prepare("SELECT owner_id FROM servers WHERE id = ?");
$check->bind_param("i", $server_id);
$check->execute();
$res = $check->get_result();

if($row = $res->fetch_assoc()){
    if($row['owner_id'] != $user_id){
        echo json_encode(['success'=>false,'error'=>'Only server owner can create roles']);
        exit;
    }
} else {
    echo json_encode(['success'=>false,'error'=>'Server not found']);
    exit;
}

$name = $_POST['name'] ?? '';
$color = $_POST['color'] ?? '#99AAB5';

if(!$name){
    echo json_encode(['success'=>false,'error'=>'Role name required']);
    exit;
}

// Get max position
$posStmt = $conn->prepare("SELECT MAX(position) as max_pos FROM roles WHERE server_id = ?");
$posStmt->bind_param("i", $server_id);
$posStmt->execute();
$posRes = $posStmt->get_result();
$pos = 0;
if($pRow = $posRes->fetch_assoc()){
    $pos = intval($pRow['max_pos']) + 1;
}

// Default permissions (basic)
$permissions = json_encode([
    'view_channels' => true,
    'send_messages' => true,
    'manage_messages' => false,
    'manage_channels' => false,
    'manage_roles' => false,
    'kick_members' => false,
    'ban_members' => false
]);

$stmt = $conn->prepare("INSERT INTO roles(server_id, name, color, position, permissions) VALUES(?, ?, ?, ?, ?)");
$stmt->bind_param("issis", $server_id, $name, $color, $pos, $permissions);
$stmt->execute();

echo json_encode(['success'=>true, 'role_id'=>$conn->insert_id]);