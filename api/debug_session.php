<?php
session_start();
header('Content-Type: application/json');

$output = [
    'session_user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
    'cookie_rememberme' => isset($_COOKIE['rememberme']) ? $_COOKIE['rememberme'] : null,
    'session_id' => session_id()
];

// If no session, try to restore from cookie
if(!isset($_SESSION['user_id']) && isset($_COOKIE['rememberme'])){
    require "config/db.php";
    $token = $_COOKIE['rememberme'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0){
        $user = $res->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $output['restored_user_id'] = $_SESSION['user_id'];
    }
}

echo json_encode($output);
?>