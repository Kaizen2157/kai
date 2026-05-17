<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../landing/login.html');
    exit;
}

if (isset($_GET['file'])) {
    $file = basename($_GET['file']); // Prevent directory traversal
    $filepath = __DIR__ . '/templates/' . $file;
    
    if (file_exists($filepath)) {
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache');
        
        readfile($filepath);
        exit;
    } else {
        // File doesn't exist yet - create a placeholder
        // In production, you'd have real template files
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        
        // Create a simple placeholder DOCX content
        echo "Template file: " . $file . "\n";
        echo "This is a placeholder. Real template will be served when uploaded.";
        exit;
    }
}

header('Location: dashboard.php');
exit;
?>