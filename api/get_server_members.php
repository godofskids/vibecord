<?php
<?php
require "../config/db.php";
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode([]);
    exit;
}

$server_id = $_GET['server_id'] ?? 0;

$stmt = $conn->prepare("
    SELECT u.id, u.username 
    FROM server_members sm
    JOIN users u ON u.id = sm.user_id
    WHERE sm.server_id = ?
");
$stmt->bind_param("i", $server_id);
$stmt->execute();
$res = $stmt->get_result();

$members = [];
while($row = $res->fetch_assoc()){
    $members[] = $row;
}

echo json_encode($members);