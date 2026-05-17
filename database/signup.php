<?php
require __DIR__ . '/../db.php';
session_start();

$fname = $_POST['fname'] ?? '';
$lname = $_POST['lname'] ?? '';
$name = $fname . " " . $lname;
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validate password strength
if (strlen($password) < 6) {
    $_SESSION['signup_error'] = "Password must be at least 6 characters long.";
    header("Location: ../landing/login.php");
    exit();
}

// Check if email already exists
$check = $conn->prepare("SELECT id, auth_type FROM users WHERE email=?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->bind_result($existing_id, $existing_auth_type);
    $check->fetch();
    
    if ($existing_auth_type === 'google') {
        $_SESSION['signup_error'] = "This email is already registered with Google. Please use the Google login button.";
    } else {
        $_SESSION['signup_error'] = "An account with this email already exists. Please log in instead.";
    }
    
    header("Location: ../landing/login.php");
    exit();
}

// Insert user
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("
    INSERT INTO users (name, email, password, auth_type)
    VALUES (?, ?, ?, 'manual')
");

$stmt->bind_param("sss", $name, $email, $hashed_password);

if ($stmt->execute()) {
    // Auto login after signup
    $_SESSION['user_id'] = $conn->insert_id;
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    
    header("Location: ../dashboard/dashboard.php");
    exit();
} else {
    $_SESSION['signup_error'] = "Something went wrong. Please try again.";
    header("Location: ../landing/login.php");
    exit();
}
?>