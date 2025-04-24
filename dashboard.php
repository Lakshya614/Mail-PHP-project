<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/db.php';

$email = $_SESSION['email'];
$view = $_GET['view'] ?? 'dashboard';
$search = $_GET['search'] ?? '';
$validCategories = ['sent', 'inbox', 'starred', 'draft', 'spam', 'trash'];
$user = $_SESSION['email'];

// Prepare and run each count query
function getEmailCount($conn, $query, $types, $params) {
    $stmt = $conn->prepare($query);
    if (!$stmt) return 0;
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->store_result();
    $count = $stmt->num_rows;
    $stmt->close();
    return $count;
}

$counts = [
    'Inbox' => getEmailCount($conn, "SELECT id FROM sent_emails WHERE recipient = ?", "s", [$user]),
    'Unread' => getEmailCount($conn, "SELECT id FROM sent_emails WHERE recipient = ? AND is_read = 0", "s", [$user]),
    'Spam' => getEmailCount($conn, "SELECT id FROM sent_emails WHERE sender = ? AND category = 'spam'", "s", [$user]),
    'Drafts' => getEmailCount($conn, "SELECT id FROM sent_emails WHERE sender = ? AND category = 'draft'", "s", [$user]),
    'Starred' => getEmailCount($conn, "SELECT id FROM sent_emails WHERE sender = ? AND category = 'starred'", "s", [$user]),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Animate.css (Optional for smooth animations) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .email-card:hover { transform: translateY(-2px); background-color: #2d2d2d; }
        .fade-in { animation: fadeIn 0.5s ease-in-out both; }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
    <meta charset="UTF-8" />
    <title>Mail Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white font-sans h-screen flex overflow-hidden">

<!-- Sidebar -->
<aside class="w-64 bg-gray-800/80 backdrop-blur-md p-6 flex flex-col shadow-xl border-r border-gray-700">
    <div class="text-purple-400 font-extrabold text-3xl mb-8 tracking-wide">MyMail</div>
    <button onclick="openCompose()" class="bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-lg mb-6 shadow transition-transform hover:scale-105">+ Compose</button>
    <nav class="flex-1 space-y-2">
        <?php foreach (['dashboard', ...$validCategories] as $v): ?>
            <a href="dashboard.php<?= $v !== 'dashboard' ? '?view=' . $v : '' ?>"
               class="flex items-center gap-2 py-2 px-3 rounded-lg transition-colors duration-200 hover:bg-purple-600 hover:text-white <?= $view === $v ? 'bg-purple-700 text-white font-semibold shadow-md' : 'text-gray-300' ?>">
                <?= ['dashboard' => '📊 Dashboard', 'inbox' => '📥 Received Mail', 'sent' => '📤 Sent Mail', 'starred' => '⭐ Starred', 'draft' => '📝 Drafts', 'spam' => '🚫 Spam', 'trash' => '🗑️ Trash'][$v] ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <a href="logout.php" class="mt-6 text-red-400 hover:text-red-500 text-sm text-center underline">Logout</a>
</aside>

<!-- Main Content -->
<main class="flex-1 p-6 overflow-y-auto space-y-6">
    <div class="sticky top-0 bg-gray-900/80 backdrop-blur-md z-10 p-4 -mx-6 mb-6 flex justify-between items-center shadow border-b border-gray-700">
        <h1 class="text-2xl font-extrabold text-white capitalize"><?= ucfirst($view) ?></h1>
        <div class="flex items-center gap-4">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>"
                       class="px-3 py-2 rounded-lg bg-gray-800 border border-gray-700 text-sm placeholder-gray-400 text-white focus:outline-none focus:ring-2 focus:ring-purple-600 transition-all">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-white text-sm shadow hover:shadow-lg">Search</button>
            </form>
            <div id="notificationBell" class="bg-gray-800 p-2 rounded-full hover:scale-105 transition transform">🔔</div>
            <a href="profile.php" class="flex items-center gap-2 bg-gray-800 px-4 py-2 rounded-full shadow hover:bg-gray-700 transition-all">
                <?php if (!empty($_SESSION['profile_picture'])): ?>
                    <img src="<?= htmlspecialchars($_SESSION['profile_picture']) ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover border border-purple-500">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-full bg-gray-600 flex items-center justify-center">👤</div>
                <?php endif; ?>
                <span class="hidden md:block font-medium"><?= isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : '' ?></span>
            </a>
        </div>
    </div>

    <?php
    if (in_array($view, $validCategories)) {
        if ($view === 'sent') {
            $query = "SELECT id, recipient, subject, message, sent_at FROM sent_emails WHERE sender = ?";
            $params = ["s" => [$email]];
        } elseif ($view === 'inbox') {
            $query = "SELECT id, sender, subject, sent_at FROM sent_emails WHERE recipient =?";
            $params = ["s" => [$email]];
        } elseif ($view === 'starred') {
            $query = "SELECT id, recipient, subject, message, sent_at FROM sent_emails WHERE sender = ? AND category = 'starred'";
            $params = ["s" => [$email]];
        } elseif ($view === 'trash') {
            $query = "SELECT id, recipient, subject, message, sent_at FROM sent_emails WHERE sender = ? AND category = 'trash'";
            $params = ["s" => [$email]];
        } elseif ($view === 'draft') {
            $query = "SELECT id, recipient, subject, message, sent_at FROM sent_emails WHERE sender = ? AND category = 'draft'";
            $params = ["s" => [$email]];
        } elseif ($view === 'spam') {
            $query = "SELECT id, recipient, subject, message, sent_at FROM sent_emails WHERE sender = ? AND category = 'spam'";
            $params = ["s" => [$email]];
        } else {
            $query = "SELECT id, sender, subject, message, sent_at FROM sent_emails WHERE recipient = ? AND category = ?";
            $params = ["ss" => [$email, $view]];
        }

        $types = array_keys($params)[0];
        $args = $params[$types];
        
        if (!empty($search)) {
            $query .= " AND (subject LIKE ? OR message LIKE ?)";
            $args[] = "%$search%";
            $args[] = "%$search%";
            $types .= "ss";
        }
        

        $query .= " ORDER BY sent_at DESC";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("SQL Prepare Error: " . $conn->error . "<br>Query: " . $query);
        }

        $types = array_keys($params)[0];
        $args = $params[$types];
        $stmt->bind_param($types, ...$args);
        $stmt->execute();
        $result = $stmt->get_result();

        echo "<div class='space-y-4'>";
        if ($result->num_rows === 0) {
            echo "<p class='text-gray-400 text-sm'>No emails found for your search.</p>";
        }
        while ($row = $result->fetch_assoc()) {
            echo "<div class='bg-gray-800 p-5 rounded-2xl shadow-md border border-gray-700 transition-all hover:shadow-xl email-card fade-in'>";
            echo "<div class='text-purple-300 text-xs mb-2 italic'>";
            
            // Corrected part: Check the 'view' and display either 'To' or 'From'
            echo "<div class='text-purple-300 text-xs mb-2 italic'>";

            // Check if it's a 'sent' email or not, and use the correct key (sender or recipient)
            if ($view === 'sent') {
                // Ensure 'recipient' key exists
                echo "To: " . (isset($row['recipient']) ? htmlspecialchars($row['recipient']) : 'Unknown');
            } else {
                // Ensure 'sender' key exists
                echo "From: " . (isset($row['sender']) ? htmlspecialchars($row['sender']) : 'Unknown');
            }
            
            echo " | " . $row['sent_at'] . "</div>";
            
            echo " | " . $row['sent_at'] . "</div>";
            echo "<div class='font-semibold text-lg text-purple-300'>" . htmlspecialchars($row['subject']) . "</div>";
            echo "<div class='text-gray-400 mt-2 text-sm leading-relaxed'>" . (isset($row['message']) ? nl2br(htmlspecialchars($row['message'])) : 'No message content available') . "</div>";
            echo "<hr class='my-4 border-gray-700'>";
            echo "<form method='POST' action='move_email.php' class='mt-2'>
                    <input type='hidden' name='email_id' value='{$row['id']}'>
                    <select name='new_category' onchange='this.form.submit()' class='bg-gray-700 text-white text-sm rounded px-2 py-1'>
                      <option disabled selected>Move to...</option>";
            foreach ($validCategories as $cat) {
                if ($cat !== $view) echo "<option value='$cat'>" . ucfirst($cat) . "</option>";
            }
            echo "</select></form></div>";
        }
        echo "</div>";
        $stmt->close();
    } else {
    ?>
   <!-- Dashboard Header -->
<div class="bg-gradient-to-r from-purple-700 to-purple-900 text-white p-8 rounded-3xl mb-8 flex justify-between items-center shadow-lg hover:shadow-xl transition-shadow duration-300">
    <div>
        <h2 class="text-2xl font-extrabold tracking-tight">📬 Welcome to MyMail</h2>
        <p class="text-sm opacity-80 mt-2">Your secure, private mailing dashboard with modern vibes.</p>
    </div>
    <button onclick="openCompose()" class="bg-white text-purple-700 font-semibold px-6 py-3 rounded-lg shadow-lg hover:bg-purple-100 transition-all duration-300 transform hover:scale-105 focus:outline-none">
        Let’s Compose
    </button>
</div>

<?php
$dashboardBoxes = [
    'Inbox' => '📥 Inbox',
    'Spam' => '🚫 Spam',
    'Drafts' => '📝 Drafts',
    'Starred' => '⭐ Important'
];
?>

<!-- Email Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-10 animate-fade-in">
    <?php foreach ($dashboardBoxes as $key => $label): ?>
        <a href="dashboard.php?view=<?= strtolower($key) ?>" class="block transition-transform transform hover:scale-105">
            <div class="bg-gradient-to-r from-purple-700 via-purple-800 to-purple-900 p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300">
                <div class="text-white text-4xl font-extrabold count-up" data-count="<?= $counts[$key] ?? 0 ?>">0</div>
                <div class="text-sm text-gray-300 mt-2"><?= $label ?></div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<!-- Email Analytics Section -->
<div class="bg-gradient-to-tr from-purple-700 via-purple-800 to-purple-900 p-6 rounded-2xl shadow-xl hover:shadow-2xl transition duration-300 mb-10">
    <h2 class="text-lg font-bold text-white mb-4">📊 Email Analytics</h2>
    <canvas id="emailAnalyticsChart" height="100"></canvas>
</div>

<!-- Fade-in Animation -->
<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.6s ease-out;
}
</style>

<!-- Count-Up & Compose Modal Script -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Count-up animation
    document.querySelectorAll('.count-up').forEach(el => {
        const target = +el.getAttribute('data-count');
        let current = 0;
        const increment = Math.ceil(target / 60);
        const update = () => {
            current += increment;
            if (current > target) current = target;
            el.textContent = current;
            if (current < target) requestAnimationFrame(update);
        };
        update();
    });

    // Email Analytics Chart
    const ctx = document.getElementById('emailAnalyticsChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Inbox', 'Spam', 'Drafts', 'Important'],
            datasets: [{
                label: 'Emails',
                data: [
                    <?= $counts['Inbox'] ?? 0 ?>,
                    <?= $counts['Spam'] ?? 0 ?>,
                    <?= $counts['Drafts'] ?? 0 ?>,
                    <?= $counts['Starred'] ?? 0 ?>
                ],
                backgroundColor: ['#a78bfa', '#f87171', '#fbbf24', '#34d399'],
                borderColor: 'transparent'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: { color: 'white' }
                }
            }
        }
    });
});

// Compose Modal open/close functions
function openCompose() {
    document.getElementById('composeModal').classList.remove('hidden');
}
function closeCompose() {
    document.getElementById('composeModal').classList.add('hidden');
}
</script>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <?php } ?>
</main>
<!-- Compose Modal -->
<div id="composeModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center hidden z-50 transition-all">
    <div class="bg-white text-gray-900 w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate__animated animate__fadeInDown">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-purple-800 text-white px-6 py-4 flex justify-between items-center">
            <h2 class="text-lg font-semibold">📤 Compose Email</h2>
            <button onclick="closeCompose()" class="text-white hover:text-red-300 text-2xl leading-none">&times;</button>
        </div>

        <!-- Form -->
        <form method="POST" id="composeForm" action="send_email.php" enctype="multipart/form-data" class="p-6">
            <!-- To -->
            <label class="block mb-2 font-medium text-sm">To</label>
            <input type="email" name="to" placeholder="Recipient's email" required
                   class="w-full px-4 py-2 mb-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">

            <!-- Subject -->
            <label class="block mb-2 font-medium text-sm">Subject</label>
            <input type="text" name="subject" placeholder="Email subject" required
                   class="w-full px-4 py-2 mb-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">

            <!-- Message -->
            <label class="block mb-2 font-medium text-sm">Message</label>
            <textarea name="message" rows="6" placeholder="Write your message here..." required
                      class="w-full px-4 py-2 mb-4 border rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>

            <!-- Attachment -->
            <label class="block mb-2 font-medium text-sm">Attachment</label>
            <input type="file" name="attachment" id="attachment"
                   class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-purple-100 file:text-purple-800 hover:file:bg-purple-200 transition mb-2">
            <div id="filePreview" class="text-xs text-gray-500 mb-4"></div>

            <!-- Hidden category -->
            <input type="hidden" name="category" id="category" value="sent">

            <!-- Buttons -->
            <div class="flex gap-4 mt-6">
                <button type="submit" onclick="setCategory('sent')"
                        class="bg-purple-600 hover:bg-purple-700 text-white w-full py-2 rounded-xl transition transform hover:scale-105">
                    🚀 Send
                </button>
                <button type="submit" onclick="setCategory('draft')"
                        class="bg-gray-600 hover:bg-gray-700 text-white w-full py-2 rounded-xl transition transform hover:scale-105">
                    💾 Save Draft
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    function openCompose() {
        document.getElementById('composeModal').classList.remove('hidden');
    }

    function closeCompose() {
        document.getElementById('composeModal').classList.add('hidden');
        document.getElementById('composeForm').reset();
        document.getElementById('filePreview').textContent = "";
    }

    function setCategory(value) {
        document.getElementById('category').value = value;
    }

    // File Preview
    document.addEventListener("DOMContentLoaded", () => {
        const fileInput = document.getElementById("attachment");
        const filePreview = document.getElementById("filePreview");

        fileInput.addEventListener("change", () => {
            const file = fileInput.files[0];
            if (file) {
                const sizeKB = (file.size / 1024).toFixed(1);
                filePreview.textContent = `📎 ${file.name} (${sizeKB} KB)`;
            } else {
                filePreview.textContent = "";
            }
        });
    });
</script>
<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
    <div class="text-white text-lg bg-purple-700 px-6 py-3 rounded-xl animate-pulse">
        Sending email...
    </div>
</div>

<!-- Success Popup -->
<div id="successPopup" class="fixed bottom-5 right-5 bg-green-600 text-white px-6 py-4 rounded-xl shadow-xl hidden z-50 animate__animated animate__fadeInUp">
    ✅ Mail sent successfully.<br>
    <span class="text-sm opacity-90">If you want to send more, click on <strong>Compose Mail</strong>.</span>
</div>
<script>
  function showSuccessPopup() {
    const popup = document.getElementById("successPopup");
    popup.classList.remove("hidden");

    setTimeout(() => {
        popup.classList.add("hidden");
    }, 4000); // stays for 4 seconds
}
    function openCompose() {
        document.getElementById('composeModal').classList.remove('hidden');
    }

    function closeCompose() {
        document.getElementById('composeModal').classList.add('hidden');
        document.getElementById('composeForm').reset();
        document.getElementById('filePreview').textContent = "";
    }

    function setCategory(value) {
        document.getElementById('category').value = value;
    }

    // File Preview
    document.addEventListener("DOMContentLoaded", () => {
        const fileInput = document.getElementById("attachment");
        const filePreview = document.getElementById("filePreview");

        fileInput.addEventListener("change", () => {
            const file = fileInput.files[0];
            if (file) {
                const sizeKB = (file.size / 1024).toFixed(1);
                filePreview.textContent = `📎 ${file.name} (${sizeKB} KB)`;
            } else {
                filePreview.textContent = "";
            }
        });

        // Form Submit Handling
        const form = document.getElementById("composeForm");
        form.addEventListener("submit", function (e) {
            e.preventDefault();

            // Show loading overlay
            document.getElementById("loadingOverlay").classList.remove("hidden");

            const formData = new FormData(form);

            fetch(form.action, {
                method: "POST",
                body: formData,
            })
            .then(response => response.ok ? response.text() : Promise.reject())
            .then(() => {
                document.getElementById("loadingOverlay").classList.add("hidden");
                closeCompose();
                showSuccessPopup();
            })
            .catch(() => {
                document.getElementById("loadingOverlay").classList.add("hidden");
                alert("❌ Failed to send email. Please try again.");
            });
        });

        function showSuccessPopup() {
            const popup = document.getElementById("successPopup");
            popup.classList.remove("hidden");
            setTimeout(() => {
                popup.classList.add("hidden");
            }, 3000);
        }
    });
</script>

</body>
</html>
