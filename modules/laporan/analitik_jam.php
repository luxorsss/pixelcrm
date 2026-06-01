<?php
$page_title = "Analisa Jam Aktif Market";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/functions.php';

$laporan = new LaporanManager();
$produk_list = $laporan->getAllProduk();

// --- Logika Filter ---
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d', strtotime('-30 days'));
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$produk_id = isset($_GET['produk_id']) ? $_GET['produk_id'] : '';
$hari_pekan = isset($_GET['hari_pekan']) ? $_GET['hari_pekan'] : '';

$filters = [
    'produk_id' => $produk_id,
    'tanggal_awal' => $tanggal_awal,
    'tanggal_akhir' => $tanggal_akhir,
    'hari_pekan' => $hari_pekan
];

// Mapping Hari MySQL (1=Minggu s/d 7=Sabtu)
$list_hari = [
    2 => 'Senin', 3 => 'Selasa', 4 => 'Rabu', 5 => 'Kamis', 6 => 'Jumat', 7 => 'Sabtu', 1 => 'Minggu'
];

// Ambil Data
$data_jam = $laporan->getAnalisaPerJam($filters);

// Data untuk Chart
$chart_labels = array_column($data_jam, 'label');
$chart_total = array_column($data_jam, 'total_transaksi');
$chart_pending = array_column($data_jam, 'total_pending');
$chart_selesai = array_column($data_jam, 'total_selesai');

// Cari Peak Hour
$peak_trx = 0;
$peak_hour_label = "-";
foreach ($data_jam as $d) {
    if ($d['total_transaksi'] > $peak_trx) {
        $peak_trx = $d['total_transaksi'];
        $peak_hour_label = $d['label'];
    }
}
?>

<div class="main-content dashboard-wrapper">
    <div class="dash-header flex-column flex-md-row align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <a href="analitik.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Ringkasan Analitik
            </a>
            <h1 class="dash-title mt-2">Waktu Market Aktif</h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Analisa perilaku jam *checkout* untuk optimasi jadwal iklan (Ads Scheduling).</div>
        </div>
        <div class="d-flex align-items-center">
            <div class="badge-clean" style="background: #FFFBEB; border: 1px solid #FDE68A; color: #D97706; padding: 0.75rem 1.25rem; font-size: 0.85rem;">
                <i class="fas fa-fire me-2"></i> Jam Teramai: <strong class="ms-1"><?= $peak_hour_label ?>:00 - <?= $peak_hour_label ?>:59</strong>
            </div>
        </div>
    </div>

    <div class="list-container p-3 mb-4 d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
        <form method="GET" class="d-flex flex-wrap align-items-center gap-2 m-0 w-100">
            
            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1 border border-light">
                <i class="fas fa-calendar-alt text-muted me-2" style="font-size: 0.85rem;"></i>
                <input type="date" name="tanggal_awal" class="form-control border-0 bg-transparent p-0 text-muted fw-bold" style="width: 110px; font-size: 0.85rem;" value="<?= $tanggal_awal ?>">
                <span class="mx-2 text-muted">-</span>
                <input type="date" name="tanggal_akhir" class="form-control border-0 bg-transparent p-0 text-muted fw-bold" style="width: 110px; font-size: 0.85rem;" value="<?= $tanggal_akhir ?>">
            </div>

            <div class="bg-light rounded-pill px-3 py-1 border border-light d-flex align-items-center">
                <select name="hari_pekan" class="form-select border-0 bg-transparent p-0 text-dark fw-bold" style="width: auto; min-width: 110px; font-size: 0.85rem; cursor: pointer;">
                    <option value="">Semua Hari</option>
                    <?php foreach ($list_hari as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $hari_pekan == $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bg-light rounded-pill px-3 py-1 border border-light d-flex align-items-center flex-grow-1" style="max-width: 300px;">
                <select name="produk_id" class="form-select border-0 bg-transparent p-0 text-dark fw-bold text-truncate" style="font-size: 0.85rem; cursor: pointer;">
                    <option value="">Semua Produk</option>
                    <?php foreach ($produk_list as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $produk_id == $p['id'] ? 'selected' : '' ?>>
                            <?= safeHtml($p['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-dark btn-sm rounded-pill fw-bold px-4 ms-auto ms-xl-0" style="padding-top: 0.4rem; padding-bottom: 0.4rem;">
                Terapkan Filter
            </button>
        </form>
    </div>

    <div class="list-container mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="list-header mb-0"><i class="fas fa-chart-bar text-primary me-2"></i>Pola Jam Checkout</h2>
            <div class="d-flex gap-3 text-muted" style="font-size: 0.8rem; font-weight: 600;">
                <div><span style="display:inline-block; width:10px; height:10px; background:#10B981; border-radius:3px; margin-right:5px;"></span>Selesai (Closing)</div>
                <div><span style="display:inline-block; width:10px; height:10px; background:#F59E0B; border-radius:3px; margin-right:5px;"></span>Pending (Leads)</div>
            </div>
        </div>
        <div style="height: 350px; position: relative;">
            <canvas id="jamChart"></canvas>
        </div>
    </div>

    <div class="product-list-container shadow-sm mb-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center p-4 border-bottom">
            <h2 class="list-header mb-0"><i class="fas fa-list text-dark me-2"></i>Detail Aktivitas Per Jam</h2>
            <button onclick="copyTableForAI()" id="btnCopyAI" class="btn btn-light text-primary fw-bold mt-3 mt-sm-0" style="border-radius: 12px; font-size: 0.85rem; border: 1px solid #BFDBFE; background: #EFF6FF;">
                <i class="fas fa-robot me-2"></i>Copy Data untuk AI
            </button>
        </div>
        
        <div class="table-responsive">
            <table id="tableDataJam" class="table-editorial mb-0">
                <thead>
                    <tr>
                        <th width="80" class="text-center">Jam</th>
                        <th class="text-center">Total Checkout</th>
                        <th class="text-center text-success">Selesai (Bayar)</th>
                        <th class="text-center text-warning">Pending (Follow Up)</th>
                        <th class="text-end">Potensi Omzet</th>
                        <th width="180">Intensitas Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sum_checkout = 0; $sum_selesai = 0; $sum_pending = 0; $sum_omzet = 0;

                    foreach ($data_jam as $row): 
                        $sum_checkout += $row['total_transaksi'];
                        $sum_selesai += $row['total_selesai'];
                        $sum_pending += $row['total_pending'];
                        $sum_omzet += $row['potensi_omzet'];

                        $percent = $peak_trx > 0 ? ($row['total_transaksi'] / $peak_trx) * 100 : 0;
                        
                        // Map color logic cleanly
                        $bar_color = '#9CA3AF'; // Secondary
                        if ($percent > 75) $bar_color = '#EF4444'; // Danger
                        elseif ($percent > 50) $bar_color = '#F59E0B'; // Warning
                        elseif ($percent > 25) $bar_color = '#3B82F6'; // Info
                    ?>
                    <tr>
                        <td class="text-center fw-bold text-dark" style="font-size: 0.95rem;"><?= $row['label'] ?></td>
                        
                        <td class="text-center">
                            <span class="badge-clean" style="background: #F3F4F6; color: #374151; font-size: 0.85rem;">
                                <?= number_format($row['total_transaksi']) ?> Leads
                            </span>
                        </td>
                        
                        <td class="text-center fw-bold" style="color: #10B981;">
                            <?= number_format($row['total_selesai']) ?>
                        </td>
                        
                        <td class="text-center fw-bold" style="color: #F59E0B;">
                            <?= number_format($row['total_pending']) ?>
                        </td>
                        
                        <td class="text-end fw-bold text-dark">
                            <?= formatNumber($row['potensi_omzet']) ?>
                        </td>
                        
                        <td class="pe-4">
                            <div style="width: 100%; height: 6px; background: #F3F4F6; border-radius: 4px; overflow: hidden; display: flex;">
                                <div style="width: <?= $percent ?>%; height: 100%; background: <?= $bar_color ?>; border-radius: 4px;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                
                <tfoot style="background: #F9FAFB;">
                    <tr>
                        <td class="text-center py-4 text-muted fw-bold" style="font-size: 0.85rem; text-transform: uppercase;">Total</td>
                        
                        <td class="text-center py-4">
                            <div class="fw-bold text-dark fs-5"><?= number_format($sum_checkout) ?></div>
                        </td>
                        
                        <td class="text-center py-4">
                            <div class="fw-bold text-success fs-5"><?= number_format($sum_selesai) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem; font-weight: 600;">Total Closing</div>
                        </td>
                        
                        <td class="text-center py-4">
                            <div class="fw-bold text-warning fs-5"><?= number_format($sum_pending) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem; font-weight: 600;">Total Pending</div>
                        </td>
                        
                        <td class="text-end py-4">
                            <div class="fw-bold text-dark fs-5"><?= formatNumber($sum_omzet) ?></div>
                        </td>
                        
                        <td class="text-center py-4 pe-4">
                            <?php 
                                $cr = $sum_checkout > 0 ? ($sum_selesai / $sum_checkout) * 100 : 0;
                                $cr_bg = $cr > 50 ? '#ECFDF5' : ($cr > 20 ? '#FFFBEB' : '#FEF2F2');
                                $cr_color = $cr > 50 ? '#059669' : ($cr > 20 ? '#D97706' : '#DC2626');
                            ?>
                            <div class="badge-clean d-inline-flex px-3 py-2" style="background: <?= $cr_bg ?>; color: <?= $cr_color ?>; font-size: 0.85rem;">
                                Efisiensi: <?= round($cr, 1) ?>%
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
// Fungsi untuk Prompt AI (dipertahankan logic-nya, disempurnakan UX-nya)
function copyTableForAI() {
    const btn = document.getElementById("btnCopyAI");
    const originalContent = btn.innerHTML;
    const table = document.getElementById("tableDataJam");
    
    let textToCopy = "DATA ANALISA WAKTU TRANSAKSI\n";
    textToCopy += "Periode: <?= $tanggal_awal ?> s/d <?= $tanggal_akhir ?> (<?= $hari_pekan ? $list_hari[$hari_pekan] : 'Semua Hari' ?>)\n";
    textToCopy += "Tujuan: Tolong buatkan analisa waktu efektif untuk jadwal tayang iklan (Ads Scheduling) berdasarkan data berikut.\n\n";
    
    const headers = [];
    table.querySelectorAll("thead th").forEach(th => headers.push(th.innerText.trim()));
    textToCopy += headers.join(" | ") + "\n" + "-".repeat(50) + "\n";

    table.querySelectorAll("tbody tr").forEach(tr => {
        const rowData = [];
        tr.querySelectorAll("td").forEach(td => rowData.push(td.innerText.replace(/[\r\n]+/g, " ").trim()));
        textToCopy += rowData.join(" | ") + "\n";
    });

    textToCopy += "-".repeat(50) + "\n";
    const footerRow = table.querySelector("tfoot tr");
    if (footerRow) {
        const footerData = [];
        footerRow.querySelectorAll("td").forEach(td => footerData.push(td.innerText.replace(/[\r\n]+/g, " ").trim()));
        textToCopy += footerData.join(" | ");
    }

    navigator.clipboard.writeText(textToCopy).then(() => {
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Tersalin!';
        btn.style.background = '#ECFDF5';
        btn.style.color = '#10B981';
        btn.style.borderColor = '#A7F3D0';
        
        setTimeout(() => {
            btn.innerHTML = originalContent;
            btn.style.background = '#EFF6FF';
            btn.style.color = '#3B82F6';
            btn.style.borderColor = '#BFDBFE';
        }, 2000);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
    const ctx = document.getElementById('jamChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                {
                    label: 'Selesai (Closing)',
                    data: <?= json_encode($chart_selesai) ?>,
                    backgroundColor: '#10B981', // Emerald
                    borderRadius: 4,
                    borderSkipped: false,
                    stack: 'Stack 0',
                },
                {
                    label: 'Pending (Leads)',
                    data: <?= json_encode($chart_pending) ?>,
                    backgroundColor: '#FCD34D', // Amber (softer yellow/orange)
                    borderRadius: 4,
                    borderSkipped: false,
                    stack: 'Stack 0',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { 
                    grid: { display: false }, 
                    border: { display: false } 
                },
                y: {
                    beginAtZero: true,
                    stacked: true,
                    grid: { color: '#F3F4F6', drawBorder: false },
                    border: { display: false }
                }
            },
            plugins: {
                legend: { display: false }, // Disembunyikan karena sudah buat custom legend di HTML
                tooltip: { 
                    mode: 'index', 
                    intersect: false,
                    backgroundColor: '#111827',
                    padding: 12,
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 14 },
                    callbacks: {
                        title: function(context) { return 'Pukul ' + context[0].label; }
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>