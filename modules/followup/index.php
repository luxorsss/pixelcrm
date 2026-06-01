<?php
// === LOGIC SECTION (No Output) ===
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

$page_title = "Followup Messages";

// Get filter
$produk_id = get('produk_id');
$products = getAllProducts();

// Get followup messages
if ($produk_id) {
    $followups = getFollowupMessages($produk_id);
    $selected_product = fetchRow("SELECT nama FROM produk WHERE id = ?", [$produk_id]);
    $total_records = count($followups);
} else {
    $followups = [];
    $selected_product = null;
    $total_records = 0;
}

// === PRESENTATION SECTION ===
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content dashboard-wrapper">
    <div class="dash-header flex-column flex-md-row align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="dash-title">Follow-up Sequence</h1>
            <div class="text-muted mt-1" style="font-weight: 500; font-size: 0.95rem;">Kelola urutan pesan WhatsApp otomatis untuk pelanggan pending.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= BASE_URL ?>monitor_followup.php" class="btn btn-light text-dark fw-bold border" style="border-radius: 12px;">
                <i class="fas fa-satellite-dish text-primary me-1"></i> Monitor Log
            </a>
            <?php if ($produk_id): ?>
                <a href="create.php?produk_id=<?= $produk_id ?>" class="btn btn-primary fw-bold" style="border-radius: 12px;">
                    <i class="fas fa-plus me-1"></i> Pesan Baru
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="w-100">
        
        <div class="list-container p-3 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3" style="background: #FFFBEB; border-color: #FDE68A;">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 40px; height: 40px; background: #FEF3C7; color: #D97706; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fas fa-filter"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark" style="font-size: 0.95rem;">Pilih Produk Target</div>
                    <div class="text-muted" style="font-size: 0.75rem;">Sequence bekerja per-produk.</div>
                </div>
            </div>
            
            <form method="GET" class="m-0 flex-grow-1" style="max-width: 400px;">
                <select name="produk_id" class="form-control-editorial fw-bold bg-white" style="appearance: auto; border-color: #FDE68A; cursor: pointer;" onchange="this.form.submit()">
                    <option value="">-- Silakan Pilih Produk --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= $product['id'] ?>" <?= $produk_id == $product['id'] ? 'selected' : '' ?>>
                            <?= clean($product['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if (!$produk_id): ?>
            <div class="text-center py-5">
                <div style="width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <i class="fas fa-hand-pointer text-muted fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Pilih Produk Terlebih Dahulu</h5>
                <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">Pilih salah satu produk di kotak filter atas untuk melihat dan mengatur urutan (Sequence) pesannya.</p>
            </div>
            
        <?php elseif (empty($followups)): ?>
            <div class="text-center py-5">
                <div style="width: 80px; height: 80px; background: #EFF6FF; color: #3B82F6; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <i class="fas fa-comments fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Belum Ada Follow-up</h5>
                <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">Automasi WhatsApp untuk <strong><?= clean($selected_product['nama']) ?></strong> masih kosong.</p>
                <a href="create.php?produk_id=<?= $produk_id ?>" class="btn btn-dark rounded-pill fw-bold px-4">
                    <i class="fas fa-plus me-2"></i>Buat Pesan Follow-up Ke-1
                </a>
            </div>
            
        <?php else: ?>
            <div class="product-list-container shadow-sm mb-4">
                <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h5 class="mb-0 fw-bold list-header m-0 p-0 text-dark">
                        <i class="fas fa-stream text-primary me-2"></i> Sequence untuk: <?= clean($selected_product['nama']) ?>
                    </h5>
                    <span class="badge bg-light text-muted border" style="font-size: 0.8rem;"><?= $total_records ?> Pesan</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table-editorial mb-0">
                        <thead>
                            <tr>
                                <th width="100" class="text-center">Sequence</th>
                                <th>Konten Pesan</th>
                                <th width="150">Jeda (Delay)</th>
                                <th width="120" class="text-center">Status</th>
                                <th width="150" class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($followups as $index => $followup): ?>
                            <tr>
                                <td class="text-center">
                                    <div style="width: 32px; height: 32px; background: #111827; color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem;">
                                        <?= $followup['urutan'] ?>
                                    </div>
                                    <?php if($index < count($followups) - 1): ?>
                                        <!-- Visual Connection Line -->
                                        <div style="width: 2px; height: 30px; background: #E5E7EB; margin: 5px auto -15px auto;"></div>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">
                                        <?= clean($followup['nama_pesan']) ?>
                                        <?php if ($followup['tipe_pesan'] === 'pesan_gambar'): ?>
                                            <span class="ms-2 badge-clean bg-light text-info border"><i class="fas fa-image me-1"></i>+Gambar</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.8rem; line-height: 1.4; max-width: 450px;">
                                        <?= truncateText(clean($followup['isi_pesan']), 70) ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <span class="badge-clean" style="background: #F3F4F6; color: #4B5563;">
                                        <i class="fas fa-stopwatch me-1 text-warning"></i> <?= formatDelay($followup['delay_value'], $followup['delay_unit']) ?>
                                    </span>
                                </td>
                                
                                <td class="text-center">
                                    <?php if ($followup['status'] === 'aktif'): ?>
                                        <span class="badge-clean" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;"><i class="fas fa-play-circle me-1"></i>Aktif</span>
                                    <?php else: ?>
                                        <span class="badge-clean" style="background: #FEF2F2; color: #EF4444; border: 1px solid #FCA5A5;"><i class="fas fa-pause-circle me-1"></i>Mati</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <!-- Tombol Preview yang sudah diperbaiki pakai data-attributes -->
                                        <button type="button" class="btn-action-icon embed btn-preview-wa" title="Lihat Tampilan WA"
                                                data-pesan="<?= htmlspecialchars($followup['isi_pesan'], ENT_QUOTES) ?>"
                                                data-gambar="<?= htmlspecialchars($followup['link_gambar'] ?? '', ENT_QUOTES) ?>"
                                                data-tipe="<?= $followup['tipe_pesan'] ?>">
                                            <i class="fas fa-mobile-alt"></i>
                                        </button>
                                        
                                        <a href="edit.php?id=<?= $followup['id'] ?>" class="btn-action-icon edit" title="Edit Pesan">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        
                                        <!-- Tombol Delete yang sudah diperbaiki pakai Modal -->
                                        <button type="button" class="btn-action-icon delete" title="Hapus Pesan"
                                                onclick="showDeleteModal(<?= $followup['id'] ?>, <?= $followup['urutan'] ?>)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- Custom Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 200ms cubic-bezier(0.16, 1, 0.3, 1);">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <div class="modal-body text-center p-4">
                <div style="width: 64px; height: 64px; background: #FEF2F2; color: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="fas fa-trash-alt" style="font-size: 1.75rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Hapus Sequence?</h5>
                <p class="text-muted small mb-4" style="line-height: 1.5;">
                    Yakin ingin menghapus pesan urutan ke-<strong id="deleteSequenceUrutan" class="text-dark"></strong>? 
                    Alur follow-up kamu akan terputus.
                </p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light w-50 fw-bold" data-bs-dismiss="modal" style="border-radius: 12px;">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger w-50 fw-bold" style="border-radius: 12px; background: #EF4444; border: none;">Hapus</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview WhatsApp Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 200ms cubic-bezier(0.16, 1, 0.3, 1); max-width: 400px;">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); background: #E5DDD5;">
            
            <div class="modal-header border-0 pb-0 d-flex justify-content-between align-items-center" style="background: #075E54; border-radius: 24px 24px 0 0; padding: 1rem 1.5rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fab fa-whatsapp text-white fs-4"></i>
                    <h5 class="modal-title text-white fw-bold m-0" style="font-size: 1rem;">WhatsApp Preview</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4" style="min-height: 300px;">
                <div class="bg-white p-3 shadow-sm position-relative" style="border-radius: 0 12px 12px 12px !important; margin-left: 10px;">
                    <!-- Ekor balon chat -->
                    <div style="position: absolute; top: 0; left: -8px; width: 0; height: 0; border-top: 10px solid white; border-left: 10px solid transparent;"></div>
                    
                    <div id="previewContent" style="font-size: 0.9rem; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word; color: #111827;"></div>
                    
                    <div class="text-end mt-1 text-muted" style="font-size: 0.65rem;">
                        <?= date('H:i') ?> <i class="fas fa-check-double text-info ms-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Event listener yang bersih untuk tombol Preview WA
    document.querySelectorAll('.btn-preview-wa').forEach(btn => {
        btn.addEventListener('click', function() {
            const message = this.getAttribute('data-pesan');
            const image = this.getAttribute('data-gambar');
            const type = this.getAttribute('data-tipe');
            showPreview(message, image, type);
        });
    });

    // UX Feedback saat hapus ditekan
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if(confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Proses...';
            this.style.opacity = '0.8';
            this.style.pointerEvents = 'none';
        });
    }
});

function showDeleteModal(id, urutan) {
    document.getElementById('deleteSequenceUrutan').textContent = urutan;
    document.getElementById('confirmDeleteBtn').href = 'delete.php?id=' + id;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

function showPreview(message, image, type) {
    // Simulasi data pelanggan untuk preview
    const sampleData = {
        '[nama]': 'Budi Santoso',
        '[produk]': '<?= clean($selected_product['nama'] ?? 'Produk Saya') ?>',
        '[harga]': 'Rp 150.000'
    };
    
    // Replace placeholder dgn data bohongan
    let previewMessage = message || '';
    Object.keys(sampleData).forEach(placeholder => {
        previewMessage = previewMessage.replace(new RegExp(placeholder.replace(/[\[\]]/g, '\\$&'), 'g'), sampleData[placeholder]);
    });
    
    // Konversi newline (\n) menjadi <br>
    let contentHtml = previewMessage.replace(/\n/g, '<br>');
    
    // Kalau ada gambar, sisipkan di atas pesan
    if (type === 'pesan_gambar' && image) {
        const imgTag = `<img src="${image}" class="img-fluid rounded mb-2 w-100" style="max-height: 250px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/300x200?text=Gambar+Rusak/Gagal';">`;
        contentHtml = imgTag + contentHtml;
    }
    
    document.getElementById('previewContent').innerHTML = contentHtml;
    
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    previewModal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>