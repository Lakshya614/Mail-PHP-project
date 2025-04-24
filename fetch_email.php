<?php
require 'includes/db.php'; // Uses your existing db.php

// Gmail credentials
$username = 'ishuy066@gmail.com';
$password = 'jxfp ewek ogbp hqhg'; // Google app password

// Connect to Gmail via IMAP
$imap = imap_open("{imap.gmail.com:993/imap/ssl}INBOX", $username, $password)
    or die("Can't connect: " . imap_last_error());

// Fetch emails from Gmail and store them in DB
function fetchNewEmails($imap, $username, $conn) {
    // Fetch all unread (UNSEEN) emails from inbox (received)
    $emails = imap_search($imap, 'UNSEEN');

    if ($emails) {
        rsort($emails); // Reverse the order to get most recent first

        foreach ($emails as $email_number) {
            $overview = imap_fetch_overview($imap, $email_number, 0)[0];
            $message = imap_fetchbody($imap, $email_number, 1); // Fetch the body of the email

            $subject = $overview->subject ?? '(No Subject)';
            $from = $overview->from;
            $date = date('Y-m-d H:i:s', strtotime($overview->date));

            // Check and decode the message if it's encoded (Gmail may use Base64 or Quoted-printable encoding)
            $message = decodeMessage($message, $overview);

            // Determine mail type (received or sent)
            $mail_type = determineMailType($from, $username);

            // Classify email into category
            $category = classifyCategory($subject, $from);

            // Insert email into the database
            $stmt = $conn->prepare("INSERT INTO sent_emails (sender, recipient, subject, sent_at, message, is_read, category, mail_type) VALUES (?, ?, ?, ?, ?, 0, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssssss", $from, $username, $subject, $date, $message, $category, $mail_type);
                $stmt->execute();
                $stmt->close();
            } else {
                die("Database prepare failed: " . $conn->error);
            }

            // Trigger email notification (example)
            triggerEmailNotification($subject, $from, $date);
        }

        echo "New received emails saved to DB!";
    } else {
        echo "No new received emails found.";
    }

    // Fetch sent emails (if needed, you can adjust this part as well)
    fetchSentEmails($imap, $username, $conn);
}

// Function to fetch sent emails from the Sent folder
function fetchSentEmails($imap, $username, $conn) {
    // Open the 'Sent' folder to fetch sent emails
    $sent_imap = imap_open("{imap.gmail.com:993/imap/ssl}[Gmail]/Sent Mail", $username, $password)
        or die("Can't connect to Sent folder: " . imap_last_error());

    // Fetch all sent (UNSEEN) emails
    $emails = imap_search($sent_imap, 'UNSEEN');

    if ($emails) {
        rsort($emails); // Reverse the order to get most recent first

        foreach ($emails as $email_number) {
            $overview = imap_fetch_overview($sent_imap, $email_number, 0)[0];
            $message = imap_fetchbody($sent_imap, $email_number, 1); // Fetch the body of the email

            $subject = $overview->subject ?? '(No Subject)';
            $from = $overview->from;
            $date = date('Y-m-d H:i:s', strtotime($overview->date));

            // Check and decode the message if it's encoded (Gmail may use Base64 or Quoted-printable encoding)
            $message = decodeMessage($message, $overview);

            // Since these are sent emails, the mail_type is always 'sent'
            $mail_type = 'sent';

            // Classify email into category
            $category = classifyCategory($subject, $from);

            // Insert sent email into the database
            $stmt = $conn->prepare("INSERT INTO sent_emails (sender, recipient, subject, sent_at, message, is_read, category, mail_type) VALUES (?, ?, ?, ?, ?, 0, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssssss", $from, $username, $subject, $date, $message, $category, $mail_type);
                $stmt->execute();
                $stmt->close();
            } else {
                die("Database prepare failed: " . $conn->error);
            }

            // Trigger email notification (example)
            triggerEmailNotification($subject, $from, $date);
        }

        echo "New sent emails saved to DB!";
    } else {
        echo "No new sent emails found.";
    }

    // Close the Sent folder IMAP connection
    imap_close($sent_imap);
}

// Function to determine if an email is received or sent
function determineMailType($from, $username) {
    // If the sender's email address is not the same as our username, it's a received email
    if (stripos($from, $username) !== false) {
        return 'received';
    } else {
        return 'sent';
    }
}

// Function to decode the message based on encoding
function decodeMessage($message, $overview) {
    // Check for encoding
    $encoding = isset($overview->encoding) ? $overview->encoding : 0; // Default to 0 (7BIT)
    
    switch ($encoding) {
        case 0: // 7BIT
        case 1: // 8BIT
            return $message;
        case 3: // BASE64
            return base64_decode($message);
        case 4: // QUOTED-PRINTABLE
            return quoted_printable_decode($message);
        default:
            return $message;
    }
}

// Basic keyword-based classification
function classifyCategory($subject, $from) {
    $subject = strtolower($subject);
    $from = strtolower($from);

    if (strpos($subject, 'sale') !== false || strpos($from, 'offers@') !== false) return 'promotions';
    if (strpos($subject, 'friend request') !== false || strpos($from, 'facebook.com') !== false) return 'social';
    if (strpos($subject, 'password') !== false || strpos($from, 'security@') !== false) return 'spam';

    return 'inbox';
}

// Trigger notification function (You can customize this as needed)
function triggerEmailNotification($subject, $from, $date) {
    echo json_encode([
        'status' => 'new_email',
        'subject' => $subject,
        'from' => $from,
        'date' => $date
    ]);
}

// Call the function to fetch new emails
fetchNewEmails($imap, $username, $conn);

// Close the IMAP connection
imap_close($imap);
?>
