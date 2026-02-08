<?php
// ================================================
// SECURITY: Block direct access
// ================================================
if (!defined('ACCESS_ALLOWED')) {
    http_response_code(403);
    exit('Direct access not allowed');
}

// ================================================
// CONFIG: Load environment variables
// ================================================
function getConfig() {
    $envFile = __DIR__ . '/env.txt';
    if (!is_file($envFile)) {
        return [];
    }
    
    $vars = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    return is_array($vars) ? $vars : [];
}
?>