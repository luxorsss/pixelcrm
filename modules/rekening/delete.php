<?php
require_once '../../includes/init.php';
require_once 'functions.php';

$id = (int)get('id');
$rekening = getRekeningById($id);

if (!$rekening) {
    setMessage('Rekening tidak ditemukan', 'error');
    redirect('index.php');
}

// Check if rekening is being used (future-proof untuk relasi)
// Saat ini langsung delete karena belum ada relasi

if (deleteRekening($id)) {
    setMessage('Rekening berhasil dihapus', 'success');
} else {
    setMessage('Gagal menghapus rekening', 'error');
}

redirect('index.php');
?>