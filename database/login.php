<?php
require __DIR__ . '/../db.php';
session_start();

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember_me']);

// Check if user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['login_error'] = "No account found with this email address. Please sign up first.";
    header("Location: ../landing/login.php");
    exit();
}

if ($user['auth_type'] === 'google') {
    $_SESSION['login_error'] = "This email is registered with Google. Please use the Google login button.";
    header("Location: ../landing/login.php");
    exit();
}

if (password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['is_admin'] = $user['is_admin'];
    unset($_SESSION['login_error']);
    
    // Remember Me - set cookie for 30 days
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
        
        $stmt = $conn->prepare("UPDATE users SET remember_token=? WHERE id=?");
        $stmt->bind_param("si", $token, $user['id']);
        $stmt->execute();
    }
    
    if ($user['is_admin'] == 1) {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../dashboard/dashboard.php");
    }
    exit();
} else {
    $_SESSION['login_error'] = "Invalid password. Please try again.";
    header("Location: ../landing/login.php");
    exit();
}
?>