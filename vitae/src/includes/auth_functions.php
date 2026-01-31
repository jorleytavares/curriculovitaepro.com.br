<?php
/**
 * Authentication Module
 * Refactored to use Services\AuthService
 */

use Services\AuthService;

// Ensure AuthService is available (via global $authService or create new)
// Assuming $pdo comes from database.php which is usually included before this.

if (!isset($pdo)) {
    // Fallback if pdo not set (should not happen in current arch)
    global $pdo;
}

// Global instance helper
function getAuthService() {
    global $pdo;
    static $service;
    if (!$service) {
        $service = new AuthService($pdo);
    }
    return $service;
}

function getClientIp(): string {
    return getAuthService()->getClientIp();
}

function isDisposableEmail(string $email): bool {
    return getAuthService()->isDisposableEmail($email);
}

function registerUser(PDO $pdo, string $name, string $email, string $password): array {
    // Ignoring passed $pdo and using service's pdo (which should be same)
    return getAuthService()->register($name, $email, $password);
}

function loginUser(PDO $pdo, string $email, string $password): array {
    return getAuthService()->login($email, $password);
}

function isLoggedIn(): bool {
    return getAuthService()->isLoggedIn();
}

function isAdmin(): bool {
    return getAuthService()->isAdmin();
}

function requireLogin(): void {
    getAuthService()->requireLogin();
}

function requireAdmin(): void {
    getAuthService()->requireAdmin();
}

function logout(): void {
    getAuthService()->logout();
}
