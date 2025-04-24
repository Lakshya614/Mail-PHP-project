<?php
session_start();
require_once 'includes/db.php';

$email = $_GET['email'];
$sql = "SELECT COUNT(*) AS count FROM notifications WHERE email = ? AND read_status = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
echo json_encode(['count' => $count]);
?>
