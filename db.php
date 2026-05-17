<?php
// db.php - Database Connection File

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'resumazing';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Function to get database connection
function getDBConnection() {
    global $conn;
    return $conn;
}
?>