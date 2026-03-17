<?php
/**
 * Quick Debug untuk Followup System
 * Simpan sebagai: debug_followup.php di root folder
 */

require_once 'includes/init.php';

echo "<h2>🔍 Debug Followup System</h2>";
echo "<pre style='background:#f5f5f5; padding:15px; border-radius:5px;'>";

// 1. CEK DATABASE TABLES
echo "1. CEK TABEL DATABASE:\n";
$tables = ['followup_logs', 'followup_messages', 'pelanggan', 'transaksi', 'detail_transaksi', 'produk'];
foreach ($tables as $table) {
    $result = mysqli_query(db(), "SELECT COUNT(*) as count FROM $table");
    if ($result) {
        $count = mysqli_fetch_assoc($result)['count'];
        echo "   ✅ $table: $count records\n";
    } else {
        echo "   ❌ $table: ERROR - " . mysqli_error(db()) . "\n";
    }
}

echo "\n2. CEK FOLLOWUP MESSAGES AKTIF:\n";
$fm = fetchAll("SELECT id, produk_id, nama_pesan, delay_value, delay_unit, status FROM followup_messages WHERE status = 'aktif'");
if ($fm) {
    foreach ($fm as $f) {
        echo "   📧 ID {$f['id']}: {$f['nama_pesan']} (Produk {$f['produk_id']}) - Delay: {$f['delay_value']} {$f['delay_unit']}\n";
    }
} else {
    echo "   ⚠️ Tidak ada followup message aktif!\n";
}

echo "\n3. CEK FOLLOWUP LOGS PENDING:\n";
$fl = fetchAll("
    SELECT fl.id, fl.transaksi_id, fl.jadwal_kirim, fl.status, p.nama, p.nomor_wa, fm.nama_pesan
    FROM followup_logs fl
    LEFT JOIN pelanggan p ON fl.pelanggan_id = p.id
    LEFT JOIN followup_messages fm ON fl.followup_message_id = fm.id
    WHERE fl.status = 'pending'
    ORDER BY fl.jadwal_kirim ASC
    LIMIT 10
");

if ($fl) {
    foreach ($fl as $f) {
        $jadwal = date('d/m/Y H:i', strtotime($f['jadwal_kirim']));
        $status = ($f['jadwal_kirim'] <= date('Y-m-d H:i:s')) ? '🟢 READY' : '🟡 WAITING';
        echo "   $status Log ID {$f['id']}: {$f['nama']} ({$f['nomor_wa']}) - {$f['nama_pesan']} - $jadwal\n";
    }
} else {
    echo "   ⚠️ Tidak ada followup logs pending!\n";
}

echo "\n4. CEK TRANSAKSI BELUM SELESAI:\n";
$tr = fetchAll("
    SELECT t.id, t.status, p.nama, t.tanggal_transaksi, t.total_harga
    FROM transaksi t
    LEFT JOIN pelanggan p ON t.pelanggan_id = p.id
    WHERE t.status != 'selesai'
    ORDER BY t.tanggal_transaksi DESC
    LIMIT 5
");

if ($tr) {
    foreach ($tr as $t) {
        $tanggal = date('d/m/Y', strtotime($t['tanggal_transaksi']));
        echo "   📋 Transaksi {$t['id']}: {$t['nama']} - Status: {$t['status']} - $tanggal - " . formatCurrency($t['total_harga']) . "\n";
    }
} else {
    echo "   ✅ Semua transaksi sudah selesai!\n";
}

echo "\n5. CEK FILE DEPENDENCY:\n";
$files = [
    'includes/whatsapp_helper.php',
    'modules/followup/functions.php',
    'logs/' // directory
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (is_dir($path) || file_exists($path)) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ $file - FILE NOT FOUND!\n";
    }
}

echo "\n6. CEK ONESENDER CONFIG:\n";
$config = fetchAll("SELECT account_name, api_url FROM onesender_config");
if ($config) {
    foreach ($config as $c) {
        echo "   📡 Account: {$c['account_name']} - URL: {$c['api_url']}\n";
    }
} else {
    echo "   ⚠️ Tidak ada konfigurasi OneSender!\n";
}

echo "\n7. CEK CRON JOB (Manual Test):\n";
echo "   🔄 Mencoba jalankan scheduler manual...\n";

// Test run scheduler
$test_query = "
    SELECT COUNT(*) as count
    FROM followup_logs fl
    JOIN followup_messages fm ON fl.followup_message_id = fm.id
    WHERE fl.status = 'pending'
    AND fl.jadwal_kirim <= NOW()
    AND fm.status = 'aktif'
";

$test_result = fetchRow($test_query);
echo "   📊 Pesan siap kirim: {$test_result['count']}\n";

if ($test_result['count'] > 0) {
    echo "   ⚠️ Ada pesan yang siap dikirim tapi belum terkirim!\n";
    echo "   💡 Coba jalankan: php cron/followup_scheduler.php\n";
}

echo "\n=== SELESAI ===\n";
echo "</pre>";

echo "<h3>🛠️ LANGKAH SELANJUTNYA:</h3>";
echo "<ol>";
echo "<li>Jika ada file dependency yang hilang, beri tahu saya untuk membuatkannya</li>";
echo "<li>Jika ada data kosong, kita perlu setup data followup</li>";
echo "<li>Jika semua OK, coba jalankan cron manual: <code>php cron/followup_scheduler.php</code></li>";
echo "<li>Cek log di folder <code>logs/followup_*.log</code></li>";
echo "</ol>";

echo "<p><a href='cron/followup_scheduler.php' target='_blank'>🔗 Test Run Scheduler (Web)</a></p>";
?>