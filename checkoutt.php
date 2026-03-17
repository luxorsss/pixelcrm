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

// Get bundling products
$bundling = fetchAll("
    SELECT b.*, p.nama, p.deskripsi, p.harga, b.diskon 
    FROM bundling b 
    JOIN produk p ON b.produk_bundling_id = p.id 
    WHERE b.produk_id = ?
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
    
    if (empty($nama) || empty($nomor_wa)) {
        setMessage('Nama dan nomor WhatsApp wajib diisi', 'error');
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
        
        // ✅ Simpan fbc & fbp ke database — pastikan $fbc tidak kosong
        execute("INSERT INTO transaksi (pelanggan_id, total_harga, status, ip_pelanggan, user_agent_pelanggan, fbc, fbp) 
                 VALUES (?, ?, 'pending', ?, ?, ?, ?)", 
                [$customer_id, $total_harga, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $fbc, $fbp]);
        
        $transaksi_id = db()->insert_id;
        
		$profit_produk_utama = ($produk['profit'] > 0) ? $produk['profit'] : $produk['harga'];
		
        execute("INSERT INTO detail_transaksi (transaksi_id, produk_id, harga, profit) VALUES (?, ?, ?, ?)", 
            [$transaksi_id, $produk_id, $produk['harga'], $profit_produk_utama]);
		
        foreach ($bundling_details as $bundle_detail) {
            // Ambil data profit dari database produk bundling
            $bundle_produk = fetchRow("SELECT profit FROM produk WHERE id = ?", [$bundle_detail['produk_id']]);
            
            $bundle_profit_asli = ($bundle_produk['profit'] > 0) ? $bundle_produk['profit'] : $bundle_detail['harga'];
            
            execute("INSERT INTO detail_transaksi (transaksi_id, produk_id, harga, profit) VALUES (?, ?, ?, ?)", 
                [$transaksi_id, $bundle_detail['produk_id'], $bundle_detail['harga'], $bundle_profit_asli]);
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

        // Hapus session fbclid setelah dipakai (clean up)
        unset($_SESSION['fbclid_final']);

        redirect("invoice.php?id=$transaksi_id");
    }
}

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
                    <label class="form-label">Nomor WhatsApp *</label>
                    <input type="text" name="nomor_wa" id="nomor_wa" class="form-control" 
                           placeholder="08123456789" value="<?= clean(post('nomor_wa')) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="nama" id="nama" class="form-control" 
                           placeholder="Nama lengkap Anda" value="<?= clean(post('nama')) ?>" required>
                </div>

                <?php if (!empty($bundling)): ?>
                <h6 class="mb-3 mt-4">Produk Tambahan (Bundling)</h6>
                <div id="bundlingContainer">
                    <?php foreach ($bundling as $bundle): 
                        $harga_diskon = $bundle['harga'] - $bundle['diskon'];
                        $is_checked = false;
                    ?>
                    <div class="bundle-item <?= $is_checked ? 'selected' : '' ?>" data-bundle-id="<?= $bundle['id'] ?>">
                        <div class="form-check">
                            <input class="form-check-input bundle-checkbox" type="checkbox" 
                                   name="bundling_ids[]" value="<?= $bundle['id'] ?>" 
                                   id="bundle_<?= $bundle['id'] ?>"
                                   data-price="<?= $harga_diskon ?>" <?= $is_checked ? 'checked' : '' ?>>
                            <label class="form-check-label w-100" for="bundle_<?= $bundle['id'] ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= clean($bundle['nama']) ?></strong>
                                        <p class="mb-1 text-muted small"><?= nl2br($bundle['deskripsi']) ?></p>
                                    </div>
                                    <div class="text-end">
                                        <span class="price-original"><?= formatCurrency($bundle['harga']) ?></span><br>
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
// ... (JS Anda tetap sama — tidak perlu diubah)
function formatCurrencyJS(t){return"Rp "+t.toLocaleString("id-ID")}

function setupPhoneValidation() {
    const nomorWaInput = document.getElementById('nomor_wa');
    const namaInput = document.getElementById('nama');
    if (!nomorWaInput || !namaInput) return;
    nomorWaInput.addEventListener('blur', function() {
        let phone = this.value.trim();
        if (phone.length >= 5) {
            if (phone.startsWith('0')) {
                phone = '62' + phone.substring(1);
                this.value = phone;
            }
            fetch('api/get_customer.php?phone=' + encodeURIComponent(phone))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.customer) {
                        namaInput.value = data.customer.nama;
                        namaInput.dispatchEvent(new Event('change'));
                    }
                })
                .catch(error => console.log('Customer lookup failed'));
        }
    });
    nomorWaInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
}

function updateTotal() {
    let total = <?= $produk['harga'] ?>;
    const orderSummary = document.getElementById('orderSummary');
    if (!orderSummary) return;
    orderSummary.innerHTML = '';
    const mainItem = document.createElement('div');
    mainItem.className = 'summary-item d-flex justify-content-between mb-2';
    mainItem.innerHTML = `<span><?= clean($produk['nama']) ?></span><span><?= formatCurrency($produk['harga']) ?></span>`;
    orderSummary.appendChild(mainItem);
    document.querySelectorAll('.bundle-checkbox').forEach(checkbox => {
        if (checkbox.checked) {
            const bundleItemEl = document.createElement('div');
            bundleItemEl.className = 'summary-bundle-item d-flex justify-content-between mb-2';
            const price = parseFloat(checkbox.dataset.price);
            total += price;
            const bundleLabel = checkbox.closest('.bundle-item').querySelector('strong').textContent;
            bundleItemEl.innerHTML = `<span>${bundleLabel}</span><span>${formatCurrencyJS(price)}</span>`;
            orderSummary.appendChild(bundleItemEl);
        }
        const bundleItem = checkbox.closest('.bundle-item');
        if (bundleItem) bundleItem.classList.toggle('selected', checkbox.checked);
    });
    const hr = document.createElement('hr');
    orderSummary.appendChild(hr);
    const totalRow = document.createElement('div');
    totalRow.className = 'd-flex justify-content-between fw-bold';
    totalRow.innerHTML = `<span>Total</span><span id="totalAmount">${formatCurrencyJS(total)}</span>`;
    orderSummary.appendChild(totalRow);
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
    setupPhoneValidation();
    setupEventListeners();
    updateTotal();
});
</script>

</body>
</html>