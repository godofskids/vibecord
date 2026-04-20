<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(["error"=>"Not logged in"]);
    exit;
}

$invite_id = intval($_POST['invite_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if(!$invite_id){
    echo json_encode(["error"=>"Invitation ID required"]);
    exit;
}

// Update invitation status to declined
$stmt = $conn->prepare("UPDATE group_invitations SET status = 'declined' WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $invite_id, $user_id);
$stmt->execute();

if($stmt->affected_rows === 0){
    echo json_encode(["error"=>"Invitation not found"]);
    exit;
}

echo json_encode(["success"=>true, "message"=>"Invitation declined"]);
?>