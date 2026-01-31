<?php
/**
 * Google OAuth - Iniciar Login
 * Redireciona o usuário para a tela de login do Google
 */
session_start();

$oauth = require __DIR__ . '/config/oauth.php';
$google = $oauth['google'];

// Gera state para segurança (CSRF protection)
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Salva URL de retorno (se veio de alguma página específica)
$_SESSION['oauth_return_url'] = $_GET['return'] ?? 'dashboard.php';

// Monta URL de autorização do Google
$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id'     => $google['client_id'],
    'redirect_uri'  => $google['redirect_uri'],
    'response_type' => 'code',
    'scope'         => $google['scope'],
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account' // Sempre mostra seleção de conta
]);

// Redireciona
header('Location: ' . $authUrl);
exit;
?>
