<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$call_id = $_GET['call_id'] ?? null;

if(!$call_id){
    echo json_encode(['success'=>false,'error'=>'Missing call_id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM `calls` WHERE call_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("s", $call_id);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()){
    echo json_encode(['success'=>true,'call'=>$row]);
} else {
    echo json_encode(['success'=>false,'error'=>'Call not found']);
}

$stmt->close();