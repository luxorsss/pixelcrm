<?php
// Handle export CSV sebelum output HTML
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$laporan = new LaporanManager();

// Handle filters - simplified + status filter
$filters = [
    'tanggal_dari' => sanitizeInput($_GET['tanggal_dari'] ?? ''),
    'tanggal_sampai' => sanitizeInput($_GET['tanggal_sampai'] ?? ''),
    'produk_id' => (int)($_GET['produk_id'] ?? 0),
    'cari_customer' => sanitizeInput($_GET['cari_customer'] ?? ''),
    'status_filter' => sanitizeInput($_GET['status_filter'] ?? ''), // NEW: Status filter
    'limit' => (int)($_GET['limit'] ?? 20),
    'page' => (int)($_GET['page'] ?? 1)
];

// Handle export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $csv_content = $laporan->exportDetailCSV($filters);
        
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="detail_penjualan_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 01 Jan 1990 00:00:00 GMT');
        
        echo "\xEF\xBB\xBF" . $csv_content;
        exit;
    } catch (Exception $e) {
        setMessage('Error export CSV: ' . $e->getMessage(), 'error');
    }
}

$page_title = "Detail Penjualan & Customer Analytics";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Ambil data dengan error handling
try {
    $report = $laporan->getDetailPenjualan($filters);
    $all_produk = $laporan->getAllProduk();
    
    // Simple customer insights calculation - no complex BI
    $customer_summary = getSimpleCustomerInsights($filters);
} catch (Exception $e) {
    setMessage('Error loading data: ' . $e->getMessage(), 'error');
    $report = ['data' => [], 'total' => 0, 'page' => 1, 'limit' => 20, 'total_pages' => 0];
    $all_produk = [];
    $customer_summary = ['total_customers' => 0, 'new_customers' => 0, 'repeat_customers' => 0];
}

// Helper function untuk customer insights sederhana
function getSimpleCustomerInsights($filters) {
    try {
        $where = ["t.status = 'selesai'"];
        $params = [];
        
        if (!empty($filters['tanggal_dari'])) {
            $where[] = "DATE(t.tanggal_transaksi) >= ?";
            $params[] = $filters['tanggal_dari'];
        }
        if (!empty($filters['tanggal_sampai'])) {
            $where[] = "DATE(t.tanggal_transaksi) <= ?";
            $params[] = $filters['tanggal_sampai'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $sql = "SELECT 
                    COUNT(DISTINCT t.pelanggan_id) as total_customers,
                    COUNT(DISTINCT CASE WHEN DATEDIFF(CURDATE(), p.tanggal_daftar) <= 30 THEN t.pelanggan_id END) as new_customers,
                    COUNT(DISTINCT CASE WHEN customer_tx.tx_count > 1 THEN t.pelanggan_id END) as repeat_customers,
                    COALESCE(AVG(t.total_harga), 0) as avg_transaction_value,
                    COALESCE(SUM(t.total_harga), 0) as total_revenue
                FROM transaksi t
                JOIN pelanggan p ON t.pelanggan_id = p.id
                LEFT JOIN (
                    SELECT pelanggan_id, COUNT(*) as tx_count 
                    FROM transaksi 
                    WHERE status = 'selesai' 
                    GROUP BY pelanggan_id
                ) customer_tx ON t.pelanggan_id = customer_tx.pelanggan_id
                WHERE {$where_clause}";
                
        $result = query($sql, $params);
        $data = $result->fetch_assoc();
        
        return [
            'total_customers' => (int)($data['total_customers'] ?? 0),
            'new_customers' => (int)($data['new_customers'] ?? 0),
            'repeat_customers' => (int)($data['repeat_customers'] ?? 0),
            'avg_transaction_value' => (float)($data['avg_transaction_value'] ?? 0),
            'total_revenue' => (float)($data['total_revenue'] ?? 0)
        ];
    } catch (Exception $e) {
        error_log("Error getSimpleCustomerInsights: " . $e->getMessage());
        return ['total_customers' => 0, 'new_customers' => 0, 'repeat_customers' => 0, 'avg_transaction_value' => 0, 'total_revenue' => 0];
    }
}

// Helper function untuk customer behavior sederhana - FIXED
function getCustomerBehavior($customer_data) {
    $days_since_last = 0;
    
    if (!empty($customer_data['transaksi_terakhir'])) {
        // Pastikan format tanggal benar
        $last_transaction_date = date('Y-m-d', strtotime($customer_data['transaksi_terakhir']));
        $today = date('Y-m-d');
        
        // Hitung selisih hari dengan benar
        $datetime1 = new DateTime($last_transaction_date);
        $datetime2 = new DateTime($today);
        $interval = $datetime2->diff($datetime1);
        $days_since_last = $interval->days;
    }
    
    // Get customer frequency dari data
    $frequency = isset($customer_data['jumlah_transaksi']) ? $customer_data['jumlah_transaksi'] : 1;
    
    // Simple risk calculation - IMPROVED
    $risk_score = 0;
    
    // Risk berdasarkan recency (kapan terakhir beli)
    if ($days_since_last > 180) $risk_score += 50;      // 6+ bulan = very high risk
    elseif ($days_since_last > 90) $risk_score += 40;   // 3-6 bulan = high risk  
    elseif ($days_since_last > 60) $risk_score += 25;   // 2-3 bulan = medium risk
    elseif ($days_since_last > 30) $risk_score += 10;   // 1-2 bulan = slight risk
    
    // Risk berdasarkan frequency (seberapa sering beli)
    if ($frequency == 1) $risk_score += 30;             // First-time buyer = risky
    elseif ($frequency == 2) $risk_score += 15;         // 2x buyer = still risky
    elseif ($frequency >= 5) $risk_score -= 10;         // Loyal customer = less risk
    
    $risk_score = min(100, max(0, $risk_score));
    
    // Simple status determination - IMPROVED
    if ($risk_score > 70) {
        $status = ['label' => 'High Risk', 'class' => 'danger', 'action' => 'retention'];
    } elseif ($risk_score > 40) {
        $status = ['label' => 'Medium Risk', 'class' => 'warning', 'action' => 'attention'];
    } elseif ($days_since_last <= 7) {
        $status = ['label' => 'Very Active', 'class' => 'success', 'action' => 'upsell'];
    } elseif ($days_since_last <= 30) {
        $status = ['label' => 'Active', 'class' => 'info', 'action' => 'upsell'];
    } else {
        $status = ['label' => 'Stable', 'class' => 'secondary', 'action' => 'maintain'];
    }
    
    return [
        'risk_score' => (int)$risk_score,
        'days_since_last' => (int)$days_since_last,
        'frequency' => (int)$frequency,
        'status' => $status,
        // Debug info (bisa dihapus di production)
        'debug' => [
            'last_date' => $customer_data['transaksi_terakhir'] ?? 'N/A',
            'today' => date('Y-m-d'),
            'calculation' => "Today - Last = {$days_since_last} days"
        ]
    ];
}

// Remove empty filters
$active_filters = array_filter($filters, function($value) {
    return $value !== '' && $value !== 0;
});

// Build URL function
function buildFilterUrl($additional_params = []) {
    global $active_filters;
    $params = array_merge($active_filters, $additional_params);
    return 'detail.php?' . http_build_query($params);
}

// Group data by customer untuk menghindari duplikasi - FIXED
$grouped_customers = [];
if (!empty($report['data'])) {
    foreach ($report['data'] as $row) {
        $customer_key = $row['customer_nama'] . '_' . $row['nomor_wa'];
        
        if (!isset($grouped_customers[$customer_key])) {
            $grouped_customers[$customer_key] = [
                'customer_nama' => $row['customer_nama'],
                'nomor_wa' => $row['nomor_wa'],
                'transaksi_terakhir' => $row['tanggal_transaksi'], // Inisialisasi dengan transaksi pertama
                'produk_list' => [],
                'total_belanja' => 0,
                'jumlah_transaksi' => 0
            ];
        }
        
        $grouped_customers[$customer_key]['produk_list'][] = [
            'nama' => $row['produk_nama'],
            'harga' => $row['harga'],
            'tanggal' => $row['tanggal_transaksi']
        ];
        $grouped_customers[$customer_key]['total_belanja'] += $row['harga'];
        $grouped_customers[$customer_key]['jumlah_transaksi']++;
        
        // Update tanggal terakhir jika lebih baru - FIX: gunakan string comparison yang benar
        if (strtotime($row['tanggal_transaksi']) > strtotime($grouped_customers[$customer_key]['transaksi_terakhir'])) {
            $grouped_customers[$customer_key]['transaksi_terakhir'] = $row['tanggal_transaksi'];
        }
    }
}

// Hitung statistik status untuk SEMUA data (tidak terfilter) - FIXED
// GRAFIK & QUICK FILTER: Gunakan semua customer dari database
$all_customers_for_chart = getAllCustomersForChart(); // Data khusus untuk grafik
$status_stats = ['high_risk' => 0, 'medium_risk' => 0, 'very_active' => 0, 'active' => 0, 'stable' => 0];

foreach ($all_customers_for_chart as $customer) {
    $behavior = getCustomerBehavior($customer);
    $status_key = strtolower(str_replace(' ', '_', $behavior['status']['label']));
    
    // Count untuk statistik (semua data, tidak terfilter)
    if (isset($status_stats[$status_key])) {
        $status_stats[$status_key]++;
    }
}

// TABEL: Apply status filter untuk tampilan tabel (data bisa terfilter)
$filtered_customers = [];

// Jika ada status filter, ambil dari ALL CUSTOMERS, bukan dari grouped_customers yang terfilter
if (!empty($filters['status_filter'])) {
    // Jika ada filter tanggal/produk/customer bersamaan dengan status filter,
    // kita perlu ambil data yang sudah terfilter DAN status filter
    if (!empty($filters['tanggal_dari']) || !empty($filters['tanggal_sampai']) || 
        !empty($filters['produk_id']) || !empty($filters['cari_customer'])) {
        // Kombinasi filter: gunakan grouped_customers + status filter
        foreach ($grouped_customers as $customer) {
            $behavior = getCustomerBehavior($customer);
            $status_key = strtolower(str_replace(' ', '_', $behavior['status']['label']));
            
            if ($status_key === $filters['status_filter']) {
                $filtered_customers[] = $customer;
            }
        }
    } else {
        // Hanya status filter: gunakan semua customer dari database
        foreach ($all_customers_for_chart as $customer) {
            $behavior = getCustomerBehavior($customer);
            $status_key = strtolower(str_replace(' ', '_', $behavior['status']['label']));
            
            if ($status_key === $filters['status_filter']) {
                $filtered_customers[] = $customer;
            }
        }
    }
} else {
    // Jika tidak ada status filter, gunakan data yang sudah terfilter tanggal/produk
    foreach ($grouped_customers as $customer) {
        $filtered_customers[] = $customer;
    }
}

// Pagination untuk filtered customers
$total_customers = count($filtered_customers);
$total_pages = ceil($total_customers / $filters['limit']);
$offset = ($filters['page'] - 1) * $filters['limit'];
$paginated_customers = array_slice($filtered_customers, $offset, $filters['limit']);
?>

<div class="main-content dashboard-wrapper flex-grow-1">
    
    <div class="dash-header flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="dash-title d-flex align-items-center gap-2">
                <i class="fas fa-user-astronaut text-primary"></i> Perilaku Pelanggan
            </h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Analisis mendalam riwayat belanja, RFM, dan risiko *churn*.</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if (!empty($report['data'])): ?>
                <a href="<?= buildFilterUrl(['export' => 'csv']) ?>" id="btnExportCSV" class="btn btn-light text-dark fw-bold border rounded-pill px-3" style="box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <i class="fas fa-file-csv me-1 text-success"></i> Export Data
                </a>
            <?php endif; ?>
            <a href="analitik.php" class="btn btn-dark fw-bold rounded-pill px-4" style="box-shadow: 0 4px 12px rgba(17, 24, 39, 0.15);">
                <i class="fas fa-chart-pie me-1"></i> Ringkasan Analitik
            </a>
        </div>
    </div>

    <!-- Top Stats & Health Chart -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="row g-3 h-100">
                <div class="col-6 col-sm-6">
                    <div class="stat-card" style="padding: 1.25rem;">
                        <div class="stat-icon" style="background: #EFF6FF; color: #2563EB; width: 36px; height: 36px; font-size: 1rem;"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="stat-value text-dark" style="font-size: 1.5rem;"><?= formatNumber($customer_summary['total_customers']) ?></div>
                            <div class="stat-label mt-1" style="font-size: 0.75rem;">Total Database</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-6">
                    <div class="stat-card" style="padding: 1.25rem;">
                        <div class="stat-icon" style="background: #ECFDF5; color: #059669; width: 36px; height: 36px; font-size: 1rem;"><i class="fas fa-user-plus"></i></div>
                        <div>
                            <div class="stat-value text-success" style="font-size: 1.5rem;"><?= formatNumber($customer_summary['new_customers']) ?></div>
                            <div class="stat-label mt-1" style="font-size: 0.75rem;">Baru <span class="fw-normal text-muted opacity-75">(30 Hari)</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-6">
                    <div class="stat-card" style="padding: 1.25rem;">
                        <div class="stat-icon" style="background: #F3E8FF; color: #9333EA; width: 36px; height: 36px; font-size: 1rem;"><i class="fas fa-redo-alt"></i></div>
                        <div>
                            <div class="stat-value" style="color: #9333EA; font-size: 1.5rem;"><?= formatNumber($customer_summary['repeat_customers']) ?></div>
                            <div class="stat-label mt-1" style="font-size: 0.75rem;">Repeat Order <span class="fw-normal text-muted opacity-75">(>1x)</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-6">
                    <div class="stat-card" style="padding: 1.25rem;">
                        <div class="stat-icon" style="background: #FEF3C7; color: #D97706; width: 36px; height: 36px; font-size: 1rem;"><i class="fas fa-receipt"></i></div>
                        <div>
                            <div class="stat-value text-warning text-truncate" style="font-size: 1.35rem;" title="<?= formatCurrency($customer_summary['avg_transaction_value']) ?>"><?= formatCurrency($customer_summary['avg_transaction_value']) ?></div>
                            <div class="stat-label mt-1" style="font-size: 0.75rem;">Avg. Transaksi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="list-container h-100 d-flex flex-column p-4">
                <h6 class="fw-bold mb-3 text-dark" style="font-size: 0.95rem;"><i class="fas fa-heartbeat text-danger me-2"></i>Kesehatan Pelanggan</h6>
                <div style="flex-grow: 1; min-height: 140px; position: relative; margin-bottom: 1.5rem;">
                    <!-- Absolute position mencegah canvas resize issue di mobile -->
                    <div style="position: absolute; top:0; left:0; right:0; bottom:0;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                <div class="row text-center g-2 mt-auto border-top pt-3">
                    <div class="col-4">
                        <div class="text-danger fw-bold fs-5" style="line-height: 1;"><?= $status_stats['high_risk'] ?></div>
                        <div class="text-muted mt-1" style="font-size: 0.65rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">High Risk</div>
                    </div>
                    <div class="col-4 border-start border-end">
                        <div class="text-success fw-bold fs-5" style="line-height: 1;"><?= $status_stats['very_active'] ?></div>
                        <div class="text-muted mt-1" style="font-size: 0.65rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Active</div>
                    </div>
                    <div class="col-4">
                        <div class="text-secondary fw-bold fs-5" style="line-height: 1;"><?= $status_stats['stable'] ?></div>
                        <div class="text-muted mt-1" style="font-size: 0.65rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Stable</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Filter & Table -->
    <div class="panel-editorial p-0 overflow-hidden mb-5">
        
        <!-- Filter Header Area -->
        <div class="p-3 p-md-4 border-bottom bg-white d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
            
            <!-- Scrollable Nav Pills for Mobile -->
            <div class="d-flex gap-2 overflow-auto hide-scrollbar pb-2 pb-xl-0" style="white-space: nowrap; -webkit-overflow-scrolling: touch; width: 100%; max-width: 100%;">
                <a href="<?= buildFilterUrl(['status_filter' => '', 'page' => 1]) ?>" 
                   class="btn <?= empty($filters['status_filter']) ? 'btn-dark' : 'btn-light text-muted border' ?> btn-sm rounded-pill px-3 fw-bold flex-shrink-0">
                    Semua <span class="ms-1 opacity-75 fw-normal">(<?= count($all_customers_for_chart) ?>)</span>
                </a>
                <a href="<?= buildFilterUrl(['status_filter' => 'high_risk', 'page' => 1]) ?>" 
                   class="btn <?= $filters['status_filter'] === 'high_risk' ? 'btn-danger' : 'btn-light text-danger border' ?> btn-sm rounded-pill px-3 fw-bold flex-shrink-0">
                    High Risk <span class="ms-1 opacity-75 fw-normal">(<?= $status_stats['high_risk'] ?>)</span>
                </a>
                <a href="<?= buildFilterUrl(['status_filter' => 'medium_risk', 'page' => 1]) ?>" 
                   class="btn <?= $filters['status_filter'] === 'medium_risk' ? 'btn-warning text-dark' : 'btn-light text-warning border' ?> btn-sm rounded-pill px-3 fw-bold flex-shrink-0">
                    Medium <span class="ms-1 opacity-75 fw-normal">(<?= $status_stats['medium_risk'] ?>)</span>
                </a>
                <a href="<?= buildFilterUrl(['status_filter' => 'very_active', 'page' => 1]) ?>" 
                   class="btn <?= $filters['status_filter'] === 'very_active' ? 'btn-success' : 'btn-light text-success border' ?> btn-sm rounded-pill px-3 fw-bold flex-shrink-0">
                    Very Active <span class="ms-1 opacity-75 fw-normal">(<?= $status_stats['very_active'] ?>)</span>
                </a>
            </div>

            <!-- Form Filter Cerdas -->
            <form method="GET" class="d-flex flex-wrap align-items-center gap-2 m-0 w-100" id="filterForm">
                <?php if(!empty($filters['status_filter'])): ?>
                    <input type="hidden" name="status_filter" value="<?= $filters['status_filter'] ?>">
                <?php endif; ?>

                <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1 border border-light flex-grow-1" style="min-width: 220px;">
                    <i class="fas fa-calendar-alt text-muted me-2" style="font-size: 0.85rem;"></i>
                    <input type="date" name="tanggal_dari" class="form-control border-0 bg-transparent p-0 text-muted fw-bold" style="width: 100px; font-size: 0.8rem;" value="<?= $filters['tanggal_dari'] ?>" title="Dari Tanggal">
                    <span class="mx-1 text-muted">-</span>
                    <input type="date" name="tanggal_sampai" class="form-control border-0 bg-transparent p-0 text-muted fw-bold" style="width: 100px; font-size: 0.8rem;" value="<?= $filters['tanggal_sampai'] ?>" title="Sampai Tanggal">
                </div>
                
                <div class="bg-light rounded-pill px-3 py-1 border border-light d-flex align-items-center flex-grow-1" style="min-width: 160px;">
                    <select name="produk_id" class="form-select border-0 bg-transparent p-0 text-dark fw-bold" style="width: 100%; font-size: 0.85rem; cursor: pointer; box-shadow: none; outline: none;">
                        <option value="">Semua Produk</option>
                        <?php foreach ($all_produk as $produk): ?>
                            <option value="<?= $produk['id'] ?>" <?= $produk['id'] == $filters['produk_id'] ? 'selected' : '' ?>>
                                <?= truncateText(safeHtml($produk['nama']), 25) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1 border border-light flex-grow-1" style="min-width: 200px;">
                    <i class="fas fa-search text-muted me-2" style="font-size: 0.85rem;"></i>
                    <input type="text" name="cari_customer" class="form-control border-0 bg-transparent p-0 text-dark fw-bold" placeholder="Cari nama/WA..." value="<?= $filters['cari_customer'] ?>" style="font-size: 0.85rem; outline: none; box-shadow: none;">
                    <?php if (!empty($active_filters)): ?>
                        <a href="detail.php" class="text-danger ms-2 text-decoration-none"><i class="fas fa-times-circle"></i></a>
                    <?php endif; ?>
                </div>
                
                <!-- Tombol submit disembunyikan, di-trigger via JS onChange/onInput -->
                <button type="submit" class="d-none">Cari</button>
            </form>
        </div>

        <!-- Tabel Data -->
        <div class="p-0 bg-light">
            <?php if (empty($paginated_customers)): ?>
                <div class="text-center py-5 bg-white">
                    <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                        <i class="fas fa-search text-muted fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Tidak Ditemukan</h5>
                    <p class="text-muted">Customer dengan kriteria tersebut tidak ditemukan dalam catatan.</p>
                    <a href="detail.php" class="btn btn-dark rounded-pill px-4">Reset Pencarian</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-editorial mb-0" style="min-width: 900px;">
                        <thead class="bg-white">
                            <tr>
                                <th width="5%" class="text-center">#</th>
                                <th width="20%">Info Pelanggan</th>
                                <th width="18%">Status & Risiko</th>
                                <th width="15%">Aktivitas</th>
                                <th width="22%">Produk Terakhir</th>
                                <th width="10%">Nilai (LTV)</th>
                                <th width="10%" class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $start_number = ($filters['page'] - 1) * $filters['limit'] + 1;
                            foreach ($paginated_customers as $index => $customer): 
                                $behavior = getCustomerBehavior($customer); 
                                $risk_class = $behavior['status']['class'];
                                
                                $progress_color = '#6B7280';
                                if($risk_class == 'danger') $progress_color = '#EF4444';
                                if($risk_class == 'warning') $progress_color = '#F59E0B';
                                if($risk_class == 'success') $progress_color = '#10B981';
                                if($risk_class == 'info') $progress_color = '#3B82F6';
                            ?>
                            <tr>
                                <td class="text-center text-muted fw-bold" style="font-size: 0.85rem;"><?= $start_number + $index ?></td>
                                
                                <td>
                                    <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem; line-height: 1.2;"><?= safeHtml($customer['customer_nama']) ?></div>
                                    <a href="https://wa.me/<?= $customer['nomor_wa'] ?>" target="_blank" class="badge-wa badge-clean text-decoration-none" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                                        <i class="fab fa-whatsapp"></i> <?= $customer['nomor_wa'] ?>
                                    </a>
                                </td>
                                
                                <td>
                                    <span class="badge-clean" style="background: rgba(var(--bs-<?= $risk_class ?>-rgb), 0.1); color: var(--bs-<?= $risk_class ?>); padding: 0.25rem 0.6rem;">
                                        <?= $behavior['status']['label'] ?>
                                    </span>
                                    <div class="mt-2 d-flex align-items-center gap-2">
                                        <div style="flex-grow: 1; height: 4px; background: #E5E7EB; border-radius: 4px; overflow: hidden;">
                                            <div style="width: <?= $behavior['risk_score'] ?>%; height: 100%; background: <?= $progress_color ?>; border-radius: 4px;"></div>
                                        </div>
                                        <span style="font-size: 0.7rem; font-weight: 700; color: #9CA3AF; min-width: 25px; text-align: right;"><?= $behavior['risk_score'] ?>%</span>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;"><i class="fas fa-shopping-basket text-muted me-1"></i><?= $customer['jumlah_transaksi'] ?> Order</div>
                                    <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                        <i class="fas fa-history me-1"></i><?= $behavior['days_since_last'] ?> hari lalu
                                    </div>
                                </td>
                                
                                <td>
                                    <div style="font-size: 0.8rem; line-height: 1.4; color: #4B5563;">
                                        <?php 
                                        $product_summary = [];
                                        foreach ($customer['produk_list'] as $produk) {
                                            $p_name = $produk['nama'];
                                            if (!isset($product_summary[$p_name])) $product_summary[$p_name] = ['count' => 0];
                                            $product_summary[$p_name]['count']++;
                                        }
                                        arsort($product_summary);
                                        $top_products = array_slice($product_summary, 0, 2, true);
                                        foreach ($top_products as $p_name => $summary): 
                                        ?>
                                            <div class="text-truncate" style="max-width: 180px;"><span class="text-dark fw-bold">&bull;</span> <?= safeHtml($p_name) ?> <?= $summary['count'] > 1 ? '<span class="text-primary fw-bold">('.$summary['count'].'x)</span>' : '' ?></div>
                                        <?php endforeach; ?>
                                        <?php if (count($product_summary) > 2): ?>
                                            <div class="text-muted fst-italic mt-1" style="font-size: 0.7rem;">+<?= count($product_summary) - 2 ?> item lainnya</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="fw-bold text-success" style="font-size: 0.95rem;"><?= formatCurrency($customer['total_belanja']) ?></div>
                                </td>
                                
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <?php if ($behavior['status']['action'] == 'retention'): ?>
                                            <a href="https://wa.me/<?= $customer['nomor_wa'] ?>?text=Hai%20<?= urlencode($customer['customer_nama']) ?>,%20kami%20rindu%20Anda!%20Ada%20promo%20spesial%20nih!" 
                                               class="btn btn-sm btn-danger rounded-pill fw-bold badge-clean" target="_blank" style="padding: 0.4rem 0.8rem; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);">
                                                <i class="fas fa-gift me-1"></i> Promo
                                            </a>
                                        <?php elseif ($behavior['status']['action'] == 'upsell'): ?>
                                            <a href="https://wa.me/<?= $customer['nomor_wa'] ?>?text=Hai%20<?= urlencode($customer['customer_nama']) ?>,%20ada%20produk%20premium%20yang%20cocok%20untuk%20Anda!" 
                                               class="btn btn-sm btn-info text-white rounded-pill fw-bold badge-clean" target="_blank" style="padding: 0.4rem 0.8rem; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);">
                                                <i class="fas fa-arrow-up me-1"></i> Upsell
                                            </a>
                                        <?php else: ?>
                                            <a href="https://wa.me/<?= $customer['nomor_wa'] ?>?text=Hai%20<?= urlencode($customer['customer_nama']) ?>,%20apa%20kabar?%20Semoga%20sehat%20selalu!" 
                                               class="btn btn-sm btn-dark rounded-pill fw-bold badge-clean" target="_blank" style="padding: 0.4rem 0.8rem;">
                                                <i class="fas fa-handshake me-1"></i> Sapa
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Modern -->
                <?php if ($total_pages > 1): ?>
                <div class="p-3 border-top bg-white d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.05em;">
                        Halaman <?= $filters['page'] ?> dari <?= $total_pages ?>
                    </div>
                    <div class="d-flex gap-1 overflow-auto hide-scrollbar" style="max-width: 100%;">
                        <?php if ($filters['page'] > 1): ?>
                            <a href="<?= buildFilterUrl(['page' => $filters['page'] - 1]) ?>" class="btn btn-sm btn-light text-dark fw-bold border-0 flex-shrink-0"><i class="fas fa-chevron-left"></i></a>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $filters['page'] - 2);
                        $end_page = min($total_pages, $filters['page'] + 2);
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <a href="<?= buildFilterUrl(['page' => $i]) ?>" class="btn btn-sm <?= $i == $filters['page'] ? 'btn-dark' : 'btn-light text-muted' ?> fw-bold border-0 flex-shrink-0" style="min-width: 36px; border-radius: 8px;"><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($filters['page'] < $total_pages): ?>
                            <a href="<?= buildFilterUrl(['page' => $filters['page'] + 1]) ?>" class="btn btn-sm btn-light text-dark fw-bold border-0 flex-shrink-0"><i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Insight & Checklist Section -->
    <?php if ($customer_summary['total_customers'] > 0): ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="panel-editorial h-100 p-4">
                <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-lightbulb text-warning me-2"></i>Insight Bisnis</h6>
                <?php
                $high_risk_count = $status_stats['high_risk']; 
                $active_count = $status_stats['very_active'] + $status_stats['active'];
                $total_cust = $customer_summary['total_customers'];
                ?>
                <div class="d-flex flex-column gap-3">
                    <?php if ($high_risk_count > 0): ?>
                        <div class="d-flex align-items-start gap-3 p-3 rounded-4" style="background: #FEF2F2; border: 1px solid #FECACA;">
                            <div style="width:40px; height:40px; background:#FFFFFF; color:#EF4444; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.1);">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <strong class="text-danger d-block mb-1" style="font-size: 0.95rem;"><?= $high_risk_count ?> Customer Berisiko Tinggi</strong>
                                <span class="text-dark" style="font-size: 0.85rem; line-height: 1.4;">Mereka sudah lama tidak belanja. Terapkan filter "High Risk" dan kirimkan campaign promo retensi (diskon).</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($active_count > 0): ?>
                        <div class="d-flex align-items-start gap-3 p-3 rounded-4" style="background: #ECFDF5; border: 1px solid #A7F3D0;">
                            <div style="width:40px; height:40px; background:#FFFFFF; color:#10B981; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.1);">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div>
                                <strong class="text-success d-block mb-1" style="font-size: 0.95rem;"><?= $active_count ?> Customer Siap Upsell</strong>
                                <span class="text-dark" style="font-size: 0.85rem; line-height: 1.4;">Mereka adalah pelanggan paling aktif. Waktu yang tepat untuk menawarkan produk/paket premium kamu.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3 mt-1">
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100" style="background: #F9FAFB; border: 1px solid #E5E7EB;">
                                <div class="text-muted fw-bold mb-1" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">Total LTV Revenue</div>
                                <div class="text-primary fw-bold fs-5 text-truncate" title="<?= formatCurrency($customer_summary['total_revenue']) ?>"><?= formatCurrency($customer_summary['total_revenue']) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 h-100" style="background: #F9FAFB; border: 1px solid #E5E7EB;">
                                <div class="text-muted fw-bold mb-1" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">Repeat Order Rate</div>
                                <div class="text-primary fw-bold fs-5"><?= $total_cust > 0 ? round(($customer_summary['repeat_customers'] / $total_cust) * 100, 1) : 0 ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel-editorial h-100 p-4 bg-dark text-white" style="background: linear-gradient(135deg, #111827 0%, #1E3A8A 100%); border: none;">
                <h6 class="fw-bold mb-4 text-white"><i class="fas fa-tasks text-info me-2"></i>Action Plan Checklist</h6>
                <div class="d-flex flex-column gap-3 checklist-container">
                    <label class="checklist-item d-flex align-items-center gap-3 p-2 rounded-3" style="cursor: pointer;">
                        <input type="checkbox" class="form-check-input mt-0" style="width: 1.25rem; height: 1.25rem; border-color: rgba(255,255,255,0.3); background-color: transparent;">
                        <span class="text-white" style="font-weight: 500; font-size: 0.9rem;">Sapa customer 'Stable' agar kembali 'Active'</span>
                    </label>
                    <label class="checklist-item d-flex align-items-center gap-3 p-2 rounded-3" style="cursor: pointer;">
                        <input type="checkbox" class="form-check-input mt-0" style="width: 1.25rem; height: 1.25rem; border-color: rgba(255,255,255,0.3); background-color: transparent;">
                        <span class="text-white" style="font-weight: 500; font-size: 0.9rem;">Kirim broadcast WhatsApp ke customer baru</span>
                    </label>
                    <label class="checklist-item d-flex align-items-center gap-3 p-2 rounded-3" style="cursor: pointer;">
                        <input type="checkbox" class="form-check-input mt-0" style="width: 1.25rem; height: 1.25rem; border-color: rgba(255,255,255,0.3); background-color: transparent;">
                        <span class="text-white" style="font-weight: 500; font-size: 0.9rem;">Evaluasi produk favorit kelompok 'High Risk'</span>
                    </label>
                    <label class="checklist-item d-flex align-items-center gap-3 p-2 rounded-3" style="cursor: pointer;">
                        <input type="checkbox" class="form-check-input mt-0" style="width: 1.25rem; height: 1.25rem; border-color: rgba(255,255,255,0.3); background-color: transparent;">
                        <span class="text-white" style="font-weight: 500; font-size: 0.9rem;">Tawarkan kupon spesial untuk repeat order</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<style>
/* Utilities for Mobile Scrolling */
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Interactive Checklist Styling */
.checklist-item { transition: background 150ms var(--ease-out); border: 1px solid transparent; }
.checklist-item input:checked { background-color: #10B981; border-color: #10B981; }
.checklist-item input:checked + span { text-decoration: line-through; opacity: 0.5; }

@media (hover: hover) and (pointer: fine) {
    .checklist-item:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); }
    .checklist-item:active { transform: scale(0.98); }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
    
    // Status Chart
    const ctx = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['High Risk', 'Medium Risk', 'Very Active', 'Active', 'Stable'],
            datasets: [{
                data: [
                    <?= $status_stats['high_risk'] ?>,
                    <?= $status_stats['medium_risk'] ?>,
                    <?= $status_stats['very_active'] ?>,
                    <?= $status_stats['active'] ?>,
                    <?= $status_stats['stable'] ?>
                ],
                backgroundColor: ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#6B7280'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '78%', 
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    padding: 12,
                    bodyFont: { weight: 'bold', size: 13 },
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percent = total > 0 ? ((context.parsed * 100) / total).toFixed(1) : 0;
                            return ' ' + context.label + ': ' + context.parsed + ' orang (' + percent + '%)';
                        }
                    }
                }
            }
        }
    });
    
    // Auto submit form saat input select/date berubah
    document.querySelectorAll('#filterForm select, #filterForm input[type="date"]').forEach(element => {
        element.addEventListener('change', () => document.getElementById('filterForm').submit());
    });
    
    // Debounce Search Auto Submit
    let searchTimeout;
    const searchInput = document.querySelector('input[name="cari_customer"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            if (this.value.trim().length >= 3 || this.value.trim().length === 0) {
                searchTimeout = setTimeout(() => document.getElementById('filterForm').submit(), 800);
            }
        });
    }
    
    // Animasi Export CSV
    const btnExport = document.getElementById('btnExportCSV');
    if (btnExport) {
        btnExport.addEventListener('click', function() {
            const icon = this.querySelector('i');
            icon.className = 'fas fa-spinner fa-spin me-1 text-success';
            setTimeout(() => { icon.className = 'fas fa-file-csv me-1 text-success'; }, 2500);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>