<?php
$page_title = 'Tambah Pixel Baru';
require_once '../../includes/header.php';
include '../../includes/sidebar.php';
require_once 'functions.php';

$errors = [];
$data = [
    'nama' => '',
    'meta_pixel_id' => '',
    'conversion_api_token' => '',
    'test_event_code' => '',
    'is_active' => 1
];

if (isPost()) {
    $data = [
        'nama' => trim(post('nama')),
        'meta_pixel_id' => trim(post('meta_pixel_id')),
        'conversion_api_token' => trim(post('conversion_api_token')),
        'test_event_code' => trim(post('test_event_code')),
        'is_active' => post('is_active') ? 1 : 0
    ];

    if (empty($data['nama'])) {
        $errors[] = 'Nama pixel wajib diisi';
    }
    if (empty($data['meta_pixel_id'])) {
        $errors[] = 'Meta Pixel ID wajib diisi';
    } elseif (!preg_match('/^[0-9]+$/', $data['meta_pixel_id'])) {
        $errors[] = 'Meta Pixel ID harus berupa deretan angka';
    }

    if (empty($errors)) {
        $new_id = createPixel($data);
        if ($new_id) {
            setMessage('Pixel baru berhasil ditambahkan!', 'success');
            redirect('index.php');
        } else {
            $errors[] = 'Gagal menyimpan pixel ke database';
        }
    }
}
?>

<div class="main-content dashboard-wrapper flex-grow-1">
    <div class="form-container" style="max-width: 900px;">
        
        <div class="dash-header mb-4">
            <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Pixel
            </a>
            <h1 class="dash-title mt-2 d-flex align-items-center gap-2">
                <i class="fas fa-plus-circle text-primary"></i> Tambah Master Pixel
            </h1>
            <div class="text-muted" style="font-size: 0.9rem;">
                Simpan konfigurasi Meta Pixel & Conversion API untuk dihubungkan ke katalog produk.
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-3 mb-4">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="panel-editorial mb-4">
                <h3 class="panel-title mb-3"><i class="fas fa-cog text-primary me-2"></i> Konfigurasi Pixel</h3>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nama Identitas Pixel <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control-editorial" 
                           placeholder="Contoh: Pixel Iklan Utama, Pixel Produk Herbal" 
                           value="<?= htmlspecialchars($data['nama']) ?>" required>
                    <div class="form-text text-muted">Nama bebas untuk membedakan akun pixel di dropdown produk.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Meta Pixel ID <span class="text-danger">*</span></label>
                    <input type="text" name="meta_pixel_id" class="form-control-editorial font-monospace" 
                           placeholder="Contoh: 300555229246452" 
                           value="<?= htmlspecialchars($data['meta_pixel_id']) ?>" required>
                    <div class="form-text text-muted">Dataset / Pixel ID numerik dari Meta Events Manager.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Conversion API (CAPI) Access Token</label>
                    <textarea name="conversion_api_token" class="form-control-editorial font-monospace" 
                              style="min-height: 100px; font-size: 0.85rem;" 
                              placeholder="EAA... (Dihasilkan dari Meta Events Manager -> Settings -> Generate Access Token)"><?= htmlspecialchars($data['conversion_api_token']) ?></textarea>
                    <div class="form-text text-muted">Token CAPI untuk pengiriman konversi akurat sisi server (bypass ad blocker & iOS 14.5+).</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Test Event Code <span class="text-muted fw-normal">(Opsional untuk Testing)</span></label>
                    <input type="text" name="test_event_code" class="form-control-editorial font-monospace" 
                           placeholder="Contoh: TEST12345" 
                           value="<?= htmlspecialchars($data['test_event_code']) ?>">
                    <div class="form-text text-muted">Isi jika sedang melakukan live testing di tab "Test Events" Meta Events Manager. Kosongkan saat live campaign.</div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <label class="toggle-switch">
                        <div>
                            <div class="toggle-label">Status Aktif</div>
                            <div class="toggle-desc">Aktifkan agar pixel ini dapat dipilih di form produk</div>
                        </div>
                        <input type="checkbox" name="is_active" value="1" class="switch-input" <?= $data['is_active'] ? 'checked' : '' ?>>
                        <div class="switch-slider"></div>
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                    <i class="fas fa-save me-2"></i> Simpan Pixel
                </button>
                <a href="index.php" class="btn btn-light rounded-pill px-4">Batal</a>
            </div>
        </form>

    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
