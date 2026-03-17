<?php
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid.']);
    exit;
}

$kode_kupon = strtoupper(clean(post('kode_kupon')));
$produk_id = post('produk_id') ? post('produk_id') : null;
$total_harga = post('total_harga') ? (int)post('total_harga') : 0;

if (empty($kode_kupon)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode kupon tidak boleh kosong.']);
    exit;
}

// Cari kupon di database
$query = "SELECT * FROM kupon WHERE kode_kupon = '$kode_kupon' LIMIT 1";
$kupons = fetchAll($query);

if (empty($kupons)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode kupon tidak ditemukan.']);
    exit;
}

$kupon = $kupons[0];
$waktu_sekarang = date('Y-m-d H:i:s');

// 1. Cek Status Aktif
if ($kupon['is_active'] == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Kupon ini sedang tidak aktif.']);
    exit;
}

// 2. Cek Masa Berlaku
if ($waktu_sekarang < $kupon['tgl_mulai'] || $waktu_sekarang > $kupon['tgl_selesai']) {
    echo json_encode(['status' => 'error', 'message' => 'Kupon sudah kedaluwarsa atau belum aktif.']);
    exit;
}

// 3. Cek Kuota (Jika kuota 0, berarti tanpa batas)
if ($kupon['kuota'] > 0 && $kupon['terpakai'] >= $kupon['kuota']) {
    echo json_encode(['status' => 'error', 'message' => 'Maaf, kuota kupon ini sudah habis.']);
    exit;
}

// 4. Cek Spesifik Produk (Jika kupon khusus produk tertentu)
if (!empty($kupon['produk_id']) && $kupon['produk_id'] != $produk_id) {
    echo json_encode(['status' => 'error', 'message' => 'Kupon ini tidak berlaku untuk produk ini.']);
    exit;
}

// Hitung Diskon
$nilai_potongan = 0;
if ($kupon['tipe_diskon'] == 'nominal') {
    $nilai_potongan = $kupon['nilai_diskon'];
} else if ($kupon['tipe_diskon'] == 'persentase') {
    $nilai_potongan = ($total_harga * $kupon['nilai_diskon']) / 100;
    // Cek maksimal potongan
    if (!empty($kupon['max_potongan']) && $kupon['max_potongan'] > 0) {
        if ($nilai_potongan > $kupon['max_potongan']) {
            $nilai_potongan = $kupon['max_potongan'];
        }
    }
}

// Pastikan diskon tidak lebih besar dari total harga
if ($nilai_potongan > $total_harga) {
    $nilai_potongan = $total_harga;
}

$harga_akhir = $total_harga - $nilai_potongan;

// Berikan respon sukses
echo json_encode([
    'status' => 'success',
    'message' => 'Kupon berhasil diterapkan!',
    'kupon_id' => $kupon['id'],
    'potongan' => $nilai_potongan,
    'potongan_format' => 'Rp ' . number_format($nilai_potongan, 0, ',', '.'),
    'harga_akhir' => $harga_akhir,
    'harga_akhir_format' => 'Rp ' . number_format($harga_akhir, 0, ',', '.')
]);
exit;