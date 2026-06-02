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
<div class="main-content dashboard-wrapper flex-grow-1">
    
    <div class="dash-header flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="dash-title d-flex align-items-center gap-2">
                <i class="fas fa-hand-sparkles text-warning" style="animation: wave 2s infinite; transform-origin: bottom right;"></i> Ringkasan Bisnis
            </h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Selamat datang kembali, pantau performa tokomu hari ini.</div>
        </div>
        <div class="dash-date d-flex align-items-center gap-2 px-3 py-2 bg-white border rounded-pill shadow-sm">
            <div style="width: 8px; height: 8px; background: #10B981; border-radius: 50%; box-shadow: 0 0 0 3px #D1FAE5;"></div>
            <span class="fw-bold text-dark" style="font-size: 0.85rem;"><?= date('d F Y') ?></span>
        </div>
    </div>

    <style>
        @keyframes wave {
            0% { transform: rotate(0deg); }
            10% { transform: rotate(14deg); }
            20% { transform: rotate(-8deg); }
            30% { transform: rotate(14deg); }
            40% { transform: rotate(-4deg); }
            50% { transform: rotate(10deg); }
            60%, 100% { transform: rotate(0deg); }
        }
        
        /* Premium Action Cards */
        .shortcut-card {
            display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
            padding: 1.5rem 1rem; border-radius: 20px; background: #FFFFFF; border: 1px solid #E5E7EB;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1); text-decoration: none !important; gap: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); height: 100%;
        }
        .shortcut-icon {
            width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: transform 0.2s;
        }
        .shortcut-title { font-weight: 700; color: #111827; font-size: 0.85rem; margin: 0; line-height: 1.3; }
        
        @media (hover: hover) and (pointer: fine) {
            .shortcut-card:hover { transform: translateY(-4px); box-shadow: 0 12px 20px rgba(0,0,0,0.05); border-color: #D1D5DB; }
            .shortcut-card:hover .shortcut-icon { transform: scale(1.1); }
        }
    </style>

    <?php if ($transaksi_pending > 0): ?>
    <div class="alert alert-editorial mb-3 p-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3" style="border-left-color: var(--warning-color); background: #FFFBEB; border-radius: 16px;">
        <div class="d-flex align-items-center gap-3">
            <div style="width: 40px; height: 40px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(217,119,6,0.1);">
                <i class="fas fa-wallet text-warning fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark m-0">Menunggu Pembayaran</h6>
                <div class="text-muted" style="font-size: 0.8rem;">Ada <strong><?= $transaksi_pending ?> transaksi</strong> yang butuh konfirmasi.</div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>modules/transaksi/?status=pending" class="btn btn-warning text-dark fw-bold rounded-pill px-4 btn-sm" style="box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);">Tinjau Transaksi</a>
    </div>
    <?php endif; ?>

    <?php if ($followup_stats && $followup_stats['ready_to_send'] > 0): ?>
    <div class="alert alert-editorial mb-4 p-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3" style="border-left-color: var(--info-color); background: #EFF6FF; border-radius: 16px;">
        <div class="d-flex align-items-center gap-3">
            <div style="width: 40px; height: 40px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(59,130,246,0.1);">
                <i class="fas fa-paper-plane text-primary fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark m-0">Antrean Follow-up</h6>
                <div class="text-muted" style="font-size: 0.8rem;"><strong><?= $followup_stats['ready_to_send'] ?> pesan</strong> jadwal hari ini siap dikirim.</div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>modules/followup/processor.php?key=followup_2024_secure_key" target="_blank" class="btn btn-primary text-white fw-bold rounded-pill px-4 btn-sm" style="box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);">Eksekusi Cron</a>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <?php if ($followup_stats): ?>
        <div class="col-xl-3 col-sm-6">
            <a href="<?= BASE_URL ?>modules/followup/" class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-icon m-0" style="background: #FEF3C7; color: #D97706;"><i class="fas fa-clock"></i></div>
                    <span class="badge bg-warning text-dark" style="font-size: 0.65rem; padding: 4px 8px;">HARI INI</span>
                </div>
                <div>
                    <div class="stat-value text-dark"><?= $followup_stats['ready_to_send'] ?></div>
                    <div class="stat-label">Follow-up Antre</div>
                </div>
                <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center" style="font-size: 0.75rem; font-weight: 700;">
                    <span class="d-flex align-items-center gap-1"><div style="width:6px;height:6px;border-radius:50%;background:#F59E0B;"></div> <?= $followup_stats['pending'] ?> Pending</span>
                    <span class="d-flex align-items-center gap-1"><div style="width:6px;height:6px;border-radius:50%;background:#10B981;"></div> <?= $followup_stats['sent'] ?> Sukses</span>
                </div>
            </a>
        </div>
        <?php else: ?>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-icon m-0" style="background: #ECFDF5; color: #059669;"><i class="fas fa-wallet"></i></div>
                    <span class="badge bg-success" style="font-size: 0.65rem; padding: 4px 8px;">BULAN INI</span>
                </div>
                <div>
                    <div class="stat-value text-dark" style="font-size: 1.4rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= formatCurrency($pendapatan_bulan_ini) ?></div>
                    <div class="stat-label">Total Omset</div>
                </div>
                <div class="mt-3 pt-3 border-top text-success" style="font-size: 0.75rem; font-weight: 700;">
                    <i class="fas fa-check-circle me-1"></i> Transaksi Selesai
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-xl-3 col-sm-6">
            <a href="<?= BASE_URL ?>modules/transaksi/?date_from=<?= $tanggal_awal_bulan ?>&date_to=<?= $tanggal_akhir_bulan ?>" class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-icon m-0" style="background: #EFF6FF; color: #2563EB;"><i class="fas fa-shopping-cart"></i></div>
                    <span class="badge bg-primary" style="font-size: 0.65rem; padding: 4px 8px;">BULAN INI</span>
                </div>
                <div>
                    <div class="stat-value text-primary"><?= $transaksi_bulan_ini ?></div>
                    <div class="stat-label">Order Masuk</div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-sm-6">
            <a href="<?= BASE_URL ?>modules/pelanggan/" class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-icon m-0" style="background: #F3E8FF; color: #9333EA;"><i class="fas fa-users"></i></div>
                </div>
                <div>
                    <div class="stat-value" style="color: #9333EA;"><?= $total_pelanggan ?></div>
                    <div class="stat-label">Database Pelanggan</div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-sm-6">
            <a href="<?= BASE_URL ?>modules/produk/" class="stat-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-icon m-0" style="background: #F1F5F9; color: #475569;"><i class="fas fa-box-open"></i></div>
                </div>
                <div>
                    <div class="stat-value" style="color: #475569;"><?= $total_produk ?></div>
                    <div class="stat-label">Katalog Produk</div>
                </div>
            </a>
        </div>
    </div>

    <div class="mb-4">
        <h6 class="fw-bold text-dark mb-3" style="font-size: 0.9rem;"><i class="fas fa-bolt text-warning me-2"></i> Akses Cepat</h6>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <a href="<?= BASE_URL ?>modules/transaksi/create.php" class="shortcut-card">
                    <div class="shortcut-icon" style="background: #EFF6FF; color: #3B82F6;"><i class="fas fa-cash-register"></i></div>
                    <h3 class="shortcut-title">Kasir / Order<br>Baru</h3>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="<?= BASE_URL ?>modules/broadcast/" class="shortcut-card">
                    <div class="shortcut-icon" style="background: #ECFDF5; color: #10B981;"><i class="fas fa-bullhorn"></i></div>
                    <h3 class="shortcut-title">Broadcast WA<br>Promo</h3>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="<?= BASE_URL ?>modules/pelanggan/create.php" class="shortcut-card">
                    <div class="shortcut-icon" style="background: #F3E8FF; color: #9333EA;"><i class="fas fa-user-plus"></i></div>
                    <h3 class="shortcut-title">Tambah Data<br>Pelanggan</h3>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="<?= BASE_URL ?>modules/laporan/analitik.php" class="shortcut-card">
                    <div class="shortcut-icon" style="background: #FFFBEB; color: #D97706;"><i class="fas fa-chart-line"></i></div>
                    <h3 class="shortcut-title">Analitik &<br>Laporan</h3>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        
        <div class="col-xl-7">
            <div class="panel-editorial p-0 overflow-hidden h-100">
                <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <h3 class="panel-title m-0" style="font-size: 1rem;"><i class="fas fa-receipt text-primary me-2"></i> Order Terbaru</h3>
                    <a href="<?= BASE_URL ?>modules/transaksi/" class="btn btn-sm btn-light border fw-bold text-dark rounded-pill px-3">Lihat Semua</a>
                </div>
                
                <div class="p-0">
                    <?php if (empty($transaksi_terbaru)): ?>
                        <div class="text-center py-5">
                            <div style="width: 64px; height: 64px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                                <i class="fas fa-inbox text-muted fs-3"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Belum Ada Order</h6>
                            <p class="text-muted small m-0">Pecah telur pertama kamu hari ini!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table-editorial mb-0">
                                <tbody>
                                    <?php foreach ($transaksi_terbaru as $transaksi): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem;"><?= clean($transaksi['nama_pelanggan']) ?></div>
                                            <div class="text-muted d-flex align-items-center gap-2" style="font-size: 0.75rem;">
                                                <i class="far fa-clock"></i> <?= formatDate($transaksi['tanggal_transaksi'], 'd M Y, H:i') ?>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="fw-bold text-dark mb-2" style="font-size: 0.95rem;"><?= formatCurrency($transaksi['total_harga']) ?></div>
                                            <div class="d-inline-block">
                                                <?= statusBadge($transaksi['status']) ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="panel-editorial p-0 overflow-hidden h-100">
                <div class="p-4 border-bottom bg-white">
                    <h3 class="panel-title m-0" style="font-size: 1rem;"><i class="fas fa-fire text-danger me-2"></i> Terlaris Bulan Ini</h3>
                </div>
                
                <div class="p-0 bg-light">
                    <?php if (empty($produk_terlaris)): ?>
                        <div class="text-center py-5">
                            <div style="width: 64px; height: 64px; background: #FEF2F2; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                                <i class="fas fa-box-open text-danger opacity-50 fs-3"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">Belum Ada Data</h6>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table-editorial mb-0">
                                <tbody>
                                    <?php foreach ($produk_terlaris as $index => $produk): 
                                        $medalColor = '#9CA3AF'; // Default gray
                                        if($index == 0) $medalColor = '#F59E0B'; // Gold
                                        else if($index == 1) $medalColor = '#94A3B8'; // Silver
                                        else if($index == 2) $medalColor = '#B45309'; // Bronze
                                    ?>
                                    <tr>
                                        <td width="50" class="text-center">
                                            <div class="fw-bold d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: white; border-radius: 8px; border: 2px solid <?= $medalColor ?>; color: <?= $medalColor ?>; font-size: 0.8rem;">
                                                <?= $index + 1 ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark" style="font-size: 0.9rem; line-height: 1.3;">
                                                <?= clean($produk['nama']) ?>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <span class="badge-clean bg-white border" style="color: #059669; border-color: #A7F3D0 !important; box-shadow: 0 2px 4px rgba(16,185,129,0.05);">
                                                <i class="fas fa-arrow-trend-up me-1"></i> <?= $produk['jumlah_terjual'] ?>x
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>