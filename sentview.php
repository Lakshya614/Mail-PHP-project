<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];

// Prepare statement with error handling
$query = "SELECT id, recipient, subject, sent_at FROM sent_emails WHERE sender = ? AND category = 'sent' ORDER BY sent_at DESC";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Prepare failed: " . $conn->error); // Debug error if query is invalid
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Sent Mail</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-6">
  <h1 class="text-2xl font-bold mb-4">📤 Sent Mails</h1>
  <div class="bg-gray-800 p-4 rounded shadow">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <a href="sent_view_detail.php?id=<?= $row['id'] ?>" class="block border-b border-gray-700 py-3 hover:bg-gray-700">
          <div class="text-sm text-purple-300">To: <?= htmlspecialchars($row['recipient']) ?></div>
          <div class="text-md font-semibold"><?= htmlspecialchars($row['subject']) ?></div>
          <div class="text-xs text-gray-400"><?= htmlspecialchars($row['sent_at']) ?></div>
        </a>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="text-gray-400">No sent mails found.</div>
    <?php endif; ?>
  </div>
</body>
</html>
