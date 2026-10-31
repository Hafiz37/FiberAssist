<?php
/**
 * Konfigurasi Koneksi Database MariaDB / MySQL (PDO)
 * Sistem Pakar Diagnosa Gangguan Internet via WhatsApp
 */

$host = 'localhost';
$dbname = 'db_sistem_pakar';
$username = 'root';
$password = 'Sembarang_37';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ERROR',
        'message' => 'Koneksi database gagal: ' . $e->getMessage()
    ]);
    exit();
}
