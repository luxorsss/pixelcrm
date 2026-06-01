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
<div class="main-content dashboard-wrapper">
    <div class="form-container">
        
        <div class="dash-header mb-4">
            <div>
                <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                    <i class="fas fa-arrow-left me-1"></i> Database Pelanggan
                </a>
                <h1 class="dash-title mt-2">Tambah Pelanggan Baru</h1>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-editorial mb-4" style="border-left-color: #EF4444;">
                <ul class="mb-0 text-danger fw-bold" style="font-size: 0.9rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="panel-editorial">
                    <h3 class="panel-title"><i class="fas fa-user-plus"></i> Form Data Pelanggan</h3>
                    
                    <form method="POST" novalidate>
                        <div class="mb-4">
                            <label for="nama" class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control-editorial" id="nama" name="nama" 
                                   placeholder="Contoh: Budi Santoso"
                                   value="<?= safeHtml(post('nama', '')) ?>" required maxlength="100">
                        </div>
                        
                        <div class="mb-4">
                            <label for="nomor_wa" class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                            <div class="input-group-editorial">
                                <span class="addon"><i class="fab fa-whatsapp text-success"></i></span>
                                <input type="text" class="form-control-editorial" id="nomor_wa" name="nomor_wa" 
                                       value="<?= safeHtml(post('nomor_wa', '')) ?>" 
                                       placeholder="628xxxxxxxxxx" required autocomplete="off">
                            </div>
                        </div>
                        
                        <div class="d-flex flex-column flex-sm-row gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn-submit flex-grow-1 order-1 order-sm-2">
                                <i class="fas fa-save me-2"></i> Simpan Pelanggan
                            </button>
                            <a href="index.php" class="btn-cancel flex-grow-1 order-2 order-sm-1">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="panel-editorial" style="background: #F9FAFB; border: 1px dashed #D1D5DB;">
                    <h3 class="panel-title" style="font-size: 1rem;"><i class="fab fa-whatsapp text-success"></i> Preview Format WA</h3>
                    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-3 border mb-2">
                        <span class="text-muted fw-bold" style="font-size: 0.75rem; text-transform: uppercase;">Akan Disimpan:</span>
                        <div id="normalized-preview" class="badge-clean bg-light text-muted border">-</div>
                    </div>
                    <div class="text-muted" style="font-size: 0.75rem; line-height: 1.5;">
                        <i class="fas fa-magic text-warning me-1"></i> Sistem akan otomatis menyesuaikan angka nol di depan menjadi kode negara <strong>62</strong>.
                    </div>
                </div>

                <div class="panel-editorial">
                    <h3 class="panel-title" style="font-size: 1rem;"><i class="fas fa-lightbulb text-warning"></i> Tips Cepat</h3>
                    <ul class="list-unstyled mb-4" style="font-size: 0.85rem; color: #4B5563;">
                        <li class="mb-3 d-flex gap-2">
                            <i class="fas fa-check-circle text-success mt-1"></i>
                            <span>Pastikan nama pelanggan lengkap untuk personalisasi <em>follow-up</em>.</span>
                        </li>
                        <li class="mb-3 d-flex gap-2">
                            <i class="fas fa-check-circle text-success mt-1"></i>
                            <span>Sistem akan mencegah duplikasi nomor WA secara otomatis.</span>
                        </li>
                    </ul>
                    
                    <hr style="border-color: #E5E7EB;">
                    
                    <div class="mt-3">
                        <p class="text-muted fw-bold mb-2" style="font-size: 0.8rem; text-transform: uppercase;">Punya Banyak Data?</p>
                        <a href="bulk.php" class="btn btn-light w-100 fw-bold border" style="border-radius: 12px; color: #10B981;">
                            <i class="fas fa-upload me-2"></i> Gunakan Bulk Import
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nomorWaInput = document.getElementById('nomor_wa');
    const normalizedPreview = document.getElementById('normalized-preview');
    
    function normalizePhoneNumber(phone) {
        phone = phone.replace(/[^0-9]/g, '');
        if (phone.startsWith('0')) {
            phone = '62' + phone.substring(1);
        } else if (phone !== '' && !phone.startsWith('62')) {
            phone = '62' + phone;
        }
        return phone;
    }
    
    function updatePreview() {
        const inputValue = nomorWaInput.value;
        const normalizedValue = normalizePhoneNumber(inputValue);
        
        normalizedPreview.textContent = normalizedValue || '-';
        
        if(normalizedValue === '') {
            normalizedPreview.className = 'badge-clean bg-light text-muted border';
        } else {
            const isValid = /^62[0-9]{8,12}$/.test(normalizedValue);
            if(isValid) {
                normalizedPreview.className = 'badge-clean bg-success text-white';
                normalizedPreview.innerHTML = '<i class="fas fa-check-circle"></i> ' + normalizedValue;
            } else {
                normalizedPreview.className = 'badge-clean bg-danger text-white';
                normalizedPreview.innerHTML = '<i class="fas fa-times-circle"></i> Tidak Valid';
            }
        }
    }
    
    if(nomorWaInput) {
        nomorWaInput.addEventListener('input', updatePreview);
        updatePreview();
    }
    
    const namaInput = document.getElementById('nama');
    if(namaInput && !namaInput.value) namaInput.focus();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>