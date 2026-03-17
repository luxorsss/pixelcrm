<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Validasi ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setMessage('ID pelanggan tidak valid!', 'error');
    redirect('index.php');
}

// Ambil data pelanggan
$pelanggan = getPelangganById($id);
if (!$pelanggan) {
    setMessage('Pelanggan tidak ditemukan!', 'error');
    redirect('index.php');
}

try {
    // Ambil statistik sebelum dihapus
    $stats = getStatistikPelanggan($id);
    
    // Hapus pelanggan dan semua transaksi terkait
    $result = deletePelangganForce($id);
    
    if ($result) {
        $nama_pelanggan = clean($pelanggan['nama']);
        
        if ($stats['total_transaksi'] > 0) {
            $message = "Pelanggan \"{$nama_pelanggan}\" dan {$stats['total_transaksi']} transaksi berhasil dihapus!";
        } else {
            $message = "Pelanggan \"{$nama_pelanggan}\" berhasil dihapus!";
        }
        
        setMessage($message, 'success');
    } else {
        setMessage('Gagal menghapus pelanggan!', 'error');
    }
} catch (Exception $e) {
    error_log("Error deleting pelanggan ID $id: " . $e->getMessage());
    setMessage('System error: Gagal menghapus pelanggan', 'error');
}

redirect('index.php');
?>