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
<div class="main-content dashboard-wrapper">
    <div class="dash-header flex-column flex-md-row align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="dash-title">Semua Transaksi</h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Kelola order masuk, ubah status, dan pantau omzet.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="bulk.php" class="btn btn-light text-dark fw-bold border" style="border-radius: 12px;">
                <i class="fas fa-file-upload me-1"></i> Import Order
            </a>
            <a href="bulk_delete_old.php" class="btn btn-light text-danger fw-bold border" style="border-radius: 12px;">
                <i class="fas fa-trash-alt me-1"></i> Bersihkan Data
            </a>
            <a href="create.php" class="btn btn-primary fw-bold" style="border-radius: 12px;">
                <i class="fas fa-plus me-1"></i> Transaksi Baru
            </a>
        </div>
    </div>

    <div class="w-100">
        <?php displaySessionMessage(); ?>
        
        <div class="panel-editorial d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 p-3 px-4" style="background: var(--bg-surface);">
            
            <div class="d-flex align-items-center gap-3 pe-4 border-end">
                <div style="width: 44px; height: 44px; background: #F3F4F6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-receipt text-primary"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Total Order</div>
                    <div class="fw-bold text-dark fs-5" style="line-height: 1;"><?= number_format($stats['total_transaksi']) ?></div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3 pe-4 border-end">
                <div style="width: 44px; height: 44px; background: #FFFBEB; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-clock text-warning"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Menunggu</div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-dark fs-5" style="line-height: 1;"><?= number_format($stats['status_pending']) ?></span>
                        <?php if ($stats['status_pending'] > 0): ?>
                            <a href="<?= buildFilterUrl(['status' => 'pending']) ?>" class="badge-clean bg-light text-warning border border-warning" style="font-size: 0.65rem; padding: 0.15rem 0.5rem; text-decoration: none;">Cek <i class="fas fa-arrow-right ms-1"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3 pe-4 border-end">
                <div style="width: 44px; height: 44px; background: #ECFDF5; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Sukses</div>
                    <div class="fw-bold text-dark fs-5" style="line-height: 1;"><?= number_format($stats['status_selesai']) ?></div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div style="width: 44px; height: 44px; background: #EFF6FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-coins text-primary"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Total Pendapatan</div>
                    <div class="fw-bold text-success fs-5" style="line-height: 1;"><?= formatCurrency($stats['total_pendapatan']) ?></div>
                </div>
            </div>
            
        </div>
        
        <div class="list-container p-3 mb-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <form method="GET" class="d-flex flex-wrap align-items-center gap-2 m-0 w-100">
                
                <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1 border border-light flex-grow-1" style="max-width: 300px;">
                    <i class="fas fa-search text-muted me-2" style="font-size: 0.85rem;"></i>
                    <input type="text" name="search" class="form-control border-0 bg-transparent p-0 text-dark fw-bold" 
                           placeholder="Cari pelanggan..." value="<?= $filters['search'] ?? '' ?>" style="font-size: 0.85rem; outline: none; box-shadow: none;">
                </div>

                <div class="d-flex align-items-center bg-light rounded-pill px-3 py-1 border border-light">
                    <i class="fas fa-calendar-alt text-muted me-2" style="font-size: 0.85rem;"></i>
                    <input type="date" name="date_from" class="form-control border-0 bg-transparent p-0 text-muted fw-bold" style="width: 110px; font-size: 0.85rem;" value="<?= $filters['date_from'] ?? '' ?>" onchange="this.form.submit()">
                    <span class="mx-2 text-muted">-</span>
                    <input type="date" name="date_to" class="form-control border-0 bg-transparent p-0 text-muted fw-bold" style="width: 110px; font-size: 0.85rem;" value="<?= $filters['date_to'] ?? '' ?>" onchange="this.form.submit()">
                </div>

                <div class="bg-light rounded-pill px-3 py-1 border border-light d-flex align-items-center">
                    <select name="status" class="form-select border-0 bg-transparent p-0 text-dark fw-bold" style="width: auto; min-width: 110px; font-size: 0.85rem; cursor: pointer;" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="diproses" <?= ($filters['status'] ?? '') === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="selesai" <?= ($filters['status'] ?? '') === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="batal" <?= ($filters['status'] ?? '') === 'batal' ? 'selected' : '' ?>>Batal</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-dark btn-sm rounded-pill fw-bold px-3 ms-auto ms-lg-0" style="padding-top: 0.4rem; padding-bottom: 0.4rem;">
                    Cari
                </button>
            </form>
        </div>
        
        <div class="product-list-container shadow-sm mb-4">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                <h5 class="mb-0 fw-bold list-header m-0 p-1">
                    Histori Order Masuk
                    <span class="badge bg-light text-muted border ms-2" style="font-size: 0.75rem;"><?= number_format($total_records) ?> Data</span>
                </h5>
                <?php if (!empty($clean_filters)): ?>
                    <a href="index.php" class="text-danger text-decoration-none fw-bold" style="font-size: 0.8rem;"><i class="fas fa-times-circle me-1"></i>Reset Filter</a>
                <?php endif; ?>
            </div>

            <?php if (empty($transaksi_list)): ?>
                <div class="text-center py-5">
                    <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                        <i class="fas fa-shopping-basket text-muted fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Tidak Ada Transaksi</h5>
                    <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">Belum ada pesanan yang sesuai dengan filter yang kamu pilih.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive" style="max-height: calc(100vh - 280px); overflow-y: auto;">
                    <table class="table-editorial mb-0">
                        <thead class="sticky-top">
                            <tr>
                                <th width="60" class="text-center">#ID</th>
                                <th width="200">Klien / Pembeli</th>
                                <th width="150" class="text-end">Nominal Order</th>
                                <th>Item</th>
                                <th width="140">Waktu Order</th>
                                <th width="130" class="text-center">Status Final</th>
                                <th width="180" class="text-end pe-4">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Ambil progress dari variabel PHP asli kamu
                            $transaksi_ids = array_column($transaksi_list, 'id');
                            $followup_progress = getMultipleFollowupProgress($transaksi_ids);

                            foreach ($transaksi_list as $transaksi): 
                                $current_status = $transaksi['status'];
                                $url_params = $clean_filters;
                                $query_suffix = !empty($url_params) ? '&' . http_build_query($url_params) : '';
                                $base_url = "update_status.php?id={$transaksi['id']}&status=";
                            ?>
                            <tr>
                                <td class="text-center">
                                    <span class="fw-bold text-muted" style="font-size: 0.85rem;"><?= $transaksi['id'] ?></span>
                                </td>
                                
                                <td>
                                    <div class="fw-bold text-dark" style="font-size: 0.95rem; line-height: 1.2;"><?= safeHtml($transaksi['nama_pelanggan']) ?></div>
                                    <a href="<?= whatsappLink($transaksi['nomor_wa']) ?>" target="_blank" class="text-muted text-decoration-none mt-1 d-inline-block" style="font-size: 0.75rem; transition: var(--transition);">
                                        <i class="fab fa-whatsapp text-success me-1"></i><span class="hover-text-primary"><?= $transaksi['nomor_wa'] ?></span>
                                    </a>
                                </td>
                                
                                <td class="text-end">
                                    <div class="fw-bold text-success" style="font-size: 1.05rem;"><?= formatCurrency($transaksi['total_harga']) ?></div>
                                </td>
                                
                                <td>
                                    <span class="badge-clean bg-light text-muted border">
                                        <i class="fas fa-box me-1"></i> <?= $transaksi['jumlah_item'] ?>
                                    </span>
                                    
                                    <?php if ($current_status === 'pending'): ?>
                                        <div class="mt-2" style="transform: scale(0.9); transform-origin: left;">
                                            <?php 
                                            $progress = $followup_progress[$transaksi['id']] ?? [
                                                'total' => 0, 'terkirim' => 0, 'pending' => 0, 'gagal' => 0, 
                                                'progress_percent' => 0, 'status' => 'none'
                                            ];
                                            echo renderFollowupProgressBadge($progress); 
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <div class="text-dark fw-bold" style="font-size: 0.85rem;"><?= formatDate($transaksi['tanggal_transaksi'], 'd M Y') ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= formatDate($transaksi['tanggal_transaksi'], 'H:i') ?> WIB</div>
                                </td>
                                
                                <td class="text-center">
                                    <?php 
                                        $s_bg = '#F3F4F6'; $s_col = '#6B7280'; $s_icon = 'fa-circle';
                                        if($current_status == 'selesai') { $s_bg = '#ECFDF5'; $s_col = '#059669'; $s_icon = 'fa-check-circle'; }
                                        if($current_status == 'pending') { $s_bg = '#FFFBEB'; $s_col = '#D97706'; $s_icon = 'fa-clock'; }
                                        if($current_status == 'diproses') { $s_bg = '#EFF6FF'; $s_col = '#2563EB'; $s_icon = 'fa-sync-alt'; }
                                        if($current_status == 'batal') { $s_bg = '#FEF2F2'; $s_col = '#EF4444'; $s_icon = 'fa-times-circle'; }
                                    ?>
                                    <span class="badge-clean" style="background: <?= $s_bg ?>; color: <?= $s_col ?>; border: 1px solid <?= $s_bg ?>;">
                                        <i class="fas <?= $s_icon ?> me-1"></i><?= ucfirst($current_status) ?>
                                    </span>
                                </td>
                                
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                                        <a href="detail.php?id=<?= $transaksi['id'] ?>" class="btn-action-icon embed" title="Buka Detail">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                        
                                        <a href="<?= BASE_URL ?>invoice.php?<?= !empty($transaksi['uuid']) ? 'uuid=' . $transaksi['uuid'] : 'id=' . $transaksi['id'] ?>" target="_blank" class="btn-action-icon" style="background: #F9FAFB; color: #4B5563;" title="Cetak Invoice">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                        
                                        <?php if ($current_status === 'pending'): ?>
                                            <a href="<?= $base_url ?>diproses<?= $query_suffix ?>" class="btn-action-icon embed" style="background: #EFF6FF;" title="Tandai Sedang Diproses">
                                                <i class="fas fa-box-open"></i>
                                            </a>
                                            <a href="<?= $base_url ?>selesai<?= $query_suffix ?>" class="btn-action-icon checkout" style="background: #ECFDF5;" title="Tandai Sukses">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            
                                            <a href="edit.php?id=<?= $transaksi['id'] ?>" class="btn-action-icon edit" title="Edit Order">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                        <?php elseif ($current_status === 'diproses'): ?>
                                            <a href="<?= $base_url ?>selesai<?= $query_suffix ?>" class="btn-action-icon checkout" style="background: #ECFDF5;" title="Tandai Sukses">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn-action-icon delete" title="Hapus Permanen"
                                                onclick="showDeleteModal(<?= $transaksi['id'] ?>)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
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

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 200ms cubic-bezier(0.16, 1, 0.3, 1);">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <div class="modal-body text-center p-4">
                <div style="width: 64px; height: 64px; background: #FEF2F2; color: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="fas fa-trash-alt" style="font-size: 1.75rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Transaksi?</h5>
                <p class="text-muted small mb-4" style="line-height: 1.5;">
                    Yakin ingin menghapus Order <strong id="deleteOrderId" class="text-dark"></strong>? 
                    Ini akan menghilangkan riwayat omzet dan performa.
                </p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light w-50 fw-bold" data-bs-dismiss="modal" style="border-radius: 12px;">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger w-50 fw-bold" style="border-radius: 12px; background: #EF4444; border: none;">Hapus</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Hover animation untuk link wa di dalam tabel */
.hover-text-primary { transition: color 0.2s ease; }
a:hover .hover-text-primary { color: var(--success-color); text-decoration: underline; }
</style>

<script>
function showDeleteModal(id) {
    document.getElementById('deleteOrderId').textContent = '#' + id;
    document.getElementById('confirmDeleteBtn').href = 'delete.php?id=' + id;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Proses...';
    this.style.opacity = '0.8';
    this.style.pointerEvents = 'none';
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>