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

// === Ambil Data Pelanggan ===
$rfmSql = "
SELECT 
    p.id,
    p.nama AS nama_pelanggan,
    MAX(t.tanggal_transaksi) AS last_purchase,
    COUNT(t.id) AS frequency,
    COALESCE(SUM(t.total_harga), 0) AS monetary
FROM pelanggan p
INNER JOIN transaksi t ON p.id = t.pelanggan_id AND t.status = 'selesai'
GROUP BY p.id, p.nama
ORDER BY monetary DESC, last_purchase DESC;
";
$rfmCustomers = $pdo->query($rfmSql)->fetchAll(PDO::FETCH_ASSOC);
$now = new DateTime();
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
        .controls { display: flex; gap: 10px; }
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
                            <th data-sort="name">Nama</th>
                            <th data-sort="recency">Recency</th>
                            <th data-sort="frequency">Frequency</th>
                            <th data-sort="monetary">Monetary (Rp)</th>
                            <th data-sort="rfm_score">Skor RFM</th>
                            <th>Segmentasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rfmCustomers)): ?>
                            <tr class="empty-row"><td colspan="6">Belum ada transaksi selesai.</td></tr>
                        <?php else:
                            foreach ($rfmCustomers as $cust):
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

                                // Format Recency Display
                                if ($recencyDays <= 30) {
                                    $recencyDisplay = $recencyDays . ' hari';
                                    $recencyClass = 'recency-low';
                                } elseif ($recencyDays <= 60) {
                                    $recencyDisplay = $recencyDays . ' hari';
                                    $recencyClass = 'recency-medium';
                                } else {
                                    $y = $interval->y;
                                    $m = $interval->m;
                                    $parts = [];
                                    if ($y > 0) $parts[] = $y . ' thn';
                                    if ($m > 0) $parts[] = $m . ' bln';
                                    $recencyDisplay = implode(' ', $parts) ?: $recencyDays . ' hari';
                                    $recencyClass = 'recency-high';
                                }
                        ?>
                                <tr 
                                    data-recency="<?= $recencyDays ?>" 
                                    data-frequency="<?= $frequency ?>" 
                                    data-monetary="<?= $monetary ?>"
                                    data-name="<?= htmlspecialchars($cust['nama_pelanggan']) ?>"
                                    data-rfm-score="<?= (int)($R*100 + $F*10 + $M) ?>"
                                >
                                    <td><?= htmlspecialchars($cust['nama_pelanggan']) ?></td>
                                    <td class="<?= $recencyClass ?>"><?= htmlspecialchars($recencyDisplay) ?></td>
                                    <td><?= $frequency ?></td>
                                    <td>Rp <?= number_format($monetary, 0, ',', '.') ?></td>
                                    <td><strong><?= $rfmScore ?></strong></td>
                                    <td><?= htmlspecialchars($segment) ?></td>
                                </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
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
        });

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
    </script>
</body>
</html>