<?php
/**
 * Database Configuration Strategy
 * Pattern: Wrapper for Core\Database Singleton
 */

// Autoload
require_once __DIR__ . '/../vendor/autoload.php';

use Core\Database;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    // Modo direto sem tela de manutenção, conforme solicitado
    error_log("[DB Error] " . $e->getMessage());
    die("Database Connection Error: " . $e->getMessage());
}

// Mantendo compatibilidade com código que usa getEnvValue
if (!function_exists('getEnvValue')) {
    function getEnvValue($key, $default = '') {
        $val = getenv($key);
        if ($val !== false) return $val;
        if (isset($_ENV[$key])) return $_ENV[$key];
        return $default;
    }
}
