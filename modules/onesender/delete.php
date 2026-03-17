<?php
require_once __DIR__ . '/../../includes/init.php';

// Get account ID
$id = (int)get('id');
if (!$id) {
    setMessage('ID account tidak valid', 'error');
    redirect('index.php');
}

// Get account data
$account = fetchRow("SELECT * FROM onesender_config WHERE id = ?", [$id]);
if (!$account) {
    setMessage('Account tidak ditemukan', 'error');
    redirect('index.php');
}

// Prevent deletion of default account
if ($account['account_name'] === 'default') {
    setMessage('Account default tidak dapat dihapus', 'error');
    redirect('index.php');
}

try {
    // Check if account is being used by any products
    $used_by_products = fetchAll("SELECT id, nama FROM produk WHERE onesender_account = ?", [$account['account_name']]);
    
    if (!empty($used_by_products)) {
        $product_names = array_column($used_by_products, 'nama');
        setMessage('Account "' . $account['account_name'] . '" tidak dapat dihapus karena masih digunakan oleh produk: ' . implode(', ', $product_names), 'error');
        redirect('index.php');
    }
    
    // Delete the account
    if (execute("DELETE FROM onesender_config WHERE id = ?", [$id])) {
        setMessage('Account OneSender "' . $account['account_name'] . '" berhasil dihapus', 'success');
    } else {
        setMessage('Gagal menghapus account', 'error');
    }
} catch (Exception $e) {
    setMessage('Error: ' . $e->getMessage(), 'error');
}

redirect('index.php');
?>