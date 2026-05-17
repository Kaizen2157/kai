<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

$client = new Google_Client();
$client->setClientId("259446168585-ie67ovgmm3nj9ragu1d5t3jvskohkkh9.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("http://localhost/kai/google/google-callback.php");
$client->addScope("email");
$client->addScope("profile");
$client->setApplicationName("Resumazing");

$auth_url = $client->createAuthUrl();
header("Location: " . $auth_url);
exit();
?>