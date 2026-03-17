<?php
/**
 * Contoh Penggunaan Universal WhatsApp Helper
 * 
 * File ini berisi contoh-contoh penggunaan WhatsApp helper
 * yang bisa dipakai di seluruh sistem CRM
 */

require_once __DIR__ . '/../includes/whatsapp_helper.php';

/**
 * CONTOH 1: Kirim Invoice ke Customer
 */
function kirimInvoice($transaksi_id) {
    // Get transaction data
    $transaksi = fetchRow("
        SELECT t.*, p.nama as nama_customer, p.nomor_wa, pr.nama as nama_produk 
        FROM transaksi t 
        JOIN pelanggan p ON t.pelanggan_id = p.id 
        JOIN detail_transaksi dt ON t.id = dt.transaksi_id 
        JOIN produk pr ON dt.produk_id = pr.id 
        WHERE t.id = ?
    ", [$transaksi_id]);
    
    if (!$transaksi) return false;
    
    // Create invoice message
    $message = "🧾 INVOICE PEMBAYARAN\n\n";
    $message .= "Halo {$transaksi['nama_customer']}!\n\n";
    $message .= "Produk: {$transaksi['nama_produk']}\n";
    $message .= "Total: " . formatCurrency($transaksi['total_harga']) . "\n";
    $message .= "ID Transaksi: {$transaksi['id']}\n\n";
    $message .= "Silakan lakukan pembayaran sesuai instruksi.\n";
    $message .= "Terima kasih! 🙏";
    
    // Send message
    return sendWhatsAppText($transaksi['nomor_wa'], $message);
}

/**
 * CONTOH 2: Kirim Akses Produk Digital
 */
function kirimAksesProduk($transaksi_id) {
    $transaksi = fetchRow("
        SELECT t.*, p.nama as nama_customer, p.nomor_wa, pr.nama as nama_produk, pr.link_akses 
        FROM transaksi t 
        JOIN pelanggan p ON t.pelanggan_id = p.id 
        JOIN detail_transaksi dt ON t.id = dt.transaksi_id 
        JOIN produk pr ON dt.produk_id = pr.id 
        WHERE t.id = ?
    ", [$transaksi_id]);
    
    if (!$transaksi) return false;
    
    $message = "🎉 AKSES PRODUK DIGITAL\n\n";
    $message .= "Halo {$transaksi['nama_customer']}!\n\n";
    $message .= "Pembayaran berhasil! Berikut akses produk Anda:\n\n";
    $message .= "📦 Produk: {$transaksi['nama_produk']}\n";
    $message .= "🔗 Link Akses: {$transaksi['link_akses']}\n\n";
    $message .= "Selamat menggunakan!\n";
    $message .= "Hubungi admin jika ada kendala.";
    
    return sendWhatsAppText($transaksi['nomor_wa'], $message);
}

/**
 * CONTOH 3: Kirim Reminder Pembayaran dengan Gambar QRIS
 */
function kirimReminderPembayaran($transaksi_id) {
    $transaksi = fetchRow("
        SELECT t.*, p.nama as nama_customer, p.nomor_wa, r.nama_bank 
        FROM transaksi t 
        JOIN pelanggan p ON t.pelanggan_id = p.id 
        LEFT JOIN rekening r ON r.id = 1
        WHERE t.id = ?
    ", [$transaksi_id]);
    
    if (!$transaksi) return false;
    
    $message = "⏰ REMINDER PEMBAYARAN\n\n";
    $message .= "Halo {$transaksi['nama_customer']}!\n\n";
    $message .= "Transaksi Anda masih pending:\n";
    $message .= "💰 Total: " . formatCurrency($transaksi['total_harga']) . "\n";
    $message .= "🏦 Transfer ke: {$transaksi['nama_bank']}\n\n";
    $message .= "Scan QR Code di bawah untuk pembayaran cepat 👇";
    
    // QRIS image URL (from your rekening module)
    $qris_image = BASE_URL . "assets/qr/qris_1.png";
    
    return sendWhatsAppImage($transaksi['nomor_wa'], $qris_image, $message);
}

/**
 * CONTOH 4: Broadcast Promo ke Semua Pelanggan
 */
function broadcastPromo($promo_message, $promo_image = null) {
    // Get all active customers
    $customers = fetchAll("SELECT nomor_wa FROM pelanggan WHERE nomor_wa IS NOT NULL AND nomor_wa != ''");
    
    if (empty($customers)) return ['success' => false, 'error' => 'No customers found'];
    
    // Prepare recipients
    $recipients = [];
    foreach ($customers as $customer) {
        $recipients[] = [
            'phone' => $customer['nomor_wa'],
            'message' => $promo_message,
            'image_url' => $promo_image
        ];
    }
    
    // Send bulk with 3 second delay between messages
    return sendWhatsAppBulk($recipients, 3);
}

/**
 * CONTOH 5: Notifikasi ke Admin saat Transaksi Baru
 */
function notifikasiAdminTransaksiBaru($transaksi_id) {
    $transaksi = fetchRow("
        SELECT t.*, p.nama as nama_customer, pr.nama as nama_produk 
        FROM transaksi t 
        JOIN pelanggan p ON t.pelanggan_id = p.id 
        JOIN detail_transaksi dt ON t.id = dt.transaksi_id 
        JOIN produk pr ON dt.produk_id = pr.id 
        WHERE t.id = ?
    ", [$transaksi_id]);
    
    if (!$transaksi) return false;
    
    $message = "🔔 TRANSAKSI BARU!\n\n";
    $message .= "Customer: {$transaksi['nama_customer']}\n";
    $message .= "Produk: {$transaksi['nama_produk']}\n";
    $message .= "Total: " . formatCurrency($transaksi['total_harga']) . "\n";
    $message .= "Status: {$transaksi['status']}\n";
    $message .= "Waktu: " . formatDate($transaksi['tanggal_transaksi'], 'd/m/Y H:i') . "\n\n";
    $message .= "Cek dashboard untuk detail lengkap.";
    
    // Admin phone number (you can store this in settings)
    $admin_phone = '6289508618321'; // Replace with actual admin number
    
    return sendWhatsAppText($admin_phone, $message);
}

/**
 * CONTOH 6: Follow-up Otomatis Customer Inactive
 */
function followupCustomerInactive() {
    // Get customers who haven't made transaction in 30 days
    $inactive_customers = fetchAll("
        SELECT p.nama, p.nomor_wa, MAX(t.tanggal_transaksi) as last_transaction 
        FROM pelanggan p 
        LEFT JOIN transaksi t ON p.id = t.pelanggan_id 
        GROUP BY p.id, p.nama, p.nomor_wa 
        HAVING last_transaction IS NULL OR last_transaction < DATE_SUB(NOW(), INTERVAL 30 DAY)
        LIMIT 10
    ");
    
    $results = [];
    foreach ($inactive_customers as $customer) {
        $message = "👋 Halo {$customer['nama']}!\n\n";
        $message .= "Kami merindukan Anda! 💝\n\n";
        $message .= "Ada produk-produk baru yang menarik di katalog kami.\n";
        $message .= "Yuk, cek katalog terbaru dan dapatkan diskon spesial!\n\n";
        $message .= "Link katalog: " . BASE_URL . "\n";
        $message .= "Gunakan kode: COMEBACK20 untuk diskon 20%";
        
        $result = sendWhatsAppText($customer['nomor_wa'], $message);
        $results[] = [
            'customer' => $customer['nama'],
            'result' => $result
        ];
        
        // Delay 2 seconds between messages
        sleep(2);
    }
    
    return $results;
}

/**
 * CONTOH 7: Template Message dengan Placeholder
 */
function kirimTemplateMessage($customer_id, $template_name, $additional_data = []) {
    // Get customer data
    $customer = fetchRow("SELECT * FROM pelanggan WHERE id = ?", [$customer_id]);
    if (!$customer) return false;
    
    // Get template
    $template = fetchRow("SELECT * FROM pengaturan_pesan WHERE jenis_pesan = ?", [$template_name]);
    if (!$template) return false;
    
    // Prepare template data
    $template_data = array_merge([
        'nama_customer' => $customer['nama'],
        'nomor_wa' => $customer['nomor_wa'],
        'tanggal' => date('d/m/Y'),
        'waktu' => date('H:i')
    ], $additional_data);
    
    // Replace placeholders (you need to implement this based on your template system)
    $message = replaceTemplatePlaceholders($template['isi_pesan'], $template_data);
    
    return sendWhatsAppText($customer['nomor_wa'], $message);
}

/**
 * CONTOH 8: Integration dengan Follow-up System
 */
function processFollowupMessage($followup_log_id) {
    require_once __DIR__ . '/../modules/followup/functions.php';
    
    // Get follow-up data
    $followup = fetchRow("
        SELECT fl.*, fm.tipe_pesan, fm.isi_pesan, fm.link_gambar,
               p.nama as nama_customer, p.nomor_wa,
               t.total_harga, pr.nama as nama_produk
        FROM followup_logs fl
        JOIN followup_messages fm ON fl.followup_message_id = fm.id
        JOIN pelanggan p ON fl.pelanggan_id = p.id
        JOIN transaksi t ON fl.transaksi_id = t.id
        JOIN detail_transaksi dt ON t.id = dt.transaksi_id
        JOIN produk pr ON dt.produk_id = pr.id
        WHERE fl.id = ?
    ", [$followup_log_id]);
    
    if (!$followup) return false;
    
    // Process message with placeholders
    $processed = processFollowupMessage($followup);
    
    // Send using universal helper
    $result = sendWhatsAppAuto(
        $processed['phone'], 
        $processed['message'], 
        $processed['image_url']
    );
    
    // Update follow-up log
    if ($result['success']) {
        markFollowupAsSent($followup_log_id, $processed['message']);
    } else {
        markFollowupAsFailed($followup_log_id, $result['error']);
    }
    
    return $result;
}

// Usage examples:
/*
// Kirim invoice
$result = kirimInvoice(123);

// Kirim akses produk
$result = kirimAksesProduk(123);

// Broadcast promo
$result = broadcastPromo(
    "🔥 FLASH SALE! Diskon 50% semua produk digital!\nBerlaku sampai besok!",
    "https://yourdomain.com/images/promo.jpg"
);

// Test koneksi
$test = testWhatsAppConnection();
if ($test['success']) {
    echo "WhatsApp API connected!";
}

// Get statistics
$stats = getWhatsAppStats(7); // Last 7 days
echo "Sent: {$stats['total_sent']}, Success: {$stats['success']}";
*/
?>