<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode([]);
    exit;
}

$server_id = intval($_GET['server_id']);

// Verify user is a member of this server
$check = $conn->prepare("SELECT id FROM server_members WHERE server_id=? AND user_id=?");
$check->bind_param("ii", $server_id, $_SESSION['user_id']);
$check->execute();
$res = $check->get_result();

if($res->num_rows == 0){
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM channels WHERE server_id=?");
$stmt->bind_param("i", $server_id);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while($row = $res->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);