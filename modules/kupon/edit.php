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

<div class="main-content dashboard-wrapper">
    <div class="form-container" style="max-width: 1000px;">
        
        <div class="dash-header mb-4">
            <div>
                <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Kupon
                </a>
                <h1 class="dash-title mt-2">Edit Kupon Promo</h1>
            </div>
            <div class="d-none d-md-block">
                <?php if ($kupon['is_active'] == 1 && strtotime($kupon['tgl_selesai']) > time()): ?>
                    <span class="badge-clean bg-success text-white"><i class="fas fa-satellite-dish me-1"></i> Promo Sedang Berjalan</span>
                <?php else: ?>
                    <span class="badge-clean bg-secondary text-white"><i class="fas fa-archive me-1"></i> Promo Berakhir / Nonaktif</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($msg = getMessage()): ?>
            <div class="alert alert-editorial mb-4" style="border-left-color: <?= $msg[1] === 'error' || $msg[1] === 'danger' ? 'var(--danger-color)' : 'var(--success-color)' ?>;">
                <div class="d-flex align-items-center">
                    <i class="fas <?= $msg[1] === 'error' || $msg[1] === 'danger' ? 'fa-exclamation-circle text-danger' : 'fa-check-circle text-success' ?> me-2 fs-5"></i>
                    <span class="fw-bold text-dark"><?= clean($msg[0]) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="kuponForm">
            <div class="row g-4">
                <div class="col-lg-8">
                    
                    <div class="panel-editorial">
                        <h3 class="panel-title"><i class="fas fa-tag text-primary"></i> Identitas Kupon</h3>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kode Promo <span class="text-danger">*</span></label>
                                <input type="text" name="kode_kupon" class="form-control-editorial text-uppercase fw-bold text-primary" 
                                       value="<?= clean($kupon['kode_kupon']) ?>" required autocomplete="off">
                                <div class="text-muted mt-2" style="font-size: 0.75rem;">Gunakan huruf kapital tanpa spasi.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Target Produk</label>
                                <select name="produk_id" class="form-control-editorial" style="appearance: auto;">
                                    <option value="">-- Berlaku Semua Produk --</option>
                                    <?php foreach ($produk_list as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= $kupon['produk_id'] == $p['id'] ? 'selected' : '' ?>><?= clean($p['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="panel-editorial">
                        <h3 class="panel-title"><i class="fas fa-percent text-success"></i> Skema Diskon</h3>
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Tipe Potongan <span class="text-danger">*</span></label>
                                <select name="tipe_diskon" id="tipe_diskon" class="form-control-editorial" required style="appearance: auto;">
                                    <option value="nominal" <?= $kupon['tipe_diskon'] == 'nominal' ? 'selected' : '' ?>>Nominal (Rp)</option>
                                    <option value="persentase" <?= $kupon['tipe_diskon'] == 'persentase' ? 'selected' : '' ?>>Persentase (%)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Besar Diskon <span class="text-danger">*</span></label>
                                <input type="number" name="nilai_diskon" class="form-control-editorial" min="1" value="<?= (int)$kupon['nilai_diskon'] ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Maksimal Potongan</label>
                                <input type="number" name="max_potongan" id="max_potongan" class="form-control-editorial" 
                                       value="<?= $kupon['max_potongan'] ? (int)$kupon['max_potongan'] : '' ?>">
                            </div>
                        </div>
                        <div class="text-muted mt-2" style="font-size: 0.75rem;">
                            <i class="fas fa-info-circle me-1"></i> Maksimal potongan hanya berlaku jika tipe diskon adalah <strong>Persentase</strong>.
                        </div>
                    </div>

                    <div class="panel-editorial mb-4 mb-lg-0">
                        <h3 class="panel-title"><i class="fas fa-calendar-alt text-warning"></i> Masa Berlaku</h3>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Waktu Mulai <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="tgl_mulai" class="form-control-editorial" value="<?= $val_tgl_mulai ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Waktu Berakhir <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="tgl_selesai" class="form-control-editorial" value="<?= $val_tgl_selesai ?>" required>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-lg-4">
                    
                    <div class="panel-editorial mb-4" style="background: #F9FAFB; border: 1px dashed var(--border-light);">
                        <h3 class="panel-title" style="font-size: 1rem;"><i class="fas fa-chart-line text-primary"></i> Performa Promo</h3>
                        
                        <?php 
                            $kuotaTotal = (int)$kupon['kuota'];
                            $terpakai = (int)$kupon['terpakai'];
                            $percent = $kuotaTotal > 0 ? ($terpakai / $kuotaTotal) * 100 : 0;
                            $color = $percent >= 90 ? '#EF4444' : ($percent >= 50 ? '#F59E0B' : '#10B981');
                        ?>
                        
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <span class="text-muted fw-bold" style="font-size: 0.75rem; text-transform: uppercase;">Telah Dipakai</span>
                            <div class="text-dark fw-bold" style="font-size: 1.5rem; line-height: 1;"><?= $terpakai ?> <span class="text-muted fw-medium" style="font-size: 0.9rem;">/ <?= $kuotaTotal > 0 ? $kuotaTotal : '&infin;' ?></span></div>
                        </div>
                        
                        <?php if ($kuotaTotal > 0): ?>
                            <div style="width: 100%; height: 6px; background: #E5E7EB; border-radius: 4px; overflow: hidden; display: flex;">
                                <div style="width: <?= $percent ?>%; height: 100%; background: <?= $color ?>; border-radius: 4px;"></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="panel-editorial mb-4">
                        <h3 class="panel-title" style="font-size: 1rem;"><i class="fas fa-users text-info"></i> Ubah Limitasi</h3>
                        <label class="form-label">Kuota Penggunaan <span class="text-danger">*</span></label>
                        <input type="number" name="kuota" class="form-control-editorial fw-bold" required value="<?= $kuotaTotal ?>" min="0" style="font-size: 1.2rem;">
                        <div class="text-muted mt-2" style="font-size: 0.75rem; line-height: 1.5;">
                            Isi angka <strong class="text-danger">0</strong> jika ingin kupon tanpa batas (unlimited).
                        </div>
                    </div>

                    <div class="panel-editorial mb-4">
                        <h3 class="panel-title" style="font-size: 1rem;"><i class="fas fa-toggle-on text-success"></i> Status Promo</h3>
                        
                        <label class="toggle-switch m-0" style="padding: 0.75rem 1rem;">
                            <div>
                                <div class="toggle-label" style="font-size: 0.85rem;">Promo Aktif</div>
                                <div class="toggle-desc" style="font-size: 0.7rem;">Kupon bisa dipakai checkout</div>
                            </div>
                            <input type="checkbox" name="is_active" value="1" <?= $kupon['is_active'] == 1 ? 'checked' : '' ?> class="switch-input">
                            <div class="switch-slider"></div>
                        </label>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i> Update Kupon
                        </button>
                        <a href="index.php" class="btn-cancel">Batal Edit</a>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Interaksi pintar: Kolom Maksimal Potongan otomatis mati jika diskon = Nominal
    const tipeDiskon = document.getElementById('tipe_diskon');
    const maxPotongan = document.getElementById('max_potongan');

    function checkTipeDiskon() {
        if (tipeDiskon.value === 'persentase') {
            maxPotongan.disabled = false;
            maxPotongan.classList.remove('bg-light');
            maxPotongan.placeholder = "Max nominal Rp";
        } else {
            maxPotongan.disabled = true;
            maxPotongan.value = '';
            maxPotongan.classList.add('bg-light');
            maxPotongan.placeholder = "Rp 0";
        }
    }

    tipeDiskon.addEventListener('change', checkTipeDiskon);
    checkTipeDiskon(); // Jalankan saat pertama kali diload
    
    // Auto submit UX feedback
    document.getElementById('kuponForm').addEventListener('submit', function() {
        const btn = this.querySelector('.btn-submit');
        if(btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengupdate...';
            btn.style.opacity = '0.8';
            btn.style.pointerEvents = 'none';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>