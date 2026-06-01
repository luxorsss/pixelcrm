<?php
$page_title = "Detail Transaksi";
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$id = (int)get('id');
if (!$id) {
    setMessage('ID transaksi tidak valid', 'error');
    redirect('index.php');
}

$transaksi = getTransaksiById($id);
if (!$transaksi) {
    setMessage('Transaksi tidak ditemukan', 'error');
    redirect('index.php');
}

$detail_items = getDetailTransaksi($id);

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content dashboard-wrapper">
    <div class="form-container" style="max-width: 1100px;">
        
        <div class="dash-header mb-4">
            <div>
                <a href="index.php" class="text-muted text-decoration-none fw-bold" style="font-size: 0.85rem;">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Transaksi
                </a>
                <h1 class="dash-title mt-2 d-flex align-items-center gap-3">
                    Order #<?= $transaksi['id'] ?>
                    <?php 
                        $s_bg = '#F3F4F6'; $s_col = '#6B7280'; $s_icon = 'fa-circle';
                        if($transaksi['status'] == 'selesai') { $s_bg = '#ECFDF5'; $s_col = '#059669'; $s_icon = 'fa-check-circle'; }
                        if($transaksi['status'] == 'pending') { $s_bg = '#FFFBEB'; $s_col = '#D97706'; $s_icon = 'fa-clock'; }
                        if($transaksi['status'] == 'diproses') { $s_bg = '#EFF6FF'; $s_col = '#2563EB'; $s_icon = 'fa-sync-alt'; }
                        if($transaksi['status'] == 'batal') { $s_bg = '#FEF2F2'; $s_col = '#EF4444'; $s_icon = 'fa-times-circle'; }
                    ?>
                    <span class="badge-clean" style="background: <?= $s_bg ?>; color: <?= $s_col ?>; font-size: 0.8rem; vertical-align: middle;">
                        <i class="fas <?= $s_icon ?> me-1"></i><?= ucfirst($transaksi['status']) ?>
                    </span>
                </h1>
            </div>
            
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="<?= BASE_URL ?>invoice.php?<?= !empty($transaksi['uuid']) ? 'uuid=' . $transaksi['uuid'] : 'id=' . $transaksi['id'] ?>" target="_blank" class="btn btn-dark fw-bold rounded-pill px-4">
                    <i class="fas fa-print me-2"></i> Cetak Invoice
                </a>
            </div>
        </div>

        <?php displaySessionMessage(); ?>

        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="panel-editorial p-0 overflow-hidden mb-4">
                    
                    <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 50px; height: 50px; background: #F3F4F6; color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800;">
                                <?= strtoupper(substr(clean($transaksi['nama_pelanggan']), 0, 1)) ?>
                            </div>
                            <div>
                                <div class="text-muted fw-bold" style="font-size: 0.75rem; text-transform: uppercase;">Pelanggan</div>
                                <div class="fw-bold text-dark fs-5" style="line-height: 1.2;"><?= safeHtml($transaksi['nama_pelanggan']) ?></div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <div class="text-muted fw-bold" style="font-size: 0.75rem; text-transform: uppercase;">Waktu Order</div>
                            <div class="fw-bold text-dark"><?= formatDate($transaksi['tanggal_transaksi'], 'd M Y') ?></div>
                            <div class="text-muted" style="font-size: 0.85rem;"><?= formatDate($transaksi['tanggal_transaksi'], 'H:i') ?> WIB</div>
                        </div>
                    </div>

                    <div class="p-3 bg-light d-flex flex-wrap gap-4 border-bottom" style="font-size: 0.85rem;">
                        <div>
                            <span class="text-muted fw-bold me-2">WhatsApp:</span>
                            <a href="<?= whatsappLink($transaksi['nomor_wa']) ?>" target="_blank" class="text-success text-decoration-none fw-bold">
                                <i class="fab fa-whatsapp me-1"></i><?= $transaksi['nomor_wa'] ?>
                            </a>
                        </div>
                        <div>
                            <span class="text-muted fw-bold me-2">Email:</span>
                            <?php if (!empty($transaksi['email'])): ?>
                                <a href="mailto:<?= safeHtml($transaksi['email']) ?>" class="text-primary text-decoration-none fw-bold">
                                    <i class="fas fa-envelope me-1"></i><?= safeHtml($transaksi['email']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted"><i class="fas fa-minus"></i> Tidak dicantumkan</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="p-4 bg-white">
                        <h6 class="fw-bold text-dark mb-4 text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.05em;">Rincian Item (<?= count($detail_items) ?>)</h6>
                        
                        <?php if (empty($detail_items)): ?>
                            <div class="text-center py-4 bg-light rounded-3">
                                <p class="text-muted mb-0">Tidak ada item dalam transaksi ini.</p>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($detail_items as $index => $item): ?>
                                    <div class="d-flex justify-content-between align-items-start pb-3 <?= $index < count($detail_items) - 1 ? 'border-bottom' : '' ?>" style="border-color: #F3F4F6 !important;">
                                        <div class="d-flex gap-3">
                                            <div style="width: 40px; height: 40px; background: #EFF6FF; color: #3B82F6; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                <i class="fas fa-box"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 1rem;"><?= safeHtml($item['nama_produk']) ?></div>
                                                <div class="text-muted" style="font-size: 0.8rem; line-height: 1.4; max-width: 400px;">
                                                    <?= safeHtml(truncateText($item['deskripsi'], 120)) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="fw-bold text-dark" style="font-size: 1rem;">
                                            <?= formatCurrency($item['harga']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 d-flex justify-content-between align-items-center" style="background: #FAFAFA; border-top: 2px dashed #E5E7EB;">
                        <span class="text-muted fw-bold text-uppercase" style="letter-spacing: 0.05em;">Total Pembayaran</span>
                        <span class="fw-extrabold text-success" style="font-size: 1.5rem; letter-spacing: -0.02em;"><?= formatCurrency($transaksi['total_harga']) ?></span>
                    </div>

                </div>
            </div>

            <div class="col-lg-4">
                
                <div class="panel-editorial sticky-top p-0 overflow-hidden" style="top: 2rem;">
                    
                    <div class="p-3 bg-white border-bottom text-center">
                        <h6 class="fw-bold text-dark m-0"><i class="fas fa-bolt text-warning me-2"></i> Control Panel</h6>
                    </div>

                    <div class="p-4" style="background: #F9FAFB;">
                        
                        <?php if ($transaksi['status'] === 'pending'): ?>
                            <div class="d-flex flex-column gap-2 mb-4">
                                <a href="update_status.php?id=<?= $transaksi['id'] ?>&status=selesai&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                                   class="btn-submit" style="background: #10B981;">
                                    <i class="fas fa-check-circle me-2"></i> Tandai Selesai (Lunas)
                                </a>
                                <a href="update_status.php?id=<?= $transaksi['id'] ?>&status=diproses&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                                   class="btn-submit" style="background: #3B82F6;">
                                    <i class="fas fa-sync-alt me-2"></i> Tandai Diproses
                                </a>
                            </div>
                        <?php elseif ($transaksi['status'] === 'diproses'): ?>
                            <div class="d-flex flex-column gap-2 mb-4">
                                <a href="update_status.php?id=<?= $transaksi['id'] ?>&status=selesai&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                                   class="btn-submit" style="background: #10B981;">
                                    <i class="fas fa-check-circle me-2"></i> Tandai Selesai (Lunas)
                                </a>
                            </div>
                        <?php elseif ($transaksi['status'] === 'selesai' && !empty($transaksi['waktu_selesai'])): ?>
                            <div class="bg-white border rounded-3 p-3 text-center mb-4 border-success">
                                <i class="fas fa-check-circle text-success fs-1 mb-2"></i>
                                <div class="fw-bold text-dark">Transaksi Berhasil</div>
                                <div class="text-muted" style="font-size: 0.8rem;">Divalidasi pada:<br><?= formatDate($transaksi['waktu_selesai'], 'd M Y H:i') ?> WIB</div>
                            </div>
                        <?php endif; ?>

                        <div class="text-muted fw-bold text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 0.05em;">Komunikasi & Edit</div>
                        <div class="d-flex flex-column gap-2">
                            <a href="<?= whatsappLink($transaksi['nomor_wa'], 'Halo ' . $transaksi['nama_pelanggan'] . ', terima kasih atas order Anda (#' . $transaksi['id'] . ').') ?>" 
                               target="_blank" class="btn btn-light border fw-bold text-dark text-start p-3" style="border-radius: 12px;">
                                <i class="fab fa-whatsapp text-success me-2 fs-5 align-middle"></i> Chat Pembeli
                            </a>

                            <?php if ($transaksi['status'] === 'pending'): ?>
                                <a href="edit.php?id=<?= $transaksi['id'] ?>" class="btn btn-light border fw-bold text-dark text-start p-3" style="border-radius: 12px;">
                                    <i class="fas fa-pen text-warning me-2 fs-5 align-middle"></i> Edit Data Order
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($transaksi['status'] !== 'batal'): ?>
                            <a href="update_status.php?id=<?= $transaksi['id'] ?>&status=batal&redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                               class="btn btn-light border fw-bold text-danger text-start p-3 mt-3" style="border-radius: 12px;"
                               onclick="return confirm('Yakin ingin MEMBATALKAN order ini? Omzet tidak akan dihitung.')">
                                <i class="fas fa-ban me-2 fs-5 align-middle"></i> Batalkan Order
                            </a>
                            <?php endif; ?>

                            <button type="button" class="btn btn-light border border-danger fw-bold text-danger text-start p-3" style="border-radius: 12px; background: #FEF2F2;"
                               onclick="showDeleteModal(<?= $transaksi['id'] ?>)">
                                <i class="fas fa-trash-alt me-2 fs-5 align-middle"></i> Hapus Permanen
                            </button>
                        </div>
                    </div>
                </div>
                
            </div>
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
                <h5 class="fw-bold text-dark mb-2">Hapus Order?</h5>
                <p class="text-muted small mb-4" style="line-height: 1.5;">
                    Yakin ingin menghapus permanen Order <strong id="deleteOrderId" class="text-dark"></strong>? 
                    Semua riwayat pembelian ini tidak akan bisa dikembalikan.
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