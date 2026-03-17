<?php
// ===============================
// LOGIC SECTION
// ===============================
$page_title = "Edit Pelanggan";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Validasi ID
$pelanggan_id = (int)get('id', 0);
if ($pelanggan_id <= 0) {
    setMessage('ID pelanggan tidak valid!', 'error');
    redirect('index.php');
}

$pelanggan = getPelangganById($pelanggan_id);
if (!$pelanggan) {
    setMessage('Pelanggan tidak ditemukan!', 'error');
    redirect('index.php');
}

$stats = getStatistikPelanggan($pelanggan_id);
$errors = [];

if (isPost()) {
    // Validate input
    $nama = clean(post('nama'));
    $nomor_wa = clean(post('nomor_wa'));
    
    if (empty($nama)) {
        $errors[] = 'Nama pelanggan harus diisi';
    }
    
    if (empty($nomor_wa)) {
        $errors[] = 'Nomor WA harus diisi';
    }
    
    if (empty($errors)) {
        $data = [
            'nama' => $nama,
            'nomor_wa' => $nomor_wa
        ];
        
        if (updatePelanggan($pelanggan_id, $data)) {
            setMessage('Data pelanggan berhasil diupdate!', 'success');
            redirect('index.php');
        } else {
            // Cek apakah nomor WA sudah digunakan pelanggan lain
            $existing = getPelangganByWA(normalizePhoneNumber($nomor_wa));
            if ($existing && $existing['id'] != $pelanggan_id) {
                $errors[] = 'Nomor WA sudah digunakan oleh pelanggan lain';
            } else {
                $errors[] = 'Gagal mengupdate data pelanggan. Silakan coba lagi.';
            }
        }
    }
} else {
    // Set default values from database
    $_POST = $pelanggan;
}

// ===============================
// PRESENTATION SECTION
// ===============================
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Header -->
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Edit Pelanggan</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="index.php" class="breadcrumb-item text-decoration-none">Pelanggan</a>
                    <span class="breadcrumb-item active">Edit</span>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="histori.php?id=<?= $pelanggan_id ?>" class="btn btn-info">
                    <i class="fas fa-history me-2"></i>Histori
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Form Edit -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Form Edit Pelanggan</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama" name="nama" 
                                       value="<?= safeHtml(post('nama', '')) ?>" 
                                       required maxlength="100">
                                <div class="invalid-feedback">Nama pelanggan harus diisi.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nomor_wa" class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fab fa-whatsapp text-success"></i>
                                    </span>
                                    <input type="text" class="form-control" id="nomor_wa" name="nomor_wa" 
                                           value="<?= safeHtml(post('nomor_wa', '')) ?>" 
                                           placeholder="628xxxxxxxxxx" required>
                                </div>
                                <small class="form-text text-muted">
                                    Format: 628xxxxxxxxxx (akan otomatis dinormalisasi)
                                </small>
                                <div class="invalid-feedback">Nomor WA harus diisi dengan format yang valid.</div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="index.php" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Pelanggan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Info Panel -->
            <div class="col-lg-4">
                <!-- Statistik Pelanggan -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistik Pelanggan</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="h5 text-primary"><?= $stats['total_transaksi'] ?></div>
                                <small class="text-muted">Transaksi</small>
                            </div>
                            <div class="col-8">
                                <div class="h5 text-success"><?= formatCurrency($stats['total_pembelian']) ?></div>
                                <small class="text-muted">Total Pembelian</small>
                            </div>
                        </div>
                        
                        <?php if ($stats['transaksi_terakhir']): ?>
                            <hr>
                            <p class="text-muted mb-1">Transaksi Terakhir:</p>
                            <p class="fw-bold"><?= formatDate($stats['transaksi_terakhir'], 'd/m/Y H:i') ?></p>
                        <?php endif; ?>
                        
                        <hr>
                        <p class="text-muted mb-1">Terdaftar:</p>
                        <p class="fw-bold"><?= formatDate($pelanggan['tanggal_daftar'], 'd/m/Y H:i') ?></p>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-tools me-2"></i>Aksi Lainnya</h6>
                    </div>
                    <div class="card-body">
                        <a href="histori.php?id=<?= $pelanggan_id ?>" class="btn btn-info w-100 mb-2">
                            <i class="fas fa-history me-2"></i>Lihat Histori Pembelian
                        </a>
                        
                        <a href="<?= whatsappLink($pelanggan['nomor_wa']) ?>" 
                           target="_blank" class="btn btn-success w-100 mb-2">
                            <i class="fab fa-whatsapp me-2"></i>Hubungi via WhatsApp
                        </a>
                        
                        <?php if ($stats['total_transaksi'] == 0): ?>
                            <a href="delete.php?id=<?= $pelanggan_id ?>" 
                               class="btn btn-outline-danger w-100"
                               onclick="return confirm('Hapus pelanggan <?= safeHtml($pelanggan['nama']) ?>?')">
                                <i class="fas fa-trash me-2"></i>Hapus Pelanggan
                            </a>
                        <?php else: ?>
                            <button class="btn btn-outline-secondary w-100" disabled title="Tidak dapat dihapus karena memiliki riwayat transaksi">
                                <i class="fas fa-lock me-2"></i>Tidak Dapat Dihapus
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nomorWaInput = document.getElementById('nomor_wa');
    const inputPreview = document.getElementById('input-preview');
    const normalizedPreview = document.getElementById('normalized-preview');
    
    function normalizePhoneNumber(phone) {
        // Remove all non-numeric characters
        phone = phone.replace(/[^0-9]/g, '');
        
        // Convert to 62 format
        if (phone.startsWith('0')) {
            phone = '62' + phone.substring(1);
        } else if (!phone.startsWith('62')) {
            phone = '62' + phone;
        }
        
        return phone;
    }
    
    function updatePreview() {
        const inputValue = nomorWaInput.value;
        const normalizedValue = normalizePhoneNumber(inputValue);
        
        if (inputPreview) inputPreview.textContent = inputValue || '-';
        if (normalizedPreview) normalizedPreview.textContent = normalizedValue || '-';
        
        // Validate format
        if (normalizedPreview) {
            const isValid = /^62[0-9]{8,11}$/.test(normalizedValue);
            normalizedPreview.className = isValid ? 'badge bg-success' : 'badge bg-danger';
        }
    }
    
    if (nomorWaInput) {
        nomorWaInput.addEventListener('input', updatePreview);
        // Initial preview update
        updatePreview();
    }
    
    // Auto focus nama input
    document.getElementById('nama').focus();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>