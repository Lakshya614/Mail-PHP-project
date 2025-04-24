<?php
require 'includes/db.php';

$where = "1"; // default: no filter

if (isset($_GET['filter'])) {
    $filter = $_GET['filter'] ?? '';
    $search = $_GET['search'] ?? '';
    $where = "1";
    
    if ($filter === 'today') {
        $where .= " AND DATE(received_at) = CURDATE()";
    } elseif ($filter === 'yesterday') {
        $where .= " AND DATE(received_at) = CURDATE() - INTERVAL 1 DAY";
    } elseif ($filter === 'unread') {
        $where .= " AND is_read = 0";
    }
    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $where .= " AND (subject LIKE '%$search%' OR sender LIKE '%$search%')";
    }
    
    $sql = "SELECT * FROM received_emails WHERE $where ORDER BY sent_at DESC";
    
$result = $conn->query($sql);

$emails = [];
while ($row = $result->fetch_assoc()) {
    $emails[] = $row;
}

header('Content-Type: application/json');
echo json_encode($emails);
?>
