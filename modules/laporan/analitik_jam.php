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

<div class="main-content">
    <div class="top-header mb-4">
        <div>
            <h1 class="page-title mb-0">Analisa Waktu Market Aktif</h1>
            <nav class="breadcrumb">
                <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                <a href="analitik.php" class="breadcrumb-item text-decoration-none">Analytics</a>
                <span class="breadcrumb-item active">Jam Checkout</span>
            </nav>
        </div>
    </div>

    <div class="content-area">
        
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <form method="GET">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Dari Tanggal</label>
                            <input type="date" name="tanggal_awal" class="form-control form-control-sm" value="<?= $tanggal_awal ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Sampai Tanggal</label>
                            <input type="date" name="tanggal_akhir" class="form-control form-control-sm" value="<?= $tanggal_akhir ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Filter Hari</label>
                            <select name="hari_pekan" class="form-select form-select-sm">
                                <option value="">Semua Hari</option>
                                <?php foreach ($list_hari as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $hari_pekan == $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Filter Produk</label>
                            <select name="produk_id" class="form-select form-select-sm">
                                <option value="">Semua Produk</option>
                                <?php foreach ($produk_list as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $produk_id == $p['id'] ? 'selected' : '' ?>>
                                        <?= safeHtml($p['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Terapkan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 text-primary"><i class="fas fa-clock me-2"></i>Pola Jam Checkout</h5>
                            <small class="text-muted">
                                <?= $hari_pekan ? 'Khusus Hari <strong>' . $list_hari[$hari_pekan] . '</strong>' : 'Semua Hari' ?> 
                                (<?= date('d M', strtotime($tanggal_awal)) ?> - <?= date('d M', strtotime($tanggal_akhir)) ?>)
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-warning text-dark p-2">
                                <i class="fas fa-fire me-1"></i> Jam Teramai: <strong><?= $peak_hour_label ?>:00 - <?= $peak_hour_label ?>:59</strong>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 400px;">
                            <canvas id="jamChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Detail Aktivitas Per Jam</h5>
                        <button onclick="copyTableForAI()" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-copy me-1"></i>Copy Data untuk AI
                        </button>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="tableDataJam" class="table table-hover table-striped mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center">Jam</th>
                                        <th class="text-center">Total Checkout</th>
                                        <th class="text-center text-success">Selesai (Bayar)</th>
                                        <th class="text-center text-warning">Pending (Follow Up)</th>
                                        <th class="text-end">Potensi Omzet</th>
                                        <th class="text-center" width="20%">Intensitas</th>
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
                                        $bar_class = 'bg-secondary';
                                        if ($percent > 75) $bar_class = 'bg-danger'; 
                                        elseif ($percent > 50) $bar_class = 'bg-warning'; 
                                        elseif ($percent > 25) $bar_class = 'bg-info'; 
                                    ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?= $row['label'] ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill px-3 fs-6"><?= number_format($row['total_transaksi']) ?></span>
                                        </td>
                                        <td class="text-center text-success fw-bold">
                                            <?= number_format($row['total_selesai']) ?>
                                        </td>
                                        <td class="text-center text-warning fw-bold">
                                            <?= number_format($row['total_pending']) ?>
                                        </td>
                                        <td class="text-end text-muted">
                                            <?= formatNumber($row['potensi_omzet']) ?>
                                        </td>
                                        <td class="px-4">
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar <?= $bar_class ?>" role="progressbar" style="width: <?= $percent ?>%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                
                                <tfoot class="bg-light fw-bold" style="border-top: 2px solid #dee2e6;">
                                    <tr>
                                        <td class="text-center py-3">TOTAL</td>
                                        <td class="text-center py-3 fs-5"><?= number_format($sum_checkout) ?></td>
                                        <td class="text-center py-3 fs-5 text-success">
                                            <?= number_format($sum_selesai) ?>
                                            <div class="small text-muted fw-normal" style="font-size: 0.75rem;">Closing</div>
                                        </td>
                                        <td class="text-center py-3 fs-5 text-warning">
                                            <?= number_format($sum_pending) ?>
                                            <div class="small text-muted fw-normal" style="font-size: 0.75rem;">Leads</div>
                                        </td>
                                        <td class="text-end py-3 text-dark">
                                            <?= formatNumber($sum_omzet) ?>
                                        </td>
                                        <td class="text-center py-3">
                                            <?php 
                                                $cr = $sum_checkout > 0 ? ($sum_selesai / $sum_checkout) * 100 : 0;
                                                $badge_color = $cr > 50 ? 'bg-success' : ($cr > 20 ? 'bg-warning' : 'bg-danger');
                                            ?>
                                            <span class="badge <?= $badge_color ?> text-white p-2">
                                                Eff: <?= round($cr, 1) ?>%
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
function copyTableForAI() {
    const table = document.getElementById("tableDataJam");
    let textToCopy = "DATA ANALISA WAKTU TRANSAKSI\n";
    textToCopy += "Periode: <?= $tanggal_awal ?> s/d <?= $tanggal_akhir ?> (<?= $hari_pekan ? $list_hari[$hari_pekan] : 'Semua Hari' ?>)\n";
    textToCopy += "Tujuan: Analisa waktu efektif untuk jadwal iklan (Ads Scheduling).\n\n";
    
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
        alert("Data berhasil dicopy! Siap untuk analisa AI.");
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('jamChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                {
                    label: 'Pending (Leads)',
                    data: <?= json_encode($chart_pending) ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.7)',
                    borderColor: '#ffc107',
                    borderWidth: 1,
                    stack: 'Stack 0',
                },
                {
                    label: 'Selesai (Closing)',
                    data: <?= json_encode($chart_selesai) ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: '#28a745',
                    borderWidth: 1,
                    stack: 'Stack 0',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Jumlah Checkout (Orang)' },
                    stacked: true
                }
            },
            plugins: {
                tooltip: { mode: 'index', intersect: false },
                legend: { position: 'top' }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>