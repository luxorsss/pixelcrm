<?php
$page_title = 'Tambah Rekening';
require_once '../../includes/header.php';
require_once 'functions.php';

$errors = [];
$data = [
    'nama_pemilik' => '',
    'nomor_rekening' => '',
    'nama_bank' => ''
];

if (isPost()) {
    $data = [
        'nama_pemilik' => post('nama_pemilik'),
        'nomor_rekening' => post('nomor_rekening'),
        'nama_bank' => post('nama_bank') === 'Other' ? post('custom_bank') : post('nama_bank'),
        'qris_payload' => post('qris_payload') // Mengambil string rahasia dari JS
    ];
    
    $errors = validateRekening($data);
    
    if (isQRISByBank($data['nama_bank']) && empty($data['qris_payload'])) {
        $errors[] = 'Gagal membaca gambar QRIS. Pastikan gambar jelas dan tidak buram.';
    }
    
    if (!$errors) {
        if (createRekening($data)) {
            setMessage('Rekening berhasil ditambahkan', 'success');
            redirect('index.php');
        } else {
            $errors[] = 'Gagal menyimpan rekening';
        }
    }
}

$bank_options = [
    'BCA', 'Mandiri', 'BRI', 'BNI', 'BTN', 'CIMB Niaga', 
    'Danamon', 'Permata', 'Maybank', 'OCBC NISP', 'BJB',
    'BSI', 'Bank Mega', 'Panin', 'BTPN', 'Muamalat',
    'QRIS', 'Other'
];
?>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<div class="d-flex">
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content flex-grow-1">
        <div class="content-area">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-plus me-2"></i>Tambah Rekening</h2>
                    <p class="text-muted mb-0">Tambahkan rekening bank atau QRIS baru</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
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

                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Nama Pemilik *</label>
                                    <input type="text" name="nama_pemilik" class="form-control" 
                                           value="<?= htmlspecialchars($data['nama_pemilik']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Bank/Metode *</label>
                                    <select name="nama_bank" class="form-select" required id="bankSelect">
                                        <option value="">Pilih Bank/Metode</option>
                                        <?php foreach ($bank_options as $bank): ?>
                                            <option value="<?= $bank ?>" <?= $data['nama_bank'] === $bank ? 'selected' : '' ?>>
                                                <?= $bank ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="custom_bank" class="form-control mt-2 d-none" id="customBank" placeholder="Nama bank lain">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nomor Rekening/Merchant ID *</label>
                                    <input type="text" name="nomor_rekening" class="form-control" 
                                           value="<?= htmlspecialchars($data['nomor_rekening']) ?>" required id="nomorRekening">
                                </div>

                                <div class="mb-3" id="qr_upload_section" style="display: none;">
                                    <label for="qr_image" class="form-label">
                                        <i class="fas fa-qrcode me-2"></i>Data QRIS (Upload atau Paste Manual)
                                    </label>
                                    
                                    <input type="file" class="form-control mb-2" id="qr_image" accept="image/*">
                                    <div class="form-text mb-3">
                                        <small id="qris_status">💡 Upload gambar QRIS, sistem akan mencoba membaca otomatis.</small>
                                    </div>
                                    
                                    <textarea class="form-control font-monospace" name="qris_payload" id="qris_payload" rows="4" placeholder="Atau paste teks QRIS (000201...) di sini jika scan gambar gagal..."></textarea>
                                    <div class="form-text">
                                        <small class="text-muted">Jika upload gagal, scan gambar kamu di <b>zxing.org</b> lalu paste teks <i>Raw text</i>-nya ke kotak di atas.</small>
                                    </div>
                                    
                                    <div id="image_preview" class="mt-3" style="display: none;">
                                        <img id="preview_img" src="" alt="Preview" style="max-width: 200px; border: 2px solid #e91e63; border-radius: 8px;">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary" id="btnSubmit">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi deteksi UI Bank Option & Preview biarkan sama seperti aslimu...
// Saya akan fokus pada bagian ekstraksi JS-nya:

document.getElementById('bankSelect').addEventListener('change', function() {
    const customInput = document.getElementById('customBank');
    const qrSection = document.getElementById('qr_upload_section');
    
    if (this.value === 'Other') {
        customInput.classList.remove('d-none');
        customInput.required = true;
    } else {
        customInput.classList.add('d-none');
        customInput.required = false;
    }
    
    const isQRIS = this.value && (this.value.toUpperCase() === 'QRIS' || this.value.toLowerCase().includes('qris'));
    qrSection.style.display = isQRIS ? 'block' : 'none';
});

// EKSTRAKSI GAMBAR QRIS KE TEKS MENGGUNAKAN JSQR
document.getElementById('qr_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('image_preview');
    const previewImg = document.getElementById('preview_img');
    const qrisPayloadInput = document.getElementById('qris_payload');
    const statusText = document.getElementById('qris_status');
    const btnSubmit = document.getElementById('btnSubmit');
    
    if (!file) {
        preview.style.display = 'none';
        qrisPayloadInput.value = '';
        return;
    }

    statusText.innerHTML = '<span class="text-warning">⏳ Sedang memproses gambar...</span>';
    btnSubmit.disabled = true;

    const reader = new FileReader();
    reader.onload = function(event) {
        const img = new Image();
        img.onload = function() {
            // Gambar harus dirender ke Canvas untuk dibaca JS
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.width = img.width;
            canvas.height = img.height;
            context.drawImage(img, 0, 0, canvas.width, canvas.height);
            
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            // Ekstrak QR Code
            const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: "dontInvert" });
            
            if (code && code.data) {
                // Berhasil Diekstrak!
                qrisPayloadInput.value = code.data;
                previewImg.src = event.target.result;
                preview.style.display = 'block';
                statusText.innerHTML = '<span class="text-success fw-bold">✅ Sukses! Data QRIS berhasil dibaca.</span>';
                btnSubmit.disabled = false;
            } else {
                // Gagal membaca QR - Biarkan user paste manual
                previewImg.src = event.target.result;
                preview.style.display = 'block';
                statusText.innerHTML = '<span class="text-danger fw-bold">❌ Gagal membaca gambar otomatis. Silakan paste teks QRIS secara manual di kotak bawah.</span>';
                btnSubmit.disabled = false; // Tetap aktifkan tombol agar bisa save manual
            }
        };
        img.src = event.target.result;
    };
    reader.readAsDataURL(file);
});
</script>

<?php require_once '../../includes/footer.php'; ?>