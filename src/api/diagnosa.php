<?php
/**
 * REST API Endpoint: Diagnosa Mandiri via WhatsApp Webhook
 * Endpoint: POST /api/diagnosa.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ForwardChaining.php';

// Ambil input JSON dari Body Request atau POST form-data
$inputJSON = json_decode(file_get_contents('php://input'), true);

$no_wa = $inputJSON['no_wa'] ?? $_POST['no_wa'] ?? $_GET['no_wa'] ?? $inputJSON['phone'] ?? $_POST['phone'] ?? $_GET['phone'] ?? $_POST['pengirim'] ?? $_GET['pengirim'] ?? null;
if ($no_wa) {
    $no_wa = preg_replace('/[^0-9]/', '', $no_wa);
}
$user_input = $inputJSON['user_input'] ?? $_POST['user_input'] ?? $_GET['user_input'] ?? $inputJSON['message'] ?? $_POST['message'] ?? $_GET['message'] ?? null;

if (!$no_wa) {
    http_response_code(400);
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Parameter no_wa / phone wajib diisi.'
    ]);
    exit();
}

if ($user_input === null || $user_input === '') {
    $user_input = 'START';
}

try {
    $engine = new ForwardChainingEngine($pdo);
    $result = $engine->processInput($no_wa, $user_input);
    $result['no_wa'] = $no_wa;

    http_response_code(200);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
