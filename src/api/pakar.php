<?php
/**
 * API Manajemen Data Pakar (Gejala, Kerusakan, dan Aturan Forward Chaining)
 * Khusus Role Administrator
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Proteksi Akses: Harus Login dan Berperan ADMIN (kecuali GET jika diizinkan)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Akses ditolak. Fitur ini hanya untuk Administrator.'
    ]);
    exit();
}

$target = $_GET['target'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// -------------------------------------------------------------
// 1. MANAJEMEN GEJALA
// -------------------------------------------------------------
if ($target === 'gejala') {
    if ($method === 'GET') {
        try {
            $stmt = $pdo->query("SELECT * FROM gejala ORDER BY kode_gejala ASC");
            echo json_encode(['status' => 'SUCCESS', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage()]);
        }
        exit();
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id_gejala = intval($input['id_gejala'] ?? 0);
        $kode_gejala = strtoupper(trim($input['kode_gejala'] ?? ''));
        $nama_gejala = trim($input['nama_gejala'] ?? '');
        $pertanyaan_wa = trim($input['pertanyaan_wa'] ?? '');

        if (empty($kode_gejala) || empty($nama_gejala) || empty($pertanyaan_wa)) {
            echo json_encode(['status' => 'ERROR', 'message' => 'Kode, nama gejala, dan pertanyaan WA wajib diisi.']);
            exit();
        }

        try {
            if ($id_gejala > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE gejala SET kode_gejala = :kode, nama_gejala = :nama, pertanyaan_wa = :tanya WHERE id_gejala = :id");
                $stmt->execute([':kode' => $kode_gejala, ':nama' => $nama_gejala, ':tanya' => $pertanyaan_wa, ':id' => $id_gejala]);
                $msg = 'Data gejala berhasil diperbarui.';
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO gejala (kode_gejala, nama_gejala, pertanyaan_wa) VALUES (:kode, :nama, :tanya)");
                $stmt->execute([':kode' => $kode_gejala, ':nama' => $nama_gejala, ':tanya' => $pertanyaan_wa]);
                $msg = 'Gejala baru berhasil ditambahkan.';
            }
            echo json_encode(['status' => 'SUCCESS', 'message' => $msg]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'ERROR', 'message' => 'Gagal simpan gejala: ' . $e->getMessage()]);
        }
        exit();
    }

    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id_gejala = intval($input['id_gejala'] ?? 0);

        try {
            $stmt = $pdo->prepare("DELETE FROM gejala WHERE id_gejala = :id");
            $stmt->execute([':id' => $id_gejala]);
            echo json_encode(['status' => 'SUCCESS', 'message' => 'Gejala berhasil dihapus.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'ERROR', 'message' => 'Gagal hapus gejala: ' . $e->getMessage()]);
        }
        exit();
    }
}

// -------------------------------------------------------------
// 2. MANAJEMEN KERUSAKAN & SOLUSI
// -------------------------------------------------------------
if ($target === 'kerusakan') {
    if ($method === 'GET') {
        try {
            $stmt = $pdo->query("SELECT * FROM kerusakan ORDER BY kode_kerusakan ASC");
            echo json_encode(['status' => 'SUCCESS', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage()]);
        }
        exit();
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id_kerusakan = intval($input['id_kerusakan'] ?? 0);
        $kode_kerusakan = strtoupper(trim($input['kode_kerusakan'] ?? ''));
        $nama_kerusakan = trim($input['nama_kerusakan'] ?? '');
        $solusi = trim($input['solusi'] ?? '');
        $butuh_teknisi = intval($input['butuh_teknisi'] ?? 0);

        if (empty($kode_kerusakan) || empty($nama_kerusakan) || empty($solusi)) {
            echo json_encode(['status' => 'ERROR', 'message' => 'Kode, nama kerusakan, dan solusi wajib diisi.']);
            exit();
        }

        try {
            if ($id_kerusakan > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE kerusakan SET kode_kerusakan = :kode, nama_kerusakan = :nama, solusi = :solusi, butuh_teknisi = :teknisi WHERE id_kerusakan = :id");
                $stmt->execute([':kode' => $kode_kerusakan, ':nama' => $nama_kerusakan, ':solusi' => $solusi, ':teknisi' => $butuh_teknisi, ':id' => $id_kerusakan]);
                $msg = 'Data kerusakan berhasil diperbarui.';
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO kerusakan (kode_kerusakan, nama_kerusakan, solusi, butuh_teknisi) VALUES (:kode, :nama, :solusi, :teknisi)");
                $stmt->execute([':kode' => $kode_kerusakan, ':nama' => $nama_kerusakan, ':solusi' => $solusi, ':teknisi' => $butuh_teknisi]);
                $msg = 'Kerusakan baru berhasil ditambahkan.';
            }
            echo json_encode(['status' => 'SUCCESS', 'message' => $msg]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'ERROR', 'message' => 'Gagal simpan kerusakan: ' . $e->getMessage()]);
        }
        exit();
    }

    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id_kerusakan = intval($input['id_kerusakan'] ?? 0);

        try {
            $stmt = $pdo->prepare("DELETE FROM kerusakan WHERE id_kerusakan = :id");
            $stmt->execute([':id' => $id_kerusakan]);
            echo json_encode(['status' => 'SUCCESS', 'message' => 'Data kerusakan berhasil dihapus.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'ERROR', 'message' => 'Gagal hapus kerusakan: ' . $e->getMessage()]);
        }
        exit();
    }
}

// -------------------------------------------------------------
// 3. MANAJEMEN ATURAN (FORWARD CHAINING RULES)
// -------------------------------------------------------------
if ($target === 'aturan') {
    if ($method === 'GET') {
        try {
            $sql = "SELECT a.id_aturan, a.kode_rule, a.id_gejala_1, a.id_gejala_2, a.id_kerusakan,
                           g1.nama_gejala AS nama_gejala_1,
                           g2.nama_gejala AS nama_gejala_2,
                           k.nama_kerusakan, k.solusi, k.butuh_teknisi
                    FROM aturan a
                    LEFT JOIN gejala g1 ON a.id_gejala_1 = g1.kode_gejala
                    LEFT JOIN gejala g2 ON a.id_gejala_2 = g2.kode_gejala
                    LEFT JOIN kerusakan k ON a.id_kerusakan = k.kode_kerusakan
                    ORDER BY a.kode_rule ASC";
            $stmt = $pdo->query($sql);
            echo json_encode(['status' => 'SUCCESS', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage()]);
        }
        exit();
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id_aturan = intval($input['id_aturan'] ?? 0);
        $kode_rule = strtoupper(trim($input['kode_rule'] ?? ''));
        $id_gejala_1 = strtoupper(trim($input['id_gejala_1'] ?? ''));
        $id_gejala_2 = strtoupper(trim($input['id_gejala_2'] ?? ''));
        $id_kerusakan = strtoupper(trim($input['id_kerusakan'] ?? ''));

        if (empty($kode_rule) || empty($id_gejala_1) || empty($id_gejala_2) || empty($id_kerusakan)) {
            echo json_encode(['status' => 'ERROR', 'message' => 'Kode rule, Gejala 1, Gejala 2, dan Kerusakan wajib diisi.']);
            exit();
        }

        try {
            if ($id_aturan > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE aturan SET kode_rule = :kode, id_gejala_1 = :g1, id_gejala_2 = :g2, id_kerusakan = :k WHERE id_aturan = :id");
                $stmt->execute([':kode' => $kode_rule, ':g1' => $id_gejala_1, ':g2' => $id_gejala_2, ':k' => $id_kerusakan, ':id' => $id_aturan]);
                $msg = 'Aturan rule berhasil diperbarui.';
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO aturan (kode_rule, id_gejala_1, id_gejala_2, id_kerusakan) VALUES (:kode, :g1, :g2, :k)");
                $stmt->execute([':kode' => $kode_rule, ':g1' => $id_gejala_1, ':g2' => $id_gejala_2, ':k' => $id_kerusakan]);
                $msg = 'Aturan rule baru berhasil ditambahkan.';
            }
            echo json_encode(['status' => 'SUCCESS', 'message' => $msg]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'ERROR', 'message' => 'Gagal simpan aturan rule: ' . $e->getMessage()]);
        }
        exit();
    }

    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id_aturan = intval($input['id_aturan'] ?? 0);

        try {
            $stmt = $pdo->prepare("DELETE FROM aturan WHERE id_aturan = :id");
            $stmt->execute([':id' => $id_aturan]);
            echo json_encode(['status' => 'SUCCESS', 'message' => 'Aturan rule berhasil dihapus.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'ERROR', 'message' => 'Gagal hapus aturan rule: ' . $e->getMessage()]);
        }
        exit();
    }
}

echo json_encode(['status' => 'ERROR', 'message' => 'Target endpoint tidak valid.']);
