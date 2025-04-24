<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['email'])) {
    die(json_encode(['error' => 'User not logged in']));
}

if (!$conn) {
    die(json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]));
}

$sql = "SELECT subject, sender FROM notifications WHERE email = ? AND read_status = 0 ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(json_encode(['error' => 'Prepare failed: ' . $conn->error]));
}

$stmt->bind_param('s', $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode(['notifications' => $notifications]);
?>
