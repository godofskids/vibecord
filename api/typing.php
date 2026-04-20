<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['is_typing'=>false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$target_id = intval($_POST['target_id'] ?? 0);
$typing = $_POST['typing'] ?? 'false';

if(!$target_id){
    echo json_encode(['success'=>false,'error'=>'No target user']);
    exit;
}

$isTyping = ($typing === 'true' || $typing === true) ? 1 : 0;

// Upsert typing status
$stmt = $conn->prepare("
    INSERT INTO typing_status (user_id, target_user_id, is_typing)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE is_typing = VALUES(is_typing), updated_at = NOW()
");
$stmt->bind_param("iii", $user_id, $target_id, $isTyping);
$stmt->execute();

echo json_encode(['success'=>true]);