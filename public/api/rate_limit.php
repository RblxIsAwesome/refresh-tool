<?php
/**
 * Rate Limiting & IP Ban System
 * 
 * Prevents abuse by limiting requests per IP address
 */

// Rate limit storage file
define('RATE_LIMIT_FILE', sys_get_temp_dir() . '/rate_limits.json');
define('IP_BAN_FILE', sys_get_temp_dir() . '/ip_bans.json');

/**
 * Check if IP is banned
 */
function checkIPBan() {
    $ip = getUserIP();
    
    if (!file_exists(IP_BAN_FILE)) {
        file_put_contents(IP_BAN_FILE, json_encode([]));
        return;
    }
    
    $bans = json_decode(file_get_contents(IP_BAN_FILE), true) ?: [];
    
    if (isset($bans[$ip])) {
        $banData = $bans[$ip];
        $banExpiry = $banData['expiry'] ?? 0;
        
        if (time() < $banExpiry) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Your IP has been temporarily banned due to suspicious activity',
                'ban_expires' => date('Y-m-d H:i:s', $banExpiry)
            ]);
            exit;
        } else {
            // Ban expired, remove it
            unset($bans[$ip]);
            file_put_contents(IP_BAN_FILE, json_encode($bans));
        }
    }
}

/**
 * Check rate limit for IP
 * 
 * @param int $maxRequests Maximum requests allowed
 * @param int $timeWindow Time window in seconds
 */
function checkRateLimit($maxRequests = 3, $timeWindow = 60) {
    $ip = getUserIP();
    
    if (!file_exists(RATE_LIMIT_FILE)) {
        file_put_contents(RATE_LIMIT_FILE, json_encode([]));
    }
    
    $rateLimits = json_decode(file_get_contents(RATE_LIMIT_FILE), true) ?: [];
    
    $currentTime = time();
    
    // Clean old entries
    foreach ($rateLimits as $rip => $data) {
        if ($currentTime - $data['first_request'] > $timeWindow) {
            unset($rateLimits[$rip]);
        }
    }
    
    // Check current IP
    if (!isset($rateLimits[$ip])) {
        $rateLimits[$ip] = [
            'count' => 1,
            'first_request' => $currentTime
        ];
    } else {
        $rateLimits[$ip]['count']++;
        
        // Check if exceeded
        if ($rateLimits[$ip]['count'] > $maxRequests) {
            $timeLeft = $timeWindow - ($currentTime - $rateLimits[$ip]['first_request']);
            
            // Ban if too many violations
            if ($rateLimits[$ip]['count'] > $maxRequests * 3) {
                banIP($ip, 3600); // Ban for 1 hour
            }
            
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Rate limit exceeded. Please wait before trying again.',
                'retry_after' => max(1, $timeLeft)
            ]);
            exit;
        }
    }
    
    file_put_contents(RATE_LIMIT_FILE, json_encode($rateLimits));
}

/**
 * Ban an IP address
 * 
 * @param string $ip IP address to ban
 * @param int $duration Ban duration in seconds
 */
function banIP($ip, $duration = 3600) {
    if (!file_exists(IP_BAN_FILE)) {
        file_put_contents(IP_BAN_FILE, json_encode([]));
    }
    
    $bans = json_decode(file_get_contents(IP_BAN_FILE), true) ?: [];
    
    $bans[$ip] = [
        'banned_at' => time(),
        'expiry' => time() + $duration,
        'reason' => 'Rate limit exceeded'
    ];
    
    file_put_contents(IP_BAN_FILE, json_encode($bans));
    
    error_log("IP banned: $ip for $duration seconds");
}

/**
 * Get user's real IP address
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}
?>
