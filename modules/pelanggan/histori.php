<?php
// ===============================
// LOGIC SECTION
// ===============================
$page_title = "Histori Pembelian";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$id = (int)get('id', 0);

if ($id <= 0) {
    setMessage('ID pelanggan tidak valid!', 'error');
    redirect('index.php');
}

// Ambil data pelanggan
$pelanggan = getPelangganById($id);
if (!$pelanggan) {
    setMessage('Pelanggan tidak ditemukan!', 'error');
    redirect('index.php');
}

// Ambil histori dan statistik
$histori = getHistoriPembelian($id);
$stats = getStatistikPelanggan($id);

// Filter status
$status_filter = clean(get('status', ''));
if (!empty($status_filter) && in_array($status_filter, ['pending', 'diproses', 'selesai', 'batal'])) {
    $histori = array_filter($histori, function($item) use ($status_filter) {
        return $item['status'] === $status_filter;
    });
}

// Pagination untuk histori
$current_page = max(1, (int)get('page', 1));
$total_records = count($histori);
$total_pages = ceil($total_records / RECORDS_PER_PAGE);

// Slice array untuk pagination
$start = ($current_page - 1) * RECORDS_PER_PAGE;
$histori_paginated = array_slice($histori, $start, RECORDS_PER_PAGE);

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
                <h1 class="page-title mb-0">Histori Pembelian</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <a href="index.php" class="breadcrumb-item text-decoration-none">Pelanggan</a>
                    <span class="breadcrumb-item active">Histori</span>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
                <a href="edit.php?id=<?= $pelanggan['id'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-2"></i>Edit Pelanggan
                </a>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <?php displaySessionMessage(); ?>
        
        <!-- Info Pelanggan -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-user-circle me-2 text-primary"></i>
                                    <?= safeHtml($pelanggan['nama']) ?>
                                </h5>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td width="120"><strong>ID Pelanggan:</strong></td>
                                        <td><span class="badge bg-light text-dark"><?= $pelanggan['id'] ?></span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Nomor WA:</strong></td>
                                        <td>
                                            <a href="<?= whatsappLink($pelanggan['nomor_wa']) ?>" 
                                               target="_blank" class="text-success text-decoration-none">
                                                <i class="fab fa-whatsapp me-1"></i>
                                                <?= $pelanggan['nomor_wa'] ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tgl Daftar:</strong></td>
                                        <td><?= formatDate($pelanggan['tanggal_daftar'], 'd/m/Y H:i') ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Quick Actions</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="<?= whatsappLink($pelanggan['nomor_wa']) ?>" 
                                       target="_blank" class="btn btn-success btn-sm">
                                        <i class="fab fa-whatsapp me-1"></i>Chat WA
                                    </a>
                                    <a href="<?= BASE_URL ?>modules/transaksi/create.php?pelanggan_id=<?= $pelanggan['id'] ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i>Transaksi Baru
                                    </a>
                                    <a href="edit.php?id=<?= $pelanggan['id'] ?>" 
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit me-1"></i>Edit Data
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="card text-center dashboard-card">
                            <div class="card-body">
                                <div class="h4 mb-1 text-primary"><?= $stats['total_transaksi'] ?></div>
                                <small class="text-muted">Total Transaksi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card text-center dashboard-card">
                            <div class="card-body">
                                <div class="h6 mb-1 text-success">
                                    <?= formatCurrency($stats['total_pembelian']) ?>
                                </div>
                                <small class="text-muted">Total Pembelian</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($stats['transaksi_terakhir']): ?>
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Transaksi Terakhir</h6>
                        <div class="text-info">
                            <?= formatDate($stats['transaksi_terakhir']) ?>
                        </div>
                        <small class="text-muted">
                            (<?= floor((time() - strtotime($stats['transaksi_terakhir'])) / 86400) ?> hari lalu)
                        </small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Filter dan Histori -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Histori Transaksi
                            <span class="badge bg-primary ms-2"><?= count($histori) ?></span>
                        </h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex gap-2">
                            <input type="hidden" name="id" value="<?= $pelanggan['id'] ?>">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="diproses" <?= $status_filter === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option value="batal" <?= $status_filter === 'batal' ? 'selected' : '' ?>>Batal</option>
                            </select>
                            <?php if (!empty($status_filter)): ?>
                                <a href="histori.php?id=<?= $pelanggan['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($histori)): ?>
                    <div class="text-center py-5">
                        <?php if (!empty($status_filter)): ?>
                            <i class="fas fa-filter fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada transaksi dengan status "<?= ucfirst($status_filter) ?>"</h5>
                            <p class="text-muted">Coba pilih status lain atau lihat semua transaksi.</p>
                            <a href="histori.php?id=<?= $pelanggan['id'] ?>" class="btn btn-outline-primary">
                                Lihat Semua Transaksi
                            </a>
                        <?php else: ?>
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada transaksi</h5>
                            <p class="text-muted">Pelanggan ini belum melakukan transaksi apapun.</p>
                            <a href="<?= BASE_URL ?>modules/transaksi/create.php?pelanggan_id=<?= $pelanggan['id'] ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Buat Transaksi Pertama
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="80">ID</th>
                                    <th>Produk</th>
                                    <th width="120">Total</th>
                                    <th width="100">Status</th>
                                    <th width="120">Tanggal</th>
                                    <th width="100">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($histori_paginated as $transaksi): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">#<?= $transaksi['transaksi_id'] ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <?php if (!empty($transaksi['produk_names'])): ?>
                                                <strong><?= safeHtml($transaksi['produk_names']) ?></strong>
                                                <br><small class="text-muted">
                                                    <i class="fas fa-box me-1"></i>
                                                    <?= $transaksi['jumlah_produk'] ?> produk
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">Produk tidak tersedia</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="text-success">
                                            <?= formatCurrency($transaksi['total_harga']) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?= statusBadge($transaksi['status']) ?>
                                    </td>
                                    <td>
                                        <span title="<?= formatDate($transaksi['tanggal_transaksi'], 'd/m/Y H:i') ?>">
                                            <?= formatDate($transaksi['tanggal_transaksi']) ?>
                                        </span>
                                        <br><small class="text-muted">
                                            <?= formatDate($transaksi['tanggal_transaksi'], 'H:i') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>modules/transaksi/detail.php?id=<?= $transaksi['transaksi_id'] ?>" 
                                               class="btn btn-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($transaksi['status'] !== 'selesai' && $transaksi['status'] !== 'batal'): ?>
                                                <a href="<?= BASE_URL ?>modules/transaksi/edit.php?id=<?= $transaksi['transaksi_id'] ?>" 
                                                   class="btn btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Menampilkan <?= $start + 1 ?> - 
                                <?= min($start + RECORDS_PER_PAGE, $total_records) ?> 
                                dari <?= $total_records ?> transaksi
                            </div>
                            
                            <?php 
                            $base_url = "histori.php?id={$pelanggan['id']}";
                            if (!empty($status_filter)) {
                                $base_url .= "&status=" . urlencode($status_filter);
                            }
                            $base_url .= "&page=";
                            echo generatePagination($current_page, $total_pages, $base_url);
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <?php if (!empty($histori)): ?>
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="h5 text-warning">
                            <?= count(array_filter($histori, fn($t) => $t['status'] === 'pending')) ?>
                        </div>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="h5 text-info">
                            <?= count(array_filter($histori, fn($t) => $t['status'] === 'diproses')) ?>
                        </div>
                        <small class="text-muted">Diproses</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="h5 text-success">
                            <?= count(array_filter($histori, fn($t) => $t['status'] === 'selesai')) ?>
                        </div>
                        <small class="text-muted">Selesai</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="h5 text-danger">
                            <?= count(array_filter($histori, fn($t) => $t['status'] === 'batal')) ?>
                        </div>
                        <small class="text-muted">Batal</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>