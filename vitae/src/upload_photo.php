<?php
/**
 * Secure Photo Upload Handler
 * Security Check: MIME Type validation, Extension whitelisting, Size limit, Unique naming.
 */
require_once __DIR__ . '/includes/auth_functions.php';

// Apenas usuários logados
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado ou erro no upload.']);
    exit;
}

$file = $_FILES['photo'];
$maxSize = 2 * 1024 * 1024; // 2MB

// 1. Validar Tamanho
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Máximo 2MB.']);
    exit;
}

// 2. Validar Tipo (MIME e Extensão)
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowedMimes)) {
    echo json_encode(['success' => false, 'message' => 'Formato não permitido. Use JPG, PNG ou WEBP.']);
    exit;
}

$extension = '';
switch ($mime) {
    case 'image/jpeg': $extension = 'jpg'; break;
    case 'image/png': $extension = 'png'; break;
    case 'image/webp': $extension = 'webp'; break;
}

// 3. Gerar Nome Seguro e Único
// Hash do arquivo + UserID para evitar colisão e adivinhação
$hash = md5_file($file['tmp_name']);
$userId = $_SESSION['user_id'];
$fileName = "user_{$userId}_{$hash}.{$extension}";

// 4. Caminho de Destino
// Certifique-se que o diretório existe e tem permissão
$uploadDir = __DIR__ . '/public/uploads/photos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destination = $uploadDir . $fileName;
$publicUrl = "/public/uploads/photos/" . $fileName;

// 5. Mover Arquivo
if (move_uploaded_file($file['tmp_name'], $destination)) {
    // Opcional: Otimizar imagem usando GD aqui se necessário
    echo json_encode(['success' => true, 'url' => $publicUrl]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno ao salvar arquivo.']);
}
?>
