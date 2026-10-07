<?php
/**
 * Master Pixel Functions
 * PixelCRM
 */

require_once __DIR__ . '/../../includes/init.php';

/**
 * Mengambil semua pixel dengan jumlah produk yang terhubung
 */
function getAllPixels() {
    return fetchAll("
        SELECT px.*, COUNT(p.id) as total_produk
        FROM pixels px
        LEFT JOIN produk p ON px.id = p.pixel_id
        GROUP BY px.id
        ORDER BY px.created_at DESC
    ");
}

/**
 * Mengambil pixel yang aktif saja untuk dropdown pilihan produk
 */
function getActivePixels() {
    return fetchAll("SELECT id, nama, meta_pixel_id FROM pixels WHERE is_active = 1 ORDER BY nama ASC");
}

/**
 * Mengambil data single pixel berdasarkan ID
 */
function getPixelById($id) {
    return fetchRow("SELECT * FROM pixels WHERE id = ?", [(int)$id]);
}

/**
 * Menyimpan data pixel baru
 */
function createPixel($data) {
    $nama = trim($data['nama'] ?? '');
    $meta_pixel_id = trim($data['meta_pixel_id'] ?? '');
    $conversion_api_token = trim($data['conversion_api_token'] ?? '');
    $test_event_code = trim($data['test_event_code'] ?? '');
    $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
    
    $sql = "INSERT INTO pixels (nama, meta_pixel_id, conversion_api_token, test_event_code, is_active, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
            
    $stmt = execute($sql, [
        clean($nama),
        clean($meta_pixel_id),
        $conversion_api_token, // Token bisa panjang, simpan as is
        clean($test_event_code),
        $is_active
    ]);
    
    return $stmt ? db()->insert_id : false;
}

/**
 * Update data pixel
 */
function updatePixel($id, $data) {
    $nama = trim($data['nama'] ?? '');
    $meta_pixel_id = trim($data['meta_pixel_id'] ?? '');
    $conversion_api_token = trim($data['conversion_api_token'] ?? '');
    $test_event_code = trim($data['test_event_code'] ?? '');
    $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
    
    $sql = "UPDATE pixels SET nama = ?, meta_pixel_id = ?, conversion_api_token = ?, test_event_code = ?, is_active = ?, updated_at = NOW() WHERE id = ?";
    
    $success = execute($sql, [
        clean($nama),
        clean($meta_pixel_id),
        $conversion_api_token,
        clean($test_event_code),
        $is_active,
        (int)$id
    ]);
    
    // Sinkronisasi data di tabel produk yang terhubung agar data legacy tetap up-to-date
    if ($success) {
        execute("UPDATE produk SET meta_pixel_id = ?, conversion_api_token = ? WHERE pixel_id = ?", [
            clean($meta_pixel_id),
            $conversion_api_token,
            (int)$id
        ]);
    }
    
    return $success;
}

/**
 * Hapus pixel
 */
function deletePixel($id) {
    // Lepaskan keterikatan produk sebelum menghapus pixel
    execute("UPDATE produk SET pixel_id = NULL WHERE pixel_id = ?", [(int)$id]);
    return execute("DELETE FROM pixels WHERE id = ?", [(int)$id]);
}

/**
 * Ambil daftar produk yang menggunakan pixel ini
 */
function getProductsUsingPixel($pixel_id) {
    return fetchAll("SELECT id, nama, harga FROM produk WHERE pixel_id = ? ORDER BY nama ASC", [(int)$pixel_id]);
}
