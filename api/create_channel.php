<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$name = $_POST['name'] ?? '';
$server_id = intval($_POST['server_id'] ?? 0);

if(!$name || !$server_id){
    echo json_encode(['success'=>false,'error'=>'Name and server_id required']);
    exit;
}

// Verify user is a member of this server
$check = $conn->prepare("SELECT id FROM server_members WHERE server_id=? AND user_id=?");
$check->bind_param("ii", $server_id, $_SESSION['user_id']);
$check->execute();
$res = $check->get_result();

if($res->num_rows == 0){
    echo json_encode(['success'=>false,'error'=>'Not a member of this server']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO channels(server_id, name) VALUES(?, ?)");
$stmt->bind_param("is", $server_id, $name);
$stmt->execute();

echo json_encode(['success'=>true]);