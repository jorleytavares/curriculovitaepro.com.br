<?php
/**
 * API: Save AI Feedback
 * Salva sugestões de áreas/competências não encontradas
 * Inclui user_id para notificação quando implementado
 */

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$jobTitle = trim($input['job_title'] ?? '');
$userText = trim($input['user_text'] ?? '');
$suggestion = trim($input['suggestion'] ?? '');

if (empty($jobTitle) && empty($suggestion)) {
    echo json_encode(['success' => false, 'message' => 'Dados insuficientes']);
    exit;
}

// Cria a pasta de logs se não existir
$feedbackDir = __DIR__ . '/../logs';
if (!is_dir($feedbackDir)) {
    mkdir($feedbackDir, 0755, true);
}

// Salva o feedback em arquivo JSON
$feedbackFile = $feedbackDir . '/ai_feedback.json';
$feedbacks = [];

if (file_exists($feedbackFile)) {
    $content = file_get_contents($feedbackFile);
    $feedbacks = json_decode($content, true) ?: [];
}

// Gera ID único para o feedback
$feedbackId = uniqid('fb_', true);

$feedbacks[] = [
    'id' => $feedbackId,
    'date' => date('Y-m-d H:i:s'),
    'user_id' => $_SESSION['user_id'] ?? null,
    'job_title' => $jobTitle,
    'user_text' => $userText,
    'suggestion' => $suggestion,
    'status' => 'pending', // pending, implemented, rejected
    'implemented_at' => null,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
];

$saved = file_put_contents(
    $feedbackFile, 
    json_encode($feedbacks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

if ($saved !== false) {
    echo json_encode([
        'success' => true,
        'message' => 'Obrigado pelo feedback! Você será notificado quando implementarmos.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar feedback'
    ]);
}
