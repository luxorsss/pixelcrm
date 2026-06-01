<?php
$page_title = 'Edit Rekening';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
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

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<div class="main-content dashboard-wrapper flex-grow-1">
    <div class="form-container" style="max-width: 1100px;">
        
        <div class="dash-header mb-4 d-flex justify-content-between align-items-center">
            <div>
                <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Rekening
                </a>
                <h1 class="dash-title mt-2 d-flex align-items-center gap-2">
                    <i class="fas fa-edit text-primary"></i> Edit Rekening
                </h1>
            </div>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-editorial mb-4" style="border-left-color: var(--danger-color); background: #FEF2F2;">
                <ul class="mb-0 text-danger fw-bold" style="font-size: 0.9rem; list-style-type: none; padding-left: 0;">
                    <?php foreach ($errors as $error): ?>
                        <li><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="rekeningForm" enctype="multipart/form-data" class="row g-4">
            
            <div class="col-lg-7">
                
                <div class="panel-editorial mb-4">
                    <h3 class="panel-title"><i class="fas fa-id-card text-primary me-2"></i> Detail Kepemilikan</h3>
                    
                    <div class="mb-4">
                        <label class="form-label text-dark fw-bold" style="font-size: 0.85rem;">Nama Pemilik <span class="text-danger">*</span></label>
                        <input type="text" name="nama_pemilik" id="inputNama" class="form-control-editorial fw-bold text-dark text-uppercase" 
                               value="<?= htmlspecialchars($data['nama_pemilik']) ?>" required placeholder="Contoh: FADIL MUHAMMAD">
                        <div class="text-muted mt-2" style="font-size: 0.75rem;">Sesuai dengan nama yang tertera pada buku tabungan atau merchant QRIS.</div>
                    </div>

                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold" style="font-size: 0.85rem;">Bank / Metode <span class="text-danger">*</span></label>
                            <select name="nama_bank" id="bankSelect" class="form-control-editorial fw-bold text-primary" required style="appearance: auto; cursor: pointer;">
                                <option value="">-- Pilih Bank --</option>
                                <?php foreach ($bank_options as $bank): ?>
                                    <option value="<?= $bank ?>" <?= ($data['nama_bank'] === $bank || ($isCustomBank && $bank === 'Other')) ? 'selected' : '' ?>>
                                        <?= $bank ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="custom_bank" class="form-control-editorial mt-2 <?= $isCustomBank ? '' : 'd-none' ?> bg-light" 
                                   id="customBank" placeholder="Ketik nama bank..." 
                                   value="<?= $isCustomBank ? htmlspecialchars($data['nama_bank']) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark fw-bold" style="font-size: 0.85rem;">Nomor Rekening / NID <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_rekening" id="nomorRekening" class="form-control-editorial fw-bold text-dark" 
                                   value="<?= htmlspecialchars($data['nomor_rekening']) ?>" required placeholder="Contoh: 7099595684">
                        </div>
                    </div>
                </div>

                <div class="panel-editorial mb-4" id="qr_upload_section" style="display: none; border-left: 4px solid #10B981;">
                    <h3 class="panel-title d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-qrcode text-success me-2"></i> Ekstraksi Data QRIS</span>
                        <span id="qris_status" class="badge bg-light text-muted border" style="font-size: 0.7rem;">Mode Edit...</span>
                    </h3>
                    
                    <?php if (isQRIS($rekening) && !empty($rekening['qr_image'])): ?>
                    <div class="mb-4 p-3 bg-light rounded-3 border border-success" id="existing_qr_section">
                        <div class="d-flex align-items-center gap-3">
                            <?php 
                            // Cek apakah isinya nama file gambar atau raw payload
                            $is_file = preg_match('/\.(jpeg|jpg|gif|png)$/i', $rekening['qr_image']);
                            $qr_src = $is_file ? "../../" . $rekening['qr_image'] : "https://api.qrserver.com/v1/create-qr-code/?size=150x150&margin=5&data=" . urlencode(trim($rekening['qr_image']));
                            ?>
                            <img src="<?= $qr_src ?>" alt="Current QR" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid #10B981;">
                            <div>
                                <div class="text-success fw-bold" style="font-size: 0.85rem;"><i class="fas fa-check-circle me-1"></i> QRIS Tersimpan</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Upload file baru di bawah jika ingin mengganti QR saat ini.</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="p-4 rounded-3 text-center border mb-3" style="background: #F9FAFB; border-style: dashed !important; position: relative; cursor: pointer;" onclick="document.getElementById('qr_image').click()">
                        <i class="fas fa-cloud-upload-alt text-success fs-1 mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">Upload QRIS Baru (Opsional)</h6>
                        <p class="text-muted mb-0" style="font-size: 0.8rem;">Klik di sini untuk menimpa gambar QRIS lama. Sistem akan mengekstrak datanya secara otomatis.</p>
                        <input type="file" name="qr_image" id="qr_image" accept="image/*" style="display: none;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark fw-bold" style="font-size: 0.85rem;">Raw Payload QRIS</label>
                        <?php 
                        // LOGIKA CERDAS:
                        // Cek apakah isi dari qr_image adalah teks mentah (bukan nama file gambar)
                        $raw_payload_value = '';
                        if (!empty($rekening['qr_image']) && !preg_match('/\.(jpeg|jpg|gif|png)$/i', $rekening['qr_image'])) {
                            $raw_payload_value = $rekening['qr_image'];
                        }
                        
                        // Prioritaskan inputan user jika ada error validasi, jika tidak gunakan data dari database
                        $display_payload = $_POST['qris_payload'] ?? $raw_payload_value;
                        ?>
                        <textarea class="form-control-editorial" name="qris_payload" id="qris_payload" rows="3" 
                                  placeholder="Jika gambar gagal diekstrak, paste teks mentah QRIS (000201...) di sini secara manual."
                                  style="font-family: monospace; font-size: 0.8rem; line-height: 1.5; resize: vertical; background: #FFFBEB; border-color: #FDE68A; color: #92400E;"><?= htmlspecialchars($display_payload) ?></textarea>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold px-5 py-3 rounded-pill btn-submit" id="btnSubmit" style="box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);">
                        <i class="fas fa-save me-2"></i> Perbarui Rekening
                    </button>
                    <a href="index.php" class="btn btn-light border fw-bold px-4 py-3 rounded-pill">Batal</a>
                </div>

            </div>

            <div class="col-lg-5">
                <div class="panel-editorial sticky-top p-0 overflow-hidden bg-light" style="top: 2rem;">
                    
                    <div class="p-3 border-bottom bg-white text-center">
                        <h6 class="fw-bold m-0" style="font-size: 0.9rem;"><i class="fas fa-eye text-info me-2"></i> Visual Preview</h6>
                    </div>

                    <div class="p-4 d-flex justify-content-center align-items-center" style="min-height: 350px;">
                        
                        <div id="mockupBank" class="w-100" style="transition: all 0.3s;">
                            <div style="background: linear-gradient(135deg, #1E3A8A 0%, #111827 100%); border-radius: 16px; padding: 1.5rem; color: white; box-shadow: 0 10px 25px rgba(0,0,0,0.2); position: relative; overflow: hidden;">
                                <div style="position: absolute; right: -20px; top: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                                <div style="position: absolute; right: 20px; bottom: -30px; width: 80px; height: 80px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <i class="fas fa-university fs-3 opacity-75"></i>
                                    <h5 class="fw-bold m-0 fst-italic" id="mockupBankName" style="letter-spacing: 1px;">BANK NAME</h5>
                                </div>
                                
                                <i class="fas fa-sim-card fs-2 text-warning mb-3 opacity-75" style="transform: rotate(90deg);"></i>
                                
                                <h3 class="fw-bold mb-3" id="mockupNumber" style="font-family: 'Courier New', Courier, monospace; letter-spacing: 3px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">
                                    0000 0000 0000
                                </h3>
                                
                                <div class="text-uppercase fw-bold opacity-75" style="font-size: 0.7rem; letter-spacing: 1px;">Cardholder Name</div>
                                <div class="fw-bold fs-6 text-truncate text-uppercase" id="mockupName">NAMA PEMILIK</div>
                            </div>
                        </div>

                        <div id="mockupQRIS" class="w-100" style="display: none; transition: all 0.3s;">
                            <div class="bg-white mx-auto shadow-sm" style="border-radius: 12px; overflow: hidden; border: 1px solid #E5E7EB; max-width: 280px;">
                                <div class="text-center p-3" style="background: #DC2626;">
                                    <h4 class="fw-bold text-white m-0 fst-italic">QRIS</h4>
                                    <div class="text-white opacity-75" style="font-size: 0.65rem;">Quick Response Code Indonesian Standard</div>
                                </div>
                                <div class="p-3 text-center">
                                    <div class="fw-bold text-dark text-truncate text-uppercase mb-2" id="mockupQrisName" style="font-size: 0.9rem;">NAMA MERCHANT</div>
                                    <div class="bg-light p-2 rounded border mx-auto mb-2" style="width: 180px; height: 180px; display: flex; align-items: center; justify-content: center; position: relative;">
                                        <img id="preview_img" src="" style="max-width: 100%; max-height: 100%; display: none;">
                                        <i class="fas fa-qrcode text-muted opacity-25" id="dummy_qr_icon" style="font-size: 6rem;"></i>
                                    </div>
                                    <div class="text-muted fw-bold" style="font-size: 0.7rem;">NMID: <span id="mockupQrisNumber">0000000</span></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Elements UI Reference
    const inputNama = document.getElementById('inputNama');
    const bankSelect = document.getElementById('bankSelect');
    const customBank = document.getElementById('customBank');
    const inputNomor = document.getElementById('nomorRekening');
    
    // Elements Mockup Reference
    const mockupBank = document.getElementById('mockupBank');
    const mockupQRIS = document.getElementById('mockupQRIS');
    const previewImg = document.getElementById('preview_img');
    const dummyIcon = document.getElementById('dummy_qr_icon');
    
    // Jika ada gambar QRIS tersimpan, langsung muat ke Mockup saat load
    const savedQrData = `<?= (isQRIS($rekening) && !empty($rekening['qr_image'])) ? $rekening['qr_image'] : "" ?>`;
    let existingQrImage = '';

    if (savedQrData) {
        if (savedQrData.match(/\.(jpeg|jpg|gif|png)$/i)) {
            // Bersihkan path file jika berupa gambar
            let cleanPath = savedQrData.replace(/^(\.\.\/)+/, '').replace(/^(\.\/)+/, '').replace(/^\/+/, '');
            existingQrImage = '<?= BASE_URL ?>' + cleanPath + '?t=' + Date.now();
        } else {
            // Render jadi barcode jika berupa raw payload
            existingQrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&margin=15&data=' + encodeURIComponent(savedQrData.trim());
        }
    }

    // Fungsi Update Live Mockup
    function updateMockup() {
        const nama = inputNama.value.trim() || 'NAMA PEMILIK';
        const nomor = inputNomor.value.trim() || '0000 0000 0000';
        let bank = bankSelect.value;
        if(bank === 'Other') bank = customBank.value.trim() || 'BANK LAINNYA';
        
        const isQRIS = bank && (bank.toUpperCase() === 'QRIS' || bank.toLowerCase().includes('qris'));
        
        if (isQRIS) {
            mockupBank.style.display = 'none';
            mockupQRIS.style.display = 'block';
            
            document.getElementById('mockupQrisName').textContent = nama;
            document.getElementById('mockupQrisNumber').textContent = nomor;

            // Load existing QR image if no new file is selected
            if(existingQrImage && document.getElementById('qr_image').files.length === 0) {
                previewImg.src = existingQrImage;
                previewImg.style.display = 'block';
                dummyIcon.style.display = 'none';
            }
        } else {
            mockupBank.style.display = 'block';
            mockupQRIS.style.display = 'none';
            
            document.getElementById('mockupName').textContent = nama;
            document.getElementById('mockupNumber').textContent = nomor;
            document.getElementById('mockupBankName').textContent = bank || 'BANK NAME';
        }
    }

    // Trigger update pada setiap inputan
    inputNama.addEventListener('input', updateMockup);
    inputNomor.addEventListener('input', updateMockup);
    customBank.addEventListener('input', updateMockup);

    // Event saat Bank dipilih
    bankSelect.addEventListener('change', function() {
        const qrSection = document.getElementById('qr_upload_section');
        
        if (this.value === 'Other') {
            customBank.classList.remove('d-none');
            customBank.required = true;
        } else {
            customBank.classList.add('d-none');
            customBank.required = false;
        }
        
        const isQRIS = this.value && (this.value.toUpperCase() === 'QRIS' || this.value.toLowerCase().includes('qris'));
        qrSection.style.display = isQRIS ? 'block' : 'none';
        
        updateMockup(); // Render ulang kartu mockup
    });


    // EKSTRAKSI GAMBAR QRIS KE TEKS MENGGUNAKAN JSQR
    document.getElementById('qr_image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const qrisPayloadInput = document.getElementById('qris_payload');
        const statusText = document.getElementById('qris_status');
        const btnSubmit = document.getElementById('btnSubmit');
        
        if (!file) {
            // Restore existing image if upload is cancelled
            if (existingQrImage) {
                previewImg.src = existingQrImage;
                previewImg.style.display = 'block';
                dummyIcon.style.display = 'none';
            } else {
                previewImg.style.display = 'none';
                dummyIcon.style.display = 'block';
            }
            qrisPayloadInput.value = '';
            statusText.innerHTML = 'Mode Edit...';
            statusText.className = 'badge bg-light text-muted border';
            return;
        }

        statusText.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
        statusText.className = 'badge bg-warning text-dark';
        btnSubmit.disabled = true;

        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                // Render ke Canvas untuk dibaca JS
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.width = img.width;
                canvas.height = img.height;
                
                // FIX UNTUK GAMBAR DOWNLOADAN: 
                // Isi background dengan warna putih solid agar PNG transparan tidak menjadi hitam!
                context.fillStyle = "#FFFFFF";
                context.fillRect(0, 0, canvas.width, canvas.height);
                
                // Tempelkan gambar di atas background putih
                context.drawImage(img, 0, 0, canvas.width, canvas.height);
                
                const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                
                // Eksekusi library jsQR
                const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: "dontInvert" });
                
                // Setel gambar di Mockup QRIS (Sebelah kanan)
                previewImg.src = event.target.result;
                previewImg.style.display = 'block';
                dummyIcon.style.display = 'none';

                if (code && code.data) {
                    // Sukses Ekstrak
                    qrisPayloadInput.value = code.data;
                    qrisPayloadInput.style.borderColor = '#10B981';
                    qrisPayloadInput.style.backgroundColor = '#ECFDF5';
                    
                    statusText.innerHTML = '<i class="fas fa-check-circle me-1"></i> Data Terbaca';
                    statusText.className = 'badge bg-success text-white';
                    btnSubmit.disabled = false;
                } else {
                    // Gagal Ekstrak - Minta manual
                    statusText.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Gagal Membaca, Paste Manual!';
                    statusText.className = 'badge bg-danger text-white';
                    
                    qrisPayloadInput.style.borderColor = '#EF4444';
                    btnSubmit.disabled = false; 
                }
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });

    // Jalankan pengecekan pertama kali (Buka blok QRIS jika data sebelumnya QRIS)
    if (bankSelect.value === 'QRIS' || bankSelect.value.toLowerCase().includes('qris') || (bankSelect.value === 'Other' && customBank.value.toLowerCase().includes('qris'))) {
        document.getElementById('qr_upload_section').style.display = 'block';
    }
    updateMockup();

    // Feedback saat disubmit
    document.getElementById('rekeningForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmit');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
        btn.style.opacity = '0.8';
        btn.style.pointerEvents = 'none';
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>