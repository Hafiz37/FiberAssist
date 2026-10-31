<?php
/**
 * API Manajemen User (Khusus Administrator)
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Proteksi Akses: Harus Login dan Berperan ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Akses ditolak. Fitur ini hanya untuk Administrator.'
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id_user, username, nama_lengkap, role, created_at FROM users ORDER BY id_user DESC");
        $users = $stmt->fetchAll();

        echo json_encode([
            'status' => 'SUCCESS',
            'data' => $users
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Gagal mengambil data user: ' . $e->getMessage()
        ]);
    }
    exit();
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');
    $nama_lengkap = trim($input['nama_lengkap'] ?? '');
    $role = strtoupper(trim($input['role'] ?? 'CS'));

    if (empty($username) || empty($password) || empty($nama_lengkap)) {
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Username, password, dan nama lengkap wajib diisi.'
        ]);
        exit();
    }

    if (!in_array($role, ['ADMIN', 'CS'])) {
        $role = 'CS';
    }

    try {
        // Cek duplikasi username
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmtCheck->execute([':username' => $username]);
        if ($stmtCheck->fetchColumn() > 0) {
            echo json_encode([
                'status' => 'ERROR',
                'message' => 'Username "' . htmlspecialchars($username) . '" sudah digunakan.'
            ]);
            exit();
        }

        $hashPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmtIns = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (:username, :password, :nama_lengkap, :role)");
        $stmtIns->execute([
            ':username' => $username,
            ':password' => $hashPassword,
            ':nama_lengkap' => $nama_lengkap,
            ':role' => $role
        ]);

        echo json_encode([
            'status' => 'SUCCESS',
            'message' => 'User baru berhasil ditambahkan!'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Gagal menambah user: ' . $e->getMessage()
        ]);
    }
    exit();
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id_user = intval($input['id_user'] ?? 0);

    if ($id_user <= 0) {
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'ID User tidak valid.'
        ]);
        exit();
    }

    if ($id_user === intval($_SESSION['user_id'])) {
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Anda tidak bisa menghapus akun Anda sendiri saat sedang aktif login.'
        ]);
        exit();
    }

    try {
        $stmtDel = $pdo->prepare("DELETE FROM users WHERE id_user = :id");
        $stmtDel->execute([':id' => $id_user]);

        echo json_encode([
            'status' => 'SUCCESS',
            'message' => 'User berhasil dihapus.'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Gagal menghapus user: ' . $e->getMessage()
        ]);
    }
    exit();
}

echo json_encode([
    'status' => 'ERROR',
    'message' => 'Method tidak didukung.'
]);
