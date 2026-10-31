<?php
/**
 * Webhook Receiver Meta WhatsApp Cloud API (Official)
 * Endpoint: GET/POST /api/webhook_wa.php
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ForwardChaining.php';
require_once __DIR__ . '/../includes/whatsapp_helper.php';

define('VERIFY_TOKEN', 'netfix_webhook_token_2026');

// ==========================================
// 1. VERIFIKASI WEBHOOK META (HTTP GET)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? null;
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? null;
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? null;

    if ($mode === 'subscribe' && $token === VERIFY_TOKEN) {
        http_response_code(200);
        echo $challenge;
        exit();
    } else {
        http_response_code(403);
        echo "Forbidden: Invalid Verify Token";
        exit();
    }
}

// ==========================================
// 2. TERIMA PESAN MASUK & STATUS WHATSAPP (HTTP POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $logDir = __DIR__ . '/../../logs';
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    @file_put_contents($logDir . '/webhook_wa.log', date('[Y-m-d H:i:s] ') . $rawInput . PHP_EOL, FILE_APPEND);

    // ==========================================
    // HANDLE STATUS UPDATES (delivery, failed, etc)
    // ==========================================
    if (isset($data['entry'][0]['changes'][0]['value']['statuses'][0])) {
        $statusObj = $data['entry'][0]['changes'][0]['value']['statuses'][0];
        
        $messageId = $statusObj['id'] ?? null;
        $status = $statusObj['status'] ?? null;
        $recipientId = $statusObj['recipient_id'] ?? null;
        
        if ($status === 'failed' && isset($statusObj['errors'][0])) {
            $error = $statusObj['errors'][0];
            $errorCode = $error['code'] ?? null;
            $errorTitle = $error['title'] ?? null;
            $errorMessage = $error['message'] ?? null;
            
            // Update database
            try {
                $stmt = $pdo->prepare("
                    UPDATE message_logs 
                    SET status = 'failed', 
                        error_code = ?, 
                        error_title = ?, 
                        error_message = ?,
                        updated_at = NOW()
                    WHERE message_id = ? OR wa_id = ?
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $stmt->execute([$errorCode, $errorTitle, $errorMessage, $messageId, $recipientId]);
            } catch (Exception $e) {
                @file_put_contents($logDir . '/webhook_wa.log', date('[Y-m-d H:i:s] ') . "DB UPDATE ERROR: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
            
            // Log error khusus
            handleWhatsAppError($errorCode, $recipientId, $errorMessage);
            
            @file_put_contents(
                $logDir . '/webhook_wa.log',
                date('[Y-m-d H:i:s] ') . "STATUS FAILED: MsgID={$messageId}, WA={$recipientId}, Error={$errorCode} - {$errorTitle}\n",
                FILE_APPEND
            );
        } elseif ($status === 'delivered' || $status === 'sent' || $status === 'read') {
            try {
                $stmt = $pdo->prepare("
                    UPDATE message_logs 
                    SET status = ?, updated_at = NOW()
                    WHERE message_id = ?
                ");
                $stmt->execute([$status, $messageId]);
            } catch (Exception $e) {
                // Silent fail
            }
        }
        
        http_response_code(200);
        echo json_encode(['status' => 'STATUS_RECEIVED']);
        exit();
    }

    // ==========================================
    // HANDLE INCOMING MESSAGES
    // ==========================================
    if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
        $messageObj = $data['entry'][0]['changes'][0]['value']['messages'][0];
        
        $no_wa = $messageObj['from'] ?? null;
        $type = $messageObj['type'] ?? 'text';
        $user_input = null;

        if ($type === 'text') {
            $user_input = trim($messageObj['text']['body'] ?? '');
        } else if ($type === 'interactive') {
            $user_input = $messageObj['interactive']['button_reply']['id'] ?? $messageObj['interactive']['list_reply']['id'] ?? null;
        }

        if ($no_wa && !empty($user_input)) {
            try {
                $engine = new ForwardChainingEngine($pdo);
                $result = $engine->processInput($no_wa, $user_input);

                $replyMessage = $result['message'] ?? 'Maaf, sistem sedang mengalami kendala. Silakan coba beberapa saat lagi.';

                $sendResult = sendWhatsAppTextMessage($no_wa, $replyMessage);

                @file_put_contents(
                    $logDir . '/webhook_wa.log',
                    date('[Y-m-d H:i:s] ') . "REPLY TO {$no_wa}: Status HTTP " . ($sendResult['http_code'] ?? 'ERR') . ", MsgID: " . ($sendResult['message_id'] ?? 'NULL') . PHP_EOL,
                    FILE_APPEND
                );

            } catch (Exception $e) {
                @file_put_contents($logDir . '/webhook_wa.log', date('[Y-m-d H:i:s] ') . "ERROR: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'SUCCESS']);
    exit();
}
