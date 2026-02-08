<?php
// Start session with long lifetime (30 days)
ini_set('session.gc_maxlifetime', 2592000); // 30 days in seconds
session_set_cookie_params(2592000); // Cookie lasts 30 days
session_start();

function parse_env(): array {
    if (!empty($_ENV) || !empty($_SERVER)) {
        return [
            'DISCORD_CLIENT_ID' => $_ENV['DISCORD_CLIENT_ID'] ?? $_SERVER['DISCORD_CLIENT_ID'] ?? '',
            'DISCORD_CLIENT_SECRET' => $_ENV['DISCORD_CLIENT_SECRET'] ?? $_SERVER['DISCORD_CLIENT_SECRET'] ?? '',
            'DISCORD_REDIRECT_URI' => $_ENV['DISCORD_REDIRECT_URI'] ?? $_SERVER['DISCORD_REDIRECT_URI'] ?? '',
        ];
    }
    
    // ✅ FIXED PATH - looks in config folder
    $envFile = __DIR__ . '/../config/env.txt';
    if (!is_file($envFile)) return [];
    $vars = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    return is_array($vars) ? $vars : [];
}

$env = parse_env();

if (!isset($_GET['code'])) {
    header('Location: login.php');
    exit;
}

$code = $_GET['code'];

$tokenUrl = 'https://discord.com/api/oauth2/token';
$tokenData = [
    'client_id' => $env['DISCORD_CLIENT_ID'],
    'client_secret' => $env['DISCORD_CLIENT_SECRET'],
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $env['DISCORD_REDIRECT_URI']
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
curl_close($ch);

$tokenInfo = json_decode($response, true);

if (!isset($tokenInfo['access_token'])) {
    die('Failed to get access token from Discord');
}

$userUrl = 'https://discord.com/api/users/@me';
$ch = curl_init($userUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $tokenInfo['access_token']
]);

$userResponse = curl_exec($ch);
curl_close($ch);

$userData = json_decode($userResponse, true);

if (!isset($userData['id'])) {
    die('Failed to get user data from Discord');
}

// ✅ ALLOW ANYONE TO LOGIN - NO RESTRICTIONS
// ✅ PERSISTENT SESSION - Lasts 30 days
$_SESSION['discord_user'] = [
    'id' => $userData['id'],
    'username' => $userData['username'],
    'discriminator' => $userData['discriminator'] ?? '0',
    'avatar' => $userData['avatar'],
    'email' => $userData['email'] ?? null,
    'login_time' => time(),
    'remember' => true // Flag for persistent session
];

header('Location: dashboard.php');
exit;
?>
