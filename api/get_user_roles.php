<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode([]);
    exit;
}

$user_id = intval($_GET['user_id'] ?? $_SESSION['user_id']);
$server_id = intval($_GET['server_id'] ?? 0);

if(!$server_id){
    echo json_encode([]);
    exit;
}

// Get user's roles in this server
$stmt = $conn->prepare("
    SELECT r.id, r.name, r.color, r.permissions
    FROM roles r
    JOIN user_roles ur ON r.id = ur.role_id
    WHERE ur.user_id = ? AND r.server_id = ?
");
$stmt->bind_param("ii", $user_id, $server_id);
$stmt->execute();
$res = $stmt->get_result();

$roles = [];
while($row = $res->fetch_assoc()){
    $row['permissions'] = json_decode($row['permissions'], true);
    $roles[] = $row;
}

echo json_encode($roles);