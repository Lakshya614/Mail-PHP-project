<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db.php';

$email = $_SESSION['email'];
$draft_id = $_GET['id'] ?? null;

if (!$draft_id) {
    header("Location: dashboard.php?view=draft");
    exit;
}

// Fetch the draft to edit
$query = "SELECT recipient, subject, message FROM draft_emails WHERE id = ? AND sender = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $draft_id, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Draft not found.";
    exit;
}

$draft = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Draft</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white font-sans h-screen flex">

  <main class="flex-1 p-6 overflow-y-auto">
    <h1 class="text-2xl font-bold mb-4">Edit Draft</h1>
    <form method="POST" action="save_draft.php" enctype="multipart/form-data">
      <input type="hidden" name="draft_id" value="<?= $draft_id ?>">
      <input type="text" name="recipient" value="<?= htmlspecialchars($draft['recipient']) ?>" placeholder="To" class="w-full mb-3 p-2 rounded border border-gray-300" required>
      <input type="text" name="subject" value="<?= htmlspecialchars($draft['subject']) ?>" placeholder="Subject" class="w-full mb-3 p-2 rounded border border-gray-300" required>
      <textarea name="message" placeholder="Message..." rows="6" class="w-full mb-3 p-2 rounded border border-gray-300" required><?= htmlspecialchars($draft['message']) ?></textarea>
      <div class="flex justify-between gap-2">
        <button type="submit" name="save" value="send" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 rounded">Send</button>
        <button type="submit" name="save" value="draft" class="flex-1 bg-gray-300 hover:bg-gray-400 text-black py-2 rounded">Save as Draft</button>
      </div>
    </form>
  </main>

</body>
</html>
