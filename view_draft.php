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
    // Redirect to drafts if no draft ID is provided
    header("Location: dashboard.php?view=draft");
    exit;
}

// Fetch the draft from the database
$query = "SELECT id, recipient, subject, message, attachment FROM draft_emails WHERE id = ? AND sender = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $draft_id, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Draft not found or not owned by the current user
    echo "Draft not found.";
    exit;
}

$draft = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Draft</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white font-sans h-screen flex">

  <!-- Sidebar (same as before) -->

  <!-- Main Content -->
  <main class="flex-1 p-6 overflow-y-auto">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold capitalize">Draft: <?= htmlspecialchars($draft['subject']) ?></h1>
      <div class="flex items-center gap-4">
        <a href="dashboard.php?view=draft" class="text-purple-400 hover:text-purple-500">Back to Drafts</a>
      </div>
    </div>

    <div class="bg-gray-800 p-4 rounded shadow border border-gray-700">
        <div class="text-purple-400 text-sm mb-1">To: <?= htmlspecialchars($draft['recipient']) ?></div>
        <div class="font-semibold text-lg"><?= htmlspecialchars($draft['subject']) ?></div>
        <div class="text-gray-300 mt-2 text-sm"><?= nl2br(htmlspecialchars($draft['message'])) ?></div>
        
        <?php if ($draft['attachment']): ?>
            <div class="mt-3">
                <a href="<?= htmlspecialchars($draft['attachment']) ?>" class="text-blue-400 hover:text-blue-500" target="_blank">View Attachment</a>
            </div>
        <?php endif; ?>

        <div class="mt-4 flex gap-4">
            <a href="compose.php?id=<?= $draft['id'] ?>" class="bg-purple-600 text-white py-2 px-4 rounded hover:bg-purple-700">Edit</a>
            <a href="send_email.php?id=<?= $draft['id'] ?>" class="bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700">Send</a>
        </div>
    </div>
  </main>
</body>
</html>
