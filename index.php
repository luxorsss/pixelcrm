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
<div class="main-content">
    <!-- Top Header -->
    <div class="bg-white border-bottom p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">Dashboard</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </nav>
            </div>
            <div class="text-muted">
                <i class="fas fa-calendar me-1"></i><?= date('d F Y') ?>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="container-fluid px-4">
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="display-4 text-primary mb-2"><?= $total_produk ?></div>
                        <h6 class="text-muted mb-3">Total Produk</h6>
                        <a href="<?= BASE_URL ?>modules/produk/" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-box me-1"></i>Kelola
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="display-4 text-success mb-2"><?= $total_pelanggan ?></div>
                        <h6 class="text-muted mb-3">Total Pelanggan</h6>
                        <a href="<?= BASE_URL ?>modules/pelanggan/" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-users me-1"></i>Kelola
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="display-4 text-info mb-2"><?= $transaksi_bulan_ini ?></div>
                        <h6 class="text-muted mb-3">Transaksi Bulan Ini</h6>
                        <a href="<?= BASE_URL ?>modules/transaksi/?date_from=<?= $tanggal_awal_bulan ?>&date_to=<?= $tanggal_akhir_bulan ?>&search=" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-shopping-cart me-1"></i>Kelola
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- 🆕 Follow-up Stats Card -->
            <?php if ($followup_stats): ?>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="display-4 text-warning mb-2"><?= $followup_stats['ready_to_send'] ?></div>
                        <h6 class="text-muted mb-3">Follow-up Siap Kirim</h6>
                        <div class="small mb-2">
                            <span class="badge bg-warning me-1"><?= $followup_stats['pending'] ?></span> Pending
                            <span class="badge bg-success"><?= $followup_stats['sent'] ?></span> Terkirim
                        </div>
                        <a href="<?= BASE_URL ?>modules/followup/" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-clock me-1"></i>Kelola
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="h4 text-success mb-2"><?= formatCurrency($pendapatan_bulan_ini) ?></div>
                        <h6 class="text-muted mb-3">Pendapatan Bulan Ini</h6>
                        <small class="text-success">
                            <i class="fas fa-check-circle me-1"></i>Transaksi Selesai
                        </small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Alert untuk transaksi pending -->
        <?php if ($transaksi_pending > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Ada <strong><?= $transaksi_pending ?></strong> transaksi yang masih pending. 
            <a href="<?= BASE_URL ?>modules/transaksi/?status=pending" class="alert-link">Lihat sekarang</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- 🆕 Alert untuk follow-up yang siap kirim -->
        <?php if ($followup_stats && $followup_stats['ready_to_send'] > 0): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-clock me-2"></i>
            Ada <strong><?= $followup_stats['ready_to_send'] ?></strong> follow-up message yang siap dikirim. 
            <a href="<?= BASE_URL ?>modules/followup/processor.php?key=followup_2024_secure_key" class="alert-link" target="_blank">Proses sekarang</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Content Row -->
        <div class="row">
            <!-- Produk Terlaris -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fas fa-trophy text-warning me-2"></i>Produk Terlaris Bulan Ini</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($produk_terlaris)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">Belum ada data penjualan bulan ini</p>
                                <a href="<?= BASE_URL ?>modules/transaksi/create.php" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-plus me-1"></i>Buat Transaksi Pertama
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($produk_terlaris as $index => $produk): ?>
                            <div class="d-flex justify-content-between align-items-center py-2 <?= $index < count($produk_terlaris) - 1 ? 'border-bottom' : '' ?>">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary me-3"><?= $index + 1 ?></span>
                                    <strong><?= clean($produk['nama']) ?></strong>
                                </div>
                                <span class="badge bg-success"><?= $produk['jumlah_terjual'] ?> terjual</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Transaksi Terbaru -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fas fa-clock text-primary me-2"></i>Transaksi Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($transaksi_terbaru)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">Belum ada transaksi</p>
                                <a href="<?= BASE_URL ?>modules/transaksi/create.php" class="btn btn-primary btn-sm mt-2">
                                    <i class="fas fa-plus me-1"></i>Buat Transaksi Pertama
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($transaksi_terbaru as $index => $transaksi): ?>
                            <div class="py-2 <?= $index < count($transaksi_terbaru) - 1 ? 'border-bottom' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= clean($transaksi['nama_pelanggan']) ?></h6>
                                        <p class="mb-1 fw-bold text-primary"><?= formatCurrency($transaksi['total_harga']) ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= formatDate($transaksi['tanggal_transaksi'], 'd/m/Y H:i') ?>
                                        </small>
                                        <?php if ($transaksi['status'] === 'selesai' && !empty($transaksi['waktu_selesai'])): ?>
                                            <br><small class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Selesai: <?= formatDate($transaksi['waktu_selesai'], 'd/m/Y H:i') ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?= statusBadge($transaksi['status']) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="<?= BASE_URL ?>modules/transaksi/" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>Lihat Semua Transaksi
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fas fa-rocket text-success me-2"></i>Aksi Cepat</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-lg-3 col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/produk/create.php" class="btn btn-outline-primary w-100 py-3 text-decoration-none">
                                    <i class="fas fa-plus fa-2x mb-2 d-block"></i>
                                    <div>Tambah Produk</div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/pelanggan/create.php" class="btn btn-outline-success w-100 py-3 text-decoration-none">
                                    <i class="fas fa-user-plus fa-2x mb-2 d-block"></i>
                                    <div>Tambah Pelanggan</div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/transaksi/create.php" class="btn btn-outline-info w-100 py-3 text-decoration-none">
                                    <i class="fas fa-shopping-cart fa-2x mb-2 d-block"></i>
                                    <div>Buat Transaksi</div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <a href="<?= BASE_URL ?>modules/laporan/analitik.php" class="btn btn-outline-warning w-100 py-3 text-decoration-none">
                                    <i class="fas fa-chart-line fa-2x mb-2 d-block"></i>
                                    <div>Lihat Laporan</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>