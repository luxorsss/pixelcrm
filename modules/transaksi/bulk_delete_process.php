<?php
/**
 * Bulk Delete Process Handler
 * Proses hapus transaksi pending lama via AJAX (opsional)
 */

require_once __DIR__ . '/../../includes/init.php';

// Pastikan request via POST dan user sudah login
if (!isPost() || !isLoggedIn()) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Akses ditolak']));
}

// Set content type untuk JSON response
header('Content-Type: application/json');

$confirm = post('confirm');

if ($confirm !== 'yes') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Konfirmasi diperlukan']);
    exit;
}

try {
    // Start transaction
    mysqli_begin_transaction(db());
    
    // 1. Get count transaksi yang akan dihapus untuk log
    $count_sql = "SELECT COUNT(*) as total FROM transaksi 
                  WHERE status = 'pending' 
                  AND tanggal_transaksi < DATE_SUB(NOW(), INTERVAL 3 MONTH)";
    $count_result = mysqli_query(db(), $count_sql);
    $count_data = mysqli_fetch_assoc($count_result);
    $expected_count = $count_data['total'];
    
    if ($expected_count == 0) {
        mysqli_rollback(db());
        echo json_encode([
            'success' => true, 
            'message' => 'Tidak ada transaksi pending yang lebih dari 3 bulan',
            'deleted_count' => 0
        ]);
        exit;
    }
    
    // 2. Hapus detail transaksi terlebih dulu
    $delete_details_sql = "DELETE dt FROM detail_transaksi dt
                          JOIN transaksi t ON dt.transaksi_id = t.id
                          WHERE t.status = 'pending' 
                          AND t.tanggal_transaksi < DATE_SUB(NOW(), INTERVAL 3 MONTH)";
    
    if (!mysqli_query(db(), $delete_details_sql)) {
        throw new Exception("Gagal menghapus detail transaksi: " . mysqli_error(db()));
    }
    
    $details_deleted = mysqli_affected_rows(db());
    
    // 3. Hapus transaksi utama
    $delete_transaksi_sql = "DELETE FROM transaksi 
                            WHERE status = 'pending' 
                            AND tanggal_transaksi < DATE_SUB(NOW(), INTERVAL 3 MONTH)";
    
    if (!mysqli_query(db(), $delete_transaksi_sql)) {
        throw new Exception("Gagal menghapus transaksi: " . mysqli_error(db()));
    }
    
    $transaksi_deleted = mysqli_affected_rows(db());
    
    // Commit transaction
    mysqli_commit(db());
    
    // Response sukses
    echo json_encode([
        'success' => true,
        'message' => "Berhasil menghapus {$transaksi_deleted} transaksi dan {$details_deleted} detail transaksi",
        'deleted_count' => $transaksi_deleted,
        'details_deleted' => $details_deleted
    ]);
    
} catch (Exception $e) {
    // Rollback pada error
    mysqli_rollback(db());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>