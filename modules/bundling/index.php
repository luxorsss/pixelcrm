<?php
$page_title = "Bundling Produk";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/functions.php';

// Pagination
$page = max(1, (int)get('page', 1));
$limit = 10;
$total = countBundling();
$total_pages = ceil($total / $limit);

// Get bundling data
$bundlings = getBundlingList($page, $limit);

// Handle quick delete all bundling for a product
if (isPost() && post('delete_all_produk_id')) {
    $produk_id = (int)post('delete_all_produk_id');
    if (deleteAllBundlingByProduct($produk_id)) {
        setMessage('Semua bundling berhasil dihapus', 'success');
    } else {
        setMessage('Gagal menghapus bundling', 'error');
    }
    redirect('index.php');
}
?>

<div class="main-content dashboard-wrapper">
    <!-- Header -->
    <div class="dash-header flex-column flex-md-row align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="dash-title">Paket Bundling</h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Kelola penawaran spesial dan diskon gabungan produk.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="create.php" class="btn btn-primary d-flex align-items-center gap-2" style="border-radius: 12px; font-weight: 700; padding: 0.75rem 1.25rem;">
                <i class="fas fa-plus"></i> Tambah Bundling
            </a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="w-100">
        <?php if (empty($bundlings)): ?>
            <div class="list-container text-center py-5 shadow-sm">
                <div class="mb-3">
                    <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fas fa-layer-group text-muted fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-1">Belum Ada Bundling</h5>
                <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">Buat penawaran paket bundling pertamamu untuk menarik pelanggan dan meningkatkan omzet penjualan.</p>
                <a href="create.php" class="btn btn-dark rounded-pill fw-bold px-4">
                    <i class="fas fa-plus me-2"></i>Buat Bundling Pertama
                </a>
            </div>
        <?php else: ?>
            <?php 
            // Group bundlings by main product for simpler display
            $grouped = [];
            foreach ($bundlings as $b) {
                if (!isset($grouped[$b['produk_id']])) {
                    $grouped[$b['produk_id']] = [
                        'produk_utama' => $b['produk_utama'],
                        'harga_utama' => $b['harga_utama'],
                        'count' => 0,
                        'total_diskon' => 0
                    ];
                }
                $grouped[$b['produk_id']]['count']++;
                $grouped[$b['produk_id']]['total_diskon'] += $b['diskon'];
            }
            ?>
            
            <div class="product-list-container shadow-sm mb-4">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <h5 class="mb-0 fw-bold list-header m-0 p-0 text-dark">
                        Daftar Bundling Aktif
                        <span class="badge bg-light text-muted border ms-2" style="font-size: 0.8rem;"><?= count($grouped) ?> Produk Utama</span>
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table-editorial mb-0">
                        <thead>
                            <tr>
                                <th width="60" class="text-center">No</th>
                                <th>Produk Utama</th>
                                <th width="150" class="text-center">Total Varian</th>
                                <th width="220" class="text-end">Total Diskon Diberikan</th>
                                <th width="150" class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($grouped as $produk_id => $data): ?>
                                <tr>
                                    <td class="text-center text-muted fw-bold" style="font-size: 0.85rem;"><?= $no++ ?></td>
                                    
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div style="width: 44px; height: 44px; background: #FFFBEB; color: #F59E0B; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem;">
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= clean($data['produk_utama']) ?></div>
                                                <div class="text-muted mt-1" style="font-size: 0.85rem; font-weight: 600;">
                                                    <?= formatCurrency($data['harga_utama']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="text-center">
                                        <span class="badge-clean" style="background: #EFF6FF; color: #2563EB;">
                                            <?= $data['count'] ?> Item
                                        </span>
                                    </td>
                                    
                                    <td class="text-end">
                                        <span class="badge-clean" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;">
                                            <?= formatCurrency($data['total_diskon']) ?>
                                        </span>
                                    </td>
                                    
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="edit.php?produk_id=<?= $produk_id ?>" class="btn-action-icon edit" title="Kelola Bundling">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button type="button" class="btn-action-icon delete" title="Hapus Semua Bundling"
                                                    onclick="confirmDeleteAll(<?= $produk_id ?>, '<?= addslashes(safeHtml($data['produk_utama'])) ?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
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
                            Halaman <?= $page ?> dari <?= $total_pages ?>
                        </div>
                        <div class="d-flex gap-1">
                            <?= getBundlingPagination($page, $total_pages) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Custom Delete All Modal -->
<div class="modal fade" id="deleteAllModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <div class="modal-body text-center p-4">
                <div style="width: 64px; height: 64px; background: #FEF2F2; color: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="fas fa-trash-alt" style="font-size: 1.75rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Semua Bundling?</h5>
                <p class="text-muted small mb-4" style="line-height: 1.5;">
                    Yakin ingin menghapus seluruh varian bundling untuk <strong id="deleteAllName" class="text-dark"></strong>? 
                    Tindakan ini permanen dan tidak bisa dibatalkan.
                </p>
                
                <form method="POST" id="deleteAllForm" class="d-flex gap-2">
                    <input type="hidden" name="delete_all_produk_id" id="deleteAllProdukId">
                    <button type="button" class="btn btn-light w-50 fw-bold" data-bs-dismiss="modal" style="border-radius: 12px; transition: all 0.2s;">Batal</button>
                    <button type="submit" class="btn btn-danger w-50 fw-bold" id="confirmDeleteBtn" style="border-radius: 12px; background: #EF4444; border: none; transition: transform 0.2s;">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteAll(produkId, produkName) {
    document.getElementById('deleteAllProdukId').value = produkId;
    document.getElementById('deleteAllName').textContent = produkName;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteAllModal'));
    deleteModal.show();
}

// Micro-interaction untuk tombol submit
document.getElementById('deleteAllForm').addEventListener('submit', function() {
    const btn = document.getElementById('confirmDeleteBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Proses...';
    btn.style.opacity = '0.8';
    btn.style.pointerEvents = 'none';
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>