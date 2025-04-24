<?php
session_start();
require_once 'includes/db.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Prepare and execute query to check user credentials
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // If user is found
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashed_password)) {
            // Store session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            header("Location: dashboard.php");
            exit;
        } else {
            $message = "Invalid password. Please try again.";
            $message_type = "error";
        }
    } else {
        $message = "No user found with that email address.";
        $message_type = "error";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Toggle password visibility
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

        // Show error/success message popup
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

        // Validate form inputs (email and password)
        function validateForm(event) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');

            // Reset errors
            emailError.textContent = '';
            passwordError.textContent = '';

            let valid = true;

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                emailError.textContent = 'Please enter a valid email.';
                valid = false;
            }

            // Password validation
            if (password.length < 6) {
                passwordError.textContent = 'Password must be at least 6 characters.';
                valid = false;
            }

            if (!valid) {
                event.preventDefault(); // Prevent form submission if validation fails
            }
        }

        // Focus/blur animations for input fields
        function handleFocus(event) {
            event.target.classList.add('border-blue-500', 'ring-2', 'ring-blue-300');
        }

        function handleBlur(event) {
            event.target.classList.remove('border-blue-500', 'ring-2', 'ring-blue-300');
        }
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #6b21a8, #4c1d95);
            color: white;
            font-family: 'Roboto', sans-serif;
            overflow-x: hidden;
        }

        /* Popup Animation */
        .popup-animation {
            animation: popupAnimation 0.5s ease-in-out;
        }

        @keyframes popupAnimation {
            0% { opacity: 0; transform: translateY(-10px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Tooltip for input fields (optional) */
        .tooltip {
            display: none;
            position: absolute;
            background-color: rgba(0, 0, 0, 0.75);
            color: white;
            padding: 5px;
            border-radius: 3px;
            font-size: 12px;
        }

        .input-container:hover .tooltip {
            display: block;
        }

        /* Loading Spinner (if needed) */
        .loading-spinner {
            display: none;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4c1d95;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-tr from-purple-900 via-gray-900 to-black text-white font-sans relative overflow-hidden">

  <!-- Login Card -->
  <div class="z-10 w-full max-w-md bg-white/5 backdrop-blur-lg p-8 rounded-2xl shadow-2xl ring-1 ring-white/10 transition-all duration-300">
    <h2 class="text-4xl font-bold text-center text-purple-400 mb-8 drop-shadow-md">Welcome Back</h2>

    <!-- Popup for error/success messages -->
    <div id="popup" class="hidden fixed top-0 left-0 right-0 p-4 text-white text-center font-semibold z-50 rounded popup-animation"></div>

    <form method="POST" onsubmit="validateForm(event)" class="space-y-6">
        <!-- Email Input -->
        <div class="relative">
            <label for="email" class="block text-sm font-semibold text-purple-300 mb-1 tracking-wide">Email Address</label>
            <input type="email" name="email" id="email" required
              class="w-full px-4 py-3 text-white bg-white/10 backdrop-blur-sm border border-purple-700 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all duration-300"
              placeholder="e.g. john@example.com">
            <div id="emailError" class="text-red-500 text-sm mt-1"></div>
        </div>

        <!-- Password Input -->
        <div class="relative">
            <label for="password" class="block text-sm font-semibold text-purple-300 mb-1 tracking-wide">Password</label>
            <input type="password" name="password" id="password" required
              class="w-full px-4 py-3 text-white bg-white/10 backdrop-blur-sm border border-purple-700 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all duration-300 pr-10"
              placeholder="Your secure password">
            <img id="passwordIcon" src="https://img.icons8.com/ios-filled/24/invisible.png"
              class="absolute top-10 right-4 cursor-pointer opacity-70 hover:opacity-100 transition-transform hover:scale-110"
              onclick="togglePassword()" />
            <div id="passwordError" class="text-red-500 text-sm mt-1"></div>
        </div>

        <!-- Submit Button -->
        <button type="submit"
          class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-purple-600 via-pink-500 to-purple-700 hover:from-purple-700 hover:to-pink-600 text-white font-semibold py-3 rounded-xl transition-all duration-300 hover:scale-105 shadow-lg shadow-purple-800/40">
            Login
        </button>

        <!-- Register Link -->
        <p class="text-sm text-center text-gray-400 mt-6">
            Don't have an account?
            <a href="register.php" class="text-purple-400 hover:text-purple-500 font-medium">Register here</a>
        </p>
    </form>
  </div>

  <?php if (isset($message)) {
    echo "<script>showPopup('$message', '$message_type');</script>";
  } ?>

</body>
</html>
