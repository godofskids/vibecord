<?php
require "db.php";

if(!isset($_SESSION['user_id'])){
    http_response_code(401);
    echo "not_logged_in";
    exit();
}