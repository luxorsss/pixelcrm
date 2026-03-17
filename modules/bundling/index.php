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

<div class="main-content">
    <div class="content-area">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-layer-group me-2"></i>Bundling Produk</h2>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Tambah Bundling
            </a>
        </div>

        <?php if (empty($bundlings)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                    <h5>Belum ada bundling produk</h5>
                    <p class="text-muted">Mulai buat bundling produk untuk meningkatkan penjualan</p>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Buat Bundling
                    </a>
                </div>
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
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daftar Bundling Produk (<?= count($grouped) ?> produk utama)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="40%">Produk Utama</th>
                                <th width="15%">Bundling</th>
                                <th width="20%">Total Diskon</th>
                                <th width="20%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($grouped as $produk_id => $data): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;">
                                                <i class="fas fa-star text-white"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?= clean($data['produk_utama']) ?></div>
                                                <small class="text-muted"><?= formatCurrency($data['harga_utama']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info fs-6"><?= $data['count'] ?> produk</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success fs-6"><?= formatCurrency($data['total_diskon']) ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit.php?produk_id=<?= $produk_id ?>" class="btn btn-warning" title="Kelola Bundling">
                                                <i class="fas fa-edit me-1"></i>Kelola
                                            </a>
                                            <button type="button" class="btn btn-danger" 
                                                    onclick="confirmDeleteAll(<?= $produk_id ?>, '<?= addslashes($data['produk_utama']) ?>')" 
                                                    title="Hapus Semua Bundling">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <?= getBundlingPagination($page, $total_pages) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete All Modal -->
<div class="modal fade" id="deleteAllModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus Semua Bundling</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin ingin menghapus <strong>SEMUA bundling</strong> untuk produk <strong id="deleteAllName"></strong>?</p>
                <div class="alert alert-danger">
                    <small><i class="fas fa-exclamation-triangle me-1"></i>Semua bundling terkait produk ini akan dihapus permanen!</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" id="deleteAllForm" class="d-inline">
                    <input type="hidden" name="delete_all_produk_id" id="deleteAllProdukId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Hapus Semua
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteAll(produkId, produkName) {
    document.getElementById('deleteAllProdukId').value = produkId;
    document.getElementById('deleteAllName').textContent = produkName;
    new bootstrap.Modal(document.getElementById('deleteAllModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>