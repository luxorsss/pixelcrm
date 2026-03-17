<?php
$page_title = "Kelola Transaksi";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Handle filters
$filters = [
    'status' => clean(get('status', '')),
    'date_from' => clean(get('date_from', '')),
    'date_to' => clean(get('date_to', '')),
    'search' => clean(get('search', ''))
];

// Remove empty filters
$clean_filters = array_filter($filters, function($value) {
    return $value !== '' && $value !== null;
});

// Mengambil semua transaksi tanpa pagination
$transaksi_list = getAllTransaksi(1, 1000, $clean_filters); // Mengambil maksimal 1000 transaksi
$total_records = count($transaksi_list);
$stats = getStatistikTransaksi();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Header -->
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-0">Kelola Transaksi</h1>
                <nav class="breadcrumb">
                    <a href="<?= BASE_URL ?>" class="breadcrumb-item text-decoration-none">Dashboard</a>
                    <span class="breadcrumb-item active">Transaksi</span>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="bulk.php" class="btn btn-success">
                    <i class="fas fa-upload me-2"></i>Bulk Import
                </a>
                <a href="bulk_delete_old.php" class="btn btn-outline-warning">
                    <i class="fas fa-trash-alt me-2"></i>Hapus Pesan
                </a>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Transaksi
                </a>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <?php displaySessionMessage(); ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <div class="h4 text-primary"><?= number_format($stats['total_transaksi']) ?></div>
                        <small class="text-muted">Total Transaksi</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <div class="h4 text-warning"><?= number_format($stats['status_pending']) ?></div>
                        <small class="text-muted">Pending</small>
                        <?php if ($stats['status_pending'] > 0): ?>
                            <br><a href="<?= buildFilterUrl(['status' => 'pending']) ?>" class="btn btn-outline-warning btn-sm mt-1">Lihat</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <div class="h4 text-success"><?= number_format($stats['status_selesai']) ?></div>
                        <small class="text-muted">Selesai</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center">
                        <div class="h4 text-success"><?= formatCurrency($stats['total_pendapatan']) ?></div>
                        <small class="text-muted">Total Pendapatan</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Transaksi</h6>
                    <?php if (!empty($clean_filters)): ?>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i>
                                Menampilkan <?= number_format($total_records) ?> dari <?= number_format($stats['total_transaksi']) ?> transaksi
                            </span>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Reset
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="diproses" <?= ($filters['status'] ?? '') === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                            <option value="selesai" <?= ($filters['status'] ?? '') === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="batal" <?= ($filters['status'] ?? '') === 'batal' ? 'selected' : '' ?>>Batal</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control" value="<?= $filters['date_from'] ?? '' ?>" onchange="this.form.submit()">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control" value="<?= $filters['date_to'] ?? '' ?>" onchange="this.form.submit()">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Cari Pelanggan</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Nama atau nomor WA..." value="<?= $filters['search'] ?? '' ?>">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Transaksi Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Daftar Transaksi 
                    <span class="badge bg-primary ms-2"><?= number_format($total_records) ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($transaksi_list)): ?>
                    <div class="text-center py-5">
                        <?php if (!empty($clean_filters)): ?>
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada transaksi sesuai filter</h5>
                            <p class="text-muted">Coba ubah kriteria pencarian atau filter.</p>
                            <a href="index.php" class="btn btn-outline-primary">Reset Filter</a>
                        <?php else: ?>
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada transaksi</h5>
                            <p class="text-muted">Mulai dengan membuat transaksi pertama.</p>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Buat Transaksi
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Tabel dengan scroll vertikal -->
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
							<thead class="table-light sticky-top">
								<tr>
									<th width="80">ID</th>
									<th>Pelanggan</th>
									<th width="120">Total</th>
									<th width="80">Items</th>
									<th width="100">Followup</th>
									<th width="120">Tanggal</th>
									<th width="180">Status & Aksi Cepat</th>
									<th width="120">Menu</th>
								</tr>
							</thead>
							<tbody>
								<?php 
								// Get followup progress untuk semua transaksi sekaligus (optimized)
								$transaksi_ids = array_column($transaksi_list, 'id');
								$followup_progress = getMultipleFollowupProgress($transaksi_ids);

								foreach ($transaksi_list as $transaksi): 
								?>
								<tr>
									<td>
										<span class="badge bg-light text-dark">#<?= $transaksi['id'] ?></span>
									</td>
									<td>
										<div>
											<strong><?= safeHtml($transaksi['nama_pelanggan']) ?></strong>
											<br>
											<small class="text-muted">
												<a href="<?= whatsappLink($transaksi['nomor_wa']) ?>" target="_blank" class="text-success text-decoration-none">
													<i class="fab fa-whatsapp me-1"></i><?= $transaksi['nomor_wa'] ?>
												</a>
											</small>
										</div>
									</td>
									<td>
										<strong class="text-success"><?= formatCurrency($transaksi['total_harga']) ?></strong>
									</td>
									<td>
										<span class="badge bg-info"><?= $transaksi['jumlah_item'] ?> item</span>
									</td>
									<td>
										<?php
										if ($transaksi['status'] === 'pending') {
											$progress = $followup_progress[$transaksi['id']] ?? [
												'total' => 0, 'terkirim' => 0, 'pending' => 0, 'gagal' => 0, 
												'progress_percent' => 0, 'status' => 'none'
											];
											echo renderFollowupProgressBadge($progress);
										} else {
											echo '<span class="badge bg-light text-muted">Status: ' . ucfirst($transaksi['status']) . '</span>';
										}
										?>
									</td>
									<td>
										<div><?= formatDate($transaksi['tanggal_transaksi']) ?></div>
										<small class="text-muted"><?= formatDate($transaksi['tanggal_transaksi'], 'H:i') ?></small>
										<?php if ($transaksi['status'] === 'selesai' && $transaksi['waktu_selesai']): ?>
											<br><small class="text-success">
												<i class="fas fa-check-circle me-1"></i>
												Selesai: <?= formatDate($transaksi['waktu_selesai'], 'd/m/Y H:i') ?>
											</small>
										<?php endif; ?>
									</td>
									<td>
										<?php
										$current_status = $transaksi['status'];
										// Build URL dengan filter untuk maintain state
										$url_params = $clean_filters;
										$query_suffix = !empty($url_params) ? '&' . http_build_query($url_params) : '';
										$base_url = "update_status.php?id={$transaksi['id']}&status=";
										?>

										<!-- Status Badge -->
										<div class="mb-1">
											<span class="badge fs-6 <?php
												switch($current_status) {
													case 'pending': echo 'bg-warning text-dark'; break;
													case 'diproses': echo 'bg-info'; break;
													case 'selesai': echo 'bg-success'; break;
													case 'batal': echo 'bg-danger'; break;
													default: echo 'bg-secondary';
												}
											?>">
												<?= ucfirst($current_status) ?>
											</span>
										</div>

										<!-- Quick Action Buttons -->
										<?php if ($current_status === 'pending'): ?>
											<div class="btn-group btn-group-sm d-flex flex-wrap gap-1">
												<a href="<?= $base_url ?>diproses<?= $query_suffix ?>" 
												   class="btn btn-outline-info btn-sm flex-fill" 
												   title="Set ke Diproses">
													<i class="fas fa-clock"></i>
												</a>
												<a href="<?= $base_url ?>selesai<?= $query_suffix ?>" 
												   class="btn btn-outline-success btn-sm flex-fill" 
												   title="Set ke Selesai">
													<i class="fas fa-check"></i>
												</a>
												<a href="<?= $base_url ?>batal<?= $query_suffix ?>" 
												   class="btn btn-outline-danger btn-sm flex-fill" 
												   title="Batalkan"
												   onclick="return confirm('Batalkan transaksi #<?= $transaksi['id'] ?>?')">
													<i class="fas fa-times"></i>
												</a>
											</div>
										<?php elseif ($current_status === 'diproses'): ?>
											<div class="btn-group btn-group-sm d-flex gap-1">
												<a href="<?= $base_url ?>selesai<?= $query_suffix ?>" 
												   class="btn btn-outline-success btn-sm flex-fill" 
												   title="Set ke Selesai">
													<i class="fas fa-check me-1"></i>Selesai
												</a>
												<a href="<?= $base_url ?>batal<?= $query_suffix ?>" 
												   class="btn btn-outline-danger btn-sm" 
												   title="Batalkan"
												   onclick="return confirm('Batalkan transaksi #<?= $transaksi['id'] ?>?')">
													<i class="fas fa-times"></i>
												</a>
											</div>
										<?php else: ?>
											<small class="text-muted">Status final</small>
										<?php endif; ?>
									</td>
									<td>
										<div class="btn-group-vertical btn-group-sm d-grid gap-1">
											<a href="detail.php?id=<?= $transaksi['id'] ?>" 
											   class="btn btn-outline-primary btn-sm" title="Detail">
												<i class="fas fa-eye me-1"></i>Detail
											</a>
											<?php if ($transaksi['status'] === 'pending'): ?>
												<a href="edit.php?id=<?= $transaksi['id'] ?>" 
												   class="btn btn-outline-warning btn-sm" title="Edit">
													<i class="fas fa-edit me-1"></i>Edit
												</a>
											<?php endif; ?>
											<a href="delete.php?id=<?= $transaksi['id'] ?>" 
											   class="btn btn-outline-danger btn-sm" 
											   onclick="return confirm('Hapus transaksi #<?= $transaksi['id'] ?>?')"
											   title="Hapus">
												<i class="fas fa-trash me-1"></i>Hapus
											</a>
										</div>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* CSS untuk header tabel yang tetap terlihat saat scroll */
.table-responsive thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
}
</style>

<script>
console.log('Simple buttons version loaded - no dropdown dependencies needed!');
console.log('Found status action buttons:', document.querySelectorAll('[href*="update_status.php"]').length);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>