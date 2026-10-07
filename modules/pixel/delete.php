<?php
require_once '../../includes/init.php';
require_once 'functions.php';

$id = (int) get('id');
if ($id) {
    if (deletePixel($id)) {
        setMessage('Pixel berhasil dihapus', 'success');
    } else {
        setMessage('Gagal menghapus pixel', 'error');
    }
}

redirect('index.php');
