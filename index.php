<?php
$page_title = "Dashboard";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Require authentication
requireAuth();

// Generate tanggal awal dan akhir bulan ini
$tanggal_awal_bulan = date('Y-m-01'); // Tanggal 1 bulan ini
$tanggal_akhir_bulan = date('Y-m-t'); // Tanggal terakhir bulan ini

// Ambil statistik dashboard menggunakan simple functions
try {
    // Total produk
    $total_produk = fetchRow("SELECT COUNT(*) as total FROM produk")['total'] ?? 0;
    
    // Total pelanggan
    $total_pelanggan = fetchRow("SELECT COUNT(*) as total FROM pelanggan")['total'] ?? 0;
    
    // Total transaksi bulan ini
    $transaksi_bulan_ini = fetchRow("
        SELECT COUNT(*) as total FROM transaksi 
        WHERE MONTH(tanggal_transaksi) = MONTH(NOW()) 
        AND YEAR(tanggal_transaksi) = YEAR(NOW())
    ")['total'] ?? 0;
    
    // Total pendapatan bulan ini
    $pendapatan_bulan_ini = fetchRow("
        SELECT COALESCE(SUM(total_harga), 0) as total FROM transaksi 
        WHERE status = 'selesai' 
        AND MONTH(waktu_selesai) = MONTH(NOW()) 
        AND YEAR(waktu_selesai) = YEAR(NOW())
    ")['total'] ?? 0;
    
    // Transaksi pending
    $transaksi_pending = fetchRow("SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending'")['total'] ?? 0;
    
    // 🆕 Follow-up statistics
    $followup_stats = null;
    if (file_exists(__DIR__ . '/modules/followup/trigger_helper.php')) {
        require_once __DIR__ . '/modules/followup/trigger_helper.php';
        $followup_stats = getFollowupDashboardStats();
    }
    
    // Produk terlaris bulan ini
    $produk_terlaris = fetchAll("
        SELECT p.nama, COUNT(dt.produk_id) as jumlah_terjual 
        FROM produk p 
        JOIN detail_transaksi dt ON p.id = dt.produk_id 
        JOIN transaksi t ON dt.transaksi_id = t.id 
        WHERE t.status = 'selesai' 
        AND MONTH(t.tanggal_transaksi) = MONTH(NOW()) 
        AND YEAR(t.tanggal_transaksi) = YEAR(NOW()) 
        GROUP BY p.id, p.nama 
        ORDER BY jumlah_terjual DESC 
        LIMIT 5
    ");
    
    // Transaksi terbaru
    $transaksi_terbaru = fetchAll("
        SELECT t.id, p.nama as nama_pelanggan, t.total_harga, t.status, t.tanggal_transaksi, t.waktu_selesai
        FROM transaksi t 
        JOIN pelanggan p ON t.pelanggan_id = p.id 
        ORDER BY t.tanggal_transaksi DESC 
        LIMIT 5
    ");
    
} catch (Exception $e) {
    // Set default values jika ada error
    $total_produk = $total_pelanggan = $transaksi_bulan_ini = $pendapatan_bulan_ini = $transaksi_pending = 0;
    $produk_terlaris = $transaksi_terbaru = [];
    $followup_stats = null;
    setMessage('Error loading dashboard data: ' . $e->getMessage(), 'error');
}
?>

<!-- Main Content -->
<div class="main-content dashboard-wrapper">
    <div class="dash-header flex-column flex-md-row align-items-start align-items-md-center gap-3">
        <div>
            <h1 class="dash-title">Ringkasan Bisnis</h1>
            <div class="text-muted" style="font-weight: 500; font-size: 0.95rem;">Selamat datang kembali di Pixel CRM.</div>
        </div>
        <div class="dash-date">
            <i class="fas fa-calendar-day me-2 text-muted"></i><?= date('d F Y') ?>
        </div>
    </div>

    <?php if ($transaksi_pending > 0): ?>
    <div class="alert alert-editorial alert-dismissible fade show mb-4 d-flex align-items-center justify-content-between" role="alert">
        <div>
            <i class="fas fa-exclamation-circle text-warning me-2"></i>
            Terdapat <strong class="text-dark"><?= $transaksi_pending ?> transaksi</strong> yang menunggu konfirmasi pembayaran.
        </div>
        <a href="<?= BASE_URL ?>modules/transaksi/?status=pending" class="btn btn-sm btn-dark rounded-pill px-3 fw-bold">Tinjau</a>
    </div>
    <?php endif; ?>

    <?php if ($followup_stats && $followup_stats['ready_to_send'] > 0): ?>
    <div class="alert alert-editorial alert-dismissible fade show mb-4 d-flex align-items-center justify-content-between" style="border-left-color: #3B82F6;" role="alert">
        <div>
            <i class="fas fa-paper-plane text-primary me-2"></i>
            <strong><?= $followup_stats['ready_to_send'] ?> pesan follow-up</strong> siap untuk dikirimkan hari ini.
        </div>
        <a href="<?= BASE_URL ?>modules/followup/processor.php?key=followup_2024_secure_key" target="_blank" class="btn btn-sm btn-dark rounded-pill px-3 fw-bold">Kirim Sekarang</a>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <?php if ($followup_stats): ?>
        <div class="col-xl-3 col-sm-6">
            <a href="<?= BASE_URL ?>modules/followup/" class="stat-card">
                <div>
                    <div class="stat-icon" style="background: #FEF3C7; color: #D97706;"><i class="fas fa-clock"></i></div>
                    <div class="stat-value text-dark"><?= $followup_stats['ready_to_send'] ?></div>
                    <div class="stat-label">Follow-up Antre</div>
                </div>
                <div class="mt-3 pt-3 border-top d-flex gap-2" style="font-size: 0.75rem; font-weight: 700;">
                    <span style="color: #6B7280;"><span class="text-warning"><?= $followup_stats['pending'] ?></span> Pending</span>
                    <span style="color: #6B7280;">•</span>
                    <span style="color: #6B7280;"><span class="text-success"><?= $followup_stats['sent'] ?></span> Terkirim</span>
                </div>
            </a>
        </div>
        <?php else: ?>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div>
                    <div class="stat-icon" style="background: #ECFDF5; color: #059669;"><i class="fas fa-wallet"></i></div>
                    <div class="stat-value text-dark" style="font-size: 1.5rem;"><?= formatCurrency($pendapatan_bulan_ini) ?></div>
                    <div class="stat-label">Pendapatan Bulan Ini</div>
                </div>
                <div class="mt-3 pt-3 border-top" style="font-size: 0.8rem; font-weight: 600; color: #059669;">
                    <i class="fas fa-arrow-trend-up me-1"></i> Transaksi Selesai
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-xl-3 col-sm-6">
            <a href="<?= BASE_URL ?>modules/transaksi/?date_from=<?= $tanggal_awal_bulan ?>&date_to=<?= $tanggal_akhir_bulan ?>" class="stat-card">
                <div>
                    <div class="stat-icon" style="background: #EFF6FF; color: #2563EB;"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-value" style="color: #2563EB;"><?= $transaksi_bulan_ini ?></div>
                    <div class="stat-label">Transaksi Bulan Ini</div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-sm-6">
            <a href="<?= BASE_URL ?>modules/pelanggan/" class="stat-card">
                <div>
                    <div class="stat-icon" style="background: #F3E8FF; color: #9333EA;"><i class="fas fa-users"></i></div>
                    <div class="stat-value" style="color: #9333EA;"><?= $total_pelanggan ?></div>
                    <div class="stat-label">Total Pelanggan</div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-sm-6">
            <a href="<?= BASE_URL ?>modules/produk/" class="stat-card">
                <div>
                    <div class="stat-icon" style="background: #F1F5F9; color: #475569;"><i class="fas fa-box"></i></div>
                    <div class="stat-value" style="color: #475569;"><?= $total_produk ?></div>
                    <div class="stat-label">Total Produk Aktif</div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-sm-6">
            <a href="<?= BASE_URL ?>modules/transaksi/create.php" class="action-btn">
                <i class="fas fa-cart-plus text-primary fs-5"></i> Kasir / Transaksi Baru
            </a>
        </div>
        <div class="col-lg-3 col-sm-6">
            <a href="<?= BASE_URL ?>modules/produk/create.php" class="action-btn">
                <i class="fas fa-box-open text-success fs-5"></i> Tambah Produk Baru
            </a>
        </div>
        <div class="col-lg-3 col-sm-6">
            <a href="<?= BASE_URL ?>modules/pelanggan/create.php" class="action-btn">
                <i class="fas fa-user-plus text-info fs-5"></i> Database Pelanggan
            </a>
        </div>
        <div class="col-lg-3 col-sm-6">
            <a href="<?= BASE_URL ?>modules/laporan/analitik.php" class="action-btn">
                <i class="fas fa-chart-pie text-warning fs-5"></i> Analisis & Laporan
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="list-container h-100">
                <h2 class="list-header"><i class="fas fa-bolt text-primary"></i> Transaksi Terakhir</h2>
                
                <?php if (empty($transaksi_terbaru)): ?>
                    <div class="text-center py-5">
                        <div class="text-muted mb-2"><i class="fas fa-inbox fs-2"></i></div>
                        <div class="fw-bold text-dark">Belum ada transaksi</div>
                        <p class="text-muted small">Pecah telur pertama kamu hari ini!</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column">
                        <?php foreach ($transaksi_terbaru as $transaksi): ?>
                        <div class="row-item">
                            <div class="d-flex flex-column gap-1">
                                <span class="fw-bold text-dark" style="font-size: 0.95rem;"><?= clean($transaksi['nama_pelanggan']) ?></span>
                                <span class="text-muted" style="font-size: 0.8rem; font-weight: 500;">
                                    <?= formatDate($transaksi['tanggal_transaksi'], 'd/m/Y H:i') ?>
                                </span>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                <span class="fw-bold text-dark"><?= formatCurrency($transaksi['total_harga']) ?></span>
                                <div style="transform: scale(0.85); transform-origin: right center;">
                                    <?= statusBadge($transaksi['status']) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= BASE_URL ?>modules/transaksi/" class="btn btn-light text-dark fw-bold w-100 mt-4" style="border-radius: 12px;">Lihat Semua Transaksi</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="list-container h-100">
                <h2 class="list-header"><i class="fas fa-fire text-danger"></i> Terlaris Bulan Ini</h2>
                
                <?php if (empty($produk_terlaris)): ?>
                    <div class="text-center py-5">
                        <div class="text-muted mb-2"><i class="fas fa-box-open fs-2"></i></div>
                        <div class="fw-bold text-dark">Belum ada data</div>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column">
                        <?php foreach ($produk_terlaris as $index => $produk): ?>
                        <div class="row-item">
                            <div class="d-flex align-items-center gap-3">
                                <div class="fw-bold" style="color: #9CA3AF; width: 1.5rem; text-align: center;">
                                    <?= $index + 1 ?>
                                </div>
                                <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                                    <?= clean($produk['nama']) ?>
                                </div>
                            </div>
                            <span class="badge-soft" style="background: #ECFDF5; color: #059669;">
                                <?= $produk['jumlah_terjual'] ?> terjual
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>