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

<div class="main-content dashboard-wrapper">
    <div class="dash-header flex-column flex-md-row align-items-start align-items-md-center gap-3">
        <div>
            <h1 class="dash-title">Analitik Bisnis</h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Pantau omzet, performa produk, dan perilaku pelanggan.</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="analitik_jam.php" class="btn btn-light text-dark fw-bold border" style="border-radius: 12px;">
                <i class="fas fa-clock me-1"></i> Jam Sibuk
            </a>
            <a href="detail.php" class="btn btn-dark fw-bold" style="border-radius: 12px;">
                <i class="fas fa-table me-1"></i> Detail Penjualan
            </a>
        </div>
    </div>

    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <form method="GET" class="filter-bar">
            <i class="fas fa-calendar-alt text-muted ms-2"></i>
            <select name="bulan" class="filter-select">
                <?php foreach ($bulan_nama as $no => $nama): ?>
                    <option value="<?= $no ?>" <?= $no == $bulan ? 'selected' : '' ?>><?= $nama ?></option>
                <?php endforeach; ?>
            </select>
            <span class="text-muted">/</span>
            <select name="tahun" class="filter-select">
                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-dark btn-sm rounded-pill px-3 ms-2 fw-bold">Terapkan</button>
        </form>

        <div class="badge-clean" style="background: #EFF6FF; color: #2563EB; font-size: 0.85rem; padding: 0.5rem 1rem;">
            Periode: <?= $bulan_nama[$bulan] . ' ' . $tahun ?> 
            <?php if ($bulan == date('n') && $tahun == date('Y')): ?>
                (Bulan Ini)
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div>
                    <div class="stat-icon" style="background: #F1F5F9; color: #475569;"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-value text-dark" style="font-size: 1.6rem;"><?= formatCurrency($overview['total_pendapatan']) ?></div>
                    <div class="stat-label">Total Omzet</div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div>
                    <div class="stat-icon" style="background: #ECFDF5; color: #059669;"><i class="fas fa-hand-holding-usd"></i></div>
                    <div class="stat-value text-success" style="font-size: 1.6rem;"><?= formatCurrency($overview['total_profit'] ?? 0) ?></div>
                    <div class="stat-label">Profit Bersih</div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div>
                    <div class="stat-icon" style="background: #EFF6FF; color: #2563EB;"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value text-primary" style="font-size: 1.8rem;"><?= formatNumber($overview['transaksi_selesai']) ?></div>
                    <div class="stat-label">Transaksi Berhasil</div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <?php 
                    $margin = 0;
                    if ($overview['total_pendapatan'] > 0) {
                        $margin = (($overview['total_profit'] ?? 0) / $overview['total_pendapatan']) * 100;
                    }
                ?>
                <div>
                    <div class="stat-icon" style="background: #FEF3C7; color: #D97706;"><i class="fas fa-percent"></i></div>
                    <div class="stat-value text-warning" style="font-size: 1.8rem;"><?= round($margin, 1) ?>%</div>
                    <div class="stat-label">Margin Profit</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="list-container h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="list-header mb-0"><i class="fas fa-chart-area text-primary me-2"></i>Trend (6 Bulan)</h2>
                </div>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-5">
            <div class="list-container h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="list-header mb-0"><i class="fas fa-chart-bar text-success me-2"></i>Harian (Bulan Ini)</h2>
                </div>
                <div class="chart-container">
                    <canvas id="harianChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="list-container h-100">
                <h2 class="list-header"><i class="fas fa-crown text-warning"></i> Top 5 Pelanggan</h2>
                <?php if (empty($top_customers)): ?>
                    <div class="text-center py-4 text-muted">Belum ada data pelanggan</div>
                <?php else: ?>
                    <div class="d-flex flex-column">
                        <?php foreach ($top_customers as $index => $customer): ?>
                            <div class="row-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fw-bold" style="color: #9CA3AF; width: 1.5rem; text-align: center;"><?= $index + 1 ?></div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= safeHtml($customer['nama']) ?></div>
                                        <div class="text-muted" style="font-size: 0.8rem;"><?= formatNumber($customer['total_transaksi']) ?> transaksi</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fw-bold text-success text-end"><?= formatCurrency($customer['total_belanja']) ?></div>
                                    <a href="https://wa.me/<?= $customer['nomor_wa'] ?>" target="_blank" class="btn btn-sm btn-light rounded-circle" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="fab fa-whatsapp text-success"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="list-container h-100">
                <h2 class="list-header"><i class="fas fa-fire text-danger"></i> Produk Terlaris</h2>
                <?php if (empty($performa_produk)): ?>
                    <div class="text-center py-4 text-muted">Belum ada data penjualan</div>
                <?php else: ?>
                    <div class="d-flex flex-column">
                        <?php foreach ($performa_produk as $index => $produk): ?>
                            <div class="row-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fw-bold" style="color: #9CA3AF; width: 1.5rem; text-align: center;"><?= $index + 1 ?></div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= safeHtml($produk['nama']) ?></div>
                                        <div class="text-muted" style="font-size: 0.8rem;"><?= formatNumber($produk['jumlah_terjual']) ?> item terjual</div>
                                    </div>
                                </div>
                                <div class="fw-bold text-dark text-end"><?= formatCurrency($produk['total_pendapatan']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="list-container h-100">
                <h2 class="list-header"><i class="fas fa-stopwatch text-info"></i> Kecepatan Pelayanan</h2>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="time-stat-box">
                            <div class="text-muted fw-bold mb-2" style="font-size: 0.8rem; text-transform: uppercase;">Rata-rata Waktu</div>
                            <?php if ($waktu_penyelesaian['total_transaksi'] > 0): ?>
                                <div class="fw-bold text-dark" style="font-size: 1.25rem;">
                                    <?= $waktu_penyelesaian['rata_rata_hari'] ?>h <?= $waktu_penyelesaian['rata_rata_jam'] ?>j <?= $waktu_penyelesaian['rata_rata_menit'] ?>m
                                </div>
                            <?php else: ?>
                                <div class="text-muted">-</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="time-stat-box">
                            <div class="text-muted fw-bold mb-2" style="font-size: 0.8rem; text-transform: uppercase;">Rekor Tercepat</div>
                            <?php if ($waktu_penyelesaian['total_transaksi'] > 0): ?>
                                <div class="fw-bold text-success" style="font-size: 1.25rem;">
                                    <?= $waktu_penyelesaian['tercepat_hari'] ?>h <?= $waktu_penyelesaian['tercepat_jam'] ?>j <?= $waktu_penyelesaian['tercepat_menit'] ?>m
                                </div>
                            <?php else: ?>
                                <div class="text-muted">-</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3 text-muted small">
                    Dihitung dari <?= formatNumber($waktu_penyelesaian['total_transaksi'] ?? 0) ?> transaksi selesai.
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="list-container h-100 d-flex flex-column justify-content-center">
                <div class="row text-center g-4">
                    <div class="col-6">
                        <div class="fw-bold text-muted small text-uppercase mb-1">Total Leads (Trx)</div>
                        <div class="fw-bold text-dark" style="font-size: 1.5rem;"><?= formatNumber($overview['total_transaksi']) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold text-muted small text-uppercase mb-1">Total Pelanggan</div>
                        <div class="fw-bold text-dark" style="font-size: 1.5rem;"><?= formatNumber($overview['total_pelanggan']) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold text-muted small text-uppercase mb-1">Produk Terjual</div>
                        <div class="fw-bold text-dark" style="font-size: 1.5rem;"><?= formatNumber(count($performa_produk)) ?> Varian</div>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold text-muted small text-uppercase mb-1">Trx Bulan Ini</div>
                        <div class="fw-bold text-dark" style="font-size: 1.5rem;"><?= formatNumber(array_sum(array_column($penjualan_harian, 'total_transaksi'))) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set Global Font to Plus Jakarta Sans
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
    Chart.defaults.color = '#6B7280';
    
    // Trend Pendapatan Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const trendData = <?= json_encode($trend_pendapatan) ?>;
    
    // Gradient for Line Chart
    let gradientLine = trendCtx.createLinearGradient(0, 0, 0, 300);
    gradientLine.addColorStop(0, 'rgba(17, 24, 39, 0.2)'); // Dark transparent
    gradientLine.addColorStop(1, 'rgba(17, 24, 39, 0)');
    
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(item => item.nama),
            datasets: [{
                label: 'Omzet (Rp)',
                data: trendData.map(item => item.pendapatan),
                borderColor: '#111827', // Dark brand color
                backgroundColor: gradientLine,
                borderWidth: 3,
                pointBackgroundColor: '#FFFFFF',
                pointBorderColor: '#111827',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.4, // Smooth curve
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    padding: 12,
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 14, weight: 'bold' },
                    displayColors: false,
                    callbacks: {
                        label: function(context) { return 'Rp ' + context.parsed.y.toLocaleString('id-ID'); }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, border: { display: false } },
                y: { 
                    grid: { color: '#F3F4F6', drawBorder: false },
                    border: { display: false },
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) return 'Rp ' + (value / 1000000) + 'M';
                            if (value >= 1000) return 'Rp ' + (value / 1000) + 'K';
                            return value;
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
            labels: harianData.map(item => item.hari),
            datasets: [{
                label: 'Omzet Harian',
                data: harianData.map(item => item.pendapatan),
                backgroundColor: '#10B981', // Success green
                borderRadius: 4, // Rounded bars
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    padding: 12,
                    titleFont: { size: 13 },
                    bodyFont: { size: 14, weight: 'bold' },
                    displayColors: false,
                    callbacks: {
                        title: function(context) { return 'Tanggal ' + context[0].label; },
                        label: function(context) {
                            const dayData = harianData[context.dataIndex];
                            return [
                                'Rp ' + context.parsed.y.toLocaleString('id-ID'),
                                dayData.total_transaksi + ' Transaksi'
                            ];
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, border: { display: false } },
                y: { display: false } // Sembunyikan axis Y biar ultra-bersih
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>