<?php
$page_title = "Analytics";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/functions.php';

// Ambil bulan dan tahun dari URL
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('n');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// Validasi input
if ($bulan < 1 || $bulan > 12) $bulan = date('n');
if ($tahun < 2020 || $tahun > date('Y') + 1) $tahun = date('Y');

$laporan = new LaporanManager();

// Ambil data analytics
try {
    $overview = $laporan->getOverviewBulanan($bulan, $tahun);
    $penjualan_harian = $laporan->getPenjualanHarian($bulan, $tahun);
    $performa_produk = $laporan->getPerformaProduk($bulan, $tahun, 5);
    $top_customers = $laporan->getTopCustomers($bulan, $tahun, 5);
    $trend_pendapatan = $laporan->getTrendPendapatan($bulan, $tahun);
	$waktu_penyelesaian = $laporan->getWaktuPenyelesaian($bulan, $tahun);
} catch (Exception $e) {
    setMessage('Error loading analytics: ' . $e->getMessage(), 'error');
    $overview = ['total_transaksi' => 0, 'total_pelanggan' => 0, 'total_pendapatan' => 0,
                'transaksi_selesai' => 0, 'transaksi_pending' => 0, 'rata_rata_transaksi' => 0];
    $penjualan_harian = [];
    $performa_produk = [];
    $top_customers = [];
    $trend_pendapatan = [];
}

$bulan_nama = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<div class="main-content">
    <!-- Header -->
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Analytics</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <span class="breadcrumb-item active">Analytics</span>
                </nav>
            </div>
            <div> <a href="analitik_jam.php" class="btn btn-outline-info me-2">
                    <i class="fas fa-clock me-2"></i>Analisa Per Jam
                </a>
                
                <a href="detail.php" class="btn btn-outline-primary">
                    <i class="fas fa-table me-2"></i>Detail Penjualan
                </a>
            </div>
        </div>
    </div>

    <div class="content-area">
        <!-- Filter Periode -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label class="form-label mb-0">Periode:</label>
                    </div>
                    <div class="col-auto">
                        <select name="bulan" class="form-select">
                            <?php foreach ($bulan_nama as $no => $nama): ?>
                                <option value="<?= $no ?>" <?= $no == $bulan ? 'selected' : '' ?>><?= $nama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="tahun" class="form-select">
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Lihat
                        </button>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="alert alert-info mb-0 py-2 px-3">
                            <strong><?= $bulan_nama[$bulan] . ' ' . $tahun ?></strong>
                            <?php if ($bulan == date('n') && $tahun == date('Y')): ?>
                                <span class="badge bg-success ms-2">Bulan Ini</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Overview Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <div class="h3 text-primary mb-2"><?= formatCurrency($overview['total_pendapatan']) ?></div>
                        <h6 class="text-muted">Total Omzet</h6>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <div class="h3 text-success mb-2"><?= formatCurrency($overview['total_profit'] ?? 0) ?></div>
                        <h6 class="text-muted">Total Profit Bersih</h6>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <div class="h3 text-secondary mb-2"><?= formatNumber($overview['transaksi_selesai']) ?></div>
                        <h6 class="text-muted">Transaksi Selesai</h6>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <?php 
                            // Hitung Margin Persen Real-time untuk display
                            $margin = 0;
                            if ($overview['total_pendapatan'] > 0) {
                                $margin = (($overview['total_profit'] ?? 0) / $overview['total_pendapatan']) * 100;
                            }
                        ?>
                        <div class="h3 text-info mb-2"><?= round($margin, 1) ?>%</div>
                        <h6 class="text-muted">Margin Profit</h6>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Chart Trend Pendapatan -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Trend Pendapatan (6 Bulan Terakhir)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trendChart" height="80"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Top Customers -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Top Customers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_customers)): ?>
                            <p class="text-muted text-center py-3">Belum ada data customer</p>
                        <?php else: ?>
                            <?php foreach ($top_customers as $customer): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="fw-bold"><?= safeHtml($customer['nama']) ?></div>
                                        <small class="text-muted"><?= formatNumber($customer['total_transaksi']) ?> transaksi</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success"><?= formatCurrency($customer['total_belanja']) ?></div>
                                        <a href="https://wa.me/<?= $customer['nomor_wa'] ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Chart Penjualan Harian -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Penjualan Harian - <?= $bulan_nama[$bulan] . ' ' . $tahun ?></h5>
                    </div>
                    <div class="card-body">
                        <canvas id="harianChart" height="80"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Performa Produk -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Produk</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($performa_produk)): ?>
                            <p class="text-muted text-center py-3">Belum ada data penjualan</p>
                        <?php else: ?>
                            <?php foreach ($performa_produk as $index => $produk): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="fw-bold">
                                            <?php if ($index < 3): ?>
                                                <span class="badge bg-warning me-2">#<?= $index + 1 ?></span>
                                            <?php endif; ?>
                                            <?= safeHtml($produk['nama']) ?>
                                        </div>
                                        <small class="text-muted"><?= formatNumber($produk['jumlah_terjual']) ?> terjual</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success"><?= formatCurrency($produk['total_pendapatan']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
		
		<!-- Waktu Penyelesaian Cards -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Rata-rata Waktu Penyelesaian</h6>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($waktu_penyelesaian['total_transaksi'] > 0): ?>
                            <div class="row">
                                <div class="col-4">
                                    <div class="h4 text-info"><?= $waktu_penyelesaian['rata_rata_hari'] ?></div>
                                    <small class="text-muted">Hari</small>
                                </div>
                                <div class="col-4">
                                    <div class="h4 text-info"><?= $waktu_penyelesaian['rata_rata_jam'] ?></div>
                                    <small class="text-muted">Jam</small>
                                </div>
                                <div class="col-4">
                                    <div class="h4 text-info"><?= $waktu_penyelesaian['rata_rata_menit'] ?></div>
                                    <small class="text-muted">Menit</small>
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                Dari <?= formatNumber($waktu_penyelesaian['total_transaksi']) ?> transaksi selesai
                            </small>
                        <?php else: ?>
                            <div class="text-muted py-3">
                                <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                                Belum ada transaksi selesai dengan waktu penyelesaian
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Transaksi Tercepat</h6>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($waktu_penyelesaian['total_transaksi'] > 0): ?>
                            <div class="row">
                                <div class="col-4">
                                    <div class="h4 text-success"><?= $waktu_penyelesaian['tercepat_hari'] ?></div>
                                    <small class="text-muted">Hari</small>
                                </div>
                                <div class="col-4">
                                    <div class="h4 text-success"><?= $waktu_penyelesaian['tercepat_jam'] ?></div>
                                    <small class="text-muted">Jam</small>
                                </div>
                                <div class="col-4">
                                    <div class="h4 text-success"><?= $waktu_penyelesaian['tercepat_menit'] ?></div>
                                    <small class="text-muted">Menit</small>
                                </div>
                            </div>
                            <small class="text-success mt-2 d-block">
                                <i class="fas fa-trophy me-1"></i>Rekor tercepat bulan ini
                            </small>
                        <?php else: ?>
                            <div class="text-muted py-3">
                                <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                                Belum ada data
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Summary Info -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="h4 text-primary"><?= formatNumber($overview['total_transaksi']) ?></div>
                                <small class="text-muted">Total Transaksi</small>
                            </div>
                            <div class="col-md-3">
                                <div class="h4 text-success"><?= formatNumber($overview['total_pelanggan']) ?></div>
                                <small class="text-muted">Total Customer</small>
                            </div>
                            <div class="col-md-3">
                                <div class="h4 text-info"><?= formatNumber(count($performa_produk)) ?></div>
                                <small class="text-muted">Produk Terjual</small>
                            </div>
                            <div class="col-md-3">
                                <div class="h4 text-warning"><?= formatNumber(array_sum(array_column($penjualan_harian, 'total_transaksi'))) ?></div>
                                <small class="text-muted">Transaksi Bulan Ini</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Trend Pendapatan Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const trendData = <?= json_encode($trend_pendapatan) ?>;
    
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(item => item.nama),
            datasets: [{
                label: 'Pendapatan',
                data: trendData.map(item => item.pendapatan),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Pendapatan: Rp ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
    
    // Penjualan Harian Chart
    const harianCtx = document.getElementById('harianChart').getContext('2d');
    const harianData = <?= json_encode($penjualan_harian) ?>;
    
    new Chart(harianCtx, {
        type: 'bar',
        data: {
            labels: harianData.map(item => 'Hari ' + item.hari),
            datasets: [{
                label: 'Pendapatan',
                data: harianData.map(item => item.pendapatan),
                backgroundColor: 'rgba(0, 123, 255, 0.7)',
                borderColor: '#007bff',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const dayData = harianData[context.dataIndex];
                            return [
                                'Pendapatan: Rp ' + context.parsed.y.toLocaleString('id-ID'),
                                'Transaksi: ' + dayData.total_transaksi
                            ];
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>