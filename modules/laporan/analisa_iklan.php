<?php
$page_title = "Analisa Iklan & ROAS";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$jsonFile = __DIR__ . '/../../data/ads_performance.json';

// Pastikan file JSON ada
if (!file_exists($jsonFile)) {
    file_put_contents($jsonFile, json_encode(['updated_at' => date('Y-m-d H:i:s'), 'records' => []], JSON_PRETTY_PRINT));
}

$adData = json_decode(file_get_contents($jsonFile), true) ?: ['records' => []];
$adRecords = $adData['records'] ?? [];

// Index data iklan berdasarkan tanggal
$adsByDate = [];
foreach ($adRecords as $r) {
    if (!empty($r['date'])) {
        $adsByDate[$r['date']] = $r;
    }
}

// 1. Ambil data penjualan selesai secara otomatis dari tabel transaksi (Single Source of Truth)
$crmByDate = [];
if (function_exists('db')) {
    try {
        $conn = db();
        if ($conn && !$conn->connect_error) {
            $sql = "SELECT 
                        DATE(tanggal_transaksi) as tgl,
                        COUNT(CASE WHEN status = 'selesai' THEN 1 END) as jml_selesai,
                        COALESCE(SUM(CASE WHEN status = 'selesai' THEN total_harga ELSE 0 END), 0) as omzet
                    FROM transaksi 
                    GROUP BY DATE(tanggal_transaksi)
                    ORDER BY tgl DESC";
            $res = $conn->query($sql);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $crmByDate[$row['tgl']] = [
                        'sales' => (int)$row['jml_selesai'],
                        'omzet' => (float)$row['omzet']
                    ];
                }
            }
        }
    } catch (Exception $e) {
        // Fallback jika database lokal belum start
    }
}

// 2. Handle Simpan / Edit Biaya Iklan & Pajak (Hanya Iklan & Pajak yang diedit manual)
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_ad_cost') {
    $date = trim($_POST['date'] ?? '');
    $spend_raw = (float)str_replace(['.', ','], ['', '.'], $_POST['spend_raw'] ?? 0);
    $tax_amount = (float)str_replace(['.', ','], ['', '.'], $_POST['tax_amount'] ?? 0);
    $tax_notes = trim($_POST['tax_notes'] ?? '');

    if ($date) {
        $adsByDate[$date] = [
            'date' => $date,
            'spend_raw' => $spend_raw,
            'tax_amount' => $tax_amount,
            'total_ad_cost' => $spend_raw + $tax_amount,
            'tax_notes' => $tax_notes ?: ($tax_amount > 0 ? "Pajak Diinput Manual" : "Bebas Pajak (HK)")
        ];

        // Simpan ke JSON
        $saveList = array_values($adsByDate);
        usort($saveList, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        $adData['updated_at'] = date('Y-m-d H:i:s');
        $adData['records'] = $saveList;
        file_put_contents($jsonFile, json_encode($adData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $notice = "Data iklan tanggal " . date('d M Y', strtotime($date)) . " berhasil diperbarui.";
    }
}

// 3. Tanggal yang dianalisa adalah tanggal yang sudah memiliki data iklan tuntas
$allDates = array_keys($adsByDate);
if ($range === 'today' && !in_array($today, $allDates)) {
    $allDates[] = $today;
}
rsort($allDates); // Terbaru di atas

// 4. Filter Rentang Tanggal
$range = $_GET['range'] ?? 'last_7d';
$startDate = $_GET['start'] ?? '';
$endDate = $_GET['end'] ?? '';

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

if ($range === 'today') {
    $startDate = $today;
    $endDate = $today;
} elseif ($range === 'yesterday') {
    $startDate = $yesterday;
    $endDate = $yesterday;
} elseif ($range === 'last_7d') {
    $startDate = date('Y-m-d', strtotime('-6 days'));
    $endDate = $today;
} elseif ($range === 'this_month') {
    $startDate = date('Y-m-01');
    $endDate = $today;
} elseif ($range === 'all') {
    $startDate = !empty($allDates) ? end($allDates) : date('Y-m-01');
    $endDate = $today;
}

// Filter dataset berdasarkan tanggal
$filteredRows = [];
foreach ($allDates as $d) {
    if ($startDate && $d < $startDate) continue;
    if ($endDate && $d > $endDate) continue;

    $ad = $adsByDate[$d] ?? ['spend_raw' => 0, 'tax_amount' => 0, 'total_ad_cost' => 0, 'tax_notes' => '-'];
    
    // Prioritas data omzet: dari query database transaksi
    // Jika tidak ada di DB lokal, fallback ke data historis terverifikasi di JSON
    $crmSales = 0;
    $crmOmzet = 0.0;
    if (isset($crmByDate[$d])) {
        $crmSales = $crmByDate[$d]['sales'];
        $crmOmzet = $crmByDate[$d]['omzet'];
    } elseif (isset($adsByDate[$d]['sales_completed'])) {
        $crmSales = (int)$adsByDate[$d]['sales_completed'];
        $crmOmzet = (float)($adsByDate[$d]['omzet_riil'] ?? 0);
    }

    $spend = (float)($ad['spend_raw'] ?? 0);
    $tax = (float)($ad['tax_amount'] ?? 0);
    $totalCost = $spend + $tax;
    $netProfit = $crmOmzet - $totalCost;
    $roas = $totalCost > 0 ? round($crmOmzet / $totalCost, 2) : 0.0;

    $filteredRows[] = [
        'date' => $d,
        'spend_raw' => $spend,
        'tax_amount' => $tax,
        'total_ad_cost' => $totalCost,
        'tax_notes' => $ad['tax_notes'] ?? '-',
        'sales_completed' => $crmSales,
        'omzet_riil' => $crmOmzet,
        'roas' => $roas,
        'net_profit' => $netProfit
    ];
}

// 5. Agregasi Total untuk Card Atas
$totSpend = 0;
$totTax = 0;
$totCost = 0;
$totSales = 0;
$totOmzet = 0;
$totProfit = 0;

foreach ($filteredRows as $fr) {
    $totSpend += $fr['spend_raw'];
    $totTax += $fr['tax_amount'];
    $totCost += $fr['total_ad_cost'];
    $totSales += $fr['sales_completed'];
    $totOmzet += $fr['omzet_riil'];
    $totProfit += $fr['net_profit'];
}

$filteredROAS = $totCost > 0 ? round($totOmzet / $totCost, 2) : 0.0;
$profitMargin = $totOmzet > 0 ? round(($totProfit / $totOmzet) * 100, 1) : 0;

// Status Keputusan Global (Sesuai Filter)
if ($totCost == 0) {
    $evalStatus = "Organik / Tanpa Iklan";
    $evalBadgeClass = "badge-subtle";
} elseif ($filteredROAS >= 1.5) {
    $evalStatus = "Scale (Performa Prima)";
    $evalBadgeClass = "badge-success";
} elseif ($filteredROAS >= 1.0) {
    $evalStatus = "Optimize (Margin Tipis)";
    $evalBadgeClass = "badge-warning";
} else {
    $evalStatus = "Evaluasi / Rem (Boncos)";
    $evalBadgeClass = "badge-danger";
}

// 6. Data untuk Grafik (Kronologis Ascending)
$chartRows = $filteredRows;
usort($chartRows, function($a, $b) {
    return strcmp($a['date'], $b['date']);
});

$chartLabels = [];
$chartOmzet = [];
$chartCost = [];

foreach ($chartRows as $cr) {
    $chartLabels[] = date('d M', strtotime($cr['date']));
    $chartOmzet[] = (float)$cr['omzet_riil'];
    $chartCost[] = (float)$cr['total_ad_cost'];
}
?>

<div class="main-content dashboard-wrapper flex-grow-1">

    <!-- Header & Filter Bar -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-3">
        <div>
            <h1 class="dash-title d-flex align-items-center gap-2 mb-1" style="font-size: 1.25rem; font-weight: 700; color: #0F172A;">
                Analisa Iklan & ROAS
            </h1>
            <div style="font-size: 0.8rem; color: #64748B;">
                Sinkronisasi belanja iklan vs omzet otomatis dari pesanan selesai PixelCRM.
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1 font-tabular" onclick="openEditModal('<?= $yesterday ?>', '', '')" style="border-radius: 6px; font-weight: 500; font-size: 0.8rem; padding: 6px 12px;">
                <i class="fas fa-edit"></i> Input Iklan & Pajak
            </button>
        </div>
    </div>

    <!-- Filter Pills (Tactile & Fast) -->
    <div class="card p-2 mb-3" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 10px;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap align-items-center gap-1">
                <a href="?range=today" class="filter-pill <?= $range === 'today' ? 'active' : '' ?>">Hari Ini</a>
                <a href="?range=yesterday" class="filter-pill <?= $range === 'yesterday' ? 'active' : '' ?>">Kemarin</a>
                <a href="?range=last_7d" class="filter-pill <?= $range === 'last_7d' ? 'active' : '' ?>">7 Hari</a>
                <a href="?range=this_month" class="filter-pill <?= $range === 'this_month' ? 'active' : '' ?>">Bulan Ini</a>
                <a href="?range=all" class="filter-pill <?= $range === 'all' ? 'active' : '' ?>">Semua Data</a>
            </div>

            <form method="GET" action="" class="d-flex align-items-center gap-2" style="font-size: 0.8rem;">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>" class="form-control form-control-sm font-tabular" style="width: 125px; font-size: 0.75rem; border-radius: 6px;">
                <span style="color: #94A3B8;">-</span>
                <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>" class="form-control form-control-sm font-tabular" style="width: 125px; font-size: 0.75rem; border-radius: 6px;">
                <button type="submit" class="btn btn-light btn-sm" style="border: 1px solid #CBD5E1; border-radius: 6px; font-size: 0.75rem; padding: 4px 10px;">Filter</button>
            </form>
        </div>
    </div>

    <?php if ($notice): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 mb-3" style="border-radius: 8px; font-size: 0.8rem;">
        <i class="fas fa-check-circle"></i>
        <span><?= htmlspecialchars($notice) ?></span>
    </div>
    <?php endif; ?>

    <!-- KPI Summary Cards (Restrained Typography & Dynamic Status) -->
    <div class="row g-2 mb-3">
        <!-- 1. Status Evaluasi (Dynamic by Filter) -->
        <div class="col-6 col-lg-3">
            <div class="kpi-mini-card">
                <div class="kpi-label">Status Evaluasi</div>
                <div class="mt-1">
                    <span class="<?= $evalBadgeClass ?>" style="font-size: 0.75rem; font-weight: 600; padding: 3px 8px; border-radius: 4px; display: inline-block;">
                        <?= $evalStatus ?>
                    </span>
                </div>
                <div class="kpi-sub mt-2">Berdasarkan data filter terpilih</div>
            </div>
        </div>

        <!-- 2. Total Modal Iklan (+Pajak) -->
        <div class="col-6 col-lg-3">
            <div class="kpi-mini-card">
                <div class="kpi-label">Biaya Iklan (+Pajak)</div>
                <div class="kpi-val font-tabular">
                    Rp <?= number_format($totCost, 0, ',', '.') ?>
                </div>
                <div class="kpi-sub mt-1">
                    Iklan: <?= number_format($totSpend, 0, ',', '.') ?> • Pajak: <?= number_format($totTax, 0, ',', '.') ?>
                </div>
            </div>
        </div>

        <!-- 3. Omzet Riil CRM -->
        <div class="col-6 col-lg-3">
            <div class="kpi-mini-card">
                <div class="kpi-label">Omzet Riil (CRM Auto)</div>
                <div class="kpi-val font-tabular" style="color: #059669;">
                    Rp <?= number_format($totOmzet, 0, ',', '.') ?>
                </div>
                <div class="kpi-sub mt-1">
                    Dari <strong><?= $totSales ?> transaksi</strong> selesai
                </div>
            </div>
        </div>

        <!-- 4. Laba Bersih & ROAS -->
        <div class="col-6 col-lg-3">
            <div class="kpi-mini-card">
                <div class="kpi-label">Laba Bersih & ROAS</div>
                <div class="kpi-val font-tabular d-flex align-items-baseline gap-2">
                    <span style="color: <?= $totProfit >= 0 ? '#059669' : '#DC2626' ?>;">
                        <?= $totProfit >= 0 ? '+' : '' ?>Rp <?= number_format($totProfit, 0, ',', '.') ?>
                    </span>
                    <span style="font-size: 0.8rem; font-weight: 600; color: #475569;">
                        (<?= number_format($filteredROAS, 2) ?>x)
                    </span>
                </div>
                <div class="kpi-sub mt-1">
                    Margin laba: <strong><?= $profitMargin ?>%</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Omzet vs Biaya Iklan -->
    <div class="card p-3 mb-3" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px;">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div style="font-size: 0.85rem; font-weight: 600; color: #0F172A;">
                Tren Harian: Omzet vs Biaya Iklan (+Pajak)
            </div>
            <div class="d-flex align-items-center gap-3" style="font-size: 0.75rem;">
                <span class="d-flex align-items-center gap-1"><span style="width: 8px; height: 8px; border-radius: 2px; background: #10B981;"></span> Omzet</span>
                <span class="d-flex align-items-center gap-1"><span style="width: 8px; height: 8px; border-radius: 2px; background: #0F172A;"></span> Biaya Iklan</span>
            </div>
        </div>
        <div style="height: 200px; position: relative;">
            <canvas id="dailyAdsChart"></canvas>
        </div>
    </div>

    <!-- Editorial Table (Dense, Clean, No Slop) -->
    <div class="card" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead style="background: #F8FAFC; color: #475569; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    <tr>
                        <th class="ps-3 py-2">Tanggal</th>
                        <th class="py-2">Spend Mentah</th>
                        <th class="py-2">Pajak (Manual)</th>
                        <th class="py-2">Total Modal</th>
                        <th class="py-2 text-center">Sales (Auto)</th>
                        <th class="py-2">Omzet (Auto)</th>
                        <th class="py-2">ROAS</th>
                        <th class="py-2">Laba Bersih</th>
                        <th class="pe-3 py-2 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody style="border-top: none;">
                    <?php if (empty($filteredRows)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted" style="font-size: 0.85rem;">
                            Tidak ada data transaksi atau iklan pada rentang tanggal ini.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($filteredRows as $row): 
                        $roas = (float)$row['roas'];
                        $profit = (float)$row['net_profit'];
                        $tax = (float)$row['tax_amount'];
                    ?>
                    <tr>
                        <td class="ps-3 py-2 font-tabular" style="font-weight: 600; color: #0F172A;">
                            <?= date('d M Y', strtotime($row['date'])) ?>
                            <?php if (!empty($row['tax_notes']) && $row['tax_notes'] !== '-'): ?>
                                <span style="font-size: 0.7rem; font-weight: 400; color: #94A3B8; display: block;"><?= htmlspecialchars($row['tax_notes']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2 font-tabular" style="color: #334155;">
                            Rp <?= number_format($row['spend_raw'], 0, ',', '.') ?>
                        </td>
                        <td class="py-2 font-tabular" style="color: <?= $tax > 0 ? '#DC2626' : '#10B981' ?>;">
                            Rp <?= number_format($tax, 0, ',', '.') ?>
                        </td>
                        <td class="py-2 font-tabular" style="font-weight: 600; color: #0F172A;">
                            Rp <?= number_format($row['total_ad_cost'], 0, ',', '.') ?>
                        </td>
                        <td class="py-2 font-tabular text-center">
                            <span style="font-weight: 600; color: #0F172A;"><?= $row['sales_completed'] ?></span>
                        </td>
                        <td class="py-2 font-tabular" style="font-weight: 600; color: #059669;">
                            Rp <?= number_format($row['omzet_riil'], 0, ',', '.') ?>
                        </td>
                        <td class="py-2 font-tabular" style="font-weight: 600; color: <?= $roas >= 1.5 ? '#059669' : ($roas >= 1.0 ? '#D97706' : '#DC2626') ?>;">
                            <?= number_format($roas, 2) ?>x
                        </td>
                        <td class="py-2 font-tabular" style="font-weight: 600; color: <?= $profit >= 0 ? '#059669' : '#DC2626' ?>;">
                            <?= $profit >= 0 ? '+' : '' ?>Rp <?= number_format($profit, 0, ',', '.') ?>
                        </td>
                        <td class="pe-3 py-2 text-end">
                            <button type="button" class="btn btn-sm btn-link text-muted p-0" onclick="openEditModal('<?= $row['date'] ?>', '<?= $row['spend_raw'] ?>', '<?= $row['tax_amount'] ?>', '<?= addslashes($row['tax_notes']) ?>')" title="Koreksi Biaya Iklan & Pajak">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit Biaya Iklan & Pajak (Hanya 2 Nilai Ini yang Diedit) -->
<div id="adModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 16px;">
    <div class="modal-box" style="background: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; width: 100%; max-width: 400px; padding: 20px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div style="font-weight: 700; font-size: 0.95rem; color: #0F172A;">Input / Edit Iklan & Pajak</div>
            <button type="button" class="btn-close" onclick="closeEditModal()" style="font-size: 0.75rem;"></button>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="save_ad_cost">
            
            <div class="mb-2">
                <label style="font-size: 0.75rem; font-weight: 600; color: #475569;">Tanggal</label>
                <input type="date" name="date" id="mDate" class="form-control form-control-sm font-tabular" required style="border-radius: 6px;">
            </div>

            <div class="mb-2">
                <label style="font-size: 0.75rem; font-weight: 600; color: #475569;">Spend Iklan Mentah (Rp)</label>
                <input type="number" name="spend_raw" id="mSpend" class="form-control form-control-sm font-tabular" placeholder="Contoh: 32652" required style="border-radius: 6px;">
            </div>

            <div class="mb-2">
                <label style="font-size: 0.75rem; font-weight: 600; color: #475569;">Pajak Riil (Rp)</label>
                <input type="number" name="tax_amount" id="mTax" class="form-control form-control-sm font-tabular" placeholder="0" value="0" required style="border-radius: 6px;">
                <div style="font-size: 0.7rem; color: #94A3B8; margin-top: 2px;">Isi 0 jika pakai akun HK bebas pajak, atau isi nominal PPN akun ID.</div>
            </div>

            <div class="mb-3">
                <label style="font-size: 0.75rem; font-weight: 600; color: #475569;">Catatan (Opsional)</label>
                <input type="text" name="tax_notes" id="mNotes" class="form-control form-control-sm" placeholder="Contoh: Akun HK atau Akun ID" style="border-radius: 6px;">
            </div>

            <div class="d-flex align-items-center justify-content-end gap-2">
                <button type="button" class="btn btn-light btn-sm" onclick="closeEditModal()" style="border-radius: 6px; font-size: 0.8rem;">Batal</button>
                <button type="submit" class="btn btn-dark btn-sm" style="border-radius: 6px; font-size: 0.8rem; padding: 4px 14px;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('dailyAdsChart').getContext('2d');
    const labels = <?= json_encode($chartLabels) ?>;
    const omzetData = <?= json_encode($chartOmzet) ?>;
    const costData = <?= json_encode($chartCost) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Omzet Riil',
                    data: omzetData,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    fill: true,
                    tension: 0.2,
                    borderWidth: 2,
                    pointRadius: 3
                },
                {
                    label: 'Biaya Iklan+Pajak',
                    data: costData,
                    borderColor: '#0F172A',
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.2,
                    borderWidth: 1.5,
                    pointRadius: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 8,
                    cornerRadius: 6,
                    callbacks: {
                        label: function(c) {
                            return c.dataset.label + ': Rp ' + Number(c.parsed.y).toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: {
                    grid: { color: '#F1F5F9' },
                    ticks: {
                        font: { size: 10 },
                        callback: function(v) { return v >= 1000 ? (v/1000) + 'k' : v; }
                    }
                }
            }
        }
    });
});

function openEditModal(date, spend, tax, notes) {
    document.getElementById('mDate').value = date || '';
    document.getElementById('mSpend').value = spend || '';
    document.getElementById('mTax').value = tax !== undefined && tax !== '' ? tax : '0';
    document.getElementById('mNotes').value = notes || '';
    document.getElementById('adModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('adModal').style.display = 'none';
}
</script>

<style>
.font-tabular {
    font-feature-settings: "tnum" 1, "cv05" 1;
    font-variant-numeric: tabular-nums;
}
.kpi-mini-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 12px 14px;
}
.kpi-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748B;
}
.kpi-val {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0F172A;
    margin-top: 2px;
}
.kpi-sub {
    font-size: 0.72rem;
    color: #94A3B8;
}
.filter-pill {
    font-size: 0.78rem;
    font-weight: 500;
    color: #475569;
    padding: 4px 10px;
    border-radius: 6px;
    text-decoration: none;
    transition: background-color 140ms ease-out, color 140ms ease-out;
}
.filter-pill:hover {
    background: #F1F5F9;
    color: #0F172A;
}
.filter-pill.active {
    background: #0F172A;
    color: #FFFFFF;
}
.badge-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
.badge-warning { background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; }
.badge-danger  { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
.badge-subtle  { background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; }

button:active {
    transform: scale(0.97) !important;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
