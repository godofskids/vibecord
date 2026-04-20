<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$name = $_POST['name'] ?? '';
if(!$name){
    echo json_encode(['success'=>false,'error'=>'Server name required']);
    exit;
}

$owner = $_SESSION['user_id'];

// Insert server
$stmt = $conn->prepare("INSERT INTO servers(name,owner_id) VALUES(?,?)");
$stmt->bind_param("si", $name, $owner);
$stmt->execute();
$server_id = $conn->insert_id;

// Add owner as a member
$stmt2 = $conn->prepare("INSERT INTO server_members(server_id, user_id) VALUES(?,?)");
$stmt2->bind_param("ii", $server_id, $owner);
$stmt2->execute();

// Create a default #general channel
$stmt3 = $conn->prepare("INSERT INTO channels(server_id, name) VALUES(?, 'general')");
$stmt3->bind_param("i", $server_id);
$stmt3->execute();

echo json_encode(['success'=>true, 'server_id'=>$server_id]);