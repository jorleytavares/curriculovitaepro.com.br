<?php
/**
 * Handler de Login via Magic Link
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_functions.php';

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL);

if (!$token || !$email) {
    header("Location: login.php?msg=" . urlencode("Link inválido."));
    exit;
}

// 1. Buscar usuário com token válido
$stmt = $pdo->prepare("
    SELECT id, name, role, magic_expires_at 
    FROM users 
    WHERE email = ? AND magic_token = ?
");
$stmt->execute([$email, $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: login.php?msg=" . urlencode("Link inválido ou já utilizado."));
    exit;
}

// 2. Verificar Expiração
if (strtotime($user['magic_expires_at']) < time()) {
    header("Location: login.php?msg=" . urlencode("Este link expirou. Solicite um novo."));
    exit;
}

// 3. Login com Sucesso
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['role'] = $user['role'] ?? 'user';
$_SESSION['logged_in'] = true;

// 4. Invalidar Token (Segurança: One-time use)
$clear = $pdo->prepare("UPDATE users SET magic_token = NULL, magic_expires_at = NULL WHERE id = ?");
$clear->execute([$user['id']]);

// 5. Redirecionar
header("Location: dashboard.php");
exit;
?>
