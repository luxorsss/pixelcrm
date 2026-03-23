<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Panggil Helper WhatsApp PixelCRM
require_once __DIR__ . '/../../includes/whatsapp_helper.php';

// Mencegah PHP Timeout jika mengirim banyak pesan (batch besar)
set_time_limit(0);

// === MODE UJI ===
$MODE_UJI = true; 
$NOMOR_UJI = '6285780146447'; // Ganti dengan nomor Anda
$NAMA_UJI = 'Admin Uji';
// ================

if (!isPost()) die('Akses ditolak.');

$tipe_pesan = post('tipe_pesan', 'text');
$pesan_template = trim($tipe_pesan === 'text' ? post('pesan') : post('caption'));
$segments = !empty($_POST['segments']) ? explode(',', $_POST['segments']) : [];
$include_produk = json_decode($_POST['include_produk_json'] ?? '[]', true);
$exclude_produk = json_decode($_POST['exclude_produk_json'] ?? '[]', true);
$wa_account_id = (int)post('wa_account_id');
$link_gambar = trim(post('link_gambar'));

// 1. Ambil Nama Akun WhatsApp (Kebutuhan Helper PixelCRM)
$wa_config = fetchRow("SELECT account_name FROM onesender_config WHERE id = ?", [$wa_account_id]);
if (!$wa_config) die('Akun WA tidak ditemukan.');
$account_name = $wa_config['account_name'];

// 2. Query Pencarian Penerima (Logic Segmentasi)
$where = "t.status = 'selesai'";
$params = [];

if (post('tanggal_mulai') && post('tanggal_akhir')) {
    $where .= " AND t.tanggal_transaksi BETWEEN ? AND ?";
    $params[] = post('tanggal_mulai') . ' 00:00:00';
    $params[] = post('tanggal_akhir') . ' 23:59:59';
}

$sql = "SELECT p.id, p.nama, p.nomor_wa, p.rfm_segment FROM pelanggan p JOIN transaksi t ON p.id = t.pelanggan_id JOIN detail_transaksi dt ON t.id = dt.transaksi_id WHERE $where";

if (!empty($include_produk)) {
    $sql .= " AND dt.produk_id IN (" . str_repeat('?,', count($include_produk) - 1) . "?)";
    $params = array_merge($params, $include_produk);
}

$sql .= " GROUP BY p.id, p.nama, p.nomor_wa, p.rfm_segment";

if (!empty($exclude_produk)) {
    $sql .= " HAVING p.id NOT IN (SELECT DISTINCT p2.id FROM pelanggan p2 JOIN transaksi t2 ON p2.id = t2.pelanggan_id AND t2.status = 'selesai' JOIN detail_transaksi dt2 ON t2.id = dt2.transaksi_id WHERE dt2.produk_id IN (" . str_repeat('?,', count($exclude_produk) - 1) . "?))";
    $params = array_merge($params, $exclude_produk);
}

// Tambahkan ORDER BY setelah HAVING
$sql .= " ORDER BY p.id ASC";

$results = fetchAll($sql, $params) ?: [];
$penerima = [];

// 3. Filter Manual Segmentasi
// 3. Filter Manual Segmentasi
foreach ($results as $row) {
    if (!empty($segments) && !in_array($row['rfm_segment'], $segments)) continue;
    
    // GANTI safeHtml MENJADI htmlspecialchars DI BAWAH INI:
    $penerima[] = [
        'nama' => htmlspecialchars($row['nama'] ?? '', ENT_QUOTES, 'UTF-8'), 
        'nomor_wa' => normalizeWa($row['nomor_wa'])
    ];
}

// 4. Potong Sesuai Batch
$urut_awal = max(1, (int)post('urut_awal', 1));
$urut_akhir = max($urut_awal, (int)post('urut_akhir', 100));
$penerima = array_slice($penerima, $urut_awal - 1, $urut_akhir - $urut_awal + 1);

// PINDAHKAN PENGECEKAN MODE UJI KE ATAS SINI
// Jika Mode Uji Aktif, override array penerima jadi 1 orang saja (Nomor Anda)
if ($MODE_UJI) {
    $penerima = [
        ['nama' => $NAMA_UJI, 'nomor_wa' => normalizeWa($NOMOR_UJI)]
    ];
}

// Pengecekan kosong diletakkan paling bawah
if (empty($penerima)) {
    die('<div style="text-align:center; padding:50px; font-family:sans-serif;"><h3>❌ Tidak ada penerima valid di batch ini.</h3><br><a href="index.php" style="padding:10px 20px; background:#0d6efd; color:white; text-decoration:none; border-radius:5px;">Kembali</a></div>');
}

// === 5. PROSES PENGIRIMAN MENGGUNAKAN HELPER PIXELCRM ===
$berhasil = 0;
$gagal = 0;
$log_error = [];

foreach ($penerima as $p) {
    $nomor = $p['nomor_wa'];
    if (strlen($nomor) < 10) continue;
    
    // Replace Variabel [nama]
    $pesan_final = str_replace('[nama]', $p['nama'], $pesan_template);
    
    // Eksekusi fungsi dari whatsapp_helper.php
    if ($tipe_pesan === 'text') {
        $res = sendWhatsAppText($nomor, $pesan_final, $account_name);
    } else {
        $res = sendWhatsAppImage($nomor, $link_gambar, $pesan_final, $account_name);
    }
    
    // Pencatatan Stat
    if ($res['success']) {
        $berhasil++;
    } else {
        $gagal++;
        $log_error[] = "Nomor $nomor: " . ($res['error'] ?? 'Unknown Error');
    }
    
    // Jeda 300 milidetik (0.3 detik) per pesan agar WA aman & server gak sesak
    usleep(300000); 
}

// === TAMPILAN HALAMAN HASIL ===
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid px-4 py-4">
        <div class="card border-0 shadow-sm mx-auto" style="max-width: 600px;">
            <div class="card-body p-4 text-center">
                <h4 class="mb-4">Hasil Broadcast</h4>
                
                <?php if ($MODE_UJI): ?>
                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i><strong>MODE UJI AKTIF:</strong><br>
                        Pesan hanya dikirim 1x ke nomor tester (<?= $NOMOR_UJI ?>).
                    </div>
                <?php endif; ?>
                
                <div class="row text-center mb-4">
                    <div class="col-6">
                        <div class="p-3 bg-success bg-opacity-10 rounded">
                            <h2 class="text-success mb-0"><?= $berhasil ?></h2>
                            <small class="text-success fw-bold">TERKIRIM</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-danger bg-opacity-10 rounded">
                            <h2 class="text-danger mb-0"><?= $gagal ?></h2>
                            <small class="text-danger fw-bold">GAGAL</small>
                        </div>
                    </div>
                </div>

                <?php if (!empty($log_error)): ?>
                    <div class="alert alert-danger text-start" style="max-height: 150px; overflow-y: auto; font-size: 13px;">
                        <strong>Log Gagal:</strong><br>
                        <?= implode('<br>', $log_error) ?>
                    </div>
                <?php endif; ?>
                
                <p class="text-muted small mb-4">
                    *Semua pesan yang terkirim telah dicatat di <a href="<?= BASE_URL ?>modules/followup/logs.php" class="text-decoration-none">Log WhatsApp</a>.
                </p>

                <a href="index.php" class="btn btn-primary px-4 py-2">
                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Menu Broadcast
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>