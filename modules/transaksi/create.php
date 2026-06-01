<?php
$page_title = "Tambah Transaksi";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Get all products for selection
$produk_list = fetchAll("SELECT id, nama, harga FROM produk ORDER BY nama");

// Inisialisasi variabel default kosong
$default_nama = '';
$default_wa = '';

// Cek apakah ada parameter pelanggan_id di URL
if (isset($_GET['pelanggan_id'])) {
    $p_id = $_GET['pelanggan_id'];
    
    // Ambil data dari tabel pelanggan
    $pelanggan = fetchRow("SELECT nama, nomor_wa FROM pelanggan WHERE id = ?", [$p_id]);
    
    if ($pelanggan) {
        $default_nama = $pelanggan['nama'];
        $default_wa = $pelanggan['nomor_wa'];
    }
}

if (isPost()) {
    $data = [
        'nama_pelanggan' => clean(post('nama_pelanggan')),
        'nomor_wa' => clean(post('nomor_wa')),
		'pelanggan_id' => !empty(post('pelanggan_id')) ? post('pelanggan_id') : null,
        'status' => clean(post('status', 'pending')),
        'tanggal_transaksi' => clean(post('tanggal_transaksi')) ?: date('Y-m-d H:i:s'),
        'items' => []
    ];
    
    // Validasi
    $errors = [];
    
    if (empty($data['nama_pelanggan'])) {
        $errors[] = 'Nama pelanggan harus diisi';
    }
    
    if (empty($data['nomor_wa'])) {
        $errors[] = 'Nomor WhatsApp harus diisi';
    } elseif (!validatePhone($data['nomor_wa'])) {
        $errors[] = 'Format nomor WhatsApp tidak valid';
    }
    
    // Validasi produk yang dipilih
    $selected_products = post('produk_id', []);
    if (empty($selected_products)) {
        $errors[] = 'Pilih minimal satu produk';
    } else {
        $total_harga = 0;
        foreach ($selected_products as $produk_id) {
            $produk = fetchRow("SELECT * FROM produk WHERE id = ?", [$produk_id]);
            if ($produk) {
                $data['items'][] = [
                    'produk_id' => $produk['id'],
                    'harga' => $produk['harga']
                ];
                $total_harga += $produk['harga'];
            }
        }
        $data['total_harga'] = $total_harga;
    }
    
    if (empty($errors)) {
        $transaksi_id = createTransaksi($data);
        if ($transaksi_id) {
            setMessage('Transaksi berhasil dibuat', 'success');
            redirect("detail.php?id=$transaksi_id");
        } else {
            setMessage('Gagal membuat transaksi', 'error');
        }
    } else {
        setMessage(implode('<br>', $errors), 'error');
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content dashboard-wrapper">
    <div class="dash-header mb-4">
        <div>
            <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Transaksi
            </a>
            <h1 class="dash-title mt-2">Kasir Manual</h1>
        </div>
    </div>

    <div class="w-100">
        <?php displaySessionMessage(); ?>
        
        <div class="row g-4">
            <div class="col-lg-8">
                <form method="POST" id="transaksiForm">
                    
                    <div class="panel-editorial mb-4">
                        <h3 class="panel-title"><i class="fas fa-user-circle text-primary"></i> Data Pelanggan</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama_pelanggan" class="form-control-editorial fw-bold text-dark" 
                                       placeholder="Contoh: Budi Santoso" value="<?= clean(post('nama_pelanggan', $default_nama)) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                                <div class="input-group-editorial">
                                    <span class="addon"><i class="fab fa-whatsapp text-success"></i></span>
                                    <input type="text" name="nomor_wa" class="form-control-editorial fw-bold text-dark" 
                                           placeholder="0812xxxxxxx" value="<?= clean(post('nomor_wa', $default_wa)) ?>" required>
                                </div>
                                <div class="text-muted mt-2" style="font-size: 0.75rem;">Akan otomatis disesuaikan ke format 62.</div>
                            </div>
                            <?php if (isset($_GET['pelanggan_id'])): ?>
                                <input type="hidden" name="pelanggan_id" value="<?= clean($_GET['pelanggan_id']) ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="panel-editorial mb-4">
                        <h3 class="panel-title"><i class="fas fa-box-open text-warning"></i> Keranjang Belanja <span class="text-danger">*</span></h3>
                        
                        <?php if (empty($produk_list)): ?>
                            <div class="text-center py-4 bg-light rounded-4 border">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted fw-bold">Belum ada produk untuk dijual.</p>
                                <a href="<?= BASE_URL ?>modules/produk/create.php" class="btn btn-dark rounded-pill">
                                    <i class="fas fa-plus me-2"></i>Tambah Produk
                                </a>
                            </div>
                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                                <?php foreach ($produk_list as $produk): ?>
                                    <label class="product-selector-card" for="produk_<?= $produk['id'] ?>" style="cursor: pointer; display: block; position: relative;">
                                        <input class="form-check-input produk-checkbox position-absolute" type="checkbox" 
                                               name="produk_id[]" value="<?= $produk['id'] ?>" 
                                               id="produk_<?= $produk['id'] ?>" data-harga="<?= $produk['harga'] ?>"
                                               <?= in_array($produk['id'], post('produk_id', [])) ? 'checked' : '' ?>
                                               style="opacity: 0; width: 0; height: 0;">
                                        
                                        <div class="p-3 border rounded-3 transition-all" style="background: #F9FAFB; border-color: #E5E7EB; min-height: 80px; display: flex; align-items: center;">
                                            <div class="me-3 check-indicator" style="width: 24px; height: 24px; border-radius: 6px; border: 2px solid #D1D5DB; display: flex; align-items: center; justify-content: center; background: white; transition: all 0.2s;">
                                                <i class="fas fa-check text-white" style="font-size: 0.7rem; opacity: 0; transform: scale(0.5); transition: all 0.2s;"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.95rem; line-height: 1.2;"><?= safeHtml($produk['nama']) ?></div>
                                                <div class="text-success fw-bold mt-1" style="font-size: 0.9rem;"><?= formatCurrency($produk['harga']) ?></div>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="panel-editorial mb-4 mb-lg-0">
                        <h3 class="panel-title"><i class="fas fa-cog text-info"></i> Pengaturan Order</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Status Awal</label>
                                <select name="status" class="form-control-editorial fw-bold" style="appearance: auto;">
                                    <option value="selesai" <?= post('status') === 'selesai' ? 'selected' : '' ?>>Selesai (Lunas)</option>
                                    <option value="diproses" <?= post('status') === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                    <option value="pending" <?= post('status') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Waktu Transaksi</label>
                                <input type="datetime-local" name="tanggal_transaksi" class="form-control-editorial text-muted" 
                                       value="<?= clean(post('tanggal_transaksi')) ?: date('Y-m-d\TH:i') ?>">
                            </div>
                        </div>
                    </div>
                    
                </div>

                <div class="col-lg-4">
                    <div class="panel-editorial sticky-top" style="top: 2rem; background: #F9FAFB; border: 1px dashed var(--border-light);">
                        <h3 class="panel-title" style="font-size: 1rem;"><i class="fas fa-receipt text-success"></i> Ringkasan Order</h3>
                        
                        <div id="summary" class="mt-3">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-shopping-basket fa-2x mb-2 opacity-50"></i>
                                <div style="font-size: 0.85rem;">Pilih produk di samping untuk memulai.</div>
                            </div>
                        </div>
                        
                        <div id="total-section" class="d-none mt-3">
                            <div class="d-flex justify-content-between align-items-center pt-3 mt-3" style="border-top: 2px dashed #D1D5DB;">
                                <span class="text-muted fw-bold" style="font-size: 0.85rem; text-transform: uppercase;">Total Tagihan</span>
                                <div class="text-success fw-bold" id="total-harga" style="font-size: 1.5rem; letter-spacing: -0.02em;">Rp 0</div>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-2 mt-4">
                            <button type="button" class="btn-submit" onclick="document.getElementById('transaksiForm').submit()">
                                <i class="fas fa-check-circle me-2"></i> Proses Pembayaran
                            </button>
                            <a href="index.php" class="btn-cancel">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* Interaksi UI untuk Product Card Selector */
.product-selector-card input:checked ~ div {
    background: #ECFDF5 !important;
    border-color: #34D399 !important;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
}
.product-selector-card input:checked ~ div .check-indicator {
    background: #10B981 !important;
    border-color: #10B981 !important;
}
.product-selector-card input:checked ~ div .check-indicator i {
    opacity: 1 !important;
    transform: scale(1) !important;
}
.product-selector-card:hover div {
    border-color: #9CA3AF;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.produk-checkbox');
    const summary = document.getElementById('summary');
    const totalSection = document.getElementById('total-section');
    const totalHarga = document.getElementById('total-harga');
    
    function updateSummary() {
        const selected = Array.from(checkboxes).filter(cb => cb.checked);
        
        if (selected.length === 0) {
            summary.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-shopping-basket fa-2x mb-2 opacity-50"></i>
                    <div style="font-size: 0.85rem;">Pilih produk di samping untuk memulai.</div>
                </div>
            `;
            totalSection.classList.add('d-none');
            return;
        }
        
        let total = 0;
        let html = '<div class="d-flex flex-column gap-2">';
        
        selected.forEach(checkbox => {
            const harga = parseInt(checkbox.dataset.harga);
            // Ambil text nama produk dari div parent
            const labelNode = checkbox.parentElement.querySelector('div.fw-bold.text-dark');
            const label = labelNode ? labelNode.textContent : 'Produk';
            total += harga;
            
            html += `
                <div class="d-flex justify-content-between align-items-start pb-2" style="border-bottom: 1px solid #E5E7EB;">
                    <div class="pe-2 text-dark" style="font-size: 0.85rem; line-height: 1.4; font-weight: 500;">${label}</div>
                    <div class="text-success fw-bold" style="font-size: 0.85rem; white-space: nowrap;">${formatCurrency(harga)}</div>
                </div>
            `;
        });
        
        html += '</div>';
        summary.innerHTML = html;
        totalHarga.textContent = formatCurrency(total);
        totalSection.classList.remove('d-none');
    }
    
    function formatCurrency(amount) {
        return 'Rp ' + amount.toLocaleString('id-ID');
    }
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSummary);
    });
    
    // Inisialisasi awal jika ada error dan form retain value
    updateSummary();

    // UX Feedback saat form disubmit
    document.getElementById('transaksiForm').addEventListener('submit', function() {
        const btn = document.querySelector('.btn-submit');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
        btn.style.opacity = '0.8';
        btn.style.pointerEvents = 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>