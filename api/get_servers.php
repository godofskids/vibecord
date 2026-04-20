<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get servers the user is a member of
$stmt = $conn->prepare("
    SELECT s.id, s.name 
    FROM servers s
    JOIN server_members sm ON s.id = sm.server_id
    WHERE sm.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$servers = [];
while($row = $res->fetch_assoc()){
    // Convert name to array of characters for frontend display
    $row['name'] = str_split($row['name']);
    $servers[] = $row;
}

echo json_encode($servers);