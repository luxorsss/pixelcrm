<?php
// Agar session & cookie bisa diakses di semua subdomain
ini_set('session.cookie_domain', '.edumuslim.my.id');
session_set_cookie_params(0, '/', '.edumuslim.my.id');
session_start();

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
            'domain' => '.edumuslim.my.id',
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
        // Peringatan jika email kosong padahal fitur email diaktifkan
        setMessage('Alamat email wajib diisi', 'error');
    } elseif ($is_email_required && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // (Opsional tapi disarankan) Peringatan jika format email salah/ngasal
        setMessage('Format alamat email tidak valid', 'error');
    } elseif (strlen($nomor_wa) < 5 || !is_numeric($nomor_wa)) {
        setMessage('Nomor WhatsApp minimal 5 digit dan hanya boleh angka', 'error');
    } else {
        if (substr($nomor_wa, 0, 1) === '0') {
            $nomor_wa = '62' . substr($nomor_wa, 1);
        }
        
        $existing_customer = fetchRow("SELECT * FROM pelanggan WHERE nomor_wa = ?", [$nomor_wa]);
        
        if ($existing_customer) {
            if ($existing_customer['nama'] !== $nama) {
                execute("UPDATE pelanggan SET nama = ? WHERE id = ?", [$nama, $existing_customer['id']]);
            }
            $customer_id = $existing_customer['id'];
        } else {
            execute("INSERT INTO pelanggan (nama, nomor_wa) VALUES (?, ?)", [$nama, $nomor_wa]);
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
        
        // ✅ Simpan transaksi BESERTA DATA KUPON
        execute("INSERT INTO transaksi (pelanggan_id, total_harga, status, ip_pelanggan, user_agent_pelanggan, fbc, fbp, kupon_id, total_diskon) 
                 VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?)", 
                [$customer_id, $total_harga, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $fbc, $fbp, $kupon_id, $total_diskon]);
        
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
        // Agar jika besok dia beli lagi lewat link WA organik, tidak terhitung sebagai konversi iklan lama
        unset($_SESSION['fbclid_final']);
        
        // Hancurkan cookie _fbc di browser pembeli
        setcookie('_fbc', '', time() - 3600, '/', '.edumuslim.my.id', true, false);

        redirect("invoice.php?id=$transaksi_id");
    }
}

// Tangkap kupon dari URL (contoh: co.php?id=1&kupon=PROMO20)
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
    <!-- Minified CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Inline critical CSS */
        body{background:#f2f5fa;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;min-height:100vh}
        .checkout-container{max-width:500px;margin:2rem auto;padding:0 1rem}
        .card{border:none;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.1);background:white}
        .card-header{background:linear-gradient(135deg,#1976d2,#1565c0);color:white;border-radius:15px 15px 0 0!important;padding:1.5rem}
        .form-control,.form-check-input{border-radius:10px}
        .btn-primary{background:linear-gradient(135deg,#1976d2,#1565c0);border:none;border-radius:10px;padding:12px 30px}
        .btn-primary:hover{background:linear-gradient(135deg,#1565c0,#0d47a1)}
        .product-info{background:#f8f9fa;border-radius:10px;padding:1rem;margin-bottom:1rem}
        .bundle-item{border:3px dashed #ff2400;border-radius:10px;padding:1rem;margin-bottom:0.5rem;background-color: #fffbe2;transition: background-color 0.2s, border-color 0.2s}
        .bundle-item.selected{border-color:#1976d2;background:#f3f8ff}
        .price-original{text-decoration:line-through;color:#666}
        .price-discount{color:#d32f2f;font-weight:bold}
        .summary-box{background:#f8f9fa;border:2px solid #1976d2;border-radius:10px;padding:1rem}
        .loading{opacity:0.7;pointer-events:none}
    </style>
</head>
<body>

<div class="checkout-container">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Checkout</h4>
        </div>
        <div class="card-body p-4">
            
            <div class="product-info">
                <h5><?= clean($produk['nama']) ?></h5>
                <h6 class="text-primary"><?= formatCurrency($produk['harga']) ?></h6>
            </div>

            <?php $msg = getMessage(); if ($msg): ?>
                <div class="alert alert-<?= $msg[1] === 'error' ? 'danger' : $msg[1] ?> alert-dismissible fade show">
                    <?= clean($msg[0]) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="checkoutForm">
                <h6 class="mb-3">Informasi Pembeli</h6>

				<div class="mb-3">
					<label class="form-label">Nama Lengkap *</label>
					<input type="text" name="nama" id="nama" class="form-control" 
						   placeholder="Nama lengkap Anda" value="<?= clean(post('nama')) ?>" required>
				</div>

				<div class="mb-3">
					<label class="form-label">Nomor WhatsApp *</label>
					<input type="text" name="nomor_wa" id="nomor_wa" class="form-control" 
						   placeholder="08123456789" value="<?= clean(post('nomor_wa')) ?>" required>
				</div>

                <?php if (isset($produk['show_email']) && $produk['show_email'] == 1): ?>
                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" id="email" class="form-control" 
                           placeholder="alamat@email.com" value="<?= clean(post('email')) ?>" required>
                </div>
                <?php endif; ?>

				<?php if (!empty($bundling)): ?>
				<h6 class="mb-3 mt-4">Produk Tambahan (Bundling)</h6>
				<div id="bundlingContainer">
					<?php foreach ($bundling as $bundle): 
						$harga_diskon = $bundle['harga'] - $bundle['diskon'];
					?>
					<div class="bundle-item" data-bundle-id="<?= $bundle['id'] ?>">
						<div class="form-check">
							<input class="form-check-input bundle-checkbox" type="checkbox" 
								   name="bundling_ids[]" value="<?= $bundle['id'] ?>" 
								   id="bundle_<?= $bundle['id'] ?>"
								   data-price="<?= $harga_diskon ?>">
							<label class="form-check-label w-100" for="bundle_<?= $bundle['id'] ?>">
								<div class="d-flex justify-content-between align-items-start">
									<div style="max-width: 70%;">
										<strong><?= clean($bundle['nama']) ?></strong>

										<div class="bundle-description-container small text-muted">
											<?php 
											$desc = clean($bundle['deskripsi_bundling']);
											if (strlen($desc) > 80): ?>
												<span class="desc-short"><?= substr($desc, 0, 80) ?>...</span>
												<span class="desc-full d-none"><?= nl2br($desc) ?></span>
												<a href="javascript:void(0)" class="btn-toggle-desc" style="text-decoration:none; font-size:11px;">Selengkapnya</a>
											<?php else: ?>
												<?= nl2br($desc) ?>
											<?php endif; ?>
										</div>
									</div>
									<div class="text-end">
										<span class="price-original" style="font-size: 0.8rem;"><?= formatCurrency($bundle['harga']) ?></span><br>
										<span class="price-discount"><?= formatCurrency($harga_diskon) ?></span>
									</div>
								</div>
							</label>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

                <h6 class="mb-3 mt-4">Ringkasan Pesanan</h6>
                <div class="summary-box" id="orderSummary">
                    <div class="summary-item d-flex justify-content-between mb-2">
                        <span><?= clean($produk['nama']) ?></span>
                        <span><?= formatCurrency($produk['harga']) ?></span>
                    </div>
                    
                    <?php foreach ($bundling as $bundle): 
                        $harga_diskon = $bundle['harga'] - $bundle['diskon'];
                        $is_checked = false;
                        if ($is_checked): ?>
                    <div class="summary-bundle-item d-flex justify-content-between mb-2" 
                         data-bundle-id="<?= $bundle['id'] ?>" data-price="<?= $harga_diskon ?>">
                        <span><?= clean($bundle['nama']) ?></span>
                        <span><?= formatCurrency($harga_diskon) ?></span>
                    </div>
                    <?php endif; endforeach; ?>
                    
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total</span>
                        <span id="totalAmount"><?= formatCurrency($produk['harga']) ?></span>
                    </div>
                </div>

                <?php if ($produk['show_kupon'] == 1): ?>
                <div class="mt-4 mb-3">
                    <label class="form-label fw-bold"><i class="fas fa-tag"></i> Kode Kupon (Opsional)</label>
                    <div id="form-kupon-container">
                        <div class="input-group">
                            <input type="text" id="kode_promo" class="form-control text-uppercase" placeholder="Masukkan kode promo..." value="<?= $kupon_dari_url ?>">
                            <button type="button" class="btn btn-secondary" id="btn-terapkan-kupon">Terapkan</button>
                        </div>
                        <div id="pesan_kupon" class="mt-2" style="font-size: 0.9rem;"></div>
                    </div>
                </div>

                <input type="hidden" name="kupon_id" id="input_kupon_id" value="">
                <input type="hidden" name="total_diskon" id="input_total_diskon" value="0">
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary w-100 mt-3" id="checkoutBtn">
                    <i class="fas fa-credit-card me-2"></i>Proses Checkout
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
    // Baca dari cookie (lebih andal di JS)
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

function formatCurrencyJS(t){return"Rp "+t.toLocaleString("id-ID")}

// Logika Auto-fill dari LocalStorage
function setupAutoFill() {
    const namaInput = document.getElementById('nama');
    const nomorWaInput = document.getElementById('nomor_wa');
    const emailInput = document.getElementById('email'); // TANGKAP ID EMAIL

    // Ambil dari cache browser jika ada
    const cachedNama = localStorage.getItem('customer_nama');
    const cachedWA = localStorage.getItem('customer_wa');
    const cachedEmail = localStorage.getItem('customer_email'); // AMBIL CACHE EMAIL

    if (namaInput && cachedNama && !namaInput.value) namaInput.value = cachedNama;
    if (nomorWaInput && cachedWA && !nomorWaInput.value) nomorWaInput.value = cachedWA;
    
    // Set value email jika formnya ada (tidak disembunyikan) dan cache-nya ada
    if (emailInput && cachedEmail && !emailInput.value) emailInput.value = cachedEmail;

    // Simpan ke cache saat user mengetik (agar tetap bisa diedit)
    if (namaInput) {
        namaInput.addEventListener('input', () => localStorage.setItem('customer_nama', namaInput.value));
    }
    if (nomorWaInput) {
        nomorWaInput.addEventListener('input', () => localStorage.setItem('customer_wa', nomorWaInput.value));
    }
    
    // Simpan email ke cache saat user mengetik
    if (emailInput) {
        emailInput.addEventListener('input', () => localStorage.setItem('customer_email', emailInput.value));
    }
}

function setupPhoneValidation() {
    const nomorWaInput = document.getElementById('nomor_wa');
    const namaInput = document.getElementById('nama');
    
    nomorWaInput.addEventListener('blur', function() {
        let phone = this.value.trim();
        if (phone.length >= 5) {
            if (phone.startsWith('0')) {
                phone = '62' + phone.substring(1);
                this.value = phone;
            }
            // Tetap lakukan fetch API untuk memastikan data terbaru dari server
            fetch('api/get_customer.php?phone=' + encodeURIComponent(phone))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.customer) {
                        // Hanya timpa jika input nama masih kosong atau berbeda
                        if(!namaInput.value || namaInput.value == "Nama lengkap Anda") {
                            namaInput.value = data.customer.nama;
                            localStorage.setItem('customer_nama', data.customer.nama);
                        }
                    }
                });
        }
    });
}

// Variabel global untuk menyimpan nilai diskon yang sedang aktif
let aktifDiskonKupon = 0;

function updateTotal() {
    let total = <?= $produk['harga'] ?>;
    const orderSummary = document.getElementById('orderSummary');
    if (!orderSummary) return;
    orderSummary.innerHTML = '';
    
    // Baris Produk Utama
    const mainItem = document.createElement('div');
    mainItem.className = 'summary-item d-flex justify-content-between mb-2';
    mainItem.innerHTML = `<span><?= clean($produk['nama']) ?></span><span><?= formatCurrency($produk['harga']) ?></span>`;
    orderSummary.appendChild(mainItem);
    
    // Baris Bundling
    document.querySelectorAll('.bundle-checkbox').forEach(checkbox => {
        if (checkbox.checked) {
            const bundleItemEl = document.createElement('div');
            bundleItemEl.className = 'summary-bundle-item d-flex justify-content-between mb-2 text-success';
            const price = parseFloat(checkbox.dataset.price);
            total += price;
            const bundleLabel = checkbox.closest('.bundle-item').querySelector('strong').textContent;
            bundleItemEl.innerHTML = `<span><i class="fas fa-plus-circle"></i> ${bundleLabel}</span><span>${formatCurrencyJS(price)}</span>`;
            orderSummary.appendChild(bundleItemEl);
        }
        const bundleItem = checkbox.closest('.bundle-item');
        if (bundleItem) bundleItem.classList.toggle('selected', checkbox.checked);
    });

    // Baris Diskon Kupon (Jika Ada)
    if (aktifDiskonKupon > 0) {
        const diskonEl = document.createElement('div');
        diskonEl.className = 'summary-item d-flex justify-content-between mb-2 text-danger fw-bold';
        diskonEl.innerHTML = `<span><i class="fas fa-tag"></i> Diskon Kupon</span><span>- ${formatCurrencyJS(aktifDiskonKupon)}</span>`;
        orderSummary.appendChild(diskonEl);
        
        // Kurangi total harga, pastikan tidak minus
        total -= aktifDiskonKupon;
        if (total < 0) total = 0;
    }

    const hr = document.createElement('hr');
    orderSummary.appendChild(hr);
    
    // Baris Total Akhir
    const totalRow = document.createElement('div');
    totalRow.className = 'd-flex justify-content-between fw-bold h5 mb-0 text-primary';
    totalRow.innerHTML = `<span>Total Bayar</span><span id="totalAmount">${formatCurrencyJS(total)}</span>`;
    orderSummary.appendChild(totalRow);
}

// Logika API Pengecekan Kupon (Letakkan setelah fungsi updateTotal)
function setupKupon() {
    const btnKupon = document.getElementById('btn-terapkan-kupon');
    const formKuponContainer = document.getElementById('form-kupon-container');
    const inputKode = document.getElementById('kode_promo');

    // 2. Logika Cek Kupon (Fetch ke API)
    if(btnKupon) {
        btnKupon.addEventListener('click', function() {
            const kode = inputKode.value.trim();
            const pesan = document.getElementById('pesan_kupon');
            
            if(kode === '') {
                pesan.innerHTML = '<span class="text-danger">Masukkan kode promo dulu.</span>';
                return;
            }

            pesan.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> Mengecek...</span>';
            
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
                    pesan.innerHTML = `<span class="text-success fw-bold"><i class="fas fa-check"></i> ${data.message}</span>`;
                    aktifDiskonKupon = parseFloat(data.potongan);
                    document.getElementById('input_kupon_id').value = data.kupon_id;
                    document.getElementById('input_total_diskon').value = data.potongan;
                    updateTotal();
                } else {
                    pesan.innerHTML = `<span class="text-danger fw-bold"><i class="fas fa-times"></i> ${data.message}</span>`;
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

    // 3. AUTO-APPLY KUPON DARI URL
    <?php if (!empty($kupon_dari_url)): ?>
        // Tunggu setengah detik agar halaman selesai merender, lalu klik tombol terapkan otomatis
        setTimeout(function() {
            if(btnKupon) btnKupon.click();
        }, 500);
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
                checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
                checkoutBtn.disabled = true;
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setupAutoFill(); // Jalankan auto-fill saat load
    setupPhoneValidation();
    setupEventListeners();
    updateTotal();
    setupKupon();
});
</script>
</body>
</html>