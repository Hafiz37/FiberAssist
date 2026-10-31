<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                s.id_sesi,
                s.no_wa,
                s.fakta_terkumpul,
                s.hasil_diagnosa,
                s.updated_at,
                s.status
            FROM sesi_diagnosa s
            WHERE s.status = 'COMPLETED'
            ORDER BY s.updated_at DESC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll();

        $laporan = [];
        foreach ($results as $row) {
            $faktaArray = json_decode($row['fakta_terkumpul'], true) ?? [];
            $hasilDiagnosa = json_decode($row['hasil_diagnosa'], true) ?? null;

            $kode_kerusakan = $hasilDiagnosa['kode_kerusakan'] ?? 'N/A';
            $nama_kerusakan = $hasilDiagnosa['nama_kerusakan'] ?? 'Belum terdiagnosa';
            $solusi = $hasilDiagnosa['solusi'] ?? '-';
            $butuh_teknisi = isset($hasilDiagnosa['butuh_teknisi']) ? (int)$hasilDiagnosa['butuh_teknisi'] : 0;
            $no_tiket = $hasilDiagnosa['no_tiket'] ?? null;
            $kode_rule = $hasilDiagnosa['kode_rule'] ?? null;

            $laporan[] = [
                'id_sesi' => (int)$row['id_sesi'],
                'no_wa' => $row['no_wa'],
                'fakta_terkumpul' => $faktaArray,
                'kode_kerusakan' => $kode_kerusakan,
                'nama_kerusakan' => $nama_kerusakan,
                'solusi' => $solusi,
                'butuh_teknisi' => $butuh_teknisi,
                'no_tiket' => $no_tiket,
                'kode_rule' => $kode_rule,
                'updated_at' => $row['updated_at'],
                'status' => $row['status']
            ];
        }

        echo json_encode([
            'status' => 'SUCCESS',
            'total' => count($laporan),
            'data' => $laporan
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Gagal memuat data laporan: ' . $e->getMessage()
        ]);
    }
    exit();
}

http_response_code(405);
echo json_encode(['status' => 'ERROR', 'message' => 'Method tidak didukung.']);
