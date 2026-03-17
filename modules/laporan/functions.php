<?php
/**
 * Laporan Manager - Versi Nominal Profit
 * Fokus pada laporan dasar dengan performa tinggi
 */

class LaporanManager {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * Ambil statistik overview bulanan
     */
    public function getOverviewBulanan($bulan = null, $tahun = null) {
        $bulan = $bulan ?: date('n');
        $tahun = $tahun ?: date('Y');

        // 1. Total transaksi & pelanggan (berdasarkan tanggal_transaksi)
        $sql1 = "SELECT 
                    COUNT(t.id) as total_transaksi,
                    COUNT(DISTINCT t.pelanggan_id) as total_pelanggan
                FROM transaksi t
                WHERE MONTH(t.tanggal_transaksi) = ? AND YEAR(t.tanggal_transaksi) = ?";

        // 2. Transaksi selesai (Pendapatan/Omzet & Profit)
        // PERUBAHAN: Sekarang hitung profit langsung dari kolom 'profit'
        // total_pendapatan = Total Omzet (dt.harga)
        // total_profit = Total Keuntungan (dt.profit)
        $sql2 = "SELECT 
					COUNT(DISTINCT t.id) as transaksi_selesai, -- Tambahkan DISTINCT di sini
					COALESCE(SUM(dt.harga), 0) as total_pendapatan, 
					COALESCE(SUM(dt.profit), 0) as total_profit,
					COALESCE(AVG(dt.harga), 0) as rata_rata_transaksi
				FROM transaksi t
				JOIN detail_transaksi dt ON t.id = dt.transaksi_id
				WHERE t.status = 'selesai'
				  AND t.waktu_selesai IS NOT NULL
				  AND MONTH(t.waktu_selesai) = ? AND YEAR(t.waktu_selesai) = ?";

        // 3. Transaksi pending
        $sql3 = "SELECT 
                    COUNT(t.id) as transaksi_pending
                FROM transaksi t
                WHERE t.status = 'pending'
                  AND MONTH(t.tanggal_transaksi) = ? AND YEAR(t.tanggal_transaksi) = ?";

        try {
            $row1 = fetchRow($sql1, [$bulan, $tahun]);
            $row2 = fetchRow($sql2, [$bulan, $tahun]);
            $row3 = fetchRow($sql3, [$bulan, $tahun]);

            return [
                'total_transaksi' => (int)($row1['total_transaksi'] ?? 0),
                'total_pelanggan' => (int)($row1['total_pelanggan'] ?? 0),
                'total_pendapatan' => (float)($row2['total_pendapatan'] ?? 0), // Ini Omzet
                'total_profit' => (float)($row2['total_profit'] ?? 0), // Ini Profit Bersih (Baru)
                'transaksi_selesai' => (int)($row2['transaksi_selesai'] ?? 0),
                'rata_rata_transaksi' => (float)($row2['rata_rata_transaksi'] ?? 0),
                'transaksi_pending' => (int)($row3['transaksi_pending'] ?? 0)
            ];
        } catch (Exception $e) {
            error_log("Error getOverviewBulanan: " . $e->getMessage());
            return [
                'total_transaksi' => 0, 'total_pelanggan' => 0, 
                'total_pendapatan' => 0, 'total_profit' => 0,
                'transaksi_selesai' => 0, 'rata_rata_transaksi' => 0, 
                'transaksi_pending' => 0
            ];
        }
    }
    
    /**
     * Ambil data penjualan harian dalam bulan
     */
    public function getPenjualanHarian($bulan, $tahun) {
        // PERUBAHAN: Mengambil profit dari kolom 'profit' langsung
        $sql = "SELECT 
                    DAY(t.waktu_selesai) as hari,
                    COUNT(DISTINCT t.id) as total_transaksi,
                    COALESCE(SUM(dt.profit), 0) as pendapatan -- Menampilkan grafik PROFIT harian
                FROM transaksi t
                JOIN detail_transaksi dt ON t.id = dt.transaksi_id
                WHERE t.status = 'selesai' 
                AND t.waktu_selesai IS NOT NULL
                AND MONTH(t.waktu_selesai) = ? 
                AND YEAR(t.waktu_selesai) = ?
                GROUP BY DAY(t.waktu_selesai)
                ORDER BY hari";

        try {
            $result = query($sql, [$bulan, $tahun]);
            $data = $result->fetch_all(MYSQLI_ASSOC);

            // Isi hari kosong dengan 0
            $jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
            $data_lengkap = [];

            for ($i = 1; $i <= $jumlah_hari; $i++) {
                $found = false;
                foreach ($data as $row) {
                    if ($row['hari'] == $i) {
                        $data_lengkap[] = [
                            'hari' => (int)$row['hari'],
                            'total_transaksi' => (int)($row['total_transaksi'] ?? 0),
                            'pendapatan' => (float)($row['pendapatan'] ?? 0)
                        ];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $data_lengkap[] = ['hari' => $i, 'total_transaksi' => 0, 'pendapatan' => 0];
                }
            }

            return $data_lengkap;
        } catch (Exception $e) {
            error_log("Error getPenjualanHarian: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ambil performa produk bulan ini
     */
    public function getPerformaProduk($bulan, $tahun, $limit = 10) {
        // PERUBAHAN: Menghitung profit dari kolom 'profit'
        $sql = "SELECT 
                    p.id, p.nama, p.harga,
                    COUNT(dt.id) as jumlah_terjual,
                    COALESCE(SUM(dt.profit), 0) as total_pendapatan, -- Profit yg dihasilkan produk ini
                    COUNT(DISTINCT t.pelanggan_id) as unique_customers
                FROM produk p
                LEFT JOIN detail_transaksi dt ON p.id = dt.produk_id
                LEFT JOIN transaksi t ON dt.transaksi_id = t.id 
                    AND t.status = 'selesai'
                    AND MONTH(t.tanggal_transaksi) = ? 
                    AND YEAR(t.tanggal_transaksi) = ?
                GROUP BY p.id, p.nama, p.harga
                HAVING jumlah_terjual > 0
                ORDER BY jumlah_terjual DESC, total_pendapatan DESC
                LIMIT ?";
                
        try {
            $result = query($sql, [$bulan, $tahun, $limit]);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            // Pastikan semua nilai numerik
            foreach ($data as &$row) {
                $row['jumlah_terjual'] = (int)($row['jumlah_terjual'] ?? 0);
                $row['total_pendapatan'] = (float)($row['total_pendapatan'] ?? 0);
                $row['unique_customers'] = (int)($row['unique_customers'] ?? 0);
                $row['harga'] = (float)($row['harga'] ?? 0);
            }
            
            return $data;
        } catch (Exception $e) {
            error_log("Error getPerformaProduk: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ambil customer terbaik bulan ini
     */
    public function getTopCustomers($bulan, $tahun, $limit = 5) {
        // PERUBAHAN: total_belanja menggunakan dt.harga (Omzet dari customer)
        // Bukan profit, karena 'belanja' berarti uang yang dikeluarkan customer
        $sql = "SELECT 
                    p.nama, p.nomor_wa,
                    COUNT(t.id) as total_transaksi,
                    COALESCE(SUM(dt.harga), 0) as total_belanja
                FROM pelanggan p
                JOIN transaksi t ON p.id = t.pelanggan_id
                JOIN detail_transaksi dt ON t.id = dt.transaksi_id
                WHERE t.status = 'selesai'
                    AND MONTH(t.waktu_selesai) = ? 
                    AND YEAR(t.waktu_selesai) = ?
                GROUP BY p.id, p.nama, p.nomor_wa
                ORDER BY total_belanja DESC
                LIMIT ?";
                
        try {
            $result = query($sql, [$bulan, $tahun, $limit]);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            foreach ($data as &$row) {
                $row['total_transaksi'] = (int)($row['total_transaksi'] ?? 0);
                $row['total_belanja'] = (float)($row['total_belanja'] ?? 0);
            }
            
            return $data;
        } catch (Exception $e) {
            error_log("Error getTopCustomers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ambil trend pendapatan 6 bulan terakhir
     */
    public function getTrendPendapatan($bulan_sekarang, $tahun_sekarang) {
        $data = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $target_bulan = $bulan_sekarang - $i;
            $target_tahun = $tahun_sekarang;
            
            if ($target_bulan <= 0) {
                $target_bulan += 12;
                $target_tahun -= 1;
            }
            
            // PERUBAHAN: Mengambil trend Profit
            $sql = "SELECT 
                        COALESCE(SUM(dt.profit), 0) as pendapatan
                    FROM transaksi t
                    JOIN detail_transaksi dt ON t.id = dt.transaksi_id 
                    WHERE status = 'selesai' 
                    AND waktu_selesai IS NOT NULL
                    AND MONTH(waktu_selesai) = ? 
                    AND YEAR(waktu_selesai) = ?";
                    
            try {
                $result = query($sql, [$target_bulan, $target_tahun]);
                $row = $result->fetch_assoc();
                
                $bulan_nama = [
                    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
                    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
                ];
                
                $data[] = [
                    'bulan' => $target_bulan,
                    'tahun' => $target_tahun,
                    'nama' => $bulan_nama[$target_bulan] . ' ' . $target_tahun,
                    'pendapatan' => (float)($row['pendapatan'] ?? 0)
                ];
            } catch (Exception $e) {
                error_log("Error getTrendPendapatan: " . $e->getMessage());
                $data[] = ['bulan' => $target_bulan, 'tahun' => $target_tahun, 'nama' => 'Error', 'pendapatan' => 0];
            }
        }
        
        return $data;
    }
    
    /**
     * Ambil detail penjualan dengan filter
     */
    public function getDetailPenjualan($filters = []) {
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
        
        if (!empty($filters['produk_id'])) {
            $where[] = "dt.produk_id = ?";
            $params[] = $filters['produk_id'];
        }
        
        if (!empty($filters['cari_customer'])) {
            $where[] = "(p.nama LIKE ? OR p.nomor_wa LIKE ?)";
            $search = "%{$filters['cari_customer']}%";
            $params[] = $search;
            $params[] = $search;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $page = (int)($filters['page'] ?? 1);
        $limit = (int)($filters['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        // PERUBAHAN: pendapatan_profit langsung ambil dari kolom profit
        $sql = "SELECT 
                    t.id as transaksi_id,
                    p.nama as customer_nama,
                    p.nomor_wa,
                    prod.nama as produk_nama,
                    t.tanggal_transaksi,
                    dt.harga,
                    dt.profit as pendapatan_profit,
                    t.total_harga
                FROM transaksi t
                JOIN pelanggan p ON t.pelanggan_id = p.id
                JOIN detail_transaksi dt ON t.id = dt.transaksi_id
                JOIN produk prod ON dt.produk_id = prod.id
                WHERE {$where_clause}
                ORDER BY t.tanggal_transaksi DESC
                LIMIT ? OFFSET ?";
                
        $params[] = $limit;
        $params[] = $offset;
        
        try {
            $result = query($sql, $params);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            foreach ($data as &$row) {
                $row['pendapatan_profit'] = (float)($row['pendapatan_profit'] ?? 0);
                $row['harga'] = (float)($row['harga'] ?? 0);
                $row['total_harga'] = (float)($row['total_harga'] ?? 0);
            }
            
            // Hitung total untuk pagination
            $count_sql = "SELECT COUNT(*) as total
                          FROM transaksi t
                          JOIN pelanggan p ON t.pelanggan_id = p.id
                          JOIN detail_transaksi dt ON t.id = dt.transaksi_id
                          JOIN produk prod ON dt.produk_id = prod.id
                          WHERE {$where_clause}";
                          
            $count_params = array_slice($params, 0, -2);
            $count_result = query($count_sql, $count_params);
            $total = (int)($count_result->fetch_assoc()['total'] ?? 0);
            
            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
        } catch (Exception $e) {
            error_log("Error getDetailPenjualan: " . $e->getMessage());
            return ['data' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'total_pages' => 0];
        }
    }
    
    /**
     * Export detail penjualan ke CSV
     */
    public function exportDetailCSV($filters = []) {
        unset($filters['page'], $filters['limit']);
        $filters['limit'] = 10000;
        
        $data = $this->getDetailPenjualan($filters);
        
        $csv = "No,Customer,WhatsApp,Produk,Tanggal,Harga,Profit,Total Transaksi\n";
        
        foreach ($data['data'] as $index => $row) {
            $csv .= ($index + 1) . ',';
            $csv .= '"' . str_replace('"', '""', $row['customer_nama']) . '",';
            $csv .= $row['nomor_wa'] . ',';
            $csv .= '"' . str_replace('"', '""', $row['produk_nama']) . '",';
            $csv .= date('d/m/Y H:i', strtotime($row['tanggal_transaksi'])) . ',';
            $csv .= '"' . number_format($row['harga'], 0, ',', '.') . '",';
            $csv .= '"' . number_format($row['pendapatan_profit'], 0, ',', '.') . '",'; // Tambah kolom profit di CSV
            $csv .= '"' . number_format($row['total_harga'], 0, ',', '.') . '"';
            $csv .= "\n";
        }
        
        return $csv;
    }
    
    public function getAllProduk() {
        $sql = "SELECT id, nama FROM produk ORDER BY nama";
        try {
            $result = query($sql);
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getAllProduk: " . $e->getMessage());
            return [];
        }
    }

    public function getWaktuPenyelesaian($bulan, $tahun) {
        $sql = "SELECT 
                    TIMESTAMPDIFF(MINUTE, tanggal_transaksi, waktu_selesai) as durasi_menit,
                    TIMESTAMPDIFF(HOUR, tanggal_transaksi, waktu_selesai) as durasi_jam,
                    TIMESTAMPDIFF(DAY, tanggal_transaksi, waktu_selesai) as durasi_hari,
                    tanggal_transaksi,
                    waktu_selesai
                FROM transaksi 
                WHERE status = 'selesai' 
                AND waktu_selesai IS NOT NULL
                AND MONTH(waktu_selesai) = ? 
                AND YEAR(waktu_selesai) = ?
                ORDER BY durasi_menit ASC";
                
        try {
            $result = query($sql, [$bulan, $tahun]);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            if (empty($data)) {
                return [
                    'rata_rata_menit' => 0, 'rata_rata_jam' => 0, 'rata_rata_hari' => 0,
                    'tercepat_menit' => 0, 'tercepat_jam' => 0, 'tercepat_hari' => 0,
                    'terlama_menit' => 0, 'terlama_jam' => 0, 'terlama_hari' => 0,
                    'total_transaksi' => 0
                ];
            }
            
            $total_menit = array_sum(array_column($data, 'durasi_menit'));
            $total_transaksi = count($data);
            $tercepat = $data[0];
            $terlama = end($data);
            
            return [
                'rata_rata_menit' => round($total_menit / $total_transaksi, 1),
                'rata_rata_jam' => round(($total_menit / $total_transaksi) / 60, 1),
                'rata_rata_hari' => round((($total_menit / $total_transaksi) / 60) / 24, 1),
                'tercepat_menit' => (int)$tercepat['durasi_menit'],
                'tercepat_jam' => round($tercepat['durasi_menit'] / 60, 1),
                'tercepat_hari' => round(($tercepat['durasi_menit'] / 60) / 24, 1),
                'terlama_menit' => (int)$terlama['durasi_menit'],
                'terlama_jam' => round($terlama['durasi_menit'] / 60, 1),
                'terlama_hari' => round(($terlama['durasi_menit'] / 60) / 24, 1),
                'total_transaksi' => $total_transaksi
            ];
        } catch (Exception $e) {
            error_log("Error getWaktuPenyelesaian: " . $e->getMessage());
            return [
                'rata_rata_menit' => 0, 'rata_rata_jam' => 0, 'rata_rata_hari' => 0,
                'total_transaksi' => 0
            ];
        }
    }
	
	/**
     * Ambil analisa transaksi per jam (Traffic & Activity)
     * Filter: Range Tanggal, Hari Spesifik (Senin/Selasa/dll), Produk
     */
    public function getAnalisaPerJam($filters = []) {
        $produk_id = $filters['produk_id'] ?? null;
        $tanggal_awal = $filters['tanggal_awal'] ?? date('Y-m-01'); 
        $tanggal_akhir = $filters['tanggal_akhir'] ?? date('Y-m-d');
        $hari_pekan = $filters['hari_pekan'] ?? null; // 1 (Minggu) - 7 (Sabtu)

        // Base Query
        $sql = "SELECT 
                    HOUR(t.tanggal_transaksi) as jam,
                    COUNT(DISTINCT t.id) as total_transaksi,
                    SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as total_pending,
                    SUM(CASE WHEN t.status = 'selesai' THEN 1 ELSE 0 END) as total_selesai,
                    COALESCE(SUM(dt.harga), 0) as potensi_omzet
                FROM transaksi t
                JOIN detail_transaksi dt ON t.id = dt.transaksi_id
                WHERE t.status IN ('selesai', 'pending', 'diproses')";

        $params = [];

        // 1. Filter Range Tanggal
        if (!empty($tanggal_awal) && !empty($tanggal_akhir)) {
            $sql .= " AND DATE(t.tanggal_transaksi) BETWEEN ? AND ?";
            $params[] = $tanggal_awal;
            $params[] = $tanggal_akhir;
        }

        // 2. Filter Hari Pekan (MySQL DAYOFWEEK: 1=Minggu, 2=Senin, dst)
        if (!empty($hari_pekan)) {
            $sql .= " AND DAYOFWEEK(t.tanggal_transaksi) = ?";
            $params[] = $hari_pekan;
        }

        // 3. Filter Produk
        if (!empty($produk_id)) {
            $sql .= " AND dt.produk_id = ?";
            $params[] = $produk_id;
        }

        $sql .= " GROUP BY HOUR(t.tanggal_transaksi) ORDER BY jam ASC";

        try {
            $result = query($sql, $params);
            $raw_data = $result->fetch_all(MYSQLI_ASSOC);

            // Normalisasi Data (Isi jam 0-23)
            $final_data = [];
            $data_map = [];
            
            foreach ($raw_data as $row) {
                $data_map[(int)$row['jam']] = $row;
            }

            for ($i = 0; $i < 24; $i++) {
                if (isset($data_map[$i])) {
                    $final_data[] = [
                        'jam' => $i,
                        'label' => sprintf('%02d:00', $i),
                        'total_transaksi' => (int)$data_map[$i]['total_transaksi'],
                        'total_pending' => (int)$data_map[$i]['total_pending'],
                        'total_selesai' => (int)$data_map[$i]['total_selesai'],
                        'potensi_omzet' => (float)$data_map[$i]['potensi_omzet']
                    ];
                } else {
                    $final_data[] = [
                        'jam' => $i,
                        'label' => sprintf('%02d:00', $i),
                        'total_transaksi' => 0,
                        'total_pending' => 0,
                        'total_selesai' => 0,
                        'potensi_omzet' => 0
                    ];
                }
            }

            return $final_data;

        } catch (Exception $e) {
            error_log("Error getAnalisaPerJam: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Helper functions
 */

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

function safeHtml($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function formatNumber($number) {
    return number_format($number, 0, ',', '.');
}

function getAllCustomersForChart() {
    try {
        $sql = "SELECT 
                    p.nama as customer_nama,
                    p.nomor_wa,
                    t.tanggal_transaksi,
                    prod.nama as produk_nama,
                    dt.harga,
                    t.total_harga
                FROM pelanggan p
                JOIN transaksi t ON p.id = t.pelanggan_id
                JOIN detail_transaksi dt ON t.id = dt.transaksi_id
                JOIN produk prod ON dt.produk_id = prod.id
                WHERE t.status = 'selesai'
                ORDER BY p.nama, t.tanggal_transaksi DESC";
                
        $result = query($sql);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        
        $grouped_customers = [];
        foreach ($data as $row) {
            $customer_key = $row['customer_nama'] . '_' . $row['nomor_wa'];
            
            if (!isset($grouped_customers[$customer_key])) {
                $grouped_customers[$customer_key] = [
                    'customer_nama' => $row['customer_nama'],
                    'nomor_wa' => $row['nomor_wa'],
                    'transaksi_terakhir' => $row['tanggal_transaksi'],
                    'produk_list' => [],
                    'total_belanja' => 0,
                    'jumlah_transaksi' => 0
                ];
            }
            
            $grouped_customers[$customer_key]['produk_list'][] = [
                'nama' => $row['produk_nama'],
                'harga' => (float)$row['harga'],
                'tanggal' => $row['tanggal_transaksi']
            ];
            $grouped_customers[$customer_key]['total_belanja'] += (float)$row['harga'];
            $grouped_customers[$customer_key]['jumlah_transaksi']++;
            
            if (strtotime($row['tanggal_transaksi']) > strtotime($grouped_customers[$customer_key]['transaksi_terakhir'])) {
                $grouped_customers[$customer_key]['transaksi_terakhir'] = $row['tanggal_transaksi'];
            }
        }
        
        return array_values($grouped_customers);
        
    } catch (Exception $e) {
        error_log("Error getAllCustomersForChart: " . $e->getMessage());
        return [];
    }
}
?>