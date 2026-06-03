<?php
// === LOGIC SECTION (No Output) ===
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$page_title = "Tambah Produk";
$onesender_accounts = getOneSenderAccounts();
$errors = [];

// Handle form submission
if (isPost()) {
    $data = [
        'nama' => clean(post('nama')),
        'deskripsi' => clean(post('deskripsi')),
        'harga' => post('harga'),
        'show_kupon' => post('show_kupon') ? 1 : 0,
        'show_email' => post('show_email') ? 1 : 0, // <--- PASTIKAN BARIS INI ADA
        'link_akses' => clean(post('link_akses')),
        'onesender_account' => clean(post('onesender_account')),
        'admin_wa' => clean(post('admin_wa')),
        'meta_pixel_id' => clean(post('meta_pixel_id')),
        'conversion_api_token' => clean(post('conversion_api_token')),
        'tracking_aktif' => post('tracking_aktif') ? 1 : 0,
        'http_post' => clean(post('http_post')),
		'profit' => (post('profit') === '' || post('profit') === null) 
                    ? (float) post('harga') 
                    : (float) post('profit')

    ];
    
    $errors = validateProdukData($data);
    
    if (empty($errors)) {
        if (createProduk($data)) {
            setMessage('Produk berhasil ditambahkan!', 'success');
            redirect('index.php');
        } else {
            $errors[] = 'Gagal menambahkan produk. Silakan coba lagi.';
        }
    }
}

// === PRESENTATION SECTION ===
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content dashboard-wrapper">
    <div class="form-container">
        
        <div class="dash-header mb-4">
            <div>
                <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Katalog
                </a>
                <h1 class="dash-title mt-2"><?= isset($produk) ? 'Edit Produk' : 'Tambah Produk Baru' ?></h1>
            </div>
        </div>

        <form action="" method="POST" enctype="multipart/form-data">
            <?php if(isset($produk)): ?>
                <input type="hidden" name="id" value="<?= $produk['id'] ?>">
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="panel-editorial">
                        <h3 class="panel-title"><i class="fas fa-box"></i> Informasi Dasar</h3>
                        
                        <div class="mb-4">
                            <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control-editorial" 
                                   placeholder="Contoh: E-Book Jago Ngoding" 
                                   value="<?= clean(post('nama')) ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Deskripsi Singkat</label>
                            <textarea name="deskripsi" class="form-control-editorial" 
                                      placeholder="Jelaskan produk ini secara singkat agar pembeli paham..."><?= post('deskripsi') ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                                <div class="input-group-editorial">
                                    <span class="addon">Rp</span>
                                    <input type="number" name="harga" class="form-control-editorial" 
                                           placeholder="150000" min="0" step="1"
                                           value="<?= post('harga') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Profit / Modal Bersih <span class="text-muted text-lowercase" style="font-size:0.7rem;">(Opsional)</span></label>
                                <div class="input-group-editorial">
                                    <span class="addon">Rp</span>
                                    <input type="number" name="profit" class="form-control-editorial" 
                                           placeholder="Contoh: 50000" min="0" step="1"
                                           value="<?= post('profit') ?>">
                                </div>
                                <div class="text-muted mt-2" style="font-size: 0.75rem;">
                                    <i class="fas fa-info-circle me-1"></i>Kosongkan jika profit 100%.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-editorial">
                        <h3 class="panel-title"><i class="fas fa-link"></i> Akses & Integrasi</h3>
                        
                        <div class="mb-4">
                            <label class="form-label">Link Akses Produk (Setelah Bayar)</label>
                            <input type="url" name="link_akses" class="form-control-editorial" 
                                   placeholder="https://drive.google.com/..." 
                                   value="<?= clean(post('link_akses')) ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">HTTP POST URL</label>
                            <input type="url" name="http_post" class="form-control-editorial" 
                                   placeholder="https://api.example.com/webhook" 
                                   value="<?= clean(post('http_post')) ?>">
                            <div class="text-muted mt-2" style="font-size: 0.75rem;">URL endpoint untuk HTTP POST notification.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor WA Admin (Notifikasi)</label>
                                <input type="text" name="admin_wa" class="form-control-editorial" 
                                       placeholder="62812..." 
                                       value="<?= clean(post('admin_wa')) ?>">
                                <div class="text-muted mt-2" style="font-size: 0.75rem;">Format: 628xxx (tanpa +).</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Akun OneSender (API)</label>
                                <select name="onesender_account" class="form-control-editorial" style="appearance: auto;">
                                    <?php foreach ($onesender_accounts as $account): ?>
                                        <option value="<?= $account['account_name'] ?>"
                                                <?= (post('onesender_account') === $account['account_name']) ? 'selected' : '' ?>>
                                            <?= clean($account['account_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="panel-editorial">
                        <h3 class="panel-title"><i class="fas fa-sliders-h"></i> Tampilan Checkout</h3>
                        
                        <label class="toggle-switch">
                            <div>
                                <div class="toggle-label">Kolom Email</div>
                                <div class="toggle-desc">Wajibkan isi email pembeli</div>
                            </div>
                            <input type="checkbox" name="show_email" value="1" class="switch-input" 
                                   <?= post('show_email') ? 'checked' : '' ?>>
                            <div class="switch-slider"></div>
                        </label>

                        <label class="toggle-switch">
                            <div>
                                <div class="toggle-label">Kolom Kupon</div>
                                <div class="toggle-desc">Tampilkan input kode diskon</div>
                            </div>
                            <input type="checkbox" name="show_kupon" value="1" class="switch-input"
                                   <?= post('show_kupon') ? 'checked' : '' ?>>
                            <div class="switch-slider"></div>
                        </label>
                    </div>

                    <div class="panel-editorial">
                        <h3 class="panel-title"><i class="fas fa-chart-line"></i> Meta Tracking</h3>
                        
                        <label class="toggle-switch mb-4" style="border-color: #BFDBFE; background: #EFF6FF;">
                            <div>
                                <div class="toggle-label text-primary">Aktifkan Tracking</div>
                                <div class="toggle-desc">Kirim event saat transaksi</div>
                            </div>
                            <input type="checkbox" name="tracking_aktif" value="1" class="switch-input" 
                                   <?= post('tracking_aktif') ? 'checked' : '' ?>>
                            <div class="switch-slider"></div>
                        </label>

                        <div class="mb-3">
                            <label class="form-label">Meta Pixel ID</label>
                            <input type="text" name="meta_pixel_id" class="form-control-editorial" 
                                   placeholder="1234567890" 
                                   value="<?= clean(post('meta_pixel_id')) ?>">
                        </div>
                        
                        <div>
                            <label class="form-label">Conversion API Token</label>
                            <textarea name="conversion_api_token" class="form-control-editorial" style="min-height: 80px;" 
                                      placeholder="EAAI..."><?= clean(post('conversion_api_token')) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2 mt-4">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i> <?= isset($produk) ? 'Update Produk' : 'Simpan Produk' ?>
                        </button>
                        <a href="index.php" class="btn-cancel flex-grow-1 order-2 order-sm-1">Batal</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>