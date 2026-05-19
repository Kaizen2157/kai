<?php
session_start();
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

$client = new Google_Client();
$client->setClientId("259446168585-ie67ovgmm3nj9ragu1d5t3jvskohkkh9.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri(getRedirectUri());
$client->addScope("email");
$client->addScope("profile");
$client->addScope("openid");
$client->setApplicationName("Resumazing");

// Uncomment to debug — visit the login URL and check the output matches Google Console exactly
// die("Redirect URI being used: " . getRedirectUri());

$auth_url = $client->createAuthUrl();
header("Location: " . $auth_url);
exit();
?>