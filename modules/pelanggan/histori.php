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
<div class="main-content dashboard-wrapper">
    <div class="dash-header flex-column flex-md-row align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Database Pelanggan
            </a>
            <h1 class="dash-title mt-2">Profil & Histori</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="edit.php?id=<?= $pelanggan['id'] ?>" class="btn btn-light text-dark fw-bold border" style="border-radius: 12px;">
                <i class="fas fa-pen me-1"></i> Edit Pelanggan
            </a>
        </div>
    </div>

    <div class="w-100">
        <?php displaySessionMessage(); ?>
        
        <div class="panel-editorial d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-4" style="padding: 2rem;">
            <div class="d-flex align-items-center gap-4">
                <div style="width: 72px; height: 72px; background: #EFF6FF; color: #3B82F6; border-radius: 24px; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; flex-shrink: 0;">
                    <?= strtoupper(substr(clean($pelanggan['nama']), 0, 1)) ?>
                </div>
                <div>
                    <h2 class="fw-bold text-dark mb-2" style="font-size: 1.5rem; letter-spacing: -0.02em;">
                        <?= safeHtml($pelanggan['nama']) ?>
                    </h2>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge-clean bg-light text-muted border">ID: <?= $pelanggan['id'] ?></span>
                        <a href="<?= whatsappLink($pelanggan['nomor_wa']) ?>" target="_blank" class="badge-wa badge-clean">
                            <i class="fab fa-whatsapp"></i> <?= $pelanggan['nomor_wa'] ?>
                        </a>
                        <span class="text-muted" style="font-size: 0.85rem; font-weight: 500;">
                            <i class="fas fa-calendar-alt me-1"></i> Bergabung <?= formatDate($pelanggan['tanggal_daftar'], 'd M Y') ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2 w-100 w-lg-auto">
                <a href="<?= whatsappLink($pelanggan['nomor_wa']) ?>" target="_blank" class="btn btn-success fw-bold flex-grow-1 flex-lg-grow-0" style="border-radius: 12px; padding: 0.75rem 1.25rem;">
                    <i class="fab fa-whatsapp me-2"></i> Chat
                </a>
                <a href="<?= BASE_URL ?>modules/transaksi/create.php?pelanggan_id=<?= $pelanggan['id'] ?>" class="btn btn-dark fw-bold flex-grow-1 flex-lg-grow-0" style="border-radius: 12px; padding: 0.75rem 1.25rem;">
                    <i class="fas fa-plus me-2"></i> Trx Baru
                </a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <div class="stat-icon" style="background: #EFF6FF; color: #2563EB;"><i class="fas fa-shopping-bag"></i></div>
                        <div class="stat-value text-primary"><?= $stats['total_transaksi'] ?></div>
                        <div class="stat-label">Total Transaksi</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <div class="stat-icon" style="background: #ECFDF5; color: #059669;"><i class="fas fa-wallet"></i></div>
                        <div class="stat-value text-success"><?= formatCurrency($stats['total_pembelian']) ?></div>
                        <div class="stat-label">Total Nilai Pembelian</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <div class="stat-icon" style="background: #F3E8FF; color: #9333EA;"><i class="fas fa-history"></i></div>
                        <div class="stat-value text-dark" style="font-size: 1.4rem;">
                            <?= $stats['transaksi_terakhir'] ? formatDate($stats['transaksi_terakhir'], 'd M Y') : '-' ?>
                        </div>
                        <div class="stat-label">
                            Transaksi Terakhir 
                            <?php if ($stats['transaksi_terakhir']): ?>
                                <span class="fw-normal text-muted ms-1">(<?= floor((time() - strtotime($stats['transaksi_terakhir'])) / 86400) ?> hari lalu)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="product-list-container shadow-sm mb-4">
            <div class="p-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h5 class="mb-0 fw-bold list-header m-0 p-1">
                    <i class="fas fa-receipt me-2 text-primary"></i> Histori Transaksi
                    <span class="badge bg-light text-dark border ms-2" style="font-size: 0.8rem;"><?= count($histori) ?></span>
                </h5>
                
                <form method="GET" class="d-flex align-items-center bg-light rounded-pill px-3 py-1 border border-light" style="width: auto;">
                    <input type="hidden" name="id" value="<?= $pelanggan['id'] ?>">
                    <i class="fas fa-filter text-muted me-2" style="font-size: 0.85rem;"></i>
                    <select name="status" class="form-select border-0 bg-transparent p-0 text-dark fw-bold" style="font-size: 0.85rem; cursor: pointer; width: 130px;" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="diproses" <?= $status_filter === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="batal" <?= $status_filter === 'batal' ? 'selected' : '' ?>>Batal</option>
                    </select>
                    <?php if (!empty($status_filter)): ?>
                        <a href="histori.php?id=<?= $pelanggan['id'] ?>" class="text-danger ms-2 text-decoration-none" title="Reset Filter"><i class="fas fa-times-circle"></i></a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (empty($histori)): ?>
                <div class="text-center py-5">
                    <?php if (!empty($status_filter)): ?>
                        <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                            <i class="fas fa-filter text-muted fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Status "<?= ucfirst($status_filter) ?>" Kosong</h5>
                        <p class="text-muted">Tidak ada transaksi yang cocok dengan filter status ini.</p>
                        <a href="histori.php?id=<?= $pelanggan['id'] ?>" class="btn btn-dark rounded-pill px-4 fw-bold">Lihat Semua Transaksi</a>
                    <?php else: ?>
                        <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                            <i class="fas fa-shopping-basket text-muted fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Belum Ada Transaksi</h5>
                        <p class="text-muted">Pelanggan ini belum melakukan pembelian apapun.</p>
                        <a href="<?= BASE_URL ?>modules/transaksi/create.php?pelanggan_id=<?= $pelanggan['id'] ?>" class="btn btn-primary rounded-pill px-4 fw-bold">
                            <i class="fas fa-plus me-2"></i>Buat Transaksi
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-editorial mb-0">
                        <thead>
                            <tr>
                                <th width="100" class="text-center">#ID</th>
                                <th>Item Pembelian</th>
                                <th width="150">Tanggal</th>
                                <th width="150" class="text-end">Nominal</th>
                                <th width="120" class="text-center">Status</th>
                                <th width="100" class="text-end pe-4">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($histori_paginated as $transaksi): ?>
                            <tr>
                                <td class="text-center">
                                    <span class="fw-bold text-muted" style="font-size: 0.85rem;">#<?= $transaksi['transaksi_id'] ?></span>
                                </td>
                                
                                <td>
                                    <?php if (!empty($transaksi['produk_names'])): ?>
                                        <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= safeHtml($transaksi['produk_names']) ?></div>
                                        <div class="text-muted mt-1" style="font-size: 0.8rem;">
                                            <i class="fas fa-box me-1"></i> <?= $transaksi['jumlah_produk'] ?> produk dibeli
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Produk tidak tersedia</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="text-dark fw-bold" style="font-size: 0.85rem;"><?= formatDate($transaksi['tanggal_transaksi'], 'd M Y') ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= formatDate($transaksi['tanggal_transaksi'], 'H:i') ?> WIB</div>
                                </td>
                                
                                <td class="text-end">
                                    <div class="fw-bold text-dark" style="font-size: 1rem;"><?= formatCurrency($transaksi['total_harga']) ?></div>
                                </td>
                                
                                <td class="text-center">
                                    <div style="transform: scale(0.9);">
                                        <?= statusBadge($transaksi['status']) ?>
                                    </div>
                                </td>
                                
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="<?= BASE_URL ?>modules/transaksi/detail.php?id=<?= $transaksi['transaksi_id'] ?>" 
                                           class="btn-action-icon embed" title="Lihat Detail Transaksi">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="p-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-3" style="background: #F9FAFB;">
                        <div class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.05em;">
                            Data <?= $start + 1 ?> - <?= min($start + RECORDS_PER_PAGE, $total_records) ?> dari <?= $total_records ?>
                        </div>
                        <div class="d-flex gap-1">
                            <?php 
                            $base_url = "histori.php?id={$pelanggan['id']}";
                            if (!empty($status_filter)) {
                                $base_url .= "&status=" . urlencode($status_filter);
                            }
                            $base_url .= "&page=";
                            echo generatePagination($current_page, $total_pages, $base_url);
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($histori)): ?>
        <div class="panel-editorial p-3 d-flex flex-wrap justify-content-center justify-content-md-between align-items-center gap-3">
            <div class="text-muted fw-bold" style="font-size: 0.85rem; text-transform: uppercase;">Total Transaksi:</div>
            
            <div class="d-flex flex-wrap gap-3">
                <div class="badge-clean bg-light text-dark border" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
                    <span class="text-success me-2"><i class="fas fa-check-circle"></i></span> 
                    <strong><?= count(array_filter($histori, fn($t) => $t['status'] === 'selesai')) ?></strong> Selesai
                </div>
                <div class="badge-clean bg-light text-dark border" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
                    <span class="text-info me-2"><i class="fas fa-sync-alt"></i></span> 
                    <strong><?= count(array_filter($histori, fn($t) => $t['status'] === 'diproses')) ?></strong> Diproses
                </div>
                <div class="badge-clean bg-light text-dark border" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
                    <span class="text-warning me-2"><i class="fas fa-clock"></i></span> 
                    <strong><?= count(array_filter($histori, fn($t) => $t['status'] === 'pending')) ?></strong> Pending
                </div>
                <div class="badge-clean bg-light text-dark border" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
                    <span class="text-danger me-2"><i class="fas fa-times-circle"></i></span> 
                    <strong><?= count(array_filter($histori, fn($t) => $t['status'] === 'batal')) ?></strong> Batal
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>