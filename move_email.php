<?php
session_start();
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_id'], $_POST['new_category'])) {
    $emailId = intval($_POST['email_id']);
    $newCategory = $_POST['new_category'];

    $stmt = $conn->prepare("UPDATE sent_emails SET category = ? WHERE id = ?");
    $stmt->bind_param("si", $newCategory, $emailId);
    $stmt->execute();
    $stmt->close();
}

header("Location: dashboard.php?view=" . urlencode($newCategory));
exit;
?>
