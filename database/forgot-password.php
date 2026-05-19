<?php
require __DIR__ . '/../db.php';

header('Content-Type: application/json');

$email = $_POST['email'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your email address.']);
    exit();
}

// Check if user exists
$stmt = $conn->prepare("SELECT id, name FROM users WHERE email=? AND auth_type='manual'");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    // Don't reveal if email exists or not for security
    echo json_encode(['success' => true, 'message' => 'If an account exists with this email, you will receive a password reset link shortly.']);
    exit();
}

// Generate reset token
$token = bin2hex(random_bytes(32));

// Store token in remember_token column (we don't need token_expires)
$stmt = $conn->prepare("UPDATE users SET remember_token=? WHERE id=?");
$stmt->bind_param("si", $token, $user['id']);
$stmt->execute();

// Check if update was successful
if ($stmt->affected_rows > 0) {
    // For localhost demo purposes - show the link
    $reset_link = "http://localhost/kai/landing/reset-password.php?token=" . $token;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Password reset link has been generated. <br><small style="opacity:0.7">Localhost: <a href="' . $reset_link . '" style="color:#00b4d8">Click here to reset</a></small>'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to generate reset token. Please try again.']);
}
?>