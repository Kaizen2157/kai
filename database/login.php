<?php
require __DIR__ . '/../db.php';
session_start();

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Check if user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    // User not found
    $_SESSION['login_error'] = "No account found with this email address. Please sign up first.";
    header("Location: ../landing/login.php");
    exit();
}

// Check if user registered manually
if ($user['auth_type'] === 'google') {
    $_SESSION['login_error'] = "This email is registered with Google. Please use the Google login button.";
    header("Location: ../landing/login.php");
    exit();
}

// Verify password
if (password_verify($password, $user['password'])) {
    // Successful login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['is_admin'] = $user['is_admin'];
    unset($_SESSION['login_error']);
    
    // Redirect based on role
    if ($user['is_admin'] == 1) {
        header("Location: ../dashboard/dashboard.php");
    } else {
        header("Location: ../dashboard/dashboard.php");
    }
    exit();
} else {
    // Wrong password
    $_SESSION['login_error'] = "Invalid password. Please try again.";
    header("Location: ../landing/login.php");
    exit();
}
?>