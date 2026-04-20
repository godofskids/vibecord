<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$call_id = $_POST['call_id'] ?? null;
$target_id = $_POST['target_id'] ?? null;
$call_type = $_POST['call_type'] ?? 'voice';
$action = $_POST['action'] ?? 'initiate';

if(!$call_id || !$target_id){
    echo json_encode(['success'=>false,'error'=>'Missing call_id or target_id']);
    exit;
}

if($action === 'initiate'){
    // Create a new call record
    $stmt = $conn->prepare("INSERT INTO `calls` (call_id, caller_id, target_id, call_type, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("siis", $call_id, $user_id, $target_id, $call_type);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success'=>true,'message'=>'Call initiated']);
    
} elseif($action === 'answer'){
    // Update call status to answered
    $stmt = $conn->prepare("UPDATE `calls` SET status = 'active' WHERE call_id = ? AND target_id = ?");
    $stmt->bind_param("si", $call_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success'=>true,'message'=>'Call answered']);
    
} elseif($action === 'reject'){
    // Update call status to rejected
    $stmt = $conn->prepare("UPDATE `calls` SET status = 'rejected' WHERE call_id = ? AND target_id = ?");
    $stmt->bind_param("si", $call_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success'=>true,'message'=>'Call rejected']);
    
} elseif($action === 'end'){
    // Update call status to ended
    $stmt = $conn->prepare("UPDATE `calls` SET status = 'ended' WHERE call_id = ? AND (caller_id = ? OR target_id = ?)");
    $stmt->bind_param("sii", $call_id, $user_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success'=>true,'message'=>'Call ended']);
}