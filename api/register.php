<?php
require "../config/db.php";

$username = trim($_POST['username']);
$password = $_POST['password'];

if(strlen($username) < 3){
    exit("username_short");
}

if(strlen($password) < 5){
    exit("password_short");
}

$check = $conn->prepare("SELECT id FROM users WHERE username=?");
$check->bind_param("s",$username);
$check->execute();
$res = $check->get_result();

if($res->num_rows > 0){
    exit("user_exists");
}

$hash = password_hash($password,PASSWORD_DEFAULT);

$stmt = $conn->prepare(
"INSERT INTO users(username,password) VALUES(?,?)"
);

$stmt->bind_param("ss",$username,$hash);
$stmt->execute();

echo "ok";