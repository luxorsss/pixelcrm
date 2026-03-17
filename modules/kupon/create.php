<?php
session_start();
require_once '../../includes/init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Ambil daftar produk untuk pilihan dropdown
$stmt_produk = $conn->query("SELECT id, nama FROM produk ORDER BY nama ASC");
$produk_list = $stmt_produk->fetchAll(PDO::FETCH_ASSOC);

// Proses jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_kupon = strtoupper(trim($_POST['kode_kupon']));
    $tipe_diskon = $_POST['tipe_diskon'];
    $nilai_diskon = $_POST['nilai_diskon'];
    $max_potongan = !empty($_POST['max_potongan']) ? $_POST['max_potongan'] : NULL;
    $produk_id = !empty($_POST['produk_id']) ? $_POST['produk_id'] : NULL;
    $kuota = $_POST['kuota'];
    $tgl_mulai = $_POST['tgl_mulai'];
    $tgl_selesai = $_POST['tgl_selesai'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Simpan ke database
    $query = "INSERT INTO kupon (kode_kupon, tipe_diskon, nilai_diskon, max_potongan, produk_id, kuota, tgl_mulai, tgl_selesai, is_active) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    try {
        $stmt->execute([$kode_kupon, $tipe_diskon, $nilai_diskon, $max_potongan, $produk_id, $kuota, $tgl_mulai, $tgl_selesai, $is_active]);
        echo "<script>alert('Kupon berhasil ditambahkan!'); window.location='index.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Gagal! Mungkin kode kupon sudah ada.');</script>";
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Tambah Kupon Baru</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Kode Kupon</label>
                                <input type="text" name="kode_kupon" class="form-control"
                                    placeholder="Contoh: RAMADAN20" required>
                                <small class="text-muted">Gunakan huruf kapital dan tanpa spasi.</small>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Tipe Diskon</label>
                                <select name="tipe_diskon" class="form-control" required>
                                    <option value="nominal">Nominal (Rupiah)</option>
                                    <option value="persentase">Persentase (%)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Nilai Diskon</label>
                                <input type="number" name="nilai_diskon" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Maksimal Potongan (Opsional, khusus persentase)</label>
                                <input type="number" name="max_potongan" class="form-control"
                                    placeholder="Kosongkan jika tidak ada batas">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Produk Spesifik (Opsional)</label>
                                <select name="produk_id" class="form-control">
                                    <option value="">-- Berlaku Semua Produk --</option>
                                    <?php foreach ($produk_list as $p): ?>
                                        <option value="<?= $p['id'] ?>">
                                            <?= htmlspecialchars($p['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Kuota Penggunaan</label>
                                <input type="number" name="kuota" class="form-control" required
                                    placeholder="Berapa kali kupon bisa dipakai">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Tanggal & Waktu Mulai</label>
                                <input type="datetime-local" name="tgl_mulai" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Tanggal & Waktu Selesai</label>
                                <input type="datetime-local" name="tgl_selesai" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                    checked>
                                <label class="custom-control-label" for="is_active">Aktifkan Kupon Ini</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Kupon</button>
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>