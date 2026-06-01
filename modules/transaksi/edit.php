<?php
// ===============================
// LOGIC SECTION
// ===============================
$page_title = "Edit Transaksi";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Validasi ID
$transaksi_id = (int)get('id', 0);
if ($transaksi_id <= 0) {
    setMessage('ID transaksi tidak valid!', 'error');
    redirect('index.php');
}

$transaksi = getTransaksiById($transaksi_id);
if (!$transaksi) {
    setMessage('Transaksi tidak ditemukan!', 'error');
    redirect('index.php');
}

// Hanya bisa edit jika status pending
if ($transaksi['status'] !== 'pending') {
    setMessage('Hanya transaksi dengan status pending yang dapat diedit!', 'error');
    redirect('detail.php?id=' . $transaksi_id);
}

$detail_items = getDetailTransaksi($transaksi_id);
$all_products = getAllProduk(1, 1000);
$errors = [];

if (isPost()) {
    // Validate input
    $nama_pelanggan = clean(post('nama_pelanggan'));
    $nomor_wa = clean(post('nomor_wa'));
    $produk_items = post('produk_items', []);
    
    if (empty($nama_pelanggan)) {
        $errors[] = 'Nama pelanggan harus diisi';
    }
    
    if (empty($nomor_wa)) {
        $errors[] = 'Nomor WA harus diisi';
    }
    
    if (empty($produk_items) || !is_array($produk_items)) {
        $errors[] = 'Minimal harus ada satu produk';
    }
    
    if (empty($errors)) {
        // Calculate total and prepare items
        $total_harga = 0;
        $items = [];
        
        foreach ($produk_items as $produk_id) {
            if (is_numeric($produk_id)) {
                $produk = getProdukById($produk_id);
                if ($produk) {
                    $items[] = [
                        'produk_id' => $produk['id'],
                        'harga' => $produk['harga']
                    ];
                    $total_harga += $produk['harga'];
                }
            }
        }
        
        if (empty($items)) {
            $errors[] = 'Produk yang dipilih tidak valid';
        } else {
            $data = [
                'nama_pelanggan' => $nama_pelanggan,
                'nomor_wa' => $nomor_wa,
                'total_harga' => $total_harga,
                'status' => clean(post('status', 'pending')),
                'tanggal_transaksi' => !empty(post('tanggal_transaksi')) ? post('tanggal_transaksi') : $transaksi['tanggal_transaksi'],
                'items' => $items
            ];
            
            if (updateTransaksi($transaksi_id, $data)) {
                setMessage('Transaksi berhasil diupdate!', 'success');
                redirect('detail.php?id=' . $transaksi_id);
            } else {
                $errors[] = 'Gagal mengupdate transaksi. Silakan coba lagi.';
            }
        }
    }
} else {
    // Set default values from database
    $_POST['nama_pelanggan'] = $transaksi['nama_pelanggan'];
    $_POST['nomor_wa'] = $transaksi['nomor_wa'];
    $_POST['status'] = $transaksi['status'];
    $_POST['tanggal_transaksi'] = date('Y-m-d\TH:i', strtotime($transaksi['tanggal_transaksi']));
    $_POST['produk_items'] = array_column($detail_items, 'produk_id');
}

// ===============================
// PRESENTATION SECTION
// ===============================
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content dashboard-wrapper">
    <div class="dash-header mb-4 d-flex justify-content-between align-items-center">
        <div>
            <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Transaksi
            </a>
            <h1 class="dash-title mt-2">Edit Transaksi #<?= $transaksi['id'] ?></h1>
        </div>
        <div class="d-flex gap-2">
            <a href="detail.php?id=<?= $transaksi_id ?>" class="btn btn-dark fw-bold rounded-pill">
                <i class="fas fa-external-link-alt me-2"></i>Lihat Detail
            </a>
        </div>
    </div>

    <div class="w-100">
        <?php displaySessionMessage(); ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-editorial mb-4" style="border-left-color: var(--danger-color); background: #FEF2F2;">
                <ul class="mb-0 text-danger fw-bold" style="font-size: 0.9rem; list-style-type: none; padding-left: 0;">
                    <?php foreach ($errors as $error): ?>
                        <li><i class="fas fa-exclamation-circle me-2"></i><?= safeHtml($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row g-4">
            <div class="col-lg-8">
                <form method="POST" id="transaksiForm">
                    
                    <div class="panel-editorial mb-4">
                        <h3 class="panel-title"><i class="fas fa-user-edit text-primary"></i> Data Pelanggan</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama_pelanggan" class="form-control-editorial fw-bold text-dark" 
                                       value="<?= safeHtml(post('nama_pelanggan', $transaksi['nama_pelanggan'] ?? '')) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                                <div class="input-group-editorial">
                                    <span class="addon"><i class="fab fa-whatsapp text-success"></i></span>
                                    <input type="text" name="nomor_wa" class="form-control-editorial fw-bold text-dark" 
                                           value="<?= safeHtml(post('nomor_wa', $transaksi['nomor_wa'] ?? '')) ?>" required>
                                </div>
                                <div class="text-muted mt-2" style="font-size: 0.75rem;">Akan otomatis disesuaikan ke format 62.</div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-editorial mb-4">
                        <h3 class="panel-title"><i class="fas fa-box-open text-warning"></i> Keranjang Belanja <span class="text-danger">*</span></h3>
                        
                        <?php if (empty($all_products)): ?>
                            <div class="text-center py-4 bg-light rounded-4 border">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted fw-bold">Belum ada produk untuk dijual.</p>
                            </div>
                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                                <?php 
                                    // Ambil array ID produk yang terpilih
                                    $selected_items = post('produk_items', []);
                                    // Kalau ini load pertama (post kosong), isi pakai data asli transaksi
                                    if(empty($_POST) && !empty($detail_items)) {
                                        $selected_items = array_column($detail_items, 'produk_id');
                                    }
                                ?>
                                <?php foreach ($all_products as $produk): ?>
                                    <label class="product-selector-card" for="produk_<?= $produk['id'] ?>" style="cursor: pointer; display: block; position: relative;">
                                        <input class="form-check-input produk-checkbox position-absolute" type="checkbox" 
                                               name="produk_items[]" value="<?= $produk['id'] ?>" 
                                               id="produk_<?= $produk['id'] ?>" data-harga="<?= $produk['harga'] ?>"
                                               <?= in_array($produk['id'], $selected_items) ? 'checked' : '' ?>
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
                        <h3 class="panel-title"><i class="fas fa-cog text-info"></i> Pengaturan Sistem</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Status Transaksi</label>
                                <select name="status" class="form-control-editorial fw-bold" style="appearance: auto;">
                                    <?php $current_status = post('status', $transaksi['status'] ?? 'pending'); ?>
                                    <option value="pending" <?= $current_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="diproses" <?= $current_status === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                    <option value="selesai" <?= $current_status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Waktu Transaksi (Opsional)</label>
                                <input type="datetime-local" name="tanggal_transaksi" class="form-control-editorial text-muted" 
                                       value="<?= clean(post('tanggal_transaksi', date('Y-m-d\TH:i', strtotime($transaksi['tanggal_transaksi'])))) ?>">
                            </div>
                        </div>
                    </div>
                    
                </div>

                <div class="col-lg-4">
                    
                    <div class="panel-editorial p-4 mb-4" style="background: #FFFBEB; border: 1px dashed #F59E0B;">
                        <h3 class="panel-title text-dark" style="font-size: 1rem;"><i class="fas fa-exclamation-triangle text-warning"></i> Perhatian Khusus</h3>
                        <div class="text-muted" style="font-size: 0.8rem; line-height: 1.6;">
                            Secara sistem, <strong>Transaksi ini hanya dapat diedit sepenuhnya jika status masih "Pending".</strong><br><br>
                            Apabila status sudah berubah menjadi Diproses/Selesai/Batal, sistem akan mengunci data agar histori omzet tetap valid dan akurat.
                        </div>
                    </div>

                    <div class="panel-editorial sticky-top" style="top: 2rem; background: #F9FAFB; border: 1px dashed var(--border-light);">
                        <h3 class="panel-title" style="font-size: 1rem;"><i class="fas fa-receipt text-success"></i> Kalkulasi Ulang</h3>
                        
                        <div id="summary" class="mt-3">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-shopping-basket fa-2x mb-2 opacity-50"></i>
                                <div style="font-size: 0.85rem;">Pilih produk untuk melihat ringkasan.</div>
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
                                <i class="fas fa-save me-2"></i> Simpan Perubahan
                            </button>
                            <a href="detail.php?id=<?= $transaksi_id ?>" class="btn-cancel">Batal Edit</a>
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
    
    // Inisialisasi awal saat load (untuk mendeteksi data produk lama)
    updateSummary();

    // UX Feedback saat form disubmit
    document.getElementById('transaksiForm').addEventListener('submit', function() {
        const btn = document.querySelector('.btn-submit');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
        btn.style.opacity = '0.8';
        btn.style.pointerEvents = 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>