<?php
session_start();
require_once 'includes/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Validate input
if (!isset($_POST['email_id'], $_POST['action'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$email_id = intval($_POST['email_id']);
$action = $_POST['action'];

// Determine new starred value
$starred = ($action === 'star') ? 1 : 0;

// Update the database
$stmt = $conn->prepare("UPDATE sent_emails SET starred = ? WHERE id = ?");
$stmt->bind_param("ii", $starred, $email_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'starred' => $starred]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
}

$stmt->close();
?>
