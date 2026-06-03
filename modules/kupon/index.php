<?php
require_once __DIR__ . '/../../includes/init.php';

// Pastikan user sudah login
if (!isLoggedIn()) {
    redirect(BASE_URL . 'login.php');
}

$page_title = "Kelola Kupon";

// Mengambil data kupon dari database memakai fungsi bawaan sistem (fetchAll)
$query = "SELECT k.*, p.nama as nama_produk 
          FROM kupon k 
          LEFT JOIN produk p ON k.produk_id = p.id 
          ORDER BY k.id DESC";
          
$kupons = fetchAll($query);
$total_records = count($kupons);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content dashboard-wrapper">
    <div class="dash-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="dash-title d-flex align-items-center gap-2">
                <i class="fas fa-ticket-alt text-primary"></i> Kelola Kupon Promo
            </h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Buat kode diskon untuk promosi dan tarik lebih banyak penjualan.</div>
        </div>
        <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
            <a href="create.php" class="btn btn-dark fw-bold rounded-pill px-4 shadow-sm">
                <i class="fas fa-plus"></i> Buat Kupon Baru
            </a>
        </div>
    </div>

    <div class="w-100">
        <?php if ($msg = getMessage()): ?>
            <div class="alert alert-editorial alert-dismissible fade show mb-4 d-flex justify-content-between align-items-center" 
                style="border-left-color: <?= $msg[1] === 'error' || $msg[1] === 'danger' ? 'var(--danger-color)' : 'var(--success-color)' ?>; background: <?= $msg[1] === 'error' || $msg[1] === 'danger' ? '#FEF2F2' : '#ECFDF5' ?>;">
                <div>
                    <i class="fas fa-<?= $msg[1] === 'error' || $msg[1] === 'danger' ? 'exclamation-circle text-danger' : 'check-circle text-success' ?> me-2 fs-5 align-middle"></i>
                    <span class="text-dark fw-bold" style="font-size: 0.95rem;"><?= $msg[0] ?></span>
                </div>
                <button type="button" class="btn-close position-relative p-0 m-0" data-bs-dismiss="alert" aria-label="Close" style="width: auto; height: auto; background: none;"><i class="fas fa-times text-muted"></i></button>
            </div>
        <?php endif; ?>
        
        <div class="product-list-container shadow-sm mb-4">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-white">
                <h5 class="mb-0 fw-bold list-header m-0 p-0 text-dark">
                    Daftar Kupon Aktif
                    <span class="badge bg-light text-muted border ms-2" style="font-size: 0.8rem;"><?= $total_records ?> Kupon</span>
                </h5>
            </div>

            <?php if (empty($kupons)): ?>
                <div class="text-center py-5">
                    <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                        <i class="fas fa-ticket-alt text-muted fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Belum Ada Kupon</h5>
                    <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">Mulai buat kode promo pertamamu untuk dibagikan kepada pelanggan.</p>
                    <a href="create.php" class="btn btn-dark rounded-pill fw-bold px-4">
                        <i class="fas fa-plus me-2"></i>Buat Promo Pertama
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-editorial mb-0">
                        <thead>
                            <tr>
                                <th>Kode Promo</th>
                                <th>Besaran Diskon</th>
                                <th>Target Produk</th>
                                <th class="text-center">Pemakaian</th>
                                <th width="160">Masa Berlaku</th>
                                <th width="100" class="text-center">Status</th>
                                <th width="120" class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kupons as $k): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width: 44px; height: 44px; background: #F3F4F6; color: var(--primary-color); border-radius: 12px; border: 1px dashed var(--border-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem; font-weight: 800;">
                                            <i class="fas fa-tag"></i>
                                        </div>
                                        <div class="fw-bold text-dark text-uppercase" style="font-size: 1.05rem; letter-spacing: 0.05em;">
                                            <?= clean($k['kode_kupon']) ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <td>
                                    <?php if($k['tipe_diskon'] == 'persentase'): ?>
                                        <span class="fw-bold text-primary" style="font-size: 1.1rem;"><?= $k['nilai_diskon'] ?>%</span>
                                        <?php if($k['max_potongan'] > 0): ?>
                                            <div class="text-muted mt-1" style="font-size: 0.75rem; font-weight: 600;">Max: <?= formatCurrency($k['max_potongan']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="fw-bold text-success" style="font-size: 1.05rem;"><?= formatCurrency($k['nilai_diskon']) ?></span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if ($k['nama_produk']): ?>
                                        <span class="badge-clean bg-light text-dark border">
                                            <i class="fas fa-box me-1 text-muted"></i> <?= clean($k['nama_produk']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-clean" style="background: #EFF6FF; color: #2563EB;">
                                            Semua Produk
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <?php 
                                        $kuota_asli = (float)$k['kuota'];
                                        $quota_percent = ($kuota_asli > 0) ? ($k['terpakai'] / $kuota_asli) * 100 : 0;
                                        $quota_color = $quota_percent >= 90 ? '#EF4444' : ($quota_percent >= 50 ? '#F59E0B' : '#10B981');
                                    ?>
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;">
                                        <?= $k['terpakai'] ?> <span class="text-muted fw-medium">/ <?= $k['kuota'] ?></span>
                                    </div>
                                    <div style="width: 100%; height: 4px; background: #F3F4F6; border-radius: 4px; overflow: hidden; margin-top: 6px; display: flex;">
                                        <div style="width: <?= $quota_percent ?>%; height: 100%; background: <?= $quota_color ?>; border-radius: 4px;"></div>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="text-dark fw-bold" style="font-size: 0.85rem;"><i class="fas fa-play text-success me-1"></i> <?= formatDate($k['tgl_mulai'], 'd/m/Y') ?></div>
                                    <div class="text-muted mt-1" style="font-size: 0.85rem; font-weight: 600;"><i class="fas fa-stop text-danger me-1"></i> <?= formatDate($k['tgl_selesai'], 'd/m/Y') ?></div>
                                </td>
                                
                                <td class="text-center">
                                    <?php if ($k['is_active'] == 1): ?>
                                        <span class="badge-clean" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;"><i class="fas fa-check-circle me-1"></i>Aktif</span>
                                    <?php else: ?>
                                        <span class="badge-clean" style="background: #F3F4F6; color: #6B7280; border: 1px solid #D1D5DB;">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="edit.php?id=<?= $k['id'] ?>" class="btn-action-icon edit" title="Edit Kupon">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <button type="button" class="btn-action-icon delete" title="Hapus Kupon"
                                                onclick="showDeleteModal(<?= $k['id'] ?>, '<?= addslashes(clean($k['kode_kupon'])) ?>')">
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
                    <i class="fas fa-ticket-alt" style="font-size: 1.75rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Kupon?</h5>
                <p class="text-muted small mb-4" style="line-height: 1.5;">
                    Yakin ingin menghapus kupon diskon <strong id="deleteKuponName" class="text-dark text-uppercase"></strong>? 
                    Pelanggan tidak akan bisa lagi menggunakan kode ini saat checkout.
                </p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light w-50 fw-bold" data-bs-dismiss="modal" style="border-radius: 12px;">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger w-50 fw-bold" style="border-radius: 12px; background: #EF4444; border: none;">Hapus</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showDeleteModal(id, kode) {
    document.getElementById('deleteKuponName').textContent = kode;
    document.getElementById('confirmDeleteBtn').href = 'delete.php?id=' + id;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Micro-interaction untuk tombol hapus
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Proses...';
    this.style.opacity = '0.8';
    this.style.pointerEvents = 'none';
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>