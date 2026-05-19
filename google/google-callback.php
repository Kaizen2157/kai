<?php
session_start();
require __DIR__ . '/../db.php';
require __DIR__ . '/../vendor/autoload.php';

function getRedirectUri(): string {
    $host = $_SERVER['HTTP_HOST'] ?? '';

    $knownHosts = [
        'vaccinal-syble-unmarginally.ngrok-free.dev' => 'https://vaccinal-syble-unmarginally.ngrok-free.dev',
        'dd50b8d0b0943c.lhr.life'                    => 'https://dd50b8d0b0943c.lhr.life',
        'localhost'                                   => 'http://localhost',
    ];

    foreach ($knownHosts as $pattern => $base) {
        if (strpos($host, $pattern) !== false) {
            return $base . '/kai/google/google-callback.php';
        }
    }

    return 'http://' . $host . '/kai/google/google-callback.php';
}

if (isset($_GET['error'])) {
    die("Google OAuth Error: " . htmlspecialchars($_GET['error']));
}

if (!isset($_GET['code'])) {
    die("No authorization code received. Please try logging in again.");
}

try {
    $client = new Google_Client();
    $client->setClientId("259446168585-ie67ovgmm3nj9ragu1d5t3jvskohkkh9.apps.googleusercontent.com");
    $client->setClientSecret("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");
    $client->setRedirectUri(getRedirectUri());
    $client->addScope("email");
    $client->addScope("profile");
    $client->addScope("openid");
    $client->setApplicationName("Resumazing");

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        die("Token Error: " . htmlspecialchars($token['error_description'] ?? $token['error']));
    }

    $client->setAccessToken($token['access_token']);

    $oauth = new Google_Service_Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    $email = $userInfo->email;
    $name = $userInfo->name;
    $google_id = $userInfo->id;

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $insert = $conn->prepare("INSERT INTO users (name, email, google_id, auth_type, is_admin) VALUES (?, ?, ?, 'google', 0)");
        $insert->bind_param("sss", $name, $email, $google_id);
        $insert->execute();
        $user_id = $conn->insert_id;
        $is_admin = 0;
    } else {
        if (empty($user['google_id'])) {
            $update = $conn->prepare("UPDATE users SET google_id = ?, auth_type = 'google' WHERE id = ?");
            $update->bind_param("si", $google_id, $user['id']);
            $update->execute();
        }
        $user_id = $user['id'];
        $is_admin = $user['is_admin'];
    }

    $_SESSION['user_id'] = $user_id;
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['is_admin'] = $is_admin;

    // Build base URL for redirect using same logic
    $redirectBase = rtrim(getRedirectUri(), '/kai/google/google-callback.php');
    // Safer: derive base from known host map
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $knownHosts = [
        'vaccinal-syble-unmarginally.ngrok-free.dev' => 'https://vaccinal-syble-unmarginally.ngrok-free.dev',
        'dd50b8d0b0943c.lhr.life'                    => 'https://dd50b8d0b0943c.lhr.life',
        'localhost'                                   => 'http://localhost',
    ];
    $base_url = 'http://' . $host;
    foreach ($knownHosts as $pattern => $base) {
        if (strpos($host, $pattern) !== false) {
            $base_url = $base;
            break;
        }
    }

    if ($is_admin == 1) {
        header("Location: " . $base_url . "/kai/admin/dashboard.php");
    } else {
        header("Location: " . $base_url . "/kai/dashboard/dashboard.php");
    }
    exit();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>