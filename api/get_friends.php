<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['friends'=>[], 'pending'=>[], 'sent'=>[]]);
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Accepted friends ---
$stmt = $conn->prepare("
    SELECT u.id, u.username
    FROM friends f
    JOIN users u ON u.id = f.receiver_id
    WHERE f.sender_id = ? AND f.status = 'accepted'

    UNION

    SELECT u.id, u.username
    FROM friends f
    JOIN users u ON u.id = f.sender_id
    WHERE f.receiver_id = ? AND f.status = 'accepted'
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$friends = [];
while($row = $res->fetch_assoc()){
    $friends[] = $row;
}

// --- Pending friend requests sent TO current user ---
$stmt2 = $conn->prepare("
    SELECT u.id, u.username
    FROM friends f
    JOIN users u ON u.id = f.sender_id
    WHERE f.receiver_id = ? AND f.status = 'pending'
");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$pending = [];
while($row = $res2->fetch_assoc()){
    $pending[] = $row;
}

// --- Pending friend requests sent BY current user ---
$stmt3 = $conn->prepare("
    SELECT u.id, u.username
    FROM friends f
    JOIN users u ON u.id = f.receiver_id
    WHERE f.sender_id = ? AND f.status = 'pending'
");
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$res3 = $stmt3->get_result();
$sent = [];
while($row = $res3->fetch_assoc()){
    $sent[] = $row;
}

// --- Blocked users (people current user blocked) ---
$stmt4 = $conn->prepare("
    SELECT u.id, u.username
    FROM friends f
    JOIN users u ON u.id = f.receiver_id
    WHERE f.sender_id = ? AND f.status = 'blocked'
");
$stmt4->bind_param("i", $user_id);
$stmt4->execute();
$res4 = $stmt4->get_result();
$blocked = [];
while($row = $res4->fetch_assoc()){
    $blocked[] = $row;
}

// --- Return JSON ---
echo json_encode(['friends' => $friends, 'pending' => $pending, 'sent' => $sent, 'blocked' => $blocked]);