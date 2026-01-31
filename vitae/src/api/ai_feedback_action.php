<?php
/**
 * API: Mark AI Feedback as Implemented
 * Marca uma sugestão como implementada e cria notificação para o usuário
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Apenas admins podem acessar
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$feedbackId = trim($input['feedback_id'] ?? '');
$action = trim($input['action'] ?? 'implement'); // implement or reject

if (empty($feedbackId)) {
    echo json_encode(['success' => false, 'message' => 'ID do feedback não informado']);
    exit;
}

// Carrega feedbacks
$feedbackFile = __DIR__ . '/../logs/ai_feedback.json';
if (!file_exists($feedbackFile)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo de feedbacks não encontrado']);
    exit;
}

$feedbacks = json_decode(file_get_contents($feedbackFile), true) ?: [];

// Encontra o feedback (suporta IDs normais e legacy_INDEX)
$targetFeedback = null;
$targetIndex = null;

// Verifica se é um ID legado (legacy_X)
if (strpos($feedbackId, 'legacy_') === 0) {
    $legacyIndex = (int)str_replace('legacy_', '', $feedbackId);
    if (isset($feedbacks[$legacyIndex])) {
        $targetFeedback = $feedbacks[$legacyIndex];
        $targetIndex = $legacyIndex;
        // Adiciona ID ao feedback legado
        $feedbacks[$legacyIndex]['id'] = $feedbackId;
    }
} else {
    // Busca por ID normal
    foreach ($feedbacks as $index => $fb) {
        if (isset($fb['id']) && $fb['id'] === $feedbackId) {
            $targetFeedback = $fb;
            $targetIndex = $index;
            break;
        }
    }
}

$targetUserId = isset($input['target_user_id']) ? (int)$input['target_user_id'] : null;

if ($targetFeedback === null) {
    echo json_encode(['success' => false, 'message' => 'Feedback não encontrado']);
    exit;
}

// Atualiza o user_id se fornecido manualmente
if ($targetUserId && empty($feedbacks[$targetIndex]['user_id'])) {
    $feedbacks[$targetIndex]['user_id'] = $targetUserId;
    $targetFeedback['user_id'] = $targetUserId; // Atualiza cópia local
}

// Atualiza o status
$newStatus = ($action === 'implement') ? 'implemented' : 'rejected';
$feedbacks[$targetIndex]['status'] = $newStatus;
$feedbacks[$targetIndex]['implemented_at'] = date('Y-m-d H:i:s');

// Salva feedbacks atualizados
file_put_contents(
    $feedbackFile, 
    json_encode($feedbacks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// Se tem user_id, cria notificação no banco
if (!empty($targetFeedback['user_id']) && $action === 'implement') {
    try {
        // Verifica se tabela de notificações existe, senão cria
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT,
                data JSON,
                is_read TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_read (user_id, is_read)
            )
        ");
        
        // Cria notificação
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, data)
            VALUES (?, 'ai_feedback_implemented', ?, ?, ?)
        ");
        
        $title = "✨ Sua sugestão foi implementada!";
        $message = "A competência \"{$targetFeedback['suggestion']}\" que você sugeriu já está disponível na Varinha Mágica. Experimente agora!";
        $data = json_encode([
            'feedback_id' => $feedbackId,
            'suggestion' => $targetFeedback['suggestion']
        ]);
        
        $stmt->execute([
            $targetFeedback['user_id'],
            $title,
            $message,
            $data
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Feedback marcado como implementado! Usuário será notificado.',
            'user_notified' => true
        ]);
    } catch (PDOException $e) {
        // Mesmo se notificação falhar, feedback foi atualizado
        echo json_encode([
            'success' => true,
            'message' => 'Feedback atualizado, mas erro ao notificar usuário.',
            'user_notified' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => true,
        'message' => $action === 'implement' ? 'Feedback marcado como implementado.' : 'Feedback rejeitado.',
        'user_notified' => false
    ]);
}
