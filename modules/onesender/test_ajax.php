<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/whatsapp_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Metode request tidak valid.']);
    exit;
}

$account_name = trim($_POST['account'] ?? '');

if (empty($account_name)) {
    echo json_encode(['success' => false, 'error' => 'Nama akun tidak ditemukan.']);
    exit;
}

// Ambil konfigurasi akun dari database
$config = fetchOne("SELECT * FROM onesender_config WHERE account_name = ?", [$account_name]);

if (!$config || empty($config['api_key']) || empty($config['api_url'])) {
    echo json_encode(['success' => false, 'error' => 'Konfigurasi device atau API Key belum lengkap.']);
    exit;
}

// Uji koneksi ke endpoint OneSender
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => rtrim($config['api_url'], '/') . '/api/status', // Sesuaikan endpoint cek status OneSender
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $config['api_key'],
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo json_encode(['success' => false, 'error' => 'Koneksi gagal: ' . $curl_error]);
    exit;
}

// Anggap sukses jika respon HTTP 200/201
if ($http_code >= 200 && $http_code < 300) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Endpoint merespons HTTP ' . $http_code]);
}