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
<div class="main-content">
    <!-- Top Header -->
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Edit Transaksi #<?= $transaksi['id'] ?></h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="index.php" class="breadcrumb-item text-decoration-none">Transaksi</a>
                    <span class="breadcrumb-item active">Edit</span>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="detail.php?id=<?= $transaksi_id ?>" class="btn btn-info">
                    <i class="fas fa-eye me-2"></i>Detail
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Form Edit -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Form Edit Transaksi</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <!-- Customer Information -->
                            <div class="card border-primary mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Informasi Pelanggan</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="nama_pelanggan" class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="nama_pelanggan" name="nama_pelanggan" 
                                                       value="<?= safeHtml(post('nama_pelanggan', '')) ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="nomor_wa" class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="fab fa-whatsapp text-success"></i>
                                                    </span>
                                                    <input type="text" class="form-control" id="nomor_wa" name="nomor_wa" 
                                                           value="<?= safeHtml(post('nomor_wa', '')) ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Product Selection -->
                            <div class="card border-success mb-4">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Pilih Produk</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row" id="productSelection">
                                        <?php foreach ($all_products as $produk): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card product-card">
                                                    <div class="card-body">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="produk_items[]" 
                                                                   value="<?= $produk['id'] ?>" 
                                                                   id="produk_<?= $produk['id'] ?>"
                                                                   data-harga="<?= $produk['harga'] ?>"
                                                                   data-nama="<?= safeHtml($produk['nama']) ?>"
                                                                   <?= (is_array(post('produk_items', [])) && in_array($produk['id'], post('produk_items', []))) ? 'checked' : '' ?>>
                                                            <label class="form-check-label w-100" for="produk_<?= $produk['id'] ?>">
                                                                <div class="d-flex justify-content-between align-items-start">
                                                                    <div>
                                                                        <strong><?= safeHtml($produk['nama']) ?></strong>
                                                                        <?php if ($produk['deskripsi']): ?>
                                                                            <br><small class="text-muted"><?= safeHtml(truncateText($produk['deskripsi'], 50)) ?></small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="text-end">
                                                                        <div class="h6 text-primary mb-0"><?= formatCurrency($produk['harga']) ?></div>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Transaction Details -->
                            <div class="card border-info mb-4">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Detail Transaksi</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="tanggal_transaksi" class="form-label">Tanggal Transaksi</label>
                                                <input type="datetime-local" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" 
                                                       value="<?= post('tanggal_transaksi', '') ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status Transaksi</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="pending" <?= post('status') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="diproses" <?= post('status') === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                                    <option value="selesai" <?= post('status') === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="detail.php?id=<?= $transaksi_id ?>" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Transaksi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Summary Panel -->
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Ringkasan Pesanan</h6>
                    </div>
                    <div class="card-body">
                        <div id="orderSummary">
                            <p class="text-muted">Loading...</p>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Total:</strong>
                            <strong class="h5 text-primary" id="totalAmount">Rp 0</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Info -->
                <div class="card mt-3 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Perhatian</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">Transaksi hanya dapat diedit jika status masih <strong>pending</strong>.</p>
                        <p class="mb-0">Setelah status berubah ke diproses/selesai/batal, transaksi tidak dapat diedit lagi.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    updateOrderSummary();
    
    // Update summary when products are selected/deselected
    document.querySelectorAll('input[name="produk_items[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateOrderSummary);
    });
});

function updateOrderSummary() {
    const selectedProducts = document.querySelectorAll('input[name="produk_items[]"]:checked');
    const summaryDiv = document.getElementById('orderSummary');
    const totalDiv = document.getElementById('totalAmount');
    
    if (selectedProducts.length === 0) {
        summaryDiv.innerHTML = '<p class="text-muted">Pilih produk untuk melihat ringkasan...</p>';
        totalDiv.textContent = 'Rp 0';
        return;
    }
    
    let html = '<div class="list-group list-group-flush">';
    let total = 0;
    
    selectedProducts.forEach(checkbox => {
        const harga = parseInt(checkbox.dataset.harga);
        const nama = checkbox.dataset.nama;
        total += harga;
        
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                <span>${nama}</span>
                <strong>${formatCurrency(harga)}</strong>
            </div>
        `;
    });
    
    html += '</div>';
    summaryDiv.innerHTML = html;
    totalDiv.textContent = formatCurrency(total);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}
</script>

<style>
.product-card {
    transition: all 0.2s;
    border: 2px solid transparent;
    cursor: pointer;
}

.product-card:hover {
    border-color: #007bff;
    transform: translateY(-2px);
}

.product-card:has(input:checked) {
    border-color: #28a745;
    background-color: #f8fff9;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>