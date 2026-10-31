<?php
/**
 * REST API Endpoint: Manajemen Tiket Gangguan Customer Service
 * Endpoint: GET / POST / DELETE /api/tiket.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Ambil daftar tiket gangguan
if ($method === 'GET') {
    $status = $_GET['status'] ?? null;
    
    if ($status) {
        $stmt = $pdo->prepare("
            SELECT t.*, k.nama_kerusakan, k.solusi, k.butuh_teknisi
            FROM tiket_gangguan t
            JOIN kerusakan k ON t.kode_kerusakan = k.kode_kerusakan
            WHERE t.status_tiket = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$status]);
    } else {
        $stmt = $pdo->prepare("
            SELECT t.*, k.nama_kerusakan, k.solusi, k.butuh_teknisi
            FROM tiket_gangguan t
            JOIN kerusakan k ON t.kode_kerusakan = k.kode_kerusakan
            ORDER BY t.created_at DESC
        ");
        $stmt->execute();
    }

    $tickets = $stmt->fetchAll();

    echo json_encode([
        'status' => 'SUCCESS',
        'total' => count($tickets),
        'data' => $tickets
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// POST: Ubah status tiket (OPEN -> IN_PROGRESS -> RESOLVED)
if ($method === 'POST') {
    $inputJSON = json_decode(file_get_contents('php://input'), true);
    
    $no_tiket = $inputJSON['no_tiket'] ?? $_POST['no_tiket'] ?? null;
    $status_tiket = $inputJSON['status_tiket'] ?? $_POST['status_tiket'] ?? null;

    if (!$no_tiket || !$status_tiket) {
        http_response_code(400);
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Parameter no_tiket dan status_tiket wajib diisi.'
        ]);
        exit();
    }

    $allowedStatus = ['OPEN', 'IN_PROGRESS', 'RESOLVED'];
    if (!in_array($status_tiket, $allowedStatus)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Status tiket harus salah satu dari: OPEN, IN_PROGRESS, RESOLVED'
        ]);
        exit();
    }

    $stmt = $pdo->prepare("UPDATE tiket_gangguan SET status_tiket = ? WHERE no_tiket = ?");
    $stmt->execute([$status_tiket, $no_tiket]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'SUCCESS',
            'message' => "Status tiket #{$no_tiket} berhasil diubah menjadi {$status_tiket}."
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'ERROR',
            'message' => "Tiket #{$no_tiket} tidak ditemukan atau status tidak berubah."
        ]);
    }
    exit();
}

// DELETE: Hapus tiket gangguan
if ($method === 'DELETE') {
    $inputJSON = json_decode(file_get_contents('php://input'), true);
    $no_tiket = $inputJSON['no_tiket'] ?? $_GET['no_tiket'] ?? null;

    if (!$no_tiket) {
        http_response_code(400);
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Parameter no_tiket wajib diisi.'
        ]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM tiket_gangguan WHERE no_tiket = ?");
        $stmt->execute([$no_tiket]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'SUCCESS',
                'message' => "Tiket #{$no_tiket} berhasil dihapus."
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'status' => 'ERROR',
                'message' => "Tiket #{$no_tiket} tidak ditemukan."
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Gagal menghapus tiket: ' . $e->getMessage()
        ]);
    }
    exit();
}

http_response_code(405);
echo json_encode(['status' => 'ERROR', 'message' => 'Method tidak didukung.']);
