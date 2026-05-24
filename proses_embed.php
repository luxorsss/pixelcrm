<?php
// proses_embed.php
ini_set('session.cookie_domain', '.edumuslim.my.id');
session_set_cookie_params(0, '/', '.edumuslim.my.id');
session_start();

require_once 'includes/init.php';
require_once 'modules/followup/functions.php';

if (isPost()) {
    $nama = trim(post('nama'));
    $nomor_wa = trim(post('nomor_wa'));
    $produk_id = (int) post('produk_id'); // Ditangkap dari hidden input HTML
    
    // Validasi Dasar
    if (empty($nama) || empty($nomor_wa)) {
        die("Nama dan nomor WhatsApp wajib diisi. Silakan kembali.");
    }

    // Rapikan Nomor WA
    if (substr($nomor_wa, 0, 1) === '0') {
        $nomor_wa = '62' . substr($nomor_wa, 1);
    }

    // Ambil Data Produk
    $produk = fetchRow("SELECT * FROM produk WHERE id = ?", [$produk_id]);
    if (!$produk) die("Produk tidak ditemukan.");

    // 1. Cek & Insert Pelanggan
    $existing_customer = fetchRow("SELECT * FROM pelanggan WHERE nomor_wa = ?", [$nomor_wa]);
    if ($existing_customer) {
        execute("UPDATE pelanggan SET nama = ? WHERE id = ?", [$nama, $existing_customer['id']]);
        $customer_id = $existing_customer['id'];
    } else {
        execute("INSERT INTO pelanggan (nama, nomor_wa) VALUES (?, ?)", [$nama, $nomor_wa]);
        $customer_id = db()->insert_id;
    }

    // 2. Insert Transaksi (Generate UUID)
    $uuid = 'INV-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    $total_harga = $produk['harga'];
    
    // Ambil fbclid jika ada di cookie
    $fbc = $_COOKIE['_fbc'] ?? null;
    $fbp = $_COOKIE['_fbp'] ?? null;

    execute("INSERT INTO transaksi (uuid, pelanggan_id, total_harga, status, ip_pelanggan, user_agent_pelanggan, fbc, fbp) 
             VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)", 
            [$uuid, $customer_id, $total_harga, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $fbc, $fbp]);
    $transaksi_id = db()->insert_id;

    // 3. Insert Detail Transaksi
    $modal_utama = $produk['harga'] - $produk['profit'];
    execute("INSERT INTO detail_transaksi (transaksi_id, produk_id, harga, profit) VALUES (?, ?, ?, ?)", 
            [$transaksi_id, $produk_id, $produk['harga'], $produk['profit']]);

    // 4. Trigger Webhook (Jika ada http_post di tabel produk)
    if (!empty($produk['http_post'])) {
        $post_data = ['name' => $nama, 'phone' => $nomor_wa];
        $ch = curl_init($produk['http_post']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    }

    // 5. Trigger Follow Up Antrean
    try {
        generateFollowupForNewTransactionTrueSequential($transaksi_id);
    } catch (Exception $e) {}

    // (Opsional) Sisipkan fungsi sendMetaCAPIEvent() Anda di sini jika ingin CAPI jalan

    // 6. Redirect ke Halaman Invoice
    redirect("https://edumuslim.my.id/invoice.php?uuid=$uuid");
    exit;
} else {
    redirect("https://edumuslim.my.id"); // Tendang jika bukan POST
}