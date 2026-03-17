<?php
// add_customer_note.php
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['customer_id']) || !isset($input['note'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$customer_id = (int)$input['customer_id'];
$note = sanitizeInput($input['note']);

if (empty($note)) {
    echo json_encode(['success' => false, 'message' => 'Catatan tidak boleh kosong']);
    exit;
}

try {
    // Create customer_notes table if not exists
    $conn->query("
        CREATE TABLE IF NOT EXISTS customer_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            note TEXT NOT NULL,
            created_by VARCHAR(100) DEFAULT 'system',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES pelanggan(id) ON DELETE CASCADE
        )
    ");
    
    $stmt = $conn->prepare("
        INSERT INTO customer_notes (customer_id, note, created_by) 
        VALUES (?, ?, ?)
    ");
    
    $created_by = 'admin'; // You can get this from session
    $stmt->bind_param("iss", $customer_id, $note, $created_by);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Catatan berhasil ditambahkan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan catatan']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>