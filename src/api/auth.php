<?php
/**
 * API Otentikasi User (Login, Logout, Check Session)
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Pastikan tabel users ter-setup secara otomatis
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id_user INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        nama_lengkap VARCHAR(100) NOT NULL,
        role ENUM('ADMIN', 'CS') NOT NULL DEFAULT 'CS',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Seed admin & cs1 jika masih kosong
    $stmtCheck = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmtCheck->fetchColumn() == 0) {
        $hashAdmin = password_hash('admin123', PASSWORD_BCRYPT);
        $hashCS = password_hash('cs123', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES ('admin', ?, 'System Administrator', 'ADMIN')")->execute([$hashAdmin]);
        $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES ('cs1', ?, 'Customer Service 1', 'CS')")->execute([$hashCS]);
    }
} catch (Exception $e) {
    // Ignore schema check error if connection issues handled by database.php
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'check') {
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'status' => 'SUCCESS',
                'authenticated' => true,
                'user' => [
                    'id_user' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'nama_lengkap' => $_SESSION['nama_lengkap'],
                    'role' => $_SESSION['role']
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'SUCCESS',
                'authenticated' => false
            ]);
        }
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputData = json_decode(file_get_contents('php://input'), true);

    if ($action === 'login') {
        $username = trim($inputData['username'] ?? '');
        $password = trim($inputData['password'] ?? '');

        if (empty($username) || empty($password)) {
            echo json_encode([
                'status' => 'ERROR',
                'message' => 'Username dan password wajib diisi.'
            ]);
            exit();
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            echo json_encode([
                'status' => 'SUCCESS',
                'message' => 'Login berhasil!',
                'user' => [
                    'id_user' => $user['id_user'],
                    'username' => $user['username'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'ERROR',
                'message' => 'Username atau password salah!'
            ]);
        }
        exit();
    }

    if ($action === 'logout') {
        session_unset();
        session_destroy();
        echo json_encode([
            'status' => 'SUCCESS',
            'message' => 'Berhasil logout.'
        ]);
        exit();
    }
}

echo json_encode([
    'status' => 'ERROR',
    'message' => 'Endpoint tidak valid.'
]);
