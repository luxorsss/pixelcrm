<?php
// === LOGIC SECTION (No Output) ===
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Validate ID
$produk_id = (int)get('id');
if (!$produk_id) {
    setMessage('ID produk tidak valid!', 'error');
    redirect('index.php');
}

// Get produk data
$produk = getProdukById($produk_id);
if (!$produk) {
    setMessage('Produk tidak ditemukan!', 'error');
    redirect('index.php');
}

// Delete produk
if (deleteProduk($produk_id)) {
    setMessage('Produk "' . $produk['nama'] . '" berhasil dihapus!', 'success');
} else {
    setMessage('Produk tidak dapat dihapus karena sudah ada transaksi yang menggunakan produk ini!', 'error');
}

redirect('index.php');
?>