<?php
session_start();
header('Content-Type: application/json');
require 'includes/db.php';

$user = $_SESSION['email'];

// 1) Sent
$stmt = $conn->prepare("SELECT COUNT(*) FROM sent_emails WHERE sender = ?");
$stmt->bind_param("s",$user); $stmt->execute();
$stmt->bind_result($sent); $stmt->fetch(); $stmt->close();

// 2) Received
$stmt = $conn->prepare("SELECT COUNT(*) FROM received_emails WHERE recipient = ?");
$stmt->bind_param("s",$user); $stmt->execute();
$stmt->bind_result($received); $stmt->fetch(); $stmt->close();

// 3) Spam
$stmt = $conn->prepare("SELECT COUNT(*) FROM received_emails WHERE recipient = ? AND category='spam'");
$stmt->bind_param("s",$user); $stmt->execute();
$stmt->bind_result($spam); $stmt->fetch(); $stmt->close();

// 4) Drafts
$stmt = $conn->prepare("SELECT COUNT(*) FROM sent_emails WHERE sender = ? AND category='draft'");
$stmt->bind_param("s",$user); $stmt->execute();
$stmt->bind_result($drafts); $stmt->fetch(); $stmt->close();

// 5) Trash
$stmt = $conn->prepare("SELECT COUNT(*) FROM received_emails WHERE recipient = ? AND category='trash'");
$stmt->bind_param("s",$user); $stmt->execute();
$stmt->bind_result($trash); $stmt->fetch(); $stmt->close();

// 6) New / Unread / Important — adapt these to your schema
$newEmails       = /* your logic here */;
$unreadEmails    = /* your logic here */;
$importantEmails = /* your logic here */;

echo json_encode([
  'sent'            => (int)$sent,
  'received'        => (int)$received,
  'spam'            => (int)$spam,
  'drafts'          => (int)$drafts,
  'trash'           => (int)$trash,
  'newEmails'       => (int)$newEmails,
  'unreadEmails'    => (int)$unreadEmails,
  'spamEmails'      => (int)$spam,
  'importantEmails' => (int)$importantEmails,
]);
