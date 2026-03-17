<?php
/**
 * Checkout Page for FREE PRODUCTS (Lead Magnet)
 * Status langsung 'selesai' & Kirim Pesan Akses
 */
ini_set('session.cookie_domain', '.edumuslim.my.id');
session_set_cookie_params(0, '/', '.edumuslim.my.id');
session_start();

require_once 'includes/init.php';
require_once 'includes/whatsapp_helper.php';

// Enable browser caching
header("Cache-Control: public, max-age=3600");
header("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));

// === 1. SIMPAN fbclid (Sama seperti checkout bayar) ===
if (isset($_GET['fbclid']) && !empty($_GET['fbclid'])) {
    $fbclid = trim($_GET['fbclid']);
    if (strlen($fbclid) >= 20 && preg_match('/^[a-zA-Z0-9_-]+$/', $fbclid)) {
        setcookie('_fbc', $fbclid, [
            'expires' => time() + 30*24*60*60,
            'path' => '/',
            'domain' => '.edumuslim.my.id',
            'secure' => true,
            'httponly' => false,
            'samesite' => 'None'
        ]);
        $_SESSION['fbclid_final'] = $fbclid;
    }
}

// Function CAPI Sederhana
function sendMetaCAPILead($access_token, $pixel_id, $user_data, $custom_data, $event_id) {
    $capi_url = 'https://graph.facebook.com/v20.0/' . $pixel_id . '/events';
    $data = [
        'data' => [[
            'event_name' => 'Lead', // Event khusus Lead Magnet
            'event_time' => time(),
            'event_id' => $event_id,
            'action_source' => 'website',
            'user_data' => $user_data,
            'custom_data' => $custom_data
        ]],
        'access_token' => $access_token
    ];
    
    $ch = curl_init($capi_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_exec($ch);
    curl_close($ch);
}

// === 2. AMBIL DATA PRODUK ===
$produk_id = (int) get('id');
if (!$produk_id) redirect('index.php');

$produk = fetchRow("SELECT * FROM produk WHERE id = ?", [$produk_id]);
if (!$produk) {
    setMessage('Produk tidak ditemukan', 'error');
    redirect('index.php');
}

// Pastikan harga 0 (Safety Check)
if ($produk['harga'] > 0) {
    // Jika ternyata berbayar, lempar ke checkout biasa
    redirect("checkout.php?id=$produk_id");
}

// === 3. PROSES SUBMIT ===
if (isPost()) {
    $nama = trim(post('nama'));
    $nomor_wa = trim(post('nomor_wa'));
    
    // Ambil tracking data
    $fbc = $_COOKIE['_fbc'] ?? $_SESSION['fbclid_final'] ?? null;
    $fbp = $_COOKIE['_fbp'] ?? null;

    if (empty($nama) || empty($nomor_wa)) {
        setMessage('Nama dan nomor WhatsApp wajib diisi', 'error');
    } elseif (strlen($nomor_wa) < 5 || !is_numeric($nomor_wa)) {
        setMessage('Nomor WhatsApp tidak valid', 'error');
    } else {
        // Format WA
        if (substr($nomor_wa, 0, 1) === '0') {
            $nomor_wa = '62' . substr($nomor_wa, 1);
        }

        // A. Simpan/Update Pelanggan
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

        // B. Simpan Transaksi (LANGSUNG SELESAI)
        execute("INSERT INTO transaksi (pelanggan_id, total_harga, status, is_invoice_sent, ip_pelanggan, user_agent_pelanggan, fbc, fbp) 
                 VALUES (?, 0, 'selesai', 1, ?, ?, ?, ?)", 
                [$customer_id, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $fbc, $fbp]);
        
        $transaksi_id = db()->insert_id;

        // C. Simpan Detail Transaksi
        execute("INSERT INTO detail_transaksi (transaksi_id, produk_id, harga, profit) VALUES (?, ?, 0, 0)", 
                [$transaksi_id, $produk_id]);

        // D. KIRIM PESAN AKSES PRODUK
        try {
            $template_row = fetchRow("SELECT isi_pesan FROM template_pesan_produk WHERE produk_id = ? AND jenis_pesan = 'akses_produk'", [$produk_id]);
            $template_pesan = $template_row['isi_pesan'] ?? "Halo *{nama}*,\n\nTerima kasih sudah mendaftar!\nBerikut adalah akses untuk *{produk}*:\n\nLink: {link_akses}\n\nSilakan simpan link ini. Semoga bermanfaat!";

            $search = ['{nama}', '[nama]', '{produk}', '[produk]', '{link_akses}', '[link_akses]', '{nowa}'];
            $replace = [
                $nama, $nama, 
                $produk['nama'], $produk['nama'], 
                $produk['link_akses'], $produk['link_akses'], 
                $nomor_wa
            ];
            
            $pesan_final = str_replace($search, $replace, $template_pesan);

            $account_wa = $produk['onesender_account'] ?? 'default';
            sendWhatsAppText($nomor_wa, $pesan_final, $account_wa);

        } catch (Exception $e) {
            error_log("Gagal kirim akses lead magnet #$transaksi_id: " . $e->getMessage());
        }

        // E. KIRIM CAPI EVENT 'LEAD'
        if (!empty($produk['conversion_api_token']) && !empty($produk['meta_pixel_id'])) {
            $user_data = [
                'ph' => hash('sha256', $nomor_wa),
                'client_ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'client_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
            if ($fbc) $user_data['fbc'] = $fbc;
            if ($fbp) $user_data['fbp'] = $fbp;

            sendMetaCAPILead(
                $produk['conversion_api_token'],
                $produk['meta_pixel_id'],
                $user_data,
                ['content_name' => $produk['nama'], 'content_category' => 'Lead Magnet'],
                'lead_' . $transaksi_id
            );
        }

        unset($_SESSION['fbclid_final']);
        redirect("invoice.php?id=$transaksi_id");
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Gratis - <?= clean($produk['nama']) ?></title>
	
	<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
	
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Nunito', sans-serif; /* Menggunakan Nunito */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.02);
            overflow: hidden;
			max-width: 500px; /* Maksimal lebar 700px */
        }
        .card-content {
            padding: 3rem;
        }
        .product-badge {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
            letter-spacing: 0.5px;
        }
        .product-title {
            font-weight: 800;
            color: #212529;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        .form-label-custom {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .form-control-lg {
            border-radius: 12px;
            border: 1px solid #dee2e6;
            padding: 14px 20px;
            font-size: 1rem;
            background-color: #fff;
            transition: all 0.2s;
        }
        .form-control-lg:focus {
            border-color: #198754;
            box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.1);
        }
        .btn-cta {
            background: #198754; /* Warna hijau yang nyaman */
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 1.1rem;
            font-weight: 700;
            width: 100%;
            transition: all 0.2s;
            margin-top: 1rem;
        }
        .btn-cta:hover {
            background: #157347;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(25, 135, 84, 0.15);
            color: white;
        }
        .secure-note {
            text-align: center;
            margin-top: 1.5rem;
            color: #adb5bd;
            font-size: 0.85rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .card-content { padding: 2rem 1.5rem; }
            .product-title { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="main-card">
        <div class="card-content">
            <div class="text-center mb-4">
                <div class="product-badge">
                    <i class="fas fa-gift me-2"></i>AKSES GRATIS
                </div>
                <h1 class="product-title"><?= clean($produk['nama']) ?></h1>
                <p class="text-muted">Isi data di bawah untuk mendapatkan akses instan.</p>
            </div>

            <?php $msg = getMessage(); if ($msg): ?>
                <div class="alert alert-<?= $msg[1] === 'error' ? 'danger' : $msg[1] ?> rounded-3 border-0 mb-4">
                    <i class="fas fa-info-circle me-2"></i><?= clean($msg[0]) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="leadForm">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label-custom">Nama Lengkap</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-4 ps-3">
                                <i class="far fa-user text-muted"></i>
                            </span>
                            <input type="text" name="nama" class="form-control form-control-lg border-start-0" placeholder="Masukkan nama Anda" required>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label-custom">Nomor WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-4 ps-3">
                                <i class="fab fa-whatsapp text-muted" style="font-size: 1.2rem;"></i>
                            </span>
                            <input type="tel" name="nomor_wa" id="nomor_wa" class="form-control form-control-lg border-start-0" placeholder="0812xxxx" required>
                        </div>
                        <div class="form-text text-success mt-2 ms-1">
                            <small><i class="fas fa-check-circle me-1"></i>Link akses akan dikirim otomatis ke WA ini.</small>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-cta" id="submitBtn">
                            DAPATKAN AKSES SEKARANG <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>

            <div class="secure-note">
                <i class="fas fa-lock me-1"></i> Data Anda 100% aman dan terenkripsi.
            </div>
        </div>
    </div>

<?php if(!empty($produk['meta_pixel_id'])): ?>
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?= $produk['meta_pixel_id'] ?>');
fbq('track', 'ViewContent', {content_name: '<?= addslashes($produk['nama']) ?>', content_ids: ['<?= $produk_id ?>'], content_type: 'product', value: 0, currency: 'IDR'});
</script>
<?php endif; ?>

<script>
// Auto format WA
document.getElementById('nomor_wa').addEventListener('blur', function() {
    let phone = this.value.trim();
    if (phone.length >= 5 && phone.startsWith('0')) {
        this.value = '62' + phone.substring(1);
    }
});
// Loading Button
document.getElementById('leadForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i>Memproses...';
    btn.style.opacity = '0.8';
    btn.disabled = true;
});
</script>

</body>
</html>