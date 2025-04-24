<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db.php';

$email = $_SESSION['email'];

// Get form data
$recipient = $_POST['to'] ?? '';
$subject = $_POST['subject'] ?? '';
$message = $_POST['message'] ?? '';
$category = $_POST['category'] ?? 'draft';  // Default to 'draft' if no category is set

// Handle file upload (optional)
$attachment = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
    $attachment = 'uploads/' . basename($_FILES['attachment']['name']);
    move_uploaded_file($_FILES['attachment']['tmp_name'], $attachment);
}

// Insert into the database (drafts table)
$query = "INSERT INTO draft_emails (sender, recipient, subject, message, attachment, category) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssssss", $email, $recipient, $subject, $message, $attachment, $category);

if ($stmt->execute()) {
    // Redirect to drafts view or a success page
    header("Location: dashboard.php?view=draft");
} else {
    // Handle error (maybe display an error message)
    echo "Error saving draft.";
}

$stmt->close();
?>
