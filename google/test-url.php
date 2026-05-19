<?php
$host = $_SERVER['HTTP_HOST'];

echo "Host: " . $host . "<br>";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'not set') . "<br>";
echo "SERVER_PORT: " . $_SERVER['SERVER_PORT'] . "<br>";
echo "HTTP_X_FORWARDED_PROTO: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set') . "<br>";

// Force HTTPS for ngrok
if (strpos($host, 'ngrok') !== false || strpos($host, 'lhr.life') !== false) {
    $base_url = 'https://' . $host;
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    // Also check forwarded proto
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $protocol = 'https';
    }
    $base_url = $protocol . '://' . $host;
}

echo "Base URL: " . $base_url . "<br>";
echo "Redirect URI being sent to Google: " . $base_url . "/kai/google/google-callback.php<br>";
?>