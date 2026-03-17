<?php
// Koneksi ke database
$host = 'localhost';
$dbname = 'wegqxcgv_crm';
$username = 'wegqxcgv_crm'; // GANTI SESUAI
$password = '_N8t8mu07'; // GANTI SESUAI

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// === Ambil Input Filter dari Form ===
$segment_filter = $_GET['segment'] ?? '';
$date_start_filter = $_GET['date_start'] ?? '';
$date_end_filter = $_GET['date_end'] ?? '';
// Pastikan input adalah array, jika tidak, set ke array kosong
$include_produk_filter = is_array($_GET['include_produk'] ?? null) ? $_GET['include_produk'] : [];
$exclude_produk_filter = is_array($_GET['exclude_produk'] ?? null) ? $_GET['exclude_produk'] : [];

// === 1. Ringkasan Umum ===
$summarySql = "
SELECT 
    COUNT(DISTINCT pelanggan_id) AS total_pelanggan,
    SUM(total_harga) AS total_revenue,
    COUNT(id) AS total_order
FROM transaksi 
WHERE status = 'selesai' AND total_harga IS NOT NULL;
";
$summary = $pdo->query($summarySql)->fetch(PDO::FETCH_ASSOC);

$total_pelanggan = (int)$summary['total_pelanggan'];
$total_revenue = (float)$summary['total_revenue'];
$total_order = (int)$summary['total_order'];

$aov = $total_order > 0 ? $total_revenue / $total_order : 0;
$avg_orders_per_customer = $total_pelanggan > 0 ? $total_order / $total_pelanggan : 0;

// === Top Spender (10%) ===
$top10Count = max(1, ceil($total_pelanggan * 0.1));
$topSpenderSql = "SELECT COUNT(*) FROM (SELECT SUM(t.total_harga) AS m FROM pelanggan p JOIN transaksi t ON p.id = t.pelanggan_id AND t.status = 'selesai' GROUP BY p.id ORDER BY m DESC LIMIT " . (int)$top10Count . ") x";
$topSpenderCount = (int)$pdo->query($topSpenderSql)->fetchColumn();

// === Frequent Buyer (>2x & top 10%) ===
$frequentBuyerSql = "SELECT COUNT(*) FROM (SELECT COUNT(t.id) AS f FROM pelanggan p JOIN transaksi t ON p.id = t.pelanggan_id AND t.status = 'selesai' GROUP BY p.id HAVING f > 2 ORDER BY f DESC LIMIT " . (int)$top10Count . ") x";
$frequentBuyerCount = (int)$pdo->query($frequentBuyerSql)->fetchColumn();

// === Dormant (>90 hari) ===
$dormantSql = "
SELECT COUNT(DISTINCT p.id)
FROM pelanggan p
JOIN transaksi t ON p.id = t.pelanggan_id AND t.status = 'selesai'
WHERE t.tanggal_transaksi = (
    SELECT MAX(t2.tanggal_transaksi)
    FROM transaksi t2
    WHERE t2.pelanggan_id = p.id AND t2.status = 'selesai'
)
AND t.tanggal_transaksi < NOW() - INTERVAL 90 DAY;
";
$dormantCount = (int)$pdo->query($dormantSql)->fetchColumn();

// === Top Customer, Produk, Bulan ===
$topCustomer = $pdo->query("SELECT p.nama, COUNT(t.id) AS freq FROM pelanggan p JOIN transaksi t ON p.id = t.pelanggan_id AND t.status = 'selesai' GROUP BY p.id ORDER BY freq DESC LIMIT 1")->fetch();
$topCustomerName = $topCustomer ? $topCustomer['nama'] . ' (' . $topCustomer['freq'] . 'x)' : '-';

$topProduct = $pdo->query("SELECT pr.nama, COUNT(dt.id) AS count FROM detail_transaksi dt JOIN produk pr ON dt.produk_id = pr.id JOIN transaksi t ON dt.transaksi_id = t.id AND t.status = 'selesai' GROUP BY pr.id ORDER BY count DESC LIMIT 1")->fetch();
$topProductName = $topProduct ? $topProduct['nama'] . ' (' . $topProduct['count'] . 'x)' : '-';

$topMonth = $pdo->query("SELECT YEAR(t.tanggal_transaksi) thn, MONTH(t.tanggal_transaksi) bln, SUM(t.total_harga) total FROM transaksi t WHERE status = 'selesai' GROUP BY thn, bln ORDER BY total DESC LIMIT 1")->fetch();
$bulanNama = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$topMonthLabel = $topMonth ? ($bulanNama[$topMonth['bln']-1] . ' ' . $topMonth['thn'] . ' (Rp ' . number_format($topMonth['total'],0,',','.') . ')') : '-';

// === Fungsi Skor RFM ===
function getRScore($days) {
    if ($days <= 30) return 5;
    if ($days <= 60) return 4;
    if ($days <= 120) return 3;
    if ($days <= 240) return 2;
    return 1;
}

function getFScore($freq) {
    if ($freq == 1) return 1;
    if ($freq == 2) return 2;
    if ($freq == 3) return 3;
    if ($freq >= 4 && $freq <= 5) return 4;
    if ($freq >= 6) return 5;
    return 1;
}

function getMScore($monetary) {
    if ($monetary <= 50000) return 1;
    if ($monetary <= 100000) return 2;
    if ($monetary <= 150000) return 3;
    if ($monetary <= 500000) return 4;
    return 5;
}

// === Fungsi Segmentasi (DIPERLUAS) ===
function getSegment($R, $F, $M) {
    // Champions: baru, sering, high spend
    if ($R >= 4 && $F >= 3 && $M >= 4) return "Champions";
    // Loyal Repeaters: aktif & repeat
    if ($R >= 3 && $F >= 2) return "Loyal Repeaters";
    // New Customers
    if ($R == 5 && $F == 1) return "New Customers";
    // At Risk: dulu aktif, sekarang hilang
    if ($R <= 2 && $F >= 2) return "At Risk";
    // Others: one-time, dormant, low-value
    return "Others";
}

// === Ambil Data Pelanggan (Dengan Filter) ===
$rfmSql = "
SELECT 
    p.id,
    p.nama AS nama_pelanggan,
    p.nomor_wa,
    MAX(t.tanggal_transaksi) AS last_purchase,
    COUNT(t.id) AS frequency,
    COALESCE(SUM(t.total_harga), 0) AS monetary
FROM pelanggan p
INNER JOIN transaksi t ON p.id = t.pelanggan_id AND t.status = 'selesai'
INNER JOIN detail_transaksi dt ON t.id = dt.transaksi_id -- Gabungkan detail_transaksi
WHERE 1=1 ";

$params = [];
if (!empty($date_start_filter)) {
    $rfmSql .= " AND t.tanggal_transaksi >= ? ";
    $params[] = $date_start_filter;
}
if (!empty($date_end_filter)) {
    $rfmSql .= " AND t.tanggal_transaksi <= ? ";
    $params[] = $date_end_filter;
}

// Filter Include Produk: Pelanggan harus pernah beli salah satu produk ini
if (!empty($include_produk_filter)) {
    $placeholders = str_repeat('?,', count($include_produk_filter) - 1) . '?';
    $rfmSql .= " AND p.id IN (
        SELECT DISTINCT dt2.pelanggan_id 
        FROM detail_transaksi dt2 
        JOIN transaksi t2 ON dt2.transaksi_id = t2.id AND t2.status = 'selesai'
        WHERE dt2.produk_id IN ($placeholders)
    ) ";
    $params = array_merge($params, $include_produk_filter);
}

// Filter Exclude Produk: Pelanggan TIDAK boleh pernah beli salah satu produk ini
if (!empty($exclude_produk_filter)) {
    $placeholders = str_repeat('?,', count($exclude_produk_filter) - 1) . '?';
    $rfmSql .= " AND p.id NOT IN (
        SELECT DISTINCT dt3.pelanggan_id 
        FROM detail_transaksi dt3 
        JOIN transaksi t3 ON dt3.transaksi_id = t3.id AND t3.status = 'selesai'
        WHERE dt3.produk_id IN ($placeholders)
    ) ";
    $params = array_merge($params, $exclude_produk_filter);
}

$rfmSql .= "
GROUP BY p.id, p.nama, p.nomor_wa
ORDER BY last_purchase DESC, monetary DESC; -- Urutkan berdasarkan Recency dulu
";

$rfmCustomers = $pdo->prepare($rfmSql);
$rfmCustomers->execute($params);
$allCustomers = $rfmCustomers->fetchAll(PDO::FETCH_ASSOC);

// Hitung Skor dan Segmentasi untuk semua pelanggan
$now = new DateTime();
$customersWithSegment = [];
foreach ($allCustomers as $cust) {
    $lastPurchase = new DateTime($cust['last_purchase']);
    $interval = $now->diff($lastPurchase);
    $recencyDays = $interval->days;
    $frequency = (int)$cust['frequency'];
    $monetary = (float)$cust['monetary'];

    $R = getRScore($recencyDays);
    $F = getFScore($frequency);
    $M = getMScore($monetary);
    $rfmScore = $R . $F . $M;
    $segment = getSegment($R, $F, $M);

    $cust['recency_days'] = $recencyDays;
    $cust['frequency'] = $frequency;
    $cust['monetary'] = $monetary;
    $cust['rfm_score'] = $rfmScore;
    $cust['segment'] = $segment;
    $cust['R'] = $R;
    $cust['F'] = $F;
    $cust['M'] = $M;

    // Format Recency Display
    if ($recencyDays <= 30) {
        $cust['recency_display'] = $recencyDays . ' hari';
        $cust['recency_class'] = 'recency-low';
    } elseif ($recencyDays <= 60) {
        $cust['recency_display'] = $recencyDays . ' hari';
        $cust['recency_class'] = 'recency-medium';
    } else {
        $y = $interval->y;
        $m = $interval->m;
        $parts = [];
        if ($y > 0) $parts[] = $y . ' thn';
        if ($m > 0) $parts[] = $m . ' bln';
        $cust['recency_display'] = implode(' ', $parts) ?: $recencyDays . ' hari';
        $cust['recency_class'] = 'recency-high';
    }

    $customersWithSegment[] = $cust;
}

// Filter berdasarkan segmentasi *setelah* menghitung segmentasi
if (!empty($segment_filter)) {
    $customersWithSegment = array_filter($customersWithSegment, function($cust) use ($segment_filter) {
        return $cust['segment'] === $segment_filter;
    });
}

// Ambil akun onesender untuk dropdown
$onesenderAccounts = [];
try {
    // Pastikan nama kolom dan tabel sesuai
    $stmt = $pdo->query("SELECT account_name FROM onesender_config ORDER BY account_name ASC");
    $onesenderAccounts = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    error_log("Fetched OneSender accounts: " . print_r($onesenderAccounts, true)); // Log hasil
} catch (PDOException $e) {
    // Jika tabel onesender_config tidak ditemukan, kosongkan array
    error_log("Error fetching OneSender accounts: " . $e->getMessage());
    $onesenderAccounts = [];
}

// Ambil daftar produk untuk filter Include/Exclude - Menggunakan $pdo yang sama
$allProducts = [];
try {
    $stmt = $pdo->query("SELECT id, nama FROM produk ORDER BY nama ASC");
    $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Fetched Products: " . print_r($allProducts, true)); // Log hasil
} catch (PDOException $e) {
    // Jika tabel produk tidak ditemukan, kosongkan array
    error_log("Error fetching products: " . $e->getMessage());
    $allProducts = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Analisis Pelanggan - RFM CRM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #eef4ff 100%);
            padding: 20px;
            color: #333;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { text-align: center; margin-bottom: 25px; color: #2c3e50; font-size: 28px; }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px;
            margin-bottom: 25px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.07);
            text-align: center;
            transition: transform 0.2s;
        }
        .card:hover { transform: translateY(-3px); }
        .card.blue { border-top: 4px solid #4A90E2; }
        .card.green { border-top: 4px solid #50C878; }
        .card.purple { border-top: 4px solid #9B59B6; }
        .card.orange { border-top: 4px solid #FFA500; }
        .card.red { border-top: 4px solid #E74C3C; }
        .card.teal { border-top: 4px solid #20B2AA; }
        .card.gold { border-top: 4px solid #FFD700; color: #333; }
        .card.gray { border-top: 4px solid #95a5a6; }

        .card h3 { font-size: 12px; color: #7f8c8d; margin-bottom: 6px; font-weight: 600; }
        .card .value { font-size: 19px; font-weight: bold; color: #2c3e50; }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 20px; /* Tambahkan margin untuk pemisah */
        }
        .table-header {
            padding: 16px 20px;
            background: linear-gradient(to right, #2575fc, #6a11cb);
            color: white;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-header h2 { font-size: 18px; }
        .controls { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; } /* Flex wrap untuk mobile */
        button#copyBtn {
            padding: 6px 14px;
            background: white;
            color: #2575fc;
            border: 1px solid #2575fc;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        button#copyBtn:hover { background: #2575fc; color: white; }
        .success-message { color: #2e7d32; margin-left: 10px; font-weight: 600; display: none; }
        .scrollable-table { max-height: 520px; overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        th { background-color: #f8fbff; position: sticky; top: 0; z-index: 10; font-weight: 600; color: #1a237e; }
        tr:hover { background-color: #fafbff; }
        .empty-row td { text-align: center; color: #7f8c8d; font-style: italic; }
        .recency-high { color: #d32f2f; font-weight: bold; }
        .recency-medium { color: #f57c00; }
        .recency-low { color: #388e3c; font-weight: bold; }

        /* Gaya untuk Form Follow-up */
        .followup-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            padding: 20px;
            margin-top: 20px; /* Jarak dari tabel */
        }
        .followup-header {
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }
        .followup-header h2 { color: #2c3e50; font-size: 18px; }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap; /* Flex wrap untuk tombol */
        }
        button#sendFollowupBtn {
            padding: 12px 20px;
            background: #27ae60; /* Hijau */
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }
        button#sendFollowupBtn:hover {
            background: #219a52;
        }
        button#sendFollowupBtn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .batch-info {
            margin-top: 10px;
            font-size: 13px;
            color: #555;
        }
        .batch-info ul {
            margin-top: 5px;
            padding-left: 20px;
        }
        .batch-info li {
            margin-bottom: 3px;
        }
        .filter-controls { /* Gaya untuk filter tanggal dan segment */
            display: flex;
            gap: 10px;
            flex-wrap: wrap; /* Wrap jika layar sempit */
            align-items: end; /* Rata bawah */
        }
        .filter-controls .form-group {
            margin-bottom: 0; /* Hilangkan margin bawah untuk rata bawah */
        }
        .filter-controls button {
            padding: 8px 12px;
            background: #3498db; /* Biru */
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .filter-controls button:hover {
            background: #2980b9;
        }
        .multi-select { /* Gaya untuk select multiple */
            height: auto;
            min-height: 36px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Dashboard Analisis Pelanggan (RFM)</h1>

        <!-- Ringkasan Metrik -->
        <div class="summary-grid">
            <div class="card blue"><h3>AOV</h3><div class="value">Rp <?= number_format($aov, 0, ',', '.') ?></div></div>
            <div class="card green"><h3>Rata-rata Order/Pelanggan</h3><div class="value"><?= number_format($avg_orders_per_customer, 2) ?></div></div>
            <div class="card gold"><h3>Top Spender (10%)</h3><div class="value"><?= $topSpenderCount ?></div></div>
            <div class="card purple"><h3>Frequent Buyer (>2x & top 10%)</h3><div class="value"><?= $frequentBuyerCount ?></div></div>
            <div class="card gray"><h3>Dormant (>90 hari)</h3><div class="value"><?= $dormantCount ?></div></div>
            <div class="card teal"><h3>Total Pelanggan Aktif</h3><div class="value"><?= number_format($total_pelanggan) ?></div></div>
            <div class="card orange"><h3>Produk Terlaris</h3><div class="value"><?= $topProductName ?></div></div>
            <div class="card red"><h3>Bulan Penjualan Tertinggi</h3><div class="value"><?= $topMonthLabel ?></div></div>
        </div>

        <!-- Filter Controls -->
        <div class="table-container">
            <div class="table-header">
                <h2>Filter Data</h2>
                <div class="controls">
                    <!-- Tombol Salin Tetap di sini jika diperlukan -->
                </div>
            </div>
            <div style="padding: 15px;">
                <form method="GET" id="filterForm">
                    <div class="filter-controls">
                        <div class="form-group">
                            <label for="date_start">Tanggal Awal:</label>
                            <input type="date" id="date_start" name="date_start" value="<?= htmlspecialchars($date_start_filter) ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_end">Tanggal Akhir:</label>
                            <input type="date" id="date_end" name="date_end" value="<?= htmlspecialchars($date_end_filter) ?>">
                        </div>
                        <div class="form-group">
                            <label for="segment">Segmentasi:</label>
                            <select id="segment" name="segment">
                                <option value="">Semua</option>
                                <option value="Champions" <?= $segment_filter === 'Champions' ? 'selected' : '' ?>>Champions</option>
                                <option value="Loyal Repeaters" <?= $segment_filter === 'Loyal Repeaters' ? 'selected' : '' ?>>Loyal Repeaters</option>
                                <option value="New Customers" <?= $segment_filter === 'New Customers' ? 'selected' : '' ?>>New Customers</option>
                                <option value="At Risk" <?= $segment_filter === 'At Risk' ? 'selected' : '' ?>>At Risk</option>
                                <option value="Others" <?= $segment_filter === 'Others' ? 'selected' : '' ?>>Others</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="include_produk">Include Produk:</label>
                            <select id="include_produk" name="include_produk[]" class="multi-select" multiple size="4">
                                <option value="">Pilih Produk...</option>
                                <?php foreach ($allProducts as $prod): ?>
                                    <option value="<?= $prod['id'] ?>" <?= in_array($prod['id'], $include_produk_filter) ? 'selected' : '' ?>><?= htmlspecialchars($prod['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exclude_produk">Exclude Produk:</label>
                            <select id="exclude_produk" name="exclude_produk[]" class="multi-select" multiple size="4">
                                <option value="">Pilih Produk...</option>
                                <?php foreach ($allProducts as $prod): ?>
                                    <option value="<?= $prod['id'] ?>" <?= in_array($prod['id'], $exclude_produk_filter) ? 'selected' : '' ?>><?= htmlspecialchars($prod['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit">Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel RFM -->
        <div class="table-container">
            <div class="table-header">
                <h2>Segmentasi Pelanggan Berdasarkan RFM</h2>
                <div class="controls">
                    <button id="copyBtn">Salin Tabel</button>
                    <span id="copyMessage" class="success-message">✔ Disalin!</span>
                </div>
            </div>
            <div class="scrollable-table">
                <table id="customerTable">
                    <thead>
                        <tr>
                            <th>No.</th> <!-- Kolom Nomor -->
                            <th data-sort="name">Nama</th>
                            <th data-sort="recency">Recency</th>
                            <th data-sort="frequency">Frequency</th>
                            <th data-sort="monetary">Monetary (Rp)</th>
                            <th data-sort="rfm_score">Skor RFM</th>
                            <th>Segmentasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customersWithSegment)): ?>
                            <tr class="empty-row"><td colspan="7">Tidak ada data pelanggan yang sesuai filter.</td></tr>
                        <?php else:
                            $counter = 1;
                            foreach ($customersWithSegment as $cust):
                        ?>
                                <tr 
                                    data-recency="<?= $cust['recency_days'] ?>" 
                                    data-frequency="<?= $cust['frequency'] ?>" 
                                    data-monetary="<?= $cust['monetary'] ?>"
                                    data-name="<?= htmlspecialchars($cust['nama_pelanggan']) ?>"
                                    data-rfm-score="<?= (int)($cust['R']*100 + $cust['F']*10 + $cust['M']) ?>"
                                    data-segment="<?= htmlspecialchars($cust['segment']) ?>"
                                    data-wa="<?= htmlspecialchars($cust['nomor_wa']) ?>"
                                >
                                    <td class="row-number"><?= $counter++ ?></td>
                                    <td><?= htmlspecialchars($cust['nama_pelanggan']) ?></td>
                                    <td class="<?= $cust['recency_class'] ?>"><?= htmlspecialchars($cust['recency_display']) ?></td>
                                    <td><?= $cust['frequency'] ?></td>
                                    <td>Rp <?= number_format($cust['monetary'], 0, ',', '.') ?></td>
                                    <td><strong><?= $cust['rfm_score'] ?></strong></td>
                                    <td><?= htmlspecialchars($cust['segment']) ?></td>
                                </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Form Follow-up Massal -->
        <div class="followup-container">
            <div class="followup-header">
                <h2>Kirim Follow-up Massal</h2>
            </div>
            <form id="massFollowupForm" method="POST" action="send_mass_followup.php"> <!-- Ganti action ke file baru -->
                <div class="form-group">
                    <label for="pesan_followup">Pesan Follow-up:</label>
                    <textarea id="pesan_followup" name="pesan_followup" placeholder="Tulis pesan Anda di sini... Gunakan [nama] untuk menyisipkan nama pelanggan." required></textarea>
                </div>
                <div class="form-group">
                    <label for="wa_account">Akun WA Gateway:</label>
                    <select id="wa_account" name="wa_account" required>
                        <option value="">Pilih Akun</option>
                        <?php foreach ($onesenderAccounts as $account): ?>
                            <option value="<?= htmlspecialchars($account) ?>" <?= $account === 'default' ? 'selected' : '' ?>><?= htmlspecialchars($account) ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($onesenderAccounts)): ?>
                            <option value="default">default (Jika konfigurasi tidak ditemukan)</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Kirim ke Pelanggan (Rentang Baris):</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" id="batch_start" name="batch_start" min="1" placeholder="Dari No." title="Nomor baris awal (dimulai dari 1)">
                        <input type="number" id="batch_end" name="batch_end" min="1" placeholder="Sampai No." title="Nomor baris akhir">
                    </div>
                </div>
                
                 <div class="batch-info">
                    <p><strong>Catatan:</strong></p>
                    <ul>
                        <li>Pesan akan dikirim ke pelanggan yang muncul di tabel di atas setelah filter diterapkan.</li>
                        <li>Gunakan kolom "No." untuk menentukan rentang baris. Misalnya, jika tabel menampilkan 200 pelanggan, "Dari No. 1" ke "Sampai No. 100" akan memilih 100 pelanggan pertama.</li>
                        <li>Pastikan nomor WA pelanggan valid.</li>
                        <li>Gunakan placeholder <code>[nama]</code> dalam pesan untuk menyisipkan nama pelanggan.</li>
                    </ul>
                </div>
                <div class="form-actions">
                    <button type="submit" id="sendFollowupBtn">Kirim Follow-up</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('customerTable');
            const headers = table.querySelectorAll('thead th[data-sort]');
            let currentSort = { column: null, direction: 'asc' };

            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    const column = header.getAttribute('data-sort');
                    if (currentSort.column === column) {
                        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSort.column = column;
                        currentSort.direction = 'desc';
                    }
                    sortTable(column, currentSort.direction);
                    updateHeaderArrows(headers, column, currentSort.direction);
                });
            });

            function sortTable(column, direction) {
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-row)'));
                rows.sort((a, b) => {
                    let valA, valB;
                    if (column === 'name') {
                        valA = a.getAttribute('data-name').toLowerCase();
                        valB = b.getAttribute('data-name').toLowerCase();
                        return direction === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
                    } else if (column === 'rfm_score') {
                        valA = parseInt(a.getAttribute('data-rfm-score')) || 0;
                        valB = parseInt(b.getAttribute('data-rfm-score')) || 0;
                        return direction === 'asc' ? valA - valB : valB - valA;
                    } else {
                        valA = parseFloat(a.getAttribute(`data-${column}`)) || 0;
                        valB = parseFloat(b.getAttribute(`data-${column}`)) || 0;
                        return direction === 'asc' ? valA - valB : valB - valA;
                    }
                });
                // Reset nomor urut setelah sorting
                rows.forEach((row, index) => {
                    const numberCell = row.querySelector('.row-number');
                    if (numberCell) {
                         numberCell.textContent = index + 1;
                    }
                });
                rows.forEach(row => tbody.appendChild(row));
            }

            function updateHeaderArrows(headers, activeColumn, direction) {
                headers.forEach(h => {
                    h.textContent = h.textContent.replace(/ ↑| ↓/g, '');
                    if (h.getAttribute('data-sort') === activeColumn) {
                        const arrow = direction === 'asc' ? ' ↑' : ' ↓';
                        h.textContent += arrow;
                    }
                });
            }

            // --- Logika Filter dan Pemilihan Baris ---
            const filterForm = document.getElementById('filterForm');
            const massForm = document.getElementById('massFollowupForm');
            const batchStartInput = document.getElementById('batch_start');
            const batchEndInput = document.getElementById('batch_end');
            const sendBtn = document.getElementById('sendFollowupBtn');

            // Mencegah submit form follow-up jika range tidak valid
            massForm.addEventListener('submit', function(e) {
                if (!validateBatchRange()) {
                    e.preventDefault();
                    alert('Rentang baris tidak valid. Pastikan "Sampai No." lebih besar atau sama dengan "Dari No." dan tidak melebihi jumlah baris.');
                }
            });

            function validateBatchRange() {
                const start = parseInt(batchStartInput.value);
                const end = parseInt(batchEndInput.value);

                // Jika kosong, kirim semua
                if (isNaN(start) && isNaN(end)) {
                    return true;
                }

                // Harus angka
                if (isNaN(start) || isNaN(end)) {
                    return false;
                }

                // Start harus >= 1
                if (start < 1) {
                    return false;
                }

                // End harus >= Start
                if (end < start) {
                    return false;
                }

                // Validasi apakah range melebihi jumlah baris
                const totalRows = document.querySelectorAll('#customerTable tbody tr:not(.empty-row)').length;
                if (end > totalRows) {
                    alert(`"Sampai No." melebihi jumlah baris yang ditampilkan (${totalRows}).`);
                    return false;
                }

                return true;
            }

            // --- Fungsi Salin Tabel ---
            document.getElementById('copyBtn').addEventListener('click', function () {
                const table = document.getElementById('customerTable');
                let text = '';
                const headers = Array.from(table.querySelectorAll('thead th')).map(th => {
                    return th.textContent.replace(/ ↑| ↓/g, '').trim();
                });
                text += headers.join('\t') + '\n';
                const rows = table.querySelectorAll('tbody tr:not(.empty-row)');
                rows.forEach(row => {
                    const cells = Array.from(row.querySelectorAll('td')).map(td => {
                        let txt = td.innerText.trim();
                        return txt.replace(/\t/g, ' ').replace(/\n/g, ' ');
                    });
                    text += cells.join('\t') + '\n';
                });
                navigator.clipboard.writeText(text).then(() => {
                    const msg = document.getElementById('copyMessage');
                    msg.style.display = 'inline';
                    setTimeout(() => msg.style.display = 'none', 2000);
                }).catch(err => {
                    alert('Gagal menyalin: ' + err);
                });
            });

            // --- Update filter URL tanpa refresh untuk salin ---
            // Filter diterapkan via GET, jaya form submit akan reload halaman dengan filter baru.
            // Tidak perlu logika JS tambahan untuk menerapkan filter secara JS murni karena PHP handle server-side.

        });
    </script>
</body>
</html>