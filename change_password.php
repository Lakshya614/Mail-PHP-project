<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db.php';

$email = $_SESSION['email'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($hashedPassword);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($current, $hashedPassword)) {
        $message = "❌ Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $message = "❌ New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $message = "❌ Password must be at least 6 characters.";
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $newHash, $email);
        $stmt->execute();
        $stmt->close();
        $message = "✅ Password updated successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .glass {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .floating-label input:focus ~ label,
    .floating-label input:not(:placeholder-shown) ~ label {
      transform: translateY(-1.5rem) scale(0.9);
      color: #c084fc;
    }
  </style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center p-6">

  <div class="max-w-md w-full glass p-8 rounded-2xl shadow-2xl space-y-6">
    <h1 class="text-3xl font-bold text-center text-purple-300 mb-4">🔐 Change Password</h1>

    <?php if ($message): ?>
      <div class="text-sm text-center p-3 rounded <?= strpos($message, '✅') !== false ? 'bg-green-700' : 'bg-red-700' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">

     <!-- Current Password -->
<div class="relative floating-label">
  <input type="password" id="current_password" name="current_password" required placeholder=" "
    class="peer w-full px-4 py-3 bg-gray-900 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-white placeholder-transparent">
  <label for="current_password" class="absolute left-4 top-3 text-gray-400 transition-all peer-placeholder-shown:top-3 peer-focus:top-[-0.5rem] peer-focus:text-sm peer-focus:text-purple-400">Current Password</label>
</div>

<!-- New Password -->
<div class="relative floating-label">
  <input type="password" id="new_password" name="new_password" required placeholder=" "
    class="peer w-full px-4 py-3 bg-gray-900 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-white placeholder-transparent">
  <label for="new_password" class="absolute left-4 top-3 text-gray-400 transition-all peer-placeholder-shown:top-3 peer-focus:top-[-0.5rem] peer-focus:text-sm peer-focus:text-purple-400">New Password</label>
</div>

<!-- Confirm Password -->
<div class="relative floating-label">
  <input type="password" id="confirm_password" name="confirm_password" required placeholder=" "
    class="peer w-full px-4 py-3 bg-gray-900 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-white placeholder-transparent">
  <label for="confirm_password" class="absolute left-4 top-3 text-gray-400 transition-all peer-placeholder-shown:top-3 peer-focus:top-[-0.5rem] peer-focus:text-sm peer-focus:text-purple-400">Confirm Password</label>
</div>

      <!-- Toggle Visibility -->
      <div class="text-sm text-gray-300 cursor-pointer hover:text-purple-300" onclick="togglePassword()">
        👁️ Show/Hide Passwords
      </div>

      <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-xl font-semibold">Update Password</button>
    </form>

    <div class="text-center">
      <a href="edit_profile.php" class="text-purple-400 hover:underline">← Back to Edit Profile</a>
    </div>
  </div>

  <script>
    function togglePassword() {
      const ids = ['current_password', 'new_password', 'confirm_password'];
      ids.forEach(id => {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
      });
    }
  </script>
</body>
</html>
