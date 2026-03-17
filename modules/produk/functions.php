<?php
/**
 * Simple Produk Functions
 * Fast & lightweight functions untuk CRUD produk
 */

// Get all produk with optional pagination
function getAllProduk($page = null, $limit = null) {
    if ($page !== null && $limit !== null) {
        // With pagination
        $offset = ($page - 1) * $limit;
        return fetchAll("SELECT * FROM produk ORDER BY id DESC LIMIT $limit OFFSET $offset");
    } else {
        // Without pagination - get all products
        return fetchAll("SELECT * FROM produk ORDER BY id DESC");
    }
}

// Get total produk count
function getTotalProduk() {
    $result = fetchRow("SELECT COUNT(*) as total FROM produk");
    return $result['total'] ?? 0;
}

// Get produk by ID
function getProdukById($id) {
    return fetchRow("SELECT * FROM produk WHERE id = ?", [$id]);
}

// Create new produk
function createProduk($data) {
    // PERUBAHAN: profit_persen diganti jadi profit
    $sql = "INSERT INTO produk (nama, deskripsi, harga, link_akses, onesender_account, admin_wa, meta_pixel_id, conversion_api_token, tracking_aktif, http_post, profit) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $data['nama'],
        $data['deskripsi'],
        $data['harga'],
        $data['link_akses'],
        $data['onesender_account'],
        $data['admin_wa'],
        $data['meta_pixel_id'] ?? '',
        $data['conversion_api_token'] ?? '',
        $data['tracking_aktif'] ?? 0,
        $data['http_post'] ?? '',
        // PERUBAHAN: Ambil data 'profit' nominal
        $data['profit'] ?? 0 
    ];
    
    return execute($sql, $params);
}

// Update produk
function updateProduk($id, $data) {
    // PERUBAHAN: profit_persen diganti jadi profit
    $sql = "UPDATE produk SET 
            nama = ?, deskripsi = ?, harga = ?, link_akses = ?, 
            onesender_account = ?, admin_wa = ?, meta_pixel_id = ?, 
            conversion_api_token = ?, tracking_aktif = ?, http_post = ?,
            profit = ?
            WHERE id = ?";
    
    $params = [
        $data['nama'],
        $data['deskripsi'],
        $data['harga'],
        $data['link_akses'],
        $data['onesender_account'],
        $data['admin_wa'],
        $data['meta_pixel_id'] ?? '',
        $data['conversion_api_token'] ?? '',
        $data['tracking_aktif'] ?? 0,
        $data['http_post'] ?? '',
        // PERUBAHAN: Ambil data 'profit' nominal
        $data['profit'] ?? 0,
        $id
    ];
    
    return execute($sql, $params);
}

// Validate produk data
function validateProdukData($data) {
    $errors = [];
    
    if (empty(trim($data['nama']))) {
        $errors[] = 'Nama produk harus diisi';
    }
    
    // 1. Cek apakah kosong (string kosong)
    if (!isset($data['harga']) || $data['harga'] === '') {
        $errors[] = 'Harga produk wajib diisi';
    } 
    // 2. Cek apakah angka valid (Gunakan < 0 agar angka 0 tetap lolos)
    elseif (!is_numeric($data['harga']) || $data['harga'] < 0) {
        $errors[] = 'Harga produk harus diisi dengan angka yang valid (0 boleh)';
    }
    
    // PERUBAHAN: Validasi Profit Nominal
    // Cek apakah profit valid (angka dan tidak negatif)
    if (isset($data['profit'])) {
        if (!is_numeric($data['profit']) || $data['profit'] < 0) {
            $errors[] = 'Nominal profit tidak boleh negatif';
        }
        
        // Opsional: Cek apakah profit lebih besar dari harga jual (biasanya tidak wajar, tapi possible)
        // if ($data['profit'] > $data['harga']) {
        //     $errors[] = 'Profit tidak boleh lebih besar dari harga jual';
        // }
    }
    
    // Validate WhatsApp number if provided
    if (!empty($data['admin_wa']) && !validatePhone($data['admin_wa'])) {
        $errors[] = 'Format nomor WhatsApp tidak valid';
    }
    
    // Validate URL if provided
    if (!empty($data['link_akses']) && !filter_var($data['link_akses'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Format link akses tidak valid';
    }
    
    // Validate HTTP POST URL if provided
    if (!empty($data['http_post']) && !filter_var($data['http_post'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Format HTTP POST URL tidak valid';
    }
    
    return $errors;
}

// Delete produk
function deleteProduk($id) {
    // Check if produk has transactions
    $count = fetchRow("SELECT COUNT(*) as total FROM detail_transaksi WHERE produk_id = ?", [$id]);
    
    if ($count['total'] > 0) {
        return false; // Cannot delete, has transactions
    }
    
    return execute("DELETE FROM produk WHERE id = ?", [$id]);
}

// Get OneSender accounts
function getOneSenderAccounts() {
    $accounts = fetchAll("SELECT account_name FROM onesender_config ORDER BY account_name");
    
    // Add default account
    array_unshift($accounts, ['account_name' => 'default']);
    
    return $accounts;
}
?>