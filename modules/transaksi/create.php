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

<div class="main-content">
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Tambah Transaksi</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="<?= BASE_URL ?>modules/transaksi/" class="breadcrumb-item text-decoration-none">Transaksi</a>
                    <span class="breadcrumb-item active">Tambah</span>
                </nav>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
        </div>
    </div>

    <div class="content-area">
        <?php displaySessionMessage(); ?>
        
        <div class="row">
            <div class="col-lg-8">
                <form method="POST" id="transaksiForm">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Data Pelanggan</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Pelanggan *</label>
                                        <input type="text" name="nama_pelanggan" class="form-control" 
                                               value="<?= clean(post('nama_pelanggan', $default_nama)) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nomor WhatsApp *</label>
                                        <input type="text" name="nomor_wa" class="form-control" 
                                               placeholder="08xxxxxxxxxx" value="<?= clean(post('nomor_wa', $default_wa)) ?>" required>
                                        <small class="text-muted">Format: 08xxxxxxxxxx</small>
                                    </div>
                                </div>
								<?php if (isset($_GET['pelanggan_id'])): ?>
									<input type="hidden" name="pelanggan_id" value="<?= clean($_GET['pelanggan_id']) ?>">
								<?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-box me-2"></i>Pilih Produk</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($produk_list)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Belum ada produk tersedia</p>
                                    <a href="<?= BASE_URL ?>modules/produk/create.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Tambah Produk
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($produk_list as $produk): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input produk-checkbox" type="checkbox" 
                                                           name="produk_id[]" value="<?= $produk['id'] ?>" 
                                                           id="produk_<?= $produk['id'] ?>"
                                                           data-harga="<?= $produk['harga'] ?>"
                                                           <?= in_array($produk['id'], post('produk_id', [])) ? 'checked' : '' ?>>
                                                    <label class="form-check-label w-100" for="produk_<?= $produk['id'] ?>">
                                                        <div>
                                                            <strong><?= safeHtml($produk['nama']) ?></strong>
                                                            <div class="text-success fw-bold"><?= formatCurrency($produk['harga']) ?></div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Pengaturan Transaksi</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="pending" <?= post('status') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="diproses" <?= post('status') === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                            <option value="selesai" <?= post('status') === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal Transaksi</label>
                                        <input type="datetime-local" name="tanggal_transaksi" class="form-control" 
                                               value="<?= clean(post('tanggal_transaksi')) ?: date('Y-m-d\TH:i') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan Transaksi
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Batal
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Ringkasan</h5>
                    </div>
                    <div class="card-body">
                        <div id="summary">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>Pilih produk untuk melihat ringkasan</p>
                            </div>
                        </div>
                        
                        <div id="total-section" class="d-none">
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Total:</strong>
                                <h4 class="text-success mb-0" id="total-harga">Rp 0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <p>Pilih produk untuk melihat ringkasan</p>
                </div>
            `;
            totalSection.classList.add('d-none');
            return;
        }
        
        let total = 0;
        let html = '<h6>Produk Dipilih:</h6><div class="list-group list-group-flush">';
        
        selected.forEach(checkbox => {
            const harga = parseInt(checkbox.dataset.harga);
            const label = checkbox.parentElement.querySelector('label strong').textContent;
            total += harga;
            
            html += `
                <div class="list-group-item px-0 py-2">
                    <div class="d-flex justify-content-between">
                        <span>${label}</span>
                        <strong class="text-success">${formatCurrency(harga)}</strong>
                    </div>
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
    
    // Initialize summary
    updateSummary();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>