<?php
// Koneksi ke database
$host = 'localhost';
$dbname = 'wegqxcgv_crm';
$username = 'wegqxcgv_crm'; // GANTI SESUAI
$password = '_N8t8mu07'; // GANTI SESUAI

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h3>Koneksi Database: <span style='color: green;'>BERHASIL</span></h3>";
} catch (PDOException $e) {
    die("<h3>Koneksi Database: <span style='color: red;'>GAGAL</span></h3><p>" . $e->getMessage() . "</p>");
}

// Ambil Produk
$allProducts = [];
try {
    $stmt = $pdo->query("SELECT id, nama FROM produk ORDER BY nama ASC");
    $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Data Produk:</h3>";
    if (empty($allProducts)) {
        echo "<p style='color: orange;'>Tabel 'produk' kosong.</p>";
    } else {
        echo "<ul>";
        foreach ($allProducts as $prod) {
            echo "<li>ID: " . $prod['id'] . " - Nama: " . htmlspecialchars($prod['nama']) . "</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<h3>Data Produk: <span style='color: red;'>GAGAL</span></h3><p>" . $e->getMessage() . "</p>";
}

// Ambil Akun WA
$onesenderAccounts = [];
try {
    $stmt = $pdo->query("SELECT account_name FROM onesender_config ORDER BY account_name ASC");
    $onesenderAccounts = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo "<h3>Data Akun WA:</h3>";
    if (empty($onesenderAccounts)) {
        echo "<p style='color: orange;'>Tabel 'onesender_config' kosong.</p>";
    } else {
        echo "<ul>";
        foreach ($onesenderAccounts as $account) {
            echo "<li>" . htmlspecialchars($account) . "</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<h3>Data Akun WA: <span style='color: red;'>GAGAL</span></h3><p>" . $e->getMessage() . "</p>";
}

// Tampilkan filter yang diterima (jika ada)
echo "<h3>Filter dari URL (GET):</h3>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

?>