<?php
// Simple delete handler - redirect to appropriate edit page
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$id = (int)get('id');
if (!$id) {
    setMessage('ID bundling tidak valid', 'error');
    redirect('index.php');
}

$bundling = getBundlingById($id);
if (!$bundling) {
    setMessage('Bundling tidak ditemukan', 'error');
    redirect('index.php');
}

// Handle delete confirmation
if (isPost() && post('confirm') === 'yes') {
    if (deleteBundling($id)) {
        setMessage('Bundling berhasil dihapus', 'success');
        redirect('index.php');
    } else {
        setMessage('Gagal menghapus bundling', 'error');
        redirect('index.php');
    }
}

// Redirect to edit page instead of showing delete form
// This simplifies the workflow
redirect('edit.php?produk_id=' . $bundling['produk_id']);
?>