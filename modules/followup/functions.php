<?php
/**
 * Followup Management Functions
 * Simple, fast, minimal bugs
 */

// Get all followup messages for a product
function getFollowupMessages($produk_id, $order = 'urutan ASC') {
    return fetchAll("SELECT * FROM followup_messages WHERE produk_id = ? ORDER BY $order", [$produk_id]);
}

// Get single followup message
function getFollowupMessage($id) {
    return fetchRow("SELECT * FROM followup_messages WHERE id = ?", [$id]);
}

// Create followup message
function createFollowupMessage($data) {
    $sql = "INSERT INTO followup_messages (produk_id, urutan, nama_pesan, delay_value, delay_unit, tipe_pesan, isi_pesan, link_gambar, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    return execute($sql, [
        $data['produk_id'],
        $data['urutan'],
        $data['nama_pesan'],
        $data['delay_value'],
        $data['delay_unit'],
        $data['tipe_pesan'],
        $data['isi_pesan'],
        $data['link_gambar'] ?? '',
        $data['status']
    ]);
}

// Update followup message
function updateFollowupMessage($id, $data) {
    $sql = "UPDATE followup_messages SET 
            urutan = ?, nama_pesan = ?, delay_value = ?, delay_unit = ?, 
            tipe_pesan = ?, isi_pesan = ?, link_gambar = ?, status = ?, 
            updated_at = NOW() WHERE id = ?";
    return execute($sql, [
        $data['urutan'],
        $data['nama_pesan'],
        $data['delay_value'],
        $data['delay_unit'],
        $data['tipe_pesan'],
        $data['isi_pesan'],
        $data['link_gambar'] ?? '',
        $data['status'],
        $id
    ]);
}

// Delete followup message
function deleteFollowupMessage($id) {
    return execute("DELETE FROM followup_messages WHERE id = ?", [$id]);
}

// Get next urutan for product
function getNextUrutan($produk_id) {
    $result = fetchRow("SELECT MAX(urutan) as max_urutan FROM followup_messages WHERE produk_id = ?", [$produk_id]);
    return ($result['max_urutan'] ?? 0) + 1;
}

// Replace placeholders in message
function replacePlaceholders($message, $placeholders = []) {
    $defaultPlaceholders = [
        '[nama]' => $placeholders['nama'] ?? 'John Doe',
        '[produk]' => $placeholders['produk'] ?? 'Contoh Produk',
        '[harga]' => $placeholders['harga'] ?? 'Rp 100.000'
    ];
    
    return str_replace(array_keys($defaultPlaceholders), array_values($defaultPlaceholders), $message);
}

// Get formatted product list from transaction
function getFormattedProductList($transaksi_id) {
    // Get all products in transaction
    $sql = "SELECT p.nama 
            FROM detail_transaksi dt 
            JOIN produk p ON dt.produk_id = p.id 
            WHERE dt.transaksi_id = ?
            ORDER BY p.nama ASC";
    
    $products = fetchAll($sql, [$transaksi_id]);
    
    if (!$products) {
        return 'Produk tidak ditemukan';
    }
    
    $productNames = array_column($products, 'nama');
    
    // Format sesuai jumlah produk
    $count = count($productNames);
    
    if ($count == 1) {
        return $productNames[0];
    } elseif ($count == 2) {
        return $productNames[0] . ' & ' . $productNames[1];
    } else {
        // Untuk 3+ produk: Produk A, Produk B, & Produk C
        $lastProduct = array_pop($productNames);
        return implode(', ', $productNames) . ', & ' . $lastProduct;
    }
}

// Get bundling total price for product preview
function getBundlingTotalPrice($produk_id) {
    // Get main product price
    $main_product = fetchRow("SELECT harga FROM produk WHERE id = ?", [$produk_id]);
    if (!$main_product) return 0;
    
    $total_price = $main_product['harga'];
    
    // Get bundling products and their prices
    $bundling_products = fetchAll("
        SELECT p.harga, b.diskon 
        FROM bundling b 
        JOIN produk p ON b.produk_bundling_id = p.id 
        WHERE b.produk_id = ?
    ", [$produk_id]);
    
    // Add bundling prices with discount calculation
    foreach ($bundling_products as $bp) {
        $bundling_price = $bp['harga'];
        
        // Apply discount if any
        if ($bp['diskon'] > 0) {
            $bundling_price = $bundling_price - $bp['diskon'];
        }
        
        $total_price += $bundling_price;
    }
    
    return $total_price;
}

// Generate followup logs for existing transactions when new message added (ENHANCED)
function generateFollowupForExistingTransactions($followup_message_id) {
    return generateForExistingTransactionsEnhanced($followup_message_id);
}

/**
 * Generate followup queue untuk transaksi baru
 */
/**
 * Generate followup queue untuk transaksi baru (SMART VERSION)
 * Menghindari spam dengan memilih produk utama untuk followup
 */
function generateFollowupForNewTransactionSmart($transaksi_id) {
    // Validasi transaksi
    $transaksi = fetchRow("
        SELECT * FROM transaksi 
        WHERE id = ? 
        AND status = 'pending' 
        AND tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    ", [$transaksi_id]);
    
    if (!$transaksi) {
        return false;
    }
    
    // Ambil semua produk yang dibeli
    $products = fetchAll("
        SELECT dt.produk_id, dt.harga, p.nama
        FROM detail_transaksi dt
        JOIN produk p ON dt.produk_id = p.id
        WHERE dt.transaksi_id = ?
        ORDER BY dt.harga DESC
    ", [$transaksi_id]);
    
    if (empty($products)) {
        return false;
    }
    
    // SMART LOGIC: Pilih produk mana yang akan mengirim followup
    $selected_product_id = determineFollowupProduct($transaksi_id, $products);
    
    if (!$selected_product_id) {
        return false; // Tidak ada produk yang layak untuk followup
    }
    
    // Generate followup hanya untuk produk terpilih
    $followups = fetchAll("
        SELECT * FROM followup_messages 
        WHERE produk_id = ? 
        AND status = 'aktif' 
        ORDER BY urutan ASC
    ", [$selected_product_id]);
    
    $generated_count = 0;
    $previous_schedule = $transaksi['tanggal_transaksi'];
    
    foreach ($followups as $followup) {
        $jadwal_kirim = calculateSequentialSendTime(
            $previous_schedule, 
            $followup['delay_value'], 
            $followup['delay_unit']
        );
        
        // Insert ke followup_logs
        $inserted = execute("
            INSERT INTO followup_logs 
            (transaksi_id, followup_message_id, pelanggan_id, jadwal_kirim, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ", [
            $transaksi_id, 
            $followup['id'], 
            $transaksi['pelanggan_id'], 
            $jadwal_kirim
        ]);
        
        if ($inserted) {
            $generated_count++;
            $previous_schedule = $jadwal_kirim;
        }
    }
    
    // Log aktivitas untuk tracking
    error_log("Smart Followup: Generated $generated_count messages for transaction #$transaksi_id using product #$selected_product_id");
    
    return $generated_count > 0;
}

/**
 * Tentukan produk mana yang akan mengirim followup
 */
function determineFollowupProduct($transaksi_id, $products) {
    // Strategi 1: Cek apakah ada bundling
    $bundling_info = getBundlingInfo($transaksi_id);
    
    if ($bundling_info['has_bundling']) {
        // Jika ada bundling, pilih produk utama (bukan produk bundling)
        $main_product = $bundling_info['main_product'];
        
        // Cek apakah produk utama punya followup messages
        $has_followup = fetchRow("
            SELECT COUNT(*) as count 
            FROM followup_messages 
            WHERE produk_id = ? AND status = 'aktif'
        ", [$main_product['produk_id']])['count'] > 0;
        
        if ($has_followup) {
            return $main_product['produk_id'];
        }
    }
    
    // Strategi 2: Pilih produk dengan harga tertinggi yang punya followup
    foreach ($products as $product) {
        $has_followup = fetchRow("
            SELECT COUNT(*) as count 
            FROM followup_messages 
            WHERE produk_id = ? AND status = 'aktif'
        ", [$product['produk_id']])['count'] > 0;
        
        if ($has_followup) {
            return $product['produk_id'];
        }
    }
    
    // Tidak ada produk yang punya followup
    return false;
}

/**
 * Get bundling information untuk transaksi
 */
function getBundlingInfo($transaksi_id) {
    // Cek apakah ada bundling dalam transaksi ini
    $bundling = fetchAll("
        SELECT 
            b.produk_id as main_product_id,
            b.produk_bundling_id as bundling_product_id,
            b.diskon,
            p1.nama as main_product_name,
            p2.nama as bundling_product_name,
            dt1.harga as main_price,
            dt2.harga as bundling_price
        FROM detail_transaksi dt1
        JOIN detail_transaksi dt2 ON dt1.transaksi_id = dt2.transaksi_id
        JOIN bundling b ON dt1.produk_id = b.produk_id AND dt2.produk_id = b.produk_bundling_id
        JOIN produk p1 ON b.produk_id = p1.id
        JOIN produk p2 ON b.produk_bundling_id = p2.id
        WHERE dt1.transaksi_id = ?
    ", [$transaksi_id]);
    
    if (empty($bundling)) {
        return [
            'has_bundling' => false,
            'main_product' => null,
            'bundling_products' => []
        ];
    }
    
    // Ambil produk utama (yang paling sering muncul sebagai main_product)
    $main_products = array_column($bundling, 'main_product_id');
    $main_product_id = array_count_values($main_products);
    arsort($main_product_id);
    $primary_product_id = array_key_first($main_product_id);
    
    // Get main product info
    $main_product = fetchRow("
        SELECT dt.produk_id, dt.harga, p.nama
        FROM detail_transaksi dt
        JOIN produk p ON dt.produk_id = p.id
        WHERE dt.transaksi_id = ? AND dt.produk_id = ?
    ", [$transaksi_id, $primary_product_id]);
    
    return [
        'has_bundling' => true,
        'main_product' => $main_product,
        'bundling_products' => $bundling,
        'bundling_count' => count($bundling)
    ];
}

/**
 * Get followup strategy explanation untuk debugging
 */
function explainFollowupStrategy($transaksi_id) {
    $products = fetchAll("
        SELECT dt.produk_id, dt.harga, p.nama,
               (SELECT COUNT(*) FROM followup_messages fm WHERE fm.produk_id = dt.produk_id AND fm.status = 'aktif') as followup_count
        FROM detail_transaksi dt
        JOIN produk p ON dt.produk_id = p.id
        WHERE dt.transaksi_id = ?
        ORDER BY dt.harga DESC
    ", [$transaksi_id]);
    
    $bundling_info = getBundlingInfo($transaksi_id);
    $selected_product = determineFollowupProduct($transaksi_id, $products);
    
    $explanation = [
        'transaksi_id' => $transaksi_id,
        'total_products' => count($products),
        'products' => $products,
        'has_bundling' => $bundling_info['has_bundling'],
        'bundling_info' => $bundling_info,
        'selected_product_id' => $selected_product,
        'strategy' => $bundling_info['has_bundling'] ? 'bundling_aware' : 'highest_price_with_followup',
        'total_followup_messages' => $selected_product ? 
            fetchRow("SELECT COUNT(*) as count FROM followup_messages WHERE produk_id = ? AND status = 'aktif'", [$selected_product])['count'] : 0
    ];
    
    return $explanation;
}

/**
 * Update existing function name untuk backward compatibility
 */
function generateFollowupForNewTransaction($transaksi_id) {
    return generateFollowupForNewTransactionSmart($transaksi_id);
}

/**
 * Hitung waktu pengiriman followup
 */
function calculateFollowupSendTime($transaksi_time, $urutan, $delay_value, $delay_unit) {
    // Untuk semua urutan, hitung dari waktu transaksi + delay
    // Scheduler nanti yang akan handle sequence berdasarkan urutan
    
    switch ($delay_unit) {
        case 'menit':
            $interval = "INTERVAL $delay_value MINUTE";
            break;
        case 'jam':
            $interval = "INTERVAL $delay_value HOUR";
            break;
        case 'hari':
            $interval = "INTERVAL $delay_value DAY";
            break;
        case 'minggu':
            $interval = "INTERVAL " . ($delay_value * 7) . " DAY";
            break;
        default:
            $interval = "INTERVAL $delay_value DAY";
    }
    
    // Hitung jadwal kirim dari waktu transaksi
    $result = fetchRow("SELECT DATE_ADD(?, $interval) as jadwal", [$transaksi_time]);
    return $result['jadwal'];
}

/**
 * Enhanced: Generate untuk existing transactions (yang sudah ada tapi di-enhance)
 */
function generateForExistingTransactionsEnhanced($followup_message_id) {
    // Get followup message data
    $message = fetchRow("SELECT * FROM followup_messages WHERE id = ?", [$followup_message_id]);
    if (!$message) return 0;
    
    // Find existing transactions untuk produk ini yang masih pending
    $existing_transactions = fetchAll("
        SELECT DISTINCT t.id, t.pelanggan_id, t.tanggal_transaksi 
        FROM transaksi t
        JOIN detail_transaksi dt ON t.id = dt.transaksi_id  
        WHERE dt.produk_id = ? 
        AND t.status = 'pending'
        AND t.tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
		AND t.status = 'pending'
        AND t.id NOT IN (
			SELECT DISTINCT fl.transaksi_id 
			FROM followup_logs fl
			JOIN followup_messages fm ON fl.followup_message_id = fm.id
			WHERE 
				fl.transaksi_id = t.id 
				AND fm.urutan = ?  -- ✅ ganti jadi: cek per URUTAN, bukan per message_id
		)
    ", [$message['produk_id'], $message['urutan']]);
    
    $count = 0;
    foreach ($existing_transactions as $transaksi) {
        // Calculate send time based on transaction date + message delay
        $jadwal_kirim = calculateFollowupSendTime(
            $transaksi['tanggal_transaksi'], 
            $message['urutan'],
            $message['delay_value'], 
            $message['delay_unit']
        );
        
        // Insert ke followup_logs
        $inserted = execute("
            INSERT INTO followup_logs (transaksi_id, followup_message_id, pelanggan_id, jadwal_kirim, status) 
            VALUES (?, ?, ?, ?, 'pending')
        ", [$transaksi['id'], $followup_message_id, $transaksi['pelanggan_id'], $jadwal_kirim]);
        
        if ($inserted) {
            $count++;
        }
    }
    
    return $count;
}

// Replace placeholders with transaction data
function replaceTransactionPlaceholders($message, $transaksi_id, $pelanggan_data = null) {
    // TAMBAHKAN: Validasi status transaksi
    $transaksi = fetchRow("
        SELECT * FROM transaksi 
        WHERE id = ? 
        AND status = 'pending'
    ", [$transaksi_id]);
    
    if (!$transaksi) {
        // Transaksi tidak ditemukan atau sudah tidak pending
        return false; // Return false instead of original message
    }
    
    // Get customer data if not provided
    if (!$pelanggan_data) {
        $pelanggan_data = fetchRow("SELECT * FROM pelanggan WHERE id = ?", [$transaksi['pelanggan_id']]);
    }
    
    // Format placeholders
    $placeholders = [
        '[nama]' => $pelanggan_data['nama'] ?? 'Customer',
        '[produk]' => getFormattedProductList($transaksi_id),
        '[harga]' => formatCurrency($transaksi['total_harga'])
    ];
    
    return str_replace(array_keys($placeholders), array_values($placeholders), $message);
}

// Send followup message via WhatsApp (moved from scheduler.php)
function sendFollowupMessage($message_data, $final_message) {
    require_once __DIR__ . '/../../includes/whatsapp_helper.php';
    
    $phone = $message_data['nomor_wa'];
    $account = $message_data['onesender_account'] ?: 'default';
    
    try {
        if ($message_data['tipe_pesan'] === 'pesan_gambar' && !empty($message_data['link_gambar'])) {
            // Send image with caption
            $result = sendWhatsAppImage($phone, $message_data['link_gambar'], $final_message, $account);
        } else {
            // Send text only
            $result = sendWhatsAppText($phone, $final_message, $account);
        }
        
        return $result;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// Get delay units array
function getDelayUnits() {
    return [
        'menit' => 'Menit',
        'jam' => 'Jam', 
        'hari' => 'Hari',
        'minggu' => 'Minggu'
    ];
}

// Format delay text
function formatDelay($value, $unit) {
    $units = getDelayUnits();
    return $value . " " . ($units[$unit] ?? $unit);
}

// Get all products for dropdown
function getAllProducts() {
    return fetchAll("SELECT id, nama FROM produk ORDER BY nama ASC");
}

// Validate followup data
function validateFollowupData($data) {
    $errors = [];
    
    if (empty($data['nama_pesan'])) {
        $errors[] = "Nama pesan harus diisi";
    }
    
    if (empty($data['isi_pesan'])) {
        $errors[] = "Isi pesan harus diisi";
    }
    
    if (!is_numeric($data['delay_value']) || $data['delay_value'] < 1) {
        $errors[] = "Delay harus berupa angka positif";
    }
    
    if (!in_array($data['delay_unit'], array_keys(getDelayUnits()))) {
        $errors[] = "Unit delay tidak valid";
    }
    
    if (!in_array($data['tipe_pesan'], ['pesan', 'pesan_gambar'])) {
        $errors[] = "Tipe pesan tidak valid";
    }
    
    if ($data['tipe_pesan'] === 'pesan_gambar' && empty($data['link_gambar'])) {
        $errors[] = "Link gambar harus diisi untuk tipe pesan gambar";
    }
    
    return $errors;
}

/**
 * Hitung waktu pengiriman sequential (berdasarkan pesan sebelumnya)
 */
function calculateSequentialSendTime($previous_time, $delay_value, $delay_unit) {
    switch ($delay_unit) {
        case 'menit':
            $interval = "INTERVAL $delay_value MINUTE";
            break;
        case 'jam':
            $interval = "INTERVAL $delay_value HOUR";
            break;
        case 'hari':
            $interval = "INTERVAL $delay_value DAY";
            break;
        case 'minggu':
            $interval = "INTERVAL " . ($delay_value * 7) . " DAY";
            break;
        default:
            $interval = "INTERVAL $delay_value DAY";
    }
    
    // Hitung jadwal kirim dari waktu sebelumnya + delay
    $result = fetchRow("SELECT DATE_ADD(?, $interval) as jadwal", [$previous_time]);
    return $result['jadwal'];
}


/**
 * Generate followup dengan TRUE sequential logic
 * Hanya jadwalkan pesan pertama, sisanya PENDING
 */
function generateFollowupForNewTransactionTrueSequential($transaksi_id) {
    // Validasi transaksi
    $transaksi = fetchRow("
        SELECT * FROM transaksi 
        WHERE id = ? 
        AND status = 'pending' 
        AND tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    ", [$transaksi_id]);
    
    if (!$transaksi) {
        return false;
    }
    
    // Gunakan smart logic untuk pilih produk
    $products = fetchAll("
        SELECT dt.produk_id, dt.harga, p.nama
        FROM detail_transaksi dt
        JOIN produk p ON dt.produk_id = p.id
        WHERE dt.transaksi_id = ?
        ORDER BY dt.harga DESC
    ", [$transaksi_id]);
    
    if (empty($products)) {
        return false;
    }
    
    // Pilih produk yang akan mengirim followup
    $selected_product_id = determineFollowupProduct($transaksi_id, $products);
    
    if (!$selected_product_id) {
        return false;
    }
    
    // Ambil semua followup messages untuk produk terpilih
    $followups = fetchAll("
        SELECT * FROM followup_messages 
        WHERE produk_id = ? 
        AND status = 'aktif' 
        ORDER BY urutan ASC
    ", [$selected_product_id]);
    
    if (empty($followups)) {
        return false;
    }
    
    $generated_count = 0;
    
    foreach ($followups as $followup) {
        if ($followup['urutan'] == 1) {
            // PESAN PERTAMA: Jadwalkan dari waktu transaksi
            $jadwal_kirim = calculateSequentialSendTime(
                $transaksi['tanggal_transaksi'], 
                $followup['delay_value'], 
                $followup['delay_unit']
            );
            $status = 'pending';
        } else {
            // PESAN KEDUA+: Masih WAITING, belum dijadwalkan
            $jadwal_kirim = null;
            $status = 'waiting';
        }
        
        // Insert ke followup_logs
        $inserted = execute("
            INSERT INTO followup_logs 
            (transaksi_id, followup_message_id, pelanggan_id, jadwal_kirim, status, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ", [
            $transaksi_id, 
            $followup['id'], 
            $transaksi['pelanggan_id'], 
            $jadwal_kirim,
            $status
        ]);
        
        if ($inserted) {
            $generated_count++;
        }
    }
    
    error_log("True Sequential: Generated $generated_count followup logs for transaction #$transaksi_id (only first message scheduled)");
    
    return $generated_count > 0;
}

/**
 * Setelah pesan terkirim, jadwalkan pesan berikutnya
 * Dipanggil dari scheduler setelah pesan berhasil dikirim
 */
function scheduleNextSequentialMessage($transaksi_id, $current_urutan) {
    // Cari pesan berikutnya yang masih WAITING
    $next_message = fetchRow("
        SELECT fl.id, fl.followup_message_id, fm.delay_value, fm.delay_unit, fm.urutan
        FROM followup_logs fl
        JOIN followup_messages fm ON fl.followup_message_id = fm.id
        WHERE fl.transaksi_id = ? 
        AND fm.urutan = ?
        AND fl.status = 'waiting'
        LIMIT 1
    ", [$transaksi_id, $current_urutan + 1]);
    
    if (!$next_message) {
        return false; // Tidak ada pesan berikutnya
    }
    
    // Hitung jadwal kirim dari SEKARANG + delay
    $jadwal_kirim = calculateSequentialSendTime(
        date('Y-m-d H:i:s'), // Sekarang (pesan sebelumnya baru saja terkirim)
        $next_message['delay_value'], 
        $next_message['delay_unit']
    );
    
    // Update status dari WAITING → PENDING dengan jadwal baru
    $updated = execute("
        UPDATE followup_logs 
        SET status = 'pending', jadwal_kirim = ? 
        WHERE id = ?
    ", [$jadwal_kirim, $next_message['id']]);
    
    if ($updated) {
        error_log("Sequential: Scheduled next message (urutan {$next_message['urutan']}) for transaction #$transaksi_id at $jadwal_kirim");
        return true;
    }
    
    return false;
}

/**
 * Update scheduler untuk menggunakan true sequential logic
 * GANTI bagian success handling di cron/followup_scheduler.php
 */
function processSuccessfulMessageSequential($message, $final_message) {
    // Update status pesan current menjadi terkirim
    $update_result = execute("
        UPDATE followup_logs 
        SET status = 'terkirim', waktu_kirim = NOW(), pesan_final = ? 
        WHERE id = ?
    ", [
        substr($final_message, 0, 500), 
        $message['log_id']
    ]);
    
    if ($update_result) {
        // ===== TRUE SEQUENTIAL LOGIC =====
        $next_scheduled = scheduleNextSequentialMessage(
            $message['transaksi_id'], 
            $message['urutan']
        );
        
        if ($next_scheduled) {
            error_log("🔄 Next sequential message scheduled for transaction {$message['transaksi_id']}");
        } else {
            error_log("🏁 All sequential messages completed for transaction {$message['transaksi_id']}");
        }
        // ===== END SEQUENTIAL LOGIC =====
        
        return true;
    }
    
    return false;
}

/**
 * Generate untuk existing transactions dengan TRUE sequential
 * Hanya buat pesan pertama, sisanya WAITING
 */
function generateForExistingTransactionsTrueSequential($followup_message_id) {
    $message = fetchRow("SELECT * FROM followup_messages WHERE id = ?", [$followup_message_id]);
    if (!$message) return 0;
    
    // Find existing transactions
    $existing_transactions = fetchAll("
        SELECT DISTINCT t.id, t.pelanggan_id, t.tanggal_transaksi 
        FROM transaksi t
        JOIN detail_transaksi dt ON t.id = dt.transaksi_id  
        WHERE dt.produk_id = ? 
        AND t.status = 'pending'
        AND t.tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        AND t.id NOT IN (
            SELECT DISTINCT transaksi_id 
            FROM followup_logs 
            WHERE followup_message_id = ?
        )
    ", [$message['produk_id'], $followup_message_id]);
    
    $count = 0;
    foreach ($existing_transactions as $transaksi) {
        if ($message['urutan'] == 1) {
            // Pesan pertama: Jadwalkan dari waktu transaksi + delay
            $jadwal_kirim = calculateSequentialSendTime(
                $transaksi['tanggal_transaksi'], 
                $message['delay_value'], 
                $message['delay_unit']
            );
            $status = 'pending';
        } else {
            // Pesan kedua+: Check apakah pesan sebelumnya sudah terkirim
            $previous_sent = fetchRow("
                SELECT fl.waktu_kirim 
                FROM followup_logs fl
                JOIN followup_messages fm ON fl.followup_message_id = fm.id
                WHERE fl.transaksi_id = ? 
                AND fm.urutan = ?
                AND fl.status = 'terkirim'
                ORDER BY fl.waktu_kirim DESC
                LIMIT 1
            ", [$transaksi['id'], $message['urutan'] - 1]);
            
            if ($previous_sent && $previous_sent['waktu_kirim']) {
                // Pesan sebelumnya sudah terkirim, jadwalkan dari waktu tersebut
                $jadwal_kirim = calculateSequentialSendTime(
                    $previous_sent['waktu_kirim'], 
                    $message['delay_value'], 
                    $message['delay_unit']
                );
                $status = 'pending';
            } else {
                // Pesan sebelumnya belum terkirim, set WAITING
                $jadwal_kirim = null;
                $status = 'waiting';
            }
        }
        
        // Insert followup log
        $inserted = execute("
            INSERT INTO followup_logs (transaksi_id, followup_message_id, pelanggan_id, jadwal_kirim, status) 
            VALUES (?, ?, ?, ?, ?)
        ", [$transaksi['id'], $followup_message_id, $transaksi['pelanggan_id'], $jadwal_kirim, $status]);
        
        if ($inserted) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Auto-delete followup saat status transaksi berubah (CLEAN VERSION)
 */
function autoUpdateFollowupOnStatusChange($transaksi_id, $old_status, $new_status) {
    if ($old_status === 'pending' && $new_status !== 'pending') {
        
        // COUNT berapa yang akan dihapus untuk logging
        $count_to_delete = fetchRow("
            SELECT COUNT(*) as count 
            FROM followup_logs 
            WHERE transaksi_id = ? 
            AND status IN ('pending', 'waiting')
        ", [$transaksi_id])['count'];
        
        if ($count_to_delete > 0) {
            // DELETE pending followups (bukan update ke skip)
            $deleted = execute("
                DELETE FROM followup_logs 
                WHERE transaksi_id = ? 
                AND status IN ('pending', 'waiting')
            ", [$transaksi_id]);
            
            if ($deleted) {
                error_log("✂️ DELETED $count_to_delete followup logs for transaction #$transaksi_id (status: $old_status → $new_status)");
                return $count_to_delete;
            }
        }
    }
    
    return 0;
}

function cleanupObsoleteFollowupLogs() {
    // Hapus followup logs untuk transaksi yang sudah tidak pending
    $deleted = execute("
        DELETE fl FROM followup_logs fl
        JOIN transaksi t ON fl.transaksi_id = t.id
        WHERE t.status != 'pending'
        AND fl.status IN ('pending', 'waiting')
    ");
    
    if ($deleted) {
        $count = db()->affected_rows;
        error_log("🧹 CLEANUP: Deleted $count obsolete followup logs");
        return $count;
    }
    
    return 0;
}

/**
 * Get stats followup logs yang bisa di-cleanup
 */
function getCleanupStats() {
    return fetchRow("
        SELECT 
            COUNT(*) as total_obsolete,
            COUNT(DISTINCT fl.transaksi_id) as affected_transactions
        FROM followup_logs fl
        JOIN transaksi t ON fl.transaksi_id = t.id
        WHERE t.status != 'pending'
        AND fl.status IN ('pending', 'waiting')
    ");
}
?>