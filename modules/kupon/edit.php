<?php
require_once __DIR__ . '/../../includes/init.php';

if (!isLoggedIn()) {
    redirect(BASE_URL . 'login.php');
}

$page_title = "Edit Kupon";

// Ambil ID dari URL
$id = (int) get('id');
if (!$id) {
    redirect('index.php');
}

// Cek apakah data kupon ada
$kupon = fetchRow("SELECT * FROM kupon WHERE id = ?", [$id]);
if (!$kupon) {
    setMessage("Kupon tidak ditemukan.", "danger");
    redirect('index.php');
}

// Ambil daftar produk untuk dropdown
$produk_list = fetchAll("SELECT id, nama FROM produk ORDER BY nama ASC");

// Proses update jika form disubmit
if (isPost()) {
    $kode_kupon = strtoupper(clean(post('kode_kupon')));
    $tipe_diskon = post('tipe_diskon');
    $nilai_diskon = post('nilai_diskon');
    $max_potongan = post('max_potongan') ? post('max_potongan') : NULL;
    $produk_id = post('produk_id') ? post('produk_id') : NULL;
    $kuota = post('kuota');
    
    // Perbaiki format tanggal dari input HTML HTML ke format database (YYYY-MM-DD HH:MM:SS)
    $tgl_mulai = str_replace('T', ' ', post('tgl_mulai'));
    if (strlen($tgl_mulai) == 16) $tgl_mulai .= ':00'; // Tambah detik jika tidak ada
    
    $tgl_selesai = str_replace('T', ' ', post('tgl_selesai'));
    if (strlen($tgl_selesai) == 16) $tgl_selesai .= ':00';
    
    $is_active = post('is_active') ? 1 : 0;

    $query = "UPDATE kupon SET kode_kupon=?, tipe_diskon=?, nilai_diskon=?, max_potongan=?, produk_id=?, kuota=?, tgl_mulai=?, tgl_selesai=?, is_active=? WHERE id=?";
    
    $update = execute($query, [$kode_kupon, $tipe_diskon, $nilai_diskon, $max_potongan, $produk_id, $kuota, $tgl_mulai, $tgl_selesai, $is_active, $id]);

    if ($update) {
        setMessage("Kupon berhasil diperbarui!", "success");
        redirect('index.php');
    } else {
        setMessage("Gagal menyimpan perubahan. Pastikan kode kupon tidak bentrok dengan yang lain.", "danger");
    }
}

// Format waktu untuk dimasukkan ke dalam <input type="datetime-local">
$val_tgl_mulai = date('Y-m-d\TH:i', strtotime($kupon['tgl_mulai']));
$val_tgl_selesai = date('Y-m-d\TH:i', strtotime($kupon['tgl_selesai']));

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="bg-white border-bottom p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">Edit Kupon</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Kupon</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4">
        <?php if ($msg = getMessage()): ?>
            <div class="alert alert-<?= $msg[1] ?> alert-dismissible fade show">
                <?= $msg[0] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kode Kupon</label>
                            <input type="text" name="kode_kupon" class="form-control" value="<?= clean($kupon['kode_kupon']) ?>" required>
                            <small class="text-muted">Huruf kapital tanpa spasi.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipe Diskon</label>
                            <select name="tipe_diskon" class="form-select" required>
                                <option value="nominal" <?= $kupon['tipe_diskon'] == 'nominal' ? 'selected' : '' ?>>Nominal (Rupiah)</option>
                                <option value="persentase" <?= $kupon['tipe_diskon'] == 'persentase' ? 'selected' : '' ?>>Persentase (%)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Besar Diskon</label>
                            <input type="number" name="nilai_diskon" class="form-control" value="<?= (int)$kupon['nilai_diskon'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Maksimal Potongan</label>
                            <input type="number" name="max_potongan" class="form-control" value="<?= $kupon['max_potongan'] ? (int)$kupon['max_potongan'] : '' ?>" placeholder="Opsional, khusus persentase">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Khusus Produk</label>
                            <select name="produk_id" class="form-select">
                                <option value="">-- Berlaku Semua Produk --</option>
                                <?php foreach ($produk_list as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $kupon['produk_id'] == $p['id'] ? 'selected' : '' ?>><?= clean($p['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kuota Penggunaan</label>
                            <input type="number" name="kuota" class="form-control" required value="<?= $kupon['kuota'] ?>">
                            <small class="text-muted text-danger">Isi angka 0 jika ingin kupon tanpa batas (unlimited).</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="datetime-local" name="tgl_mulai" class="form-control" value="<?= $val_tgl_mulai ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="datetime-local" name="tgl_selesai" class="form-control" value="<?= $val_tgl_selesai ?>" required>
                        </div>
                    </div>

                    <div class="mb-4 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= $kupon['is_active'] == 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Aktifkan Kupon</label>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>