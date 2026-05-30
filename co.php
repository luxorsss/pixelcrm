<?php
// Mengambil nama domain yang sedang diakses secara otomatis
$current_host = $_SERVER['HTTP_HOST'];
$cookie_domain = '.' . preg_replace('/^www\./', '', $current_host);

/**
 * Checkout Page - Fixed fbclid Persistence (Cookie + Session)
 */
require_once 'includes/init.php';
require_once 'modules/followup/functions.php';

// Enable browser caching
header("Cache-Control: public, max-age=3600");
header("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));

// === SIMPAN fbclid ke COOKIE (_fbc) & SESSION ===
if (isset($_GET['fbclid']) && !empty($_GET['fbclid'])) {
    $fbclid = trim($_GET['fbclid']);
    // Validasi: panjang minimal & hanya karakter alfanumerik, -, _
    if (strlen($fbclid) >= 20 && preg_match('/^[a-zA-Z0-9_-]+$/', $fbclid)) {
        // Set cookie _fbc (standar Meta) — berlaku 30 hari
        setcookie('_fbc', $fbclid, [
            'expires' => time() + 30*24*60*60,
            'path' => '/',
            'domain' => $cookie_domain,
            'secure' => true,        // Wajib true jika HTTPS (harus aktif!)
            'httponly' => false,     // false agar JS bisa baca untuk Pixel
            'samesite' => 'None'     // Wajib untuk cross-site (iklan → LP → checkout)
        ]);
        // Simpan juga ke session sebagai fallback
        $_SESSION['fbclid_final'] = $fbclid;
    }
}
// === AKHIR SIMPAN fbclid ===

// Function untuk kirim CAPI — DIPERBAIKI: hapus spasi di URL
function sendMetaCAPIEvent($access_token, $pixel_id, $event_name, $user_data, $custom_data = [], $event_id = null) {
    if (!$event_id) {
        $event_id = uniqid('event_', true);
    }
    // ✅ Perbaikan: HAPUS SPASI di URL (sebelumnya: '/ ' . $pixel_id)
    $capi_url = 'https://graph.facebook.com/v20.0/' . $pixel_id . '/events';

    $data = [
        'data' => [
            [
                'event_name' => $event_name,
                'event_time' => time(),
                'event_id' => $event_id,
                'action_source' => 'website',
                'user_data' => $user_data,
                'custom_data' => $custom_data
            ]
        ],
        'access_token' => $access_token
    ];

    $ch = curl_init($capi_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Dinaikkan ke true

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        error_log("CAPI Error ({$event_name}): HTTP {$http_code}, Response: " . $response);
    }

    return $response;
}

// Get product ID
$produk_id = (int) get('id');
if (!$produk_id) {
    redirect('index.php');
}

// Get product data with bundling
$produk = fetchRow("SELECT * FROM produk WHERE id = ?", [$produk_id]);
if (!$produk) {
    setMessage('Produk tidak ditemukan', 'error');
    redirect('index.php');
}

// Get the HTTP POST URL from product
$post_url = $produk['http_post'];

// Get bundling products (HANYA YANG AKTIF)
$bundling = fetchAll("
    SELECT b.id, b.deskripsi as deskripsi_bundling, p.nama, p.harga, b.diskon 
    FROM bundling b 
    JOIN produk p ON b.produk_bundling_id = p.id 
    WHERE b.produk_id = ? AND b.is_active = 1
", [$produk_id]);

// Process form submission
if (isPost()) {
    $nama = trim(post('nama'));
    $nomor_wa = trim(post('nomor_wa'));
    $email = trim(post('email')); // <--- TANGKAP EMAIL
    $bundling_ids = post('bundling_ids', []);
    
    // === AMBIL fbc dari COOKIE dulu, baru SESSION (lebih andal) ===
    $fbc = $_COOKIE['_fbc'] ?? $_SESSION['fbclid_final'] ?? null;
    $fbp = $_COOKIE['_fbp'] ?? null;
    // === AKHIR AMBIL fbc ===
    
    // Cek apakah fitur email aktif tapi pengunjung tidak mengisinya
    $is_email_required = (isset($produk['show_email']) && $produk['show_email'] == 1);

    if (empty($nama) || empty($nomor_wa)) {
        setMessage('Nama dan nomor WhatsApp wajib diisi', 'error');
    } elseif ($is_email_required && empty($email)) {
        setMessage('Alamat email wajib diisi', 'error');
    } elseif ($is_email_required && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setMessage('Format alamat email tidak valid', 'error');
    } elseif (strlen($nomor_wa) < 5 || !is_numeric($nomor_wa)) {
        setMessage('Nomor WhatsApp minimal 5 digit dan hanya boleh angka', 'error');
    } else {
        if (substr($nomor_wa, 0, 1) === '0') {
            $nomor_wa = '62' . substr($nomor_wa, 1);
        }
        
        $existing_customer = fetchRow("SELECT * FROM pelanggan WHERE nomor_wa = ?", [$nomor_wa]);
        
        if ($existing_customer) {
            // Update nama dan email jika ada inputan baru
            $new_nama = $nama;
            $new_email = !empty($email) ? $email : $existing_customer['email'];
            execute("UPDATE pelanggan SET nama = ?, email = ? WHERE id = ?", [$new_nama, $new_email, $existing_customer['id']]);
            $customer_id = $existing_customer['id'];
        } else {
            // Insert pelanggan baru beserta email
            execute("INSERT INTO pelanggan (nama, nomor_wa, email) VALUES (?, ?, ?)", [$nama, $nomor_wa, $email]);
            $customer_id = db()->insert_id;
        }
        
        if ($post_url) {
            $post_data = [
                'name' => $nama,
                'phone' => $nomor_wa
            ];

            $ch = curl_init($post_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $response = curl_exec($ch);
            curl_close($ch);
        }
        
        $total_harga = $produk['harga'];
        $bundling_details = [];
        
        if (!empty($bundling_ids) && is_array($bundling_ids)) {
            foreach ($bundling_ids as $bundle_id) {
                $bundle = fetchRow("SELECT b.*, p.* FROM bundling b JOIN produk p ON b.produk_bundling_id = p.id WHERE b.id = ?", [$bundle_id]);
                if ($bundle) {
                    $harga_diskon = $bundle['harga'] - $bundle['diskon'];
                    $total_harga += $harga_diskon;
                    $bundling_details[] = [
                        'produk_id' => $bundle['produk_bundling_id'],
                        'harga' => $harga_diskon
                    ];
                }
            }
        }
        
        // CEK APAKAH ADA KUPON YANG DIPAKAI
        $kupon_id = post('kupon_id') ? (int)post('kupon_id') : null;
        $total_diskon = post('total_diskon') ? (int)post('total_diskon') : 0;
        
        // Kurangi total harga dengan diskon
        if ($total_diskon > 0) {
            $total_harga -= $total_diskon;
            if ($total_harga < 0) $total_harga = 0; // Jaga-jaga agar tidak minus
        }
        
        // Generate UUID (Contoh hasil: INV-8F3A9C2B)
        $uuid = 'INV-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

        // ✅ Simpan transaksi BESERTA DATA KUPON dan UUID
        execute("INSERT INTO transaksi (uuid, pelanggan_id, total_harga, status, ip_pelanggan, user_agent_pelanggan, fbc, fbp, kupon_id, total_diskon) 
                 VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?)", 
                [$uuid, $customer_id, $total_harga, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $fbc, $fbp, $kupon_id, $total_diskon]);
        
        $transaksi_id = db()->insert_id;

        // TAMBAHKAN +1 KUOTA KUPON TERPAKAI
        if ($kupon_id) {
            execute("UPDATE kupon SET terpakai = terpakai + 1 WHERE id = ?", [$kupon_id]);
        }
        
		// HITUNG HARGA DAN PROFIT PRODUK UTAMA SETELAH DIPOTONG KUPON
        $harga_normal_utama = (float)$produk['harga'];
        $profit_normal_utama = ($produk['profit'] > 0) ? (float)$produk['profit'] : $harga_normal_utama;
        
        // Cari modal asli produk (Harga - Profit)
        $modal_utama = $harga_normal_utama - $profit_normal_utama;

        // Terapkan pemotongan kupon ke harga jual produk utama
        $harga_final_utama = $harga_normal_utama - $total_diskon;
        if ($harga_final_utama < 0) $harga_final_utama = 0; // Jaga-jaga agar harga tidak minus

        // Profit Final = Harga Jual Baru - Modal Asli
        $profit_final_utama = $harga_final_utama - $modal_utama;
        if ($profit_final_utama < 0) $profit_final_utama = 0; // Jaga-jaga agar profit tidak minus di laporan

        // Simpan ke detail transaksi dengan harga dan profit yang sudah dipotong diskon
        execute("INSERT INTO detail_transaksi (transaksi_id, produk_id, harga, profit) VALUES (?, ?, ?, ?)", 
            [$transaksi_id, $produk_id, $harga_final_utama, $profit_final_utama]);
		
        foreach ($bundling_details as $bundle_detail) {
			// Ambil data produk asli untuk cek harga normal & profit normal
			$bundle_produk = fetchRow("SELECT harga, profit FROM produk WHERE id = ?", [$bundle_detail['produk_id']]);

			$harga_normal = (float)$bundle_produk['harga'];
			$profit_normal = (float)$bundle_produk['profit'];
			$harga_jual_bundling = (float)$bundle_detail['harga'];

			// Hitung modal asli: Harga Normal - Profit Normal
			$modal = $harga_normal - $profit_normal;

			// Profit Baru: Harga Jual Bundling - Modal
			// Jika hasilnya negatif (karena diskon terlalu besar), set ke 0
			$profit_final = max(0, $harga_jual_bundling - $modal);

			execute("INSERT INTO detail_transaksi (transaksi_id, produk_id, harga, profit) VALUES (?, ?, ?, ?)", 
				[$transaksi_id, $bundle_detail['produk_id'], $harga_jual_bundling, $profit_final]);
		}
        
        try {
            $followup_generated = generateFollowupForNewTransactionTrueSequential($transaksi_id);
        } catch (Exception $e) {
            error_log("Failed to generate smart followup queue for transaction $transaksi_id: " . $e->getMessage());
        }

        // Kirim AddToCart via CAPI
        if (!empty($produk['conversion_api_token']) && !empty($produk['meta_pixel_id'])) {
            if (!isset($_SESSION['addtocart_sent_' . $transaksi_id])) {
                $user_data = [
                    'ph' => hash('sha256', $nomor_wa),
                    'client_ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'client_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ];
                if ($fbc) $user_data['fbc'] = $fbc;
                if ($fbp) $user_data['fbp'] = $fbp;

                $custom_data = [
                    'currency' => 'IDR',
                    'value' => (float) $produk['harga'],
                    'content_ids' => [(string) $produk_id],
                    'contents' => [
                        [
                            'id' => (string) $produk_id,
                            'quantity' => 1
                        ]
                    ],
                    'content_type' => 'product'
                ];

                $event_id = 'addtocart_' . $transaksi_id;

                sendMetaCAPIEvent(
                    $produk['conversion_api_token'],
                    $produk['meta_pixel_id'],
                    'AddToCart',
                    $user_data,
                    $custom_data,
                    $event_id
                );

                $_SESSION['addtocart_sent_' . $transaksi_id] = true;
            }
        }

        // ✅ BERSIHKAN SESSION & COOKIE SETELAH CHECKOUT SUKSES
        unset($_SESSION['fbclid_final']);
        
        // Hancurkan cookie _fbc di browser pembeli
        setcookie('_fbc', '', time() - 3600, '/', '.edumuslim.my.id', true, false);

        redirect("invoice.php?uuid=$uuid");
    }
}

// Tangkap kupon dari URL
$kupon_dari_url = isset($_GET['kupon']) ? clean($_GET['kupon']) : '';

$page_title = 'Checkout - ' . $produk['nama'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
	<link rel="icon" type="image/x-icon" href="favicon.ico">
	<!-- Preload critical resources -->
    <link rel="preload" href="https://connect.facebook.net/en_US/fbevents.js" as="script">
    <!-- Google Fonts: Plus Jakarta Sans for Premium Modern look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Minified CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --brand-primary: #054A3B;     /* Deep Emerald Green - Islamic/Premium */
            --brand-primary-light: #0A6B56;
            --brand-accent: #D4AF37;      /* Muted Gold */
            --brand-accent-hover: #C5A028;
            --bg-color: #F8F9F7;          /* Soft warm off-white */
            --card-bg: #FFFFFF;
            --text-main: #1A201E;
            --text-muted: #6B726F;
            --border-color: #E6EAE8;
            --success-bg: #F0FDF4;
            --success-text: #166534;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2rem 1rem;
            /* Subtle texture for depth */
            background-image: radial-gradient(var(--border-color) 1px, transparent 1px);
            background-size: 24px 24px;
        }

        .checkout-container {
            width: 100%;
            max-width: 540px;
            animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .card {
            border: none;
            border-radius: 20px;
            background: var(--card-bg);
            box-shadow: 0 20px 40px rgba(5, 74, 59, 0.06), 0 1px 3px rgba(0,0,0,0.02);
            overflow: hidden;
        }

        .card-header {
            background: var(--brand-primary);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: none;
            position: relative;
            overflow: hidden;
        }

        /* Subtle Islamic Geometric vibe via CSS overlay */
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.05) 10%, transparent 10%),
                        radial-gradient(circle, rgba(255, 255, 255, 0.03) 10%, transparent 10%);
            background-size: 30px 30px;
            background-position: 0 0, 15px 15px;
            transform: rotate(45deg);
            pointer-events: none;
        }

        .card-header h4 {
            font-weight: 700;
            letter-spacing: -0.5px;
            position: relative;
            z-index: 1;
            font-size: 1.25rem;
        }
        
        .card-header h4 i {
            color: var(--brand-accent);
        }

        .card-body {
            padding: 2rem 2.5rem;
        }

        @media (max-width: 576px) {
            .card-body { padding: 1.5rem; }
            body { padding: 1rem 0.5rem; }
        }

        /* Form Styling */
        h6.section-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
        }
        h6.section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
            margin-left: 1rem;
        }

        .form-floating > .form-control {
            border: 1.5px solid var(--border-color);
            border-radius: 12px;
            font-family: inherit;
            color: var(--text-main);
            transition: all 0.25s ease;
            box-shadow: none;
        }
        .form-floating > .form-control:focus {
            border-color: var(--brand-primary-light);
            box-shadow: 0 0 0 4px rgba(10, 107, 86, 0.1);
        }
        .form-floating > label {
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Product Info Box */
        .product-info {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px dashed var(--border-color);
        }
        .product-info h5 {
            font-weight: 700;
            color: var(--text-main);
            font-size: 1.35rem;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        .product-price-badge {
            display: inline-block;
            background: var(--success-bg);
            color: var(--success-text);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        /* Bundling Cards */
        .bundle-item {
            border: 1.5px solid var(--border-color);
            border-radius: 14px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            background-color: var(--card-bg);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .bundle-item:hover {
            border-color: var(--brand-primary-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(5, 74, 59, 0.04);
        }

        .bundle-item.selected {
            border-color: var(--brand-primary);
            background: rgba(5, 74, 59, 0.02);
            box-shadow: 0 4px 12px rgba(5, 74, 59, 0.08);
        }

        .bundle-item.selected::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--brand-primary);
        }

        .form-check-input {
            width: 1.25em;
            height: 1.25em;
            margin-top: 0.15em;
            border-color: #cbd5e1;
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
        }

        .price-original {
            text-decoration: line-through;
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 500;
        }
        .price-discount {
            color: var(--brand-primary);
            font-weight: 700;
            font-size: 1.05rem;
            display: block;
            line-height: 1.2;
        }
        
        .bundle-description-container {
            font-size: 0.85rem;
            line-height: 1.5;
            margin-top: 0.5rem;
        }
        .btn-toggle-desc {
            color: var(--brand-primary-light);
            font-weight: 600;
            transition: color 0.2s;
        }
        .btn-toggle-desc:hover { color: var(--brand-primary); }

        /* Order Summary / Receipt */
        .summary-box {
            background: #FAFAFA;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        .summary-item {
            font-size: 0.95rem;
            color: var(--text-main);
            font-weight: 500;
        }
        .summary-bundle-item {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .summary-box hr {
            border-color: var(--border-color);
            border-style: dashed;
            opacity: 1;
            margin: 1.25rem 0;
        }
        .total-row {
            font-size: 1.2rem;
            color: var(--brand-primary);
        }

        /* Coupon Input */
        .input-group .form-control {
            border: 1.5px solid var(--border-color);
            border-radius: 10px 0 0 10px;
            height: 48px;
        }
        .input-group .form-control:focus {
            border-color: var(--brand-primary-light);
            box-shadow: none;
        }
        .input-group .btn-secondary {
            background: var(--text-main);
            border: none;
            border-radius: 0 10px 10px 0;
            font-weight: 600;
            padding: 0 1.5rem;
            transition: background 0.2s;
        }
        .input-group .btn-secondary:hover {
            background: var(--brand-primary);
        }

        /* CTA Button */
        .btn-primary {
            background: var(--brand-primary);
            color: #ffffff;
            font-weight: 700;
            letter-spacing: 0.5px;
            border: none;
            border-radius: 12px;
            padding: 16px 30px;
            font-size: 1.1rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 8px 20px rgba(5, 74, 59, 0.2);
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover {
            background: var(--brand-primary-light);
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(5, 74, 59, 0.3);
        }
        .btn-primary:active {
            transform: translateY(1px);
        }

        /* Animations */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stagger-1 { animation: fadeUp 0.6s 0.1s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .stagger-2 { animation: fadeUp 0.6s 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .stagger-3 { animation: fadeUp 0.6s 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
        .stagger-4 { animation: fadeUp 0.6s 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-color); }
        ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
        ::selection { background: var(--brand-primary-light); color: white; }

    </style>
</head>
<body>

<div class="checkout-container">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-lock me-2"></i>Secure Checkout</h4>
        </div>
        <div class="card-body">
            
            <div class="product-info stagger-1">
                <h5><?= clean($produk['nama']) ?></h5>
                <div class="product-price-badge"><?= formatCurrency($produk['harga']) ?></div>
            </div>

            <?php $msg = getMessage(); if ($msg): ?>
                <div class="alert alert-<?= $msg[1] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" style="border-radius: 12px;">
                    <?= clean($msg[0]) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="checkoutForm">
                
                <div class="stagger-2">
                    <h6 class="section-title">Detail Informasi</h6>

                    <div class="form-floating mb-3">
                        <input type="text" name="nama" id="nama" class="form-control" 
                               placeholder="Nama lengkap Anda" value="<?= clean(post('nama')) ?>" required>
                        <label for="nama">Nama Lengkap *</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="tel" name="nomor_wa" id="nomor_wa" class="form-control" 
                               placeholder="08123456789" value="<?= clean(post('nomor_wa')) ?>" required>
                        <label for="nomor_wa">Nomor WhatsApp *</label>
                    </div>

                    <?php if (isset($produk['show_email']) && $produk['show_email'] == 1): ?>
                    <div class="form-floating mb-3">
                        <input type="email" name="email" id="email" class="form-control" 
                               placeholder="alamat@email.com" value="<?= clean(post('email')) ?>" required>
                        <label for="email">Alamat Email *</label>
                    </div>
                    <?php endif; ?>
                </div>

				<?php if (!empty($bundling)): ?>
                <div class="stagger-3 mt-4">
                    <h6 class="section-title">Tingkatkan Pesanan (Opsional)</h6>
                    <div id="bundlingContainer">
                        <?php foreach ($bundling as $bundle): 
                            $harga_diskon = $bundle['harga'] - $bundle['diskon'];
                        ?>
                        <!-- Entire div is clickable via JS logic / wrapping label -->
                        <label class="bundle-item d-block m-0 mb-3" for="bundle_<?= $bundle['id'] ?>">
                            <div class="d-flex align-items-start gap-3">
                                <div class="form-check m-0 pt-1">
                                    <input class="form-check-input bundle-checkbox" type="checkbox" 
                                           name="bundling_ids[]" value="<?= $bundle['id'] ?>" 
                                           id="bundle_<?= $bundle['id'] ?>"
                                           data-price="<?= $harga_diskon ?>">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong style="color: var(--text-main); font-size: 0.95rem; line-height: 1.3; max-width: 65%;">
                                            <?= clean($bundle['nama']) ?>
                                        </strong>
                                        <div class="text-end" style="min-width: 80px;">
                                            <span class="price-original"><?= formatCurrency($bundle['harga']) ?></span><br>
                                            <span class="price-discount"><?= formatCurrency($harga_diskon) ?></span>
                                        </div>
                                    </div>

                                    <div class="bundle-description-container text-muted">
                                        <?php 
                                        $desc = clean($bundle['deskripsi_bundling']);
                                        if (strlen($desc) > 80): ?>
                                            <span class="desc-short"><?= substr($desc, 0, 80) ?>...</span>
                                            <span class="desc-full d-none"><?= nl2br($desc) ?></span>
                                            <a href="javascript:void(0)" class="btn-toggle-desc ms-1" style="text-decoration:none;">Selengkapnya</a>
                                        <?php else: ?>
                                            <?= nl2br($desc) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
				<?php endif; ?>

                <?php if ($produk['show_kupon'] == 1): ?>
                <div class="stagger-4 mt-4">
                    <h6 class="section-title">Kode Promo</h6>
                    <div id="form-kupon-container">
                        <div class="input-group">
                            <input type="text" id="kode_promo" class="form-control text-uppercase" placeholder="Masukkan kupon..." value="<?= $kupon_dari_url ?>">
                            <button type="button" class="btn btn-secondary" id="btn-terapkan-kupon">Terapkan</button>
                        </div>
                        <div id="pesan_kupon" class="mt-2" style="font-size: 0.85rem; padding-left: 5px;"></div>
                    </div>
                </div>
                <input type="hidden" name="kupon_id" id="input_kupon_id" value="">
                <input type="hidden" name="total_diskon" id="input_total_diskon" value="0">
                <?php endif; ?>

                <div class="summary-box stagger-4" id="orderSummary">
                    <!-- Dinamis via JS -->
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mt-4 stagger-4" id="checkoutBtn">
                    Selesaikan Pembayaran <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Meta Pixel Code -->
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    
    // ✅ Ambil fbc dari COOKIE (standar Meta), lalu fallback ke session PHP
    <?php
    $fbc_js = $_COOKIE['_fbc'] ?? $_SESSION['fbclid_final'] ?? null;
    $fbp_js = $_COOKIE['_fbp'] ?? null;
    ?>

    fbq('init', '<?= $produk['meta_pixel_id'] ?>', {
        <?php if ($fbc_js): ?>'fbc': '<?= addslashes($fbc_js) ?>',<?php endif; ?>
        <?php if ($fbp_js): ?>'fbp': '<?= addslashes($fbp_js) ?>',<?php endif; ?>
        'agent': 'pl_web'
    });
    
    fbq('track', 'AddToCart', {
        content_ids: ['<?= $produk['id'] ?>'],
        content_name: '<?= addslashes($produk['nama']) ?>',
        content_type: 'product',
        value: <?= $produk['harga'] ?>,
        currency: 'IDR'
    });
</script>
<noscript>
    <img height="1" width="1" style="display:none"
         src="https://www.facebook.com/tr?id=<?= $produk['meta_pixel_id'] ?>&ev=AddToCart&noscript=1"/>
</noscript>
<!-- End Meta Pixel Code -->

<!-- Minified JS with defer -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('btn-toggle-desc')) {
        e.preventDefault();
        const container = e.target.closest('.bundle-description-container');
        const shortSpan = container.querySelector('.desc-short');
        const fullSpan = container.querySelector('.desc-full');
        
        if (fullSpan.classList.contains('d-none')) {
            fullSpan.classList.remove('d-none');
            shortSpan.classList.add('d-none');
            e.target.textContent = 'Sembunyikan';
        } else {
            fullSpan.classList.add('d-none');
            shortSpan.classList.remove('d-none');
            e.target.textContent = 'Selengkapnya';
        }
    }
});

function formatCurrencyJS(t){
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(t);
}

// Logika Auto-fill dari LocalStorage
function setupAutoFill() {
    const namaInput = document.getElementById('nama');
    const nomorWaInput = document.getElementById('nomor_wa');
    const emailInput = document.getElementById('email'); 

    const cachedNama = localStorage.getItem('customer_nama');
    const cachedWA = localStorage.getItem('customer_wa');
    const cachedEmail = localStorage.getItem('customer_email'); 

    if (namaInput && cachedNama && !namaInput.value) namaInput.value = cachedNama;
    if (nomorWaInput && cachedWA && !nomorWaInput.value) nomorWaInput.value = cachedWA;
    if (emailInput && cachedEmail && !emailInput.value) emailInput.value = cachedEmail;

    if (namaInput) {
        namaInput.addEventListener('input', () => localStorage.setItem('customer_nama', namaInput.value));
    }
    if (nomorWaInput) {
        nomorWaInput.addEventListener('input', () => localStorage.setItem('customer_wa', nomorWaInput.value));
    }
    if (emailInput) {
        emailInput.addEventListener('input', () => localStorage.setItem('customer_email', emailInput.value));
    }
}

function setupPhoneValidation() {
    const nomorWaInput = document.getElementById('nomor_wa');
    const namaInput = document.getElementById('nama');
    if (!nomorWaInput || !namaInput) return;

    const savedPhone = localStorage.getItem('crm_customer_phone');
    const savedName = localStorage.getItem('crm_customer_name');
    
    if (savedPhone && !nomorWaInput.value) nomorWaInput.value = savedPhone;
    if (savedName && !namaInput.value) namaInput.value = savedName;

    namaInput.addEventListener('input', function() {
        const currentName = this.value.trim();
        localStorage.setItem('crm_customer_name', currentName);
        if (currentName !== '') {
            localStorage.setItem('crm_name_is_manual', 'true');
        } else {
            localStorage.removeItem('crm_name_is_manual');
        }
    });

    nomorWaInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        localStorage.setItem('crm_customer_phone', this.value.trim());
        const isManual = localStorage.getItem('crm_name_is_manual');
        if (isManual !== 'true') {
            localStorage.removeItem('crm_customer_name');
            namaInput.value = ''; 
        }
    });

    nomorWaInput.addEventListener('blur', function() {
        let phone = this.value.trim();
        if (phone.length >= 5) {
            if (phone.startsWith('0')) {
                phone = '62' + phone.substring(1);
                this.value = phone;
            }
            localStorage.setItem('crm_customer_phone', phone);
            const isManual = localStorage.getItem('crm_name_is_manual');
            if (isManual === 'true') return; 

            fetch('api/get_customer.php?phone=' + encodeURIComponent(phone), { cache: 'no-store' })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.customer) {
                        if (namaInput.value.trim() === '') {
                            namaInput.value = data.customer.nama;
                            localStorage.setItem('crm_customer_name', data.customer.nama);
                            namaInput.dispatchEvent(new Event('change'));
                        }
                    }
                })
                .catch(error => console.log('Customer lookup failed'));
        }
    });
}

let aktifDiskonKupon = 0;

function updateTotal() {
    let total = <?= $produk['harga'] ?>;
    const orderSummary = document.getElementById('orderSummary');
    if (!orderSummary) return;
    orderSummary.innerHTML = '';
    
    // Baris Produk Utama
    const mainItem = document.createElement('div');
    mainItem.className = 'summary-item d-flex justify-content-between mb-3';
    mainItem.innerHTML = `<span><?= clean($produk['nama']) ?></span><span><?= formatCurrency($produk['harga']) ?></span>`;
    orderSummary.appendChild(mainItem);
    
    // Baris Bundling
    document.querySelectorAll('.bundle-checkbox').forEach(checkbox => {
        const bundleItem = checkbox.closest('.bundle-item');
        if (checkbox.checked) {
            const bundleItemEl = document.createElement('div');
            bundleItemEl.className = 'summary-bundle-item d-flex justify-content-between mb-2';
            const price = parseFloat(checkbox.dataset.price);
            total += price;
            const bundleLabel = bundleItem.querySelector('strong').textContent.trim();
            bundleItemEl.innerHTML = `<span style="color: var(--brand-primary);"><i class="fas fa-plus-circle me-1"></i> ${bundleLabel}</span><span style="color: var(--brand-primary); font-weight: 500;">${formatCurrencyJS(price)}</span>`;
            orderSummary.appendChild(bundleItemEl);
            bundleItem.classList.add('selected');
        } else {
            bundleItem.classList.remove('selected');
        }
    });

    // Baris Diskon Kupon (Jika Ada)
    if (aktifDiskonKupon > 0) {
        const diskonEl = document.createElement('div');
        diskonEl.className = 'summary-item d-flex justify-content-between mb-2 text-danger fw-bold';
        diskonEl.innerHTML = `<span><i class="fas fa-tag me-1"></i> Diskon</span><span>- ${formatCurrencyJS(aktifDiskonKupon)}</span>`;
        orderSummary.appendChild(diskonEl);
        
        total -= aktifDiskonKupon;
        if (total < 0) total = 0;
    }

    const hr = document.createElement('hr');
    orderSummary.appendChild(hr);
    
    // Baris Total Akhir
    const totalRow = document.createElement('div');
    totalRow.className = 'd-flex justify-content-between fw-bold total-row align-items-center mb-0';
    totalRow.innerHTML = `<span style="color: var(--text-main); font-size: 1rem;">Total Pembayaran</span><span id="totalAmount">${formatCurrencyJS(total)}</span>`;
    orderSummary.appendChild(totalRow);
}

function setupKupon() {
    const btnKupon = document.getElementById('btn-terapkan-kupon');
    const inputKode = document.getElementById('kode_promo');

    if(btnKupon) {
        btnKupon.addEventListener('click', function() {
            const kode = inputKode.value.trim();
            const pesan = document.getElementById('pesan_kupon');
            
            if(kode === '') {
                pesan.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Masukkan kode promo.</span>';
                return;
            }

            pesan.innerHTML = '<span style="color: var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Memeriksa kode...</span>';
            
            const formData = new FormData();
            formData.append('kode_kupon', kode);
            formData.append('produk_id', '<?= $produk['id'] ?>');
            formData.append('total_harga', '<?= $produk['harga'] ?>'); 

            fetch('api/cek_kupon.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    pesan.innerHTML = `<span style="color: var(--success-text); font-weight: 600;"><i class="fas fa-check-circle"></i> ${data.message}</span>`;
                    aktifDiskonKupon = parseFloat(data.potongan);
                    document.getElementById('input_kupon_id').value = data.kupon_id;
                    document.getElementById('input_total_diskon').value = data.potongan;
                    updateTotal();
                } else {
                    pesan.innerHTML = `<span class="text-danger fw-bold"><i class="fas fa-times-circle"></i> ${data.message}</span>`;
                    aktifDiskonKupon = 0;
                    document.getElementById('input_kupon_id').value = '';
                    document.getElementById('input_total_diskon').value = '0';
                    updateTotal();
                }
            })
            .catch(error => {
                pesan.innerHTML = '<span class="text-danger">Terjadi kesalahan koneksi.</span>';
            });
        });
    }

    <?php if (!empty($kupon_dari_url)): ?>
        setTimeout(function() {
            if(btnKupon) btnKupon.click();
        }, 600);
    <?php endif; ?>
}

function setupEventListeners() {
    document.querySelectorAll('.bundle-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateTotal);
    });
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function() {
            const checkoutBtn = document.getElementById('checkoutBtn');
            if (checkoutBtn) {
                checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses Pesanan...';
                checkoutBtn.style.opacity = '0.8';
                checkoutBtn.disabled = true;
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setupAutoFill();
    setupPhoneValidation();
    setupEventListeners();
    updateTotal();
    setupKupon();
});
</script>
</body>
</html>