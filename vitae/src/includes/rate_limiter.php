<?php
/**
 * Rate Limiting Helper
 * Protects against brute-force attacks using file-based storage
 * 
 * Usage:
 * if (isRateLimited('login', getClientIp(), 5, 300)) {
 *     die('Muitas tentativas. Aguarde 5 minutos.');
 * }
 * incrementAttempts('login', getClientIp());
 */

// Directory to store rate limit data
define('RATE_LIMIT_DIR', __DIR__ . '/../logs/rate_limits');

/**
 * Check if an identifier is rate limited
 * @param string $action The action being rate limited (e.g., 'login', 'register')
 * @param string $identifier The identifier (usually IP address)
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $windowSeconds Time window in seconds
 * @return bool True if rate limited, false if allowed
 */
function isRateLimited(string $action, string $identifier, int $maxAttempts = 5, int $windowSeconds = 300): bool {
    $key = md5($action . ':' . $identifier);
    $file = getRateLimitFile($key);
    
    if (!file_exists($file)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($file), true);
    
    if (!$data || !isset($data['attempts']) || !isset($data['first_attempt'])) {
        return false;
    }
    
    // Check if window has expired
    if (time() - $data['first_attempt'] > $windowSeconds) {
        // Reset the counter
        @unlink($file);
        return false;
    }
    
    return $data['attempts'] >= $maxAttempts;
}

/**
 * Increment attempt counter for an identifier
 * @param string $action The action being rate limited
 * @param string $identifier The identifier (usually IP address)
 */
function incrementAttempts(string $action, string $identifier): void {
    $key = md5($action . ':' . $identifier);
    $file = getRateLimitFile($key);
    
    // Ensure directory exists
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $data = ['attempts' => 0, 'first_attempt' => time()];
    
    if (file_exists($file)) {
        $existing = json_decode(file_get_contents($file), true);
        if ($existing && isset($existing['attempts'])) {
            $data = $existing;
        }
    }
    
    $data['attempts']++;
    $data['last_attempt'] = time();
    
    file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Clear rate limit for an identifier (e.g., after successful login)
 * @param string $action The action
 * @param string $identifier The identifier
 */
function clearRateLimit(string $action, string $identifier): void {
    $key = md5($action . ':' . $identifier);
    $file = getRateLimitFile($key);
    
    if (file_exists($file)) {
        @unlink($file);
    }
}

/**
 * Get the file path for rate limit storage
 * @param string $key The hashed key
 * @return string The file path
 */
function getRateLimitFile(string $key): string {
    // Create subdirectory based on first 2 chars to avoid too many files in one dir
    $subdir = substr($key, 0, 2);
    return RATE_LIMIT_DIR . '/' . $subdir . '/' . $key . '.json';
}

/**
 * Cleanup old rate limit files (run periodically via cron)
 * @param int $maxAgeSeconds Maximum age of files to keep
 */
function cleanupRateLimits(int $maxAgeSeconds = 3600): void {
    if (!is_dir(RATE_LIMIT_DIR)) {
        return;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(RATE_LIMIT_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'json') {
            if (time() - $file->getMTime() > $maxAgeSeconds) {
                @unlink($file->getPathname());
            }
        }
    }
}
?>
