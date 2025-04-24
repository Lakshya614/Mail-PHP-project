<?php
session_start();
require 'includes/db.php';
header('Content-Type: application/json'); // Ensure JSON response is returned

// ✅ Include PHPMailer
require 'includes/PHPMailer-master/src/Exception.php';
require 'includes/PHPMailer-master/src/PHPMailer.php';
require 'includes/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $to       = $_POST['to'];
    $subject  = $_POST['subject'];
    $message  = $_POST['message'];
    $from     = $_SESSION['email'] ?? 'noreply@example.com';  // Assuming user is logged in
    $category = $_POST['category'] ?? 'sent';  // Can be 'sent' or 'draft'
    $attachmentName = null;

    // Handle file upload
    if (!empty($_FILES['attachment']['name'])) {
        $fileTmp  = $_FILES['attachment']['tmp_name'];
        $fileName = basename($_FILES['attachment']['name']);
        $targetPath = 'uploads/' . $fileName;

        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        if (move_uploaded_file($fileTmp, $targetPath)) {
            $attachmentName = $fileName;
        }
    }

    // ✅ Handle Draft Saving (Without Sending Email)
    if ($category === 'draft') {
        $stmt = $conn->prepare("INSERT INTO sent_emails (sender, recipient, subject, message, attachment, category, sent_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $from, $to, $subject, $message, $attachmentName, $category);
        $stmt->execute();
        $stmt->close();

        // Return success response for draft
        echo json_encode(["success" => true, "message" => "Draft saved successfully!"]);
        exit;
    }

    // ✅ Sending Email via PHPMailer
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ishuy066@gmail.com';         // Replace with your Gmail
        $mail->Password   = 'jxfp ewek ogbp hqhg';         // App password (not normal Gmail)
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom($from, 'Mail Dashboard');
        $mail->addAddress($to);  // Add recipient

        // Attach file if exists
        if ($attachmentName) {
            $mail->addAttachment('uploads/' . $attachmentName);
        }

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($message);

        // Send email
        $mail->send();

        // Save sent email details in the database
        $stmt = $conn->prepare("INSERT INTO sent_emails (sender, recipient, subject, message, attachment, category, sent_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $from, $to, $subject, $message, $attachmentName, $category);
        $stmt->execute();
        $stmt->close();

        // Return success response for email sent
        echo json_encode(["success" => true, "message" => "Mail sent successfully!"]);
    } catch (Exception $e) {
        // If error occurs in sending email
        echo json_encode([
            "success" => false,
            "error" => "Mailer Error: " . $mail->ErrorInfo
        ]);
    }
} else {
    // If request method is not POST
    echo json_encode(["success" => false, "error" => "Invalid request"]);
}
