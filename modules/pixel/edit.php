<?php
$page_title = 'Edit Master Pixel';
require_once '../../includes/header.php';
include '../../includes/sidebar.php';
require_once 'functions.php';

$id = (int) get('id');
$pixel = getPixelById($id);

if (!$pixel) {
    setMessage('Data pixel tidak ditemukan', 'error');
    redirect('index.php');
}

$errors = [];
$data = [
    'nama' => $pixel['nama'],
    'meta_pixel_id' => $pixel['meta_pixel_id'],
    'conversion_api_token' => $pixel['conversion_api_token'],
    'test_event_code' => $pixel['test_event_code'],
    'is_active' => (int)$pixel['is_active']
];

$linked_products = getProductsUsingPixel($id);

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
        if (updatePixel($id, $data)) {
            setMessage('Perubahan pixel berhasil disimpan!', 'success');
            redirect('index.php');
        } else {
            $errors[] = 'Gagal memperbarui pixel';
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
                <i class="fas fa-edit text-primary"></i> Edit Master Pixel
            </h1>
            <div class="text-muted" style="font-size: 0.9rem;">
                Perbarui konfigurasi Pixel ID dan Conversion API Token.
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
                           placeholder="Contoh: Pixel Iklan Utama" 
                           value="<?= htmlspecialchars($data['nama']) ?>" required>
                    <div class="form-text text-muted">Nama bebas untuk membedakan akun pixel.</div>
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
                              placeholder="EAA..."><?= htmlspecialchars($data['conversion_api_token']) ?></textarea>
                    <div class="form-text text-muted">Token CAPI dari Meta Events Manager.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Test Event Code <span class="text-muted fw-normal">(Opsional untuk Testing)</span></label>
                    <input type="text" name="test_event_code" class="form-control-editorial font-monospace" 
                           placeholder="Contoh: TEST12345" 
                           value="<?= htmlspecialchars($data['test_event_code']) ?>">
                    <div class="form-text text-muted">Kosongkan jika iklan sedang berjalan normal.</div>
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

            <!-- Panel Produk Terhubung -->
            <div class="panel-editorial mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="panel-title m-0">
                        <i class="fas fa-boxes text-primary me-2"></i> Produk yang Menggunakan Pixel Ini
                    </h3>
                    <span class="badge bg-light text-dark border px-3 py-1 rounded-pill">
                        <?= count($linked_products) ?> Produk Terhubung
                    </span>
                </div>

                <?php if (empty($linked_products)): ?>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">
                        Belum ada produk yang memilih pixel ini. Anda dapat memilihnya saat membuat atau mengedit produk di katalog.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr class="text-muted" style="font-size: 0.8rem;">
                                    <th>Nama Produk</th>
                                    <th>Harga</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($linked_products as $lp): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($lp['nama']) ?></td>
                                        <td class="text-muted">Rp <?= number_format($lp['harga'], 0, ',', '.') ?></td>
                                        <td class="text-end">
                                            <a href="../produk/edit.php?id=<?= $lp['id'] ?>" class="btn btn-sm btn-light border rounded-pill px-3" target="_blank">
                                                <i class="fas fa-external-link-alt me-1"></i> Edit Produk
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                    <i class="fas fa-save me-2"></i> Simpan Perubahan
                </button>
                <a href="index.php" class="btn btn-light rounded-pill px-4">Batal</a>
            </div>
        </form>

    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
