<?php
require_once __DIR__ . '/../../includes/init.php';

// Pastikan user sudah login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'login.php');
}

// Ambil ID dari URL
$id = (int) get('id');

if ($id) {
    // Jalankan query hapus data
    $delete = execute("DELETE FROM kupon WHERE id = ?", [$id]);
    
    if ($delete) {
        setMessage("Data kupon berhasil dihapus secara permanen.", "success");
    } else {
        setMessage("Terjadi kesalahan, gagal menghapus kupon.", "danger");
    }
}

// Langsung lempar kembali ke halaman index
redirect('index.php');