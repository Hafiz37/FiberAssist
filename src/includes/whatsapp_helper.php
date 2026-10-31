<?php

function sendWhatsAppTextMessage($to, $textMessage) {
    $config = require __DIR__ . '/../config/whatsapp.php';
    
    $to = preg_replace('/[^0-9]/', '', $to);
    if (substr($to, 0, 1) === '0') {
        $to = '62' . substr($to, 1);
    }
    
    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $to,
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => (string)$textMessage
        ]
    ];

    $ch = curl_init($config['api_url']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['access_token'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true) ?? $response;
    $messageId = $responseData['messages'][0]['id'] ?? null;

    logMessageToDatabase($to, $textMessage, $httpCode, $responseData, $messageId);

    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'response' => $responseData,
        'curl_error' => $curlErr,
        'message_id' => $messageId
    ];
}

function logMessageToDatabase($waId, $messageBody, $httpCode, $response, $messageId = null) {
    try {
        require_once __DIR__ . '/../config/database.php';
        
        $status = ($httpCode >= 200 && $httpCode < 300) ? 'sent' : 'failed';
        $errorCode = null;
        $errorTitle = null;
        $errorMessage = null;

        if (isset($response['error'])) {
            $errorCode = $response['error']['code'] ?? null;
            $errorTitle = $response['error']['message'] ?? null;
            $errorMessage = $response['error']['error_data']['details'] ?? json_encode($response['error']);
        }

        $stmt = $pdo->prepare("
            INSERT INTO message_logs (wa_id, message_id, message_body, status, error_code, error_title, error_message, http_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$waId, $messageId, $messageBody, $status, $errorCode, $errorTitle, $errorMessage, $httpCode]);
    } catch (Exception $e) {
        error_log("Failed to log message: " . $e->getMessage());
    }
}

function handleWhatsAppError($errorCode, $waId, $errorDetails) {
    $logDir = __DIR__ . '/../../logs';
    
    switch ($errorCode) {
        case 130497:
            $logMessage = "[ERROR 130497] Country restricted: WA {$waId} - {$errorDetails}\n";
            $logMessage .= "ACTION: Add {$waId} to Test Numbers in Meta Dashboard\n";
            break;
        case 131026:
            $logMessage = "[ERROR 131026] Message undeliverable: WA {$waId}\n";
            break;
        case 131047:
            $logMessage = "[ERROR 131047] Re-engagement message: WA {$waId}\n";
            break;
        default:
            $logMessage = "[ERROR {$errorCode}] {$errorDetails} - WA {$waId}\n";
    }
    
    @file_put_contents($logDir . '/whatsapp_errors.log', date('[Y-m-d H:i:s] ') . $logMessage, FILE_APPEND);
    
    return $logMessage;
}
