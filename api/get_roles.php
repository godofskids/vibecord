<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode([]);
    exit;
}

$server_id = intval($_GET['server_id'] ?? 0);

if(!$server_id){
    echo json_encode([]);
    exit;
}

// Get all roles for the server
$stmt = $conn->prepare("SELECT id, name, color, position, permissions FROM roles WHERE server_id = ? ORDER BY position ASC");
$stmt->bind_param("i", $server_id);
$stmt->execute();
$res = $stmt->get_result();

$roles = [];
while($row = $res->fetch_assoc()){
    $row['permissions'] = json_decode($row['permissions'], true);
    $roles[] = $row;
}

echo json_encode($roles);