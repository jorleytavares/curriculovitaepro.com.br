<?php
/**
 * Google OAuth - Callback
 * Processa o retorno do Google e cria/autentica o usuário
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

$oauth = require __DIR__ . '/config/oauth.php';
$google = $oauth['google'];

// Verifica erros do Google
if (isset($_GET['error'])) {
    header('Location: login.php?error=' . urlencode('Login cancelado ou erro: ' . $_GET['error']));
    exit;
}

// Verifica código de autorização
if (!isset($_GET['code'])) {
    header('Location: login.php?error=' . urlencode('Código de autorização não recebido'));
    exit;
}

// Verifica CSRF (state)
if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    header('Location: login.php?error=' . urlencode('Falha na verificação de segurança'));
    exit;
}

// Limpa state usado
unset($_SESSION['oauth_state']);

$code = $_GET['code'];

// 1. Trocar código por access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code'          => $code,
    'client_id'     => $google['client_id'],
    'client_secret' => $google['client_secret'],
    'redirect_uri'  => $google['redirect_uri'],
    'grant_type'    => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($tokenData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded']
]);
$tokenResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    error_log("Google OAuth Token Error: " . $tokenResponse);
    header('Location: login.php?error=' . urlencode('Erro ao obter token de acesso'));
    exit;
}

$tokenJson = json_decode($tokenResponse, true);
$accessToken = $tokenJson['access_token'] ?? null;

if (!$accessToken) {
    header('Location: login.php?error=' . urlencode('Token de acesso inválido'));
    exit;
}

// 2. Buscar dados do usuário
$userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
$ch = curl_init($userInfoUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken]
]);
$userResponse = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($userResponse, true);

if (!isset($userInfo['email'])) {
    header('Location: login.php?error=' . urlencode('Não foi possível obter seu email'));
    exit;
}

$email = $userInfo['email'];
$name = $userInfo['name'] ?? explode('@', $email)[0];
$googleId = $userInfo['sub'] ?? null;
$picture = $userInfo['picture'] ?? null;

// 3. Verificar se usuário já existe
try {
    // Migração: adiciona coluna google_id se não existir
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL");
    } catch(PDOException $e) {}
    
    // Busca por google_id ou email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1");
    $stmt->execute([$googleId, $email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Usuário existe - atualiza google_id se necessário
        if (empty($user['google_id'])) {
            $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?")->execute([$googleId, $user['id']]);
        }
        $userId = $user['id'];
        $userName = $user['name'];
        $userRole = $user['role'] ?? 'user';
    } else {
        // Novo usuário - criar conta
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, google_id, role, plan, created_at) VALUES (?, ?, ?, ?, 'user', 'free', NOW())");
        // Senha aleatória (usuário usa Google para login)
        $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $stmt->execute([$name, $email, $randomPassword, $googleId]);
        
        $userId = $pdo->lastInsertId();
        $userName = $name;
        $userRole = 'user';
    }
    
    // 4. Criar sessão
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $userRole;
    $_SESSION['login_method'] = 'google';
    
    // Redireciona para destino
    $returnUrl = $_SESSION['oauth_return_url'] ?? 'dashboard.php';
    unset($_SESSION['oauth_return_url']);
    
    header('Location: ' . $returnUrl);
    exit;
    
} catch (PDOException $e) {
    error_log("Google OAuth DB Error: " . $e->getMessage());
    header('Location: login.php?error=' . urlencode('Erro ao criar conta. Tente novamente.'));
    exit;
}
?>
