<?php
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $status = $_POST['is_read']; // 1 = read, 0 = unread

    $stmt = $conn->prepare("UPDATE received_emails SET is_read = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $id);
    $stmt->execute();

    echo json_encode(['success' => true]);
}
?>
