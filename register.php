<?php
require_once 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $name = $_POST["name"];
    $profile_picture = $_FILES["profile_picture"]['name'];

    // Password hashing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Profile picture upload handling
    if ($profile_picture) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($profile_picture);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file);
    } else {
        $target_file = null;
    }

    // Insert new user into database
    $stmt = $conn->prepare("INSERT INTO users (email, password, name, profile_picture) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $hashed_password, $name, $target_file);

    if ($stmt->execute()) {
        $message = "Registration successful! Please login.";
        $message_type = "success";
    } else {
        $message = "Error: Could not register user. Please try again.";
        $message_type = "error";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // Password Visibility Toggle
    function togglePassword() {
      const passwordField = document.getElementById('password');
      const passwordIcon = document.getElementById('passwordIcon');
      if (passwordField.type === "password") {
        passwordField.type = "text";
        passwordIcon.src = "https://img.icons8.com/ios-filled/50/000000/visible.png";
      } else {
        passwordField.type = "password";
        passwordIcon.src = "https://img.icons8.com/ios-filled/50/000000/invisible.png";
      }
    }

    // Show Popup
    function showPopup(message, type) {
      const popup = document.getElementById('popup');
      const popupMessage = document.getElementById('popupMessage');
      const popupClass = type === 'success' ? 'bg-green-500' : 'bg-red-500';
      popupMessage.textContent = message;
      popup.classList.remove('hidden');
      popup.classList.add(popupClass);

      setTimeout(() => {
        popup.classList.add('hidden');
      }, 3000);
    }
  </script>
</head>
<body class="relative min-h-screen bg-gradient-to-br from-[#1e1b4b] via-[#1f2937] to-black text-white font-sans overflow-hidden">

  <!-- Animated Background Blobs -->
  <div class="absolute top-0 left-0 w-96 h-96 bg-purple-500 opacity-20 blur-3xl rounded-full animate-ping-slow z-0"></div>
  <div class="absolute bottom-0 right-0 w-96 h-96 bg-pink-600 opacity-20 blur-3xl rounded-full animate-ping-slow z-0"></div>

  <!-- Registration Card -->
  <div class="relative z-10 flex items-center justify-center min-h-screen px-4">
    <div class="w-full max-w-md bg-white/10 border border-white/10 backdrop-blur-md p-8 rounded-3xl shadow-[0_8px_30px_rgb(128,0,128,0.2)] transition-all hover:shadow-purple-500/30 duration-300">
      <h2 class="text-4xl font-extrabold text-center text-purple-400 mb-8 tracking-wide drop-shadow-lg animate-fade-in">Join My Mail 💌</h2>

      <!-- Popup Message -->
      <div id="popup" class="hidden fixed top-6 left-1/2 transform -translate-x-1/2 px-6 py-4 rounded-lg shadow-lg text-white text-sm font-medium z-50 flex items-center space-x-2 transition-all duration-300">
        <span id="popupIcon"></span>
        <div id="popupMessage"></div>
      </div>

      <form method="POST" enctype="multipart/form-data" class="space-y-6 animate-fade-in">
        
        <!-- Floating Input -->
        <div class="relative">
          <input type="text" name="name" id="name" placeholder=" " required
            class="peer w-full px-4 pt-6 pb-2 rounded-lg bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-purple-500 transition duration-200" />
          <label for="name"
            class="absolute left-4 top-2 text-sm text-gray-400 peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-500 transition-all">Full
            Name</label>
        </div>

        <div class="relative">
          <input type="email" name="email" id="email" placeholder=" " required
            class="peer w-full px-4 pt-6 pb-2 rounded-lg bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-purple-500 transition duration-200" />
          <label for="email"
            class="absolute left-4 top-2 text-sm text-gray-400 peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-500 transition-all">Email</label>
        </div>

        <div class="relative">
          <input type="password" name="password" id="password" placeholder=" " required
            class="peer w-full px-4 pt-6 pb-2 rounded-lg bg-gray-800 text-white border border-gray-600 focus:outline-none focus:ring-2 focus:ring-purple-500 transition duration-200" />
          <label for="password"
            class="absolute left-4 top-2 text-sm text-gray-400 peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-500 transition-all">Password</label>
          <img id="passwordIcon"
            src="https://img.icons8.com/ios-filled/24/ffffff/invisible.png"
            class="absolute right-4 top-4 cursor-pointer transition hover:scale-110" onclick="togglePassword()" />
        </div>

        <div>
          <label for="profile_picture" class="block text-sm font-semibold text-gray-400 mb-2">Profile Picture</label>
          <input type="file" name="profile_picture" id="profile_picture"
            class="block w-full text-sm text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-600 file:text-white hover:file:bg-purple-700 cursor-pointer bg-gray-800 rounded-lg border border-gray-600 transition duration-200" />
        </div>

        <button type="submit"
          class="w-full bg-gradient-to-r from-purple-600 to-pink-500 hover:from-purple-700 hover:to-pink-600 text-white py-3 rounded-xl font-bold shadow-lg hover:shadow-purple-700/50 transition-all duration-300 transform hover:-translate-y-1">
          Register
        </button>
      </form>

      <p class="text-sm text-center text-gray-400 mt-6">
        Already registered? <a href="login.php" class="text-purple-400 hover:text-pink-400 underline">Login here</a>
      </p>
    </div>
  </div>

  <?php 
    if (isset($message)) {
      echo "<script>showPopup('$message', '$message_type');</script>";
    }
  ?>

  <script>
    function togglePassword() {
      const passwordField = document.getElementById('password');
      const passwordIcon = document.getElementById('passwordIcon');
      if (passwordField.type === "password") {
        passwordField.type = "text";
        passwordIcon.src = "https://img.icons8.com/ios-filled/24/ffffff/visible.png";
      } else {
        passwordField.type = "password";
        passwordIcon.src = "https://img.icons8.com/ios-filled/24/ffffff/invisible.png";
      }
    }

    function showPopup(message, type) {
      const popup = document.getElementById('popup');
      const popupMessage = document.getElementById('popupMessage');
      const popupIcon = document.getElementById('popupIcon');

      popup.className = 'fixed top-6 left-1/2 transform -translate-x-1/2 px-6 py-4 rounded-lg shadow-lg text-white text-sm font-medium z-50 flex items-center space-x-2';
      popup.classList.add(type === 'success' ? 'bg-green-500' : 'bg-red-500');

      popupIcon.innerHTML = type === 'success'
        ? '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
        : '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';

      popupMessage.textContent = message;
      popup.classList.remove('hidden');

      setTimeout(() => {
        popup.classList.add('hidden');
      }, 3500);
    }

    // Optional slow pulse animation
    tailwind.config = {
      theme: {
        extend: {
          animation: {
            'ping-slow': 'ping 4s cubic-bezier(0, 0, 0.2, 1) infinite',
            'fade-in': 'fadeIn 0.8s ease-out forwards',
          },
          keyframes: {
            fadeIn: {
              '0%': { opacity: 0, transform: 'translateY(20px)' },
              '100%': { opacity: 1, transform: 'translateY(0)' },
            },
          }
        }
      }
    };
  </script>
</body>


</html>
