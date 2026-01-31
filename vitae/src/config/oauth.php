<?php
/**
 * OAuth Configuration
 * Credenciais para login social
 */

// Se existir arquivo local com segredos, usa ele
if (file_exists(__DIR__ . '/oauth.local.php')) {
    return require __DIR__ . '/oauth.local.php';
}

// Detecta ambiente (produção ou desenvolvimento)
$isProduction = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'curriculovitaepro.com.br') !== false;

$baseUrl = $isProduction 
    ? 'https://curriculovitaepro.com.br' 
    : 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

return [
    'google' => [
        'client_id'     => getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET',
        'redirect_uri'  => $baseUrl . '/auth_google_callback.php',
        'scope'         => 'openid email profile'
    ],
    'linkedin' => [
        'client_id'     => getenv('LINKEDIN_CLIENT_ID') ?: '',
        'client_secret' => getenv('LINKEDIN_CLIENT_SECRET') ?: '',
        'redirect_uri'  => $baseUrl . '/auth_linkedin_callback.php',
        'scope'         => 'openid profile email'
    ]
];
?>