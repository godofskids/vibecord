<?php

$host = "localhost";
$user = "root";
$pass = "root";
$db   = "vibecord";

$conn = new mysqli($host,$user,$pass,$db);

if ($conn->connect_error) {
    die("Database failed");
}

if(session_status() === PHP_SESSION_NONE){
    session_start();

}

?>