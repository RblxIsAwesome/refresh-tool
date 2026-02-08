<?php
// ================================================
// IP BAN SYSTEM
// ================================================

function getIPBanFile() {
    return __DIR__ . '/ip_bans.json';
}

function isIPBanned($ip) {
    $banFile = getIPBanFile();
    if (!file_exists($banFile)) {
        return false;
    }
    
    $bans = json_decode(file_get_contents($banFile), true) ?: [];
    
    if (isset($bans[$ip])) {
        $ban = $bans[$ip];
        
        if ($ban['permanent']) {
            return true;
        }
        
        if ($ban['expires'] > time()) {
            return true;
        } else {
            unset($bans[$ip]);
            file_put_contents($banFile, json_encode($bans, JSON_PRETTY_PRINT));
            return false;
        }
    }
    
    return false;
}

function banIP($ip, $duration = 3600, $permanent = false) {
    $banFile = getIPBanFile();
    $bans = [];
    
    if (file_exists($banFile)) {
        $bans = json_decode(file_get_contents($banFile), true) ?: [];
    }
    
    $bans[$ip] = [
        'banned_at' => time(),
        'expires' => $permanent ? 0 : (time() + $duration),
        'permanent' => $permanent,
        'reason' => 'Rate limit exceeded'
    ];
    
    file_put_contents($banFile, json_encode($bans, JSON_PRETTY_PRINT));
}

function checkIPBan() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    if (isIPBanned($ip)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Your IP has been banned due to excessive requests']);
        exit;
    }
}

// ================================================
// RATE LIMITING SYSTEM
// ================================================

function getRateLimitFile() {
    return __DIR__ . '/rate_limits.json';
}

function checkRateLimit($maxRequests = 5, $timeWindow = 60) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $rateLimitFile = getRateLimitFile();
    $limits = [];
    
    if (file_exists($rateLimitFile)) {
        $limits = json_decode(file_get_contents($rateLimitFile), true) ?: [];
    }
    
    $currentTime = time();
    
    foreach ($limits as $storedIP => $data) {
        if ($currentTime - $data['first_request'] > $timeWindow) {
            unset($limits[$storedIP]);
        }
    }
    
    if (!isset($limits[$ip])) {
        $limits[$ip] = [
            'count' => 1,
            'first_request' => $currentTime
        ];
    } else {
        $timePassed = $currentTime - $limits[$ip]['first_request'];
        
        if ($timePassed > $timeWindow) {
            $limits[$ip] = [
                'count' => 1,
                'first_request' => $currentTime
            ];
        } else {
            $limits[$ip]['count']++;
            
            if ($limits[$ip]['count'] > $maxRequests) {
                banIP($ip, 3600);
                
                http_response_code(429);
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'Rate limit exceeded. You have been temporarily banned.',
                    'retry_after' => 3600
                ]);
                exit;
            }
        }
    }
    
    file_put_contents($rateLimitFile, json_encode($limits, JSON_PRETTY_PRINT));
}
?>
