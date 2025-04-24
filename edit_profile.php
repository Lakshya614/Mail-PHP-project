<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db.php';

$email = $_SESSION['email'];

$stmt = $conn->prepare("SELECT name, email, profile_picture FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $new_email = $_POST['email'];
    $profile_picture = $user['profile_picture'];

    if (!empty($_FILES['profile_picture']['name'])) {
        $fileTmp  = $_FILES['profile_picture']['tmp_name'];
        $fileName = basename($_FILES['profile_picture']['name']);
        $targetPath = 'uploads/' . $fileName;

        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        if (move_uploaded_file($fileTmp, $targetPath)) {
            $profile_picture = $targetPath;
        }
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_picture = ? WHERE email = ?");
    $stmt->bind_param("ssss", $name, $new_email, $profile_picture, $email);
    $stmt->execute();
    $stmt->close();

    $_SESSION['email'] = $new_email;

    header("Location: profile.php?status=updated");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      background: linear-gradient(to right top, #1e293b, #0f172a);
    }
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
    @keyframes fadeInUp {
      0% { opacity: 0; transform: translateY(20px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeInUp {
      animation: fadeInUp 0.6s ease-out both;
    }
  </style>
</head>
<body class="text-white min-h-screen flex items-center justify-center p-6">

  <div class="w-full max-w-xl glass rounded-3xl shadow-2xl p-10 animate-fadeInUp space-y-8">

    <h1 class="text-4xl font-extrabold text-center text-purple-300 tracking-wide">Edit Your Profile</h1>

    <form action="edit_profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">

      <!-- Avatar -->
      <div class="flex justify-center">
        <div class="w-28 h-28 rounded-full overflow-hidden shadow-xl border-4 border-purple-500 relative group">
          <img id="preview" src="<?= htmlspecialchars($user['profile_picture']) ?: 'https://via.placeholder.com/100x100?text=👤' ?>" class="object-cover w-full h-full transition group-hover:scale-105 duration-300" />
          <div class="absolute bottom-0 w-full text-xs text-center bg-black bg-opacity-40 py-1 text-gray-200 hidden group-hover:block">Click to change</div>
        </div>
      </div>

      <!-- Name -->
      <div class="floating-label relative">
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" placeholder=" " required class="peer w-full px-4 py-3 rounded-lg bg-gray-900 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-purple-500">
        <label for="name" class="absolute left-4 top-3 text-gray-400 transition-all duration-200">Full Name</label>
      </div>

      <!-- Email -->
      <div class="floating-label relative">
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" placeholder=" " required class="peer w-full px-4 py-3 rounded-lg bg-gray-900 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-purple-500">
        <label for="email" class="absolute left-4 top-3 text-gray-400 transition-all duration-200">Email Address</label>
      </div>

      <!-- Profile Picture -->
      <div>
        <label for="profile_picture" class="block text-sm font-semibold text-purple-300 mb-2">Change Profile Picture</label>
        <input type="file" id="profile_picture" name="profile_picture" accept="image/*"
               class="w-full text-sm text-white bg-gray-900 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-600 file:text-white hover:file:bg-purple-700 transition"
               onchange="previewImage(event)">
        <p class="text-xs text-gray-400 mt-1">Leave blank to keep your current picture</p>
      </div>

      <!-- Submit -->
      <button type="submit" class="w-full py-3 bg-purple-600 hover:bg-purple-700 transition rounded-xl font-semibold text-white shadow-lg tracking-wide">💾 Save Changes</button>
    </form>

    <div class="text-center">
      <a href="profile.php" class="text-purple-400 hover:text-purple-200 underline transition">← Back to Profile</a>
    </div>

  </div>

  <script>
    function previewImage(event) {
      const input = event.target;
      const preview = document.getElementById('preview');
      const reader = new FileReader();
      reader.onload = () => {
        preview.src = reader.result;
      };
      if (input.files[0]) {
        reader.readAsDataURL(input.files[0]);
      }
    }
  </script>
</body>
</html>
