<?php
$page_title = "Analisa Iklan & ROAS";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$jsonFile = __DIR__ . '/../../data/ads_performance.json';

// Pastikan file JSON ada
if (!file_exists($jsonFile)) {
    file_put_contents($jsonFile, json_encode(['updated_at' => date('Y-m-d H:i:s'), 'records' => []], JSON_PRETTY_PRINT));
}

$adData = json_decode(file_get_contents($jsonFile), true);
$records = $adData['records'] ?? [];

// Handle Form Submission Manual (Input / Koreksi Data)
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_record') {
    $date = trim($_POST['date'] ?? '');
    $spend_raw = (float)str_replace(['.', ','], ['', '.'], $_POST['spend_raw'] ?? 0);
    $tax_amount = (float)str_replace(['.', ','], ['', '.'], $_POST['tax_amount'] ?? 0);
    $tax_notes = trim($_POST['tax_notes'] ?? '');
    
    // Ambil data sales riil dari database PixelCRM jika ada, atau dari input form
    $sales_input = isset($_POST['sales_completed']) && $_POST['sales_completed'] !== '' ? (int)$_POST['sales_completed'] : null;
    $omzet_input = isset($_POST['omzet_riil']) && $_POST['omzet_riil'] !== '' ? (float)str_replace(['.', ','], ['', '.'], $_POST['omzet_riil']) : null;
    
    $sales_completed = $sales_input ?? 0;
    $omzet_riil = $omzet_input ?? 0;

    // Coba hitung dari DB jika koneksi aktif dan input tidak manual
    if (($sales_input === null || $omzet_input === null) && function_exists('db')) {
        try {
            $conn = db();
            if ($conn && !$conn->connect_error) {
                $q = $conn->query("SELECT COUNT(*) as jml, COALESCE(SUM(total_amount), 0) as omzet FROM transaksi WHERE status = 'selesai' AND DATE(created_at) = '{$conn->real_escape_string($date)}'");
                if ($q && $r = $q->fetch_assoc()) {
                    if ($sales_input === null) $sales_completed = (int)$r['jml'];
                    if ($omzet_input === null) $omzet_riil = (float)$r['omzet'];
                }
            }
        } catch (Exception $e) {
            // fallback gracefully
        }
    }

    if ($date) {
        $total_ad_cost = $spend_raw + $tax_amount;
        $net_profit = $omzet_riil - $total_ad_cost;
        $roas = $total_ad_cost > 0 ? round($omzet_riil / $total_ad_cost, 2) : 0;
        $cpa = $sales_completed > 0 ? round($total_ad_cost / $sales_completed) : 0;
        
        $status = 'warning';
        if ($roas >= 1.5) {
            $status = 'success';
        } elseif ($roas < 1.0) {
            $status = 'danger';
        }

        $newRecord = [
            'date' => $date,
            'spend_raw' => $spend_raw,
            'tax_amount' => $tax_amount,
            'total_ad_cost' => $total_ad_cost,
            'sales_completed' => $sales_completed,
            'omzet_riil' => $omzet_riil,
            'cpa' => $cpa,
            'roas' => $roas,
            'net_profit' => $net_profit,
            'tax_notes' => $tax_notes ?: ($tax_amount > 0 ? "Pajak Diinput Manual" : "Bebas Pajak"),
            'status' => $status
        ];

        // Update atau tambahkan
        $found = false;
        foreach ($records as $idx => $rec) {
            if ($rec['date'] === $date) {
                $records[$idx] = $newRecord;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $records[] = $newRecord;
        }

        // Urutkan kembali berdasarkan tanggal descending
        usort($records, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        $adData['updated_at'] = date('Y-m-d H:i:s');
        $adData['records'] = $records;
        file_put_contents($jsonFile, json_encode($adData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $message = "Data tanggal {$date} berhasil disimpan!";
        $messageType = "success";
    }
}

// Urutkan untuk tampilan (descending: terbaru di atas)
usort($records, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

// Hitung total ringkasan
$tot_spend_raw = 0;
$tot_tax = 0;
$tot_ad_cost = 0;
$tot_omzet = 0;
$tot_sales = 0;
$tot_profit = 0;

foreach ($records as $r) {
    $tot_spend_raw += $r['spend_raw'] ?? 0;
    $tot_tax += $r['tax_amount'] ?? 0;
    $tot_ad_cost += $r['total_ad_cost'] ?? 0;
    $tot_omzet += $r['omzet_riil'] ?? 0;
    $tot_sales += $r['sales_completed'] ?? 0;
    $tot_profit += $r['net_profit'] ?? 0;
}

$overall_roas = $tot_ad_cost > 0 ? round($tot_omzet / $tot_ad_cost, 2) : 0;
$overall_cpa = $tot_sales > 0 ? round($tot_ad_cost / $tot_sales) : 0;
$profit_margin = $tot_omzet > 0 ? round(($tot_profit / $tot_omzet) * 100, 1) : 0;

// Data untuk Chart (diurutkan kronologis ascending)
$chartRecords = $records;
usort($chartRecords, function($a, $b) {
    return strcmp($a['date'], $b['date']);
});

$chartLabels = [];
$chartOmzet = [];
$chartCost = [];
$chartProfit = [];

foreach ($chartRecords as $cr) {
    $chartLabels[] = date('d M', strtotime($cr['date']));
    $chartOmzet[] = (float)($cr['omzet_riil'] ?? 0);
    $chartCost[] = (float)($cr['total_ad_cost'] ?? 0);
    $chartProfit[] = (float)($cr['net_profit'] ?? 0);
}
?>

<div class="main-content dashboard-wrapper flex-grow-1">
    
    <!-- Top Header -->
    <div class="dash-header flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="dash-title d-flex align-items-center gap-2 mb-0">
                    <i class="fas fa-bullseye text-primary"></i> Analisa Iklan & ROAS
                </h1>
                <span class="badge" style="background: #EFF6FF; color: #1E40AF; border: 1px solid #BFDBFE; font-size: 0.75rem; font-weight: 600; padding: 4px 8px; border-radius: 6px;">
                    Meta vs PixelCRM
                </span>
            </div>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">
                Sinkronisasi belanja iklan harian, beban pajak riil, dan laba bersih penjualan.
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" onclick="openInputModal()" style="border-radius: 8px; font-weight: 600; padding: 10px 16px; transition: transform 160ms cubic-bezier(0.16, 1, 0.3, 1);">
                <i class="fas fa-plus"></i> Input / Koreksi H-1
            </button>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> d-flex align-items-center gap-2 mb-4" style="border-radius: 12px; border: 1px solid #A7F3D0; background: #ECFDF5; color: #065F46; padding: 12px 16px;">
        <i class="fas fa-check-circle"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
    <?php endif; ?>

    <!-- 4 KPI Cards (Tactile & Clean) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Belanja Iklan -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size: 0.85rem; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">Total Modal Iklan</span>
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #F1F5F9; display: flex; align-items: center; justify-content: center; color: #475569;">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
                <div class="metric-number" style="font-size: 1.65rem; font-weight: 700; color: #0F172A; font-feature-settings: 'tnum' 1; font-variant-numeric: tabular-nums;">
                    Rp <?= number_format($tot_ad_cost, 0, ',', '.') ?>
                </div>
                <div class="d-flex align-items-center gap-2 mt-2" style="font-size: 0.8rem; color: #64748B;">
                    <span>Iklan: Rp <?= number_format($tot_spend_raw, 0, ',', '.') ?></span>
                    <span>•</span>
                    <span style="color: <?= $tot_tax > 0 ? '#DC2626' : '#10B981' ?>;">Pajak: Rp <?= number_format($tot_tax, 0, ',', '.') ?></span>
                </div>
            </div>
        </div>

        <!-- 2. Omzet Penjualan Riil -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size: 0.85rem; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">Omzet Riil CRM</span>
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #ECFDF5; display: flex; align-items: center; justify-content: center; color: #059669;">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <div class="metric-number" style="font-size: 1.65rem; font-weight: 700; color: #059669; font-feature-settings: 'tnum' 1; font-variant-numeric: tabular-nums;">
                    Rp <?= number_format($tot_omzet, 0, ',', '.') ?>
                </div>
                <div class="mt-2" style="font-size: 0.8rem; color: #64748B;">
                    Dari <strong><?= $tot_sales ?> pesanan lunas</strong> terverifikasi
                </div>
            </div>
        </div>

        <!-- 3. Laba Bersih Iklan (Net Profit) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size: 0.85rem; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">Laba Bersih Iklan</span>
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: <?= $tot_profit >= 0 ? '#ECFDF5' : '#FEF2F2' ?>; display: flex; align-items: center; justify-content: center; color: <?= $tot_profit >= 0 ? '#059669' : '#DC2626' ?>;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="metric-number" style="font-size: 1.65rem; font-weight: 700; color: <?= $tot_profit >= 0 ? '#0F172A' : '#DC2626' ?>; font-feature-settings: 'tnum' 1; font-variant-numeric: tabular-nums;">
                    <?= $tot_profit >= 0 ? '+Rp ' : '-Rp ' ?><?= number_format(abs($tot_profit), 0, ',', '.') ?>
                </div>
                <div class="mt-2" style="font-size: 0.8rem; color: #64748B;">
                    Margin laba bersih: <strong style="color: <?= $tot_profit >= 0 ? '#059669' : '#DC2626' ?>;"><?= $profit_margin ?>%</strong>
                </div>
            </div>
        </div>

        <!-- 4. ROAS & CPA Riil -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="kpi-card" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size: 0.85rem; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">ROAS & CPA Riil</span>
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #EFF6FF; display: flex; align-items: center; justify-content: center; color: #2563EB;">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
                <div class="metric-number d-flex align-items-baseline gap-2" style="font-size: 1.65rem; font-weight: 700; color: #0F172A; font-feature-settings: 'tnum' 1; font-variant-numeric: tabular-nums;">
                    <span><?= number_format($overall_roas, 2) ?>x</span>
                    <span style="font-size: 0.85rem; font-weight: 600; color: #64748B;">(ROAS)</span>
                </div>
                <div class="mt-2" style="font-size: 0.8rem; color: #64748B;">
                    Rata-rata CPA: <strong>Rp <?= number_format($overall_cpa, 0, ',', '.') ?></strong> / sales
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Visual Section (Chart.js) -->
    <div class="card mb-4" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #0F172A; margin: 0;">Tren Harian: Omzet vs Biaya Iklan (+Pajak)</h3>
                <p style="font-size: 0.85rem; color: #64748B; margin: 4px 0 0 0;">Area hijau di atas garis hitam menandakan zona surplus profit.</p>
            </div>
            <div class="d-flex align-items-center gap-3" style="font-size: 0.85rem; font-weight: 600;">
                <span class="d-flex align-items-center gap-1"><span style="width: 12px; height: 12px; border-radius: 3px; background: #10B981; display: inline-block;"></span> Omzet</span>
                <span class="d-flex align-items-center gap-1"><span style="width: 12px; height: 12px; border-radius: 3px; background: #0F172A; display: inline-block;"></span> Biaya Iklan+Pajak</span>
            </div>
        </div>
        <div style="height: 280px; position: relative;">
            <canvas id="adsPerformanceChart"></canvas>
        </div>
    </div>

    <!-- Detail Editorial Table -->
    <div class="card" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        <div class="p-4 d-flex align-items-center justify-content-between border-bottom" style="border-color: #F1F5F9 !important;">
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 700; color: #0F172A; margin: 0;">Rekapitulasi Harian Lengkap</h3>
                <p style="font-size: 0.85rem; color: #64748B; margin: 2px 0 0 0;">Catatan belanja iklan, potongan pajak riil, dan konversi PixelCRM.</p>
            </div>
            <div style="font-size: 0.8rem; color: #94A3B8;">
                Update terakhir: <?= htmlspecialchars($adData['updated_at'] ?? '-') ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                <thead style="background: #F8FAFC; color: #475569; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                    <tr>
                        <th class="ps-4 py-3">Tanggal</th>
                        <th class="py-3">Spend Mentah</th>
                        <th class="py-3">Pajak (Manual)</th>
                        <th class="py-3">Total Biaya</th>
                        <th class="py-3 text-center">Sales</th>
                        <th class="py-3">Omzet Riil</th>
                        <th class="py-3">CPA Riil</th>
                        <th class="py-3">ROAS</th>
                        <th class="py-3">Laba Bersih</th>
                        <th class="py-3">Status</th>
                        <th class="pe-4 py-3 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody style="border-top: none;">
                    <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x mb-3 d-block text-secondary"></i>
                            Belum ada catatan data iklan. Klik tombol "Input / Koreksi H-1" untuk memulai.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($records as $row): 
                        $roas = (float)($row['roas'] ?? 0);
                        $profit = (float)($row['net_profit'] ?? 0);
                        $tax = (float)($row['tax_amount'] ?? 0);
                    ?>
                    <tr style="transition: background-color 140ms ease-out;">
                        <td class="ps-4 py-3 font-tabular" style="font-weight: 600; color: #0F172A;">
                            <?= date('d M Y', strtotime($row['date'])) ?>
                            <div style="font-size: 0.75rem; font-weight: 400; color: #94A3B8;"><?= htmlspecialchars($row['tax_notes'] ?? '') ?></div>
                        </td>
                        <td class="py-3 font-tabular" style="color: #475569;">
                            Rp <?= number_format($row['spend_raw'], 0, ',', '.') ?>
                        </td>
                        <td class="py-3 font-tabular" style="color: <?= $tax > 0 ? '#DC2626' : '#10B981' ?>; font-weight: 500;">
                            Rp <?= number_format($tax, 0, ',', '.') ?>
                        </td>
                        <td class="py-3 font-tabular" style="font-weight: 700; color: #0F172A;">
                            Rp <?= number_format($row['total_ad_cost'], 0, ',', '.') ?>
                        </td>
                        <td class="py-3 font-tabular text-center" style="font-weight: 600;">
                            <span class="badge" style="background: #F1F5F9; color: #334155; border-radius: 6px; padding: 4px 8px;">
                                <?= $row['sales_completed'] ?> order
                            </span>
                        </td>
                        <td class="py-3 font-tabular" style="font-weight: 700; color: #059669;">
                            Rp <?= number_format($row['omzet_riil'], 0, ',', '.') ?>
                        </td>
                        <td class="py-3 font-tabular" style="color: #475569;">
                            Rp <?= number_format($row['cpa'], 0, ',', '.') ?>
                        </td>
                        <td class="py-3 font-tabular" style="font-weight: 700; color: <?= $roas >= 1.5 ? '#059669' : ($roas >= 1.0 ? '#D97706' : '#DC2626') ?>;">
                            <?= number_format($roas, 2) ?>x
                        </td>
                        <td class="py-3 font-tabular" style="font-weight: 700; color: <?= $profit >= 0 ? '#059669' : '#DC2626' ?>;">
                            <?= $profit >= 0 ? '+' : '' ?>Rp <?= number_format($profit, 0, ',', '.') ?>
                        </td>
                        <td class="py-3">
                            <?php if ($roas >= 1.5): ?>
                                <span class="badge" style="background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; border-radius: 6px; padding: 4px 8px; font-size: 0.75rem;">🟢 Scale</span>
                            <?php elseif ($roas >= 1.0): ?>
                                <span class="badge" style="background: #FFFBEB; color: #92400E; border: 1px solid #FDE68A; border-radius: 6px; padding: 4px 8px; font-size: 0.75rem;">🟡 Tipis</span>
                            <?php else: ?>
                                <span class="badge" style="background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; border-radius: 6px; padding: 4px 8px; font-size: 0.75rem;">🔴 Boncos</span>
                            <?php endif; ?>
                        </td>
                        <td class="pe-4 py-3 text-end">
                            <button type="button" class="btn btn-sm btn-light" style="border: 1px solid #E2E8F0; border-radius: 6px; padding: 4px 8px; font-size: 0.8rem;" onclick='editRecord(<?= json_encode($row) ?>)'>
                                <i class="fas fa-edit"></i>
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

<!-- Modal Input / Edit Record (Emil Kowalski Tactile Micro-Modal) -->
<div id="inputModal" class="modal-backdrop-custom" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 1050; align-items: center; justify-content: center; padding: 16px;">
    <div class="modal-panel-custom" style="background: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; width: 100%; max-width: 480px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); transform: scale(0.96); opacity: 0; transition: transform 160ms cubic-bezier(0.16, 1, 0.3, 1), opacity 160ms ease-out;">
        <div class="p-4 border-bottom d-flex align-items-center justify-content-between" style="border-color: #F1F5F9 !important;">
            <h5 class="mb-0" style="font-weight: 700; font-size: 1.1rem; color: #0F172A;" id="modalTitle">Input / Koreksi Data Iklan</h5>
            <button type="button" class="btn-close" onclick="closeInputModal()"></button>
        </div>
        <form method="POST" action="" class="p-4">
            <input type="hidden" name="action" value="save_record">
            
            <div class="mb-3">
                <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #334155;">Tanggal Laporan</label>
                <input type="date" name="date" id="formDate" class="form-control" required style="border-radius: 8px; border-color: #CBD5E1;" value="<?= date('Y-m-d', strtotime('-1 day')) ?>">
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #334155;">Spend Iklan Mentah (Rp)</label>
                    <input type="number" name="spend_raw" id="formSpend" class="form-control font-tabular" placeholder="32652" required style="border-radius: 8px; border-color: #CBD5E1;">
                </div>
                <div class="col-6">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #334155;">Pajak Riil (Rp)</label>
                    <input type="number" name="tax_amount" id="formTax" class="form-control font-tabular" placeholder="0" value="0" required style="border-radius: 8px; border-color: #CBD5E1;">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #334155;">Catatan Akun / Pajak</label>
                <input type="text" name="tax_notes" id="formNotes" class="form-control" placeholder="Contoh: Akun HK (0%) atau Akun ID (PPN 11%)" style="border-radius: 8px; border-color: #CBD5E1;">
                <div class="form-text" style="font-size: 0.75rem;">Jika jalan 2 akun, masukkan total pajak yang riil Anda bayarkan di akun bertarif pajak.</div>
            </div>

            <div class="row g-2 mb-4">
                <div class="col-6">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #334155;">Order Selesai (Opsional)</label>
                    <input type="number" name="sales_completed" id="formSales" class="form-control font-tabular" placeholder="Kosongkan jika auto-CRM" style="border-radius: 8px; border-color: #CBD5E1;">
                </div>
                <div class="col-6">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: #334155;">Omzet Riil (Opsional)</label>
                    <input type="number" name="omzet_riil" id="formOmzet" class="form-control font-tabular" placeholder="Kosongkan jika auto-CRM" style="border-radius: 8px; border-color: #CBD5E1;">
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-end gap-2">
                <button type="button" class="btn btn-light" onclick="closeInputModal()" style="border-radius: 8px; font-weight: 600;">Batal</button>
                <button type="submit" class="btn btn-primary" style="border-radius: 8px; font-weight: 600; padding: 8px 20px;">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart.js initialization with Emil Kowalski restrained aesthetics
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('adsPerformanceChart').getContext('2d');
    
    const labels = <?= json_encode($chartLabels) ?>;
    const omzetData = <?= json_encode($chartOmzet) ?>;
    const costData = <?= json_encode($chartCost) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Omzet Riil CRM',
                    data: omzetData,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.08)',
                    fill: true,
                    tension: 0.25,
                    borderWidth: 2.5,
                    pointBackgroundColor: '#10B981',
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Biaya Iklan + Pajak',
                    data: costData,
                    borderColor: '#0F172A',
                    backgroundColor: 'rgba(15, 23, 42, 0.03)',
                    fill: false,
                    tension: 0.25,
                    borderWidth: 2,
                    pointBackgroundColor: '#0F172A',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0F172A',
                    titleColor: '#F8FAFC',
                    bodyColor: '#F8FAFC',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: { size: 12, weight: 'bold' },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': Rp ' + Number(context.parsed.y).toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748B', font: { size: 11 } }
                },
                y: {
                    grid: { color: '#F1F5F9' },
                    ticks: {
                        color: '#64748B',
                        font: { size: 11 },
                        callback: function(value) {
                            if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return (value / 1000).toFixed(0) + 'k';
                            return value;
                        }
                    }
                }
            }
        }
    });
});

// Modal Logic
function openInputModal() {
    const modal = document.getElementById('inputModal');
    const panel = modal.querySelector('.modal-panel-custom');
    modal.style.display = 'flex';
    setTimeout(() => {
        panel.style.transform = 'scale(1)';
        panel.style.opacity = '1';
    }, 10);
}

function closeInputModal() {
    const modal = document.getElementById('inputModal');
    const panel = modal.querySelector('.modal-panel-custom');
    panel.style.transform = 'scale(0.96)';
    panel.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
        document.getElementById('modalTitle').textContent = 'Input / Koreksi Data Iklan';
        document.getElementById('formSpend').value = '';
        document.getElementById('formTax').value = '0';
        document.getElementById('formNotes').value = '';
        document.getElementById('formSales').value = '';
        document.getElementById('formOmzet').value = '';
    }, 160);
}

function editRecord(data) {
    document.getElementById('modalTitle').textContent = 'Koreksi Data: ' + data.date;
    document.getElementById('formDate').value = data.date;
    document.getElementById('formSpend').value = data.spend_raw;
    document.getElementById('formTax').value = data.tax_amount;
    document.getElementById('formNotes').value = data.tax_notes || '';
    document.getElementById('formSales').value = data.sales_completed || '';
    document.getElementById('formOmzet').value = data.omzet_riil || '';
    openInputModal();
}
</script>

<style>
.font-tabular {
    font-feature-settings: "tnum" 1, "cv05" 1;
    font-variant-numeric: tabular-nums;
}
.kpi-card {
    transition: transform 160ms cubic-bezier(0.16, 1, 0.3, 1), box-shadow 160ms ease-out;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05) !important;
}
button:active {
    transform: scale(0.97) !important;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
