<?php
$page_title = 'Edit Rekening';
require_once '../../includes/header.php';
require_once 'functions.php';

$id = (int)get('id');
$rekening = getRekeningById($id);

if (!$rekening) {
    setMessage('Rekening tidak ditemukan', 'error');
    redirect('index.php');
}

$errors = [];
$data = $rekening;

if (isPost()) {
    $data = [
        'id' => $id,
        'nama_pemilik' => post('nama_pemilik'),
        'nomor_rekening' => post('nomor_rekening'),
        'nama_bank' => post('nama_bank') === 'Other' ? post('custom_bank') : post('nama_bank')
    ];
    
    $errors = validateRekening($data);
    
    $qr_image_path = null;
    
    // Handle QRIS image upload
    if (isQRISByBank($data['nama_bank']) && isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleQRISImageUpload($_FILES['qr_image'], $id);
        if ($upload_result['success']) {
            // Delete old image if exists
            if (!empty($rekening['qr_image'])) {
                deleteQRISImage($rekening['qr_image']);
            }
            $qr_image_path = $upload_result['path'];
        } else {
            $errors[] = $upload_result['error'];
        }
    }
    
    if (!$errors) {
        if (updateRekening($id, $data, $qr_image_path)) {
            setMessage('Rekening berhasil diperbarui', 'success');
            redirect('index.php');
        } else {
            $errors[] = 'Gagal memperbarui rekening';
        }
    }
}

// Bank options untuk dropdown
$bank_options = [
    'BCA', 'Mandiri', 'BRI', 'BNI', 'BTN', 'CIMB Niaga', 
    'Danamon', 'Permata', 'Maybank', 'OCBC NISP', 'BJB',
    'BSI', 'Bank Mega', 'Panin', 'BTPN', 'Muamalat',
    'QRIS', 'Other'
];

// Check if current bank is in the list
$isCustomBank = !in_array($data['nama_bank'], $bank_options);
?>

<div class="d-flex">
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content flex-grow-1">
        <div class="content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-edit me-2"></i>Edit Rekening</h2>
                    <p class="text-muted mb-0">Perbarui informasi rekening</p>
                </div>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Form Rekening</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($errors): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Nama Pemilik *</label>
                                    <input type="text" name="nama_pemilik" class="form-control" 
                                           value="<?= htmlspecialchars($data['nama_pemilik']) ?>" 
                                           placeholder="Nama pemilik rekening" required>
                                    <div class="form-text">Nama sesuai yang terdaftar di bank</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Bank/Metode Pembayaran *</label>
                                    <select name="nama_bank" class="form-select" required id="bankSelect">
                                        <option value="">Pilih Bank/Metode</option>
                                        <?php foreach ($bank_options as $bank): ?>
                                            <option value="<?= $bank ?>" 
                                                    <?= ($data['nama_bank'] === $bank || ($isCustomBank && $bank === 'Other')) ? 'selected' : '' ?>>
                                                <?= $bank ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="custom_bank" class="form-control mt-2 <?= $isCustomBank ? '' : 'd-none' ?>" 
                                           id="customBank" placeholder="Masukkan nama bank lain"
                                           value="<?= $isCustomBank ? htmlspecialchars($data['nama_bank']) : '' ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nomor Rekening/Kode *</label>
                                    <input type="text" name="nomor_rekening" class="form-control" 
                                           value="<?= htmlspecialchars($data['nomor_rekening']) ?>" 
                                           placeholder="Nomor rekening atau kode QRIS" required
                                           id="nomorRekening">
                                    <div class="form-text" id="rekeningHelp">
                                        Minimal 8 karakter untuk rekening bank
                                    </div>
                                </div>

                                <!-- Upload QRIS Image (hidden by default) -->
                                <div class="mb-3" id="qr_upload_section" style="display: none;">
                                    <label for="qr_image" class="form-label">
                                        <i class="fas fa-qrcode me-2"></i>Upload Gambar QRIS Baru
                                    </label>
                                    <input type="file" class="form-control" name="qr_image" id="qr_image" accept="image/*">
                                    <div class="form-text">
                                        <small>
                                            ✅ Format: JPG, PNG | Ukuran maksimal: 2MB<br>
                                            💡 Screenshot QR Code dari aplikasi merchant/bank
                                        </small>
                                    </div>
                                    
                                    <!-- Preview -->
                                    <div id="image_preview" class="mt-3" style="display: none;">
                                        <img id="preview_img" src="" alt="Preview" style="max-width: 200px; border: 2px solid #e91e63; border-radius: 8px;">
                                    </div>
                                </div>

                                <!-- Existing QRIS Image (for edit mode) -->
                                <?php if (isQRIS($rekening) && !empty($rekening['qr_image'])): ?>
                                <div class="mb-3" id="existing_qr_section">
                                    <label class="form-label">
                                        <i class="fas fa-image me-2"></i>Gambar QRIS Saat Ini
                                    </label>
                                    <div class="p-3" style="background: #e8f5e8; border: 1px solid #28a745; border-radius: 8px;">
                                        <img src="../../<?= $rekening['qr_image'] ?>" alt="Current QR" style="max-width: 200px; border: 2px solid #28a745; border-radius: 8px;">
                                        <br><small class="text-muted mt-2 d-block">File: <?= $rekening['qr_image'] ?></small>
                                        <small class="text-info">💡 Upload gambar baru untuk mengganti</small>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Perbarui
                                    </button>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Batal
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Current Info -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-info-circle"></i> Info Saat Ini</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <?php if (isQRIS($rekening)): ?>
                                    <div class="badge bg-success mb-2">
                                        <i class="fas fa-qrcode"></i> QRIS
                                    </div>
                                    <div><strong><?= htmlspecialchars($rekening['nama_pemilik']) ?></strong></div>
                                    <div class="small text-muted">Kode: <?= htmlspecialchars($rekening['nomor_rekening']) ?></div>
                                    <?php if (!empty($rekening['qr_image'])): ?>
                                    <div class="small text-success mt-2">
                                        <i class="fas fa-check-circle"></i> Gambar QR tersedia
                                    </div>
                                    <?php else: ?>
                                    <div class="small text-warning mt-2">
                                        <i class="fas fa-exclamation-triangle"></i> Belum ada gambar QR
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="badge bg-primary mb-2">
                                        <i class="fas fa-university"></i> Bank
                                    </div>
                                    <div><strong><?= htmlspecialchars($rekening['nama_bank']) ?></strong></div>
                                    <div><code><?= htmlspecialchars($rekening['nomor_rekening']) ?></code></div>
                                    <div class="small text-muted">a.n <?= htmlspecialchars($rekening['nama_pemilik']) ?></div>
                                <?php endif; ?>
                            </div>
                            <hr>
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> 
                                Dibuat: <?= formatDate($rekening['created_at'], 'd/m/Y H:i') ?>
                            </small>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-eye"></i> Preview Perubahan</h6>
                        </div>
                        <div class="card-body">
                            <div id="preview">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Live preview - UPDATE function yang sudah ada
function updatePreview() {
    const pemilik = document.querySelector('[name="nama_pemilik"]').value;
    const bank = document.getElementById('bankSelect').value;
    const customBank = document.getElementById('customBank').value;
    const nomor = document.querySelector('[name="nomor_rekening"]').value;
    
    const finalBank = bank === 'Other' ? customBank : bank;
    
    const isQRIS = finalBank && (finalBank.toUpperCase() === 'QRIS' || finalBank.toLowerCase().includes('qris'));
    const maskedNumber = nomor.length > 8 ? 
        nomor.substring(0, 4) + '****' + nomor.substring(nomor.length - 4) : nomor;
    
    let preview = '';
    if (isQRIS) {
        const hasNewImage = document.getElementById('qr_image').files.length > 0;
        preview = `
            <div class="text-center">
                <div class="badge bg-success mb-2">
                    <i class="fas fa-qrcode"></i> QRIS
                </div>
                <div><strong>${pemilik || '[Nama Merchant]'}</strong></div>
                <div class="small text-muted">ID: ${nomor || '[Merchant ID]'}</div>
                <div class="small ${hasNewImage ? 'text-info' : 'text-success'} mt-2">
                    <i class="fas fa-image"></i> ${hasNewImage ? 'Gambar baru dipilih' : 'Upload gambar QR'}
                </div>
            </div>`;
    } else {
        preview = `
            <div class="text-center">
                <div class="badge bg-primary mb-2">
                    <i class="fas fa-university"></i> Bank
                </div>
                <div><strong>${finalBank || '[Bank]'}</strong></div>
                <div><code>${maskedNumber || '[Nomor Rekening]'}</code></div>
                <div class="small text-muted">a.n ${pemilik || '[Nama Pemilik]'}</div>
            </div>`;
    }
    
    document.getElementById('preview').innerHTML = preview;
}

// Handle custom bank input - UPDATE function yang sudah ada
document.getElementById('bankSelect').addEventListener('change', function() {
    const customInput = document.getElementById('customBank');
    const qrSection = document.getElementById('qr_upload_section');
    const existingSection = document.getElementById('existing_qr_section');
    
    if (this.value === 'Other') {
        customInput.classList.remove('d-none');
        customInput.required = true;
        if (!customInput.value) customInput.focus();
    } else {
        customInput.classList.add('d-none');
        customInput.required = false;
        if (this.value !== 'Other') customInput.value = '';
    }
    
    // Show/hide QRIS upload section
    const isQRIS = this.value && (this.value.toUpperCase() === 'QRIS' || this.value.toLowerCase().includes('qris'));
    if (isQRIS) {
        qrSection.style.display = 'block';
        if (existingSection) existingSection.style.display = 'block';
    } else {
        qrSection.style.display = 'none';
        if (existingSection) existingSection.style.display = 'none';
    }
    
    updatePreview();
});

// Update help text based on bank selection - UPDATE function yang sudah ada
document.getElementById('bankSelect').addEventListener('change', function() {
    const helpText = document.getElementById('rekeningHelp');
    const customBank = document.getElementById('customBank').value;
    const finalBank = this.value === 'Other' ? customBank : this.value;
    const isQRIS = finalBank && (finalBank.toUpperCase() === 'QRIS' || finalBank.toLowerCase().includes('qris'));
    
    if (isQRIS) {
        helpText.innerHTML = 'Masukkan Merchant ID QRIS<br><small class="text-success">💡 Upload gambar QR baru jika diperlukan</small>';
    } else {
        helpText.textContent = 'Minimal 8 karakter untuk rekening bank';
    }
});

// Live preview update
document.querySelectorAll('input, select').forEach(el => {
    el.addEventListener('input', updatePreview);
    el.addEventListener('change', updatePreview);
});

// Handle custom bank input untuk preview dan QRIS detection
document.getElementById('customBank').addEventListener('input', function() {
    const qrSection = document.getElementById('qr_upload_section');
    const existingSection = document.getElementById('existing_qr_section');
    const isQRIS = this.value && (this.value.toUpperCase() === 'QRIS' || this.value.toLowerCase().includes('qris'));
    
    if (document.getElementById('bankSelect').value === 'Other') {
        if (isQRIS) {
            qrSection.style.display = 'block';
            if (existingSection) existingSection.style.display = 'block';
        } else {
            qrSection.style.display = 'none';
            if (existingSection) existingSection.style.display = 'none';
        }
        
        // Update help text
        const helpText = document.getElementById('rekeningHelp');
        if (isQRIS) {
            helpText.innerHTML = 'Masukkan Merchant ID QRIS<br><small class="text-success">💡 Upload gambar QR baru jika diperlukan</small>';
        } else {
            helpText.textContent = 'Minimal 8 karakter untuk rekening bank';
        }
    }
    
    updatePreview();
});

// Preview uploaded image
document.getElementById('qr_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('image_preview');
    const previewImg = document.getElementById('preview_img');
    
    if (file) {
        // Validate file
        if (!file.type.startsWith('image/')) {
            alert('File harus berupa gambar!');
            this.value = '';
            return;
        }
        
        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran file maksimal 2MB!');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
    
    updatePreview(); // Update preview text
});

// Initial check for QRIS on page load
document.addEventListener('DOMContentLoaded', function() {
    const bankSelect = document.getElementById('bankSelect');
    const customBank = document.getElementById('customBank');
    const qrSection = document.getElementById('qr_upload_section');
    const existingSection = document.getElementById('existing_qr_section');
    
    const finalBank = bankSelect.value === 'Other' ? customBank.value : bankSelect.value;
    const isQRIS = finalBank && (finalBank.toUpperCase() === 'QRIS' || finalBank.toLowerCase().includes('qris'));
    
    if (isQRIS) {
        qrSection.style.display = 'block';
        if (existingSection) existingSection.style.display = 'block';
    } else {
        qrSection.style.display = 'none';
        if (existingSection) existingSection.style.display = 'none';
    }
    
    updatePreview();
});
</script>

<?php require_once '../../includes/footer.php'; ?>