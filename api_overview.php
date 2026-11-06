<?php
/**
 * PixelCRM - Public API Overview Endpoint
 * Read-only summary metrics for EduMuslim growth and AI monitoring.
 * No authentication required.
 */

// Avoid CLI notice
if (!isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once __DIR__ . '/includes/init.php';

if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $sevenDaysAgo = date('Y-m-d 00:00:00', strtotime('-7 days'));
    $firstDayThisMonth = date('Y-m-01 00:00:00');

    // 1. High-level counts
    $total_pelanggan = (int)(fetchRow("SELECT COUNT(*) as total FROM pelanggan")['total'] ?? 0);
    $total_produk = (int)(fetchRow("SELECT COUNT(*) as total FROM produk")['total'] ?? 0);

    // 2. Status Breakdown & Overall Revenue
    $status_rows = fetchAll("
        SELECT 
            status, 
            COUNT(*) as count, 
            COALESCE(SUM(total_harga), 0) as total_nominal 
        FROM transaksi 
        GROUP BY status
    ");

    $status_stats = [
        'selesai' => ['count' => 0, 'nominal' => 0.0],
        'pending' => ['count' => 0, 'nominal' => 0.0],
        'diproses' => ['count' => 0, 'nominal' => 0.0],
        'batal' => ['count' => 0, 'nominal' => 0.0]
    ];
    $total_transaksi = 0;

    foreach ($status_rows as $row) {
        $st = $row['status'];
        $cnt = (int)$row['count'];
        $nom = (float)$row['total_nominal'];
        $total_transaksi += $cnt;
        if (isset($status_stats[$st])) {
            $status_stats[$st]['count'] = $cnt;
            $status_stats[$st]['nominal'] = $nom;
        } else {
            $status_stats[$st] = ['count' => $cnt, 'nominal' => $nom];
        }
    }

    $completed_count = $status_stats['selesai']['count'] ?? 0;
    $pending_count = $status_stats['pending']['count'] ?? 0;
    $total_revenue = $status_stats['selesai']['nominal'] ?? 0.0;
    $pending_revenue = $status_stats['pending']['nominal'] ?? 0.0;
    $conversion_rate = $total_transaksi > 0 ? round(($completed_count / $total_transaksi) * 100, 2) : 0;

    // 3. Time breakdowns
    // Today
    $today_stats = fetchRow("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            COALESCE(SUM(CASE WHEN status = 'selesai' THEN total_harga ELSE 0 END), 0) as revenue
        FROM transaksi 
        WHERE DATE(tanggal_transaksi) = '$today'
    ");

    // Yesterday
    $yesterday_stats = fetchRow("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            COALESCE(SUM(CASE WHEN status = 'selesai' THEN total_harga ELSE 0 END), 0) as revenue
        FROM transaksi 
        WHERE DATE(tanggal_transaksi) = '$yesterday'
    ");

    // Last 7 Days
    $seven_days_stats = fetchRow("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            COALESCE(SUM(CASE WHEN status = 'selesai' THEN total_harga ELSE 0 END), 0) as revenue
        FROM transaksi 
        WHERE tanggal_transaksi >= '$sevenDaysAgo'
    ");

    // This Month
    $this_month_stats = fetchRow("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            COALESCE(SUM(CASE WHEN status = 'selesai' THEN total_harga ELSE 0 END), 0) as revenue
        FROM transaksi 
        WHERE tanggal_transaksi >= '$firstDayThisMonth'
    ");

    // 4. Focus Product: eBook Qila (ID 6 & 19)
    $qila_stats = fetchRow("
        SELECT 
            COUNT(DISTINCT t.id) as total_transaksi,
            SUM(CASE WHEN t.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending,
            COALESCE(SUM(CASE WHEN t.status = 'selesai' THEN t.total_harga ELSE 0 END), 0) as revenue_selesai,
            COALESCE(SUM(CASE WHEN t.status = 'pending' THEN t.total_harga ELSE 0 END), 0) as potential_revenue
        FROM transaksi t
        JOIN detail_transaksi dt ON t.id = dt.transaksi_id
        WHERE dt.produk_id IN (6, 19)
    ");

    $qila_this_month = fetchRow("
        SELECT 
            COUNT(DISTINCT t.id) as total_transaksi,
            SUM(CASE WHEN t.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending,
            COALESCE(SUM(CASE WHEN t.status = 'selesai' THEN t.total_harga ELSE 0 END), 0) as revenue_selesai
        FROM transaksi t
        JOIN detail_transaksi dt ON t.id = dt.transaksi_id
        WHERE dt.produk_id IN (6, 19) AND t.tanggal_transaksi >= '$firstDayThisMonth'
    ");

    // 4c. Sisa Usia Campaign Focus & Daily Breakdown (ID 29 & Bundling IDs 18, 26)
    $sisa_usia_daily = fetchAll("
        SELECT 
            DATE(t.tanggal_transaksi) as tanggal,
            COUNT(DISTINCT t.id) as total_checkout,
            SUM(CASE WHEN t.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending,
            COALESCE(SUM(CASE WHEN t.status = 'selesai' AND dt.produk_id = 29 THEN dt.harga ELSE 0 END), 0) as omzet_pokok_sisa_usia,
            COALESCE(SUM(CASE WHEN t.status = 'selesai' AND dt.produk_id != 29 THEN dt.harga ELSE 0 END), 0) as omzet_bundling,
            COALESCE(SUM(CASE WHEN t.status = 'selesai' THEN dt.harga ELSE 0 END), 0) as total_omzet_riil
        FROM transaksi t
        JOIN detail_transaksi dt ON t.id = dt.transaksi_id
        WHERE t.id IN (
            SELECT DISTINCT transaksi_id FROM detail_transaksi WHERE produk_id = 29
        )
        GROUP BY DATE(t.tanggal_transaksi)
        ORDER BY tanggal ASC
    ");

    $sisa_usia_bundling_stats = fetchRow("
        SELECT 
            COUNT(DISTINCT t.id) as total_order_selesai,
            COUNT(DISTINCT CASE WHEN dt.produk_id = 18 THEN t.id END) as order_include_daqo,
            COUNT(DISTINCT CASE WHEN dt.produk_id = 26 THEN t.id END) as order_include_stki,
            COUNT(DISTINCT CASE WHEN dt.produk_id = 18 AND t.id IN (SELECT transaksi_id FROM detail_transaksi WHERE produk_id = 26) THEN t.id END) as order_take_all_bundle,
            COALESCE(SUM(CASE WHEN dt.produk_id = 29 THEN dt.harga ELSE 0 END), 0) as omzet_pokok,
            COALESCE(SUM(CASE WHEN dt.produk_id != 29 THEN dt.harga ELSE 0 END), 0) as omzet_bundling,
            COALESCE(SUM(dt.harga), 0) as grand_total_revenue
        FROM transaksi t
        JOIN detail_transaksi dt ON t.id = dt.transaksi_id
        WHERE t.status = 'selesai'
          AND t.id IN (SELECT DISTINCT transaksi_id FROM detail_transaksi WHERE produk_id = 29)
    ");

    // 4d. Dynamic Daily Breakdown per Product if Requested (?produk_id=XX)
    $req_produk_id = isset($_GET['produk_id']) ? (int)$_GET['produk_id'] : 0;
    $product_daily_breakdown = [];
    if ($req_produk_id > 0) {
        $product_daily_breakdown = fetchAll("
            SELECT 
                DATE(t.tanggal_transaksi) as tanggal,
                COUNT(DISTINCT t.id) as total_transaksi,
                SUM(CASE WHEN t.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending,
                COALESCE(SUM(CASE WHEN t.status = 'selesai' THEN dt.harga ELSE 0 END), 0) as omzet_produk
            FROM transaksi t
            JOIN detail_transaksi dt ON t.id = dt.transaksi_id
            WHERE dt.produk_id = ?
            GROUP BY DATE(t.tanggal_transaksi)
            ORDER BY tanggal ASC
        ", [$req_produk_id]);
    }

    // 4b. Day of Week Distribution (All-Time Completed Orders)
    $day_of_week_rows = fetchAll("
        SELECT 
            DAYNAME(tanggal_transaksi) as day_name,
            DAYOFWEEK(tanggal_transaksi) as day_num,
            COUNT(*) as total_orders,
            COALESCE(SUM(total_harga), 0) as total_revenue
        FROM transaksi
        WHERE status = 'selesai'
        GROUP BY day_num, day_name
        ORDER BY day_num ASC
    ");

    // 5. Products Leaderboard
    $products_leaderboard = fetchAll("
        SELECT 
            p.id,
            p.nama,
            p.harga,
            COUNT(CASE WHEN t.status = 'selesai' THEN 1 END) as terjual_selesai,
            COUNT(CASE WHEN t.status = 'pending' THEN 1 END) as jumlah_pending,
            COALESCE(SUM(CASE WHEN t.status = 'selesai' THEN dt.harga ELSE 0 END), 0) as total_omzet
        FROM produk p
        LEFT JOIN detail_transaksi dt ON p.id = dt.produk_id
        LEFT JOIN transaksi t ON dt.transaksi_id = t.id
        GROUP BY p.id, p.nama, p.harga
        ORDER BY terjual_selesai DESC
        LIMIT 10
    ");

    // 6. Customer RFM Segments
    $rfm_rows = fetchAll("
        SELECT 
            COALESCE(rfm_segment, 'Unassigned') as segment, 
            COUNT(*) as count 
        FROM pelanggan 
        GROUP BY rfm_segment 
        ORDER BY count DESC
    ");
    $rfm_distribution = [];
    foreach ($rfm_rows as $row) {
        $pct = $total_pelanggan > 0 ? round(($row['count'] / $total_pelanggan) * 100, 1) : 0;
        $rfm_distribution[] = [
            'segment' => $row['segment'],
            'count' => (int)$row['count'],
            'percentage' => $pct
        ];
    }

    // 7. Recent Transactions (Sanitized / Masked for Privacy)
    $recent_rows = fetchAll("
        SELECT 
            t.id,
            t.total_harga,
            t.status,
            t.tanggal_transaksi,
            p.nama as pelanggan_nama,
            p.nomor_wa,
            GROUP_CONCAT(pr.nama SEPARATOR ', ') as produk_nama
        FROM transaksi t
        LEFT JOIN pelanggan p ON t.pelanggan_id = p.id
        LEFT JOIN detail_transaksi dt ON t.id = dt.transaksi_id
        LEFT JOIN produk pr ON dt.produk_id = pr.id
        GROUP BY t.id
        ORDER BY t.tanggal_transaksi DESC
        LIMIT 10
    ");

    $recent_transactions = [];
    foreach ($recent_rows as $item) {
        $wa = trim((string)$item['nomor_wa']);
        $masked_wa = strlen($wa) > 6 
            ? substr($wa, 0, 4) . '****' . substr($wa, -3)
            : '****';

        $recent_transactions[] = [
            'id' => (int)$item['id'],
            'produk' => $item['produk_nama'] ?? 'Unknown',
            'total_harga' => (float)$item['total_harga'],
            'status' => $item['status'],
            'tanggal' => $item['tanggal_transaksi'],
            'pelanggan_nama' => $item['pelanggan_nama'],
            'nomor_wa_masked' => $masked_wa
        ];
    }

    // Prepare JSON payload
    $response = [
        'status' => 'success',
        'meta' => [
            'app' => APP_NAME,
            'version' => APP_VERSION,
            'generated_at' => $now,
            'timezone' => date_default_timezone_get(),
            'notes' => 'Public read-only metrics overview for EduMuslim Growth & AI team'
        ],
        'overall_summary' => [
            'total_customers' => $total_pelanggan,
            'total_products' => $total_produk,
            'total_orders' => $total_transaksi,
            'completed_orders' => $completed_count,
            'pending_orders' => $pending_count,
            'processed_orders' => $status_stats['diproses']['count'] ?? 0,
            'canceled_orders' => $status_stats['batal']['count'] ?? 0,
            'conversion_rate_percent' => $conversion_rate,
            'total_revenue_idr' => $total_revenue,
            'potential_pending_revenue_idr' => $pending_revenue
        ],
        'time_metrics' => [
            'today' => [
                'date' => $today,
                'total_orders' => (int)($today_stats['total'] ?? 0),
                'completed_orders' => (int)($today_stats['selesai'] ?? 0),
                'pending_orders' => (int)($today_stats['pending'] ?? 0),
                'revenue_idr' => (float)($today_stats['revenue'] ?? 0)
            ],
            'yesterday' => [
                'date' => $yesterday,
                'total_orders' => (int)($yesterday_stats['total'] ?? 0),
                'completed_orders' => (int)($yesterday_stats['selesai'] ?? 0),
                'pending_orders' => (int)($yesterday_stats['pending'] ?? 0),
                'revenue_idr' => (float)($yesterday_stats['revenue'] ?? 0)
            ],
            'last_7_days' => [
                'total_orders' => (int)($seven_days_stats['total'] ?? 0),
                'completed_orders' => (int)($seven_days_stats['selesai'] ?? 0),
                'pending_orders' => (int)($seven_days_stats['pending'] ?? 0),
                'revenue_idr' => (float)($seven_days_stats['revenue'] ?? 0)
            ],
            'this_month' => [
                'month' => date('F Y'),
                'total_orders' => (int)($this_month_stats['total'] ?? 0),
                'completed_orders' => (int)($this_month_stats['selesai'] ?? 0),
                'pending_orders' => (int)($this_month_stats['pending'] ?? 0),
                'revenue_idr' => (float)($this_month_stats['revenue'] ?? 0)
            ]
        ],
        'qila_ebook_focus' => [
            'product_name' => 'eBook Qila (Panduan Qiyamul Lail)',
            'monitored_product_ids' => [6, 19],
            'all_time_orders' => (int)($qila_stats['total_transaksi'] ?? 0),
            'all_time_completed' => (int)($qila_stats['selesai'] ?? 0),
            'all_time_pending' => (int)($qila_stats['pending'] ?? 0),
            'all_time_revenue_idr' => (float)($qila_stats['revenue_selesai'] ?? 0),
            'all_time_pending_revenue_idr' => (float)($qila_stats['potential_revenue'] ?? 0),
            'this_month_orders' => (int)($qila_this_month['total_transaksi'] ?? 0),
            'this_month_completed' => (int)($qila_this_month['selesai'] ?? 0),
            'this_month_revenue_idr' => (float)($qila_this_month['revenue_selesai'] ?? 0)
        ],
        'sisa_usia_campaign' => [
            'product_name' => 'eBook Sisa Usia (Persiapan Pulang)',
            'product_id' => 29,
            'bundling_summary' => [
                'total_orders_selesai' => (int)($sisa_usia_bundling_stats['total_order_selesai'] ?? 0),
                'order_with_daqo' => (int)($sisa_usia_bundling_stats['order_include_daqo'] ?? 0),
                'order_with_stki' => (int)($sisa_usia_bundling_stats['order_include_stki'] ?? 0),
                'order_take_all_bundle' => (int)($sisa_usia_bundling_stats['order_take_all_bundle'] ?? 0),
                'omzet_pokok_sisa_usia' => (float)($sisa_usia_bundling_stats['omzet_pokok'] ?? 0),
                'omzet_tambahan_bundling' => (float)($sisa_usia_bundling_stats['omzet_bundling'] ?? 0),
                'grand_total_revenue' => (float)($sisa_usia_bundling_stats['grand_total_revenue'] ?? 0)
            ],
            'daily_breakdown' => $sisa_usia_daily
        ],
        'product_daily_breakdown' => $product_daily_breakdown,
        'products_leaderboard' => $products_leaderboard,
        'rfm_customer_segments' => $rfm_distribution,
        'recent_orders' => $recent_transactions,
        'day_of_week_distribution' => $day_of_week_rows,
        'followup_system' => [
            'queue' => fetchAll("
                SELECT fl.id, fl.transaksi_id, fl.pelanggan_id, p.nama as pelanggan_nama, p.nomor_wa,
                       fm.produk_id, pr.nama as produk_nama, fm.urutan, fm.nama_pesan, fm.delay_value, fm.delay_unit,
                       fl.status, fl.jadwal_kirim, fl.waktu_kirim
                FROM followup_logs fl
                JOIN followup_messages fm ON fl.followup_message_id = fm.id
                JOIN pelanggan p ON fl.pelanggan_id = p.id
                JOIN produk pr ON fm.produk_id = pr.id
                ORDER BY fl.id DESC
                LIMIT 20
            "),
            'sequences' => fetchAll("
                SELECT fm.id, fm.produk_id, pr.nama as produk_nama, fm.urutan, fm.nama_pesan, fm.delay_value, fm.delay_unit, fm.status
                FROM followup_messages fm
                JOIN produk pr ON fm.produk_id = pr.id
                ORDER BY fm.produk_id ASC, fm.urutan ASC
            ")
        ]
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
