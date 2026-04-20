<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$username = trim($_POST['username'] ?? '');

if(empty($username)){
    echo json_encode(["error"=>"Username required"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    echo json_encode(["error"=>"User not found"]);
    exit;
}

$user = $result->fetch_assoc();
echo json_encode(["success"=>true, "user_id"=>$user['id']]);
?>