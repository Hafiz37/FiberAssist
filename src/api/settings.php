<?php
/**
 * REST API Endpoint: Pengaturan WhatsApp Gateway & Webhook (Fonnte Config)
 * Endpoint: GET / POST /api/settings.php
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

// Auto Setup Table settings jika belum ada
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmtCheck = $pdo->query("SELECT COUNT(*) FROM settings");
    if ($stmtCheck->fetchColumn() == 0) {
        $stmtIns = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmtIns->execute(['wa_phone_number_id', '1224307307443062']);
        $stmtIns->execute(['wa_access_token', 'EAAPJ0KHeNE4BSnneArFUU5bg0RuMTYuL6zI4eZBKPY3TQU6XWHnai8bMf3jAYo2Cfn7WwONYIqzPi2HvMtJF6gGd4g1Yl1u8tV4MliUwqMh7lvwSiHaTlJkfjN6LIfBfrAP4r0MoDkDIRMmOg07IoKEiK1ZBzpH0RimiDwgzordhF69Sw6MyqItIYBPwZDZD']);
        $stmtIns->execute(['webhook_url', 'http://localhost/FiberAssist/api/webhook_wa.php']);
        $stmtIns->execute(['engine_url', 'http://localhost/FiberAssist/src/api/diagnosa.php']);
    }
} catch (Exception $e) {
    // Ignore if table exists
}

$method = $_SERVER['REQUEST_METHOD'];

// GET: Ambil seluruh data konfigurasi
if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $rows = $stmt->fetchAll();
        $settings = [];

        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }

        // Fallback default dari config/whatsapp.php jika belum tersimpan di DB
        $configFile = require __DIR__ . '/../config/whatsapp.php';
        if (empty($settings['wa_phone_number_id'])) {
            $settings['wa_phone_number_id'] = $configFile['phone_number_id'] ?? '1224307307443062';
        }
        if (empty($settings['wa_access_token'])) {
            $settings['wa_access_token'] = $configFile['access_token'] ?? '';
        }

        echo json_encode([
            'status' => 'SUCCESS',
            'data' => $settings
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Gagal mengambil pengaturan: ' . $e->getMessage()
        ]);
    }
    exit();
}

// POST: Simpan pembaruan konfigurasi (Khusus Role ADMIN)
if ($method === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Akses ditolak. Fitur pengaturan hanya untuk Administrator.'
        ]);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);

    $wa_phone_number_id = trim($input['wa_phone_number_id'] ?? '1224307307443062');
    $wa_access_token = trim($input['wa_access_token'] ?? '');
    $webhook_url = trim($input['webhook_url'] ?? '');
    $engine_url = trim($input['engine_url'] ?? '');

    try {
        $stmtUpsert = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        $stmtUpsert->execute(['wa_phone_number_id', $wa_phone_number_id]);
        $stmtUpsert->execute(['wa_access_token', $wa_access_token]);
        $stmtUpsert->execute(['webhook_url', $webhook_url]);
        $stmtUpsert->execute(['engine_url', $engine_url]);

        // Sinkronisasi ke file config/whatsapp.php
        $configContent = "<?php\n/**\n * Konfigurasi Meta WhatsApp Cloud API (Official)\n */\n\nreturn [\n    'phone_number_id' => '{$wa_phone_number_id}',\n    'waba_id'         => '1681647470150968',\n    'phone_number'     => '+15551662346',\n    'access_token'    => '{$wa_access_token}',\n    'api_version'     => 'v20.0',\n    'api_url'         => 'https://graph.facebook.com/v20.0/{$wa_phone_number_id}/messages'\n];\n";
        file_put_contents(__DIR__ . '/../config/whatsapp.php', $configContent);

        echo json_encode([
            'status' => 'SUCCESS',
            'message' => 'Pengaturan Meta WhatsApp Cloud API berhasil disimpan.'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Gagal menyimpan pengaturan: ' . $e->getMessage()
        ]);
    }
    exit();
}

http_response_code(405);
echo json_encode(['status' => 'ERROR', 'message' => 'Method tidak didukung.']);
