<?php
session_start();
require "../config/db.php";

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit();
}

$stmt = $conn->prepare("SELECT id, username FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows === 0){
    echo json_encode(["error"=>"User not found"]);
    exit();
}

$user = $res->fetch_assoc();

// Use ui-avatars with first letter of username
$defaultPfp = "https://ui-avatars.com/api/?name=" . urlencode($user['username'][0]) . "&background=7289DA&color=fff&size=128";

echo json_encode([
    "user" => ["id" => $user["id"], "username" => $user['username']],
    "username" => $user['username'],
    "pfp" => $defaultPfp
]);
?>