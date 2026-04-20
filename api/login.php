<?php
session_start();
require "../config/db.php";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if(!$username || !$password){
    echo "empty";
    exit();
}

// fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
$stmt->bind_param("s",$username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if(!$user){
    echo "nouser";
    exit();
}

if(!password_verify($password,$user['password'])){
    echo "badpass";
    exit();
}

// success
$_SESSION['user_id'] = $user['id'];

// Set a persistent cookie (30 days)
$token = bin2hex(random_bytes(16)); // generate random token
$stmt = $conn->prepare("UPDATE users SET token=? WHERE id=?");
$stmt->bind_param("si", $token, $user['id']);
$stmt->execute();

// Set cookie for browser
setcookie("rememberme", $token, time() + 60*60*24*30, "/", "", false, true);

echo "ok";