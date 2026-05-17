<?php
session_start();
require __DIR__ . '/../db.php';
require __DIR__ . '/../vendor/autoload.php';

// Check for errors first
if (isset($_GET['error'])) {
    die("Google OAuth Error: " . htmlspecialchars($_GET['error']));
}

// Check if code exists
if (!isset($_GET['code'])) {
    die("No authorization code received. Please try logging in again.");
}

try {
    $client = new Google_Client();
    $client->setClientId("259446168585-ie67ovgmm3nj9ragu1d5t3jvskohkkh9.apps.googleusercontent.com");
    $client->setClientSecret("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");
    $client->setRedirectUri("http://localhost/kai/google/google-callback.php");
    $client->addScope("email");
    $client->addScope("profile");
    $client->setApplicationName("Resumazing");

    // Exchange authorization code for access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    // Check for token errors
    if (isset($token['error'])) {
        die("Token Error: " . htmlspecialchars($token['error_description'] ?? $token['error']));
    }
    
    $client->setAccessToken($token['access_token']);

    // Get user info
    $oauth = new Google_Service_Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    $email = $userInfo->email;
    $name = $userInfo->name;
    $google_id = $userInfo->id;

    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        // Create new Google user
        $insert = $conn->prepare("
            INSERT INTO users (name, email, google_id, auth_type)
            VALUES (?, ?, ?, 'google')
        ");
        $insert->bind_param("sss", $name, $email, $google_id);
        $insert->execute();
        $user_id = $conn->insert_id;
    } else {
        // Update Google ID if user exists but logged in with Google for first time
        if (empty($user['google_id'])) {
            $update = $conn->prepare("UPDATE users SET google_id = ?, auth_type = 'google' WHERE id = ?");
            $update->bind_param("si", $google_id, $user['id']);
            $update->execute();
        }
        $user_id = $user['id'];
    }

    // Set session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;

    // Redirect to dashboard
    header("Location: /kai/dashboard/dashboard.php");
    exit();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>