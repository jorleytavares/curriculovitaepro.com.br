<?php
/**
 * API Endpoint: Resume Analysis
 * Returns JSON with score and suggestions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Manual require since vendor/autoload might be missing
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../services/ResumeAnalyzerService.php';
}

use Services\ResumeAnalyzerService;

// JSON Header
header('Content-Type: application/json');

// Auth Check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Method Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Input Handling
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // Fallback para Form Data normal se não for JSON raw (embora JS vá mandar JSON)
    $input = $_POST;
}

if (empty($input)) {
    echo json_encode(['score' => 0, 'suggestions' => [], 'strengths' => []]);
    exit;
}

// Analyze
try {
    $analyzer = new ResumeAnalyzerService();
    $result = $analyzer->analyze($input);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
