<?php
require_once __DIR__ . '/../../includes/init.php';

// Konstanta pagination
if (!defined('RECORDS_PER_PAGE')) {
    define('RECORDS_PER_PAGE', 10);
}

/**
 * Mengambil semua transaksi dengan pagination dan filter - OPTIMIZED
 */
function getAllTransaksi($page = 1, $limit = RECORDS_PER_PAGE, $filters = []) {
    $page = max(1, (int)$page);
    $limit = max(1, min(100, (int)$limit)); // Limit maksimal 100 untuk performa
    $offset = ($page - 1) * $limit;
    
    $where_conditions = [];
    $params = [];
    
    // Filter by status
    if (!empty($filters['status'])) {
        $where_conditions[] = "t.status = ?";
        $params[] = $filters['status'];
    }
    
    // Filter by date range
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(t.tanggal_transaksi) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(t.tanggal_transaksi) <= ?";
        $params[] = $filters['date_to'];
    }
    
    // Search by customer name or phone
    if (!empty($filters['search'])) {
        $where_conditions[] = "(p.nama LIKE ? OR p.nomor_wa LIKE ?)";
        $search_term = "%{$filters['search']}%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Query yang dioptimasi - menggunakan subquery untuk count
    $sql = "
        SELECT 
            t.id,
            t.pelanggan_id,
            t.total_harga,
            t.status,
            t.tanggal_transaksi,
			t.waktu_selesai,
            p.nama as nama_pelanggan,
            p.nomor_wa,
            (SELECT COUNT(*) FROM detail_transaksi dt WHERE dt.transaksi_id = t.id) as jumlah_item
        FROM transaksi t
        INNER JOIN pelanggan p ON t.pelanggan_id = p.id
        {$where_clause}
        ORDER BY t.tanggal_transaksi DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $result = fetchAll($sql, $params);
    
    // Jika query gagal, return array kosong
    return $result !== false ? $result : [];
}

/**
 * Mengambil total jumlah transaksi dengan filter - OPTIMIZED
 */
function getTotalTransaksi($filters = []) {
    $where_conditions = [];
    $params = [];
    
    if (!empty($filters['status'])) {
        $where_conditions[] = "t.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(t.tanggal_transaksi) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(t.tanggal_transaksi) <= ?";
        $params[] = $filters['date_to'];
    }
    
    if (!empty($filters['search'])) {
        $where_conditions[] = "(p.nama LIKE ? OR p.nomor_wa LIKE ?)";
        $search_term = "%{$filters['search']}%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "
        SELECT COUNT(t.id) as total 
        FROM transaksi t
        INNER JOIN pelanggan p ON t.pelanggan_id = p.id
        {$where_clause}
    ";
    
    $result = fetchRow($sql, $params);
    return (int)($result['total'] ?? 0);
}

/**
 * Mengambil detail transaksi berdasarkan ID
 */
function getTransaksiById($id) {
    $sql = "
        SELECT 
            t.*,
            p.nama as nama_pelanggan,
            p.nomor_wa,
			p.email
        FROM transaksi t
        INNER JOIN pelanggan p ON t.pelanggan_id = p.id
        WHERE t.id = ?
        LIMIT 1
    ";
    
    return fetchRow($sql, [$id]);
}

/**
 * Mengambil detail item transaksi
 */
function getDetailTransaksi($transaksi_id) {
    $sql = "
        SELECT 
            dt.*,
            p.nama as nama_produk,
            p.deskripsi
        FROM detail_transaksi dt
        INNER JOIN produk p ON dt.produk_id = p.id
        WHERE dt.transaksi_id = ?
        ORDER BY dt.id
    ";
    
    return fetchAll($sql, [$transaksi_id]);
}

/**
 * Create or get pelanggan by nomor_wa - OPTIMIZED
 */
function createOrGetPelanggan($data) {
    // Validasi input
    if (empty($data['nomor_wa']) || empty($data['nama'])) {
        return false;
    }
    
    // Normalisasi nomor WA
    $nomor_wa = preg_replace('/[^0-9]/', '', $data['nomor_wa']);
    if (substr($nomor_wa, 0, 1) === '0') {
        $nomor_wa = '62' . substr($nomor_wa, 1);
    }
    
    // Cek apakah pelanggan sudah ada
    $existing = fetchRow("SELECT id FROM pelanggan WHERE nomor_wa = ? LIMIT 1", [$nomor_wa]);
    
    if ($existing) {
        // Update nama jika berbeda
        execute("UPDATE pelanggan SET nama = ? WHERE id = ?", [$data['nama'], $existing['id']]);
        return $existing['id'];
    }
    
    // Buat pelanggan baru
    if (execute("INSERT INTO pelanggan (nama, nomor_wa) VALUES (?, ?)", [$data['nama'], $nomor_wa])) {
        return db()->insert_id;
    }
    
    return false;
}

/**
 * Update status transaksi - IMPROVED
 */
function updateStatusTransaksi($transaksi_id, $new_status) {
    $valid_statuses = ['pending', 'diproses', 'selesai', 'batal'];
    
    if (!in_array($new_status, $valid_statuses)) {
        return false;
    }
    
    // Cek apakah transaksi exists
    $transaksi = fetchRow("SELECT id, status FROM transaksi WHERE id = ? LIMIT 1", [$transaksi_id]);
    if (!$transaksi) {
        return false;
    }
    
    return execute("UPDATE transaksi SET status = ? WHERE id = ?", [$new_status, $transaksi_id]);
}

/**
 * Hapus transaksi - IMPROVED dengan validasi
 */
function deleteTransaksi($transaksi_id) {
    try {
        // Cek apakah transaksi exists
        $transaksi = fetchRow("SELECT id FROM transaksi WHERE id = ? LIMIT 1", [$transaksi_id]);
        if (!$transaksi) {
            return false;
        }
        
        db()->begin_transaction();
        
        // Delete detail transaksi first (karena foreign key)
        execute("DELETE FROM detail_transaksi WHERE transaksi_id = ?", [$transaksi_id]);
        
        // Delete transaksi
        if (!execute("DELETE FROM transaksi WHERE id = ?", [$transaksi_id])) {
            throw new Exception("Gagal menghapus transaksi");
        }
        
        db()->commit();
        return true;
        
    } catch (Exception $e) {
        db()->rollback();
        error_log("Error deleting transaksi: " . $e->getMessage());
        return false;
    }
}

/**
 * Get transaction statistics - OPTIMIZED dengan single query
 */
function getStatistikTransaksi() {
    $sql = "
        SELECT 
            COUNT(*) as total_transaksi,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as status_pending,
            SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as status_diproses,
            SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as status_selesai,
            SUM(CASE WHEN status = 'batal' THEN 1 ELSE 0 END) as status_batal,
            COALESCE(SUM(CASE WHEN status = 'selesai' THEN total_harga ELSE 0 END), 0) as total_pendapatan,
            SUM(CASE WHEN DATE(tanggal_transaksi) = CURDATE() THEN 1 ELSE 0 END) as transaksi_hari_ini
        FROM transaksi
    ";
    
    $result = fetchRow($sql);
    
    if (!$result) {
        // Return default values jika query gagal
        return [
            'total_transaksi' => 0,
            'status_pending' => 0,
            'status_diproses' => 0,
            'status_selesai' => 0,
            'status_batal' => 0,
            'total_pendapatan' => 0,
            'transaksi_hari_ini' => 0
        ];
    }
    
    return [
        'total_transaksi' => (int)$result['total_transaksi'],
        'status_pending' => (int)$result['status_pending'],
        'status_diproses' => (int)$result['status_diproses'],
        'status_selesai' => (int)$result['status_selesai'],
        'status_batal' => (int)$result['status_batal'],
        'total_pendapatan' => (float)$result['total_pendapatan'],
        'transaksi_hari_ini' => (int)$result['transaksi_hari_ini']
    ];
}

/**
 * Build filter URL for pagination
 */
function buildFilterUrl($filters, $additional_params = []) {
    $params = array_merge($filters, $additional_params);
    $clean_params = array_filter($params, function($value) {
        return $value !== '' && $value !== null;
    });
    return 'index.php' . (!empty($clean_params) ? '?' . http_build_query($clean_params) : '');
}

/**
 * Display session message helper
 */
function displaySessionMessage() {
    $message = getMessage();
    if ($message) {
        $type = $message[1] === 'error' ? 'danger' : $message[1];
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo clean($message[0]);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

/**
 * Safe HTML output
 */
function safeHtml($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Status badge helper - SIMPLIFIED
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'diproses' => 'info', 
        'selesai' => 'success',
        'batal' => 'danger'
    ];
    $class = $badges[strtolower($status)] ?? 'secondary';
    return "btn-outline-$class";
}

/**
 * Membuat transaksi baru - IMPROVED validation
 */
function createTransaksi($data) {
    try {
        // Validasi input
        if (empty($data['nama_pelanggan']) || empty($data['nomor_wa']) || 
            empty($data['total_harga']) || empty($data['items'])) {
            throw new Exception("Data transaksi tidak lengkap");
        }
        
        db()->begin_transaction();
        
        // 1. Create or get customer
        $pelanggan_id = createOrGetPelanggan([
            'nama' => $data['nama_pelanggan'],
            'nomor_wa' => $data['nomor_wa']
        ]);
        
        if (!$pelanggan_id) {
            throw new Exception("Gagal membuat/mengambil data pelanggan");
        }
        
        // 2. Create transaction
        $tanggal = $data['tanggal_transaksi'] ?? date('Y-m-d H:i:s');
        $status = in_array($data['status'] ?? '', ['pending', 'diproses', 'selesai', 'batal']) 
                  ? $data['status'] : 'pending';
		
		// Tentukan waktu_selesai
        $waktu_selesai = ($status === 'selesai') ? $tanggal : null;
        
        if (!execute("INSERT INTO transaksi (pelanggan_id, total_harga, status, tanggal_transaksi, waktu_selesai) VALUES (?, ?, ?, ?, ?)", 
                     [$pelanggan_id, $data['total_harga'], $status, $tanggal, $waktu_selesai])) {
            throw new Exception("Gagal membuat transaksi");
        }
        
        $transaksi_id = db()->insert_id;
        
        // 3. Create transaction details
        foreach ($data['items'] as $item) {
            if (empty($item['produk_id']) || empty($item['harga'])) {
                throw new Exception("Data item transaksi tidak valid");
            }
			
			// --- LOGIKA TAMBAHAN: Ambil data profit dari tabel produk ---
			$produk = fetchRow("SELECT profit FROM produk WHERE id = ?", [$item['produk_id']]);
			$profit_per_item = $produk ? $produk['profit'] : 0;
			// ----------------------------------------------------------
            
            if (!execute("INSERT INTO detail_transaksi (transaksi_id, produk_id, harga, profit) VALUES (?, ?, ?, ?)",
                         [$transaksi_id, $item['produk_id'], $item['harga'], $profit_per_item])) {
                throw new Exception("Gagal membuat detail transaksi");
            }
        }
        
        db()->commit();
        return $transaksi_id;
        
    } catch (Exception $e) {
        db()->rollback();
        error_log("Error creating transaksi: " . $e->getMessage());
        return false;
    }
}

/**
 * Get template pesan akses produk untuk transaksi
 */
function getAccessProductTemplate($transaksi_id) {
    // Ambil produk dari transaksi
    $produk_list = fetchAll("
        SELECT DISTINCT p.id, p.nama, p.link_akses, p.onesender_account
        FROM detail_transaksi dt
        INNER JOIN produk p ON dt.produk_id = p.id
        WHERE dt.transaksi_id = ?
    ", [$transaksi_id]);
    
    if (empty($produk_list)) {
        return null;
    }
    
    // Ambil template untuk produk pertama (atau bisa dimodifikasi untuk multi-produk)
    $first_produk = $produk_list[0];
    
    // Cek apakah ada template khusus untuk produk ini
    $template = fetchRow("
        SELECT isi_pesan 
        FROM template_pesan_produk 
        WHERE produk_id = ? AND jenis_pesan = 'akses_produk'
        LIMIT 1
    ", [$first_produk['id']]);
    
    if ($template) {
        return [
            'template' => $template['isi_pesan'],
            'produk_list' => $produk_list,
            'account' => $first_produk['onesender_account'] ?? 'default'
        ];
    }
    
    // Jika tidak ada template khusus, gunakan template default
    $default_template = fetchRow("
        SELECT isi_pesan 
        FROM pengaturan_pesan 
        WHERE jenis_pesan = 'akses_produk'
        LIMIT 1
    ");
    
    if ($default_template) {
        return [
            'template' => $default_template['isi_pesan'],
            'produk_list' => $produk_list,
            'account' => $first_produk['onesender_account'] ?? 'default'
        ];
    }
    
    // Template fallback jika tidak ada di database
    return [
        'template' => "Halo {nama_pelanggan}!\n\nTerima kasih telah melakukan pembelian. Berikut akses produk Anda:\n\n{list_produk}\n\nSilakan simpan link tersebut dengan baik.\n\nTerima kasih!",
        'produk_list' => $produk_list,
        'account' => $first_produk['onesender_account'] ?? 'default'
    ];
}

/**
 * Replace template variables dengan data transaksi
 */
function replaceTemplateVariables($template, $transaksi_data, $produk_list) {
    // Data pelanggan
    $template = str_replace('{nama_pelanggan}', $transaksi_data['nama_pelanggan'], $template);
    $template = str_replace('{nomor_wa}', $transaksi_data['nomor_wa'], $template);
    
    // Data transaksi
    $template = str_replace('{id_transaksi}', $transaksi_data['id'], $template);
    $template = str_replace('{total_harga}', formatCurrency($transaksi_data['total_harga']), $template);
    $template = str_replace('{tanggal_transaksi}', formatDate($transaksi_data['tanggal_transaksi']), $template);
    
    // List produk dan link akses
    $produk_text = '';
    foreach ($produk_list as $index => $produk) {
        $produk_text .= ($index + 1) . ". " . $produk['nama'];
        if (!empty($produk['link_akses'])) {
            $produk_text .= "\nLink: " . $produk['link_akses'];
        }
        $produk_text .= "\n\n";
    }
    
    $template = str_replace('{list_produk}', trim($produk_text), $template);
    
    return $template;
}

/**
 * Kirim pesan akses produk otomatis
 */
function sendAccessProductMessage($transaksi_id) {
    require_once __DIR__ . '/../../includes/whatsapp_helper.php';
    
    try {
        // Ambil data transaksi
        $transaksi = getTransaksiById($transaksi_id);
        if (!$transaksi) {
            throw new Exception("Transaksi tidak ditemukan");
        }
        
        // Ambil template pesan
        $template_data = getAccessProductTemplate($transaksi_id);
        if (!$template_data) {
            throw new Exception("Template pesan tidak ditemukan");
        }
        
        // Replace variables dalam template
        $message = replaceTemplateVariables(
            $template_data['template'], 
            $transaksi, 
            $template_data['produk_list']
        );
        
        // Kirim pesan via WhatsApp
        $result = sendWhatsAppText(
            $transaksi['nomor_wa'], 
            $message, 
            $template_data['account']
        );
        
        // Log hasil pengiriman
        $log_message = $result['success'] 
            ? "Pesan akses produk berhasil dikirim untuk transaksi #$transaksi_id" 
            : "Gagal mengirim pesan akses produk untuk transaksi #$transaksi_id: " . $result['error'];
        
        error_log($log_message);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error sending access product message: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get all transaksi with followup stats
 * Menambahkan data followup yang sudah terkirim untuk transaksi pending
 */
function getAllTransaksiWithFollowup($page = 1, $limit = 20, $filters = []) {
    $offset = ($page - 1) * $limit;
    
    // Base query with followup stats
    $sql = "
        SELECT 
            t.*,
            p.nama as nama_pelanggan,
            p.nomor_wa,
            COUNT(dt.id) as jumlah_item,
            -- Followup stats (only for pending transactions)
            CASE 
                WHEN t.status = 'pending' THEN COALESCE(fl_stats.followup_total, 0)
                ELSE 0 
            END as followup_total,
            CASE 
                WHEN t.status = 'pending' THEN COALESCE(fl_stats.followup_sent, 0)
                ELSE 0 
            END as followup_sent
        FROM transaksi t
        JOIN pelanggan p ON t.pelanggan_id = p.id
        LEFT JOIN detail_transaksi dt ON t.id = dt.transaksi_id
        -- Followup stats subquery
        LEFT JOIN (
            SELECT 
                fl.transaksi_id,
                COUNT(*) as followup_total,
                SUM(CASE WHEN fl.status = 'terkirim' THEN 1 ELSE 0 END) as followup_sent
            FROM followup_logs fl
            GROUP BY fl.transaksi_id
        ) fl_stats ON t.id = fl_stats.transaksi_id AND t.status = 'pending'
    ";
    
    // Add WHERE clause for filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($filters['status'])) {
        $where_conditions[] = "t.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(t.tanggal_transaksi) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(t.tanggal_transaksi) <= ?";
        $params[] = $filters['date_to'];
    }
    
    if (!empty($filters['search'])) {
        $where_conditions[] = "(p.nama LIKE ? OR p.nomor_wa LIKE ?)";
        $search_term = '%' . $filters['search'] . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql .= " GROUP BY t.id, p.nama, p.nomor_wa ORDER BY t.tanggal_transaksi DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    return fetchAll($sql, $params);
}

/**
 * Get followup summary for a specific transaction
 */
function getTransactionFollowupSummary($transaksi_id) {
    return fetchRow("
        SELECT 
            COUNT(*) as total_followup,
            SUM(CASE WHEN status = 'terkirim' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'gagal' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN status = 'skip' THEN 1 ELSE 0 END) as skipped,
            SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting
        FROM followup_logs 
        WHERE transaksi_id = ?
    ", [$transaksi_id]) ?: [
        'total_followup' => 0,
        'sent' => 0,
        'pending' => 0,
        'failed' => 0,
        'skipped' => 0,
        'waiting' => 0
    ];
}

/**
 * Get followup logs for a transaction
 */
function getTransactionFollowupLogs($transaksi_id) {
    return fetchAll("
        SELECT 
            fl.*,
            fm.nama_pesan,
            fm.tipe_pesan,
            fm.urutan,
            fm.delay_value,
            fm.delay_unit
        FROM followup_logs fl
        JOIN followup_messages fm ON fl.followup_message_id = fm.id
        WHERE fl.transaksi_id = ?
        ORDER BY fm.urutan, fl.created_at
    ", [$transaksi_id]);
}

/**
 * Get pending transactions that need followup scheduling
 */
function getPendingTransactionsForFollowup($limit = 50) {
    return fetchAll("
        SELECT DISTINCT 
            t.id, 
            t.tanggal_transaksi, 
            t.pelanggan_id,
            p.nama as nama_pelanggan, 
            p.nomor_wa,
            COUNT(fl.id) as existing_followups
        FROM transaksi t
        JOIN pelanggan p ON t.pelanggan_id = p.id
        LEFT JOIN followup_logs fl ON t.id = fl.transaksi_id
        WHERE t.status = 'pending' 
        AND t.tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY t.id, t.tanggal_transaksi, t.pelanggan_id, p.nama, p.nomor_wa
        HAVING existing_followups = 0
        ORDER BY t.tanggal_transaksi DESC
        LIMIT ?
    ", [$limit]);
}

/**
 * Get followup statistics overview
 */
function getFollowupStats($days = 30) {
    $stats = fetchRow("
        SELECT 
            COUNT(DISTINCT fl.transaksi_id) as transactions_with_followup,
            COUNT(*) as total_followup_messages,
            SUM(CASE WHEN fl.status = 'terkirim' THEN 1 ELSE 0 END) as messages_sent,
            SUM(CASE WHEN fl.status = 'pending' THEN 1 ELSE 0 END) as messages_pending,
            SUM(CASE WHEN fl.status = 'gagal' THEN 1 ELSE 0 END) as messages_failed,
            SUM(CASE WHEN fl.status = 'skip' THEN 1 ELSE 0 END) as messages_skipped
        FROM followup_logs fl
        WHERE fl.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ", [$days]);
    
    if (!$stats) {
        return [
            'transactions_with_followup' => 0,
            'total_followup_messages' => 0,
            'messages_sent' => 0,
            'messages_pending' => 0,
            'messages_failed' => 0,
            'messages_skipped' => 0
        ];
    }
    
    // Calculate success rate
    $total_processed = $stats['messages_sent'] + $stats['messages_failed'];
    $stats['success_rate'] = $total_processed > 0 ? round(($stats['messages_sent'] / $total_processed) * 100, 1) : 0;
    
    return $stats;
}

/**
 * Cancel all pending followups for a transaction (when status changes from pending)
 */
function cancelTransactionFollowups($transaksi_id, $reason = 'Status transaksi berubah') {
    try {
        return execute("
            UPDATE followup_logs 
            SET status = 'skip', 
                waktu_kirim = NOW(), 
                pesan_final = ? 
            WHERE transaksi_id = ? 
            AND status IN ('pending', 'waiting')
        ", [$reason, $transaksi_id]);
    } catch (Exception $e) {
        error_log("Error canceling followups for transaction $transaksi_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if transaction has any followup messages configured
 */
function hasFollowupMessagesForTransaction($transaksi_id) {
    $result = fetchRow("
        SELECT COUNT(DISTINCT fm.id) as message_count
        FROM transaksi t
        JOIN detail_transaksi dt ON t.id = dt.transaksi_id
        JOIN followup_messages fm ON dt.produk_id = fm.produk_id
        WHERE t.id = ? AND fm.status = 'aktif'
    ", [$transaksi_id]);
    
    return ($result['message_count'] ?? 0) > 0;
}

/**
 * Mengambil semua produk dengan pagination - OPTIMIZED
 */
function getAllProduk($page = 1, $limit = 50) {
    $page = max(1, (int)$page);
    $limit = max(1, min(1000, (int)$limit)); // Limit maksimal 1000 untuk performa
    $offset = ($page - 1) * $limit;
    
    $sql = "
        SELECT 
            id,
            nama,
            deskripsi,
            harga,
            link_akses,
            onesender_account,
            admin_wa,
            meta_pixel_id,
            conversion_api_token,
            tracking_aktif
        FROM produk
        ORDER BY nama ASC
        LIMIT ? OFFSET ?
    ";
    
    $result = fetchAll($sql, [$limit, $offset]);
    
    // Jika query gagal, return array kosong
    return $result !== false ? $result : [];
}

/**
 * Mengambil produk berdasarkan ID
 */
function getProdukById($id) {
    $sql = "
        SELECT 
            id,
            nama,
            deskripsi,
            harga,
            link_akses,
            onesender_account,
            admin_wa,
            meta_pixel_id,
            conversion_api_token,
            tracking_aktif
        FROM produk
        WHERE id = ?
        LIMIT 1
    ";
    
    return fetchRow($sql, [$id]);
}

/**
 * Get followup progress untuk multiple transaksi sekaligus (optimized)
 */
function getMultipleFollowupProgress($transaksi_ids) {
    if (empty($transaksi_ids)) {
        return [];
    }
    
    $ids_placeholder = str_repeat('?,', count($transaksi_ids) - 1) . '?';
    
    $query = "
        SELECT 
            fl.transaksi_id,
            COUNT(fl.id) as total_followup,
            SUM(CASE WHEN fl.status = 'terkirim' THEN 1 ELSE 0 END) as terkirim,
            SUM(CASE WHEN fl.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN fl.status = 'gagal' THEN 1 ELSE 0 END) as gagal
        FROM followup_logs fl
        WHERE fl.transaksi_id IN ($ids_placeholder)
        GROUP BY fl.transaksi_id
    ";
    
    $results = fetchAll($query, $transaksi_ids);
    
    $progress_data = [];
    
    foreach ($results as $result) {
        $total = (int)$result['total_followup'];
        $terkirim = (int)$result['terkirim'];
        $pending = (int)$result['pending'];
        $gagal = (int)$result['gagal'];
        
        $progress_percent = $total > 0 ? round(($terkirim / $total) * 100) : 0;
        
        // Tentukan status overall
        $status = 'active';
        if ($terkirim == $total) {
            $status = 'completed';
        } elseif ($gagal > 0 && $pending == 0) {
            $status = 'failed';
        } elseif ($terkirim == 0) {
            $status = 'waiting';
        }
        
        $progress_data[$result['transaksi_id']] = [
            'total' => $total,
            'terkirim' => $terkirim,
            'pending' => $pending,
            'gagal' => $gagal,
            'progress_percent' => $progress_percent,
            'status' => $status
        ];
    }
    
    // Fill missing transaksi dengan empty progress
    foreach ($transaksi_ids as $id) {
        if (!isset($progress_data[$id])) {
            $progress_data[$id] = [
                'total' => 0,
                'terkirim' => 0,
                'pending' => 0,
                'gagal' => 0,
                'progress_percent' => 0,
                'status' => 'none'
            ];
        }
    }
    
    return $progress_data;
}

/**
 * Render followup progress badge
 */
function renderFollowupProgressBadge($progress) {
    if ($progress['total'] == 0) {
        return '<span class="badge bg-light text-muted">No Followup</span>';
    }
    
    $total = $progress['total'];
    $terkirim = $progress['terkirim'];
    $pending = $progress['pending'];
    $gagal = $progress['gagal'];
    $percent = $progress['progress_percent'];
    $status = $progress['status'];
    
    // Warna badge berdasarkan status
    $badge_class = '';
    $icon = '';
    
    switch($status) {
        case 'completed':
            $badge_class = 'bg-success';
            $icon = 'fas fa-check-circle';
            break;
        case 'failed':
            $badge_class = 'bg-danger';
            $icon = 'fas fa-exclamation-triangle';
            break;
        case 'waiting':
            $badge_class = 'bg-warning text-dark';
            $icon = 'fas fa-clock';
            break;
        case 'active':
            $badge_class = 'bg-info';
            $icon = 'fas fa-paper-plane';
            break;
        default:
            $badge_class = 'bg-secondary';
            $icon = 'fas fa-question-circle';
    }
    
    // Progress bar color
    $bar_class = '';
    switch($status) {
        case 'completed':
            $bar_class = 'bg-success';
            break;
        case 'failed':
            $bar_class = 'bg-danger';
            break;
        case 'waiting':
            $bar_class = 'bg-warning';
            break;
        case 'active':
            $bar_class = 'bg-info';
            break;
        default:
            $bar_class = 'bg-secondary';
    }
    
    $output = '<div class="followup-progress text-center">';
    
    // Progress badge
    $output .= "<span class='badge {$badge_class} mb-1'>";
    $output .= "<i class='{$icon} me-1'></i>";
    $output .= "{$terkirim}/{$total}";
    $output .= "</span>";
    
    // Progress bar mini
    if ($total > 0) {
        $output .= "<div class='progress mt-1' style='height: 4px;'>";
        $output .= "<div class='progress-bar {$bar_class}' style='width: {$percent}%'></div>";
        $output .= "</div>";
        
        // Detail info on hover
        $tooltip = "Terkirim: {$terkirim}";
        if ($pending > 0) $tooltip .= " | Pending: {$pending}";
        if ($gagal > 0) $tooltip .= " | Gagal: {$gagal}";
        
        $output .= "<small class='text-muted d-block' title='{$tooltip}'>{$percent}%</small>";
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Manual delete followup logs untuk transaksi tertentu
 * Digunakan khusus saat admin manual update status
 */
function manualDeleteTransactionFollowups($transaksi_id, $admin_reason = 'Manual status change by admin') {
    try {
        // Step 1: Hitung berapa yang akan dihapus untuk audit log
        $count_result = fetchRow("
            SELECT 
                COUNT(*) as total_to_delete,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting_count
            FROM followup_logs 
            WHERE transaksi_id = ? 
            AND status IN ('pending', 'waiting')
        ", [$transaksi_id]);
        
        $total_to_delete = (int)($count_result['total_to_delete'] ?? 0);
        $pending_count = (int)($count_result['pending_count'] ?? 0);
        $waiting_count = (int)($count_result['waiting_count'] ?? 0);
        
        if ($total_to_delete === 0) {
            return [
                'success' => true,
                'deleted_count' => 0,
                'message' => 'No followup messages to delete'
            ];
        }
        
        // Step 2: DELETE followup logs
        $delete_result = execute("
            DELETE FROM followup_logs 
            WHERE transaksi_id = ? 
            AND status IN ('pending', 'waiting')
        ", [$transaksi_id]);
        
        if ($delete_result) {
            // Step 3: Audit logging
            error_log("✂️ MANUAL DELETE by Admin: Deleted $total_to_delete followup logs for transaction #$transaksi_id");
            error_log("   - Breakdown: $pending_count pending, $waiting_count waiting");
            error_log("   - Reason: $admin_reason");
            
            return [
                'success' => true,
                'deleted_count' => $total_to_delete,
                'pending_deleted' => $pending_count,
                'waiting_deleted' => $waiting_count,
                'message' => "Successfully deleted $total_to_delete followup messages"
            ];
        } else {
            return [
                'success' => false,
                'deleted_count' => 0,
                'message' => 'Failed to delete followup messages'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error in manualDeleteTransactionFollowups: " . $e->getMessage());
        return [
            'success' => false,
            'deleted_count' => 0,
            'message' => 'Exception: ' . $e->getMessage()
        ];
    }
}

/**
 * Get followup deletion preview (untuk konfirmasi sebelum delete)
 */
function getFollowupDeletionPreview($transaksi_id) {
    $preview = fetchRow("
        SELECT 
            COUNT(*) as total_followups,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting_count,
            SUM(CASE WHEN status = 'terkirim' THEN 1 ELSE 0 END) as sent_count,
            SUM(CASE WHEN status = 'gagal' THEN 1 ELSE 0 END) as failed_count
        FROM followup_logs 
        WHERE transaksi_id = ?
    ", [$transaksi_id]);
    
    if (!$preview) {
        return [
            'total_followups' => 0,
            'will_be_deleted' => 0,
            'will_be_kept' => 0,
            'pending_count' => 0,
            'waiting_count' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
            'has_followups' => false
        ];
    }
    
    $will_be_deleted = $preview['pending_count'] + $preview['waiting_count'];
    $will_be_kept = $preview['sent_count'] + $preview['failed_count'];
    
    return [
        'total_followups' => (int)$preview['total_followups'],
        'will_be_deleted' => $will_be_deleted,
        'will_be_kept' => $will_be_kept,
        'pending_count' => (int)$preview['pending_count'],
        'waiting_count' => (int)$preview['waiting_count'],
        'sent_count' => (int)$preview['sent_count'],
        'failed_count' => (int)$preview['failed_count'],
        'has_followups' => $preview['total_followups'] > 0
    ];
}

/**
 * Manual update transaction status dengan followup deletion
 * Wrapper function untuk kemudahan penggunaan
 */
function manualUpdateTransactionStatus($transaksi_id, $new_status, $admin_note = '') {
    $valid_statuses = ['pending', 'diproses', 'selesai', 'batal'];
    
    if (!in_array($new_status, $valid_statuses)) {
        return [
            'success' => false,
            'message' => 'Invalid status'
        ];
    }
    
    // Get current status
    $current = fetchRow("SELECT status FROM transaksi WHERE id = ?", [$transaksi_id]);
    
    if (!$current) {
        return [
            'success' => false,
            'message' => 'Transaction not found'
        ];
    }
    
    $old_status = $current['status'];
    
    if ($old_status === $new_status) {
        return [
            'success' => true,
            'message' => 'Status already set to ' . $new_status
        ];
    }
    
    try {
        db()->begin_transaction();
        
        // Step 1: Update transaction status
        $waktu_selesai_sql = $new_status === 'selesai' ? 'NOW()' : 'NULL';
        $update_result = execute("
            UPDATE transaksi 
            SET status = ?, waktu_selesai = " . ($new_status === 'selesai' ? 'NOW()' : 'NULL') . " 
            WHERE id = ?
        ", [$new_status, $transaksi_id]);
        
        if (!$update_result) {
            throw new Exception('Failed to update transaction status');
        }
        
        $followup_result = ['deleted_count' => 0];
        
        // Step 2: Delete followups jika status berubah dari pending
        if ($old_status === 'pending' && $new_status !== 'pending') {
            $reason = "Status changed from '$old_status' to '$new_status'";
            if (!empty($admin_note)) {
                $reason .= " - Note: $admin_note";
            }
            
            // Hapus followup logs yang pending atau waiting
            $delete_result = execute("
                DELETE FROM followup_logs 
                WHERE transaksi_id = ? 
                AND status IN ('pending', 'waiting')
            ", [$transaksi_id]);
            
            if ($delete_result !== false) {
                $deleted_count = db()->affected_rows;
                $followup_result = [
                    'deleted_count' => $deleted_count,
                    'success' => true
                ];
            } else {
                throw new Exception('Failed to delete followup messages');
            }
        }
        
        db()->commit();
        
        return [
            'success' => true,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'followups_deleted' => $followup_result['deleted_count'],
            'message' => "Status updated successfully"
        ];
        
    } catch (Exception $e) {
        db()->rollback();
        error_log("Error in manualUpdateTransactionStatus: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get transaction status change impact (untuk preview sebelum update)
 */
function getStatusChangeImpact($transaksi_id, $new_status) {
    $current = fetchRow("SELECT status FROM transaksi WHERE id = ?", [$transaksi_id]);
    
    if (!$current) {
        return null;
    }
    
    $old_status = $current['status'];
    
    // Get followup preview jika akan berubah dari pending
    $followup_impact = null;
    if ($old_status === 'pending' && $new_status !== 'pending') {
        $followup_impact = getFollowupDeletionPreview($transaksi_id);
    }
    
    return [
        'old_status' => $old_status,
        'new_status' => $new_status,
        'will_change' => $old_status !== $new_status,
        'followup_impact' => $followup_impact,
        'will_delete_followups' => $followup_impact && $followup_impact['will_be_deleted'] > 0,
        'actions' => [
            'update_status' => true,
            'set_completion_time' => $new_status === 'selesai',
            'send_access_message' => $new_status === 'selesai',
            'delete_followups' => $old_status === 'pending' && $new_status !== 'pending'
        ]
    ];
}
?>