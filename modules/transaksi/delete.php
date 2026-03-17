<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$id = (int)get('id');
if (!$id) {
    setMessage('ID transaksi tidak valid', 'error');
    redirect('index.php');
}

// Cek apakah transaksi ada
$transaksi = getTransaksiById($id);
if (!$transaksi) {
    setMessage('Transaksi tidak ditemukan', 'error');
    redirect('index.php');
}

// Hapus transaksi
if (deleteTransaksi($id)) {
    setMessage("Transaksi #$id berhasil dihapus", 'success');
} else {
    setMessage('Gagal menghapus transaksi', 'error');
}

redirect('index.php');
?>