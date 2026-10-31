<?php
/**
 * REST API Endpoint: Riwayat Sesi Diagnosa WhatsApp (Session Logs)
 * Endpoint: GET / DELETE /api/sesi.php
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

// Proteksi: Pengguna harus login (CS atau ADMIN)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Silakan login terlebih dahulu.'
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// GET: Ambil daftar seluruh sesi diagnosa
if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM sesi_diagnosa ORDER BY updated_at DESC");
        $sessions = $stmt->fetchAll();

        foreach ($sessions as &$s) {
            $s['fakta_terkumpul'] = json_decode($s['fakta_terkumpul'], true) ?? [];
        }

        echo json_encode([
            'status' => 'SUCCESS',
            'total' => count($sessions),
            'data' => $sessions
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Gagal mengambil data sesi: ' . $e->getMessage()
        ]);
    }
    exit();
}

// DELETE: Hapus log sesi diagnosa tertentu
if ($method === 'DELETE') {
    $inputJSON = json_decode(file_get_contents('php://input'), true);
    $id_sesi = intval($inputJSON['id_sesi'] ?? 0);

    if ($id_sesi <= 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'ID Sesi tidak valid.'
        ]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM sesi_diagnosa WHERE id_sesi = ?");
        $stmt->execute([$id_sesi]);

        echo json_encode([
            'status' => 'SUCCESS',
            'message' => "Log sesi #{$id_sesi} berhasil dihapus."
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Gagal menghapus sesi: ' . $e->getMessage()
        ]);
    }
    exit();
}

http_response_code(405);
echo json_encode(['status' => 'ERROR', 'message' => 'Method tidak didukung.']);
