<?php
/**
 * Webhook Asaas
 * Recebe notificações de pagamentos do Asaas e atualiza o sistema
 * 
 * URL para cadastrar no Asaas: https://curriculovitaepro.com.br/webhook/asaas.php
 */

// Carrega configurações
require_once __DIR__ . '/../config/database.php';
$asaasConfig = require __DIR__ . '/../config/asaas.php';

// Log de debug (opcional, remover em produção)
$input = file_get_contents('php://input');
error_log("[Asaas Webhook] Recebido: " . $input);

// Verifica se é uma requisição válida (POST com JSON)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Decodifica o payload
$payload = json_decode($input, true);

if (!$payload || !isset($payload['event'])) {
    http_response_code(400);
    exit('Invalid payload');
}

// Token de segurança (opcional, mas recomendado)
$expectedToken = $asaasConfig['webhook_token'];
if (!empty($expectedToken)) {
    // O Asaas envia o header 'asaas-webhook-token' ou 'access_token'
    $receivedToken = $_SERVER['HTTP_ASAAS_WEBHOOK_TOKEN'] ?? $_SERVER['HTTP_ACCESS_TOKEN'] ?? '';
    if ($receivedToken !== $expectedToken) {
        http_response_code(401);
        error_log("[Asaas Webhook] Token inválido recebido: $receivedToken");
        exit('Unauthorized');
    }
}

// Extrai informações do evento
$event = $payload['event'];
$payment = $payload['payment'] ?? null;

// Eventos que nos interessam
switch ($event) {
    
    // ==========================================
    // PAGAMENTO CONFIRMADO (Liberação do Plano)
    // ==========================================
    case 'PAYMENT_CONFIRMED':
    case 'PAYMENT_RECEIVED':
        if ($payment) {
            activatePlan($pdo, $payment);
        }
        break;
    
    // ==========================================
    // ASSINATURA CRIADA
    // ==========================================
    case 'SUBSCRIPTION_CREATED':
        // Apenas logar, a ativação real vem quando o pagamento é confirmado
        error_log("[Asaas Webhook] Nova assinatura criada: " . json_encode($payload));
        break;
    
    // ==========================================
    // PAGAMENTO VENCIDO / FALHOU (Rebaixar Plano)
    // ==========================================
    case 'PAYMENT_OVERDUE':
    case 'PAYMENT_DELETED':
    case 'SUBSCRIPTION_DELETED':
        if ($payment) {
            deactivatePlan($pdo, $payment);
        }
        break;
    
    default:
        // Evento não tratado, apenas logamos
        error_log("[Asaas Webhook] Evento não tratado: $event");
        break;
}

// Responde OK para o Asaas
http_response_code(200);
echo json_encode(['status' => 'processed']);

// ========================================
// FUNÇÕES AUXILIARES
// ========================================

/**
 * Ativa o plano PRO do usuário
 */
function activatePlan($pdo, $payment) {
    // Busca o customer no Asaas e encontra o usuário local
    $asaasCustomerId = $payment['customer'] ?? null;
    
    if (!$asaasCustomerId) {
        error_log("[Asaas Webhook] ERRO: Payment sem customer ID");
        return;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE asaas_id = ?");
    $stmt->execute([$asaasCustomerId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        error_log("[Asaas Webhook] Usuário não encontrado para asaas_id: $asaasCustomerId");
        return;
    }
    
    // Define expiração para 1 mês a partir de agora (ou baseado no ciclo da assinatura)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+35 days')); // 35 dias dá margem
    
    $update = $pdo->prepare("UPDATE users SET plan = 'pro', plan_expires_at = ? WHERE id = ?");
    $update->execute([$expiresAt, $user['id']]);
    
    error_log("[Asaas Webhook] ✅ Plano PRO ativado para user ID: " . $user['id']);
}

/**
 * Desativa o plano PRO do usuário (volta para free)
 */
function deactivatePlan($pdo, $payment) {
    $asaasCustomerId = $payment['customer'] ?? null;
    
    if (!$asaasCustomerId) return;
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE asaas_id = ?");
    $stmt->execute([$asaasCustomerId]);
    $user = $stmt->fetch();
    
    if (!$user) return;
    
    $update = $pdo->prepare("UPDATE users SET plan = 'free', plan_expires_at = NULL WHERE id = ?");
    $update->execute([$user['id']]);
    
    error_log("[Asaas Webhook] ⚠️ Plano rebaixado para FREE - user ID: " . $user['id']);
}
