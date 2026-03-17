<?php
// ===============================
// LOGIC SECTION
// ===============================
$page_title = "Tambah Pelanggan";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

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
        
        $pelanggan_id = createPelanggan($data);
        
        if ($pelanggan_id) {
            setMessage('Pelanggan berhasil ditambahkan!', 'success');
            redirect('index.php');
        } else {
            // Cek apakah nomor WA sudah terdaftar
            if (getPelangganByWA(normalizePhoneNumber($nomor_wa))) {
                $errors[] = 'Nomor WA sudah terdaftar di sistem';
            } else {
                $errors[] = 'Gagal menambahkan pelanggan. Silakan coba lagi.';
            }
        }
    }
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
                <h1 class="page-title mb-0">Tambah Pelanggan</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="index.php" class="breadcrumb-item text-decoration-none">Pelanggan</a>
                    <span class="breadcrumb-item active">Tambah</span>
                </nav>
            </div>
            <div class="d-flex gap-2">
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
            <!-- Form Tambah Pelanggan -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Form Tambah Pelanggan</h5>
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
                                    <i class="fas fa-save me-2"></i>Simpan Pelanggan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Info Panel -->
            <div class="col-lg-4">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi</h6>
                    </div>
                    <div class="card-body">
                        <h6>Tips Menambah Pelanggan:</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Pastikan nama pelanggan lengkap dan jelas
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Nomor WA akan otomatis diformat ke standar Indonesia (62xxx)
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Sistem akan mencegah duplikasi nomor WA
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Data pelanggan akan tersimpan dengan timestamp otomatis
                            </li>
                        </ul>
                        
                        <hr>
                        
                        <h6>Untuk Input Banyak Data:</h6>
                        <p class="text-muted mb-3">
                            Jika Anda memiliki banyak data pelanggan, 
                            gunakan fitur <strong>Bulk Import</strong> yang dapat 
                            memproses ratusan data sekaligus.
                        </p>
                        <a href="bulk.php" class="btn btn-outline-success w-100">
                            <i class="fas fa-upload me-2"></i>Bulk Import
                        </a>
                    </div>
                </div>
                
                <!-- Preview Nomor WA -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fab fa-whatsapp text-success me-2"></i>Preview Nomor WA</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">Nomor yang diinput:</p>
                        <div id="input-preview" class="badge bg-light text-dark">-</div>
                        
                        <p class="text-muted mb-2 mt-3">Akan disimpan sebagai:</p>
                        <div id="normalized-preview" class="badge bg-success">-</div>
                        
                        <p class="text-muted mb-0 mt-3">
                            <small>Nomor akan otomatis dikonversi ke format Indonesia (62xxx)</small>
                        </p>
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
        
        inputPreview.textContent = inputValue || '-';
        normalizedPreview.textContent = normalizedValue || '-';
        
        // Validate format
        const isValid = /^62[0-9]{8,11}$/.test(normalizedValue);
        normalizedPreview.className = isValid ? 'badge bg-success' : 'badge bg-danger';
    }
    
    nomorWaInput.addEventListener('input', updatePreview);
    
    // Initial preview update
    updatePreview();
    
    // Auto focus nama input
    document.getElementById('nama').focus();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>