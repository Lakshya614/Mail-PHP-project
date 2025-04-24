<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db.php';

$email = $_SESSION['email'];

$stmt = $conn->prepare("SELECT name, profile_picture FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          animation: {
            fadeIn: 'fadeIn 1s ease-in-out',
          },
          keyframes: {
            fadeIn: {
              '0%': { opacity: 0 },
              '100%': { opacity: 1 },
            }
          }
        }
      }
    };
  </script>
</head>
<body class="bg-gradient-to-r from-gray-900 to-gray-800 text-white min-h-screen flex items-center justify-center p-6">

  <div class="max-w-xl w-full space-y-6 animate-fadeIn">

    <h1 class="text-4xl font-extrabold text-center">👤 My Profile</h1>

    <!-- Profile Card -->
    <div class="bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-700 hover:shadow-2xl transition duration-300">
      <div class="flex items-center gap-6">
        <?php if (!empty($user['profile_picture'])): ?>
          <div class="relative">
            <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" class="w-20 h-20 rounded-full border-4 border-purple-500 object-cover shadow-md transition-transform hover:scale-105">
          </div>
        <?php else: ?>
          <div class="w-20 h-20 rounded-full bg-gray-600 flex items-center justify-center text-3xl border-4 border-purple-500 shadow-md">👤</div>
        <?php endif; ?>
        
        <div>
          <p class="text-lg"><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
          <p class="text-lg"><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
        </div>
      </div>
    </div>

    <!-- Edit Profile -->
    <div class="bg-gray-800 p-5 rounded-xl shadow hover:shadow-xl transition">
      <h2 class="text-2xl font-semibold mb-2">✏️ Edit Profile</h2>
      <a href="edit_profile.php" class="text-purple-400 hover:text-purple-300 transition underline">Edit Name, Email, and Profile Picture</a>
    </div>

    <!-- Change Password -->
    <div class="bg-gray-800 p-5 rounded-xl shadow hover:shadow-xl transition">
      <h2 class="text-2xl font-semibold mb-2">🔒 Change Password</h2>
      <a href="change_password.php" class="text-purple-400 hover:text-purple-300 transition underline">Change your password</a>
    </div>

    <!-- Logout -->
    <form action="logout.php" method="POST" class="bg-red-600 p-5 rounded-xl text-center shadow hover:shadow-xl transition">
      <button type="submit" class="w-full py-2 text-white font-bold rounded hover:bg-red-500 transition duration-200">🚪 Logout</button>
    </form>

    <!-- Back to Dashboard -->
    <div class="text-center">
      <a href="dashboard.php" class="text-purple-400 hover:text-purple-300 transition underline">← Back to Dashboard</a>
    </div>

  </div>

</body>
</html>
