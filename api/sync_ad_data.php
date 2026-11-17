<?php
/**
 * PixelCRM - Secure Ad Data Sync Endpoint
 * Menerima payload data belanja iklan & pajak dari AI Agent via HTTPS (Port 443).
 * Langsung menyimpan ke database MySQL tabel `iklan_harian`.
 */

// Hindari notice CLI
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

// Secret token otentikasi
define('SYNC_SECRET_KEY', 'pix_sec_99a8b7c6d5e4f321_edumuslim');

// Validasi Token Bearer
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
    $token = $matches[1];
} elseif (isset($_REQUEST['key'])) {
    $token = $_REQUEST['key'];
}

if ($token !== SYNC_SECRET_KEY) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Token tidak valid']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed: Gunakan POST']);
    exit;
}

// Baca Payload JSON
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
    exit;
}

try {
    $conn = db();
    if (!$conn || $conn->connect_error) {
        throw new Exception("Gagal terhubung ke database MySQL: " . ($conn ? $conn->connect_error : 'Unknown'));
    }

    // Pastikan tabel iklan_harian ada
    $conn->query("CREATE TABLE IF NOT EXISTS `iklan_harian` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `tanggal` DATE UNIQUE NOT NULL,
        `spend_raw` DECIMAL(12,2) NOT NULL DEFAULT 0,
        `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
        `tax_notes` VARCHAR(255) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Persiapkan statement UPSERT
    $stmt = $conn->prepare("INSERT INTO `iklan_harian` (`tanggal`, `spend_raw`, `tax_amount`, `tax_notes`) 
                            VALUES (?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE 
                                `spend_raw` = VALUES(`spend_raw`), 
                                `tax_amount` = VALUES(`tax_amount`), 
                                `tax_notes` = VALUES(`tax_notes`)");

    if (!$stmt) {
        throw new Exception("Gagal prepare statement: " . $conn->error);
    }

    $insertedCount = 0;
    
    // Cek apakah payload berupa batch array atau single record
    $records = isset($data['batch']) && is_array($data['batch']) ? $data['batch'] : [$data];

    foreach ($records as $item) {
        $date = trim($item['date'] ?? $item['tanggal'] ?? '');
        $spend = (float)($item['spend_raw'] ?? $item['spend'] ?? 0);
        $tax = (float)($item['tax_amount'] ?? $item['pajak'] ?? 0);
        $notes = trim($item['tax_notes'] ?? $item['catatan'] ?? '');

        if ($date) {
            $stmt->bind_param("sdds", $date, $spend, $tax, $notes);
            $stmt->execute();
            $insertedCount++;
        }
    }

    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'message' => "Berhasil menyimpan {$insertedCount} data iklan ke database cPanel.",
        'processed_records' => $insertedCount,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
