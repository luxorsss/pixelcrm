<?php
require_once __DIR__ . '/../../../includes/init.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$segments = $input['segments'] ?? [];
$include_produk = array_filter(array_map('intval', $input['include_produk'] ?? []));
$exclude_produk = array_filter(array_map('intval', $input['exclude_produk'] ?? []));

$tgl_mulai = null; $tgl_akhir = null;
if (!empty($input['tanggal_mulai']) && !empty($input['tanggal_akhir'])) {
    $tgl_mulai = date('Y-m-d', strtotime($input['tanggal_mulai']));
    $tgl_akhir = date('Y-m-d', strtotime($input['tanggal_akhir']));
}

$eligible_ids = [];

// Handle Include Produk
if (!empty($include_produk)) {
    $placeholders = str_repeat('?,', count($include_produk) - 1) . '?';
    $sql_in = "SELECT DISTINCT p.id FROM pelanggan p JOIN transaksi t ON p.id = t.pelanggan_id AND t.status = 'selesai' JOIN detail_transaksi dt ON t.id = dt.transaksi_id WHERE dt.produk_id IN ($placeholders)";
    $res_in = fetchAll($sql_in, $include_produk);
    $eligible_ids = array_column($res_in ?: [], 'id');
} else {
    $res_all = fetchAll("SELECT DISTINCT p.id FROM pelanggan p JOIN transaksi t ON p.id = t.pelanggan_id AND t.status = 'selesai'");
    $eligible_ids = array_column($res_all ?: [], 'id');
}

// Handle Exclude Produk
if (!empty($exclude_produk)) {
    $placeholders_ex = str_repeat('?,', count($exclude_produk) - 1) . '?';
    $sql_ex = "SELECT DISTINCT p.id FROM pelanggan p JOIN transaksi t ON p.id = t.pelanggan_id AND t.status = 'selesai' JOIN detail_transaksi dt ON t.id = dt.transaksi_id WHERE dt.produk_id IN ($placeholders_ex)";
    $res_ex = fetchAll($sql_ex, $exclude_produk);
    $excluded_ids = array_column($res_ex ?: [], 'id');
    $eligible_ids = array_values(array_diff($eligible_ids, $excluded_ids));
}

if (empty($eligible_ids)) {
    echo json_encode(['success' => true, 'total' => 0, 'total_batch' => 0, 'penerima' => []]); exit;
}

$placeholders = str_repeat('?,', count($eligible_ids) - 1) . '?';
$where = "p.id IN ($placeholders)";
$params = $eligible_ids;

if ($tgl_mulai && $tgl_akhir) {
    $where .= " AND t.tanggal_transaksi BETWEEN ? AND ?";
    $params[] = $tgl_mulai . ' 00:00:00';
    $params[] = $tgl_akhir . ' 23:59:59';
}

if (!empty($segments)) {
    $seg_placeholders = str_repeat('?,', count($segments) - 1) . '?';
    $where .= " AND p.rfm_segment IN ($seg_placeholders)";
    $params = array_merge($params, $segments);
}

$sql = "
SELECT
    p.id, p.nama, p.nomor_wa, p.rfm_recency, p.rfm_frequency, p.rfm_monetary, p.rfm_segment,
    MAX(t.tanggal_transaksi) AS last_transaction,
    DATEDIFF(CURDATE(), MAX(t.tanggal_transaksi)) AS days_since_last,
    COUNT(DISTINCT t.id) AS total_transaksi, COALESCE(SUM(dt.harga), 0) AS total_spending
FROM pelanggan p
JOIN transaksi t ON p.id = t.pelanggan_id AND t.status = 'selesai'
JOIN detail_transaksi dt ON t.id = dt.transaksi_id
WHERE $where
GROUP BY p.id, p.nama, p.nomor_wa
ORDER BY p.id ASC
";

$results = fetchAll($sql, $params) ?: [];
$filtered = [];

foreach ($results as $row) {
    $filtered[] = [
        'nama' => htmlspecialchars($row['nama'] ?? '', ENT_QUOTES, 'UTF-8'),
        'nomor_wa' => $row['nomor_wa'],
        'pembelian_terakhir' => $row['last_transaction'] ? date('Y-m-d', strtotime($row['last_transaction'])) : '-',
        'recency_hari' => (int)$row['days_since_last'],
        'frekuensi' => (int)$row['total_transaksi'],
        'monetary' => (float)$row['total_spending'],
        'segment' => $row['rfm_segment'] ?? 'Others'
    ];
}

$urut_awal = max(1, (int)($input['urut_awal'] ?? 1));
$urut_akhir = max($urut_awal, (int)($input['urut_akhir'] ?? 100));
$filtered_batch = array_slice($filtered, $urut_awal - 1, $urut_akhir - $urut_awal + 1);

echo json_encode([
    'success' => true, 'total' => count($filtered), 'total_batch' => count($filtered_batch), 'penerima' => $filtered_batch
]);
?>