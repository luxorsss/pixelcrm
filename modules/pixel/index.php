<?php
$page_title = 'Master Pixel & Tracking';
require_once '../../includes/header.php';
include '../../includes/sidebar.php';
require_once 'functions.php';

$pixels = getAllPixels();
$totalPixels = count($pixels);
$activePixels = 0;
$totalLinkedProducts = 0;

foreach ($pixels as $p) {
    if ($p['is_active']) $activePixels++;
    $totalLinkedProducts += (int)$p['total_produk'];
}
?>

<div class="main-content dashboard-wrapper flex-grow-1">
    <div class="form-container" style="max-width: 1200px;">

        <div class="dash-header mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h1 class="dash-title d-flex align-items-center gap-2">
                    <i class="fas fa-chart-line text-primary"></i> Master Pixel & Tracking
                </h1>
                <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">
                    Kelola akun Meta Pixel & Conversion API (CAPI) terpusat untuk katalog produk Anda.
                </div>
            </div>
            <div>
                <a href="create.php" class="btn btn-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fas fa-plus me-2"></i> Tambah Pixel Baru
                </a>
            </div>
        </div>

        <!-- Metric summary cards -->
        <div class="panel-editorial metrics-strip d-flex flex-nowrap align-items-center gap-3 mb-4 p-3 px-4 overflow-auto hide-scrollbar" style="background: var(--bg-surface); white-space: nowrap;">
            <div class="d-flex align-items-center gap-3 pe-4 border-end" style="min-width: fit-content;">
                <div style="width: 44px; height: 44px; background: #EFF6FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fab fa-facebook text-primary"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Total Pixel</div>
                    <div class="fw-bold text-dark fs-5" style="line-height: 1;"><?= $totalPixels ?></div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3 pe-4 border-end" style="min-width: fit-content;">
                <div style="width: 44px; height: 44px; background: #ECFDF5; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Status Aktif</div>
                    <div class="fw-bold text-success fs-5" style="line-height: 1;"><?= $activePixels ?></div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3" style="min-width: fit-content;">
                <div style="width: 44px; height: 44px; background: #F3E8FF; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-box-open text-purple" style="color: #9333EA;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Produk Terhubung</div>
                    <div class="fw-bold fs-5" style="line-height: 1; color: #9333EA;"><?= $totalLinkedProducts ?></div>
                </div>
            </div>
        </div>

        <!-- Table Panel -->
        <div class="panel-editorial p-0 overflow-hidden mb-5">
            <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                <h3 class="panel-title m-0" style="font-size: 1rem;">
                    <i class="fas fa-list text-primary me-2"></i> Daftar Akun Pixel
                </h3>
                <span class="badge bg-light text-muted border px-3 py-2 rounded-pill fw-semibold">
                    <?= count($pixels) ?> Data Ditemukan
                </span>
            </div>

            <div class="table-responsive">
                <table class="table-editorial mb-0" style="min-width: 800px;">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 60px;" class="text-center">No</th>
                            <th>Nama & Pixel ID</th>
                            <th>Conversion API (CAPI)</th>
                            <th>Test Event Code</th>
                            <th class="text-center">Produk Terkait</th>
                            <th class="text-center">Status</th>
                            <th class="text-end" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pixels)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="empty-state-icon mx-auto mb-3">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Belum Ada Akun Pixel</h6>
                                    <p class="text-muted small mb-3" style="max-width: 360px; margin: 0 auto;">Hubungkan Meta Pixel atau TikTok Pixel untuk melacak konversi checkout pelanggan secara otomatis.</p>
                                    <a href="create.php" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold">
                                        <i class="fas fa-plus me-1"></i> Hubungkan Pixel Pertama
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pixels as $index => $row): ?>
                                <tr>
                                    <td class="text-center text-muted fw-bold"><?= $index + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                                            <?= htmlspecialchars($row['nama']) ?>
                                        </div>
                                        <div class="text-muted font-monospace mt-1" style="font-size: 0.8rem;">
                                            <i class="fab fa-facebook me-1 text-primary"></i> ID: <strong><?= htmlspecialchars($row['meta_pixel_id']) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['conversion_api_token'])): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">
                                                <i class="fas fa-key me-1"></i> Terhubung
                                            </span>
                                            <div class="text-muted font-monospace mt-1" style="font-size: 0.72rem;">
                                                <?= htmlspecialchars(substr($row['conversion_api_token'], 0, 15)) ?>...
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border px-2 py-1 rounded-pill">
                                                <i class="fas fa-minus-circle me-1"></i> Kosong
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['test_event_code'])): ?>
                                            <span class="badge bg-warning-subtle text-dark border px-2 py-1 rounded-pill font-monospace" style="font-size: 0.75rem;">
                                                <?= htmlspecialchars($row['test_event_code']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.8rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ((int)$row['total_produk'] > 0): ?>
                                            <span class="badge bg-primary px-3 py-1 rounded-pill">
                                                <?= (int)$row['total_produk'] ?> Produk
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.8rem;">0 Produk</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['is_active']): ?>
                                            <span class="badge bg-success px-3 py-1 rounded-pill">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-3 py-1 rounded-pill">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-light border rounded-pill px-3" title="Edit">
                                                <i class="fas fa-edit text-dark"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 text-danger" 
                                                    onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama'])) ?>', <?= (int)$row['total_produk'] ?>)" 
                                                    title="Hapus">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <div class="modal-body p-4 text-center">
                <div style="width: 56px; height: 56px; background: #FEE2E2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
                    <i class="fas fa-exclamation-triangle text-danger fs-4"></i>
                </div>
                <h5 class="fw-bold mb-2">Hapus Akun Pixel?</h5>
                <p class="text-muted mb-3" id="deleteModalMessage">
                    Apakah Anda yakin ingin menghapus pixel ini?
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <a href="javascript:void(0)" id="deleteConfirmBtn" class="btn btn-danger rounded-pill px-4">Ya, Hapus</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name, totalProduk) {
    const msg = totalProduk > 0 
        ? `Pixel <strong>"${name}"</strong> saat ini terhubung ke <strong>${totalProduk} produk</strong>. Jika dihapus, relasi produk akan diubah menjadi tanpa pixel.`
        : `Apakah Anda yakin ingin menghapus pixel <strong>"${name}"</strong>?`;
    
    document.getElementById('deleteModalMessage').innerHTML = msg;
    document.getElementById('deleteConfirmBtn').href = `delete.php?id=${id}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>
