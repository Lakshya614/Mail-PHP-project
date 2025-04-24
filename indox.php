<!DOCTYPE html>
<html>
<head>
    <title>Inbox</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; cursor: pointer; }
        button { margin-right: 10px; }
        .unread { font-weight: bold; background-color: #f2f2f2; }
        .controls { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>📥 Mail Dashboard</h2>

    <div class="controls">
        <input type="text" id="search" placeholder="Search subject or sender" oninput="fetchEmails()">
        <button onclick="setFilter('')">All</button>
        <button onclick="setFilter('today')">Today</button>
        <button onclick="setFilter('yesterday')">Yesterday</button>
        <button onclick="setFilter('unread')">Unread</button>
    </div>

    <div id="inbox-container">Loading...</div>

    <!-- Modal for full email view -->
    <div id="email-modal" style="display:none; position:fixed; top:10%; left:20%; width:60%; background:white; padding:20px; border:1px solid #ccc; box-shadow: 0 0 10px rgba(0,0,0,0.3); z-index:9999;">
        <button onclick="closeModal()">Close</button>
        <h3 id="modal-subject"></h3>
        <p><strong>From:</strong> <span id="modal-from"></span></p>
        <p><strong>Sent:</strong> <span id="modal-date"></span></p>
        <hr>
        <div id="modal-message"></div>
    </div>

    <script>
        let currentFilter = "";

        function setFilter(filter) {
            currentFilter = filter;
            fetchEmails();
        }

        function fetchEmails() {
            const search = document.getElementById('search').value;
            fetch('fetch_email.php')
                .then(() => fetch('load_emails.php?filter=' + currentFilter + '&search=' + encodeURIComponent(search)))
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('inbox-container');
                    if (data.length === 0) {
                        container.innerHTML = "<p>No emails found.</p>";
                        return;
                    }

                    let html = "<table><tr><th>Status</th><th>From</th><th>Subject</th><th>Sent At</th><th>Actions</th></tr>";
                    data.forEach(email => {
                        const rowClass = email.is_read == 0 ? "unread" : "";
                        html += `<tr class="${rowClass}" onclick="openModal(${encodeURIComponent(JSON.stringify(email))})">
                            <td>${email.is_read == 0 ? '🔵' : '✅'}</td>
                            <td>${email.sender}</td>
                            <td>${email.subject}</td>
                            <td>${email.sent_at}</td>
                            <td>
                                <button onclick="event.stopPropagation(); toggleRead(${email.id}, ${email.is_read == 0 ? 1 : 0})">
                                    Mark as ${email.is_read == 0 ? 'Read' : 'Unread'}
                                </button>
                            </td>
                        </tr>`;
                    });
                    html += "</table>";

                    container.innerHTML = html;
                });
        }

        function toggleRead(id, status) {
            fetch('toggle_read_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&is_read=${status}`
            }).then(() => fetchEmails());
        }

        function openModal(emailJson) {
            const email = typeof emailJson === "string" ? JSON.parse(decodeURIComponent(emailJson)) : emailJson;
            document.getElementById('modal-subject').innerText = email.subject;
            document.getElementById('modal-from').innerText = email.sender;
            document.getElementById('modal-date').innerText = email.sent_at;
            document.getElementById('modal-message').innerText = email.message;
            document.getElementById('email-modal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('email-modal').style.display = 'none';
        }

        fetchEmails();
        setInterval(fetchEmails, 15000); // Refresh every 15s
    </script>
</body>
</html>
