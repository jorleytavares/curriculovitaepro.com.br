<?php
/**
 * Site Settings Helper
 * Gerencia configurações globais do site (AdSense, Analytics, etc.)
 */

function getSetting($pdo, $key, $default = '') {
    try {
        $stmt = $pdo->prepare("SELECT value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : $default;
    } catch(PDOException $e) {
        return $default;
    }
}

function setSetting($pdo, $key, $value) {
    try {
        // Upsert (Insert or Update)
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE value = VALUES(value)");
        return $stmt->execute([$key, $value]);
    } catch(PDOException $e) {
        return false;
    }
}

// Cria tabela de configurações se não existir
function initSettingsTable($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        return true;
    } catch(PDOException $e) {
        return false;
    }
}
?>
