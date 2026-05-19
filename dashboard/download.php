<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../landing/login.html');
    exit;
}

if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('Location: dashboard.php');
    exit;
}

$file = basename($_GET['file']); // Prevent directory traversal
$filepath = __DIR__ . '/../admin/templates/files/' . $file;

if (file_exists($filepath)) {
    // Increment download count in database
    require_once __DIR__ . '/../db.php';
    
    // Update downloads count - increment by 1 where file_path matches
    $updateStmt = $conn->prepare("UPDATE templates SET downloads = downloads + 1 WHERE file_path = ?");
    $updateStmt->bind_param("s", $file);
    $updateStmt->execute();
    $updateStmt->close();
    $conn->close();
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Clear output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Stream the file
    readfile($filepath);
    exit;
} else {
    // File not found
    header('Location: dashboard.php?error=file_not_found');
    exit;
}
?>