<?php
/**
 * API: Enviar Magic Link
 * Gera token e envia email para login sem senha
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/EmailService.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

header('Content-Type: application/json');

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

// 1. Rate Check
$clientIp = getClientIp();
if (isRateLimited('magic_link', $clientIp, 3, 600)) { // 3 tentativas em 10 min
    echo json_encode(['success' => false, 'message' => 'Muitas solicitações. Aguarde 10 minutos.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'E-mail inválido']);
    exit;
}

// 2. Verificar Usuário
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Incrementa rate limit apenas se usuário existe para evitar枚举 (User Enumeration)?
    // Não, melhor incrementar sempre para evitar DOS.
    incrementAttempts('magic_link', $clientIp);

    // 3. Auto-Migração (Garantir colunas)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN magic_token VARCHAR(64) NULL DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN magic_expires_at DATETIME NULL DEFAULT NULL");
        $pdo->exec("CREATE INDEX idx_magic_token ON users(magic_token)");
    } catch (PDOException $e) { /* Ignora se já existe */ }

    // 4. Gerar Token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // 5. Salvar
    $update = $pdo->prepare("UPDATE users SET magic_token = ?, magic_expires_at = ? WHERE id = ?");
    $update->execute([$token, $expires, $user['id']]);

    // 6. Enviar Email
    if (EmailService::sendMagicLink($email, $token)) {
        echo json_encode(['success' => true, 'message' => 'Link enviado! Verifique seu e-mail.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao enviar e-mail.']);
    }

} else {
    // Usuário não encontrado: Simulamos sucesso para segurança (User Enumeration Protection)
    // Mas damos um delay igual ao envio real
    incrementAttempts('magic_link', $clientIp);
    sleep(1);
    echo json_encode(['success' => true, 'message' => 'Link enviado! Verifique seu e-mail.']);
}
?>
