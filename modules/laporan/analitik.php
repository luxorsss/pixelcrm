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

<div class="main-content dashboard-wrapper flex-grow-1">
    
    <div class="dash-header flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="dash-title d-flex align-items-center gap-2">
                <i class="fas fa-chart-line text-primary"></i> Analitik Bisnis
            </h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Pantau omzet, performa produk, dan perilaku pelanggan.</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="analitik_jam.php" class="btn btn-light text-dark fw-bold border rounded-pill px-3" style="box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <i class="fas fa-clock text-info me-1"></i> Jam Sibuk
            </a>
            <a href="detail.php" class="btn btn-dark fw-bold rounded-pill px-4" style="box-shadow: 0 4px 12px rgba(17, 24, 39, 0.15);">
                <i class="fas fa-table me-1"></i> Detail Laporan
            </a>
        </div>
    </div>

    <div class="panel-editorial p-3 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3" style="background: var(--bg-surface);">
        <form method="GET" class="d-flex flex-wrap align-items-center gap-2 w-100 m-0">
            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1 border border-light flex-grow-1 flex-md-grow-0" style="min-width: 200px;">
                <i class="fas fa-calendar-alt text-muted me-2" style="font-size: 0.85rem;"></i>
                <select name="bulan" class="form-select border-0 bg-transparent p-0 text-dark fw-bold pe-3" style="font-size: 0.9rem; cursor: pointer; box-shadow: none; outline: none; flex: 1;">
                    <?php foreach ($bulan_nama as $no => $nama): ?>
                        <option value="<?= $no ?>" <?= $no == $bulan ? 'selected' : '' ?>><?= $nama ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="text-muted mx-2">/</span>
                <select name="tahun" class="form-select border-0 bg-transparent p-0 text-dark fw-bold" style="font-size: 0.9rem; cursor: pointer; box-shadow: none; outline: none; width: auto;">
                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-dark btn-sm rounded-pill px-4 fw-bold flex-grow-1 flex-md-grow-0 py-2">Terapkan</button>
        </form>

        <div class="badge-clean flex-shrink-0" style="background: #EFF6FF; color: #2563EB; font-size: 0.85rem; padding: 0.5rem 1rem; border: 1px solid #BFDBFE;">
            <i class="fas fa-filter me-1"></i> <?= $bulan_nama[$bulan] . ' ' . $tahun ?> 
            <?php if ($bulan == date('n') && $tahun == date('Y')): ?>
                <span class="fw-bold ms-1">(Bulan Ini)</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card" style="padding: 1.25rem;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-icon m-0" style="background: #F1F5F9; color: #475569; width: 36px; height: 36px; font-size: 1rem;"><i class="fas fa-money-bill-wave"></i></div>
                </div>
                <div>
                    <div class="stat-value text-dark text-truncate" style="font-size: 1.35rem;" title="<?= formatCurrency($overview['total_pendapatan']) ?>"><?= formatCurrency($overview['total_pendapatan']) ?></div>
                    <div class="stat-label mt-1" style="font-size: 0.75rem;">Total Omzet</div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-xl-3">
            <div class="stat-card" style="padding: 1.25rem;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-icon m-0" style="background: #ECFDF5; color: #059669; width: 36px; height: 36px; font-size: 1rem;"><i class="fas fa-hand-holding-usd"></i></div>
                </div>
                <div>
                    <div class="stat-value text-success text-truncate" style="font-size: 1.35rem;" title="<?= formatCurrency($overview['total_profit'] ?? 0) ?>"><?= formatCurrency($overview['total_profit'] ?? 0) ?></div>
                    <div class="stat-label mt-1" style="font-size: 0.75rem;">Profit Bersih</div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-xl-3">
            <div class="stat-card" style="padding: 1.25rem;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-icon m-0" style="background: #EFF6FF; color: #2563EB; width: 36px; height: 36px; font-size: 1rem;"><i class="fas fa-check-circle"></i></div>
                </div>
                <div>
                    <div class="stat-value text-primary" style="font-size: 1.5rem;"><?= formatNumber($overview['transaksi_selesai']) ?></div>
                    <div class="stat-label mt-1" style="font-size: 0.75rem;">Trx Berhasil</div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-xl-3">
            <div class="stat-card" style="padding: 1.25rem;">
                <?php 
                    $margin = 0;
                    if ($overview['total_pendapatan'] > 0) {
                        $margin = (($overview['total_profit'] ?? 0) / $overview['total_pendapatan']) * 100;
                    }
                ?>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="stat-icon m-0" style="background: #FEF3C7; color: #D97706; width: 36px; height: 36px; font-size: 1rem;"><i class="fas fa-percent"></i></div>
                </div>
                <div>
                    <div class="stat-value text-warning" style="font-size: 1.5rem;"><?= round($margin, 1) ?>%</div>
                    <div class="stat-label mt-1" style="font-size: 0.75rem;">Margin Profit</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="panel-editorial h-100 p-0 overflow-hidden d-flex flex-column">
                <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <h3 class="panel-title m-0" style="font-size: 1rem;"><i class="fas fa-chart-area text-primary me-2"></i> Trend Penjualan (6 Bulan)</h3>
                </div>
                <div class="p-3 bg-light flex-grow-1" style="min-height: 250px; position: relative;">
                    <div style="position: absolute; top: 1rem; left: 1rem; right: 1rem; bottom: 1rem;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-5">
            <div class="panel-editorial h-100 p-0 overflow-hidden d-flex flex-column">
                <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <h3 class="panel-title m-0" style="font-size: 1rem;"><i class="fas fa-chart-bar text-success me-2"></i> Harian (Bulan Ini)</h3>
                </div>
                <div class="p-3 bg-light flex-grow-1" style="min-height: 250px; position: relative;">
                    <div style="position: absolute; top: 1rem; left: 1rem; right: 1rem; bottom: 1rem;">
                        <canvas id="harianChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="panel-editorial p-0 overflow-hidden h-100">
                <div class="p-4 border-bottom bg-white">
                    <h3 class="panel-title m-0" style="font-size: 1rem;"><i class="fas fa-crown text-warning me-2"></i> Top 5 Pelanggan Sultan</h3>
                </div>
                <div class="p-0 bg-light">
                    <?php if (empty($top_customers)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users-slash text-muted opacity-25 fs-1 mb-2"></i>
                            <div class="fw-bold text-dark">Belum ada pelanggan</div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table-editorial mb-0">
                                <tbody>
                                    <?php foreach ($top_customers as $index => $customer): 
                                        $medalColor = '#9CA3AF'; 
                                        if($index == 0) $medalColor = '#F59E0B'; 
                                        else if($index == 1) $medalColor = '#94A3B8'; 
                                        else if($index == 2) $medalColor = '#B45309'; 
                                    ?>
                                    <tr>
                                        <td width="50" class="text-center">
                                            <div class="fw-bold d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: white; border-radius: 8px; border: 2px solid <?= $medalColor ?>; color: <?= $medalColor ?>; font-size: 0.8rem;">
                                                <?= $index + 1 ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem; max-width: 150px;"><?= safeHtml($customer['nama']) ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-shopping-basket me-1"></i><?= formatNumber($customer['total_transaksi']) ?> trx</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-bold text-success" style="font-size: 0.95rem;"><?= formatCurrency($customer['total_belanja']) ?></div>
                                        </td>
                                        <td width="50" class="text-end pe-4">
                                            <a href="https://wa.me/<?= $customer['nomor_wa'] ?>" target="_blank" class="btn-action-icon" style="background: #ECFDF5; color: #10B981; border: 1px solid #A7F3D0;" title="Chat Pelanggan">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
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

        <div class="col-lg-6">
            <div class="panel-editorial p-0 overflow-hidden h-100">
                <div class="p-4 border-bottom bg-white">
                    <h3 class="panel-title m-0" style="font-size: 1rem;"><i class="fas fa-fire text-danger me-2"></i> Produk Terlaris</h3>
                </div>
                <div class="p-0 bg-light">
                    <?php if (empty($performa_produk)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-box-open text-muted opacity-25 fs-1 mb-2"></i>
                            <div class="fw-bold text-dark">Belum ada penjualan</div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table-editorial mb-0">
                                <tbody>
                                    <?php foreach ($performa_produk as $index => $produk): 
                                        $medalColor = '#9CA3AF'; 
                                        if($index == 0) $medalColor = '#F59E0B'; 
                                        else if($index == 1) $medalColor = '#94A3B8'; 
                                        else if($index == 2) $medalColor = '#B45309'; 
                                    ?>
                                    <tr>
                                        <td width="50" class="text-center">
                                            <div class="fw-bold d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: white; border-radius: 8px; border: 2px solid <?= $medalColor ?>; color: <?= $medalColor ?>; font-size: 0.8rem;">
                                                <?= $index + 1 ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem; max-width: 150px;"><?= safeHtml($produk['nama']) ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-tag me-1"></i><?= formatNumber($produk['jumlah_terjual']) ?> terjual</div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="fw-bold text-primary" style="font-size: 0.95rem;"><?= formatCurrency($produk['total_pendapatan']) ?></div>
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
    
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="panel-editorial p-0 overflow-hidden h-100">
                <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <h3 class="panel-title m-0" style="font-size: 1rem;"><i class="fas fa-stopwatch text-info me-2"></i> Kinerja Pelayanan</h3>
                </div>
                <div class="p-4 bg-light h-100 d-flex flex-column justify-content-center">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <div class="bg-white border rounded-4 p-3 text-center h-100 shadow-sm">
                                <div class="text-muted fw-bold mb-2" style="font-size: 0.75rem; text-transform: uppercase;">Rata-rata Waktu</div>
                                <?php if ($waktu_penyelesaian['total_transaksi'] > 0): ?>
                                    <div class="fw-bold text-dark d-flex align-items-baseline justify-content-center gap-1">
                                        <span style="font-size: 1.5rem; line-height: 1;"><?= $waktu_penyelesaian['rata_rata_hari'] ?></span><span class="text-muted small">h</span>
                                        <span style="font-size: 1.5rem; line-height: 1;"><?= $waktu_penyelesaian['rata_rata_jam'] ?></span><span class="text-muted small">j</span>
                                        <span style="font-size: 1.5rem; line-height: 1;"><?= $waktu_penyelesaian['rata_rata_menit'] ?></span><span class="text-muted small">m</span>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted fs-4">-</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="bg-white border rounded-4 p-3 text-center h-100 shadow-sm" style="border-color: #A7F3D0 !important;">
                                <div class="text-success fw-bold mb-2" style="font-size: 0.75rem; text-transform: uppercase;">Rekor Tercepat</div>
                                <?php if ($waktu_penyelesaian['total_transaksi'] > 0): ?>
                                    <div class="fw-bold text-success d-flex align-items-baseline justify-content-center gap-1">
                                        <span style="font-size: 1.5rem; line-height: 1;"><?= $waktu_penyelesaian['tercepat_hari'] ?></span><span class="text-muted small">h</span>
                                        <span style="font-size: 1.5rem; line-height: 1;"><?= $waktu_penyelesaian['tercepat_jam'] ?></span><span class="text-muted small">j</span>
                                        <span style="font-size: 1.5rem; line-height: 1;"><?= $waktu_penyelesaian['tercepat_menit'] ?></span><span class="text-muted small">m</span>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted fs-4">-</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3 text-muted" style="font-size: 0.75rem;">
                        *Berdasarkan durasi dari Checkout ke Selesai pada <?= formatNumber($waktu_penyelesaian['total_transaksi'] ?? 0) ?> transaksi.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel-editorial p-4 h-100 d-flex flex-column justify-content-center bg-dark text-white text-center rounded-4" style="background: linear-gradient(135deg, #111827 0%, #1E3A8A 100%); border: none;">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="text-uppercase mb-1 opacity-75" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 1px;">Total Leads</div>
                        <div class="fw-bold text-white" style="font-size: 1.75rem; line-height: 1;"><?= formatNumber($overview['total_transaksi']) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-uppercase mb-1 opacity-75" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 1px;">Pelanggan Unik</div>
                        <div class="fw-bold text-white" style="font-size: 1.75rem; line-height: 1;"><?= formatNumber($overview['total_pelanggan']) ?></div>
                    </div>
                    <div class="col-6 border-top pt-4" style="border-color: rgba(255,255,255,0.1) !important;">
                        <div class="text-uppercase mb-1 opacity-75" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 1px;">Varian Terjual</div>
                        <div class="fw-bold text-white" style="font-size: 1.75rem; line-height: 1;"><?= formatNumber(count($performa_produk)) ?></div>
                    </div>
                    <div class="col-6 border-top pt-4" style="border-color: rgba(255,255,255,0.1) !important;">
                        <div class="text-uppercase mb-1 opacity-75" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 1px;">Order Bulan Ini</div>
                        <div class="fw-bold text-warning" style="font-size: 1.75rem; line-height: 1;"><?= formatNumber(array_sum(array_column($penjualan_harian, 'total_transaksi'))) ?></div>
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
    
    // Config agar chart.js merespon perubahan ukuran div parent dengan baik di HP
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
    };

    // Trend Pendapatan Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const trendData = <?= json_encode($trend_pendapatan) ?>;
    
    let gradientLine = trendCtx.createLinearGradient(0, 0, 0, 250);
    gradientLine.addColorStop(0, 'rgba(59, 130, 246, 0.4)'); // Blue transparent
    gradientLine.addColorStop(1, 'rgba(59, 130, 246, 0)');
    
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendData.map(item => item.nama.substring(0,3)), // Ambil 3 huruf awal agar tidak tabrakan di HP
            datasets: [{
                label: 'Omzet (Rp)',
                data: trendData.map(item => item.pendapatan),
                borderColor: '#3B82F6', 
                backgroundColor: gradientLine,
                borderWidth: 3,
                pointBackgroundColor: '#FFFFFF',
                pointBorderColor: '#3B82F6',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.4, // Smooth curve
                fill: true
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                tooltip: {
                    backgroundColor: '#111827',
                    padding: 12,
                    titleFont: { size: 12 },
                    bodyFont: { size: 14, weight: 'bold' },
                    displayColors: false,
                    callbacks: {
                        title: function(context) { return trendData[context[0].dataIndex].nama; },
                        label: function(context) { return 'Rp ' + context.parsed.y.toLocaleString('id-ID'); }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, border: { display: false } },
                y: { 
                    grid: { color: '#E5E7EB', drawBorder: false, borderDash: [5, 5] },
                    border: { display: false },
                    beginAtZero: true,
                    ticks: {
                        maxTicksLimit: 5,
                        callback: function(value) {
                            if (value >= 1000000) return (value / 1000000) + 'M';
                            if (value >= 1000) return (value / 1000) + 'K';
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
                label: 'Omzet',
                data: harianData.map(item => item.pendapatan),
                backgroundColor: '#10B981', // Success green
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                tooltip: {
                    backgroundColor: '#111827',
                    padding: 12,
                    titleFont: { size: 12 },
                    bodyFont: { size: 13, weight: 'bold' },
                    displayColors: false,
                    callbacks: {
                        title: function(context) { return 'Tgl ' + context[0].label; },
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
                x: { grid: { display: false }, border: { display: false }, ticks: { maxTicksLimit: 15 } },
                y: { display: false } // Sembunyikan axis Y biar ultra-bersih
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>