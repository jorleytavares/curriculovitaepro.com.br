<?php
/**
 * CSRF Protection Helper
 * Prevents Cross-Site Request Forgery attacks
 * 
 * Usage:
 * 1. In forms: <?php echo csrfField(); ?>
 * 2. On POST: if (!verifyCsrfToken($_POST['csrf_token'])) { die('Invalid request'); }
 */

/**
 * Generates a CSRF token and stores it in session
 * @return string The generated token
 */
function generateCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Generate new token if not exists or expired (30 min lifetime)
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > 1800) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Returns hidden input field with CSRF token
 * @return string HTML hidden input
 */
function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verifies the submitted CSRF token
 * @param string|null $token The token from form submission
 * @return bool True if valid, false otherwise
 */
function verifyCsrfToken(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Timing-safe comparison
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validates CSRF and dies with error if invalid
 * Use this at the top of POST handlers
 */
function requireValidCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            die('Requisição inválida. Por favor, recarregue a página e tente novamente.');
        }
    }
}
?>
