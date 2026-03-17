<?php
/**
 * Sequential Followup Scheduler - Updated Version
 */

$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    echo "<pre style='font-family: monospace; background: #f5f5f5; padding: 20px;'>";
    echo "🚀 Sequential Followup Scheduler - Web Debug Mode\n";
    echo str_repeat("=", 50) . "\n\n";
}

// Load dependencies
try {
    require_once __DIR__ . '/../includes/init.php';
    require_once __DIR__ . '/../includes/whatsapp_helper.php';
    require_once __DIR__ . '/../modules/followup/functions.php';
    log_message("✅ Dependencies loaded successfully");
} catch (Exception $e) {
    log_message("❌ FATAL: Failed to load dependencies - " . $e->getMessage());
    exit(1);
}

// Logging function
function log_message($message) {
    static $log_file = null;
    
    if ($log_file === null) {
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $log_file = $log_dir . '/followup_' . date('Y-m') . '.log';
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
    if (php_sapi_name() !== 'cli') {
        echo $log_entry;
        flush();
    }
}

log_message('🚀 Sequential followup scheduler started');

try {
    // UPDATED QUERY: Optimized untuk menghindari duplicate product joins
    $query = "
        SELECT DISTINCT
            fl.id as log_id,
            fl.transaksi_id,
            fl.followup_message_id,
            fl.pelanggan_id,
            fl.jadwal_kirim,
            fm.nama_pesan,
            fm.isi_pesan,
            fm.tipe_pesan,
            fm.link_gambar,
            fm.urutan,
            p.nama as pelanggan_nama,
            p.nomor_wa,
            -- Get onesender_account from the product that generated this followup
            (SELECT pr.onesender_account 
             FROM followup_messages fmx 
             JOIN produk pr ON fmx.produk_id = pr.id 
             WHERE fmx.id = fl.followup_message_id 
             LIMIT 1) as onesender_account
        FROM followup_logs fl
        JOIN followup_messages fm ON fl.followup_message_id = fm.id
        JOIN pelanggan p ON fl.pelanggan_id = p.id
        WHERE fl.status = 'pending'
        AND fl.jadwal_kirim <= NOW()
        AND fm.status = 'aktif'
        ORDER BY fl.jadwal_kirim ASC, fm.urutan ASC
        LIMIT 20
    ";
    
    log_message("🔍 Executing optimized query...");
    $pending_messages = fetchAll($query);
    
    if (empty($pending_messages)) {
        log_message('ℹ️ No pending messages to send');
        
        // Show upcoming for debugging
        $upcoming = fetchAll("
            SELECT DATE(jadwal_kirim) as tanggal, COUNT(*) as count
            FROM followup_logs 
            WHERE status = 'pending' 
            AND jadwal_kirim > NOW()
            GROUP BY DATE(jadwal_kirim) 
            ORDER BY tanggal ASC 
            LIMIT 5
        ");
        
        if (!empty($upcoming)) {
            log_message("📅 Upcoming messages:");
            foreach ($upcoming as $up) {
                log_message("   - " . date('d/m/Y', strtotime($up['tanggal'])) . ": {$up['count']} messages");
            }
        }
        
        // Show stats untuk context
        $stats = [
            'total_pending' => fetchRow("SELECT COUNT(*) as count FROM followup_logs WHERE status = 'pending'")['count'],
            'total_sent_today' => fetchRow("SELECT COUNT(*) as count FROM followup_logs WHERE status = 'terkirim' AND DATE(waktu_kirim) = CURDATE()")['count'],
            'failed_today' => fetchRow("SELECT COUNT(*) as count FROM followup_logs WHERE status = 'gagal' AND DATE(created_at) = CURDATE()")['count']
        ];
        
        log_message("📊 Current Stats: {$stats['total_pending']} pending, {$stats['total_sent_today']} sent today, {$stats['failed_today']} failed today");
        
        exit(0);
    }
    
    log_message("📬 Found " . count($pending_messages) . " messages to send");
    
    $sent_count = 0;
    $failed_count = 0;
    $processed_ids = [];
    
    foreach ($pending_messages as $message) {
		try {
			// Gunakan functions dengan validasi
			$final_message = replaceTransactionPlaceholders(
				$message['isi_pesan'], 
				$message['transaksi_id']
			);

			if ($final_message === false) {
				log_message("⚠️ Transaction no longer pending: {$message['transaksi_id']}");

				// DELETE followup log (bukan update ke skip)
				execute("DELETE FROM followup_logs WHERE id = ?", [$message['log_id']]);
				log_message("✂️ Deleted obsolete followup log ID: {$message['log_id']}");
				continue;
			}

			if (empty($final_message)) {
				log_message("⚠️ Empty message for log ID: {$message['log_id']}");
				continue;
			}

			$result = sendFollowupMessage($message, $final_message);

			if ($result['success']) {
				processSuccessfulMessageSequential($message, $final_message);
				$sent_count++;
			} else {
				$failed_count++;
			}

		} catch (Exception $e) {
			log_message("❌ EXCEPTION: ID {$message['log_id']} - " . $e->getMessage());
			$failed_count++;
		}
	}
    
    log_message("📊 SUMMARY: $sent_count sent, $failed_count failed");
    
    // Enhanced stats untuk monitoring
    $stats = fetchRow("
        SELECT 
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as total_pending,
            COUNT(CASE WHEN status = 'terkirim' THEN 1 END) as total_sent,
            COUNT(CASE WHEN status = 'terkirim' AND DATE(waktu_kirim) = CURDATE() THEN 1 END) as sent_today,
            COUNT(CASE WHEN status = 'gagal' AND DATE(created_at) = CURDATE() THEN 1 END) as failed_today
        FROM followup_logs
    ");
    
    log_message("📈 OVERALL STATS: {$stats['total_pending']} pending, {$stats['total_sent']} total sent, {$stats['sent_today']} sent today, {$stats['failed_today']} failed today");
    
    // Performance info
    $execution_time = microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
    log_message("⏱️ Execution time: " . round($execution_time, 2) . " seconds");
    
} catch (Exception $e) {
    log_message('💥 FATAL ERROR: ' . $e->getMessage());
    log_message('Stack trace: ' . $e->getTraceAsString());
    exit(1);
}
log_message('🏁 Sequential followup scheduler finished successfully');

if (!$is_cli) {
    echo "</pre>";
    echo "<p><a href='../debug_followup.php'>← Back to Debug</a></p>";
}

exit(0);
?>