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

<div class="main-content">
    <!-- Header -->
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Customer Analytics</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="analitik.php" class="breadcrumb-item text-decoration-none">Analytics</a>
                    <span class="breadcrumb-item active">Detail</span>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <?php if (!empty($report['data'])): ?>
                    <a href="<?= buildFilterUrl(['export' => 'csv']) ?>" class="btn btn-success">
                        <i class="fas fa-download me-2"></i>Export CSV
                    </a>
                <?php endif; ?>
                <a href="analitik.php" class="btn btn-outline-primary">
                    <i class="fas fa-chart-line me-2"></i>Analytics
                </a>
            </div>
        </div>
    </div>

    <div class="content-area">
        <!-- Customer Summary Cards + Status Chart -->
        <div class="row mb-4">
            <!-- Summary Cards -->
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-lg-6 col-md-6 mb-3">
                        <div class="card dashboard-card">
                            <div class="card-body text-center">
                                <div class="h3 text-primary mb-2"><?= formatNumber($customer_summary['total_customers']) ?></div>
                                <h6 class="text-muted">Total Customer</h6>
                                <small class="text-muted">Dari database</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 col-md-6 mb-3">
                        <div class="card dashboard-card">
                            <div class="card-body text-center">
                                <div class="h3 text-success mb-2"><?= formatNumber($customer_summary['new_customers']) ?></div>
                                <h6 class="text-muted">Customer Baru</h6>
                                <small class="text-muted">(30 hari terakhir)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 col-md-6 mb-3">
                        <div class="card dashboard-card">
                            <div class="card-body text-center">
                                <div class="h3 text-info mb-2"><?= formatNumber($customer_summary['repeat_customers']) ?></div>
                                <h6 class="text-muted">Repeat Customer</h6>
                                <small class="text-muted">(Lebih dari 1 pembelian)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 col-md-6 mb-3">
                        <div class="card dashboard-card">
                            <div class="card-body text-center">
                                <div class="h3 text-warning mb-2"><?= formatCurrency($customer_summary['avg_transaction_value']) ?></div>
                                <h6 class="text-muted">Rata-rata Transaksi</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Status Distribution Chart -->
            <div class="col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Distribusi Status Customer</h6>
                    </div>
                    <div class="card-body">
                        <div style="height: 250px; position: relative;">
                            <canvas id="statusChart"></canvas>
                        </div>
                        
                        <!-- Status Legend -->
                        <div class="mt-3">
                            <div class="row text-center">
                                <div class="col-6 mb-2">
                                    <span class="badge bg-danger"><?= $status_stats['high_risk'] ?></span>
                                    <small class="d-block">High Risk</small>
                                </div>
                                <div class="col-6 mb-2">
                                    <span class="badge bg-warning"><?= $status_stats['medium_risk'] ?></span>
                                    <small class="d-block">Medium Risk</small>
                                </div>
                                <div class="col-6 mb-2">
                                    <span class="badge bg-success"><?= $status_stats['very_active'] ?></span>
                                    <small class="d-block">Very Active</small>
                                </div>
                                <div class="col-6 mb-2">
                                    <span class="badge bg-info"><?= $status_stats['active'] ?></span>
                                    <small class="d-block">Active</small>
                                </div>
                                <div class="col-12">
                                    <span class="badge bg-secondary"><?= $status_stats['stable'] ?></span>
                                    <small class="d-block">Stable</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Data</h6>
                    <?php if (!empty($active_filters)): ?>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i>
                                <?= $total_customers ?> customer ditemukan
                                <?php if (!empty($filters['status_filter'])): ?>
                                    (status: <?= ucwords(str_replace('_', ' ', $filters['status_filter'])) ?>)
                                <?php endif; ?>
                            </span>
                            <a href="detail.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Reset
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <!-- Quick Status Filters -->
                <div class="row mb-3">
                    <div class="col-12">
                        <h6 class="mb-2">
                            <i class="fas fa-bolt me-2"></i>Quick Filter Status:
                        </h6>
                        <div class="btn-group flex-wrap" role="group">
                            <a href="<?= buildFilterUrl(['status_filter' => '', 'page' => 1]) ?>" 
                               class="btn <?= empty($filters['status_filter']) ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                                <i class="fas fa-users me-1"></i>Semua
                                <span class="badge bg-white text-primary ms-1"><?= count($all_customers_for_chart) ?></span>
                            </a>
                            <a href="<?= buildFilterUrl(['status_filter' => 'high_risk', 'page' => 1]) ?>" 
                               class="btn <?= $filters['status_filter'] === 'high_risk' ? 'btn-danger' : 'btn-outline-danger' ?> btn-sm">
                                <i class="fas fa-exclamation-triangle me-1"></i>High Risk
                                <span class="badge bg-white text-danger ms-1"><?= $status_stats['high_risk'] ?></span>
                            </a>
                            <a href="<?= buildFilterUrl(['status_filter' => 'medium_risk', 'page' => 1]) ?>" 
                               class="btn <?= $filters['status_filter'] === 'medium_risk' ? 'btn-warning' : 'btn-outline-warning' ?> btn-sm">
                                <i class="fas fa-eye me-1"></i>Medium Risk
                                <span class="badge bg-white text-warning ms-1"><?= $status_stats['medium_risk'] ?></span>
                            </a>
                            <a href="<?= buildFilterUrl(['status_filter' => 'very_active', 'page' => 1]) ?>" 
                               class="btn <?= $filters['status_filter'] === 'very_active' ? 'btn-success' : 'btn-outline-success' ?> btn-sm">
                                <i class="fas fa-star me-1"></i>Very Active
                                <span class="badge bg-white text-success ms-1"><?= $status_stats['very_active'] ?></span>
                            </a>
                            <a href="<?= buildFilterUrl(['status_filter' => 'active', 'page' => 1]) ?>" 
                               class="btn <?= $filters['status_filter'] === 'active' ? 'btn-info' : 'btn-outline-info' ?> btn-sm">
                                <i class="fas fa-thumbs-up me-1"></i>Active
                                <span class="badge bg-white text-info ms-1"><?= $status_stats['active'] ?></span>
                            </a>
                            <a href="<?= buildFilterUrl(['status_filter' => 'stable', 'page' => 1]) ?>" 
                               class="btn <?= $filters['status_filter'] === 'stable' ? 'btn-secondary' : 'btn-outline-secondary' ?> btn-sm">
                                <i class="fas fa-minus me-1"></i>Stable
                                <span class="badge bg-white text-secondary ms-1"><?= $status_stats['stable'] ?></span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Traditional Filters -->
                <form method="GET" class="row g-3" id="filterForm">
                    <div class="col-md-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="tanggal_dari" class="form-control" 
                               value="<?= $filters['tanggal_dari'] ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="tanggal_sampai" class="form-control" 
                               value="<?= $filters['tanggal_sampai'] ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Produk</label>
                        <select name="produk_id" class="form-select">
                            <option value="">Semua Produk</option>
                            <?php foreach ($all_produk as $produk): ?>
                                <option value="<?= $produk['id'] ?>" 
                                        <?= $produk['id'] == $filters['produk_id'] ? 'selected' : '' ?>>
                                    <?= safeHtml($produk['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Per Halaman</label>
                        <select name="limit" class="form-select">
                            <option value="10" <?= $filters['limit'] == 10 ? 'selected' : '' ?>>10</option>
                            <option value="20" <?= $filters['limit'] == 20 ? 'selected' : '' ?>>20</option>
                            <option value="50" <?= $filters['limit'] == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $filters['limit'] == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Cari Customer</label>
                        <input type="text" name="cari_customer" class="form-control" 
                               placeholder="Nama atau WhatsApp..." 
                               value="<?= $filters['cari_customer'] ?>">
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Filter
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.location='detail.php'">
                            <i class="fas fa-refresh me-1"></i>Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Customer Table -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Data Customer & Analytics
                        <?php if (!empty($active_filters) || !empty($filters['status_filter'])): ?>
                            (<?= $total_customers ?> customer)
                        <?php endif; ?>
                    </h5>
                    
                    <?php if (!empty($paginated_customers)): ?>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="text-muted">
                                Halaman <?= $filters['page'] ?> dari <?= $total_pages ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($paginated_customers)): ?>
                    <div class="text-center py-5">
                        <?php if (!empty($active_filters) || !empty($filters['status_filter'])): ?>
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada customer yang sesuai kriteria</h5>
                            <p class="text-muted">Coba sesuaikan filter pencarian Anda.</p>
                            <a href="detail.php" class="btn btn-outline-primary">Reset Filter</a>
                        <?php else: ?>
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada data customer</h5>
                            <p class="text-muted">Data akan muncul setelah ada transaksi selesai.</p>
                            <a href="<?= BASE_URL ?>modules/transaksi/create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Buat Transaksi
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Customer</th>
                                    <th>Status & Risk</th>
                                    <th>Pembelian</th>
                                    <th>Produk Terbeli</th>
                                    <th>Total Belanja</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $start_number = ($filters['page'] - 1) * $filters['limit'] + 1;
                                foreach ($paginated_customers as $index => $customer): 
                                    $behavior = getCustomerBehavior($customer); // Pass full customer data
                                    $risk_class = $behavior['status']['class'];
                                ?>
                                <tr class="<?= $risk_class == 'danger' ? 'table-danger' : ($risk_class == 'warning' ? 'table-warning' : '') ?>">
                                    <td><?= $start_number + $index ?></td>
                                    
                                    <!-- Customer Info -->
                                    <td>
                                        <div class="fw-bold"><?= safeHtml($customer['customer_nama']) ?></div>
                                        <a href="https://wa.me/<?= $customer['nomor_wa'] ?>" 
                                           target="_blank" class="text-success text-decoration-none">
                                            <i class="fab fa-whatsapp me-1"></i><?= $customer['nomor_wa'] ?>
                                        </a>
                                    </td>
                                    
                                    <!-- Status & Risk -->
                                    <td>
                                        <span class="badge bg-<?= $behavior['status']['class'] ?> mb-1">
                                            <?= $behavior['status']['label'] ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            Risk: <?= $behavior['risk_score'] ?>% 
                                            (<?= $behavior['frequency'] ?>x buyer)
                                        </small>
                                        <div class="progress mt-1" style="height: 4px;">
                                            <div class="progress-bar bg-<?= $risk_class ?>" 
                                                 style="width: <?= $behavior['risk_score'] ?>%"></div>
                                        </div>
                                    </td>
                                    
                                    <!-- Purchase Info - FIXED -->
                                    <td>
                                        <div class="mb-1">
                                            <strong><?= $customer['jumlah_transaksi'] ?></strong> transaksi
                                        </div>
                                        <small class="text-muted">
                                            Terakhir: <?= date('d/m/Y', strtotime($customer['transaksi_terakhir'])) ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <strong><?= $behavior['days_since_last'] ?> hari lalu</strong>
                                        </small>
                                    </td>
                                    
                                    <!-- Products -->
                                    <td>
                                        <div style="max-height: 80px; overflow-y: auto;">
                                            <?php 
                                            // Group produk yang sama untuk tampilan yang lebih rapi
                                            $product_summary = [];
                                            foreach ($customer['produk_list'] as $produk) {
                                                $product_name = $produk['nama'];
                                                if (!isset($product_summary[$product_name])) {
                                                    $product_summary[$product_name] = [
                                                        'count' => 0,
                                                        'total_harga' => 0,
                                                        'harga_satuan' => $produk['harga']
                                                    ];
                                                }
                                                $product_summary[$product_name]['count']++;
                                                $product_summary[$product_name]['total_harga'] += $produk['harga'];
                                            }
                                            
                                            // Tampilkan maksimal 3 produk teratas (berdasarkan total harga)
                                            arsort($product_summary, SORT_NUMERIC);
                                            $top_products = array_slice($product_summary, 0, 3, true);
                                            
                                            foreach ($top_products as $product_name => $summary): 
                                            ?>
                                                <div class="small mb-1">
                                                    • <?= safeHtml($product_name) ?>
                                                    <?php if ($summary['count'] > 1): ?>
                                                        <span class="badge bg-secondary"><?= $summary['count'] ?>x</span>
                                                    <?php endif; ?>
                                                    <span class="text-success"><?= formatCurrency($summary['total_harga']) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                            
                                            <?php if (count($product_summary) > 3): ?>
                                                <small class="text-muted">
                                                    +<?= count($product_summary) - 3 ?> produk lainnya
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <!-- Total Spending -->
                                    <td>
                                        <div class="fw-bold text-primary">
                                            <?= formatCurrency($customer['total_belanja']) ?>
                                        </div>
                                        <small class="text-muted">
                                            Avg: <?= formatCurrency($customer['total_belanja'] / $customer['jumlah_transaksi']) ?>
                                        </small>
                                    </td>
                                    
                                    <!-- Actions -->
                                    <td>
                                        <div class="btn-group-vertical btn-group-sm" role="group">
                                            <!-- Basic WhatsApp -->
                                            <a href="https://wa.me/<?= $customer['nomor_wa'] ?>" 
                                               class="btn btn-success btn-sm" target="_blank" title="Chat WhatsApp">
                                                <i class="fab fa-whatsapp me-1"></i>Chat
                                            </a>
                                            
                                            <!-- Action berdasarkan behavior -->
                                            <?php if ($behavior['status']['action'] == 'retention'): ?>
                                                <a href="https://wa.me/<?= $customer['nomor_wa'] ?>?text=Hai%20<?= urlencode($customer['customer_nama']) ?>,%20kami%20rindu%20Anda!%20Ada%20promo%20spesial%20nih!" 
                                                   class="btn btn-danger btn-sm" target="_blank" title="Kirim promo retensi">
                                                    <i class="fas fa-gift me-1"></i>Promo
                                                </a>
                                            <?php elseif ($behavior['status']['action'] == 'upsell'): ?>
                                                <a href="https://wa.me/<?= $customer['nomor_wa'] ?>?text=Hai%20<?= urlencode($customer['customer_nama']) ?>,%20ada%20produk%20premium%20yang%20cocok%20untuk%20Anda!" 
                                                   class="btn btn-info btn-sm" target="_blank" title="Tawarkan upsell">
                                                    <i class="fas fa-arrow-up me-1"></i>Upsell
                                                </a>
                                            <?php else: ?>
                                                <a href="https://wa.me/<?= $customer['nomor_wa'] ?>?text=Hai%20<?= urlencode($customer['customer_nama']) ?>,%20apa%20kabar?%20Semoga%20sehat%20selalu!" 
                                                   class="btn btn-primary btn-sm" target="_blank" title="Sapa customer">
                                                    <i class="fas fa-handshake me-1"></i>Sapa
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4" aria-label="Customer pagination">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="text-muted">
                                    Halaman <?= $filters['page'] ?> dari <?= $total_pages ?> 
                                    (<?= number_format($total_customers) ?> total customer)
                                </div>
                                <div class="btn-group" role="group">
                                    <?php if ($filters['page'] > 1): ?>
                                        <a href="<?= buildFilterUrl(['page' => 1]) ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-angle-double-left"></i> Pertama
                                        </a>
                                        <a href="<?= buildFilterUrl(['page' => $filters['page'] - 1]) ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-angle-left"></i> Sebelumnya
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($filters['page'] < $total_pages): ?>
                                        <a href="<?= buildFilterUrl(['page' => $filters['page'] + 1]) ?>" class="btn btn-outline-primary btn-sm">
                                            Berikutnya <i class="fas fa-angle-right"></i>
                                        </a>
                                        <a href="<?= buildFilterUrl(['page' => $total_pages]) ?>" class="btn btn-outline-primary btn-sm">
                                            Terakhir <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <ul class="pagination justify-content-center">
                                <?php if ($filters['page'] > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildFilterUrl(['page' => $filters['page'] - 1]) ?>" aria-label="Previous">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php 
                                $start_page = max(1, $filters['page'] - 2);
                                $end_page = min($total_pages, $filters['page'] + 2);
                                
                                // Show first page if not in range
                                if ($start_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildFilterUrl(['page' => 1]) ?>">1</a>
                                    </li>
                                    <?php if ($start_page > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?= $i == $filters['page'] ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= buildFilterUrl(['page' => $i]) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Show last page if not in range -->
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildFilterUrl(['page' => $total_pages]) ?>"><?= $total_pages ?></a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if ($filters['page'] < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildFilterUrl(['page' => $filters['page'] + 1]) ?>" aria-label="Next">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                    <!-- Summary Info -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="text-muted">
                                Menampilkan <?= count($paginated_customers) ?> dari <?= $total_customers ?> customer
                                <?php if (!empty($filters['status_filter'])): ?>
                                    dengan status <strong><?= ucwords(str_replace('_', ' ', $filters['status_filter'])) ?></strong>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <?php if (!empty($paginated_customers)): ?>
                                <a href="<?= buildFilterUrl(['export' => 'csv']) ?>" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-download me-1"></i>Export CSV
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Simple Business Tips -->
        <?php if (!empty($grouped_customers)): ?>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips Bisnis</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $high_risk_count = 0;
                        $active_count = 0;
                        $total_customers = count($grouped_customers);
                        
                        foreach ($grouped_customers as $customer) {
                            $behavior = getCustomerBehavior($customer);
                            if ($behavior['risk_score'] > 70) $high_risk_count++;
                            if ($behavior['status']['action'] == 'upsell') $active_count++;
                        }
                        ?>
                        
                        <ul class="list-unstyled">
                            <?php if ($high_risk_count > 0): ?>
                                <li class="mb-2">
                                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                    <strong><?= $high_risk_count ?></strong> customer berisiko tinggi. 
                                    Segera lakukan campaign retensi!
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($active_count > 0): ?>
                                <li class="mb-2">
                                    <i class="fas fa-arrow-up text-success me-2"></i>
                                    <strong><?= $active_count ?></strong> customer aktif siap ditawarkan upsell.
                                </li>
                            <?php endif; ?>
                            
                            <li class="mb-2">
                                <i class="fas fa-chart-line text-info me-2"></i>
                                Total revenue: <strong><?= formatCurrency($customer_summary['total_revenue']) ?></strong>
                            </li>
                            
                            <li class="mb-2">
                                <i class="fas fa-users text-primary me-2"></i>
                                Repeat rate: <strong><?= $total_customers > 0 ? round(($customer_summary['repeat_customers'] / $total_customers) * 100, 1) : 0 ?>%</strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-tasks me-2"></i>Action Items</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-phone text-danger me-2"></i>
                                Hubungi customer high-risk dalam 24 jam
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-envelope text-info me-2"></i>
                                Kirim newsletter ke customer aktif mingguan
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-gift text-success me-2"></i>
                                Buat program loyalitas untuk repeat customer
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-chart-bar text-warning me-2"></i>
                                Analisa produk terlaris untuk stok optimal
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Simple form enhancements
document.addEventListener('DOMContentLoaded', function() {
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
                backgroundColor: [
                    '#dc3545', // High Risk - Danger
                    '#ffc107', // Medium Risk - Warning  
                    '#28a745', // Very Active - Success
                    '#17a2b8', // Active - Info
                    '#6c757d'  // Stable - Secondary
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Kita pakai legend custom di HTML
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((context.parsed * 100) / total).toFixed(1) : 0;
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            layout: {
                padding: 10
            }
        }
    });
    
    // Auto submit on select changes
    document.querySelectorAll('#filterForm select').forEach(element => {
        element.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
    
    // Date auto submit
    document.querySelectorAll('#filterForm input[type="date"]').forEach(element => {
        element.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
    
    // Search with delay
    let searchTimeout;
    const searchInput = document.querySelector('input[name="cari_customer"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchValue = this.value.trim();
            
            if (searchValue.length >= 3 || searchValue.length === 0) {
                searchTimeout = setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 1000);
            }
        });
    }
    
    // Export loading state
    document.querySelectorAll('a[href*="export=csv"]').forEach(function(link) {
        link.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Export...';
            this.style.pointerEvents = 'none';
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.style.pointerEvents = 'auto';
            }, 3000);
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>