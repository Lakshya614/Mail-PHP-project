<?php
session_start();
require 'includes/db.php'; // Ensure your db connection file is correct

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];

// Prepare the query to fetch sent emails
$query = "SELECT id, recipient, subject, created_at FROM sent_emails WHERE sender = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query); // Prepare the query

// Check if the query preparation is successful
if ($stmt === false) {
    die('Error preparing the query: ' . $conn->error); // Display the error if prepare fails
}

$stmt->bind_param("s", $email); // Bind the email parameter to the query
$stmt->execute(); // Execute the prepared statement
$result = $stmt->get_result(); // Get the result set

// Display the sent emails
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
    <?php while ($row = $result->fetch_assoc()): ?>
      <a href="sent_view.php?id=<?= $row['id'] ?>" class="block border-b border-gray-700 py-3 hover:bg-gray-700">
        <div class="text-sm text-purple-300">To: <?= htmlspecialchars($row['recipient']) ?></div>
        <div class="text-md font-semibold"><?= htmlspecialchars($row['subject']) ?></div>
        <div class="text-xs text-gray-400"><?= $row['created_at'] ?></div>
      </a>
    <?php endwhile; ?>
  </div>
</body>
</html>
