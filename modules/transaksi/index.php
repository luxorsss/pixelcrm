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
<div class="main-content dashboard-wrapper flex-grow-1">
    
    <div class="dash-header flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="dash-title d-flex align-items-center gap-2">
                <i class="fas fa-shopping-basket text-primary"></i> Data Transaksi
            </h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Kelola order masuk, ubah status, dan pantau omzet penjualan.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="bulk.php" class="btn btn-light text-dark fw-bold border rounded-pill" style="box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                <i class="fas fa-file-import me-1"></i> Import Order
            </a>
            <a href="bulk_delete_old.php" class="btn btn-light text-danger fw-bold border rounded-pill" style="box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                <i class="fas fa-broom me-1"></i> Bersihkan Data
            </a>
            <a href="create.php" class="btn btn-dark fw-bold rounded-pill" style="box-shadow: 0 4px 12px rgba(17, 24, 39, 0.15);">
                <i class="fas fa-plus me-1"></i> Order Manual
            </a>
        </div>
    </div>

    <?php displaySessionMessage(); ?>
    
    <div class="panel-editorial d-flex flex-wrap flex-md-nowrap justify-content-between align-items-center gap-3 mb-4 p-3 px-4 overflow-auto" style="background: var(--bg-surface); white-space: nowrap;">
        
        <div class="d-flex align-items-center gap-3 pe-4 border-end" style="min-width: fit-content;">
            <div style="width: 44px; height: 44px; background: #F3F4F6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-receipt text-primary"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Total Order</div>
                <div class="fw-bold text-dark fs-5" style="line-height: 1;"><?= number_format($stats['total_transaksi']) ?></div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 pe-4 border-end" style="min-width: fit-content;">
            <div style="width: 44px; height: 44px; background: #FFFBEB; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-clock text-warning"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Menunggu</div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="fw-bold text-dark fs-5" style="line-height: 1;"><?= number_format($stats['status_pending']) ?></span>
                    <?php if ($stats['status_pending'] > 0): ?>
                        <a href="<?= buildFilterUrl(['status' => 'pending']) ?>" class="badge-clean bg-white text-warning border border-warning" style="font-size: 0.65rem; padding: 0.15rem 0.5rem; text-decoration: none;">Cek <i class="fas fa-arrow-right ms-1"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 pe-4 border-end" style="min-width: fit-content;">
            <div style="width: 44px; height: 44px; background: #ECFDF5; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-check-circle text-success"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Selesai</div>
                <div class="fw-bold text-dark fs-5" style="line-height: 1;"><?= number_format($stats['status_selesai']) ?></div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3" style="min-width: fit-content;">
            <div style="width: 44px; height: 44px; background: #EFF6FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                <i class="fas fa-coins text-primary"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Total Pendapatan</div>
                <div class="fw-bold text-success fs-5" style="line-height: 1;"><?= formatCurrency($stats['total_pendapatan']) ?></div>
            </div>
        </div>
        
    </div>
    
    <div class="panel-editorial p-3 p-md-4 mb-4 d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
        <form method="GET" class="d-flex flex-wrap align-items-center gap-2 m-0 w-100">
            
            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-2 border border-light flex-grow-1" style="min-width: 250px; transition: var(--transition);" id="searchContainer">
                <i class="fas fa-search text-muted me-2" style="font-size: 0.85rem;"></i>
                <input type="text" name="search" class="form-control border-0 bg-transparent p-0 text-dark fw-bold" 
                        placeholder="Cari nama, invoice..." value="<?= $filters['search'] ?? '' ?>" autocomplete="off"
                        style="font-size: 0.9rem; outline: none; box-shadow: none;"
                        onfocus="document.getElementById('searchContainer').style.borderColor='#3B82F6'; document.getElementById('searchContainer').style.background='#ffffff';"
                        onblur="document.getElementById('searchContainer').style.borderColor='transparent'; document.getElementById('searchContainer').style.background='#f8f9fa';">
            </div>

            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-2 border border-light flex-grow-1 flex-md-grow-0" style="min-width: 280px;">
                <i class="fas fa-calendar-alt text-muted me-2" style="font-size: 0.85rem;"></i>
                <input type="date" name="date_from" class="form-control border-0 bg-transparent p-0 text-muted fw-bold" style="width: 110px; font-size: 0.85rem;" value="<?= $filters['date_from'] ?? '' ?>" onchange="this.form.submit()">
                <span class="mx-2 text-muted">-</span>
                <input type="date" name="date_to" class="form-control border-0 bg-transparent p-0 text-muted fw-bold" style="width: 110px; font-size: 0.85rem;" value="<?= $filters['date_to'] ?? '' ?>" onchange="this.form.submit()">
            </div>

            <div class="bg-light rounded-pill px-3 py-2 border border-light d-flex align-items-center flex-grow-1 flex-md-grow-0">
                <select name="status" class="form-select border-0 bg-transparent p-0 text-dark fw-bold" style="width: 100%; min-width: 120px; font-size: 0.85rem; cursor: pointer; box-shadow: none; outline: none;" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Menunggu</option>
                    <option value="diproses" <?= ($filters['status'] ?? '') === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                    <option value="selesai" <?= ($filters['status'] ?? '') === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="batal" <?= ($filters['status'] ?? '') === 'batal' ? 'selected' : '' ?>>Batal</option>
                </select>
            </div>

            <div class="d-flex gap-2 flex-grow-1 flex-md-grow-0 mt-2 mt-md-0">
                <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4 py-2 w-100" style="min-width: 100px;">
                    Cari
                </button>
                <?php if (!empty($clean_filters)): ?>
                    <a href="index.php" class="btn btn-light text-danger rounded-pill px-3 fw-bold border-0 py-2" title="Reset Filter">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <div class="panel-editorial p-0 overflow-hidden mb-5">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
            <h5 class="mb-0 fw-bold list-header m-0 p-1" style="font-size: 1.1rem;">
                <i class="fas fa-list text-primary me-2"></i>Histori Order Masuk
                <span class="badge bg-light text-muted border ms-2" style="font-size: 0.75rem; vertical-align: middle;"><?= number_format($total_records) ?> Data</span>
            </h5>
        </div>

        <?php if (empty($transaksi_list)): ?>
            <div class="text-center py-5">
                <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <i class="fas fa-shopping-basket text-muted fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Tidak Ada Transaksi</h5>
                <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">Belum ada pesanan yang sesuai dengan filter yang kamu pilih.</p>
                <?php if (!empty($clean_filters)): ?>
                    <a href="index.php" class="btn btn-dark rounded-pill fw-bold px-4">Hapus Filter Pencarian</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive" style="max-height: calc(100vh - 280px); overflow-y: auto;">
                <table class="table-editorial mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th width="8%" class="text-center">#ID</th>
                            <th width="22%">Klien / Pembeli</th>
                            <th width="15%" class="text-end">Nominal Order</th>
                            <th width="15%">Waktu Order</th>
                            <th width="15%" class="text-center">Status Final</th>
                            <th width="25%" class="text-end pe-4">Aksi / Manage</th>
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
                                <span class="badge-clean bg-light text-muted border fw-bold" style="font-family: monospace;">#<?= $transaksi['id'] ?></span>
                            </td>
                            
                            <td>
                                <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem; line-height: 1.2;"><?= safeHtml($transaksi['nama_pelanggan']) ?></div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge-clean bg-light text-muted border" style="font-size: 0.65rem;">
                                        <i class="fas fa-box"></i> <?= $transaksi['jumlah_item'] ?> item
                                    </span>
                                    <a href="<?= whatsappLink($transaksi['nomor_wa']) ?>" target="_blank" class="text-decoration-none hover-text-primary" style="font-size: 0.75rem; color: #059669; font-weight: 600;">
                                        <i class="fab fa-whatsapp me-1"></i><?= $transaksi['nomor_wa'] ?>
                                    </a>
                                </div>
                            </td>
                            
                            <td class="text-end">
                                <div class="fw-bold text-success" style="font-size: 1.05rem;"><?= formatCurrency($transaksi['total_harga']) ?></div>
                            </td>
                            
                            <td>
                                <div class="text-dark fw-bold" style="font-size: 0.85rem;"><i class="far fa-calendar-alt text-muted me-1"></i><?= formatDate($transaksi['tanggal_transaksi'], 'd M Y') ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><i class="far fa-clock text-muted me-1"></i><?= formatDate($transaksi['tanggal_transaksi'], 'H:i') ?> WIB</div>
                            </td>
                            
                            <td class="text-center">
                                <?php 
                                    $s_bg = '#F3F4F6'; $s_col = '#6B7280'; $s_icon = 'fa-circle';
                                    if($current_status == 'selesai') { $s_bg = '#ECFDF5'; $s_col = '#059669'; $s_icon = 'fa-check-circle'; }
                                    if($current_status == 'pending') { $s_bg = '#FFFBEB'; $s_col = '#D97706'; $s_icon = 'fa-clock'; }
                                    if($current_status == 'diproses') { $s_bg = '#EFF6FF'; $s_col = '#2563EB'; $s_icon = 'fa-sync-alt'; }
                                    if($current_status == 'batal') { $s_bg = '#FEF2F2'; $s_col = '#EF4444'; $s_icon = 'fa-times-circle'; }
                                ?>
                                <span class="badge-clean" style="background: <?= $s_bg ?>; color: <?= $s_col ?>; border: 1px solid <?= $s_bg ?>; width: 100%; justify-content: center;">
                                    <i class="fas <?= $s_icon ?> me-1"></i><?= ucfirst($current_status) ?>
                                </span>
                                
                                <?php if ($current_status === 'pending'): ?>
                                    <div class="mt-2 text-start" style="transform: scale(0.9); transform-origin: center;">
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
                            
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1 flex-nowrap">
                                    <a href="detail.php?id=<?= $transaksi['id'] ?>" class="btn-action-icon embed" title="Buka Detail" style="background: #F8FAFC;">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    
                                    <a href="<?= BASE_URL ?>invoice.php?<?= !empty($transaksi['uuid']) ? 'uuid=' . $transaksi['uuid'] : 'id=' . $transaksi['id'] ?>" target="_blank" class="btn-action-icon" style="background: #F8FAFC; color: #4B5563;" title="Cetak Invoice">
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

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 200ms cubic-bezier(0.16, 1, 0.3, 1);">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
            <div class="modal-body text-center p-4">
                <div style="width: 64px; height: 64px; background: #FEF2F2; color: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="fas fa-trash-alt" style="font-size: 1.75rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Transaksi?</h5>
                <p class="text-muted small mb-4" style="line-height: 1.5;">
                    Yakin ingin menghapus Order <strong id="deleteOrderId" class="text-dark"></strong>? 
                    Ini akan menghilangkan riwayat pendapatan pada laporan.
                </p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light w-50 fw-bold rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger w-50 fw-bold rounded-pill" style="background: #EF4444; border: none;">Hapus</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Hover animation untuk link wa di dalam tabel */
.hover-text-primary { transition: color 0.2s ease; }
a:hover .hover-text-primary { color: var(--info-color); text-decoration: underline; }
/* Override form inputs untuk mobile */
@media (max-width: 768px) {
    input[type="date"] { flex: 1; }
}
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